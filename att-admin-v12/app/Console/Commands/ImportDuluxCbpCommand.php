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

class ImportDuluxCbpCommand extends Command
{
    protected $signature = 'dulux:import-cbp {--year=2026 : Target year to import} {--month= : Specific month to import (1-12)} {--limit=0 : Limit number of rows}';
    protected $description = 'Import Dulux CBP (Consumer Buying Price) dataset from JSONL chunks into Report Submissions';

    public function handle(): int
    {
        $targetYear = (int) ($this->option('year') ?: 2026);
        $this->info("=== Starting Dulux CBP {$targetYear} Import ===");

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

        $allDuluxIds = Principal::where('name', 'LIKE', '%Dulux%')
            ->orWhere('name', 'LIKE', '%AkzoNobel%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->pluck('id')
            ->toArray();
        if (!in_array($duluxPrincipal->id, $allDuluxIds)) {
            $allDuluxIds[] = $duluxPrincipal->id;
        }

        $template = ReportTemplate::where('code', 'RPT-DULUX-CBP-PRICING')->first();
        if (!$template) {
            $template = ReportTemplate::create([
                'code' => 'RPT-DULUX-CBP-PRICING',
                'principal_id' => $duluxPrincipal->id,
                'title' => 'Laporan CBP (Consumer Buying Price) & Cek Harga Dulux',
                'description' => 'Monitoring harga beli konsumen (CBP) produk Dulux, Catylac, serta harga brand & subbrand kompetitor (Tin, Galon, Pail) dan promo toko.',
                'category' => 'pricing',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
            ]);
        }

        if (method_exists($template, 'principals')) {
            $template->principals()->sync($allDuluxIds);
        }

        // Ensure form fields exist
        $fieldsMap = [
            'produk_dulux_cbp' => ['field_label' => 'Pilih Produk Dulux yang Dicek Harganya', 'field_type' => 'product_select'],
            'kategori_produk' => ['field_label' => 'Kategori Produk / Segmen Cat', 'field_type' => 'text'],
            'brand_cat' => ['field_label' => 'Brand Cat (Dulux / Kompetitor)', 'field_type' => 'dropdown'],
            'tipe_outlet' => ['field_label' => 'Tipe Outlet / Toko (PARETO / MTI)', 'field_type' => 'text'],
            'kemasan_produk' => ['field_label' => 'Kemasan Produk', 'field_type' => 'dropdown'],
            'harga_cbp_dulux_rp' => ['field_label' => 'Harga Jual Toko ke Konsumen Dulux (CBP Rp)', 'field_type' => 'currency'],
            'merk_kompetitor' => ['field_label' => 'Merk Kompetitor Sejenis di Toko', 'field_type' => 'dropdown'],
            'subbrand_kompetitor' => ['field_label' => 'Nama Subbrand / Produk yang Dicek', 'field_type' => 'text'],
            'harga_kompetitor_tin_rp' => ['field_label' => 'Harga Jual Kemasan Tin 1L/1Kg (Rp)', 'field_type' => 'currency'],
            'harga_kompetitor_galon_rp' => ['field_label' => 'Harga Jual Kemasan Galon 2.5L/4-5Kg (Rp)', 'field_type' => 'currency'],
            'harga_kompetitor_pail_rp' => ['field_label' => 'Harga Jual Kemasan Pail 20L/25Kg (Rp)', 'field_type' => 'currency'],
            'diskon_promo_nominal_rp' => ['field_label' => 'Diskon / Potongan Harga Promo Toko (Nominal Rp)', 'field_type' => 'currency'],
            'diskon_promo_persen' => ['field_label' => 'Diskon / Potongan Harga Promo Toko (Persen %)', 'field_type' => 'number'],
            'keterangan_promo_toko' => ['field_label' => 'Keterangan Program Promo / Bundling Toko', 'field_type' => 'text'],
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

        $existingLocations = WorkLocation::whereIn('principal_id', $allDuluxIds)
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

        $defaultEmp = Employee::whereIn('principal_id', $allDuluxIds)->first();
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
            $months = [];
            for ($i = 1; $i <= 12; $i++) {
                $mP = str_pad($i, 2, '0', STR_PAD_LEFT);
                if (file_exists($chunksDir . "/cbp_{$targetYear}_m{$mP}.jsonl.gz")) {
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
            $chunkFile = $chunksDir . "/cbp_{$targetYear}_m{$mPad}.jsonl.gz";

            if (!file_exists($chunkFile)) {
                $this->warn("Chunk file not found: {$chunkFile}, skipping.");
                continue;
            }

            $this->info(PHP_EOL . "Importing Year {$targetYear} Month {$m} from cbp_{$targetYear}_m{$mPad}.jsonl.gz...");
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
                $sap = trim($row['sap_member'] ?: ($row['sap_gab'] ?? ''));
                $sUpper = strtoupper($storeName);

                // Find or create WorkLocation
                $workLocId = $locByName[$sUpper] ?? ($locByCode[$sap] ?? null);
                if (!$workLocId && !empty($storeName)) {
                    $newLoc = WorkLocation::create([
                        'company_id' => $companyId,
                        'name' => $storeName,
                        'type' => 'client',
                        'latitude' => -6.2000000,
                        'longitude' => 106.8166667,
                        'radius_meter' => 100,
                        'code' => $sap ?: null,
                        'region' => $row['regional'] ?: null,
                        'branch_name' => $row['area'] ?: null,
                        'category' => $row['store_type'] ?: 'Retail Store',
                        'principal_id' => $duluxPrincipal->id,
                        'is_active' => true,
                    ]);
                    $workLocId = $newLoc->id;
                    $locByName[$sUpper] = $workLocId;
                    if ($sap) $locByCode[$sap] = $workLocId;
                }

                $transDate = $row['trans_date'] ?: ($targetYear . '-' . $mPad . '-01');
                $submittedAt = $transDate . ' 12:00:00';
                $verifiedAt = $transDate . ' 17:00:00';
                $subCode = "SUB-CBP-{$targetYear}-" . str_pad($rawId, 7, '0', STR_PAD_LEFT);

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

                $brand = trim($row['brand'] ?? 'AN');
                $product = trim($row['product'] ?? '');
                $category = trim($row['category'] ?? '');
                $storeType = trim($row['store_type'] ?? '');
                $pTin = (float) ($row['price_tin'] ?? 0);
                $pGalon = (float) ($row['price_galon'] ?? 0);
                $pPail = (float) ($row['price_pail'] ?? 0);
                $reason = trim($row['reason_galon'] ?: ($row['reason_tin'] ?: ($row['reason_pail'] ?? '')));

                $isDulux = (strtoupper($brand) === 'AN');
                $duluxPrice = $pGalon > 0 ? $pGalon : ($pTin > 0 ? $pTin : $pPail);

                $kemasanText = '2.5 Liter / 4 Kg / 5 Kg (Galon)';
                if ($pGalon > 0 && $pTin > 0 && $pPail > 0) {
                    $kemasanText = 'Semua Kemasan (Tin, Galon, Pail)';
                } elseif ($pGalon > 0) {
                    $kemasanText = '2.5 Liter / 4 Kg / 5 Kg (Galon)';
                } elseif ($pPail > 0) {
                    $kemasanText = '20 Liter / 25 Kg (Pail Besar)';
                } elseif ($pTin > 0) {
                    $kemasanText = '1 Liter / 1 Kg (Small Tin)';
                }

                $valuesData = [
                    [
                        'field_name' => 'produk_dulux_cbp',
                        'field_type' => 'product_select',
                        'value_text' => $isDulux ? $product : ($category ?: $product),
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'kategori_produk',
                        'field_type' => 'text',
                        'value_text' => $category,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'brand_cat',
                        'field_type' => 'dropdown',
                        'value_text' => $isDulux ? 'AN (AkzoNobel / Dulux)' : $brand,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'tipe_outlet',
                        'field_type' => 'text',
                        'value_text' => $storeType,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'kemasan_produk',
                        'field_type' => 'dropdown',
                        'value_text' => $kemasanText,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'harga_cbp_dulux_rp',
                        'field_type' => 'currency',
                        'value_text' => $isDulux ? (string) $duluxPrice : '0',
                        'value_number' => $isDulux ? $duluxPrice : 0,
                    ],
                    [
                        'field_name' => 'merk_kompetitor',
                        'field_type' => 'dropdown',
                        'value_text' => $isDulux ? 'AN (AkzoNobel / Dulux)' : $brand,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'subbrand_kompetitor',
                        'field_type' => 'text',
                        'value_text' => $product,
                        'value_number' => null,
                    ],
                    [
                        'field_name' => 'harga_kompetitor_tin_rp',
                        'field_type' => 'currency',
                        'value_text' => (string) $pTin,
                        'value_number' => $pTin,
                    ],
                    [
                        'field_name' => 'harga_kompetitor_galon_rp',
                        'field_type' => 'currency',
                        'value_text' => (string) $pGalon,
                        'value_number' => $pGalon,
                    ],
                    [
                        'field_name' => 'harga_kompetitor_pail_rp',
                        'field_type' => 'currency',
                        'value_text' => (string) $pPail,
                        'value_number' => $pPail,
                    ],
                    [
                        'field_name' => 'diskon_promo_nominal_rp',
                        'field_type' => 'currency',
                        'value_text' => '0',
                        'value_number' => 0,
                    ],
                    [
                        'field_name' => 'diskon_promo_persen',
                        'field_type' => 'number',
                        'value_text' => '0',
                        'value_number' => 0,
                    ],
                    [
                        'field_name' => 'keterangan_promo_toko',
                        'field_type' => 'text',
                        'value_text' => $reason ?: 'Normal Price (No disc)',
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
                    $this->flushBatch($batchSubmissions, $fieldIds, $now);
                    $batchSubmissions = [];
                    $this->output->write('.');
                }

                if ($limit > 0 && $totalImported >= $limit) {
                    break 2;
                }
            }

            if (!empty($batchSubmissions)) {
                $this->flushBatch($batchSubmissions, $fieldIds, $now);
                $batchSubmissions = [];
                $this->output->write('.');
            }

            gzclose($fp);
            $this->info(PHP_EOL . "Month {$m} Completed! ({$monthCount} rows processed)");
        }

        $this->info(PHP_EOL . "=== Dulux CBP Import Completed! Total {$totalImported} rows processed. ===");
        return 0;
    }

    private function flushBatch(array $batchSubmissions, array $fieldIds, string $now): void
    {
        $codes = array_map(fn($item) => $item['sub']['submission_code'], $batchSubmissions);
        $existing = DB::table('report_submissions')->whereIn('submission_code', $codes)->pluck('id', 'submission_code')->toArray();

        // Filter out already imported rows before transaction to prevent duplicate key race conditions
        $toInsert = array_filter($batchSubmissions, fn($item) => !isset($existing[$item['sub']['submission_code']]));
        if (empty($toInsert)) {
            return;
        }

        DB::transaction(function () use ($toInsert, $fieldIds, $now) {
            foreach ($toInsert as $item) {
                $subId = DB::table('report_submissions')->insertGetId($item['sub']);

                $insertVals = [];
                foreach ($item['vals'] as $val) {
                    $fName = $val['field_name'];
                    if (!isset($fieldIds[$fName])) continue;

                    $insertVals[] = [
                        'report_submission_id' => $subId,
                        'report_form_field_id' => $fieldIds[$fName],
                        'value_text' => $val['value_text'],
                        'value_number' => $val['value_number'],
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
