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

        // 1. Hapus field status ketersediaan tinter
        ReportFormField::where('report_template_id', $stockEnd->id)
            ->whereIn('field_name', [
                'status_ketersediaan_tinter',
                'status_ketersediaan_tinter_di_toko',
            ])
            ->delete();

        // 2. Buat atau perbarui field stock_items_json untuk menampung rincian multi-produk keranjang Stock End
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $stockEnd->id,
                'field_name' => 'stock_items_json',
            ],
            [
                'field_label' => 'Rincian Stok Produk (JSON)',
                'field_type' => 'textarea',
                'is_required' => false,
                'order_index' => 99,
                'placeholder' => 'Data terstruktur rincian stok produk terkonsolidasi',
            ]
        );

        // 3. Pastikan field tipe_tinter_warna dan qty_kaleng_tinta ada untuk produk kategori Tinta/Tinter (optional)
        $tinterOptions = [
            'Dramatone - High Hide White (WH)',
            'Dramatone - Deep Black (BK)',
            'Dramatone - Oxide Red (OR)',
            'Dramatone - Bright Red (BR)',
            'Dramatone - Oxide Yellow (OY)',
            'Dramatone - Organic Yellow (YR)',
            'Dramatone - Phthalo Blue (BL)',
            'Dramatone - Phthalo Green (GR)',
            'Dramatone - Magenta (MG)',
            'Dramatone - Violet (VT)',
            'Acotone - Exterior Red (ER)',
            'Acotone - Exterior Yellow (EY)',
            'Acotone - Exterior Orange (EO)',
            'Acotone - Exterior Brown (EB)',
            'Lainnya / Varian Tinter Baru',
        ];

        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $stockEnd->id,
                'field_name' => 'tipe_tinter_warna',
            ],
            [
                'field_label' => 'Tipe Tinter / Warna Pasta Pewarna',
                'field_type' => 'dropdown',
                'options' => $tinterOptions,
                'is_required' => false,
                'order_index' => 12,
            ]
        );

        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $stockEnd->id,
                'field_name' => 'qty_kaleng_tinta',
            ],
            [
                'field_label' => 'Kuantiti / Jumlah Kaleng Tinta Tinter',
                'field_type' => 'number',
                'placeholder' => 'Jumlah kaleng tinter',
                'is_required' => false,
                'order_index' => 13,
            ]
        );

        // 4. Buat atau perbarui field status akses gudang toko jika belum ada
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $stockEnd->id,
                'field_name' => 'status_akses_gudang',
            ],
            [
                'field_label' => 'Status Akses Pengecekan Gudang Toko',
                'field_type' => 'radio',
                'options' => [
                    'Full Access (Bisa Cek Rak & Gudang Toko Bebas)',
                    'Half Access (Hanya Cek Rak Depan Toko)',
                    'No Access (Toko Menolak Cek Fisik / Data Estimasi)',
                ],
                'is_required' => false,
                'order_index' => 14,
            ]
        );

        // 5. Pastikan field foto bukti fisik ada
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $stockEnd->id,
                'field_name' => 'foto_stok',
            ],
            [
                'field_label' => 'Foto Bukti Fisik Stok Toko & Gudang',
                'field_type' => 'image',
                'is_required' => false,
                'order_index' => 15,
            ]
        );

        // 6. Set is_required = false pada produk, brand, dan volume_liter agar fleksibel multi-item
        ReportFormField::where('report_template_id', $stockEnd->id)
            ->whereIn('field_name', ['produk', 'brand', 'volume_liter', 'keterangan_akses'])
            ->update(['is_required' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $stockEnd = ReportTemplate::where('code', 'RPT-DULUX-STOCK-END')->first();
        if ($stockEnd) {
            ReportFormField::where('report_template_id', $stockEnd->id)
                ->whereIn('field_name', ['stock_items_json'])
                ->delete();
        }
    }
};
