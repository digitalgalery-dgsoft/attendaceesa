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
use Illuminate\Support\Facades\DB;

class ImportDuluxStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dulux:import-stock
                            {--year=2026 : Tahun dataset (2026)}
                            {--month= : Bulan spesifik (1..7 atau 1,2,3)}
                            {--limit=0 : Batasi jumlah record yang diimpor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor dataset Laporan Stock End & Tinter Dulux 2026 dari chunks JSONL.gz ke database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: 2026);
        $monthOpt = (string) $this->option('month');
        $limit = (int) $this->option('limit');

        $this->info("=== Memulai Impor Dataset Dulux Stock End Tahun {$year} ===");

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

        // 2. Dapatkan Template Form RPT-DULUX-STOCK-END
        $template = ReportTemplate::where('code', 'RPT-DULUX-STOCK-END')->first();
        if (!$template) {
            $this->error("Template dengan kode 'RPT-DULUX-STOCK-END' tidak ditemukan.");
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

        // 4. Dapatkan Default Employee & Company untuk submitter & work locations
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

        // 6. Tentukan bulan yang akan diproses
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
            $chunkFile = sprintf('%s/stock_%04d_m%02d.jsonl.gz', $chunksDir, $year, $m);
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

            $batchSubmissions = [];
            $monthCount = 0;

            while (!gzeof($fp)) {
                $line = gzgets($fp);
                if ($line === false) {
                    break;
                }
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $row = json_decode($line, true);
                if (!$row || empty($row['submission_code'])) {
                    continue;
                }

                $sap = trim((string) ($row['sap'] ?? ''));
                $storeName = trim((string) ($row['store_name'] ?? ''));
                $area = trim((string) ($row['area'] ?? ''));
                $region = trim((string) ($row['region'] ?? ''));
                $rsmArea = trim((string) ($row['rsm_area'] ?? ''));
                $derp = trim((string) ($row['derp'] ?? ''));

                $workLocId = null;
                if ($sap !== '' && isset($locByCode[$sap])) {
                    $workLocId = $locByCode[$sap];
                } elseif ($storeName !== '' && isset($locByName[strtoupper($storeName)])) {
                    $workLocId = $locByName[strtoupper($storeName)];
                }

                // Normalisasi Akses Gudang
                $ketRaw = strtolower(trim((string) ($row['keterangan'] ?? '')));
                if (str_contains($ketRaw, 'no acces') || str_contains($ketRaw, 'no access')) {
                    $statusAkses = 'No Access (Toko Menolak Cek Fisik / Data Estimasi)';
                } elseif (str_contains($ketRaw, 'half acces') || str_contains($ketRaw, 'half access') || str_contains($ketRaw, 'limited')) {
                    $statusAkses = 'Half Access (Hanya Cek Rak Depan Toko)';
                } else {
                    $statusAkses = 'Full Access (Bisa Cek Rak & Gudang Toko Bebas)';
                }

                $brand = trim((string) ($row['brand'] ?? 'Dulux'));
                $produk = trim((string) ($row['produk'] ?? ''));
                $warna = trim((string) ($row['warna'] ?? ''));
                if ($warna === '' || strtoupper($warna) === 'ALL') {
                    $baseWarna = 'Ready Mix (Warna Jadi Pabrik)';
                } else {
                    $baseWarna = $warna;
                }

                $kemasanGalon = (float) ($row['kemasan_galon'] ?? 2.5);
                $qtyGalon = (int) ($row['qty_galon'] ?? 0);
                $kemasanPail = (float) ($row['kemasan_pail'] ?? 20.0);
                $qtyPail = (int) ($row['qty_pail'] ?? 0);
                $volumeLiter = (float) ($row['volume_liter'] ?? 0.0);
                $conf = (float) ($row['conf'] ?? 1.0);

                $subDate = $row['submission_date'] ?: sprintf('%04d-%02d-20 12:00:00', $year, $m);
                $verifDate = $row['tgl_catat'] ?: sprintf('%04d-%02d-20 17:00:00', $year, $m);

                $subData = [
                    'report_template_id' => $template->id,
                    'principal_id' => $duluxPrincipal->id,
                    'employee_id' => $defaultEmpId,
                    'work_location_id' => $workLocId,
                    'submission_code' => $row['submission_code'],
                    'status' => 'approved',
                    'submitted_at' => $subDate,
                    'verified_at' => $verifDate,
                    'created_at' => $subDate,
                    'updated_at' => $now,
                ];

                $valuesData = [
                    [
                        'field_name' => 'produk_stock_end',
                        'field_type' => 'product_select',
                        'value_text' => $produk,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'brand_cat',
                        'field_type' => 'dropdown',
                        'value_text' => $brand,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'base_warna',
                        'field_type' => 'dropdown',
                        'value_text' => $baseWarna,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'kemasan_galon',
                        'field_type' => 'number',
                        'value_text' => (string) $kemasanGalon,
                        'value_number' => $kemasanGalon,
                    ],
                    [
                        'field_name' => 'stok_qty_galon',
                        'field_type' => 'number',
                        'value_text' => (string) $qtyGalon,
                        'value_number' => $qtyGalon,
                    ],
                    [
                        'field_name' => 'kemasan_pail',
                        'field_type' => 'number',
                        'value_text' => (string) $kemasanPail,
                        'value_number' => $kemasanPail,
                    ],
                    [
                        'field_name' => 'stok_qty_pail',
                        'field_type' => 'number',
                        'value_text' => (string) $qtyPail,
                        'value_number' => $qtyPail,
                    ],
                    [
                        'field_name' => 'total_volume_stok_liter',
                        'field_type' => 'number',
                        'value_text' => (string) $volumeLiter,
                        'value_number' => $volumeLiter,
                    ],
                    [
                        'field_name' => 'konversi_faktor',
                        'field_type' => 'number',
                        'value_text' => (string) $conf,
                        'value_number' => $conf,
                    ],
                    [
                        'field_name' => 'kategori_tinter',
                        'field_type' => 'dropdown',
                        'value_text' => 'Tidak Ada Mesin / Non-Tinting',
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'tipe_tinter_warna',
                        'field_type' => 'dropdown',
                        'value_text' => 'Semua Warna Dramatone / Full Set',
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'qty_kaleng_tinta',
                        'field_type' => 'number',
                        'value_text' => '0',
                        'value_number' => 0,
                    ],
                    [
                        'field_name' => 'status_ketersediaan_tinter',
                        'field_type' => 'radio',
                        'value_text' => 'Stok Aman (Siap Oplos)',
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'status_akses_gudang',
                        'field_type' => 'radio',
                        'value_text' => $statusAkses,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'derp_member_id',
                        'field_type' => 'text',
                        'value_text' => $derp,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'keterangan_stok_toko',
                        'field_type' => 'textarea',
                        'value_text' => "Area: {$area} | Region: {$region} | RSM: {$rsmArea}",
                        'value_number' => null,
                    ],
                ];

                $batchSubmissions[] = [
                    'sub' => $subData,
                    'vals' => $valuesData,
                ];

                $monthCount++;
                $totalImported++;

                if (count($batchSubmissions) >= $batchSize) {
                    $this->flushBatch($batchSubmissions, $fieldIds, $fieldTypes, $now);
                    $batchSubmissions = [];
                    $this->output->write('.');
                }

                if ($limit > 0 && $totalImported >= $limit) {
                    break 2;
                }
            }

            if (!empty($batchSubmissions)) {
                $this->flushBatch($batchSubmissions, $fieldIds, $fieldTypes, $now);
                $batchSubmissions = [];
            }

            gzclose($fp);
            $this->info(PHP_EOL . "Month {$m} Completed! ({$monthCount} rows processed)");
        }

        $this->info(PHP_EOL . "=== Dulux Stock End Import Completed! Total {$totalImported} rows processed. ===");
        return 0;
    }

    /**
     * Flush batch submission & values ke database dengan aman.
     */
    private function flushBatch(array $batchSubmissions, array $fieldIds, array $fieldTypes, string $now): void
    {
        $codes = array_map(fn($item) => $item['sub']['submission_code'], $batchSubmissions);
        $existing = DB::table('report_submissions')
            ->whereIn('submission_code', $codes)
            ->pluck('id', 'submission_code')
            ->toArray();

        DB::transaction(function () use ($batchSubmissions, $fieldIds, $fieldTypes, $now, $existing) {
            foreach ($batchSubmissions as $item) {
                $code = $item['sub']['submission_code'];
                if (isset($existing[$code])) {
                    continue; // Skip sudah diimpor sebelumnya
                }

                $subId = DB::table('report_submissions')->insertGetId($item['sub']);

                $insertVals = [];
                foreach ($item['vals'] as $val) {
                    $fName = $val['field_name'];
                    if (isset($fieldIds[$fName])) {
                        $insertVals[] = [
                            'report_submission_id' => $subId,
                            'report_form_field_id' => $fieldIds[$fName],
                            'field_name' => $fName,
                            'field_type' => $val['field_type'] ?? ($fieldTypes[$fName] ?? 'text'),
                            'value_text' => $val['value_text'],
                            'value_number' => $val['value_number'],
                            'value_json' => null,
                            'created_at' => $item['sub']['submitted_at'],
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($insertVals)) {
                    DB::table('report_submission_values')->insert($insertVals);
                }
            }
        });
    }
}
