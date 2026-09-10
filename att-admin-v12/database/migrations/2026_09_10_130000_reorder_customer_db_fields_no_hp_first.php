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
        $template = ReportTemplate::where('code', 'RPT-DULUX-DATABASE-PELANGGAN')->first();
        if (!$template) {
            $template = ReportTemplate::where('code', 'LIKE', '%DATABASE-PELANGGAN%')->first();
        }
        if (!$template) {
            return;
        }

        // Ordered list with no_hp_pelanggan as #1
        $orderedFields = [
            'no_hp_pelanggan' => 1,
            'nama_pelanggan' => 2,
            'alamat_pelanggan' => 3,
            'tipe_pelanggan' => 4,
            'tujuan_ke_toko' => 5,
            'brand_dicari' => 6,
            'brand_dibeli' => 7,
            'alasan_pilih_brand' => 8,
            'tipe_pengecatan' => 9,
            'memerlukan_preview' => 10,
            'value_pembelian_rp' => 11,
            'painter_loyalty' => 12,
            'keterangan' => 13,
            'foto_1' => 14,
            'foto_2' => 15,
            'foto_3' => 16,
        ];

        foreach ($orderedFields as $fieldName => $orderIndex) {
            ReportFormField::where('report_template_id', $template->id)
                ->where('field_name', $fieldName)
                ->update(['order_index' => $orderIndex]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-DULUX-DATABASE-PELANGGAN')->first();
        if (!$template) return;

        // Revert nama_pelanggan to 1 and no_hp_pelanggan to 2
        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'nama_pelanggan')
            ->update(['order_index' => 1]);

        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'no_hp_pelanggan')
            ->update(['order_index' => 2]);
    }
};
