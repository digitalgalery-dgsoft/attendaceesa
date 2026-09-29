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

        // 2. Ambil seluruh SKU produk aktif Wings Surya
        $activeWingsProductIds = Product::whereIn('principal_id', $allWingsIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // 3. Buat / Update Template Form Baru: Laporan Penjualan (Regular)
        $templateData = [
            'code' => 'RPT-WINGS-REGULAR-SALES-01',
            'title' => 'Laporan Penjualan (Regular)',
            'description' => 'Pencatatan transaksi penjualan produk reguler Wings Surya: 1 produk 1 submit laporan, harga toko, kuantiti, kalkulasi value, jenis pembayaran (tanpa foto sell out di akhir).',
            'category' => 'offtake',
            'report_group' => 'regular',
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

        // Sync ke master produk Wings
        if (Schema::hasTable('report_template_product') && !empty($activeWingsProductIds)) {
            $template->products()->sync($activeWingsProductIds);
        }

        // 4. Daftarkan Field-Field Form Laporan Penjualan (Regular) - Tanpa Foto Sell Out di Akhir
        $fields = [
            [
                'field_label' => 'Status Penjualan',
                'field_name' => 'status_penjualan',
                'field_type' => 'radio',
                'options' => ['Pembayaran di booth', 'No Sell Out'],
                'placeholder' => null,
                'help_text' => 'Pilih status penjualan reguler hari ini',
                'is_required' => false,
                'order_index' => 0,
            ],
            [
                'field_label' => 'Alasan No Sell Out',
                'field_name' => 'alasan_no_sell_out',
                'field_type' => 'radio',
                'options' => ['Stock Kosong (OOS)'],
                'placeholder' => null,
                'help_text' => 'Alasan tidak adanya penjualan sell out di toko (Stock Kosong OOS)',
                'is_required' => false,
                'order_index' => 1,
            ],
            [
                'field_label' => 'Keterangan No Sell Out',
                'field_name' => 'keterangan_no_sell_out',
                'field_type' => 'text',
                'placeholder' => 'Keterangan kendala jika ada...',
                'help_text' => 'Catatan penjelasan tambahan untuk kondisi No Sell Out',
                'is_required' => false,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Data Rincian Produk Penjualan (Cart JSON)',
                'field_name' => 'mbr_sales_items_json',
                'field_type' => 'text',
                'placeholder' => '[]',
                'help_text' => 'Data transaksi produk penjualan reguler (JSON)',
                'is_required' => true,
                'order_index' => 3,
            ],
            [
                'field_label' => 'Nama Produk',
                'field_name' => 'nama_produk',
                'field_type' => 'text',
                'placeholder' => 'Nama varian produk',
                'help_text' => 'Nama produk reguler yang dilaporkan',
                'is_required' => false,
                'order_index' => 4,
            ],
            [
                'field_label' => 'Harga Toko (Rp)',
                'field_name' => 'harga_toko',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Harga jual per unit di toko',
                'is_required' => false,
                'order_index' => 5,
            ],
            [
                'field_label' => 'Kuantiti Terjual',
                'field_name' => 'qty_penjualan',
                'field_type' => 'number',
                'placeholder' => '1',
                'help_text' => 'Jumlah kuantiti produk yang terjual',
                'is_required' => false,
                'order_index' => 6,
            ],
            [
                'field_label' => 'Total Kuantiti Terjual (Pcs)',
                'field_name' => 'total_qty_penjualan',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total kuantiti fisik produk yang terjual',
                'is_required' => true,
                'order_index' => 7,
            ],
            [
                'field_label' => 'Total Nilai Penjualan (Rp)',
                'field_name' => 'total_value_penjualan_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total omzet nilai penjualan produk dalam rupiah',
                'is_required' => true,
                'order_index' => 8,
            ],
            [
                'field_label' => 'Total Penjualan Bayar di Booth (Rp)',
                'field_name' => 'total_bayar_di_booth_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total pembayaran transaksi yang diterima langsung di booth',
                'is_required' => false,
                'order_index' => 9,
            ],
            [
                'field_label' => 'Total Penjualan Bayar di Kasir (Rp)',
                'field_name' => 'total_bayar_di_kasir_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total pembayaran transaksi yang dibayarkan di kasir toko',
                'is_required' => false,
                'order_index' => 10,
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
        $template = ReportTemplate::where('code', 'RPT-WINGS-REGULAR-SALES-01')->first();
        if ($template) {
            $template->fields()->delete();
            $template->delete();
        }
    }
};
