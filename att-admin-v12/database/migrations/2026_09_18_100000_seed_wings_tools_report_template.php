<?php

use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan HANYA entitas PT WINGS SURYA
        $wingsSurya = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS SURYA%')
              ->orWhere('code', 'PR-WINGS-SURYA')
              ->orWhere('code', 'LIKE', '%WINGS-SURYA%');
        })->first();

        if (!$wingsSurya) {
            $wingsSurya = Principal::where('name', 'LIKE', '%WINGS%')->first();
        }

        if (!$wingsSurya) {
            $wingsSurya = Principal::firstOrCreate(
                ['code' => 'PR-WINGS-SURYA'],
                [
                    'name' => 'PT WINGS SURYA',
                    'subdomain' => 'wings',
                    'theme_color' => '#E53935',
                    'theme_color_secondary' => '#C62828',
                    'portal_title' => 'Portal Pelaporan & Monitoring Wings Surya',
                    'is_active' => true,
                ]
            );
        }

        // 2. Buat / Update Template Form: Laporan Tools (Properti Free Taste)
        $templateData = [
            'code' => 'RPT-WINGS-MBR-TOOLS-01',
            'title' => 'Laporan Tools (Properti Free Taste)',
            'description' => 'Pencatatan pemeriksaan kelengkapan dan kondisi fisik tools / properti free taste Wings Surya (panci susu, mangkuk pengaduk, kompor, galon, dll) sebelum dan selama kegiatan sampling.',
            'category' => 'sampling',
            'report_group' => 'event_mbr',
            'require_gps' => true,
            'require_signature' => false,
            'is_active' => true,
            'version' => 1,
        ];

        if (Schema::hasColumn('report_templates', 'icon')) {
            $templateData['icon'] = 'wrench-screwdriver';
        }
        if (Schema::hasColumn('report_templates', 'color')) {
            $templateData['color'] = '#D32F2F';
        }

        $template = ReportTemplate::updateOrCreate(
            ['code' => $templateData['code']],
            array_merge($templateData, ['principal_id' => $wingsSurya->id])
        );

        // Sync HANYA ke PT WINGS SURYA
        $template->principals()->sync([$wingsSurya->id]);

        // 3. Daftarkan 4 Field Input Sesuai Spesifikasi Excel Laporan Tools.xlsx
        $toolsList = [
            '1 Pcs panci susu',
            '1 Pcs mangkuk pengaduk',
            '1 Pcs Gunting',
            '2 set sendok garpu',
            '1 pcs centong sayur',
            '1 pcs capitan',
            '1 Pcs Pompa dispenser air (optional)',
            '1 Pcs Galon air',
            '1 Pcs Kompor portable + Gas',
            '1 pcs saringan / tirisan mie',
            '1 Pcs tray',
            '1 Gelas Takar',
            'Papercup & Garpu kecil (untuk pengunjung)',
        ];

        $fields = [
            [
                'field_label' => 'Pilih Tools / Properti Free Taste',
                'field_name' => 'nama_tools',
                'field_type' => 'dropdown',
                'options' => $toolsList,
                'placeholder' => 'Pilih tools / properti...',
                'help_text' => 'Pilih jenis tools sesuai list referensi standar free taste',
                'is_required' => true,
                'order_index' => 1,
            ],
            [
                'field_label' => 'Status Ketersediaan Tools',
                'field_name' => 'status_ketersediaan',
                'field_type' => 'radio',
                'options' => ['ADA', 'TIDAK'],
                'help_text' => 'Pilih ADA jika fisik alat tersedia, atau TIDAK jika tidak ada / belum tersedia di lokasi',
                'is_required' => true,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Kondisi Tools',
                'field_name' => 'kondisi_tools',
                'field_type' => 'radio',
                'options' => ['BAGUS', 'TIDAK BAGUS'],
                'help_text' => 'Pilih kondisi fisik tools (BAGUS jika layak pakai, TIDAK BAGUS jika rusak/cacat)',
                'is_required' => false,
                'order_index' => 3,
            ],
            [
                'field_label' => 'Foto Bukti Fisik Tools',
                'field_name' => 'foto_tools',
                'field_type' => 'camera_photo',
                'placeholder' => 'Ambil foto bukti fisik tools yang tidak bagus',
                'help_text' => 'Wajib jika kondisi tools tidak bagus',
                'is_required' => false,
                'order_index' => 4,
            ],
        ];

        foreach ($fields as $fData) {
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $template->id,
                    'field_name' => $fData['field_name'],
                ],
                array_merge($fData, [
                    'report_template_id' => $template->id,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-TOOLS-01')->first();
        if ($template) {
            $template->fields()->delete();
            $template->principals()->detach();
            $template->delete();
        }
    }
};
