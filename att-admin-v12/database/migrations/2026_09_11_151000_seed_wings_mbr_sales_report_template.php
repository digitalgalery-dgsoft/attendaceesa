<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan seluruh entitas PT WINGS SURYA & PT LION WINGS
        $wingsPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS%')
              ->orWhere('name', 'LIKE', '%LION%')
              ->orWhere('code', 'LIKE', '%WINGS%')
              ->orWhere('subdomain', 'LIKE', '%wings%');
        })->get();

        if ($wingsPrincipals->isEmpty()) {
            $primaryWings = Principal::firstOrCreate(
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
            $wingsPrincipals = collect([$primaryWings]);
        }

        $primaryWings = $wingsPrincipals->first();
        $allWingsIds = $wingsPrincipals->pluck('id')->toArray();

        // 2. Daftarkan 40 SKU Master Produk Mie Sedaap dari file acuan Laporan Penjualan.xlsx
        $mieSedaapSkus = [
            ['name' => 'MIE SEDAP GORENG', 'sku_code' => 'WS-MS-01', 'price' => 3100, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP SOTO', 'sku_code' => 'WS-MS-02', 'price' => 3100, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP GORENG ALA CHEF DEVINA', 'sku_code' => 'WS-MS-03', 'price' => 3300, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP BASO SPESIAL', 'sku_code' => 'WS-MS-04', 'price' => 3100, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP AYAM BAWANG', 'sku_code' => 'WS-MS-05', 'price' => 3100, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP AYAM SPESIAL', 'sku_code' => 'WS-MS-06', 'price' => 3100, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP KARI AYAM', 'sku_code' => 'WS-MS-07', 'price' => 3100, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP KARI KENTAL SPESIAL', 'sku_code' => 'WS-MS-08', 'price' => 3200, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP WHITE CURRY', 'sku_code' => 'WS-MS-09', 'price' => 3200, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP AYAM KRISPY', 'sku_code' => 'WS-MS-10', 'price' => 3200, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP AYAM JERIT', 'sku_code' => 'WS-MS-11', 'price' => 3200, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP NIKMAT HQQ SALERO PADANG', 'sku_code' => 'WS-MS-12', 'price' => 3300, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP NIKMAT HQQ AYAM BAKAR LIMAU', 'sku_code' => 'WS-MS-13', 'price' => 3300, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP NIKMAT HQQ SOTO MADURA', 'sku_code' => 'WS-MS-14', 'price' => 3300, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP SELECTION KOREAN SPICY CHICKEN', 'sku_code' => 'WS-MS-15', 'price' => 3400, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP SELECTION KOREAN SPICY SOUP', 'sku_code' => 'WS-MS-16', 'price' => 3400, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP SELECTION SINGAPORE SPICY LAKSA', 'sku_code' => 'WS-MS-17', 'price' => 3400, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP SELECTION KOREAN CHEESE BULDAK', 'sku_code' => 'WS-MS-18', 'price' => 3500, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP GORENG PACK ISI 5', 'sku_code' => 'WS-MS-19', 'price' => 15000, 'category' => 'Mie Instant', 'uom' => 'Pack'],
            ['name' => 'MIE SEDAP SOTO PACK ISI 5', 'sku_code' => 'WS-MS-20', 'price' => 15000, 'category' => 'Mie Instant', 'uom' => 'Pack'],
            ['name' => 'MIE SEDAP AYAM BAWANG PACK ISI 5', 'sku_code' => 'WS-MS-21', 'price' => 15000, 'category' => 'Mie Instant', 'uom' => 'Pack'],
            ['name' => 'MIE SEDAP BAKED GORENG', 'sku_code' => 'WS-MS-22', 'price' => 3600, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP BAKED SOTO', 'sku_code' => 'WS-MS-23', 'price' => 3600, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP TASTY BAKMI AYAM', 'sku_code' => 'WS-MS-24', 'price' => 5500, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP TASTY BAKMI AYAM GEPREK MATAH', 'sku_code' => 'WS-MS-25', 'price' => 5500, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP TASTY BEEF YAKINIKU', 'sku_code' => 'WS-MS-26', 'price' => 5800, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP TASTY CHICKEN TERIYAKI', 'sku_code' => 'WS-MS-27', 'price' => 5800, 'category' => 'Mie Instant', 'uom' => 'Pcs'],
            ['name' => 'MIE SEDAP CUP GORENG', 'sku_code' => 'WS-MS-28', 'price' => 4800, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP SOTO', 'sku_code' => 'WS-MS-29', 'price' => 4800, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP BASO', 'sku_code' => 'WS-MS-30', 'price' => 4800, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP KARI SPESIAL', 'sku_code' => 'WS-MS-31', 'price' => 4800, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP AYAM NAMPOL', 'sku_code' => 'WS-MS-32', 'price' => 5000, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP KOREAN SPICY CHICKEN', 'sku_code' => 'WS-MS-33', 'price' => 5200, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP KOREAN SPICY SOUP', 'sku_code' => 'WS-MS-34', 'price' => 5200, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP AYAM JERIT', 'sku_code' => 'WS-MS-35', 'price' => 5000, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP BASO BLEDUK', 'sku_code' => 'WS-MS-36', 'price' => 5000, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP KARI MERCON', 'sku_code' => 'WS-MS-37', 'price' => 5000, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP CUP SPICY LAKSA', 'sku_code' => 'WS-MS-38', 'price' => 5200, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP RAMEN YES HAKATA CHICKEN RAMEN', 'sku_code' => 'WS-MS-39', 'price' => 6000, 'category' => 'Mie Instant', 'uom' => 'Cup'],
            ['name' => 'MIE SEDAP RAMEN YES TOKYO CHICKEN YAKITORI', 'sku_code' => 'WS-MS-40', 'price' => 6000, 'category' => 'Mie Instant', 'uom' => 'Cup'],
        ];

        $createdProductIds = [];
        foreach ($mieSedaapSkus as $item) {
            $product = Product::firstOrCreate(
                [
                    'principal_id' => $primaryWings->id,
                    'sku_code' => $item['sku_code'],
                ],
                [
                    'name' => $item['name'],
                    'brand' => 'MIE SEDAAP',
                    'category' => $item['category'],
                    'price' => $item['price'],
                    'uom' => $item['uom'],
                    'is_active' => true,
                ]
            );
            $createdProductIds[] = $product->id;
        }

        // 3. Buat / Update Template Form Baru: Laporan Penjualan (Event MBR)
        $templateData = [
            'code' => 'RPT-WINGS-MBR-SALES-01',
            'title' => 'Laporan Penjualan (Event MBR)',
            'description' => 'Pencatatan transaksi penjualan multi-produk event MBR Wings Surya dengan metode keranjang (cart), harga toko, kuantiti, kalkulasi value, jenis pembayaran, foto struk per produk, dan foto sell out toko.',
            'category' => 'offtake',
            'report_group' => 'event_mbr',
            'require_gps' => true,
            'require_signature' => false,
            'is_active' => true,
            'version' => 1,
        ];

        if (Schema::hasColumn('report_templates', 'icon')) {
            $templateData['icon'] = 'cart-shopping';
        }
        if (Schema::hasColumn('report_templates', 'color')) {
            $templateData['color'] = '#D32F2F';
        }

        $template = ReportTemplate::updateOrCreate(
            ['code' => $templateData['code']],
            array_merge($templateData, ['principal_id' => $primaryWings->id])
        );

        // Sync ke seluruh entitas Wings
        $template->principals()->sync($allWingsIds);

        // Sync ke produk
        if (Schema::hasTable('report_template_product') && !empty($createdProductIds)) {
            $template->products()->sync($createdProductIds);
        }

        // 4. Daftarkan Field-Field Form Laporan Penjualan Event MBR
        $fields = [
            [
                'field_label' => 'Data Rincian Produk Penjualan (Cart JSON)',
                'field_name' => 'mbr_sales_items_json',
                'field_type' => 'text',
                'placeholder' => '[]',
                'help_text' => 'Data keranjang transaksi multi-produk penjualan event MBR (JSON)',
                'is_required' => true,
                'order_index' => 1,
            ],
            [
                'field_label' => 'Total Kuantiti Terjual (Pcs)',
                'field_name' => 'total_qty_penjualan',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total kuantiti fisik seluruh produk yang terjual',
                'is_required' => true,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Total Nilai Penjualan (Rp)',
                'field_name' => 'total_value_penjualan_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total omzet nilai penjualan seluruh produk dalam rupiah',
                'is_required' => true,
                'order_index' => 3,
            ],
            [
                'field_label' => 'Total Penjualan Bayar di Booth (Rp)',
                'field_name' => 'total_bayar_di_booth_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total pembayaran transaksi yang diterima langsung di booth',
                'is_required' => false,
                'order_index' => 4,
            ],
            [
                'field_label' => 'Total Penjualan Bayar di Kasir (Rp)',
                'field_name' => 'total_bayar_di_kasir_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total pembayaran transaksi yang dibayarkan di kasir toko',
                'is_required' => false,
                'order_index' => 5,
            ],
            [
                'field_label' => 'Foto Sell Out Toko',
                'field_name' => 'foto_sell_out_toko',
                'field_type' => 'photo',
                'placeholder' => 'Ambil foto dokumentasi display / booth toko',
                'help_text' => 'Dokumentasi booth / sell out toko saat event berlangsung',
                'is_required' => true,
                'order_index' => 6,
            ],
        ];

        foreach ($fields as $fData) {
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $template->id,
                    'field_name' => $fData['field_name'],
                ],
                array_merge($fData, [
                    'report_template_id' => $template->id,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-SALES-01')->first();
        if ($template) {
            $template->fields()->delete();
            $template->delete();
        }
    }
};
