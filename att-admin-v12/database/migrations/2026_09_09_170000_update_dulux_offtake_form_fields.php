<?php

use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $template = ReportTemplate::where('code', 'RPT-DULUX-OFFTAKE-01')->first();
        if (!$template) {
            return;
        }

        $template->update([
            'title' => 'Laporan Offtake / Penjualan Harian Dulux & Catylac',
            'description' => 'Pencatatan transaksi penjualan harian produk Dulux & Catylac (Tin, Galon, Pail, Grand Total Liter & Rp) beserta trafik customer dan bukti foto card offtake & nota.',
            'category' => 'offtake',
            'is_active' => true,
        ]);

        $fields = [
            [
                'field_label' => 'Tipe Transaksi Hari Ini',
                'field_name' => 'tipe_laporan_offtake',
                'field_type' => 'dropdown',
                'options' => ['Sale', 'No Sale'],
                'is_required' => true,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Pilih Produk / Sub Brand',
                'field_name' => 'sub_brand',
                'field_type' => 'product_select',
                'placeholder' => 'Pilih produk dari katalog...',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Brand',
                'field_name' => 'brand',
                'field_type' => 'dropdown',
                'options' => ['Dulux', 'Catylac', 'Maxilite'],
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Brand (RM / Base)',
                'field_name' => 'brand_rm_base',
                'field_type' => 'dropdown',
                'options' => ['Dulux RM', 'Dulux Base', 'Catylac RM', 'Catylac Base'],
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Sub Brand Spesifik / Varian (Sub Brand 1)',
                'field_name' => 'sub_brand1',
                'field_type' => 'text',
                'placeholder' => 'Terisi otomatis...',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Detail RM / Base (Sub Brand 2)',
                'field_name' => 'sub_brand2',
                'field_type' => 'text',
                'placeholder' => 'Terisi otomatis...',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Kemasan Tin',
                'field_name' => 'kemasan_tin',
                'field_type' => 'dropdown',
                'options' => ['0.8 Liter', '0.9 Liter', '1 Liter', 'Tidak Ada Tin'],
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Kuantiti Tin Terjual (Unit)',
                'field_name' => 'qty_tin',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Volume Tin (Liter)',
                'field_name' => 'volume_tin_l',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Kemasan Galon',
                'field_name' => 'kemasan_galon',
                'field_type' => 'dropdown',
                'options' => [
                    '0.8 Liter',
                    '0.9 Liter',
                    '1 Liter',
                    '2.4 Liter',
                    '2.5 Liter',
                    '3.5 Liter',
                    '4 Liter',
                    '4.5 Liter',
                    '5 Liter',
                    'Tidak Ada Galon'
                ],
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Kuantiti Galon Terjual (Unit)',
                'field_name' => 'qty_galon',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Volume Galon (Liter)',
                'field_name' => 'volume_galon_l',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Kemasan Pail',
                'field_name' => 'kemasan_pail',
                'field_type' => 'dropdown',
                'options' => [
                    '18.5 Liter',
                    '20 Liter',
                    '21 Liter',
                    '22 Liter',
                    '25 Liter',
                    'Tidak Ada Pail'
                ],
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Kuantiti Pail Terjual (Unit)',
                'field_name' => 'qty_pail',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Volume Pail (Liter)',
                'field_name' => 'volume_pail_l',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Grand Total Kuantiti Unit (Tin + Galon + Pail)',
                'field_name' => 'total_volume_unit',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Grand Total Volume Penjualan (Liter)',
                'field_name' => 'total_volume_liter',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Grand Total Nilai Penjualan (Rupiah)',
                'field_name' => 'total_nilai_sales_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false,
                'is_readonly' => true,
            ],
            [
                'field_label' => 'Jumlah Customer Masuk',
                'field_name' => 'jml_customer_masuk',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Jumlah Cust yang Beli Cat',
                'field_name' => 'jml_customer_beli_cat',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Jumlah Cust Yang Beli Produk Dulux',
                'field_name' => 'jml_customer_beli_dulux',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Foto Card Offtake (1 Foto)',
                'field_name' => 'foto_card_offtake',
                'field_type' => 'photo', // Kamera / Galeri
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Foto Nota Penjualan (Multi Foto)',
                'field_name' => 'foto_nota_penjualan',
                'field_type' => 'multi_photo', // Kamera / Galeri
                'is_required' => false,
                'is_readonly' => false,
            ],
            [
                'field_label' => 'Rincian Transaksi Produk (JSON)',
                'field_name' => 'offtake_items_json',
                'field_type' => 'textarea',
                'placeholder' => '[]',
                'is_required' => false,
                'is_readonly' => true,
            ],
        ];

        // Hapus field lama
        ReportFormField::where('report_template_id', $template->id)->delete();

        foreach ($fields as $index => $f) {
            ReportFormField::create(array_merge($f, [
                'report_template_id' => $template->id,
                'order_index' => $index + 1,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
