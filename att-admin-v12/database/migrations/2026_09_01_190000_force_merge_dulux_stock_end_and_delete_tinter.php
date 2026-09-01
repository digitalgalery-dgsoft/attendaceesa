<?php

use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dapatkan seluruh Principal Dulux (PT ICI PAINTS INDONESIA, ALVA, TSM, dll)
        $duluxPrincipals = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('subdomain', 'dulux')
            ->get();

        $allDuluxIds = $duluxPrincipals->pluck('id')->toArray();
        $primaryDulux = $duluxPrincipals->first();

        if (!$primaryDulux && Principal::count() > 0) {
            $primaryDulux = Principal::first();
        }

        // 2. HAPUS TOTAL Laporan Tinter Terpisah (RPT-DULUX-TINTER-LSO)
        $tinterTemplates = ReportTemplate::where('code', 'RPT-DULUX-TINTER-LSO')
            ->orWhere('title', 'LIKE', '%Laporan Tinter%')
            ->get();

        foreach ($tinterTemplates as $tinter) {
            ReportFormField::where('report_template_id', $tinter->id)->delete();
            $tinter->principals()->detach();
            $tinter->assignments()->delete();
            $tinter->delete();
        }

        // 3. Pastikan Laporan Stock End (RPT-DULUX-STOCK-END) disatukan dengan 12 Field Lengkap
        $stockEnd = ReportTemplate::where('code', 'RPT-DULUX-STOCK-END')->first();
        if (!$stockEnd && $primaryDulux) {
            $stockEnd = ReportTemplate::create([
                'code' => 'RPT-DULUX-STOCK-END',
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Stock End (Stock Opname Bulanan) & Tinter Dulux',
                'description' => 'Pencatatan sisa stok fisik akhir bulan seluruh SKU Dulux & Catylac serta ketersediaan pasta tinter mesin tinting (Dramatone & Acotone) di toko.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
                'report_days' => [],
            ]);
        } elseif ($stockEnd) {
            $stockEnd->update([
                'title' => 'Laporan Stock End (Stock Opname Bulanan) & Tinter Dulux',
                'description' => 'Pencatatan sisa stok fisik akhir bulan seluruh SKU Dulux & Catylac serta ketersediaan pasta tinter mesin tinting (Dramatone & Acotone) di toko.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
            ]);
        }

        if ($stockEnd && !empty($allDuluxIds)) {
            $stockEnd->principals()->sync($allDuluxIds);
        }

        if ($stockEnd) {
            $dramatoneOptions = [
                'White (W1)',
                'Black (B1)',
                'Yellow Oxide (Y1)',
                'Red Oxide (R1)',
                'Organic Yellow (Y2)',
                'Organic Red (R2)',
                'Blue (BL)',
                'Green (GR)',
                'Magenta (MG)',
                'Orange (OR)',
                'Violet (VT)',
                'Semua Warna Dramatone / Full Set',
            ];

            $acotoneOptions = [
                'Acotone White (AW)',
                'Acotone Black (AB)',
                'Acotone Yellow Oxide (AYO)',
                'Acotone Red Oxide (ARO)',
                'Acotone Bright Yellow (AY2)',
                'Acotone Bright Red (AR2)',
                'Acotone Blue (ABL)',
                'Acotone Green (AGR)',
                'Acotone Magenta (AMG)',
                'Acotone Orange (AOR)',
                'Acotone Violet (AVT)',
                'Acotone Transparent Red (ATR)',
                'Acotone Transparent Yellow (ATY)',
                'Semua Warna Acotone / Full Set',
            ];

            $allTinterOptions = array_merge($dramatoneOptions, $acotoneOptions);

            $fields = [
                [
                    'field_label' => 'Pilih Produk Dulux / Catylac yang Dicek',
                    'field_name' => 'produk_stock_end',
                    'field_type' => 'product_select',
                    'is_required' => true,
                    'order_index' => 1,
                ],
                [
                    'field_label' => 'Base / Tipe Warna',
                    'field_name' => 'base_warna',
                    'field_type' => 'dropdown',
                    'options' => ['Base A (Putih/Light)', 'Base B (Medium)', 'Base C (Dark)', 'Base D (Clear/Deep)', 'Ready Mix (Warna Jadi Pabrik)', 'Cat Dasar Primer'],
                    'is_required' => true,
                    'order_index' => 2,
                ],
                [
                    'field_label' => 'Stok Fisik Kemasan Galon (Qty)',
                    'field_name' => 'stok_qty_galon',
                    'field_type' => 'number',
                    'placeholder' => 'Jumlah galon',
                    'is_required' => true,
                    'order_index' => 3,
                ],
                [
                    'field_label' => 'Stok Fisik Kemasan Pail (Qty)',
                    'field_name' => 'stok_qty_pail',
                    'field_type' => 'number',
                    'placeholder' => 'Jumlah pail',
                    'is_required' => true,
                    'order_index' => 4,
                ],
                [
                    'field_label' => 'Estimasi Total Volume Stok di Toko (Liter)',
                    'field_name' => 'total_volume_stok_liter',
                    'field_type' => 'number',
                    'placeholder' => 'Total volume liter',
                    'is_required' => true,
                    'order_index' => 5,
                ],
                [
                    'field_label' => 'Kategori Tinter / Mesin Tinting',
                    'field_name' => 'kategori_tinter',
                    'field_type' => 'dropdown',
                    'options' => ['Dramatone', 'Acotone', 'Tidak Ada Mesin / Non-Tinting'],
                    'default_value' => 'Dramatone',
                    'is_required' => true,
                    'order_index' => 6,
                ],
                [
                    'field_label' => 'Tipe Tinter / Warna Pasta Pewarna',
                    'field_name' => 'tipe_tinter_warna',
                    'field_type' => 'dropdown',
                    'options' => $allTinterOptions,
                    'is_required' => true,
                    'order_index' => 7,
                ],
                [
                    'field_label' => 'Kuantiti / Jumlah Kaleng Tinta Tinter',
                    'field_name' => 'qty_kaleng_tinta',
                    'field_type' => 'number',
                    'placeholder' => 'Jumlah kaleng tinter',
                    'is_required' => false,
                    'order_index' => 8,
                ],
                [
                    'field_label' => 'Status Ketersediaan Tinter di Toko',
                    'field_name' => 'status_ketersediaan_tinter',
                    'field_type' => 'radio',
                    'options' => ['Stok Aman (Siap Oplos)', 'Stok Menipis (Perlu Order Ulang)', 'Stok Habis (Mesin Tidak Bisa Oplos)', 'Tidak Ada Mesin'],
                    'is_required' => true,
                    'order_index' => 9,
                ],
                [
                    'field_label' => 'Status Akses Pengecekan Gudang Toko',
                    'field_name' => 'status_akses_gudang',
                    'field_type' => 'radio',
                    'options' => ['Full Access (Bisa Cek Rak & Gudang Toko Bebas)', 'Half Access (Hanya Cek Rak Depan Toko)', 'No Access (Toko Menolak Cek Fisik / Data Estimasi)'],
                    'is_required' => true,
                    'order_index' => 10,
                ],
                [
                    'field_label' => 'Foto Fisik Rak Display, Tumpukan Stok Gudang & Mesin Tinter',
                    'field_name' => 'foto_stok_gudang',
                    'field_type' => 'multi_photo',
                    'is_required' => true,
                    'order_index' => 11,
                ],
                [
                    'field_label' => 'Keterangan / Kendala Stok & Tinter Toko',
                    'field_name' => 'keterangan_stok_toko',
                    'field_type' => 'textarea',
                    'placeholder' => 'Catatan status stok lambat laku (slow moving), kelebihan stok, atau request restock tinter...',
                    'is_required' => false,
                    'order_index' => 12,
                ],
            ];

            // Rebuild all 12 fields
            ReportFormField::where('report_template_id', $stockEnd->id)->delete();

            foreach ($fields as $fieldData) {
                ReportFormField::create(array_merge($fieldData, [
                    'report_template_id' => $stockEnd->id,
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
