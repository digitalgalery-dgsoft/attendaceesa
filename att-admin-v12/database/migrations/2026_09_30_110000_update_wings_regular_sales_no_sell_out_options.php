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
        $template = ReportTemplate::where('code', 'RPT-WINGS-REGULAR-SALES-01')->first();
        if ($template) {
            $field = ReportFormField::where('report_template_id', $template->id)
                ->where('field_name', 'alasan_no_sell_out')
                ->first();

            if ($field) {
                $field->options = ['Stock Kosong (OOS)'];
                $field->help_text = 'Alasan tidak adanya penjualan sell out di toko (Stock Kosong OOS)';
                $field->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-REGULAR-SALES-01')->first();
        if ($template) {
            $field = ReportFormField::where('report_template_id', $template->id)
                ->where('field_name', 'alasan_no_sell_out')
                ->first();

            if ($field) {
                $field->options = ['Toko Tidak Mengijinkan', 'Barang OOS'];
                $field->help_text = 'Alasan tidak adanya penjualan sell out di toko';
                $field->save();
            }
        }
    }
};
