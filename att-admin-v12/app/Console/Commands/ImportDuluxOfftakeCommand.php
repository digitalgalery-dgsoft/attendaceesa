<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;
use App\Models\Principal;
use App\Models\WorkLocation;
use App\Models\Employee;
use PDO;

class ImportDuluxOfftakeCommand extends Command
{
    protected $signature = 'dulux:import-offtake {--month= : Specific month to import (1-12)} {--limit=0 : Limit number of rows}';
    protected $description = 'Import Dulux Offtake 2025 dataset from SQLite into Report Submissions';

    public function handle(): int
    {
        $this->info("=== Starting Dulux Offtake 2025 Import ===");

        $dataDir = storage_path('app/dulux_data');
        $sqlitePath = $dataDir . '/offtake_2025.sqlite';
        $gzPath = $dataDir . '/offtake_2025.sqlite.gz';

        if (!file_exists($sqlitePath)) {
            if (file_exists($gzPath)) {
                $this->info("Decompressing offtake_2025.sqlite.gz...");
                $fp_in = gzopen($gzPath, 'rb');
                $fp_out = fopen($sqlitePath, 'wb');
                while (!gzeof($fp_in)) {
                    fwrite($fp_out, gzread($fp_in, 1024 * 1024 * 2));
                }
                fclose($fp_out);
                gzclose($fp_in);
                $this->info("Decompressed successfully!");
            } else {
                $this->error("File offtake_2025.sqlite or .sqlite.gz not found at {$dataDir}");
                return 1;
            }
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

        // Ensure fields exist
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

        // 2. Open SQLite Connection
        $sqlite = new PDO("sqlite:" . $sqlitePath);
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 3. Sync / Cache Stores (Work Locations)
        $this->info("Syncing store locations...");
        $stmtStores = $sqlite->query("SELECT DISTINCT name_store, sap, region, area, category_store FROM offtake_raw");
        $storeMap = [];

        // Pre-load existing work locations
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

        $companyId = \App\Models\Company::value('id') ?? 1;

        while ($sRow = $stmtStores->fetch(PDO::FETCH_ASSOC)) {
            $sName = trim($sRow['name_store']);
            $sap = trim($sRow['sap']);
            $sUpper = strtoupper($sName);

            $locId = $locByName[$sUpper] ?? ($locByCode[$sap] ?? null);
            if (!$locId) {
                $newLoc = WorkLocation::create([
                    'company_id' => $companyId,
                    'name' => $sName,
                    'code' => $sap ?: null,
                    'region' => $sRow['region'] ?: null,
                    'branch_name' => $sRow['area'] ?: null,
                    'category' => $sRow['category_store'] ?: 'Blue Store',
                    'principal_id' => $duluxPrincipal->id,
                    'is_active' => true,
                ]);
                $locId = $newLoc->id;
                $locByName[$sUpper] = $locId;
                if ($sap) $locByCode[$sap] = $locId;
            }
            $storeMap[$sName] = $locId;
        }
        $this->info("Cached " . count($storeMap) . " stores.");

        // Fallback default employee for submissions
        $defaultEmp = Employee::where('principal_id', $duluxPrincipal->id)->first();
        if (!$defaultEmp) {
            $defaultEmp = Employee::first();
        }
        $defaultEmpId = $defaultEmp ? $defaultEmp->id : null;

        // 4. Query Raw Data from SQLite
        $targetMonth = (int) $this->option('month');
        $limit = (int) $this->option('limit');

        $whereClause = "WHERE year = 2025";
        if ($targetMonth > 0) {
            $whereClause .= " AND month = {$targetMonth}";
        }
        $limitClause = $limit > 0 ? "LIMIT {$limit}" : "";

        $countStmt = $sqlite->query("SELECT count(*) as total FROM offtake_raw {$whereClause} {$limitClause}");
        $totalToImport = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $this->info("Ready to import {$totalToImport} rows for " . ($targetMonth > 0 ? "Month {$targetMonth}" : "All 12 Months 2025") . "...");

        $queryStmt = $sqlite->query("SELECT * FROM offtake_raw {$whereClause} ORDER BY id ASC {$limitClause}");

        $batchSubmissions = [];
        $batchValues = [];
        $batchSize = 1000;
        $importedCount = 0;
        $now = now()->toDateTimeString();

        $progressBar = $this->output->createProgressBar($totalToImport);
        $progressBar->start();

        while ($row = $queryStmt->fetch(PDO::FETCH_ASSOC)) {
            $rawId = $row['id'];
            $storeName = trim($row['name_store']);
            $workLocId = $storeMap[$storeName] ?? null;
            $transDate = $row['trans_date'] ?: '2025-01-01';
            $submittedAt = $transDate . ' 12:00:00';
            $verifiedAt = $transDate . ' 17:00:00';
            $subCode = 'SUB-OFFTAKE-2025-' . str_pad($rawId, 7, '0', STR_PAD_LEFT);

            // Submission Record
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

            // Values
            $subBrand = trim($row['sub_brand'] ?: $row['brand']);
            $kemasanGalon = trim($row['kemasan_galon'] ?: '');
            $qtyGalon = (float) ($row['qty_galon'] ?: 0);
            $kemasanPail = trim($row['kemasan_pail'] ?: '');
            $qtyPail = (float) ($row['qty_pail'] ?: 0);
            $volLiter = (float) ($row['volume_liter'] ?: 0);
            $estValueRp = $volLiter > 0 ? round($volLiter * 58000, 2) : 0; // standard estimated price/L

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
                $importedCount += count($batchSubmissions);
                $progressBar->advance(count($batchSubmissions));
                $batchSubmissions = [];
            }
        }

        if (!empty($batchSubmissions)) {
            $this->flushBatch($batchSubmissions, $fieldIds, $now);
            $importedCount += count($batchSubmissions);
            $progressBar->advance(count($batchSubmissions));
        }

        $progressBar->finish();
        $this->info(PHP_EOL . "=== Import Completed! Total {$importedCount} transactions imported into Report Submissions. ===");

        return 0;
    }

    private function flushBatch(array $batchSubmissions, array $fieldIds, string $now): void
    {
        DB::transaction(function () use ($batchSubmissions, $fieldIds, $now) {
            foreach ($batchSubmissions as $item) {
                // Insert submission or update if exists
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
