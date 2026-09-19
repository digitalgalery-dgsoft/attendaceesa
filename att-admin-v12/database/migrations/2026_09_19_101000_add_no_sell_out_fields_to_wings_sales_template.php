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
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-SALES-01')->first();
        if (!$template) {
            return;
        }

        // 1. Tambah field status_penjualan ('Pembayaran di booth' / 'No Sell Out')
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $template->id,
                'field_name' => 'status_penjualan',
            ],
            [
                'report_template_id' => $template->id,
                'field_label' => 'Status Penjualan',
                'field_name' => 'status_penjualan',
                'field_type' => 'radio',
                'options' => ['Pembayaran di booth', 'No Sell Out'],
                'placeholder' => null,
                'help_text' => 'Pilih status penjualan event hari ini',
                'is_required' => false,
                'order_index' => 0,
            ]
        );

        // 2. Tambah field alasan_no_sell_out ('Toko Tidak Mengijinkan' / 'Barang OOS')
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $template->id,
                'field_name' => 'alasan_no_sell_out',
            ],
            [
                'report_template_id' => $template->id,
                'field_label' => 'Alasan No Sell Out',
                'field_name' => 'alasan_no_sell_out',
                'field_type' => 'radio',
                'options' => ['Toko Tidak Mengijinkan', 'Barang OOS'],
                'placeholder' => null,
                'help_text' => 'Alasan tidak adanya penjualan sell out di toko',
                'is_required' => false,
                'order_index' => 1,
            ]
        );

        // 3. Pastikan foto_sell_out_toko tidak memblokir laporan No Sell Out
        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'foto_sell_out_toko')
            ->update([
                'is_required' => false,
                'help_text' => 'Dokumentasi booth / sell out toko saat event berlangsung (Wajib jika ada penjualan)',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-SALES-01')->first();
        if (!$template) {
            return;
        }

        ReportFormField::where('report_template_id', $template->id)
            ->whereIn('field_name', ['status_penjualan', 'alasan_no_sell_out'])
            ->delete();
    }
};
