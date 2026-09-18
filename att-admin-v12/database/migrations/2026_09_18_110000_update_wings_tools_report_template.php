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
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-TOOLS-01')->first();
        if (!$template) {
            return;
        }

        // 1. Hapus field textarea keterangan_kondisi (digantikan oleh radio kondisi_tools)
        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'keterangan_kondisi')
            ->delete();

        // 2. Tambah / Update field radio kondisi_tools
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $template->id,
                'field_name' => 'kondisi_tools',
            ],
            [
                'report_template_id' => $template->id,
                'field_label' => 'Kondisi Tools',
                'field_name' => 'kondisi_tools',
                'field_type' => 'radio',
                'options' => ['BAGUS', 'TIDAK BAGUS'],
                'placeholder' => null,
                'help_text' => 'Pilih kondisi fisik tools (BAGUS jika layak pakai, TIDAK BAGUS jika rusak/cacat)',
                'is_required' => false,
                'order_index' => 3,
            ]
        );

        // 3. Update field foto_tools
        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $template->id,
                'field_name' => 'foto_tools',
            ],
            [
                'report_template_id' => $template->id,
                'field_label' => 'Foto Bukti Fisik Tools',
                'field_name' => 'foto_tools',
                'field_type' => 'camera_photo',
                'placeholder' => 'Ambil foto bukti fisik tools yang tidak bagus',
                'help_text' => 'Wajib jika kondisi tools tidak bagus',
                'is_required' => false,
                'order_index' => 4,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-TOOLS-01')->first();
        if (!$template) {
            return;
        }

        ReportFormField::where('report_template_id', $template->id)
            ->where('field_name', 'kondisi_tools')
            ->delete();

        ReportFormField::updateOrCreate(
            [
                'report_template_id' => $template->id,
                'field_name' => 'keterangan_kondisi',
            ],
            [
                'report_template_id' => $template->id,
                'field_label' => 'Keterangan Kondisi Tools',
                'field_name' => 'keterangan_kondisi',
                'field_type' => 'textarea',
                'placeholder' => 'Keterangan kondisi tools...',
                'help_text' => 'Catatan kondisi fisik alat saat dilakukan pengecekan',
                'is_required' => false,
                'order_index' => 3,
            ]
        );
    }
};
