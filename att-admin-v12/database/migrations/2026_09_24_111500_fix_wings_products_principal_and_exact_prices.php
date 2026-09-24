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
        // 1. Temukan Principal Utama PT WINGS SURYA pada server ini
        // (Server 1 AMK = ID 48 / code 141, Server 2 AKP = ID 42 / code 206, Server 3 ATK = ID 38 / code 502)
        $primaryWingsPrincipal = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS SURYA%')
              ->where('code', 'NOT LIKE', 'PR-%');
        })->first();

        if (!$primaryWingsPrincipal) {
            $primaryWingsPrincipal = Principal::whereIn('code', ['141', '206', '502', 'PR-WINGS-SURYA'])->first()
                ?? Principal::where('name', 'LIKE', '%WINGS SURYA%')->first()
                ?? Principal::where('name', 'LIKE', '%WINGS%')->first();
        }

        $allWingsPrincipalIds = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS%')
              ->orWhere('code', 'LIKE', '%WINGS%')
              ->orWhereIn('code', ['141', '206', '502', '120', '203', '501']);
        })->pluck('id')->toArray();

        // 2. Daftar 36 Master Produk Wings Surya dengan Harga Standar tepat dari Kolom HARGA Excel (rounded IDR)
        $standardPrices = [
            // MIE : " SEDAAP" (Bag)
            'SM40AB'            => ['price' => 2710.00,  'name' => 'MIE SEDAAP AYAM', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-05'],
            'SM40AB5'           => ['price' => 13301.00, 'name' => 'MIE SEDAAP AYAM BAWANG ISI 5', 'uom' => 'Pack', 'old_sku' => 'WS-MS-21'],
            'SM40S'             => ['price' => 2710.00,  'name' => 'MIE SEDAAP SOTO', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-02'],
            'SM40S5'            => ['price' => 13301.00, 'name' => 'MIE SEDAAP SOTO ISI 5', 'uom' => 'Pack', 'old_sku' => 'WS-MS-20'],
            'SM40K'             => ['price' => 2710.00,  'name' => 'MIE SEDAAP AYAM KRISPY', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-10'],
            'SM40KKS'           => ['price' => 2710.00,  'name' => 'MIE SEDAAP KARI KENTAL SPESIAL', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-08'],
            'SM40B'             => ['price' => 2710.00,  'name' => 'MIE SEDAAP BASO', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-04'],
            'SM40WHC'           => ['price' => 2710.00,  'name' => 'MIE SEDAAP WHITE CURRY', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-09'],
            'SM40SSL'           => ['price' => 2710.00,  'name' => 'MIE SEDAAP SINGAPORE SPICY LAKSA', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-17'],
            'SM40SM'            => ['price' => 2710.00,  'name' => 'MIE SEDAAP SOTO MADURA', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-14'],
            'SM40AJ'            => ['price' => 2710.00,  'name' => 'MIE SEDAAP AYAM JERIT', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-11'],
            'SM40AS'            => ['price' => 2485.00,  'name' => 'MIE SEDAAP AYAM SPESIAL', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-06'],
            'SM40G'             => ['price' => 2846.00,  'name' => 'MIE SEDAAP GORENG', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-01'],
            'SM40G5'            => ['price' => 14007.00, 'name' => 'MIE SEDAAP GORENG ISI 5', 'uom' => 'Pack', 'old_sku' => 'WS-MS-19'],
            'SM40GK'            => ['price' => 2846.00,  'name' => 'MIE SEDAAP GORENG KRIUK', 'uom' => 'Pcs', 'old_sku' => null],
            'SM40SP'            => ['price' => 2846.00,  'name' => 'MIE SEDAAP SALERO PADANG', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-12'],
            'SM40ABL'           => ['price' => 2846.00,  'name' => 'MIE SEDAAP AYAM BAKAR LIMAU', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-13'],
            'SM40KCB'           => ['price' => 2846.00,  'name' => 'MIE SEDAAP KOREAN CHEESE BULDAK', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-18'],
            'SM40GAC'           => ['price' => 2846.00,  'name' => 'MIE SEDAAP GORENG AYAM CRISPI', 'uom' => 'Pcs', 'old_sku' => null],

            // MIE : " SEDAAP SELECTION"
            'SM40KSS'           => ['price' => 2710.00,  'name' => 'MIE SEDAAP SELECTION KOREAN SPICY SOUP', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-16'],
            'SM40KSC'           => ['price' => 2846.00,  'name' => 'MIE SEDAAP SELECTION KOREAN SPICY CHICKEN', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-15'],

            // MIE : " SEDAAP CUP"
            'SMC12B'            => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP BASO', 'uom' => 'Cup', 'old_sku' => 'WS-MS-30'],
            'SMC12S'            => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP SOTO', 'uom' => 'Cup', 'old_sku' => 'WS-MS-29'],
            'SMC12KS'           => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP KARI SPESIAL', 'uom' => 'Cup', 'old_sku' => 'WS-MS-31'],
            'SMC12AJ'           => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP AYAM JERIT', 'uom' => 'Cup', 'old_sku' => 'WS-MS-35'],
            'SMC12BB'           => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP BASO BLEDUK', 'uom' => 'Cup', 'old_sku' => 'WS-MS-36'],
            'SMC12AN'           => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP AYAM NAMPOL', 'uom' => 'Cup', 'old_sku' => 'WS-MS-32'],
            'SMC12KM'           => ['price' => 4165.00,  'name' => 'MIE SEDAAP CUP KARI MERCON', 'uom' => 'Cup', 'old_sku' => 'WS-MS-37'],
            'SMC12G'            => ['price' => 4367.00,  'name' => 'MIE SEDAAP CUP GORENG', 'uom' => 'Cup', 'old_sku' => 'WS-MS-28'],

            // MIE : " SEDAAP CUP SELECTION"
            'SMC12KSS'          => ['price' => 4367.00,  'name' => 'MIE SEDAAP CUP SELECTION KOREAN SPICY SOUP', 'uom' => 'Cup', 'old_sku' => 'WS-MS-34'],
            'SMC12KSC'          => ['price' => 4367.00,  'name' => 'MIE SEDAAP CUP SELECTION KOREAN SPICY CHICKEN', 'uom' => 'Cup', 'old_sku' => 'WS-MS-33'],

            // RAMEN "YES"
            'RY40HCR/TCY (MT)'  => ['price' => 3050.00,  'name' => 'MIE SEDAAP RAMEN YES HAKATA CHICKEN RAMEN', 'uom' => 'Cup', 'old_sku' => 'WS-MS-39'],
            'RY60HCR/TCY (GT)'  => ['price' => 3050.00,  'name' => 'MIE SEDAAP RAMEN YES TOKYO CHICKEN YAKITORI', 'uom' => 'Cup', 'old_sku' => 'WS-MS-40'],

            // MIE : "TASTY"
            'SM12BA/AG/BY/CT'   => ['price' => 5100.00,  'name' => 'MIE SEDAAP TASTY', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-24'],

            // MIE : " SEDAAP BAKED"
            'SMB24G'            => ['price' => 2400.00,  'name' => 'MIE SEDAAP BAKED GORENG', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-22'],
            'SMB24S'            => ['price' => 2400.00,  'name' => 'MIE SEDAAP BAKED SOTO', 'uom' => 'Pcs', 'old_sku' => 'WS-MS-23'],
        ];

        $targetPrincipalId = $primaryWingsPrincipal ? $primaryWingsPrincipal->id : 48;
        $activeProductIds = [];

        // 3. Update / Pastikan 36 Produk Aktif ini Terkait ke Principal PT WINGS SURYA dengan Harga Tepat
        foreach ($standardPrices as $sku => $meta) {
            $product = Product::where('sku_code', $sku)
                ->orWhere('sku_code', $sku . ' ')
                ->first();

            if (!$product && !empty($meta['old_sku'])) {
                $product = Product::whereIn('principal_id', $allWingsPrincipalIds)
                    ->where('sku_code', $meta['old_sku'])
                    ->first();
            }

            if (!$product) {
                $product = new Product();
            }

            $product->principal_id = $targetPrincipalId;
            $product->sku_code     = $sku;
            $product->name         = $meta['name'];
            $product->category     = 'Mie Instant';
            $product->brand        = 'MIE SEDAAP';
            $product->price        = $meta['price'];
            $product->uom          = $meta['uom'];
            $product->is_active    = true;
            $product->save();

            $activeProductIds[] = $product->id;
        }

        // 4. Nonaktifkan seluruh produk lama WS-MS-* yang bukan bagian dari 36 produk aktif
        $oldWsProducts = Product::where('sku_code', 'LIKE', 'WS-MS%')
            ->whereNotIn('id', $activeProductIds)
            ->get();

        foreach ($oldWsProducts as $oldProd) {
            foreach ($standardPrices as $nSku => $meta) {
                if (!empty($meta['old_sku']) && $meta['old_sku'] === $oldProd->sku_code) {
                    $oldProd->price = $meta['price'];
                    break;
                }
            }
            $oldProd->is_active = false;
            $oldProd->save();
        }

        // 5. Perbarui Pivot Tabel report_template_product
        if (!empty($oldWsProducts)) {
            $oldIds = $oldWsProducts->pluck('id')->toArray();
            DB::table('report_template_product')->whereIn('product_id', $oldIds)->delete();
        }

        $wingsTemplates = ReportTemplate::where(function ($q) use ($allWingsPrincipalIds) {
            $q->whereIn('principal_id', $allWingsPrincipalIds)
              ->orWhereIn('code', [
                  'RPT-WINGS-MBR-SALES-01',
                  'RPT-WINGS-MBR-FREETASTE-01',
                  'RPT-WINGS-OOS-FOOD-01',
                  'RPT-WINGS-PENJUALAN-HADIAH-01',
              ]);
        })->get();

        foreach ($wingsTemplates as $template) {
            if (in_array($template->code, ['RPT-WINGS-MBR-SALES-01', 'RPT-WINGS-MBR-FREETASTE-01'])) {
                $template->products()->sync($activeProductIds);
            } else {
                $template->products()->syncWithoutDetaching($activeProductIds);
            }
        }

        // 6. Sesuaikan riwayat submissions jika ada
        if (Schema::hasTable('report_submission_values')) {
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

                    $sku = trim((string)($entry['sku_code'] ?? ($entry['sku'] ?? '')));
                    if (isset($standardPrices[$sku])) {
                        $price = $standardPrices[$sku]['price'];
                        $entry['price'] = $price;
                        $entry['distributor_price'] = $price;
                        if (isset($entry['qty'])) {
                            $qty = floatval($entry['qty']);
                            $entry['subtotal'] = $qty * $price;
                        }
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed
    }
};
