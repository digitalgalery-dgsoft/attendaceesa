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
        $stockEnd = ReportTemplate::where('code', 'RPT-DULUX-STOCK-END')->first();
        if (!$stockEnd) {
            return;
        }

        $stockEnd->update([
            'title' => 'Laporan Stock End (Stock Opname Bulanan) Dulux & Catylac',
            'description' => 'Pencatatan sisa stok fisik akhir bulan seluruh SKU Dulux, Catylac, dan Catylac Smart Choice di toko mitra.',
            'category' => 'stock',
            'require_gps' => true,
            'is_active' => true,
        ]);

        $products = [
            'Alkali Killer', 'Alkali Resisting Primer', 'Ambiance', 'Ambiance Base',
            'Ambiance Diamond Glow', 'Ambiance Diamond Glow Base', 'Aquashield', 'Aquashield 2K',
            'Aquashield Base', 'Aquashield Max', 'Aquashield Max Base', 'Catylac Ceiling',
            'Catylac Exterior', 'Catylac Exterior Base', 'Catylac Glow', 'Catylac Glow Base',
            'Catylac Hi-Gloss', 'Catylac Interior', 'Catylac Interior 2in1',
            'Catylac Interior 2in1 Base', 'Catylac Interior Base', 'Catylac Plamur',
            'Catylac Primer Eksterior', 'Catylac Primer Interior', 'Catylac Smart Choice Exterior',
            'Catylac Smart Choice Exterior Primer', 'Catylac Smart Choice Interior',
            'Catylac Smart Choice Interior Primer', 'Ceiling', 'Easy Clean',
            'Easy Clean Anti - Viral', 'Easy Clean Anti - Viral Base', 'Easy Clean Base',
            'Hammerite - DTR', 'Hammerite Thinner', 'Pearl Glo', 'Pearl Glo Base',
            'Pentalite', 'Pentalite Antibac', 'Pentalite Antibac Base', 'Pentalite Base',
            'Pentalite Light & Space', 'Powerflexx', 'Powerflexx Base', 'Powerflexx Next Gen',
            'Powerflexx Next Gen Base', 'Tinter', 'V-Gloss', 'V-Gloss Base', 'V-Gloss Doff',
            'V-Gloss High', 'Wallfiller', 'Weathershield', 'Weathershield Base',
            'Weathershield Core Dualshield', 'Weathershield Core Dualshield Base',
            'Weathershield Dirt Resistance', 'Weathershield Dirt Resistance Base',
            'Weathershield Flash', 'Weathershield Flash Base', 'Weathershield Gloss',
            'Weathershield Power Sealer', 'Weathershield Primer', 'Weathershield Putty',
            'Weathershield Roof Paint'
        ];

        $fields = [
            [
                'field_label' => 'Tanggal Pencatatan Stok',
                'field_name' => 'tanggal_pencatatan_stok',
                'field_type' => 'date',
                'is_required' => true,
                'order_index' => 1,
            ],
            [
                'field_label' => 'Brand Cat',
                'field_name' => 'brand',
                'field_type' => 'dropdown',
                'options' => ['Dulux', 'Catylac', 'Catylac Smart Choice'],
                'is_required' => true,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Keterangan Akses Toko',
                'field_name' => 'keterangan_akses',
                'field_type' => 'dropdown',
                'options' => ['Full Acces', 'Half Acces', 'No Acces'],
                'is_required' => false,
                'order_index' => 3,
            ],
            [
                'field_label' => 'Produk Cat',
                'field_name' => 'produk',
                'field_type' => 'dropdown',
                'options' => $products,
                'is_required' => true,
                'order_index' => 4,
            ],
            [
                'field_label' => 'Nama / Kode Warna',
                'field_name' => 'warna',
                'field_type' => 'text',
                'placeholder' => 'ALL / Putih / Nama Warna',
                'is_required' => false,
                'order_index' => 5,
            ],
            [
                'field_label' => 'Ukuran Kemasan Galon (L)',
                'field_name' => 'kemasan_galon',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 2.5',
                'is_required' => false,
                'order_index' => 6,
            ],
            [
                'field_label' => 'Kuantiti Galon (Qty)',
                'field_name' => 'kuantiti_galon',
                'field_type' => 'number',
                'placeholder' => 'Jumlah kaleng galon',
                'is_required' => false,
                'order_index' => 7,
            ],
            [
                'field_label' => 'Ukuran Kemasan Pail (L)',
                'field_name' => 'kemasan_pail',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 20',
                'is_required' => false,
                'order_index' => 8,
            ],
            [
                'field_label' => 'Kuantiti Pail (Qty)',
                'field_name' => 'kuantiti_pail',
                'field_type' => 'number',
                'placeholder' => 'Jumlah kaleng pail',
                'is_required' => false,
                'order_index' => 9,
            ],
            [
                'field_label' => 'Total Volume Stok (Liter)',
                'field_name' => 'volume_liter',
                'field_type' => 'number',
                'placeholder' => 'Total volume (L)',
                'is_required' => true,
                'order_index' => 10,
            ],
            [
                'field_label' => 'Faktor Konversi / Density (conf)',
                'field_name' => 'conf',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 1.27',
                'is_required' => false,
                'order_index' => 11,
            ],
            [
                'field_label' => 'Foto Bukti Fisik Stok Toko',
                'field_name' => 'foto_stok',
                'field_type' => 'image',
                'is_required' => false,
                'order_index' => 12,
            ],
            [
                'field_label' => 'Catatan Tambahan',
                'field_name' => 'catatan',
                'field_type' => 'textarea',
                'placeholder' => 'Catatan stok opname...',
                'is_required' => false,
                'order_index' => 13,
            ],
        ];

        // Hapus field lama dan ganti dengan field baru yang sinkron
        ReportFormField::where('report_template_id', $stockEnd->id)->delete();

        foreach ($fields as $f) {
            ReportFormField::create(array_merge($f, [
                'report_template_id' => $stockEnd->id,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
