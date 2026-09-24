<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ReportTemplate;
use App\Models\ReportSubmissionValue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan / Pastikan Principal PT WINGS SURYA
        $wingsPrincipal = Principal::where(function ($q) {
            $q->where('code', 'PR-WINGS-SURYA')
              ->orWhere('name', 'LIKE', '%WINGS SURYA%')
              ->orWhere('subdomain', 'wings');
        })->first();

        if (!$wingsPrincipal) {
            $wingsPrincipal = Principal::firstOrCreate(
                ['code' => 'PR-WINGS-SURYA'],
                [
                    'name' => 'PT WINGS SURYA',
                    'subdomain' => 'wings',
                    'theme_color' => '#E53935',
                    'theme_color_secondary' => '#C62828',
                    'portal_title' => 'Portal Pelaporan & Monitoring Wings Surya',
                    'is_active' => true,
                ]
            );
        }

        // 2. Definisi 36 Master Produk Baru dari HARGA MIE SEDAP.xlsx
        $newProductCatalog = [
            // MIE : " SEDAAP" (Bag)
            [
                'sku_code' => 'SM40AB',
                'name'     => 'MIE SEDAAP AYAM',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-05',
            ],
            [
                'sku_code' => 'SM40AB5',
                'name'     => 'MIE SEDAAP AYAM BAWANG ISI 5',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 13301.00,
                'uom'      => 'Pack',
                'old_sku'  => 'WS-MS-21',
            ],
            [
                'sku_code' => 'SM40S',
                'name'     => 'MIE SEDAAP SOTO',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-02',
            ],
            [
                'sku_code' => 'SM40S5',
                'name'     => 'MIE SEDAAP SOTO ISI 5',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 13301.00,
                'uom'      => 'Pack',
                'old_sku'  => 'WS-MS-20',
            ],
            [
                'sku_code' => 'SM40K',
                'name'     => 'MIE SEDAAP AYAM KRISPY',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-10',
            ],
            [
                'sku_code' => 'SM40KKS',
                'name'     => 'MIE SEDAAP KARI KENTAL SPESIAL',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-08',
            ],
            [
                'sku_code' => 'SM40B',
                'name'     => 'MIE SEDAAP BASO',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-04',
            ],
            [
                'sku_code' => 'SM40WHC',
                'name'     => 'MIE SEDAAP WHITE CURRY',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-09',
            ],
            [
                'sku_code' => 'SM40SSL',
                'name'     => 'MIE SEDAAP SINGAPORE SPICY LAKSA',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-17',
            ],
            [
                'sku_code' => 'SM40SM',
                'name'     => 'MIE SEDAAP SOTO MADURA',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-14',
            ],
            [
                'sku_code' => 'SM40AJ',
                'name'     => 'MIE SEDAAP AYAM JERIT',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-11',
            ],
            [
                'sku_code' => 'SM40AS',
                'name'     => 'MIE SEDAAP AYAM SPESIAL',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2485.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-06',
            ],
            [
                'sku_code' => 'SM40G',
                'name'     => 'MIE SEDAAP GORENG',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-01',
            ],
            [
                'sku_code' => 'SM40G5',
                'name'     => 'MIE SEDAAP GORENG ISI 5',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 14007.00,
                'uom'      => 'Pack',
                'old_sku'  => 'WS-MS-19',
            ],
            [
                'sku_code' => 'SM40GK',
                'name'     => 'MIE SEDAAP GORENG KRIUK',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => null,
            ],
            [
                'sku_code' => 'SM40SP',
                'name'     => 'MIE SEDAAP SALERO PADANG',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-12',
            ],
            [
                'sku_code' => 'SM40ABL',
                'name'     => 'MIE SEDAAP AYAM BAKAR LIMAU',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-13',
            ],
            [
                'sku_code' => 'SM40KCB',
                'name'     => 'MIE SEDAAP KOREAN CHEESE BULDAK',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-18',
            ],
            [
                'sku_code' => 'SM40GAC',
                'name'     => 'MIE SEDAAP GORENG AYAM CRISPI',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => null,
            ],

            // MIE : " SEDAAP SELECTION"
            [
                'sku_code' => 'SM40KSS',
                'name'     => 'MIE SEDAAP SELECTION KOREAN SPICY SOUP',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP SELECTION',
                'price'    => 2710.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-16',
            ],
            [
                'sku_code' => 'SM40KSC',
                'name'     => 'MIE SEDAAP SELECTION KOREAN SPICY CHICKEN',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP SELECTION',
                'price'    => 2846.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-15',
            ],

            // MIE : " SEDAAP CUP"
            [
                'sku_code' => 'SMC12B',
                'name'     => 'MIE SEDAAP CUP BASO',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-30',
            ],
            [
                'sku_code' => 'SMC12S',
                'name'     => 'MIE SEDAAP CUP SOTO',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-29',
            ],
            [
                'sku_code' => 'SMC12KS',
                'name'     => 'MIE SEDAAP CUP KARI SPESIAL',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-31',
            ],
            [
                'sku_code' => 'SMC12AJ',
                'name'     => 'MIE SEDAAP CUP AYAM JERIT',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-35',
            ],
            [
                'sku_code' => 'SMC12BB',
                'name'     => 'MIE SEDAAP CUP BASO BLEDUK',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-36',
            ],
            [
                'sku_code' => 'SMC12AN',
                'name'     => 'MIE SEDAAP CUP AYAM NAMPOL',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-32',
            ],
            [
                'sku_code' => 'SMC12KM',
                'name'     => 'MIE SEDAAP CUP KARI MERCON',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4165.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-37',
            ],
            [
                'sku_code' => 'SMC12G',
                'name'     => 'MIE SEDAAP CUP GORENG',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP',
                'price'    => 4367.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-28',
            ],

            // MIE : " SEDAAP CUP SELECTION"
            [
                'sku_code' => 'SMC12KSS',
                'name'     => 'MIE SEDAAP CUP SELECTION KOREAN SPICY SOUP',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP SELECTION',
                'price'    => 4367.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-34',
            ],
            [
                'sku_code' => 'SMC12KSC',
                'name'     => 'MIE SEDAAP CUP SELECTION KOREAN SPICY CHICKEN',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP CUP SELECTION',
                'price'    => 4367.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-33',
            ],

            // RAMEN "YES"
            [
                'sku_code' => 'RY40HCR/TCY (MT)',
                'name'     => 'MIE SEDAAP RAMEN YES HAKATA CHICKEN RAMEN',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'RAMEN YES',
                'price'    => 3050.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-39',
            ],
            [
                'sku_code' => 'RY60HCR/TCY (GT)',
                'name'     => 'MIE SEDAAP RAMEN YES TOKYO CHICKEN YAKITORI',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'RAMEN YES',
                'price'    => 3050.00,
                'uom'      => 'Cup',
                'old_sku'  => 'WS-MS-40',
            ],

            // MIE : "TASTY"
            [
                'sku_code' => 'SM12BA/AG/BY/CT',
                'name'     => 'MIE SEDAAP TASTY',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE TASTY',
                'price'    => 5100.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-24',
            ],

            // MIE : " SEDAAP BAKED"
            [
                'sku_code' => 'SMB24G',
                'name'     => 'MIE SEDAAP BAKED GORENG',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP BAKED',
                'price'    => 2400.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-22',
            ],
            [
                'sku_code' => 'SMB24S',
                'name'     => 'MIE SEDAAP BAKED SOTO',
                'category' => 'Mie Instant',
                'brand'    => 'MIE SEDAAP',
                'group'    => 'MIE SEDAAP BAKED',
                'price'    => 2400.00,
                'uom'      => 'Pcs',
                'old_sku'  => 'WS-MS-23',
            ],
        ];

        // 3. Mapping untuk penyesuaian data laporan lama ke produk baru
        // Format: 'OLD_IDENTIFIER' => ['sku_code' => ..., 'name' => ..., 'price' => ...]
        $legacySkuToNew = [];
        $activeProductIds = [];

        foreach ($newProductCatalog as $item) {
            $product = null;

            // Jika ada old_sku acuan, utamakan update record yang sudah ada agar ID tetap sama
            if (!empty($item['old_sku'])) {
                $product = Product::where('principal_id', $wingsPrincipal->id)
                    ->where(function ($q) use ($item) {
                        $q->where('sku_code', $item['old_sku'])
                          ->orWhere('sku_code', $item['sku_code']);
                    })
                    ->first();
            }

            if (!$product) {
                // Cari berdasarkan sku_code baru
                $product = Product::where('principal_id', $wingsPrincipal->id)
                    ->where('sku_code', $item['sku_code'])
                    ->first();
            }

            if ($product) {
                $product->update([
                    'principal_id' => $wingsPrincipal->id,
                    'sku_code'     => $item['sku_code'],
                    'name'         => $item['name'],
                    'brand'        => $item['brand'],
                    'category'     => $item['category'],
                    'price'        => $item['price'],
                    'uom'          => $item['uom'],
                    'is_active'    => true,
                ]);
            } else {
                $product = Product::create([
                    'principal_id' => $wingsPrincipal->id,
                    'sku_code'     => $item['sku_code'],
                    'name'         => $item['name'],
                    'brand'        => $item['brand'],
                    'category'     => $item['category'],
                    'price'        => $item['price'],
                    'uom'          => $item['uom'],
                    'is_active'    => true,
                ]);
            }

            $activeProductIds[] = $product->id;

            // Catat mapping penggantian
            $targetInfo = [
                'product_id'        => $product->id,
                'sku_code'          => $item['sku_code'],
                'name'              => $item['name'],
                'distributor_price' => $item['price'],
            ];

            $legacySkuToNew[$item['sku_code']] = $targetInfo;
            if (!empty($item['old_sku'])) {
                $legacySkuToNew[$item['old_sku']] = $targetInfo;
            }
        }

        // 4. Mapping produk lama yang di-pensiunkan (retired) ke padanan produk baru terdekat
        $retiredFallbacks = [
            'WS-MS-03' => 'SM40G',             // Devina Goreng -> MIE SEDAAP GORENG
            'WS-MS-07' => 'SM40KKS',           // Kari Ayam -> KARI KENTAL SPESIAL
            'WS-MS-25' => 'SM12BA/AG/BY/CT',   // Tasty Ayam Geprek -> TASTY
            'WS-MS-26' => 'SM12BA/AG/BY/CT',   // Tasty Beef Yakiniku -> TASTY
            'WS-MS-27' => 'SM12BA/AG/BY/CT',   // Tasty Chicken Teriyaki -> TASTY
            'WS-MS-38' => 'SM40SSL',           // Cup Spicy Laksa -> SPICY LAKSA
        ];

        foreach ($retiredFallbacks as $oldSku => $targetNewSku) {
            if (isset($legacySkuToNew[$targetNewSku])) {
                $legacySkuToNew[$oldSku] = $legacySkuToNew[$targetNewSku];
            }
        }

        // Nonaktifkan produk lama yang sudah tidak ada di katalog 36 produk baru
        Product::where('principal_id', $wingsPrincipal->id)
            ->where('brand', 'LIKE', '%SEDAAP%')
            ->whereNotIn('id', $activeProductIds)
            ->update(['is_active' => false]);

        // 5. Hubungkan (sync) 36 produk aktif ini ke Template Pelaporan Wings Surya
        $mbrTemplates = ReportTemplate::whereIn('code', [
            'RPT-WINGS-MBR-SALES-01',
            'RPT-WINGS-MBR-FREETASTE-01',
        ])->get();

        foreach ($mbrTemplates as $template) {
            $template->products()->sync($activeProductIds);
        }

        $otherWingsTemplates = ReportTemplate::where(function ($q) use ($wingsPrincipal) {
            $q->where('principal_id', $wingsPrincipal->id)
              ->orWhereIn('code', [
                  'RPT-WINGS-OOS-FOOD-01',
                  'RPT-WINGS-PENJUALAN-HADIAH-01',
              ]);
        })->whereNotIn('code', [
            'RPT-WINGS-MBR-SALES-01',
            'RPT-WINGS-MBR-FREETASTE-01',
        ])->get();

        foreach ($otherWingsTemplates as $template) {
            $template->products()->syncWithoutDetaching($activeProductIds);
        }

        // 6. Sesuaikan data laporan yang sudah masuk sebelumnya
        // Update payload JSON pada kolom mbr_sales_items_json dan mbr_freetaste_items_json
        $submissionValues = ReportSubmissionValue::whereIn('field_name', [
            'mbr_sales_items_json',
            'mbr_freetaste_items_json',
        ])->get();

        foreach ($submissionValues as $val) {
            $raw = $val->value_json ?: json_decode($val->value_text ?: '[]', true);
            if (!is_array($raw) || empty($raw)) {
                continue;
            }

            $modified = false;
            foreach ($raw as &$entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $entrySku = trim((string)($entry['sku_code'] ?? ''));
                $entryName = trim((string)($entry['name'] ?? ($entry['product_name'] ?? '')));
                $matchedTarget = null;

                // 1. Cek dari SKU
                if ($entrySku && isset($legacySkuToNew[$entrySku])) {
                    $matchedTarget = $legacySkuToNew[$entrySku];
                }

                // 2. Cek dari Nama jika SKU tidak cocok
                if (!$matchedTarget && $entryName) {
                    foreach ($newProductCatalog as $np) {
                        if (strcasecmp($entryName, $np['name']) === 0 || stripos($entryName, $np['name']) !== false) {
                            $matchedTarget = $legacySkuToNew[$np['sku_code']] ?? null;
                            break;
                        }
                    }
                }

                if ($matchedTarget) {
                    $entry['product_id']        = $matchedTarget['product_id'];
                    $entry['sku_code']          = $matchedTarget['sku_code'];
                    $entry['name']              = $matchedTarget['name'];
                    $entry['product_name']      = $matchedTarget['name'];
                    $entry['distributor_price'] = (float)$matchedTarget['distributor_price'];
                    $modified = true;
                }
            }
            unset($entry);

            if ($modified) {
                $val->value_json = $raw;
                $val->value_text = json_encode($raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $val->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse
    }
};
