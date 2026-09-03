<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use App\Models\WorkLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportDuluxOosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dulux:import-oos
                            {--year=2026 : Tahun dataset (2026)}
                            {--month= : Bulan spesifik (1..7 atau 1,2,3)}
                            {--limit=0 : Batasi jumlah record yang diimpor}
                            {--clean : Hapus data OOS sebelumnya untuk template ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor dataset Laporan Out of Stock (OOS LSO & SSO) Dulux 2026 dari chunks JSONL.gz ke database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: 2026);
        $monthOpt = (string) $this->option('month');
        $limit = (int) $this->option('limit');
        $clean = (bool) $this->option('clean');

        $this->info("=== Memulai Impor Dataset Dulux Out of Stock (OOS) Tahun {$year} ===");

        // 1. Dapatkan Principal Dulux
        $duluxPrincipal = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->first();

        if (!$duluxPrincipal) {
            $this->error("Principal Dulux tidak ditemukan.");
            return 1;
        }

        $allDuluxIds = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->pluck('id')
            ->toArray();

        $this->info("Principal terpilih: {$duluxPrincipal->name} (ID: {$duluxPrincipal->id})");

        // 2. Dapatkan Template OOS
        $template = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if (!$template) {
            $template = ReportTemplate::where('code', 'LIKE', '%OOS%')->first();
        }

        if (!$template) {
            $this->error("Template Laporan OOS Dulux tidak ditemukan.");
            return 1;
        }

        $this->info("Template terpilih: {$template->name} (ID: {$template->id})");

        // 3. Mapping form fields
        $fields = ReportFormField::where('report_template_id', $template->id)->get();
        $fieldIds = [];
        $fieldTypes = [];
        foreach ($fields as $f) {
            $fieldIds[$f->field_name] = $f->id;
            $fieldTypes[$f->field_name] = $f->field_type;
        }

        $this->info("Form fields terdaftar: " . count($fieldIds) . " field.");

        // 3b. Handle Clean / Incomplete submission cleanup
        if ($clean) {
            $this->info("Membersihkan seluruh data OOS yang ada untuk template ID {$template->id}...");
            $existingSubIds = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->pluck('id')
                ->toArray();
            if (!empty($existingSubIds)) {
                foreach (array_chunk($existingSubIds, 2000) as $chunkIds) {
                    DB::table('report_submission_values')->whereIn('report_submission_id', $chunkIds)->delete();
                }
                foreach (array_chunk($existingSubIds, 2000) as $chunkIds) {
                    DB::table('report_submissions')->whereIn('id', $chunkIds)->delete();
                }
            }
            $this->info("Data lama berhasil dibersihkan.");
        } else {
            $orphanedDeleted = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->where('submission_code', 'LIKE', 'SUB-DULUX-OOS-%')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('report_submission_values')
                        ->whereColumn('report_submission_values.report_submission_id', 'report_submissions.id');
                })
                ->delete();

            if ($orphanedDeleted > 0) {
                $this->info("Membersihkan {$orphanedDeleted} submission tanpa values.");
            }
        }

        // 4. Dapatkan Default Employee & Company
        $companyId = Company::value('id') ?? 1;

        $defaultEmp = Employee::whereIn('principal_id', $allDuluxIds)->first();
        if (!$defaultEmp) {
            $defaultEmp = Employee::first();
        }
        $defaultEmpId = $defaultEmp ? $defaultEmp->id : null;

        // 5. Cache SELURUH WorkLocation (cepat dan lengkap)
        $allLocations = DB::table('work_locations')->select('id', 'name', 'code')->get();
        $locByName = [];
        $locByCode = [];
        foreach ($allLocations as $loc) {
            if ($loc->name) {
                $locByName[strtoupper(trim($loc->name))] = $loc->id;
            }
            if ($loc->code) {
                $locByCode[trim($loc->code)] = $loc->id;
            }
        }

        $this->info("Cache lokasi lengkap: " . count($locByCode) . " lokasi berkode, " . count($locByName) . " nama lokasi.");

        // 6. Tentukan bulan target
        $targetMonths = [];
        if (!empty($monthOpt)) {
            if (str_contains($monthOpt, '..')) {
                [$startM, $endM] = explode('..', $monthOpt);
                for ($m = (int) $startM; $m <= (int) $endM; $m++) {
                    $targetMonths[] = $m;
                }
            } elseif (str_contains($monthOpt, ',')) {
                $targetMonths = array_map('intval', explode(',', $monthOpt));
            } else {
                $targetMonths = [(int) $monthOpt];
            }
        } else {
            $targetMonths = range(1, 7);
        }

        $chunksDir = storage_path('app/dulux_data/chunks');
        $batchSize = 1000;
        $totalImported = 0;
        $now = now()->toDateTimeString();

        foreach ($targetMonths as $m) {
            $chunkFile = sprintf('%s/oos_%04d_m%02d.jsonl.gz', $chunksDir, $year, $m);
            if (!file_exists($chunkFile)) {
                $this->warn("Chunk file tidak ditemukan: {$chunkFile}, dilewati.");
                continue;
            }

            $this->info(PHP_EOL . "Memproses Bulan {$m} dari {$chunkFile}...");
            $fp = gzopen($chunkFile, 'rb');
            if (!$fp) {
                $this->error("Gagal membuka file: {$chunkFile}");
                continue;
            }

            // Dapatkan existing codes untuk bulan ini agar idempotent
            $prefix = sprintf("SUB-DULUX-OOS-%04d-M%02d-", $year, $m);
            $existingCodes = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->where('submission_code', 'LIKE', $prefix . '%')
                ->pluck('submission_code')
                ->flip()
                ->toArray();

            $this->info("Ditemukan " . count($existingCodes) . " submission yang sudah ada untuk Bulan {$m}.");

            $batchSubmissions = [];
            $batchValues = [];
            $monthCount = 0;

            while (!gzeof($fp)) {
                $line = gzgets($fp);
                if ($line === false) break;
                $line = trim($line);
                if ($line === '') continue;

                $row = json_decode($line, true);
                if (!$row || empty($row['submission_code'])) continue;

                $code = $row['submission_code'];
                if (isset($existingCodes[$code])) {
                    continue; // Skip already imported
                }

                $sap = trim((string) ($row['sap'] ?? ''));
                $storeName = trim((string) ($row['store_name'] ?? ''));
                $area = trim((string) ($row['area'] ?? ''));
                $region = trim((string) ($row['region'] ?? ''));

                $workLocId = null;
                if ($sap !== '' && isset($locByCode[$sap])) {
                    $workLocId = $locByCode[$sap];
                } elseif ($storeName !== '' && isset($locByName[strtoupper($storeName)])) {
                    $workLocId = $locByName[strtoupper($storeName)];
                } else {
                    $newLoc = WorkLocation::create([
                        'company_id' => $companyId,
                        'name' => $storeName ?: ('Toko Dulux ' . $sap),
                        'type' => 'client',
                        'latitude' => -6.2000000,
                        'longitude' => 106.8166667,
                        'radius_meter' => 100,
                        'code' => $sap ?: null,
                        'region' => $region ?: null,
                        'branch_name' => $area ?: null,
                        'category' => ($row['channel_toko'] === 'LSO') ? 'Modern Trade (LSO)' : 'Traditional Store (SSO)',
                        'principal_id' => $duluxPrincipal->id,
                        'is_active' => true,
                    ]);
                    $workLocId = $newLoc->id;
                    if ($storeName !== '') $locByName[strtoupper($storeName)] = $workLocId;
                    if ($sap !== '') $locByCode[$sap] = $workLocId;
                }

                $subDate = $row['submission_date'] ?? $now;

                $batchSubmissions[] = [
                    'submission_code' => $code,
                    'record_data' => [
                        'report_template_id' => $template->id,
                        'principal_id' => $duluxPrincipal->id,
                        'employee_id' => $defaultEmpId,
                        'work_location_id' => $workLocId,
                        'submission_code' => $code,
                        'status' => 'approved',
                        'submitted_at' => $subDate,
                        'verified_at' => $subDate,
                        'created_at' => $subDate,
                        'updated_at' => $now,
                    ],
                    'raw_values' => $row,
                ];

                $monthCount++;

                if (count($batchSubmissions) >= $batchSize || ($limit > 0 && $monthCount >= $limit)) {
                    $this->flushOosBatch($batchSubmissions, $fieldIds, $fieldTypes, $now);
                    $totalImported += count($batchSubmissions);
                    $this->output->write('.');
                    $batchSubmissions = [];

                    if ($limit > 0 && $monthCount >= $limit) {
                        break;
                    }
                }
            }

            if (!empty($batchSubmissions)) {
                $this->flushOosBatch($batchSubmissions, $fieldIds, $fieldTypes, $now);
                $totalImported += count($batchSubmissions);
                $this->output->write('.');
            }

            gzclose($fp);
            $this->info(PHP_EOL . "Selesai Bulan {$m}: {$monthCount} record diproses.");
        }

        // Hapus cache widget dan dashboard
        Cache::flush();

        $this->info(PHP_EOL . "=== IMPOR DULUX OOS SELESAI: Total {$totalImported} record berhasil dimasukkan! ===");
        return 0;
    }

    /**
     * Flush batch submission dan values ke database
     */
    protected function flushOosBatch(array &$batch, array $fieldIds, array $fieldTypes, string $now): void
    {
        if (empty($batch)) {
            return;
        }

        $records = [];
        $codes = [];
        foreach ($batch as $item) {
            $records[] = $item['record_data'];
            $codes[] = $item['submission_code'];
        }

        DB::table('report_submissions')->insert($records);

        // Ambil mapping ID berdasarkan submission_code
        $insertedMap = DB::table('report_submissions')
            ->whereIn('submission_code', $codes)
            ->pluck('id', 'submission_code')
            ->toArray();

        $valuesBatch = [];

        foreach ($batch as $item) {
            $code = $item['submission_code'];
            if (!isset($insertedMap[$code])) {
                continue;
            }

            $subId = $insertedMap[$code];
            $raw = $item['raw_values'];

            $isOos = !empty($raw['is_oos']);
            $statusKetersediaan = $isOos ? 'OOS Riil' : 'No OOS / Stok Lengkap';
            $channelLabel = ($raw['channel_toko'] === 'LSO') ? 'Modern Outlet / Toko Modern (LSO)' : 'Specialist Traditional Store (SSO)';

            $fieldValueMappings = [
                'channel_toko' => ['text' => $channelLabel, 'num' => null],
                'week' => ['text' => (string)($raw['week'] ?? ''), 'num' => !empty($raw['week']) ? (float)$raw['week'] : null],
                'produk_oos' => ['text' => $raw['product_name'] ?? '', 'num' => null],
                'base_warna_oos' => ['text' => $raw['base_color'] ?? '', 'num' => null],
                'kemasan_size_oos' => ['text' => $raw['kemasan'] ?? '', 'num' => null],
                'lama_oos_hari' => ['text' => (string)($raw['lama_oos_hari'] ?? 0), 'num' => (float)($raw['lama_oos_hari'] ?? 0)],
                'saran_qty_order' => ['text' => (string)($raw['saran_qty_order'] ?? 0), 'num' => (float)($raw['saran_qty_order'] ?? 0)],
                'alasan_oos' => ['text' => $raw['alasan_oos'] ?? '', 'num' => null],
                'status_ketersediaan' => ['text' => $statusKetersediaan, 'num' => null],
                'account_lso' => ['text' => $raw['account_lso'] ?? '', 'num' => null],
                'rsm_area' => ['text' => $raw['rsm_area'] ?? '', 'num' => null],
                'id_member_derp' => ['text' => $raw['id_member_derp'] ?? '', 'num' => null],
            ];

            foreach ($fieldValueMappings as $fieldName => $valData) {
                if ($valData['text'] === '' && $valData['num'] === null) {
                    continue;
                }
                if (!isset($fieldIds[$fieldName])) {
                    continue;
                }

                $fId = $fieldIds[$fieldName];
                $fType = $fieldTypes[$fieldName] ?? 'text';

                $valuesBatch[] = [
                    'report_submission_id' => $subId,
                    'report_form_field_id' => $fId,
                    'field_name' => $fieldName,
                    'field_type' => $fType,
                    'value_text' => $valData['text'] !== '' ? $valData['text'] : null,
                    'value_number' => $valData['num'],
                    'value_json' => null,
                    'created_at' => $item['record_data']['submitted_at'] ?? $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($valuesBatch)) {
            foreach (array_chunk($valuesBatch, 2000) as $chunk) {
                DB::table('report_submission_values')->insert($chunk);
            }
        }
    }
}
