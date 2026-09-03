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

        $extraFields = [
            [
                'field_label' => 'Brand Cat (Dulux / Catylac)',
                'field_name' => 'brand_cat',
                'field_type' => 'dropdown',
                'options' => ['Dulux', 'Catylac', 'Catylac Smart Choice'],
                'is_required' => false,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Ukuran Kemasan Galon (L)',
                'field_name' => 'kemasan_galon',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 2.5',
                'is_required' => false,
                'order_index' => 4,
            ],
            [
                'field_label' => 'Ukuran Kemasan Pail (L)',
                'field_name' => 'kemasan_pail',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 20',
                'is_required' => false,
                'order_index' => 6,
            ],
            [
                'field_label' => 'Faktor Konversi / Density',
                'field_name' => 'konversi_faktor',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 1.27',
                'is_required' => false,
                'order_index' => 8,
            ],
            [
                'field_label' => 'ID Member Toko / DERP',
                'field_name' => 'derp_member_id',
                'field_type' => 'text',
                'placeholder' => 'ID DERP',
                'is_required' => false,
                'order_index' => 15,
            ],
        ];

        foreach ($extraFields as $f) {
            ReportFormField::firstOrCreate(
                [
                    'report_template_id' => $stockEnd->id,
                    'field_name' => $f['field_name'],
                ],
                array_merge($f, ['report_template_id' => $stockEnd->id])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
