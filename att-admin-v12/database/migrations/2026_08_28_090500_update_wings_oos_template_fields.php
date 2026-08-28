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
        $templates = ReportTemplate::whereIn('code', [
            'RPT-WINGS-OOS-FOOD-01',
            'RPT-WINGS-OOS-CARE-01',
        ])->get();

        foreach ($templates as $template) {
            // 1. Hapus field tanggal PO, nama PIC, dan foto rak display
            ReportFormField::where('report_template_id', $template->id)
                ->whereIn('field_name', [
                    'estimasi_po_date',
                    'nama_pic_toko',
                    'foto_rak_stok',
                    'foto_rak_care',
                ])
                ->delete();

            // 2. Update field status_ketersediaan_stok agar tidak ada 'In Stock (Stok Aman di Rak & Gudang)'
            $statusField = ReportFormField::where('report_template_id', $template->id)
                ->where('field_name', 'status_ketersediaan_stok')
                ->first();

            if ($statusField) {
                $statusField->update([
                    'options' => [
                        'OOS (Out of Stock / Kosong Total)',
                        'Under Minimum Stock (Stok Menipis Kritis)',
                    ],
                ]);
            }

            // 3. Re-order order_index field yang tersisa
            $remainingFields = ReportFormField::where('report_template_id', $template->id)
                ->orderBy('order_index', 'asc')
                ->get();

            foreach ($remainingFields as $idx => $field) {
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
