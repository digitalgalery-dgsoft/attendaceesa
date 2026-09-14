<?php

use App\Models\Principal;
use App\Models\Product;
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
        // 1. Temukan seluruh entitas PT WINGS SURYA & PT LION WINGS
        $wingsPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS%')
              ->orWhere('name', 'LIKE', '%LION%')
              ->orWhere('code', 'LIKE', '%WINGS%')
              ->orWhere('subdomain', 'LIKE', '%wings%');
        })->get();

        if ($wingsPrincipals->isEmpty()) {
            $primaryWings = Principal::firstOrCreate(
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
            $wingsPrincipals = collect([$primaryWings]);
        }

        $primaryWings = $wingsPrincipals->first();
        $allWingsIds = $wingsPrincipals->pluck('id')->toArray();

        // 2. Ambil seluruh SKU Master Produk Mie Sedaap yang terkait dengan Wings
        $mieSedaapSkus = Product::whereIn('principal_id', $allWingsIds)
            ->where(function ($q) {
                $q->where('brand', 'LIKE', '%SEDAAP%')
                  ->orWhere('name', 'LIKE', '%MIE SEDAP%')
                  ->orWhere('name', 'LIKE', '%MIE SEDAAP%');
            })
            ->pluck('id')
            ->toArray();

        // 3. Buat / Update Template Form Baru: Laporan Free Taste (Event MBR)
        $templateData = [
            'code' => 'RPT-WINGS-MBR-FREETASTE-01',
            'title' => 'Laporan Free Taste (Event MBR)',
            'description' => 'Pencatatan aktivitas sampling dan free taste produk Wings Surya (Mie Sedaap) pada event MBR, meliputi stok awal sampling, jumlah mie yang dimasak, stok akhir sampling, jumlah cup yang dibagikan secara mandiri, serta dokumentasi foto.',
            'category' => 'sampling',
            'report_group' => 'event_mbr',
            'require_gps' => true,
            'require_signature' => false,
            'is_active' => true,
            'version' => 1,
        ];

        if (Schema::hasColumn('report_templates', 'icon')) {
            $templateData['icon'] = 'beaker';
        }
        if (Schema::hasColumn('report_templates', 'color')) {
            $templateData['color'] = '#F59E0B';
        }

        $template = ReportTemplate::updateOrCreate(
            ['code' => $templateData['code']],
            array_merge($templateData, ['principal_id' => $primaryWings->id])
        );

        // Sync ke seluruh entitas Wings
        $template->principals()->sync($allWingsIds);

        // Sync ke master produk Mie Sedaap
        if (Schema::hasTable('report_template_product') && !empty($mieSedaapSkus)) {
            $template->products()->sync($mieSedaapSkus);
        }

        // 4. Daftarkan Field-Field Form Laporan Free Taste Event MBR
        $fields = [
            [
                'field_label' => 'Data Rincian Produk Sampling (JSON)',
                'field_name' => 'mbr_freetaste_items_json',
                'field_type' => 'text',
                'placeholder' => '[]',
                'help_text' => 'Data multi-item sampling produk event MBR (JSON)',
                'is_required' => true,
                'order_index' => 1,
            ],
            [
                'field_label' => 'Total Stok Awal Sampling (Pcs)',
                'field_name' => 'total_stok_awal_sampling',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total kuantiti stok awal mie untuk sampling (Pcs)',
                'is_required' => true,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Total Mie yang Dimasak (Pcs)',
                'field_name' => 'total_mie_dimasak',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total kuantiti mie yang dimasak untuk sampling (Pcs)',
                'is_required' => true,
                'order_index' => 3,
            ],
            [
                'field_label' => 'Total Stok Akhir Sampling (Pcs)',
                'field_name' => 'total_stok_akhir_sampling',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total sisa stok mie sampling (Stok Awal - Dimasak) (Pcs)',
                'is_required' => true,
                'order_index' => 4,
            ],
            [
                'field_label' => 'Total Jumlah Free Taste / Sampling (Cup)',
                'field_name' => 'total_cup_dibagikan',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total cup hasil sampling yang dibagikan secara gratis kepada pengunjung',
                'is_required' => true,
                'order_index' => 5,
            ],
            [
                'field_label' => 'Foto Stand / Booth Sampling',
                'field_name' => 'foto_booth_sampling',
                'field_type' => 'photo',
                'placeholder' => 'Ambil foto dokumentasi stand / booth sampling',
                'help_text' => 'Dokumentasi stand/booth sampling dan aktivitas memasak di toko saat event',
                'is_required' => true,
                'order_index' => 6,
            ],
            [
                'field_label' => 'Catatan Aktivitas Sampling',
                'field_name' => 'catatan_sampling',
                'field_type' => 'textarea',
                'placeholder' => 'Catatan antusiasme pengunjung atau kendala sampling di lapangan...',
                'help_text' => 'Catatan tambahan terkait kegiatan free taste',
                'is_required' => false,
                'order_index' => 7,
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
        $template = ReportTemplate::where('code', 'RPT-WINGS-MBR-FREETASTE-01')->first();
        if ($template) {
            $template->fields()->delete();
            $template->delete();
        }
    }
};
