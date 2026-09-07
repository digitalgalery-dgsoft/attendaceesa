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

class ImportDuluxDailyMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dulux:import-daily-maintenance
                            {--year=2026 : Tahun dataset (2025 atau 2026)}
                            {--month= : Bulan spesifik (1..12 atau 1,2,3)}
                            {--limit=0 : Batasi jumlah record yang diimpor}
                            {--clean : Hapus data Daily Maintenance sebelumnya untuk template ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor dataset Laporan Daily Maintenance POST & Mesin Tinting Dulux dari chunks JSONL.gz ke database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: 2026);
        $monthOpt = (string) $this->option('month');
        $limit = (int) $this->option('limit');
        $clean = (bool) $this->option('clean');

        $this->info("=== Memulai Impor Dataset Dulux Daily Maintenance Tahun {$year} ===");

        // 1. Dapatkan Principal Dulux
        $duluxPrincipal = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('subdomain', 'dulux')
            ->first();

        if (!$duluxPrincipal) {
            $duluxPrincipal = Principal::create([
                'name' => 'PT ICI PAINTS INDONESIA (DULUX)',
                'code' => 'DULUX-ICI',
                'subdomain' => 'dulux',
                'is_active' => true,
            ]);
        }

        $allDuluxIds = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('subdomain', 'dulux')
            ->pluck('id')
            ->toArray();

        $this->info("Principal terpilih: {$duluxPrincipal->name} (ID: {$duluxPrincipal->id})");

        // 2. Dapatkan / Buat Template Daily Maintenance
        $template = ReportTemplate::where('code', 'RPT-DULUX-DAILY-MAINTENANCE')->first();
        if (!$template) {
            $template = ReportTemplate::where('code', 'LIKE', '%DAILY-MAINTENANCE%')->first();
        }

        if (!$template) {
            $template = ReportTemplate::create([
                'code' => 'RPT-DULUX-DAILY-MAINTENANCE',
                'principal_id' => $duluxPrincipal->id,
                'title' => 'Laporan Daily Maintenance POST & Mesin Tinting Dulux',
                'description' => 'Kartu harian pemeriksaan & perawatan mesin tinting (POST Maintenance), nozzle cleaning, kalibrasi, dan program Mix2Win di toko.',
                'category' => 'display',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]);
        }

        if (method_exists($template, 'principals')) {
            $template->principals()->sync($allDuluxIds);
        }

        $this->info("Template terpilih: {$template->title} (ID: {$template->id})");

        // 3. Mapping Form Fields
        $fieldsConfig = [
            'tipe_mesin_post' => ['label' => 'Tipe Mesin Tinting POST di Toko', 'type' => 'dropdown'],
            'no_mesin_post' => ['label' => 'Nomor Seri / No Mesin POST Dulux', 'type' => 'text'],
            'nama_dc' => ['label' => 'Nama DC / Petugas Maintenance', 'type' => 'text'],
            'nama_tl' => ['label' => 'Nama Team Leader', 'type' => 'text'],
            'kategori_toko' => ['label' => 'Kategori Toko', 'type' => 'text'],
            'status_nozzle_cleaning' => ['label' => 'Status Pemeriksaan Kebersihan Nozzle & Brush Cleaning', 'type' => 'radio'],
            'status_sirkulasi_tinter' => ['label' => 'Status Sirkulasi & Agitasi Pasta Tinter', 'type' => 'radio'],
            'status_software_komputer' => ['label' => 'Status Software Tinting Komputer & Database Formula Warna', 'type' => 'radio'],
            'status_program_mix2win' => ['label' => 'Status Partisipasi Program Mix2Win Toko', 'type' => 'radio'],
            'cek_isi_tinta' => ['label' => 'Cek Isi Tinta Tabung', 'type' => 'text'],
            'pembersihan_mesin' => ['label' => 'Pembersihan Permukaan Mesin Tinting', 'type' => 'text'],
            'pembersihan_shaker' => ['label' => 'Pembersihan Permukaan Mesin Shaker', 'type' => 'text'],
            'pembersihan_komputer' => ['label' => 'Pembersihan Permukaan Komputer Tinting', 'type' => 'text'],
            'foto_brush_cleaning' => ['label' => 'Foto Proses Brush Cleaning / Pembersihan Nozzle Mesin', 'type' => 'camera_photo'],
            'foto_mesin_tinting' => ['label' => 'Foto Mesin Tinting & Area Oplos Toko', 'type' => 'camera_photo'],
            'kesimpulan_maintenance' => ['label' => 'Kesimpulan Kondisi Mesin & Rekomendasi Maintenance', 'type' => 'textarea'],
        ];

        $fieldIds = [];
        $fieldTypes = [];
        $orderIdx = 1;
        foreach ($fieldsConfig as $fName => $cfg) {
            $f = ReportFormField::firstOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $fName],
                [
                    'field_label' => $cfg['label'],
                    'field_type' => $cfg['type'],
                    'is_required' => false,
                    'order_index' => $orderIdx++,
                ]
            );
            $fieldIds[$fName] = $f->id;
            $fieldTypes[$fName] = $f->field_type;
        }

        $this->info("Form fields terdaftar: " . count($fieldIds) . " field.");

        // 3b. Handle Clean
        if ($clean) {
            $this->info("Membersihkan data Daily Maintenance untuk template ID {$template->id} tahun {$year}...");
            $existingSubIds = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->where('submission_code', 'LIKE', "SUB-DULUX-MAINT-{$year}-%")
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
            // Clean orphaned submissions without values
            $orphanedDeleted = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->where('submission_code', 'LIKE', 'SUB-DULUX-MAINT-%')
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

        // 4. Default Employee & Company
        $companyId = Company::value('id') ?? 1;

        $defaultEmp = Employee::whereIn('principal_id', $allDuluxIds)->first();
        if (!$defaultEmp) {
            $defaultEmp = Employee::first();
        }
        $defaultEmpId = $defaultEmp ? $defaultEmp->id : null;

        // 5. Cache Work Locations
        $existingLocations = WorkLocation::whereIn('principal_id', $allDuluxIds)
            ->orWhereNull('principal_id')
            ->get();

        $locByName = [];
        $locByCode = [];
        foreach ($existingLocations as $loc) {
            $locByName[strtoupper(trim($loc->name))] = $loc->id;
            if (!empty($loc->code)) {
                $locByCode[trim($loc->code)] = $loc->id;
            }
        }

        $this->info("Cache lokasi: " . count($locByName) . " toko dimuat.");

        // 6. Target Bulan
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
            $targetMonths = ($year === 2026) ? range(1, 7) : range(1, 12);
        }

        $chunksDir = storage_path('app/dulux_data/chunks');
        $batchSize = 1000;
        $totalImported = 0;
        $now = now()->toDateTimeString();

        foreach ($targetMonths as $m) {
            $chunkFile = sprintf('%s/daily_maint_%04d_m%02d.jsonl.gz', $chunksDir, $year, $m);
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

            $prefix = sprintf("SUB-DULUX-MAINT-%04d-M%02d-", $year, $m);
            $existingCodes = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->where('submission_code', 'LIKE', $prefix . '%')
                ->pluck('submission_code')
                ->flip()
                ->toArray();

            $this->info("Ditemukan " . count($existingCodes) . " submission yang sudah ada untuk Bulan {$m}.");

            $batchSubmissions = [];
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
                    continue;
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
                    $this->flushMaintBatch($batchSubmissions, $fieldIds, $fieldTypes, $now);
                    $totalImported += count($batchSubmissions);
                    $this->output->write('.');
                    $batchSubmissions = [];

                    if ($limit > 0 && $monthCount >= $limit) {
                        break;
                    }
                }
            }

            if (!empty($batchSubmissions)) {
                $this->flushMaintBatch($batchSubmissions, $fieldIds, $fieldTypes, $now);
                $totalImported += count($batchSubmissions);
                $this->output->write('.');
            }

            gzclose($fp);
            $this->info(PHP_EOL . "Selesai Bulan {$m}: {$monthCount} record diproses.");
        }

        Cache::flush();

        $this->info(PHP_EOL . "=== IMPOR DULUX DAILY MAINTENANCE SELESAI: Total {$totalImported} record berhasil dimasukkan! ===");
        return 0;
    }

    /**
     * Flush batch submission dan values ke database
     */
    protected function flushMaintBatch(array &$batch, array $fieldIds, array $fieldTypes, string $now): void
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

            $fieldValueMappings = [
                'tipe_mesin_post' => ['text' => $raw['tipe_mesin_post'] ?? '', 'num' => null],
                'no_mesin_post' => ['text' => $raw['no_mesin_post'] ?? '', 'num' => null],
                'nama_dc' => ['text' => $raw['nama_dc'] ?? '', 'num' => null],
                'nama_tl' => ['text' => $raw['nama_tl'] ?? '', 'num' => null],
                'kategori_toko' => ['text' => $raw['kategori_toko'] ?? '', 'num' => null],
                'status_nozzle_cleaning' => ['text' => $raw['status_nozzle_cleaning'] ?? '', 'num' => null],
                'status_sirkulasi_tinter' => ['text' => $raw['status_sirkulasi_tinter'] ?? '', 'num' => null],
                'status_software_komputer' => ['text' => $raw['status_software_komputer'] ?? '', 'num' => null],
                'status_program_mix2win' => ['text' => $raw['status_program_mix2win'] ?? '', 'num' => null],
                'cek_isi_tinta' => ['text' => $raw['cek_isi_tinta'] ?? '', 'num' => null],
                'pembersihan_mesin' => ['text' => $raw['pembersihan_mesin'] ?? '', 'num' => null],
                'pembersihan_shaker' => ['text' => $raw['pembersihan_shaker'] ?? '', 'num' => null],
                'pembersihan_komputer' => ['text' => $raw['pembersihan_komputer'] ?? '', 'num' => null],
                'foto_brush_cleaning' => ['text' => $raw['foto_brush_cleaning'] ?? '', 'num' => null],
                'foto_mesin_tinting' => ['text' => $raw['foto_mesin_tinting'] ?? '', 'num' => null],
                'kesimpulan_maintenance' => ['text' => $raw['kesimpulan_maintenance'] ?? '', 'num' => null],
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
            foreach (array_chunk($valuesBatch, 1000) as $chunk) {
                DB::table('report_submission_values')->insert($chunk);
            }
        }
    }
}
