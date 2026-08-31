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
        $offtakeTemplate = ReportTemplate::where('code', 'RPT-DULUX-OFFTAKE-01')->first();
        if ($offtakeTemplate) {
            // Hapus field lama yang duplikat dengan form baru
            $offtakeTemplate->fields()->whereIn('field_name', [
                'sku_warna_produk',
                'kategori_produk',
                'kemasan_produk',
            ])->delete();

            // Re-index remaining fields
            $fields = $offtakeTemplate->fields()->orderBy('order_index', 'asc')->get();
            foreach ($fields as $idx => $field) {
                $field->update(['order_index' => $idx + 1]);
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
