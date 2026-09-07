<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;
use App\Models\Principal;
use App\Models\WorkLocation;
use App\Models\Employee;
use App\Models\Company;

class ImportDuluxOfftakeCommand extends Command
{
    protected $signature = 'dulux:import-offtake {--year=2026 : Target year to import (2025 or 2026)} {--month= : Specific month to import (1-12)} {--limit=0 : Limit number of rows}';
    protected $description = 'Import Dulux Offtake dataset from JSONL chunks into Report Submissions';

    public function handle(): int
    {
        $targetYear = (int) ($this->option('year') ?: 2026);
        $this->info("=== Starting Dulux Offtake {$targetYear} Import ===");

        $chunksDir = storage_path('app/dulux_data/chunks');
        if (!is_dir($chunksDir)) {
            $this->error("Chunks directory not found: {$chunksDir}");
            return 1;
        }

        // 1. Locate Primary Dulux Principal & Template
        $duluxPrincipal = Principal::where('code', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
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

        $template = ReportTemplate::where('code', 'RPT-DULUX-OFFTAKE-01')->first();
        if (!$template) {
            $template = ReportTemplate::create([
                'code' => 'RPT-DULUX-OFFTAKE-01',
                'principal_id' => $duluxPrincipal->id,
                'title' => 'Laporan Offtake / Penjualan Harian & Bukti Nota Dulux',
                'description' => 'Pencatatan penjualan harian produk Dulux & Catylac, bukti nota penjualan, dan traffic customer di toko.',
                'category' => 'offtake',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
            ]);
        }

        // Ensure form fields exist
        $fieldsMap = [
            'produk_terjual' => ['field_label' => 'Pilih Produk Terjual (Dulux / Catylac)', 'field_type' => 'product_select'],
            'kemasan_galon' => ['field_label' => 'Kemasan Galon (Liter/Kg)', 'field_type' => 'dropdown'],
            'qty_galon' => ['field_label' => 'Jumlah Galon Terjual (Qty)', 'field_type' => 'number'],
            'kemasan_pail' => ['field_label' => 'Kemasan Pail (Liter/Kg)', 'field_type' => 'dropdown'],
            'qty_pail' => ['field_label' => 'Jumlah Pail Terjual (Qty)', 'field_type' => 'number'],
            'total_volume_liter' => ['field_label' => 'Estimasi Total Volume Penjualan (Liter)', 'field_type' => 'number'],
            'total_nilai_sales_rp' => ['field_label' => 'Total Nilai Penjualan (Rupiah)', 'field_type' => 'currency'],
            'tipe_customer' => ['field_label' => 'Tipe Pembeli / Customer', 'field_type' => 'radio'],
            'traffic_customer_datang' => ['field_label' => 'Jumlah Customer Datang ke Toko Hari Ini', 'field_type' => 'number'],
            'traffic_customer_beli_cat' => ['field_label' => 'Jumlah Customer yang Membeli Cat', 'field_type' => 'number'],
            'traffic_customer_beli_dulux' => ['field_label' => 'Jumlah Customer yang Membeli Dulux', 'field_type' => 'number'],
        ];

        $fieldIds = [];
        $orderIdx = 1;
        foreach ($fieldsMap as $name => $meta) {
            $f = ReportFormField::firstOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $name],
                [
                    'field_label' => $meta['field_label'],
                    'field_type' => $meta['field_type'],
                    'is_required' => false,
                    'order_index' => $orderIdx++,
                ]
            );
            $fieldIds[$name] = $f->id;
        }

        // 2. Cache Existing Work Locations & Employees
        $this->info("Caching store locations...");
        $companyId = Company::value('id') ?? 1;

        $existingLocations = WorkLocation::where('principal_id', $duluxPrincipal->id)
            ->orWhereNull('principal_id')
            ->get();
        
        $locByName = [];
        $locByCode = [];
        foreach ($existingLocations as $loc) {
            $locByName[strtoupper(trim($loc->name))] = $loc->id;
            if ($loc->code) {
                $locByCode[trim($loc->code)] = $loc->id;
            }
        }

        $defaultEmp = Employee::where('principal_id', $duluxPrincipal->id)->first();
        if (!$defaultEmp) {
            $defaultEmp = Employee::first();
        }
        $defaultEmpId = $defaultEmp ? $defaultEmp->id : null;

        // 3. Determine Months to Import
        $targetMonthOpt = (string) $this->option('month');
        $limit = (int) $this->option('limit');
        
        if (!empty($targetMonthOpt)) {
            if (str_contains($targetMonthOpt, '..')) {
                [$startM, $endM] = explode('..', $targetMonthOpt);
                $months = range((int)$startM, (int)$endM);
            } elseif (str_contains($targetMonthOpt, ',')) {
                $months = array_map('intval', explode(',', $targetMonthOpt));
            } else {
                $months = [(int)$targetMonthOpt];
            }
        } else {
            // Dynamically discover available month chunks for the target year
            $months = [];
            for ($i = 1; $i <= 12; $i++) {
                $mP = str_pad($i, 2, '0', STR_PAD_LEFT);
                if (file_exists($chunksDir . "/offtake_{$targetYear}_m{$mP}.jsonl.gz")) {
                    $months[] = $i;
                }
            }
            if (empty($months)) {
                $months = range(1, 12);
            }
        }

        $totalImported = 0;
        $batchSize = 1000;
        $now = now()->toDateTimeString();

        foreach ($months as $m) {
            $mPad = str_pad($m, 2, '0', STR_PAD_LEFT);
            $chunkFile = $chunksDir . "/offtake_{$targetYear}_m{$mPad}.jsonl.gz";

            if (!file_exists($chunkFile)) {
                $this->warn("Chunk file not found: {$chunkFile}, skipping.");
                continue;
            }

            $this->info(PHP_EOL . "Importing Year {$targetYear} Month {$m} from offtake_{$targetYear}_m{$mPad}.jsonl.gz...");
            $fp = gzopen($chunkFile, 'rb');
            if (!$fp) {
                $this->error("Failed to open {$chunkFile}");
                continue;
            }

            $batchSubmissions = [];
            $monthCount = 0;

            while (!gzeof($fp)) {
                $line = gzgets($fp);
                if (!$line) continue;
                $row = json_decode($line, true);
                if (!$row) continue;

                $rawId = $row['id'] ?? uniqid();
                $storeName = trim($row['name_store'] ?? '');
                $sap = trim($row['sap'] ?? '');
                $sUpper = strtoupper($storeName);

                // Find WorkLocation if exists
                $workLocId = $locByName[$sUpper] ?? ($locByCode[$sap] ?? null);

                $transDate = $row['trans_date'] ?: ($targetYear . '-01-01');
                $submittedAt = $transDate . ' 12:00:00';
                $verifiedAt = $transDate . ' 17:00:00';
                $subCode = "SUB-OFFTAKE-{$targetYear}-" . str_pad($rawId, 7, '0', STR_PAD_LEFT);

                $subData = [
                    'report_template_id' => $template->id,
                    'principal_id' => $duluxPrincipal->id,
                    'employee_id' => $defaultEmpId,
                    'work_location_id' => $workLocId,
                    'submission_code' => $subCode,
                    'status' => 'approved',
                    'submitted_at' => $submittedAt,
                    'verified_at' => $verifiedAt,
                    'created_at' => $submittedAt,
                    'updated_at' => $now,
                ];

                $subBrand = trim($row['sub_brand'] ?: ($row['brand'] ?? 'Dulux'));
                $kemasanGalon = trim($row['kemasan_galon'] ?: '');
                $qtyGalon = (float) ($row['qty_galon'] ?: 0);
                $kemasanPail = trim($row['kemasan_pail'] ?: '');
                $qtyPail = (float) ($row['qty_pail'] ?: 0);
                $volLiter = (float) ($row['volume_liter'] ?: 0);
                $estValueRp = $volLiter > 0 ? round($volLiter * 58000, 2) : 0;

                $valuesData = [
                    [
                        'field_name' => 'produk_terjual',
                        'field_type' => 'product_select',
                        'value_text' => $subBrand,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'kemasan_galon',
                        'field_type' => 'dropdown',
                        'value_text' => !empty($kemasanGalon) ? $kemasanGalon . ' Liter/Kg' : 'Tidak Ada Galon',
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'qty_galon',
                        'field_type' => 'number',
                        'value_text' => (string) $qtyGalon,
                        'value_number' => $qtyGalon,
                    ],
                    [
                        'field_name' => 'kemasan_pail',
                        'field_type' => 'dropdown',
                        'value_text' => !empty($kemasanPail) ? $kemasanPail . ' Liter/Kg' : 'Tidak Ada Pail',
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'qty_pail',
                        'field_type' => 'number',
                        'value_text' => (string) $qtyPail,
                        'value_number' => $qtyPail,
                    ],
                    [
                        'field_name' => 'total_volume_liter',
                        'field_type' => 'number',
                        'value_text' => (string) $volLiter,
                        'value_number' => $volLiter,
                    ],
                    [
                        'field_name' => 'total_nilai_sales_rp',
                        'field_type' => 'currency',
                        'value_text' => (string) $estValueRp,
                        'value_number' => $estValueRp,
                    ],
                    [
                        'field_name' => 'tipe_customer',
                        'field_type' => 'radio',
                        'value_text' => 'End User (Pemilik Rumah Langsung)',
                        'value_number' => null,
                    ],
                ];

                $batchSubmissions[] = [
                    'sub' => $subData,
                    'vals' => $valuesData,
                ];

                if (count($batchSubmissions) >= $batchSize) {
                    $this->flushBatch($batchSubmissions, $fieldIds, $now);
                    $monthCount += count($batchSubmissions);
                    $totalImported += count($batchSubmissions);
                    $this->output->write(".");
                    $batchSubmissions = [];
                }

                if ($limit > 0 && $totalImported >= $limit) {
                    break 2;
                }
            }

            if (!empty($batchSubmissions)) {
                $this->flushBatch($batchSubmissions, $fieldIds, $now);
                $monthCount += count($batchSubmissions);
                $totalImported += count($batchSubmissions);
            }

            gzclose($fp);
            $this->info(PHP_EOL . "Month {$m} Completed! ({$monthCount} rows processed)");
        }

        $this->info(PHP_EOL . "=== Dulux Offtake Import Completed! Total {$totalImported} rows processed. ===");
        return 0;
    }

    private function flushBatch(array $batchSubmissions, array $fieldIds, string $now): void
    {
        $codes = array_map(fn($item) => $item['sub']['submission_code'], $batchSubmissions);
        $existing = DB::table('report_submissions')->whereIn('submission_code', $codes)->pluck('id', 'submission_code')->toArray();

        DB::transaction(function () use ($batchSubmissions, $fieldIds, $now, $existing) {
            foreach ($batchSubmissions as $item) {
                $code = $item['sub']['submission_code'];
                if (isset($existing[$code])) {
                    continue; // Skip already imported row
                }

                $subId = DB::table('report_submissions')->insertGetId($item['sub']);

                $insertVals = [];
                foreach ($item['vals'] as $v) {
                    $insertVals[] = [
                        'report_submission_id' => $subId,
                        'report_form_field_id' => $fieldIds[$v['field_name']] ?? null,
                        'field_name' => $v['field_name'],
                        'field_type' => $v['field_type'],
                        'value_text' => $v['value_text'],
                        'value_number' => $v['value_number'],
                        'value_json' => null,
                        'created_at' => $item['sub']['submitted_at'],
                        'updated_at' => $now,
                    ];
                }
                if (!empty($insertVals)) {
                    DB::table('report_submission_values')->insert($insertVals);
                }
            }
        });
    }
}
