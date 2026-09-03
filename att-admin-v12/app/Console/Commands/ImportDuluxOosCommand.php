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
                            {--limit=0 : Batasi jumlah record yang diimpor}';

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

        $this->info("=== Memulai Impor Dataset Dulux Out of Stock (OOS) Tahun {$year} ===");

        // 1. Dapatkan Principal Dulux
        $duluxPrincipal = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('subdomain', 'dulux')
            ->first();

        if (!$duluxPrincipal) {
            $duluxPrincipal = Principal::first();
        }

        if (!$duluxPrincipal) {
            $this->error('Principal tidak ditemukan di database.');
            return 1;
        }

        $allDuluxIds = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->pluck('id')
            ->toArray();
        if (!in_array($duluxPrincipal->id, $allDuluxIds)) {
            $allDuluxIds[] = $duluxPrincipal->id;
        }

        $this->info("Principal terpilih: {$duluxPrincipal->name} (ID: {$duluxPrincipal->id})");

        // 2. Dapatkan Template Form RPT-DULUX-OOS-SSO
        $template = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if (!$template) {
            $this->error("Template dengan kode 'RPT-DULUX-OOS-SSO' tidak ditemukan.");
            return 1;
        }

        $this->info("Template terpilih: {$template->title} (ID: {$template->id})");

        // 3. Mapping Form Fields
        $fields = ReportFormField::where('report_template_id', $template->id)->get();
        $fieldIds = [];
        $fieldTypes = [];
        foreach ($fields as $f) {
            $fieldIds[$f->field_name] = $f->id;
            $fieldTypes[$f->field_name] = $f->field_type;
        }

        $this->info("Form fields terdaftar: " . count($fieldIds) . " field.");

        // 4. Dapatkan Default Employee & Company
        $companyId = Company::value('id') ?? 1;

        $defaultEmp = Employee::whereIn('principal_id', $allDuluxIds)->first();
        if (!$defaultEmp) {
            $defaultEmp = Employee::first();
        }
        $defaultEmpId = $defaultEmp ? $defaultEmp->id : null;

        // 5. Cache WorkLocation berdasarkan SAP (code) dan Nama Toko
        $duluxLocations = WorkLocation::whereIn('principal_id', $allDuluxIds)
            ->orWhereNull('principal_id')
            ->get();

        $locByName = [];
        $locByCode = [];
        foreach ($duluxLocations as $loc) {
            $locByName[strtoupper(trim($loc->name))] = $loc->id;
            if ($loc->code) {
                $locByCode[trim($loc->code)] = $loc->id;
            }
        }

        $this->info("Cache lokasi awal: " . count($locByCode) . " lokasi dengan kode SAP.");

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
                        'company_id' => $companyId,
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
                    $this->flushOosBatch($batchSubmissions, $fieldIds, $now);
                    $totalImported += count($batchSubmissions);
                    $this->output->write('.');
                    $batchSubmissions = [];

                    if ($limit > 0 && $monthCount >= $limit) {
                        break;
                    }
                }
            }

            if (!empty($batchSubmissions)) {
                $this->flushOosBatch($batchSubmissions, $fieldIds, $now);
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
    protected function flushOosBatch(array &$batch, array $fieldIds, string $now): void
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
                'channel_toko' => $channelLabel,
                'week' => $raw['week'] ?? null,
                'produk_oos' => $raw['product_name'] ?? null,
                'base_warna_oos' => $raw['base_color'] ?? null,
                'kemasan_size_oos' => $raw['kemasan'] ?? null,
                'lama_oos_hari' => $raw['lama_oos_hari'] ?? 0,
                'saran_qty_order' => $raw['saran_qty_order'] ?? 0,
                'alasan_oos' => $raw['alasan_oos'] ?? null,
                'status_ketersediaan' => $statusKetersediaan,
                'account_lso' => $raw['account_lso'] ?? null,
                'rsm_area' => $raw['rsm_area'] ?? null,
                'id_member_derp' => $raw['id_member_derp'] ?? null,
            ];

            foreach ($fieldValueMappings as $fieldName => $val) {
                if ($val === null || $val === '' || !isset($fieldIds[$fieldName])) {
                    continue;
                }

                $fId = $fieldIds[$fieldName];
                $valuesBatch[] = [
                    'report_submission_id' => $subId,
                    'report_form_field_id' => $fId,
                    'value' => (string) $val,
                    'created_at' => $now,
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
