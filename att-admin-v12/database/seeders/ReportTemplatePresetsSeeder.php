<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReportTemplatePresetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Dulux (AkzoNobel)
        $dulux = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('code', 'LIKE', '%DULUX%')
            ->first();

        if (!$dulux) {
            $dulux = Principal::firstOrCreate(
                ['code' => 'PR-DULUX'],
                [
                    'name' => 'PT AKZONOBEL COATINGS INDONESIA (DULUX)',
                    'subdomain' => 'dulux',
                    'theme_color' => '#0F52BA',
                    'portal_title' => 'Portal Pelaporan & Monitoring Dulux',
                    'is_active' => true,
                ]
            );
        } else if (empty($dulux->subdomain)) {
            $dulux->update([
                'subdomain' => 'dulux',
                'theme_color' => '#0F52BA',
                'portal_title' => 'Portal Pelaporan & Monitoring Dulux',
            ]);
        }

        if ($dulux) {
            $this->seedDuluxTemplates($dulux);
        }

        // 2. Fonterra
        $fonterra = Principal::where('name', 'LIKE', '%FONTERRA%')
            ->orWhere('code', 'LIKE', '%FONTERRA%')
            ->first();

        if (!$fonterra) {
            $fonterra = Principal::firstOrCreate(
                ['code' => 'PR-FONTERRA'],
                [
                    'name' => 'PT FONTERRA BRANDS INDONESIA',
                    'subdomain' => 'fonterra',
                    'theme_color' => '#008080',
                    'portal_title' => 'Portal Monitoring & Field Report Fonterra',
                    'is_active' => true,
                ]
            );
        } else if (empty($fonterra->subdomain)) {
            $fonterra->update([
                'subdomain' => 'fonterra',
                'theme_color' => '#008080',
                'portal_title' => 'Portal Monitoring & Field Report Fonterra',
            ]);
        }

        if ($fonterra) {
            $this->seedFonterraTemplates($fonterra);
        }

        // 3. Mamasuka (Daesang)
        $mamasuka = Principal::where('name', 'LIKE', '%MAMASUKA%')
            ->orWhere('name', 'LIKE', '%DAESANG%')
            ->orWhere('code', 'LIKE', '%MAMASUKA%')
            ->first();

        if (!$mamasuka) {
            $mamasuka = Principal::firstOrCreate(
                ['code' => 'PR-MAMASUKA'],
                [
                    'name' => 'PT ANEKA BOGA NUSANTARA (MAMASUKA)',
                    'subdomain' => 'mamasuka',
                    'theme_color' => '#D97706',
                    'portal_title' => 'Portal Pelaporan & Display Tracker Mamasuka',
                    'is_active' => true,
                ]
            );
        } else if (empty($mamasuka->subdomain)) {
            $mamasuka->update([
                'subdomain' => 'mamasuka',
                'theme_color' => '#D97706',
                'portal_title' => 'Portal Pelaporan & Display Tracker Mamasuka',
            ]);
        }

        if ($mamasuka) {
            $this->seedMamasukaTemplates($mamasuka);
        }
    }

    private function seedDuluxTemplates(Principal $principal): void
    {
        // Template 1: Offtake & Market Share Dulux
        $template = ReportTemplate::firstOrCreate(
            ['principal_id' => $principal->id, 'code' => 'RPT-DULUX-OFFTAKE-01'],
            [
                'title' => 'Laporan Offtake & Market Share Dulux',
                'description' => 'Formulir pencatatan penjualan offtake harian, cek stok, dan perbandingan penjualan brand kompetitor.',
                'category' => 'offtake',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $fields = [
            ['field_label' => 'Kategori Produk Dulux', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Dulux Cat Tembok Interior', 'Dulux Weathershield Exterior', 'Dulux Cat Kayu & Besi', 'Dulux Cat Dasar / Primer', 'Cat Pelapis Bocor (Aquashield)'], 'is_required' => true],
            ['field_label' => 'SKU / Nama Produk Terjual', 'field_name' => 'sku_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Dulux Pentalite Briliant White 20L', 'is_required' => true],
            ['field_label' => 'Jumlah Terjual (Pail / Galon)', 'field_name' => 'jumlah_terjual', 'field_type' => 'number', 'placeholder' => 'Jumlah kaleng/pail', 'is_required' => true],
            ['field_label' => 'Total Nilai Penjualan (Rupiah)', 'field_name' => 'total_value_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
            ['field_label' => 'Sisa Stok di Toko', 'field_name' => 'sisa_stok', 'field_type' => 'number', 'placeholder' => 'Jumlah stok fisik saat ini', 'is_required' => true],
            ['field_label' => 'Status Barang Kosong (OOS)', 'field_name' => 'status_oos', 'field_type' => 'radio', 'options' => ['Ada Stok (Normal)', 'Kosong Toko', 'Kosong Distributor'], 'is_required' => true],
            ['field_label' => 'Brand Kompetitor Paling Laku Hari Ini', 'field_name' => 'brand_kompetitor_terlaris', 'field_type' => 'dropdown', 'options' => ['Jotun', 'Nippon Paint', 'Mowilex', 'Danapaints', 'Lenkote', 'Propan', 'TOA', 'Lain-lain'], 'is_required' => false],
            ['field_label' => 'Estimasi Penjualan Brand Kompetitor (Rp)', 'field_name' => 'estimasi_sales_kompetitor', 'field_type' => 'currency', 'is_required' => false],
            ['field_label' => 'Foto Display Toko / Tinting Machine', 'field_name' => 'foto_display', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Catatan Tambahan & Program Promo Toko', 'field_name' => 'catatan', 'field_type' => 'textarea', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedFonterraTemplates(Principal $principal): void
    {
        // Template 2: POSM & Pricing Fonterra
        $template = ReportTemplate::firstOrCreate(
            ['principal_id' => $principal->id, 'code' => 'RPT-FONTERRA-POSM-PRICE-01'],
            [
                'title' => 'Laporan POSM & Price Tag Tracking Fonterra',
                'description' => 'Formulir monitoring pemasangan POSM, sticker kemasan, dan validasi harga promo susu Fonterra (Anlene, Boneeto, Anchor).',
                'category' => 'posm',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $fields = [
            ['field_label' => 'Brand Fonterra', 'field_name' => 'brand', 'field_type' => 'dropdown', 'options' => ['Anlene Actifit 3X', 'Anlene Gold 5X', 'Boneeto Cokelat/Vanila', 'Anchor Butter / Cheese', 'Mainland'], 'is_required' => true],
            ['field_label' => 'Tipe POSM / Display Yang Dipasang', 'field_name' => 'tipe_posm', 'field_type' => 'dropdown', 'options' => ['Wobbler', 'Shelf Talker', 'Floor Display', 'Endcap Gondola', 'Chiller Sticker', 'Banner Gantung'], 'is_required' => true],
            ['field_label' => 'Kondisi POSM di Toko', 'field_name' => 'kondisi_posm', 'field_type' => 'radio', 'options' => ['Terpasang Rapi (Bagus)', 'Rusak / Sobek', 'Hilang / Dicopot Toko'], 'is_required' => true],
            ['field_label' => 'Harga Normal Toko (Rp)', 'field_name' => 'harga_normal', 'field_type' => 'currency', 'is_required' => true],
            ['field_label' => 'Harga Promo Toko (Rp)', 'field_name' => 'harga_promo', 'field_type' => 'currency', 'is_required' => false],
            ['field_label' => 'Foto POSM & Shelf Display (Multi-Foto)', 'field_name' => 'foto_posm_cluster', 'field_type' => 'multi_photo', 'is_required' => true],
            ['field_label' => 'Jumlah Kemasan Baru Terpasang Stiker', 'field_name' => 'total_stiker_pasang', 'field_type' => 'number', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedMamasukaTemplates(Principal $principal): void
    {
        // Template 3: Rent Display & Expired Date Mamasuka
        $template = ReportTemplate::firstOrCreate(
            ['principal_id' => $principal->id, 'code' => 'RPT-MAMASUKA-RENT-EXP-01'],
            [
                'title' => 'Laporan Rent Display & Expired Date Mamasuka',
                'description' => 'Pencatatan realisasi sewa display, monitoring kadaluarsa produk bumbu/rumput laut, dan promo kompetitor.',
                'category' => 'display',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $fields = [
            ['field_label' => 'Kategori Produk Mamasuka', 'field_name' => 'kategori_mamasuka', 'field_type' => 'dropdown', 'options' => ['Gim (Rumput Laut Panggang)', 'Tepung Bumbu / Roti', 'Mayonais / Salad Dressing', 'Delisaos Saus Tiram / Pasta', 'Bumbu Instan Kuah Bakso'], 'is_required' => true],
            ['field_label' => 'Tipe Sewa Display (Rent Display)', 'field_name' => 'tipe_sewa_display', 'field_type' => 'dropdown', 'options' => ['Endcap Depan', 'Wing Stage / Side Rack', 'Floor Display Island', 'Chiller Hanging'], 'is_required' => true],
            ['field_label' => 'Status Realisasi Display', 'field_name' => 'status_display', 'field_type' => 'radio', 'options' => ['Sudah Terpasang Sesuai Kontrak', 'Belum Terpasang (Menunggu PIC Toko)', 'Terhalang Barang Toko'], 'is_required' => true],
            ['field_label' => 'Tanggal Expired Terdekat di Rak', 'field_name' => 'tanggal_expired', 'field_type' => 'date', 'help_text' => 'Pilih tanggal kadaluarsa paling dekat yang ditemukan di rak', 'is_required' => true],
            ['field_label' => 'Jumlah Stok Mendekati Expired (< 3 Bulan)', 'field_name' => 'qty_near_expired', 'field_type' => 'number', 'is_required' => false],
            ['field_label' => 'Foto Display Mamasuka (Before & After)', 'field_name' => 'foto_display_before_after', 'field_type' => 'multi_photo', 'is_required' => true],
            ['field_label' => 'Promo Kompetitor Serupa (Sasa / Ajinomoto / MamaSuka)', 'field_name' => 'info_promo_kompetitor', 'field_type' => 'textarea', 'placeholder' => 'Contoh: Diskon 20% atau Beli 2 Gratis 1 di toko ini...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }
}
