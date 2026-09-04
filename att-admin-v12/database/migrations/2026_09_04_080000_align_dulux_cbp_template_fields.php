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
        $template = ReportTemplate::where('code', 'RPT-DULUX-CBP-PRICING')->first();
        if (!$template) {
            return;
        }

        $template->update([
            'title' => 'Laporan CBP (Consumer Buying Price) & Cek Harga Dulux',
            'description' => 'Monitoring harga beli konsumen (CBP) produk Dulux, Catylac, serta harga brand & subbrand kompetitor (Tin, Galon, Pail) sesuai format Raw Data & Dashboard MOP.',
            'category' => 'pricing',
            'is_active' => true,
        ]);

        $fields = [
            [
                'field_label' => 'Kategori Segmen Cat (Category)',
                'field_name' => 'kategori_produk',
                'field_type' => 'dropdown',
                'options' => [
                    'Super Premium Interior',
                    'Dulux Interior',
                    'Mass Interior',
                    'Super Premium Exterior',
                    'Premium Exterior',
                    'Enamel',
                    'Waterproofing',
                    'Sealer Premium Interior',
                    'Sealer Premium Exterior',
                    'Sealer Mass Interior',
                    'Sealer Mass Exterior',
                    'Economy Interior',
                    'Economy Exterior',
                    'Segmen Lainnya'
                ],
                'is_required' => true,
            ],
            [
                'field_label' => 'Brand Cat (AN Dulux vs Kompetitor)',
                'field_name' => 'brand_cat',
                'field_type' => 'dropdown',
                'options' => [
                    'AN (AkzoNobel / Dulux)',
                    'JOTUN',
                    'NIPPON PAINT',
                    'AVIAN / NO DROP / LENKOTE',
                    'MOWILEX',
                    'SIKA',
                    'AQUAPROOF',
                    'PROPAN',
                    'KANSAI / DANAPAINT',
                    'PACIFIC PAINT',
                    'MERK LAINNYA'
                ],
                'is_required' => true,
            ],
            [
                'field_label' => 'Nama Sub Brand / Produk yang Dicek',
                'field_name' => 'subbrand_produk',
                'field_type' => 'text',
                'placeholder' => 'Contoh: Ambiance, Pentalite, Weathershield, Catylac, V-Gloss, Aquashield, Majestic, Spotless, No Drop...',
                'is_required' => true,
            ],
            [
                'field_label' => 'Harga Normal Kemasan Tin 1L / 1Kg (Rp)',
                'field_name' => 'harga_tin_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false,
            ],
            [
                'field_label' => 'Harga Promo / Terendah Tin 1L / 1Kg (Lowest Tin Rp)',
                'field_name' => 'harga_terendah_tin_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0 (Jika ada promo)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Harga Normal Kemasan Galon 2.5L / 4-5Kg (Rp)',
                'field_name' => 'harga_galon_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false,
            ],
            [
                'field_label' => 'Harga Promo / Terendah Galon 2.5L / 4-5Kg (Lowest Galon Rp)',
                'field_name' => 'harga_terendah_galon_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0 (Jika ada promo)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Harga Normal Kemasan Pail 20L / 25Kg (Rp)',
                'field_name' => 'harga_pail_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false,
            ],
            [
                'field_label' => 'Harga Promo / Terendah Pail 20L / 25Kg (Lowest Pail Rp)',
                'field_name' => 'harga_terendah_pail_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0 (Jika ada promo)',
                'is_required' => false,
            ],
        ];

        // Delete previous fields
        ReportFormField::where('report_template_id', $template->id)->delete();

        foreach ($fields as $index => $f) {
            ReportFormField::create(array_merge($f, [
                'report_template_id' => $template->id,
                'order_index' => $index + 1,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
