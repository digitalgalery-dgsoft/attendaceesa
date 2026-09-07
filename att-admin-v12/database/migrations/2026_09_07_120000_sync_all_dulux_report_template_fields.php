<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;
use App\Models\ReportSubmission;
use App\Models\ReportSubmissionValue;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Jalankan sinkronisasi template Dulux resmi
        try {
            ReportTemplate::syncDuluxMergedStockEnd();
        } catch (\Throwable $e) {
            \Log::error("Migration error syncing Dulux templates: " . $e->getMessage());
        }

        // 2. Pastikan template OOS (RPT-DULUX-OOS-SSO) memiliki 8 field lengkap
        $oosTemplate = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if ($oosTemplate) {
            $readyMixColors = [
                'Bukan Ready Mix (Base Oplos)',
                'Brilliant White (1001)',
                'White (1000)',
                'Black / Hitam (1002)',
                'Barley White (1003)',
                'Off White',
                'Grey / Abu-abu',
                'Cream / Vanilla',
                'Tropical Green / Hijau',
                'Sky Blue / Biru',
                'Sunshine / Kuning',
                'Signal Red / Merah',
                'Mocha / Coklat',
                'Warna Ready Mix Lainnya',
            ];

            $oosReasons = [
                '1. Sudah buka PO namun belum ada pengiriman ke toko',
                '2. Sudah buka PO namun kendala stock di distributor/pabrik',
                '3. Kendala pembayaran toko (limit kredit / kiriman diblokir)',
                '4. Barang sedang dalam proses pengiriman ke toko',
                '5. Stok DC / Hub Modern Trade Kosong',
                '6. PO Toko Belum Diterbitkan Buyer / PIC',
                '7. Toko belum bersedia reorder / menunggu omset',
            ];

            $oosFields = [
                ['field_label' => 'Tipe Gerai / Channel Toko', 'field_name' => 'channel_toko', 'field_type' => 'dropdown', 'options' => ['Specialist Traditional Store (SSO)', 'Modern Outlet / Toko Modern (LSO)'], 'is_required' => true],
                ['field_label' => 'Pilih Produk Dulux yang Mengalami Out of Stock (OOS)', 'field_name' => 'produk_oos', 'field_type' => 'product_select', 'is_required' => true],
                ['field_label' => 'Kemasan / Size yang Kosong', 'field_name' => 'kemasan_size_oos', 'field_type' => 'dropdown', 'options' => ['Small Tin (1L / 1Kg)', 'Galon (2.5L / 4-5Kg)', 'Pail Besar (20L / 25Kg)'], 'is_required' => true],
                ['field_label' => 'Base / Kategori Warna yang Kosong', 'field_name' => 'base_warna_oos', 'field_type' => 'dropdown', 'options' => ['Base A', 'Base B', 'Base C', 'Base D', 'Ready Mix / Warna Jadi', 'Alkali Primer / Cat Dasar'], 'is_required' => true],
                ['field_label' => 'Pilihan Warna Ready Mix (Jika Warna Jadi Kosong)', 'field_name' => 'warna_ready_mix_oos', 'field_type' => 'dropdown', 'options' => $readyMixColors, 'is_required' => true],
                ['field_label' => 'Lama Kondisi Barang Kosong (Jumlah Hari)', 'field_name' => 'lama_oos_hari', 'field_type' => 'number', 'placeholder' => 'Contoh: 7 (hari)', 'is_required' => true],
                ['field_label' => 'Saran Kuantiti Order ke Toko (Qty Kemasan)', 'field_name' => 'saran_qty_order', 'field_type' => 'number', 'placeholder' => 'Saran kuantiti order', 'is_required' => false],
                ['field_label' => 'Penyebab / Alasan Out of Stock (OOS)', 'field_name' => 'alasan_oos', 'field_type' => 'dropdown', 'options' => $oosReasons, 'is_required' => true],
            ];

            foreach ($oosFields as $index => $f) {
                ReportFormField::updateOrCreate(
                    [
                        'report_template_id' => $oosTemplate->id,
                        'field_name' => $f['field_name'],
                    ],
                    array_merge($f, [
                        'order_index' => $index + 1,
                    ])
                );
            }
        }

        // 3. Re-link foreign key report_form_field_id pada report_submission_values yang null/yatim
        try {
            $orphans = ReportSubmissionValue::where(function ($q) {
                $q->whereNull('report_form_field_id')
                  ->orWhere('report_form_field_id', 0);
            })->with('submission')->get();

            foreach ($orphans as $val) {
                if ($val->submission && $val->submission->report_template_id) {
                    $matchedField = ReportFormField::where('report_template_id', $val->submission->report_template_id)
                        ->where('field_name', $val->field_name)
                        ->first();
                    if ($matchedField) {
                        $val->update(['report_form_field_id' => $matchedField->id]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("Re-linking orphaned report submission values: " . $e->getMessage());
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
