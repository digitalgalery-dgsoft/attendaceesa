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
            'description' => 'Pencatatan volume dan transaksi penjualan harian produk Dulux & Catylac (Galon, Pail, Sub Brand, Volume Liter) sesuai format Raw Offtake & Rekap Toko.',
            'category' => 'offtake',
            'is_active' => true,
        ]);

        $fields = [
            [
                'field_label' => 'Brand',
                'field_name' => 'brand',
                'field_type' => 'dropdown',
                'options' => ['Dulux', 'Catylac', 'Maxilite'],
                'is_required' => true,
            ],
            [
                'field_label' => 'Brand (RM / Base)',
                'field_name' => 'brand_rm_base',
                'field_type' => 'dropdown',
                'options' => ['Dulux RM', 'Dulux Base', 'Catylac RM', 'Catylac Base'],
                'is_required' => true,
            ],
            [
                'field_label' => 'Sub Brand',
                'field_name' => 'sub_brand',
                'field_type' => 'text',
                'placeholder' => 'Contoh: Catylac Interior, Weathershield, Aquashield, Pentalite, Catylac Exterior, Easy Clean, V-Gloss...',
                'is_required' => true,
            ],
            [
                'field_label' => 'Sub Brand Spesifik / Varian (Sub Brand 1)',
                'field_name' => 'sub_brand1',
                'field_type' => 'text',
                'placeholder' => 'Varian khusus jika ada',
                'is_required' => false,
            ],
            [
                'field_label' => 'Detail RM / Base (Sub Brand 2)',
                'field_name' => 'sub_brand2',
                'field_type' => 'text',
                'placeholder' => 'Detail RM atau Base',
                'is_required' => false,
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
                'is_required' => true,
            ],
            [
                'field_label' => 'Kuantiti Galon Terjual (Unit)',
                'field_name' => 'qty_galon',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
            ],
            [
                'field_label' => 'Volume Galon (Liter)',
                'field_name' => 'volume_galon_l',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => false,
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
                'is_required' => true,
            ],
            [
                'field_label' => 'Kuantiti Pail Terjual (Unit)',
                'field_name' => 'qty_pail',
                'field_type' => 'number',
                'placeholder' => '0',
                'is_required' => false,
            ],
            [
                'field_label' => 'Volume Pail (Liter)',
                'field_name' => 'volume_pail_l',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => false,
            ],
            [
                'field_label' => 'Total Volume Kuantiti Unit (Galon + Pail)',
                'field_name' => 'total_volume_unit',
                'field_type' => 'number',
                'placeholder' => 'Total unit',
                'is_required' => false,
            ],
            [
                'field_label' => 'Total Volume Penjualan (Liter)',
                'field_name' => 'total_volume_liter',
                'field_type' => 'number',
                'placeholder' => '0.00',
                'is_required' => true,
            ],
            [
                'field_label' => 'Total Nilai Penjualan (Rupiah)',
                'field_name' => 'total_nilai_sales_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false,
            ],
            [
                'field_label' => 'Status Transaksi',
                'field_name' => 'status_transaksi',
                'field_type' => 'dropdown',
                'options' => ['Agency', 'Direct', 'Retailer / Toko', 'Lainnya'],
                'is_required' => false,
            ],
            [
                'field_label' => 'Foto Bukti Offtake Card / Nota Penjualan',
                'field_name' => 'foto_nota_penjualan',
                'field_type' => 'camera_photo',
                'is_required' => true,
            ],
            [
                'field_label' => 'Catatan Penjualan & Program Promo Toko',
                'field_name' => 'catatan_penjualan',
                'field_type' => 'textarea',
                'placeholder' => 'Catatan tambahan transaksi hari ini...',
                'is_required' => false,
            ],
        ];

        // Delete old fields
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
