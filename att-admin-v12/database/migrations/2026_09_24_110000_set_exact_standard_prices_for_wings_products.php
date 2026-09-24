<?php

use App\Models\Principal;
use App\Models\Product;
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
        // 36 Master Produk Wings Surya dengan Harga Standar tepat dari Kolom HARGA Excel (rounded IDR)
        $standardPrices = [
            // MIE : " SEDAAP" (Bag)
            'SM40AB'            => 2710.00,  // MIE SEDAP AYAM
            'SM40AB5'           => 13301.00, // MIE SEDAP AYAM BAWANG ISI 5
            'SM40S'             => 2710.00,  // MIE SEDAP SOTO
            'SM40S5'            => 13301.00, // MIE SEDAP SOTO ISI 5
            'SM40K'             => 2710.00,  // MIE SEDAP AYAM KRISPY
            'SM40KKS'           => 2710.00,  // MIE SEDAP KARI KENTAL SPESIAL
            'SM40B'             => 2710.00,  // MIE SEDAP BASO
            'SM40WHC'           => 2710.00,  // MIE SEDAP WHITE CURRY
            'SM40SSL'           => 2710.00,  // SINGAPORE SPICY LAKSA
            'SM40SM'            => 2710.00,  // MIE SEDAP SOTO MADURA
            'SM40AJ'            => 2710.00,  // MIE SEDAP AYAM JERIT
            'SM40AS'            => 2485.00,  // MIE SEDAP AYAM SPESIAL
            'SM40G'             => 2846.00,  // MIE SEDAP GORENG
            'SM40G5'            => 14007.00, // MIE SEDAP GORENG ISI 5
            'SM40GK'            => 2846.00,  // MIE SEDAP GORENG KRIUK
            'SM40SP'            => 2846.00,  // SALERO PADANG
            'SM40ABL'           => 2846.00,  // AYAM BAKAR LIMAU
            'SM40KCB'           => 2846.00,  // KOREAN CHEESE BULDAK
            'SM40GAC'           => 2846.00,  // MIE SEDAP GORENG AYAM CRISPI

            // MIE : " SEDAAP SELECTION"
            'SM40KSS'           => 2710.00,  // KOREAN SPICY SOUP
            'SM40KSC'           => 2846.00,  // KOREAN SPICY CHICKEN

            // MIE : " SEDAAP CUP"
            'SMC12B'            => 4165.00,  // BASO
            'SMC12S'            => 4165.00,  // SOTO
            'SMC12KS'           => 4165.00,  // KARI SPESIAL
            'SMC12AJ'           => 4165.00,  // AYAM JERIT
            'SMC12BB'           => 4165.00,  // BASO BLEDUK
            'SMC12AN'           => 4165.00,  // AYAM NAMPOL
            'SMC12KM'           => 4165.00,  // KARI MERCON
            'SMC12G'            => 4367.00,  // GORENG

            // MIE : " SEDAAP CUP SELECTION"
            'SMC12KSS'          => 4367.00,  // KOREAN SPICY SOUP
            'SMC12KSC'          => 4367.00,  // KOREAN SPICY CHICKEN

            // RAMEN "YES"
            'RY40HCR/TCY (MT)'  => 3050.00,  // HAKATA CHICKEN RAMEN
            'RY60HCR/TCY (GT)'  => 3050.00,  // TOKYO CHICKENYAKITORI

            // MIE : "TASTY"
            'SM12BA/AG/BY/CT'   => 5100.00,  // TASTY

            // MIE : " SEDAAP BAKED"
            'SMB24G'            => 2400.00,  // BAKED GORENG
            'SMB24S'            => 2400.00,  // BAKED SOTO
        ];

        // 1. Update harga standar pada tabel products
        foreach ($standardPrices as $sku => $price) {
            Product::where('sku_code', $sku)
                ->orWhere('sku_code', $sku . ' ')
                ->update([
                    'price' => $price,
                    'is_active' => true,
                ]);
        }

        // 2. Sesuaikan riwayat submissions jika ada
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
                        $price = $standardPrices[$sku];
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
