<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-FREETASTE-01')->first();
        if (!$template) {
            return;
        }

        // 1. Pastikan foto_kegiatan_sampling terdaftar (urutan 6)
        $fieldKegiatan = ReportFormField::firstOrNew([
            'report_template_id' => $template->id,
            'field_name' => 'foto_kegiatan_sampling',
        ]);
        $fieldKegiatan->field_label = 'Foto Kegiatan Sampling';
        $fieldKegiatan->field_type = 'photo';
        $fieldKegiatan->placeholder = 'Ambil foto dokumentasi aktivitas memasak / pembagian sampling tester';
        $fieldKegiatan->help_text = 'Foto dokumentasi aktivitas sampling saat berlangsung bersama pengunjung';
        $fieldKegiatan->is_required = false;
        $fieldKegiatan->order_index = 6;
        $fieldKegiatan->save();

        // 2. Sesuaikan urutan foto_booth_sampling ke 7
        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'foto_booth_sampling')
            ->update([
                'field_label' => 'Foto Stand / Booth Sampling',
                'order_index' => 7,
                'is_required' => true,
            ]);

        // 3. Tambah field foto_stock_akhir (urutan 8)
        $fieldStock = ReportFormField::firstOrNew([
            'report_template_id' => $template->id,
            'field_name' => 'foto_stock_akhir',
        ]);
        $fieldStock->field_label = 'Foto Stock Akhir';
        $fieldStock->field_type = 'photo';
        $fieldStock->placeholder = 'Ambil foto dokumentasi sisa stok akhir mie sampling';
        $fieldStock->help_text = 'Foto dokumentasi fisik sisa stok produk mie setelah aktivitas sampling selesai';
        $fieldStock->is_required = false;
        $fieldStock->order_index = 8;
        $fieldStock->save();

        // 4. Sesuaikan catatan_sampling ke 9
        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'catatan_sampling')
            ->update([
                'order_index' => 9,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-FREETASTE-01')->first();
        if ($template) {
            ReportFormField::where('report_template_id', $template->id)
                ->where('field_name', 'foto_stock_akhir')
                ->delete();
        }
    }
};
