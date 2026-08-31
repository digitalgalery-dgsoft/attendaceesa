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
        $fieldsToRemove = [
            'account_lso',
            'account_lso_oos',
            'tipe_toko',
            'kategori_toko_post',
        ];

        ReportFormField::whereIn('field_name', $fieldsToRemove)->delete();

        // Re-index remaining fields for Dulux templates
        $targetTemplates = [
            'RPT-DULUX-TINTER-LSO',
            'RPT-DULUX-OOS-LSO',
            'RPT-DULUX-TRAFIK-PEMBELI',
            'RPT-DULUX-DAILY-MAINTENANCE',
        ];

        foreach ($targetTemplates as $code) {
            $template = ReportTemplate::where('code', $code)->first();
            if ($template) {
                $fields = $template->fields()->orderBy('order_index', 'asc')->get();
                foreach ($fields as $idx => $field) {
                    $field->update(['order_index' => $idx + 1]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as store data is dynamically resolved from WorkLocation
    }
};
