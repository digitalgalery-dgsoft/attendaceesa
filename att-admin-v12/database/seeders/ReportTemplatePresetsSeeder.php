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
        // 1. Temukan seluruh entitas ICI PAINTS / DULUX di database
        $duluxPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%ICI%')
              ->orWhere('name', 'LIKE', '%PAINT%')
              ->orWhere('name', 'LIKE', '%DULUX%')
              ->orWhere('name', 'LIKE', '%AKZONOBEL%')
              ->orWhere('code', 'LIKE', '%ICI%')
              ->orWhere('code', 'LIKE', '%DULUX%');
        })->get();

        if ($duluxPrincipals->isEmpty()) {
            $primaryDulux = Principal::firstOrCreate(
                ['code' => 'PR-ICI-PAINTS'],
                [
                    'name' => 'PT ICI PAINTS INDONESIA',
                    'subdomain' => 'dulux',
                    'theme_color' => '#0F52BA',
                    'theme_color_secondary' => '#0284C7',
                    'portal_title' => 'Portal Pelaporan & Monitoring Dulux (ICI Paints)',
                    'is_active' => true,
                ]
            );
            $duluxPrincipals = collect([$primaryDulux]);
        } else {
            foreach ($duluxPrincipals as $dp) {
                $dp->update([
                    'subdomain' => 'dulux',
                    'theme_color' => '#0F52BA',
                    'theme_color_secondary' => '#0284C7',
                    'portal_title' => 'Portal Pelaporan & Monitoring Dulux (ICI Paints)',
                    'is_active' => true,
                ]);
            }
        }

        $primaryDulux = $duluxPrincipals->first();
        $allDuluxIds = $duluxPrincipals->pluck('id')->toArray();

        // Hapus / bersihkan template generic lama Dulux jika ada
        $oldCodes = [
            'RPT-DULUX-STOCK-OOS-01',
            'RPT-DULUX-MARKET-SHARE-01',
            'RPT-DULUX-TINTING-DISPLAY-01',
            'RPT-DULUX-DATABASE-TL-01',
        ];
        foreach ($oldCodes as $oldCode) {
            $oldTemplate = ReportTemplate::where('code', $oldCode)->first();
            if ($oldTemplate) {
                $oldTemplate->principals()->detach();
                $oldTemplate->delete();
            }
        }

        // Seed 9 Template Resmi Dulux (ICI Paints / AkzoNobel) - Tinter telah disatukan ke Stock End
        $this->removeDuluxTinterLso();
        $this->seedDuluxCbpPricing($primaryDulux, $allDuluxIds);
        $this->seedDuluxOfftakeTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxStockEndTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxOosSsoTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxOosLsoTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxDataPelangganTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxTrafikPembeliTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxRegistrasiMitraTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxDailyMaintenanceTemplate($primaryDulux, $allDuluxIds);

        // 2. Fonterra
        $fonterraPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%FONTERRA%')
              ->orWhere('code', 'LIKE', '%FONTERRA%')
              ->orWhere('subdomain', 'LIKE', '%FONTERRA%');
        })->get();

        if ($fonterraPrincipals->isEmpty()) {
            $primaryFonterra = Principal::firstOrCreate(
                ['code' => 'PR-FONTERRA'],
                [
                    'name' => 'PT FONTERRA BRANDS INDONESIA',
                    'subdomain' => 'fonterra',
                    'theme_color' => '#003399',
                    'theme_color_secondary' => '#0077CC',
                    'portal_title' => 'Portal Pelaporan & Monitoring Fonterra Brands',
                    'is_active' => true,
                ]
            );
            $fonterraPrincipals = collect([$primaryFonterra]);
        } else {
            foreach ($fonterraPrincipals as $fp) {
                $fp->update([
                    'subdomain' => 'fonterra',
                    'theme_color' => '#003399',
                    'theme_color_secondary' => '#0077CC',
                    'portal_title' => 'Portal Pelaporan & Monitoring Fonterra Brands',
                    'is_active' => true,
                ]);
            }
        }

        $primaryFonterra = $fonterraPrincipals->first();
        $allFonterraIds = $fonterraPrincipals->pluck('id')->toArray();

        $this->seedFonterraTemplates($primaryFonterra, $allFonterraIds);

        // 3. DAESANG / MAMASUKA / MIWON / JICO AGUNG
        $mamasukaPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%MAMASUKA%')
              ->orWhere('name', 'LIKE', '%DAESANG%')
              ->orWhere('name', 'LIKE', '%JICO%')
              ->orWhere('name', 'LIKE', '%MIWON%')
              ->orWhere('code', 'LIKE', '%MAMASUKA%')
              ->orWhere('code', 'LIKE', '%DAESANG%')
              ->orWhere('subdomain', 'LIKE', '%mamasuka%');
        })->get();

        if ($mamasukaPrincipals->isEmpty()) {
            $primaryMamasuka = Principal::firstOrCreate(
                ['code' => 'PR-DAESANG-MAMASUKA'],
                [
                    'name' => 'PT DAESANG AGUNG INDONESIA (MAMASUKA)',
                    'subdomain' => 'mamasuka',
                    'theme_color' => '#D32F2F',
                    'theme_color_secondary' => '#F57C00',
                    'portal_title' => 'Portal Pelaporan & Monitoring Daesang (MamaSuka & Miwon)',
                    'is_active' => true,
                ]
            );
            $mamasukaPrincipals = collect([$primaryMamasuka]);
        } else {
            foreach ($mamasukaPrincipals as $mp) {
                $mp->update([
                    'subdomain' => 'mamasuka',
                    'theme_color' => '#D32F2F',
                    'theme_color_secondary' => '#F57C00',
                    'portal_title' => 'Portal Pelaporan & Monitoring Daesang (MamaSuka & Miwon)',
                    'is_active' => true,
                ]);
            }
        }

        $primaryMamasuka = $mamasukaPrincipals->first();
        $allMamasukaIds = $mamasukaPrincipals->pluck('id')->toArray();

        $this->seedMamasukaTemplates($primaryMamasuka, $allMamasukaIds);

        // 4. WINGS GROUP: KHUSUS HANYA PT WINGS SURYA & PT LION WINGS
        // Reset subdomain 'wings' dari entitas non-Wings Surya / Lion Wings (misal CV Sinar Surya, Sayap Mas Utama)
        Principal::where('subdomain', 'wings')
            ->where(function ($q) {
                $q->where('name', 'NOT LIKE', '%WINGS SURYA%')
                  ->where('name', 'NOT LIKE', '%LION WINGS%')
                  ->where('code', 'NOT LIKE', '%WINGS-SURYA%')
                  ->where('code', 'NOT LIKE', '%LION-WINGS%');
            })
            ->update(['subdomain' => null]);

        // Temukan SEMUA entitas / divisi PT WINGS SURYA di database (misal code 141, 206, 502)
        $allWingsSurya = Principal::where('name', 'LIKE', '%WINGS SURYA%')
            ->orWhere('code', 'PR-WINGS-SURYA')
            ->get();

        if ($allWingsSurya->isEmpty()) {
            $wingsSurya = Principal::create([
                'code' => 'PR-WINGS-SURYA',
                'name' => 'PT WINGS SURYA',
                'subdomain' => 'wings',
                'theme_color' => '#D32F2F',
                'theme_color_secondary' => '#FF5252',
                'portal_title' => 'Portal Pelaporan & Monitoring PT Wings Surya',
                'is_active' => true,
            ]);
            $allWingsSurya = collect([$wingsSurya]);
        } else {
            foreach ($allWingsSurya as $ws) {
                $ws->update([
                    'subdomain' => 'wings',
                    'theme_color' => '#D32F2F',
                    'theme_color_secondary' => '#FF5252',
                    'portal_title' => 'Portal Pelaporan & Monitoring PT Wings Surya',
                    'is_active' => true,
                ]);
            }
        }

        // Temukan SEMUA entitas / divisi PT LION WINGS di database (misal code 120, 203, 501)
        $allLionWings = Principal::where('name', 'LIKE', '%LION WINGS%')
            ->orWhere('code', 'PR-LION-WINGS')
            ->get();

        if ($allLionWings->isEmpty()) {
            $lionWings = Principal::create([
                'code' => 'PR-LION-WINGS',
                'name' => 'PT LION WINGS',
                'subdomain' => 'wings',
                'theme_color' => '#008848',
                'theme_color_secondary' => '#00B050',
                'portal_title' => 'Portal Pelaporan & Monitoring PT Lion Wings',
                'is_active' => true,
            ]);
            $allLionWings = collect([$lionWings]);
        } else {
            foreach ($allLionWings as $lw) {
                $lw->update([
                    'subdomain' => 'wings',
                    'theme_color' => '#008848',
                    'theme_color_secondary' => '#00B050',
                    'portal_title' => 'Portal Pelaporan & Monitoring PT Lion Wings',
                    'is_active' => true,
                ]);
            }
        }

        $allWingsSuryaIds = $allWingsSurya->pluck('id')->toArray();
        $allLionWingsIds = $allLionWings->pluck('id')->toArray();
        $allWingsIds = array_values(array_unique(array_merge($allWingsSuryaIds, $allLionWingsIds)));
        $primaryWings = $allWingsSurya->first();

        $this->seedWingsTemplates($primaryWings, $allWingsIds);
    }

    /**
     * 1. Hapus Laporan Tinter Terpisah (Telah disatukan ke Stock End)
     */
    private function removeDuluxTinterLso(): void
    {
        $templates = ReportTemplate::where('code', 'RPT-DULUX-TINTER-LSO')->get();
        foreach ($templates as $t) {
            ReportFormField::where('report_template_id', $t->id)->delete();
            $t->principals()->detach();
            $t->assignments()->delete();
            $t->delete();
        }
    }

    /**
     * 2. CBP / New Pricing Dulux
     */
    private function seedDuluxCbpPricing(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-CBP-PRICING'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan CBP (Consumer Buying Price) & Cek Harga Dulux',
                'description' => 'Monitoring harga beli konsumen (CBP) produk Dulux, Catylac, serta harga brand & subbrand kompetitor (Tin, Galon, Pail) dan promo toko.',
                'category' => 'pricing',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

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
                'is_required' => true
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
                'is_required' => true
            ],
            [
                'field_label' => 'Nama Sub Brand / Produk yang Dicek',
                'field_name' => 'subbrand_produk',
                'field_type' => 'text',
                'placeholder' => 'Contoh: Ambiance, Pentalite, Weathershield, Catylac, V-Gloss, Aquashield, Majestic, Spotless, No Drop...',
                'is_required' => true
            ],
            [
                'field_label' => 'Harga Normal Kemasan Tin 1L / 1Kg (Rp)',
                'field_name' => 'harga_tin_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false
            ],
            [
                'field_label' => 'Harga Promo / Terendah Tin 1L / 1Kg (Lowest Tin Rp)',
                'field_name' => 'harga_terendah_tin_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0 (Jika ada promo)',
                'is_required' => false
            ],
            [
                'field_label' => 'Harga Normal Kemasan Galon 2.5L / 4-5Kg (Rp)',
                'field_name' => 'harga_galon_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false
            ],
            [
                'field_label' => 'Harga Promo / Terendah Galon 2.5L / 4-5Kg (Lowest Galon Rp)',
                'field_name' => 'harga_terendah_galon_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0 (Jika ada promo)',
                'is_required' => false
            ],
            [
                'field_label' => 'Harga Normal Kemasan Pail 20L / 25Kg (Rp)',
                'field_name' => 'harga_pail_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'is_required' => false
            ],
            [
                'field_label' => 'Harga Promo / Terendah Pail 20L / 25Kg (Lowest Pail Rp)',
                'field_name' => 'harga_terendah_pail_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0 (Jika ada promo)',
                'is_required' => false
            ],
        ];

        ReportFormField::where('report_template_id', $template->id)->delete();
        foreach ($fields as $index => $field) {
            ReportFormField::create(
                array_merge($field, ['report_template_id' => $template->id, 'order_index' => $index + 1])
            );
        }
    }

    /**
     * 3. Template Offtake / Penjualan Harian & Bukti Nota Dulux
     */
    private function seedDuluxOfftakeTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-OFFTAKE-01'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Offtake / Penjualan Harian & Bukti Nota Dulux',
                'description' => 'Pencatatan penjualan harian produk Dulux & Catylac, bukti nota penjualan, dan traffic customer di toko.',
                'category' => 'offtake',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Brand', 'field_name' => 'brand', 'field_type' => 'dropdown', 'options' => ['Dulux', 'Catylac', 'Maxilite'], 'is_required' => true],
            ['field_label' => 'Brand (RM / Base)', 'field_name' => 'brand_rm_base', 'field_type' => 'dropdown', 'options' => ['Dulux RM', 'Dulux Base', 'Catylac RM', 'Catylac Base'], 'is_required' => true],
            ['field_label' => 'Sub Brand', 'field_name' => 'sub_brand', 'field_type' => 'text', 'placeholder' => 'Contoh: Catylac Interior, Weathershield, Aquashield, Pentalite, Catylac Exterior, Easy Clean, V-Gloss...', 'is_required' => true],
            ['field_label' => 'Sub Brand Spesifik / Varian (Sub Brand 1)', 'field_name' => 'sub_brand1', 'field_type' => 'text', 'placeholder' => 'Varian khusus jika ada', 'is_required' => false],
            ['field_label' => 'Detail RM / Base (Sub Brand 2)', 'field_name' => 'sub_brand2', 'field_type' => 'text', 'placeholder' => 'Detail RM atau Base', 'is_required' => false],
            ['field_label' => 'Kemasan Galon', 'field_name' => 'kemasan_galon', 'field_type' => 'dropdown', 'options' => ['0.8 Liter', '0.9 Liter', '1 Liter', '2.4 Liter', '2.5 Liter', '3.5 Liter', '4 Liter', '4.5 Liter', '5 Liter', 'Tidak Ada Galon'], 'is_required' => true],
            ['field_label' => 'Kuantiti Galon Terjual (Unit)', 'field_name' => 'qty_galon', 'field_type' => 'number', 'placeholder' => '0', 'is_required' => false],
            ['field_label' => 'Volume Galon (Liter)', 'field_name' => 'volume_galon_l', 'field_type' => 'number', 'placeholder' => '0.00', 'is_required' => false, 'is_readonly' => true],
            ['field_label' => 'Kemasan Pail', 'field_name' => 'kemasan_pail', 'field_type' => 'dropdown', 'options' => ['18.5 Liter', '20 Liter', '21 Liter', '22 Liter', '25 Liter', 'Tidak Ada Pail'], 'is_required' => true],
            ['field_label' => 'Kuantiti Pail Terjual (Unit)', 'field_name' => 'qty_pail', 'field_type' => 'number', 'placeholder' => '0', 'is_required' => false],
            ['field_label' => 'Volume Pail (Liter)', 'field_name' => 'volume_pail_l', 'field_type' => 'number', 'placeholder' => '0.00', 'is_required' => false, 'is_readonly' => true],
            ['field_label' => 'Total Volume Kuantiti Unit (Galon + Pail)', 'field_name' => 'total_volume_unit', 'field_type' => 'number', 'placeholder' => 'Total unit', 'is_required' => false, 'is_readonly' => true],
            ['field_label' => 'Total Volume Penjualan (Liter)', 'field_name' => 'total_volume_liter', 'field_type' => 'number', 'placeholder' => '0.00', 'is_required' => true, 'is_readonly' => true],
            ['field_label' => 'Total Nilai Penjualan (Rupiah)', 'field_name' => 'total_nilai_sales_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Status Transaksi', 'field_name' => 'status_transaksi', 'field_type' => 'dropdown', 'options' => ['Agency', 'Direct', 'Retailer / Toko', 'Lainnya'], 'is_required' => false],
            ['field_label' => 'Foto Bukti Offtake Card / Nota Penjualan', 'field_name' => 'foto_nota_penjualan', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Catatan Penjualan & Program Promo Toko', 'field_name' => 'catatan_penjualan', 'field_type' => 'textarea', 'placeholder' => 'Catatan tambahan transaksi hari ini...', 'is_required' => false],
        ];

        // Delete old fields
        ReportFormField::where('report_template_id', $template->id)->delete();

        foreach ($fields as $index => $field) {
            ReportFormField::create(array_merge($field, [
                'report_template_id' => $template->id,
                'order_index' => $index + 1
            ]));
        }
    }

    /**
     * 4. Stock End Report & Tinter Dulux (Disatukan)
     */
    private function seedDuluxStockEndTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-STOCK-END'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Stock End (Stock Opname Bulanan) & Tinter Dulux',
                'description' => 'Pencatatan sisa stok fisik akhir bulan seluruh SKU Dulux & Catylac serta ketersediaan pasta tinter mesin tinting (Dramatone & Acotone) di toko.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $dramatoneOptions = [
            'White (W1)',
            'Black (B1)',
            'Yellow Oxide (Y1)',
            'Red Oxide (R1)',
            'Organic Yellow (Y2)',
            'Organic Red (R2)',
            'Blue (BL)',
            'Green (GR)',
            'Magenta (MG)',
            'Orange (OR)',
            'Violet (VT)',
            'Semua Warna Dramatone / Full Set',
        ];

        $acotoneOptions = [
            'Acotone White (AW)',
            'Acotone Black (AB)',
            'Acotone Yellow Oxide (AYO)',
            'Acotone Red Oxide (ARO)',
            'Acotone Bright Yellow (AY2)',
            'Acotone Bright Red (AR2)',
            'Acotone Blue (ABL)',
            'Acotone Green (AGR)',
            'Acotone Magenta (AMG)',
            'Acotone Orange (AOR)',
            'Acotone Violet (AVT)',
            'Acotone Transparent Red (ATR)',
            'Acotone Transparent Yellow (ATY)',
            'Semua Warna Acotone / Full Set',
        ];

        $allTinterOptions = array_merge($dramatoneOptions, $acotoneOptions);

        $fields = [
            ['field_label' => 'Pilih Produk Dulux / Catylac yang Dicek', 'field_name' => 'produk_stock_end', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Base / Tipe Warna', 'field_name' => 'base_warna', 'field_type' => 'dropdown', 'options' => ['Base A (Putih/Light)', 'Base B (Medium)', 'Base C (Dark)', 'Base D (Clear/Deep)', 'Ready Mix (Warna Jadi Pabrik)', 'Cat Dasar Primer'], 'is_required' => true],
            ['field_label' => 'Stok Fisik Kemasan Galon (Qty)', 'field_name' => 'stok_qty_galon', 'field_type' => 'number', 'placeholder' => 'Jumlah galon', 'is_required' => true],
            ['field_label' => 'Stok Fisik Kemasan Pail (Qty)', 'field_name' => 'stok_qty_pail', 'field_type' => 'number', 'placeholder' => 'Jumlah pail', 'is_required' => true],
            ['field_label' => 'Estimasi Total Volume Stok di Toko (Liter)', 'field_name' => 'total_volume_stok_liter', 'field_type' => 'number', 'placeholder' => 'Total volume liter', 'is_required' => true, 'is_readonly' => true],
            ['field_label' => 'Kategori Tinter / Mesin Tinting', 'field_name' => 'kategori_tinter', 'field_type' => 'dropdown', 'options' => ['Dramatone', 'Acotone', 'Tidak Ada Mesin / Non-Tinting'], 'is_required' => true],
            ['field_label' => 'Tipe Tinter / Warna Pasta Pewarna', 'field_name' => 'tipe_tinter_warna', 'field_type' => 'dropdown', 'options' => $allTinterOptions, 'is_required' => true],
            ['field_label' => 'Kuantiti / Jumlah Kaleng Tinta Tinter', 'field_name' => 'qty_kaleng_tinta', 'field_type' => 'number', 'placeholder' => 'Jumlah kaleng tinter', 'is_required' => false],
            ['field_label' => 'Status Ketersediaan Tinter di Toko', 'field_name' => 'status_ketersediaan_tinter', 'field_type' => 'radio', 'options' => ['Stok Aman (Siap Oplos)', 'Stok Menipis (Perlu Order Ulang)', 'Stok Habis (Mesin Tidak Bisa Oplos)', 'Tidak Ada Mesin'], 'is_required' => true],
            ['field_label' => 'Status Akses Pengecekan Gudang Toko', 'field_name' => 'status_akses_gudang', 'field_type' => 'radio', 'options' => ['Full Access (Bisa Cek Rak & Gudang Toko Bebas)', 'Half Access (Hanya Cek Rak Depan Toko)', 'No Access (Toko Menolak Cek Fisik / Data Estimasi)'], 'is_required' => true],
            ['field_label' => 'Foto Fisik Rak Display, Tumpukan Stok Gudang & Mesin Tinter', 'field_name' => 'foto_stok_gudang', 'field_type' => 'multi_photo', 'is_required' => true],
            ['field_label' => 'Keterangan / Kendala Stok & Tinter Toko', 'field_name' => 'keterangan_stok_toko', 'field_type' => 'textarea', 'placeholder' => 'Catatan status stok lambat laku (slow moving), kelebihan stok, atau request restock tinter...', 'is_required' => false],
        ];

        ReportFormField::where('report_template_id', $template->id)->delete();

        foreach ($fields as $index => $field) {
            ReportFormField::create(array_merge($field, [
                'report_template_id' => $template->id,
                'order_index' => $index + 1,
            ]));
        }
    }

    /**
     * 5. Out of Stock Dulux (SSO & LSO Digabung)
     */
    private function seedDuluxOosSsoTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-OOS-SSO'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Out of Stock (OOS) Dulux',
                'description' => 'Pencatatan barang kosong (Out of Stock) di toko Specialist Traditional (SSO) maupun Modern Trade (LSO).',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $readyMixColors = [
            'Bukan Ready Mix (Base Oplos)',
            'Brilliant White (1001)',
            'White (1000)',
            'Black / Hitam (1002)',
            'Barley White (1003)',
            'Off White',
            'Grey / Abu-abu',
            'Cream / Vanilla',
            'Tropical Green / Hijau',
            'Sky Blue / Biru',
            'Sunshine / Kuning',
            'Signal Red / Merah',
            'Mocha / Coklat',
            'Warna Ready Mix Lainnya',
        ];

        $oosReasons = [
            '1. Sudah buka PO namun belum ada pengiriman ke toko',
            '2. Sudah buka PO namun kendala stock di distributor/pabrik',
            '3. Kendala pembayaran toko (limit kredit / kiriman diblokir)',
            '4. Barang sedang dalam proses pengiriman ke toko',
            '5. Stok DC / Hub Modern Trade Kosong',
            '6. PO Toko Belum Diterbitkan Buyer / PIC',
            '7. Toko belum bersedia reorder / menunggu omset',
        ];

        $fields = [
            ['field_label' => 'Tipe Gerai / Channel Toko', 'field_name' => 'channel_toko', 'field_type' => 'dropdown', 'options' => ['Specialist Traditional Store (SSO)', 'Modern Outlet / Toko Modern (LSO)'], 'is_required' => true],
            ['field_label' => 'Pilih Produk Dulux yang Mengalami Out of Stock (OOS)', 'field_name' => 'produk_oos', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Kemasan / Size yang Kosong', 'field_name' => 'kemasan_size_oos', 'field_type' => 'dropdown', 'options' => ['Small Tin (1L / 1Kg)', 'Galon (2.5L / 4-5Kg)', 'Pail Besar (20L / 25Kg)'], 'is_required' => true],
            ['field_label' => 'Base / Kategori Warna yang Kosong', 'field_name' => 'base_warna_oos', 'field_type' => 'dropdown', 'options' => ['Base A', 'Base B', 'Base C', 'Base D', 'Ready Mix / Warna Jadi', 'Alkali Primer / Cat Dasar'], 'is_required' => true],
            ['field_label' => 'Pilihan Warna Ready Mix (Jika Warna Jadi Kosong)', 'field_name' => 'warna_ready_mix_oos', 'field_type' => 'dropdown', 'options' => $readyMixColors, 'is_required' => true],
            ['field_label' => 'Lama Kondisi Barang Kosong (Jumlah Hari)', 'field_name' => 'lama_oos_hari', 'field_type' => 'number', 'placeholder' => 'Contoh: 7 (hari)', 'is_required' => true],
            ['field_label' => 'Saran Kuantiti Order ke Toko (Qty Kemasan)', 'field_name' => 'saran_qty_order', 'field_type' => 'number', 'placeholder' => 'Saran kuantiti order', 'is_required' => false],
            ['field_label' => 'Penyebab / Alasan Out of Stock (OOS)', 'field_name' => 'alasan_oos', 'field_type' => 'dropdown', 'options' => $oosReasons, 'is_required' => true],
        ];

        ReportFormField::where('report_template_id', $template->id)->delete();
        foreach ($fields as $index => $field) {
            ReportFormField::create(
                array_merge($field, ['report_template_id' => $template->id, 'order_index' => $index + 1])
            );
        }
    }

    /**
     * 6. Out of Stock LSO Report Dulux (Dinonaktifkan / Digabung ke SSO)
     */
    private function seedDuluxOosLsoTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::where('code', 'RPT-DULUX-OOS-LSO')->first();
        if ($template) {
            ReportFormField::where('report_template_id', $template->id)->delete();
            $template->update(['is_active' => false]);
        }
    }

    /**
     * 7. Data Pelanggan Dulux (Brand Dicari Dropdown, Foto & Catatan Dihapus)
     */
    private function seedDuluxDataPelangganTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-DATABASE-PELANGGAN'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Data Pelanggan & Konsumen Dulux',
                'description' => 'Pendataan profil konsumen pembeli cat di toko, brand yang dicari vs brand yang dibeli, dan kebutuhan preview warna.',
                'category' => 'general',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $brandOptions = [
            'DULUX / CATYLAC',
            'JOTUN',
            'NIPPON PAINT',
            'AVIAN / NO DROP / LENKOTE',
            'MOWILEX',
            'PROPAN',
            'DANAPAINT / KANSAI',
            'PACIFIC PAINT',
            'MERK LAINNYA',
        ];

        $fields = [
            ['field_label' => 'Nama Lengkap Pelanggan', 'field_name' => 'nama_pelanggan', 'field_type' => 'text', 'placeholder' => 'Nama konsumen / pembeli', 'is_required' => true],
            ['field_label' => 'Nomor HP / WhatsApp Pelanggan', 'field_name' => 'no_hp_pelanggan', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx', 'is_required' => true],
            ['field_label' => 'Alamat / Domisili Pelanggan', 'field_name' => 'alamat_pelanggan', 'field_type' => 'text', 'placeholder' => 'Alamat atau lokasi proyek konsumen', 'is_required' => false],
            ['field_label' => 'Tipe / Kategori Pelanggan', 'field_name' => 'tipe_pelanggan', 'field_type' => 'radio', 'options' => ['Pemilik Rumah (End User)', 'Tukang Cat & Bangunan', 'Mandor Proyek', 'Kontraktor / Aplikator', 'Mitra Dulux Terdaftar'], 'is_required' => true],
            ['field_label' => 'Tujuan Datang ke Toko', 'field_name' => 'tujuan_ke_toko', 'field_type' => 'dropdown', 'options' => ['Membeli Cat Dulux / Catylac', 'Membeli Cat Merk Lain', 'Membeli Bahan Bangunan Lainnya', 'Konsultasi / Tanya Warna', 'Komplain Produk'], 'is_required' => true],
            ['field_label' => 'Brand Cat yang Awalnya Dicari / Ditanyakan', 'field_name' => 'brand_dicari', 'field_type' => 'dropdown', 'options' => $brandOptions, 'is_required' => true],
            ['field_label' => 'Brand Cat yang Akhirnya Dibeli', 'field_name' => 'brand_dibeli', 'field_type' => 'dropdown', 'options' => ['DULUX (Pentalite/Weathershield/EasyClean/Ambiance)', 'CATYLAC (Interior/Exterior/Plamur)', 'AQUASHIELD (Pelapis Anti Bocor)', 'JOTUN', 'NIPPON PAINT', 'AVIAN / NO DROP / LENKOTE', 'MOWILEX', 'PROPAN', 'Tidak Jadi Beli Cat'], 'is_required' => true],
            ['field_label' => 'Alasan Konsumen Memilih Brand Tersebut', 'field_name' => 'alasan_pilih_brand', 'field_type' => 'dropdown', 'options' => ['Kualitas dan Daya Tahan Terbukti', 'Merk Terkenal / Rekomendasi Arsitek', 'Rekomendasi SPG / DC Dulux di Toko', 'Rekomendasi Pemilik / Karyawan Toko', 'Harga Lebih Terjangkau / Diskon Promo', 'Warna Sesuai Pilihan (Bisa Oplos)', 'Tukang Cat Terbiasa Pakai Merk Tersebut'], 'is_required' => true],
            ['field_label' => 'Tipe Pekerjaan Pengecatan', 'field_name' => 'tipe_pengecatan', 'field_type' => 'radio', 'options' => ['Pengecatan Rumah Baru (Tembok Baru)', 'Pengecatan Ulang / Renovasi (Repainting)', 'Pengecatan Proyek Komersial / Ruko'], 'is_required' => true],
            ['field_label' => 'Apakah Memerlukan Preview Warna Visualizer?', 'field_name' => 'memerlukan_preview', 'field_type' => 'radio', 'options' => ['Ya (Dibuatkan Visualisasi Warna / Demo)', 'Tidak (Sudah Memiliki Pilihan Warna Pasti)'], 'is_required' => true],
            ['field_label' => 'Total Estimasi Nilai Pembelian (Rupiah)', 'field_name' => 'value_pembelian_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
        ];

        ReportFormField::where('report_template_id', $template->id)->delete();
        foreach ($fields as $index => $field) {
            ReportFormField::create(
                array_merge($field, ['report_template_id' => $template->id, 'order_index' => $index + 1])
            );
        }
    }

    /**
     * 8. Trafik Pembeli Toko Dulux
     */
    private function seedDuluxTrafikPembeliTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-TRAFIK-PEMBELI'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Trafik Pembeli Toko Dulux',
                'description' => 'Pencatatan ringkas harian total pengunjung toko cat, pembeli cat umum, dan pembeli produk Dulux.',
                'category' => 'general',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Jumlah Total Customer Datang ke Toko Hari Ini', 'field_name' => 'jml_customer_datang', 'field_type' => 'number', 'placeholder' => 'Jumlah pengunjung toko', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Membeli Cat (Semua Brand)', 'field_name' => 'jml_customer_beli_cat', 'field_type' => 'number', 'placeholder' => 'Jumlah pembeli cat', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Membeli Produk Dulux / Catylac', 'field_name' => 'jml_customer_beli_dulux', 'field_type' => 'number', 'placeholder' => 'Jumlah pembeli Dulux', 'is_required' => true],
            ['field_label' => 'Estimasi Persentase Market Share Dulux di Toko Hari Ini (%)', 'field_name' => 'estimasi_market_share_persen', 'field_type' => 'number', 'placeholder' => 'Contoh: 60 (%)', 'is_required' => false, 'is_readonly' => true],
            ['field_label' => 'Kondisi Keramaian Toko Hari Ini', 'field_name' => 'kondisi_keramaian_toko', 'field_type' => 'radio', 'options' => ['Sangat Ramai (Banyak Pengunjung & Transaksi)', 'Normal / Sedang', 'Sepi (Faktor Cuaca Hujan / Hari Kerja)'], 'is_required' => true],
            ['field_label' => 'Catatan Evaluasi Trafik Toko', 'field_name' => 'catatan_trafik_toko', 'field_type' => 'textarea', 'placeholder' => 'Catatan situasi toko hari ini...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    /**
     * 9. Registrasi New MD (Mitra Dulux)
     */
    private function seedDuluxRegistrasiMitraTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-REGISTRASI-MITRA'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Registrasi New MD (Mitra Dulux Non-Incentive)',
                'description' => 'Formulir resmi pendaftaran anggota baru program Mitra Dulux untuk tukang cat, mandor, kontraktor, dan aplikator.',
                'category' => 'survey',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Nama Lengkap Mitra Dulux (Sesuai KTP)', 'field_name' => 'nama_lengkap_mitra', 'field_type' => 'text', 'placeholder' => 'Nama lengkap mitra', 'is_required' => true],
            ['field_label' => 'Nomor KTP / SIM Resmi', 'field_name' => 'no_ktp_sim', 'field_type' => 'text', 'placeholder' => '16 digit NIK KTP / No SIM', 'is_required' => true],
            ['field_label' => 'Foto Identitas Resmi (KTP / SIM Mitra)', 'field_name' => 'foto_identitas_resmi', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Nomor Handphone untuk Telepon', 'field_name' => 'no_hp_telepon', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx', 'is_required' => true],
            ['field_label' => 'Nomor Handphone untuk WhatsApp', 'field_name' => 'no_hp_whatsapp', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx', 'is_required' => true],
            ['field_label' => 'Alamat Lengkap Sesuai KTP', 'field_name' => 'alamat_ktp', 'field_type' => 'textarea', 'placeholder' => 'Alamat lengkap mitra...', 'is_required' => true],
            ['field_label' => 'Profesi / Keahlian Mitra', 'field_name' => 'profesi_mitra', 'field_type' => 'dropdown', 'options' => ['Mandor Bangunan', 'Tukang Cat Profesional', 'Kontraktor / Pemborong Bangunan', 'Aplikator Cat Khusus', 'Arsitek / Desainer Interior', 'Karyawan / Bagian Pembelian Toko'], 'is_required' => true],
            ['field_label' => 'Jumlah Tukang Cat di Bawah Koordinasi Mitra', 'field_name' => 'jumlah_tukang_cat', 'field_type' => 'number', 'placeholder' => 'Jumlah anak buah / tukang', 'is_required' => false],
            ['field_label' => 'Nama Proyek Pengecatan yang Sedang Dikerjakan', 'field_name' => 'nama_proyek_pengecatan', 'field_type' => 'text', 'placeholder' => 'Contoh: Rumah Tinggal Bpk. Hendra / Ruko 3 Lantai', 'is_required' => true],
            ['field_label' => 'Estimasi Luas Bidang Pengecatan (m2)', 'field_name' => 'luas_bidang_pengecatan', 'field_type' => 'number', 'placeholder' => 'Luas m2', 'is_required' => false],
            ['field_label' => 'Foto Proyek Pengecatan (Eksterior / Interior)', 'field_name' => 'foto_proyek_pengecatan', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Foto Mitra Dulux Bersama Petugas DC / DGO di Toko', 'field_name' => 'foto_painter_bersama_dc', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Foto Bukti Nota Pembelian Produk Dulux Pertama', 'field_name' => 'foto_bukti_nota_pertama', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Tanda Tangan Digital Mitra Dulux', 'field_name' => 'tanda_tangan_mitra_dulux', 'field_type' => 'signature', 'is_required' => true],
            ['field_label' => 'Catatan Rekomendasi Petugas DC / DGO', 'field_name' => 'catatan_rekomendasi_mitra', 'field_type' => 'textarea', 'placeholder' => 'Catatan potensi mitra Dulux dan loyalitas produk...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    /**
     * 10. Daily Maintenance POST Dulux
     */
    private function seedDuluxDailyMaintenanceTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-DAILY-MAINTENANCE'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Daily Maintenance POST & Mesin Tinting Dulux',
                'description' => 'Kartu harian pemeriksaan & perawatan mesin tinting (POST Maintenance), nozzle cleaning, kalibrasi, dan program Mix2Win di toko.',
                'category' => 'display',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Tipe Mesin Tinting POST di Toko', 'field_name' => 'tipe_mesin_post', 'field_type' => 'dropdown', 'options' => ['Mesin D200 (Automatic Tinting)', 'Mesin Discovery (Automatic Tinting)', 'Mesin XProtint (Automatic Tinting)', 'Mesin Element 2', 'Mesin Manual Dispenser', 'Toko Tidak Memiliki Mesin Tinting'], 'is_required' => true],
            ['field_label' => 'Nomor Seri / No Mesin POST Dulux', 'field_name' => 'no_mesin_post', 'field_type' => 'text', 'placeholder' => 'Contoh: POST-2022-JKT-042', 'is_required' => false],
            ['field_label' => 'Status Pemeriksaan Kebersihan Nozzle & Brush Cleaning', 'field_name' => 'status_nozzle_cleaning', 'field_type' => 'radio', 'options' => ['Nozzle Bersih & Sudah Dilakukan Brush Cleaning', 'Nozzle Tersumbat (Butuh Pembersihan Ekstra / Air Hangat)', 'Nozzle Rusak / Butuh Service Teknisi'], 'is_required' => true],
            ['field_label' => 'Status Sirkulasi & Agitasi Pasta Tinter', 'field_name' => 'status_sirkulasi_tinter', 'field_type' => 'radio', 'options' => ['Sirkulasi & Pengadukan Tinter Berjalan Normal', 'Motor Agitasi Berisik / Butuh Pelumasan', 'Tinta Mengendap / Tidak Berputar'], 'is_required' => true],
            ['field_label' => 'Status Software Tinting Komputer & Database Formula Warna', 'field_name' => 'status_software_komputer', 'field_type' => 'radio', 'options' => ['Software Normal & Database Formula Warna Terupdate', 'Software Error / Butuh Update Formula', 'Komputer / Monitor Mati'], 'is_required' => true],
            ['field_label' => 'Status Partisipasi Program Mix2Win Toko', 'field_name' => 'status_program_mix2win', 'field_type' => 'radio', 'options' => ['Program Mix2Win Aktif & Kupon Tercetak Normal', 'Program Mix2Win Belum Diaktifkan Toko', 'Kendala Printer / Sambungan Internet Mix2Win'], 'is_required' => true],
            ['field_label' => 'Foto Proses Brush Cleaning / Pembersihan Nozzle Mesin', 'field_name' => 'foto_brush_cleaning', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Foto Mesin Tinting & Area Oplos Toko', 'field_name' => 'foto_mesin_tinting', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Kesimpulan Kondisi Mesin & Rekomendasi Maintenance', 'field_name' => 'kesimpulan_maintenance', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan kendala teknis atau pasta yang perlu di-restock teknisi...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedFonterraTemplates(Principal $primaryFonterra, array $allFonterraIds): void
    {
        $templatesData = [
            // 1. Offtake SPG
            [
                'code' => 'RPT-FONTERRA-OFFTAKE-SPG-01',
                'title' => 'Laporan Offtake / Penjualan SPG Fonterra',
                'description' => 'Pencatatan penjualan harian produk Fonterra (Anlene, Boneeto, Anchor) oleh SPG di toko/outlet modern trade.',
                'category' => 'offtake',
                'icon' => 'shopping-cart',
                'color' => '#1E88E5',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Metode Transaksi Pembeli', 'field_name' => 'metode_transaksi', 'field_type' => 'dropdown', 'options' => ['Reguler / Tunai', 'Debit / QRIS', 'Kartu Kredit', 'Voucher Belanja Toko'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk Fonterra', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Adult Nutrition (Anlene)', 'Kids Milk (Boneeto)', 'Dairy Food / Butter & Cheese (Anchor)'], 'is_required' => true],
                    ['field_label' => 'Brand / Sub Brand', 'field_name' => 'sub_brand', 'field_type' => 'dropdown', 'options' => ['Anlene Actifit 3X', 'Anlene Gold 5X', 'Anlene Total 10', 'Boneeto Cokelat / Vanila', 'Anchor Butter (Salted/Unsalted)', 'Anchor Cheddar Cheese', 'Anchor Cooking & Whipping Cream', 'Mainland Cheese'], 'is_required' => true],
                    ['field_label' => 'Nama SKU / Varian / Ukuran', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Anlene Gold 5X Vanilla 650g', 'is_required' => true],
                    ['field_label' => 'Tipe Penjualan', 'field_name' => 'tipe_penjualan', 'field_type' => 'radio', 'options' => ['Harga Normal', 'Harga Promo / Cut Price', 'Bundling / GWP (Hadiah Langsung)'], 'is_required' => true],
                    ['field_label' => 'Jumlah Qty Terjual (Pcs/Box)', 'field_name' => 'quantity_terjual', 'field_type' => 'number', 'placeholder' => 'Jumlah pcs/kemasan terjual', 'is_required' => true],
                    ['field_label' => 'Harga Satuan (Rp)', 'field_name' => 'harga_satuan_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Total Nilai Penjualan (Rp)', 'field_name' => 'total_nilai_sales_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Keterangan Promo / Hadiah GWP', 'field_name' => 'keterangan_promo_gwp', 'field_type' => 'text', 'placeholder' => 'Contoh: Beli 2 box gratis pouch cantik / tumbler', 'is_required' => false],
                    ['field_label' => 'Foto Struk / Bukti Transaksi Konsumen', 'field_name' => 'foto_struk_transaksi', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Penjualan / Feedback Konsumen', 'field_name' => 'catatan_penjualan', 'field_type' => 'textarea', 'placeholder' => 'Catatan promo toko, respon pembeli, atau kendala...', 'is_required' => false],
                ]
            ],

            // 2. Offtake SPT
            [
                'code' => 'RPT-FONTERRA-OFFTAKE-SPT-01',
                'title' => 'Laporan Offtake & Kunjungan SPT Fonterra',
                'description' => 'Pencatatan aktivitas penjualan, kunjungan institusi/toko, dan penyerahan gift/sampling oleh Sales Promotion Team (SPT).',
                'category' => 'offtake',
                'icon' => 'briefcase',
                'color' => '#0277BD',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Nama Instansi / Toko / Customer yang Dikunjungi', 'field_name' => 'nama_instansi_customer', 'field_type' => 'text', 'placeholder' => 'Nama Toko / Perusahaan / Instansi', 'is_required' => true],
                    ['field_label' => 'Nama Kontak Person (PIC)', 'field_name' => 'nama_pic_customer', 'field_type' => 'text', 'placeholder' => 'Nama PIC yang ditemui', 'is_required' => true],
                    ['field_label' => 'Nomor Telepon / WhatsApp PIC', 'field_name' => 'nomor_telepon_pic', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx', 'is_required' => true],
                    ['field_label' => 'Kategori Brand Fonterra', 'field_name' => 'kategori_brand', 'field_type' => 'dropdown', 'options' => ['Anlene Adult Nutrition', 'Boneeto Kids Growth Milk', 'Anchor Dairy Food', 'Anchor Bakery / Food Service'], 'is_required' => true],
                    ['field_label' => 'Nama Produk / SKU yang Dipesan', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Nama produk Fonterra yang dipesan', 'is_required' => true],
                    ['field_label' => 'Jumlah Qty Order (Pcs / Karton)', 'field_name' => 'quantity_order', 'field_type' => 'number', 'placeholder' => 'Jumlah pesanan', 'is_required' => true],
                    ['field_label' => 'Total Nilai Order (Rp)', 'field_name' => 'total_nilai_order_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Gift / Merchandise / Sampling yang Diserahkan', 'field_name' => 'gift_merchandise_diberikan', 'field_type' => 'text', 'placeholder' => 'Merchandise atau sampel gratis yang diserahkan', 'is_required' => false],
                    ['field_label' => 'Status Progress Kunjungan', 'field_name' => 'progress_update', 'field_type' => 'dropdown', 'options' => ['Closing Order (Deal)', 'Follow-up Negosiasi', 'Presentasi / Demo Produk', 'Sampling Trial', 'Penagihan / Administrasi'], 'is_required' => true],
                    ['field_label' => 'Catatan Koordinasi & Tindak Lanjut', 'field_name' => 'catatan_koordinasi', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan hasil diskusi dengan customer dan rencana kunjungan berikutnya...', 'is_required' => true],
                    ['field_label' => 'Foto Pertemuan / Bukti Order & Kunjungan', 'field_name' => 'foto_kegiatan_spt', 'field_type' => 'camera_photo', 'is_required' => true],
                ]
            ],

            // 3. Stock & OOS
            [
                'code' => 'RPT-FONTERRA-STOCK-OOS-01',
                'title' => 'Laporan Cek Stok & OOS Fonterra',
                'description' => 'Monitoring ketersediaan stok fisik produk Fonterra di rak display & gudang serta tracking estimasi PO saat barang kosong.',
                'category' => 'stock',
                'icon' => 'archive-box',
                'color' => '#E65100',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Kategori Brand Fonterra', 'field_name' => 'kategori_brand', 'field_type' => 'dropdown', 'options' => ['Anlene Adult Nutrition', 'Boneeto Kids Growth Milk', 'Anchor Dairy (Butter & Cheese)'], 'is_required' => true],
                    ['field_label' => 'Nama SKU / Varian Produk', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Nama SKU produk yang dicek', 'is_required' => true],
                    ['field_label' => 'Status Ketersediaan Stok', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['Stok Tersedia (Ready Sesuai Planogram)', 'Stok Kritis / Menipis (< Minimum Stock)', 'Out of Stock / Kosong Total (OOS)'], 'is_required' => true],
                    ['field_label' => 'Minimum Stock Display Toko (Pcs)', 'field_name' => 'stok_minimum_toko', 'field_type' => 'number', 'placeholder' => 'Jumlah minimum stock di toko', 'is_required' => true],
                    ['field_label' => 'Stok Fisik Aktual di Toko (Pcs)', 'field_name' => 'stok_fisik_aktual', 'field_type' => 'number', 'placeholder' => 'Jumlah fisik aktual (rak + gudang)', 'is_required' => true],
                    ['field_label' => 'Alasan Barang Kosong (Jika OOS)', 'field_name' => 'alasan_oos', 'field_type' => 'dropdown', 'options' => ['Tidak OOS (Stok Ready)', 'Barang Kosong di Distributor / DC', 'Keterlambatan Kirim PO Toko', 'Toko Belum Rilis PO Baru', 'Over Limit Kredit Toko', 'SKU Discontinue / Delisted'], 'is_required' => true],
                    ['field_label' => 'Estimasi Tanggal Rilis PO Toko', 'field_name' => 'estimasi_tanggal_po_toko', 'field_type' => 'date', 'is_required' => false],
                    ['field_label' => 'Nama PIC Order / MD Toko', 'field_name' => 'nama_pic_order_toko', 'field_type' => 'text', 'placeholder' => 'Nama MD / Purchasing Toko', 'is_required' => false],
                    ['field_label' => 'Jabatan PIC Order Toko', 'field_name' => 'jabatan_pic_order', 'field_type' => 'dropdown', 'options' => ['Kepala Toko / Store Manager', 'Purchasing / Buyer', 'Supervisor Area', 'Staff Gudang / MD'], 'is_required' => false],
                    ['field_label' => 'Foto Display Rak & Gudang Stok', 'field_name' => 'foto_rak_gudang_stok', 'field_type' => 'multi_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Stok & Komitmen Order Toko', 'field_name' => 'catatan_stok', 'field_type' => 'textarea', 'placeholder' => 'Catatan tindak lanjut order stok...', 'is_required' => false],
                ]
            ],

            // 4. Expired Date Tracking
            [
                'code' => 'RPT-FONTERRA-EXP-DATE-01',
                'title' => 'Laporan Expired Date & FEFO Fonterra',
                'description' => 'Pemantauan tanggal kadaluarsa produk Fonterra di rak & gudang guna memastikan rotasi First-Expired First-Out (FEFO).',
                'category' => 'stock',
                'icon' => 'calendar-days',
                'color' => '#C2185B',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk Fonterra', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['Anlene Actifit / Gold / Total 10', 'Boneeto Cokelat / Vanila', 'Anchor Butter', 'Anchor Cheese'], 'is_required' => true],
                    ['field_label' => 'Nama SKU & Ukuran Kemasan', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Nama SKU dan gramasi produk', 'is_required' => true],
                    ['field_label' => 'Bulan & Tahun Expired Date Terdekat pada Kemasan', 'field_name' => 'tanggal_expired', 'field_type' => 'month_year', 'is_required' => true],
                    ['field_label' => 'Jumlah Stok pada Tanggal Expired Ini (Pcs)', 'field_name' => 'jumlah_stok_exp', 'field_type' => 'number', 'placeholder' => 'Jumlah pcs stok dengan tanggal expired ini', 'is_required' => true],
                    ['field_label' => 'Sisa Umur Simpan (Bulan Menuju Expired)', 'field_name' => 'selisih_bulan_exp', 'field_type' => 'number', 'placeholder' => 'Berapa bulan menuju expired', 'is_required' => true],
                    ['field_label' => 'Status Kategori Expired', 'field_name' => 'status_kategori_exp', 'field_type' => 'radio', 'options' => ['Aman (> 6 Bulan)', 'Warning (3 - 6 Bulan)', 'Kritis / Near Expired (< 3 Bulan)'], 'is_required' => true],
                    ['field_label' => 'Rencana Tindakan FEFO', 'field_name' => 'tindakan_fefo', 'field_type' => 'dropdown', 'options' => ['Pajang Paling Depan (Rotasi FEFO)', 'Ajukan Retur ke Distributor', 'Usulkan Promo Cut Price / Clearance Toko', 'Koordinasi Mutasi ke Toko Beromset Tinggi'], 'is_required' => true],
                    ['field_label' => 'Foto Kemasan Produk & Cetakan Batch Expired', 'field_name' => 'foto_batch_expired', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Kondisi Kemasan & Batch Code', 'field_name' => 'catatan_expired', 'field_type' => 'textarea', 'placeholder' => 'Catatan nomor batch, kondisi kemasan, atau respon toko...', 'is_required' => false],
                ]
            ],

            // 5. Share of Shelf (SOS)
            [
                'code' => 'RPT-FONTERRA-SOS-01',
                'title' => 'Laporan Share of Shelf (SOS) Fonterra',
                'description' => 'Audit proporsi facing dan pembagian rak display produk Fonterra dibanding kompetitor pada kategori susu dan dairy.',
                'category' => 'display',
                'icon' => 'view-columns',
                'color' => '#00897B',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Kategori Rak Display yang Dicek', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Susu Dewasa (Adult Nutrition)', 'Susu Anak (Kids Growth Milk)', 'Butter & Margarine', 'Cheese & Cooking Dairy'], 'is_required' => true],
                    ['field_label' => 'Asal Brand / Produsen', 'field_name' => 'sub_brand_origin', 'field_type' => 'radio', 'options' => ['Brand Fonterra (Anlene / Boneeto / Anchor)', 'Kompetitor (Entrasol / Prenagen / Dancow / Prochiz / Lainnya)'], 'is_required' => true],
                    ['field_label' => 'Nama Brand / Sub-Brand yang Dihitung', 'field_name' => 'nama_brand_tercatat', 'field_type' => 'text', 'placeholder' => 'Contoh: Anlene Gold 5X atau Entrasol Platinum', 'is_required' => true],
                    ['field_label' => 'Total Facing Seluruh Brand di Kategori Ini', 'field_name' => 'total_facing_kategori', 'field_type' => 'number', 'placeholder' => 'Total facing satu lorong/kategori', 'is_required' => true],
                    ['field_label' => 'Jumlah Facing Brand Ini', 'field_name' => 'jumlah_facing_brand', 'field_type' => 'number', 'placeholder' => 'Jumlah facing untuk brand yang dihitung', 'is_required' => true],
                    ['field_label' => 'Persentase Share of Shelf (% SOS)', 'field_name' => 'persentase_sos', 'field_type' => 'number', 'placeholder' => 'Persentase % SOS (Contoh: 45)', 'is_required' => true],
                    ['field_label' => 'Analisa Kondisi SOS di Rak', 'field_name' => 'analisa_kondisi_sos', 'field_type' => 'dropdown', 'options' => ['Standar Planogram Fonterra Terpenuhi', 'Facing Berkurang karena Produk OOS', 'Kompetitor Sewa Tambahan Facing', 'Display Diubah Pihak Toko'], 'is_required' => true],
                    ['field_label' => 'Foto 1: Full Rak Gondola / Shelving (Tampak Luas)', 'field_name' => 'foto_full_gondola_1', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: Detail Facing Rak Fonterra', 'field_name' => 'foto_detail_facing_2', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Planogram & Facing', 'field_name' => 'catatan_sos', 'field_type' => 'textarea', 'placeholder' => 'Catatan display dan pergerakan kompetitor...', 'is_required' => false],
                ]
            ],

            // 6. Promo Fonterra (Promo Own)
            [
                'code' => 'RPT-FONTERRA-PROMO-OWN-01',
                'title' => 'Laporan Implementasi Promo Fonterra',
                'description' => 'Monitoring implementasi program promosi resmi Fonterra di toko (Diskon, GWP Hadiah, Bundling, validasi Price Tag promo).',
                'category' => 'promo',
                'icon' => 'tag',
                'color' => '#7B1FA2',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk Fonterra', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['Anlene', 'Boneeto', 'Anchor'], 'is_required' => true],
                    ['field_label' => 'Nama SKU Produk Promo', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Nama SKU produk promo', 'is_required' => true],
                    ['field_label' => 'Tipe Program Promo', 'field_name' => 'tipe_promo_group', 'field_type' => 'dropdown', 'options' => ['Price Discount / Cut Price', 'Gift With Purchase (GWP)', 'Bandling Package / Twin Pack', 'Voucher Cashback / Poin Member'], 'is_required' => true],
                    ['field_label' => 'Mekanisme & Syarat Promo', 'field_name' => 'mekanisme_promo', 'field_type' => 'text', 'placeholder' => 'Contoh: Beli Anlene 650g Diskon Rp 10.000 / Gratis Piring Cantik', 'is_required' => true],
                    ['field_label' => 'Tanggal Mulai Periode Promo', 'field_name' => 'periode_mulai_promo', 'field_type' => 'date', 'is_required' => true],
                    ['field_label' => 'Tanggal Berakhir Periode Promo', 'field_name' => 'periode_berakhir_promo', 'field_type' => 'date', 'is_required' => true],
                    ['field_label' => 'Status Implementasi di Toko', 'field_name' => 'status_implementasi_toko', 'field_type' => 'radio', 'options' => ['Sudah Terpasang & Aktif Sesuai Jadwal', 'Belum Terpasang (Kendala Materi / PIC)', 'Promo Tidak Diizinkan Toko'], 'is_required' => true],
                    ['field_label' => 'Alasan Jika Belum Terpasang', 'field_name' => 'alasan_belum_implementasi', 'field_type' => 'text', 'placeholder' => 'Tuliskan alasan kendala...', 'is_required' => false],
                    ['field_label' => 'Harga Normal Toko (Rp)', 'field_name' => 'harga_normal_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Harga Promo Toko (Rp)', 'field_name' => 'harga_promo_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Status Price Tag Promo di Rak', 'field_name' => 'status_price_tag_promo', 'field_type' => 'radio', 'options' => ['Price Tag Kuning / Promo Sudah Terpasang', 'Masih Price Tag Normal Putih', 'Price Tag Tidak Ada'], 'is_required' => true],
                    ['field_label' => 'Ketersediaan Materi POSM Promo', 'field_name' => 'ketersediaan_posm_promo', 'field_type' => 'radio', 'options' => ['POSM Promo Terpasang Lengkap (Wobbler/Shelftalker)', 'POSM Belum Dipasang', 'Tidak Ada Materi POSM'], 'is_required' => true],
                    ['field_label' => 'Foto Implementasi Promo & Price Tag di Toko', 'field_name' => 'foto_display_promo', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Efektivitas Promo di Toko', 'field_name' => 'catatan_promo', 'field_type' => 'textarea', 'placeholder' => 'Catatan respon konsumen dan laju penjualan promo...', 'is_required' => false],
                ]
            ],

            // 7. Promo Competitor
            [
                'code' => 'RPT-FONTERRA-PROMO-COMP-01',
                'title' => 'Laporan Promo & Aktivitas Kompetitor',
                'description' => 'Intelijen pasar terkait program promosi, diskon, hadiah, dan materi display brand kompetitor.',
                'category' => 'competitor',
                'icon' => 'chart-bar',
                'color' => '#D32F2F',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Kompetitor', 'field_name' => 'brand_kompetitor', 'field_type' => 'dropdown', 'options' => ['Entrasol (Kalbe)', 'Prenagen (Kalbe)', 'Diabetasol (Kalbe)', 'Dancow (Nestle)', 'Milo (Nestle)', 'SGM (Danone)', 'Elle & Vire (Butter/Cream)', 'Prochiz (Cheese)', 'Kraft (Mondelez)', 'Lainnya'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Susu Dewasa / Kalsium', 'Susu Anak / Balita', 'Butter & Mentega', 'Keju & Cooking Dairy'], 'is_required' => true],
                    ['field_label' => 'Nama SKU Produk Kompetitor', 'field_name' => 'sku_produk_kompetitor', 'field_type' => 'text', 'placeholder' => 'Nama SKU produk kompetitor', 'is_required' => true],
                    ['field_label' => 'Tipe Promo Kompetitor', 'field_name' => 'tipe_promo_kompetitor', 'field_type' => 'dropdown', 'options' => ['Cut Price / Diskon Kasir', 'Special Price / Diskon Toko', 'Beli 1 Gratis 1 / Bundling', 'Hadiah Langsung (GWP)', 'Katalog Koran / Mailer Toko'], 'is_required' => true],
                    ['field_label' => 'Mekanisme & Syarat Promo Kompetitor', 'field_name' => 'mekanisme_promo_kompetitor', 'field_type' => 'text', 'placeholder' => 'Syarat dan mekanisme promo kompetitor', 'is_required' => true],
                    ['field_label' => 'Periode Promo Kompetitor', 'field_name' => 'periode_promo_kompetitor', 'field_type' => 'text', 'placeholder' => 'Contoh: 1 - 15 Agustus 2026', 'is_required' => true],
                    ['field_label' => 'Harga Normal Kompetitor (Rp)', 'field_name' => 'harga_normal_kompetitor_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Harga Promo Kompetitor (Rp)', 'field_name' => 'harga_promo_kompetitor_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Keberadaan Additional Display Kompetitor', 'field_name' => 'ada_additional_display_kompetitor', 'field_type' => 'radio', 'options' => ['Ada Additional Display (Endcap/Island/Chiller)', 'Tidak Ada (Hanya di Rak Reguler)'], 'is_required' => true],
                    ['field_label' => 'Material POSM / Aktivitas yang Digunakan', 'field_name' => 'jenis_posm_kompetitor', 'field_type' => 'text', 'placeholder' => 'Materi POSM kompetitor (Wobbler, Banner, SPG Standby, dll)', 'is_required' => false],
                    ['field_label' => 'Foto Promo & Display Kompetitor di Toko', 'field_name' => 'foto_promo_kompetitor', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Analisa Dampak terhadap Fonterra & Rekomendasi', 'field_name' => 'analisa_dampak_terhadap_fonterra', 'field_type' => 'textarea', 'placeholder' => 'Analisa dampak terhadap penjualan Fonterra dan saran antisipasi...', 'is_required' => false],
                ]
            ],

            // 8. Price Monitoring (Own & Competitor)
            [
                'code' => 'RPT-FONTERRA-PRICE-CHECK-01',
                'title' => 'Laporan Price Monitoring (Fonterra vs Kompetitor)',
                'description' => 'Pencatatan dan perbandingan harga normal & harga promo per SKU produk Fonterra dan kompetitor di toko.',
                'category' => 'price',
                'icon' => 'currency-dollar',
                'color' => '#388E3C',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Asal Produk (Own Brand / Kompetitor)', 'field_name' => 'asal_produk', 'field_type' => 'radio', 'options' => ['Produk Fonterra (Own Brand)', 'Produk Kompetitor (Competitor Brand)'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Susu Dewasa', 'Susu Anak', 'Butter & Margarine', 'Keju & Cooking Dairy'], 'is_required' => true],
                    ['field_label' => 'Nama Brand / Produsen', 'field_name' => 'nama_brand', 'field_type' => 'text', 'placeholder' => 'Contoh: Anlene, Entrasol, Anchor, Elle & Vire', 'is_required' => true],
                    ['field_label' => 'Nama SKU & Ukuran Kemasan', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Anlene Gold 5X Vanilla 650g', 'is_required' => true],
                    ['field_label' => 'Harga Normal di Toko (Rp)', 'field_name' => 'harga_normal_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Harga Promo di Toko (Rp)', 'field_name' => 'harga_promo_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0 (Isi 0 jika tidak ada promo)', 'is_required' => true],
                    ['field_label' => 'Status Promo Toko', 'field_name' => 'status_promo', 'field_type' => 'radio', 'options' => ['Sedang Ada Promo', 'Harga Normal (Tidak Ada Promo)'], 'is_required' => true],
                    ['field_label' => 'Periode Promo (Jika Ada)', 'field_name' => 'periode_promo', 'field_type' => 'text', 'placeholder' => 'Contoh: 1 - 15 Agustus 2026', 'is_required' => false],
                    ['field_label' => 'Status Kesesuaian Price Tag di Rak', 'field_name' => 'status_price_tag', 'field_type' => 'radio', 'options' => ['Price Tag Ada & Sesuai', 'Price Tag Ada tapi Harga Beda', 'Price Tag Hilang / Tidak Ada'], 'is_required' => true],
                    ['field_label' => 'Apakah Termasuk SKU Fokus?', 'field_name' => 'apakah_sku_fokus', 'field_type' => 'radio', 'options' => ['Ya (SKU Fokus Fonterra / Kompetitor)', 'Bukan SKU Fokus'], 'is_required' => true],
                    ['field_label' => 'Foto Price Tag di Rak Toko', 'field_name' => 'foto_price_tag_rak', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Selisih Harga / Respon Pembeli', 'field_name' => 'catatan_harga', 'field_type' => 'textarea', 'placeholder' => 'Catatan selisih harga atau respon pembeli...', 'is_required' => false],
                ]
            ],

            // 9. Kemasan & Sticker Tracking
            [
                'code' => 'RPT-FONTERRA-PACKAGING-STICKER-01',
                'title' => 'Laporan Tracking Kemasan & Pemasangan Stiker Fonterra',
                'description' => 'Monitoring perputaran stok kemasan lama vs baru (transisi desain kemasan) dan penempelan stiker promosi pada kemasan produk.',
                'category' => 'display',
                'icon' => 'sparkles',
                'color' => '#F57C00',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Nama Program / Campaign', 'field_name' => 'nama_program_campaign', 'field_type' => 'text', 'placeholder' => 'Contoh: Tracking Kemasan Boneeto Balok Susun / Stiker Anlene Gold 5X', 'is_required' => true],
                    ['field_label' => 'Brand Produk Fonterra', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['Anlene', 'Boneeto', 'Anchor'], 'is_required' => true],
                    ['field_label' => 'Nama SKU Produk yang Dicek', 'field_name' => 'sku_nama_produk', 'field_type' => 'text', 'placeholder' => 'Nama SKU produk yang dicek', 'is_required' => true],
                    ['field_label' => 'Total Stok Kemasan Lama di Rak & Gudang (Pcs)', 'field_name' => 'jumlah_kemasan_lama_rak', 'field_type' => 'number', 'placeholder' => 'Jumlah stok kemasan lama', 'is_required' => true],
                    ['field_label' => 'Total Stok Kemasan Baru di Rak & Gudang (Pcs)', 'field_name' => 'jumlah_kemasan_baru_rak', 'field_type' => 'number', 'placeholder' => 'Jumlah stok kemasan baru', 'is_required' => true],
                    ['field_label' => 'Total Stiker yang Ditempel Hari Ini (Pcs)', 'field_name' => 'jumlah_stiker_ditempel', 'field_type' => 'number', 'placeholder' => 'Jumlah stiker yang berhasil ditempel', 'is_required' => true],
                    ['field_label' => 'Sisa Stok Stiker Fisik yang Dipegang (Pcs)', 'field_name' => 'kondisi_stok_stiker_sisa', 'field_type' => 'number', 'placeholder' => 'Sisa stok stiker yang belum terpakai', 'is_required' => true],
                    ['field_label' => 'Foto Produk yang Sudah Ditempel Stiker / Perbandingan Kemasan', 'field_name' => 'foto_kemasan_stiker', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan / Kendala Penempelan Stiker di Toko', 'field_name' => 'catatan_kendala_penempelan', 'field_type' => 'textarea', 'placeholder' => 'Catatan kendala atau penolakan toko jika ada...', 'is_required' => false],
                ]
            ],

            // 10. POSM Implementation & Tracking
            [
                'code' => 'RPT-FONTERRA-POSM-01',
                'title' => 'Laporan Pemasangan & Kondisi POSM Fonterra',
                'description' => 'Audit ketersediaan, pemasangan materi promosi POSM baru, serta inventarisasi POSM rusak / hilang di toko.',
                'category' => 'posm',
                'icon' => 'photo',
                'color' => '#5C6BC0',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Nama Program / Materi POSM', 'field_name' => 'nama_posm_campaign', 'field_type' => 'text', 'placeholder' => 'Contoh: Wobbler Anlene Kalsium / Shelftalker Anchor Baking', 'is_required' => true],
                    ['field_label' => 'Tipe Material POSM', 'field_name' => 'tipe_material_posm', 'field_type' => 'dropdown', 'options' => ['Wobbler', 'Shelftalker', 'Hanging Mobile / Banner Gantung', 'Floor Sticker', 'Poster A3/A4', 'Chiller Frame Sticker', 'Standee Flag'], 'is_required' => true],
                    ['field_label' => 'Sumber Materi POSM', 'field_name' => 'sumber_posm', 'field_type' => 'radio', 'options' => ['Materi Resmi Fonterra (Official)', 'Materi Agency / Vendor Toko'], 'is_required' => true],
                    ['field_label' => 'Status Pemasangan POSM di Toko', 'field_name' => 'status_pemasangan', 'field_type' => 'radio', 'options' => ['Sudah Terpasang Rapi', 'Belum Terpasang (Baru Tiba)', 'Toko Menolak Pemasangan POSM'], 'is_required' => true],
                    ['field_label' => 'Jumlah POSM Terpasang Sebelumnya (Pcs)', 'field_name' => 'qty_terpasang_sebelumnya', 'field_type' => 'number', 'placeholder' => 'Jumlah POSM yang sudah ada', 'is_required' => true],
                    ['field_label' => 'Jumlah POSM yang Baru Dipasang Hari Ini (Pcs)', 'field_name' => 'qty_pemasangan_baru', 'field_type' => 'number', 'placeholder' => 'Jumlah POSM baru dipasang', 'is_required' => true],
                    ['field_label' => 'Jumlah POSM yang Rusak / Robek (Pcs)', 'field_name' => 'qty_posm_rusak', 'field_type' => 'number', 'placeholder' => 'Jumlah POSM rusak', 'is_required' => true],
                    ['field_label' => 'Jumlah POSM yang Hilang / Dicopot Toko (Pcs)', 'field_name' => 'qty_posm_hilang', 'field_type' => 'number', 'placeholder' => 'Jumlah POSM hilang', 'is_required' => true],
                    ['field_label' => 'Total POSM Aktif Terpasang Saat Ini (Pcs)', 'field_name' => 'total_posm_aktif_akhir', 'field_type' => 'number', 'placeholder' => 'Total POSM aktif', 'is_required' => true],
                    ['field_label' => 'Foto 1: POSM Tampak Dekat', 'field_name' => 'foto_posm_tampak_dekat', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: POSM Tampak Jauh / Full Area Rak', 'field_name' => 'foto_posm_tampak_jauh', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 3: Dokumentasi Tambahan', 'field_name' => 'foto_posm_tambahan', 'field_type' => 'camera_photo', 'is_required' => false],
                    ['field_label' => 'Catatan Kendala & Izin Penempatan POSM Toko', 'field_name' => 'catatan_posm', 'field_type' => 'textarea', 'placeholder' => 'Catatan izin toko atau kondisi penempatan POSM...', 'is_required' => false],
                ]
            ],

            // 11. Additional Display
            [
                'code' => 'RPT-FONTERRA-ADD-DISPLAY-01',
                'title' => 'Laporan Additional Display Fonterra',
                'description' => 'Monitoring penempatan display sekunder, sewa endcap, floor display, wing stage, dan hanging sachet di luar rak reguler.',
                'category' => 'display',
                'icon' => 'squares-plus',
                'color' => '#26A69A',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Tipe Additional Display', 'field_name' => 'tipe_additional_display', 'field_type' => 'dropdown', 'options' => ['Endcap Gondola Depan', 'Floor Display / Island Standee', 'Wing Stage / Side Rack', 'Hanger Sachet (Permanent/Temporary)', 'Chiller / Freezer Dedicated', 'Table Top Kasir', 'Clip Strip / Tier Rack'], 'is_required' => true],
                    ['field_label' => 'Status Biaya / Kontrak Display', 'field_name' => 'status_biaya_display', 'field_type' => 'radio', 'options' => ['Sewa Berbayar (Paid / Contract)', 'Gratis / Kesepakatan Toko (Free Placement)', 'Bonus Pembelian Toko'], 'is_required' => true],
                    ['field_label' => 'Brand Produk yang Didisplay', 'field_name' => 'brand_produk_display', 'field_type' => 'dropdown', 'options' => ['Anlene', 'Boneeto', 'Anchor Dairy', 'Mix Brand Fonterra'], 'is_required' => true],
                    ['field_label' => 'Status Realisasi Display di Toko', 'field_name' => 'status_realisasi_display', 'field_type' => 'radio', 'options' => ['Aktif & Terisi Penuh Produk Fonterra', 'Produk Menipis / Perlu Refill Segera', 'Terisi Produk Campur / Kompetitor', 'Display Kosong / Belum Disiapkan Toko'], 'is_required' => true],
                    ['field_label' => 'Deskripsi Lokasi Display di Toko', 'field_name' => 'keterangan_lokasi_display', 'field_type' => 'text', 'placeholder' => 'Contoh: Endcap Lorong 3 Depan Kasir Utama', 'is_required' => true],
                    ['field_label' => 'Foto 1: Tampak Depan Full Display', 'field_name' => 'foto_additional_display_depan', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: Tampak Samping & Suasana Sekitar', 'field_name' => 'foto_additional_display_samping', 'field_type' => 'camera_photo', 'is_required' => false],
                    ['field_label' => 'Catatan Kebersihan Display, Refill, & Kesepakatan Toko', 'field_name' => 'catatan_additional_display', 'field_type' => 'textarea', 'placeholder' => 'Catatan kebersihan display, stok refill, dan kesepakatan toko...', 'is_required' => false],
                ]
            ],
        ];

        $hasIconCol = \Illuminate\Support\Facades\Schema::hasColumn('report_templates', 'icon');
        $hasColorCol = \Illuminate\Support\Facades\Schema::hasColumn('report_templates', 'color');

        foreach ($templatesData as $tpl) {
            $fields = $tpl['fields'];
            unset($tpl['fields']);

            if (!$hasIconCol) {
                unset($tpl['icon']);
            }
            if (!$hasColorCol) {
                unset($tpl['color']);
            }

            $template = ReportTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                array_merge($tpl, ['principal_id' => $primaryFonterra->id])
            );

            // Sync seluruh id principal fonterra yang matching
            $template->principals()->sync($allFonterraIds);

            foreach ($fields as $index => $field) {
                ReportFormField::updateOrCreate(
                    ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                    array_merge($field, ['order_index' => $index + 1])
                );
            }
        }
    }

    /**
     * 3. Seed 9 Official Reporting Templates for Daesang / MamaSuka
     */
    private function seedMamasukaTemplates(Principal $primaryMamasuka, array $allMamasukaIds): void
    {
        $templatesData = [
            // 1. Rental Display
            [
                'code' => 'RPT-MAMASUKA-RENT-DISPLAY-01',
                'title' => 'Laporan Rental Display Mamasuka (Sewa Display)',
                'description' => 'Monitoring realisasi sewa display, endcap gondola, wing stage, chiller, dan audit kesesuaian masa kontrak display di toko.',
                'category' => 'display',
                'icon' => 'squares-2x2',
                'color' => '#E53935',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk Mamasuka', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['MAMASUKA', 'MIWON', 'DAESANG', 'MIX BRAND'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['GIM (Rumput Laut Panggang)', 'DELISAOS (Saus Tiram, Pasta, Hot Lava)', 'TEPUNG BUMBU & ROTI', 'MAYONAIS & SALAD DRESSING', 'BUMBU INSTAN & KUAH BAKSO', 'MSG CP (Miwon)', 'TOPPOKI & KOREAN FOOD'], 'is_required' => true],
                    ['field_label' => 'Sub Kategori & Nama SKU Produk', 'field_name' => 'nama_sku_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Delisaos Hot Lava 260ml / Gim Bori Original 30g', 'is_required' => true],
                    ['field_label' => 'Tipe Rental Display', 'field_name' => 'tipe_rental_display', 'field_type' => 'dropdown', 'options' => ['Endcap Gondola Depan (TG)', 'Floor Display / Island', 'Wing Stage / Side Rack', 'Chiller Dedicated', 'Gondola Header', 'Clip Strip / Tier Rack'], 'is_required' => true],
                    ['field_label' => 'Periode Kontrak Sewa: Tanggal Mulai', 'field_name' => 'periode_kontrak_mulai', 'field_type' => 'date', 'is_required' => true],
                    ['field_label' => 'Periode Kontrak Sewa: Tanggal Selesai', 'field_name' => 'periode_kontrak_selesai', 'field_type' => 'date', 'is_required' => true],
                    ['field_label' => 'Status Implementasi di Toko', 'field_name' => 'status_implementasi', 'field_type' => 'radio', 'options' => ['Sesuai Kontrak & Aktif', 'Belum Terpasang (Menunggu PIC Toko)', 'Terhalang Barang / Produk Lain', 'Toko Menolak Pemasangan'], 'is_required' => true],
                    ['field_label' => 'Materi POSM Terpasang', 'field_name' => 'posm_terpasang', 'field_type' => 'dropdown', 'options' => ['Wobbler', 'Shelftalker', 'Hanger Sachet', 'Floor Sticker', 'Banner Gantung', 'Tidak Ada POSM'], 'is_required' => true],
                    ['field_label' => 'Foto 1: Tampak Depan Full Display Rental', 'field_name' => 'foto_rental_depan', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: Tampak Samping & Lingkungan Rak', 'field_name' => 'foto_rental_samping', 'field_type' => 'camera_photo', 'is_required' => false],
                    ['field_label' => 'Catatan / Remark Sewa Display', 'field_name' => 'catatan_rental', 'field_type' => 'textarea', 'placeholder' => 'Catatan kondisi sewa, nego dengan buyer toko, atau masalah display...', 'is_required' => false],
                ]
            ],

            // 2. Additional Display
            [
                'code' => 'RPT-MAMASUKA-ADD-DISPLAY-01',
                'title' => 'Laporan Additional Display Mamasuka (Display Tambahan)',
                'description' => 'Pengajuan dan pencatatan display sekunder tambahan di luar sewa berbayar (side rack, hanger kasir, island ekstra).',
                'category' => 'display',
                'icon' => 'squares-plus',
                'color' => '#D32F2F',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['MAMASUKA', 'MIWON', 'DAESANG'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk yang Didisplay', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['GIM (Rumput Laut)', 'DELISAOS', 'TEPUNG BUMBU', 'MAYONAIS', 'BUMBU INSTAN', 'MSG CP'], 'is_required' => true],
                    ['field_label' => 'Tipe Additional Display', 'field_name' => 'tipe_additional_display', 'field_type' => 'dropdown', 'options' => ['Side Rack / Wing Stage', 'Floor Island Ekstra', 'Hanger Depan Kasir', 'Clip Strip di Lorong', 'Endcap Free Placement'], 'is_required' => true],
                    ['field_label' => 'Posisi / Lokasi Titik Display di Toko', 'field_name' => 'posisi_display_toko', 'field_type' => 'text', 'placeholder' => 'Contoh: Hanger Sachet di Kasir 3 / Side Rack Lorong Bumbu', 'is_required' => true],
                    ['field_label' => 'Status Pengajuan Display', 'field_name' => 'status_pengajuan', 'field_type' => 'radio', 'options' => ['Propose (Pengajuan Baru ke Toko)', 'Approve (Disetujui Toko & Terpasang)', 'Reject (Ditolak Toko)'], 'is_required' => true],
                    ['field_label' => 'Alasan Penolakan Toko (Jika Reject)', 'field_name' => 'alasan_reject', 'field_type' => 'textarea', 'placeholder' => 'Alasan kepala toko menolak display tambahan...', 'is_required' => false],
                    ['field_label' => 'Foto Dokumentasi Display Tambahan', 'field_name' => 'foto_additional_display', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Tambahan', 'field_name' => 'catatan_display', 'field_type' => 'textarea', 'placeholder' => 'Catatan performa display tambahan...', 'is_required' => false],
                ]
            ],

            // 3. Pricing & Price Tag
            [
                'code' => 'RPT-MAMASUKA-PRICING-01',
                'title' => 'Laporan Cek Harga & Price Tag Produk Mamasuka',
                'description' => 'Audit kesesuaian harga normal, harga promo, price tag rak, dan status ketersediaan produk Mamasuka & Miwon.',
                'category' => 'pricing',
                'icon' => 'currency-dollar',
                'color' => '#C62828',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['MAMASUKA', 'MIWON', 'DAESANG'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['GIM', 'DELISAOS', 'TEPUNG BUMBU', 'MAYONAIS', 'BUMBU INSTAN', 'MSG CP'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk', 'field_name' => 'nama_sku_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Mamasuka Delisaos Saus Tiram 260ml', 'is_required' => true],
                    ['field_label' => 'Harga Normal Toko (Rupiah)', 'field_name' => 'harga_normal_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Harga Promo Toko (Rupiah)', 'field_name' => 'harga_promo_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0 jika tidak ada promo', 'is_required' => false],
                    ['field_label' => 'Tipe Promo Harga', 'field_name' => 'tipe_promo_harga', 'field_type' => 'dropdown', 'options' => ['Tidak Ada Promo (Harga Normal)', 'Diskon Langsung (Price Cut)', 'Banded / Bundling', 'Special Mailer / Katalog Toko', 'Cashback / Voucher'], 'is_required' => true],
                    ['field_label' => 'Status Price Tag di Rak', 'field_name' => 'status_price_tag', 'field_type' => 'radio', 'options' => ['Terpasang Sesuai & Jelas', 'Harga di Price Tag Salah / Tidak Update', 'Price Tag Rusak / Hilang'], 'is_required' => true],
                    ['field_label' => 'Status Produk Fokus', 'field_name' => 'status_focus_sku', 'field_type' => 'radio', 'options' => ['Focus SKU (Produk Utama)', 'Non-Focus SKU'], 'is_required' => true],
                    ['field_label' => 'Ketersediaan Produk di Rak', 'field_name' => 'status_ketersediaan', 'field_type' => 'radio', 'options' => ['Available (Tersedia)', 'Out of Stock (Kosong)'], 'is_required' => true],
                    ['field_label' => 'Foto Price Tag & Produk di Rak', 'field_name' => 'foto_price_tag', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Harga', 'field_name' => 'catatan_harga', 'field_type' => 'textarea', 'placeholder' => 'Catatan persaingan harga atau kendala price tag...', 'is_required' => false],
                ]
            ],

            // 4. Promo Tracking Mamasuka
            [
                'code' => 'RPT-MAMASUKA-PROMO-OWN-01',
                'title' => 'Laporan Tracking Program Promo Mamasuka',
                'description' => 'Pencatatan realisasi program promosi konsumen (diskon, gimmick, hadiah) Mamasuka di outlet toko.',
                'category' => 'promo',
                'icon' => 'tag',
                'color' => '#FF5722',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Nama / Kode Program Promo', 'field_name' => 'nama_program_promo', 'field_type' => 'text', 'placeholder' => 'Contoh: Promo Gim Bori Rafaksi 15% / Promo Delisaos Piring Cantik', 'is_required' => true],
                    ['field_label' => 'Brand & Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Mamasuka - Gim (Rumput Laut)', 'Mamasuka - Delisaos', 'Mamasuka - Tepung Bumbu', 'Mamasuka - Mayonais', 'Mamasuka - Bumbu Instan', 'Miwon - MSG'], 'is_required' => true],
                    ['field_label' => 'SKU Produk yang Dipromosikan', 'field_name' => 'sku_produk_promo', 'field_type' => 'text', 'placeholder' => 'SKU produk yang masuk program promo', 'is_required' => true],
                    ['field_label' => 'Tipe Program Promo', 'field_name' => 'tipe_program_promo', 'field_type' => 'dropdown', 'options' => ['Diskon Harga (Price Cut / Rafaksi)', 'Beli X Gratis Y (Buy 1 Get 1)', 'Gimmick Hadiah Pembelian', 'Bundling Pack', 'Mailer / Koran Promosi Toko'], 'is_required' => true],
                    ['field_label' => 'Deskripsi Mekanisme Promo', 'field_name' => 'mekanisme_promo', 'field_type' => 'text', 'placeholder' => 'Contoh: Beli 2 Gim Bori 30g Diskon 15% / Beli 2 Delisaos Gratis Mangkok', 'is_required' => true],
                    ['field_label' => 'Periode Promo: Mulai & Selesai', 'field_name' => 'periode_promo_info', 'field_type' => 'text', 'placeholder' => 'Contoh: 15 Juli 2026 s/d 31 Juli 2026', 'is_required' => true],
                    ['field_label' => 'Status Implementasi di Toko', 'field_name' => 'status_implementasi_promo', 'field_type' => 'radio', 'options' => ['Berjalan Sesuai Jadwal', 'Belum Berjalan di Toko', 'Stok Hadiah / Gimmick Habis', 'Toko Menolak Mengikuti Promo'], 'is_required' => true],
                    ['field_label' => 'Harga Normal vs Harga Promo (Rp)', 'field_name' => 'perbandingan_harga', 'field_type' => 'text', 'placeholder' => 'Contoh: Normal Rp 14.300 -> Promo Rp 10.696', 'is_required' => true],
                    ['field_label' => 'Status Material Promosi (POP / POSM)', 'field_name' => 'status_pop_posm', 'field_type' => 'radio', 'options' => ['POP Terpasang Lengkap & Rapi', 'POP Belum Terpasang', 'POP Rusak / Hilang'], 'is_required' => true],
                    ['field_label' => 'Foto Bukti Promo di Rak / Kasir Toko', 'field_name' => 'foto_promo_toko', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Kendala & Respon Konsumen', 'field_name' => 'catatan_promo', 'field_type' => 'textarea', 'placeholder' => 'Catatan kendala promosi atau respon pembeli...', 'is_required' => false],
                ]
            ],

            // 5. Promo Competitor
            [
                'code' => 'RPT-MAMASUKA-PROMO-COMP-01',
                'title' => 'Laporan Promo Kompetitor (Sasa, Ajinomoto, Kobe, dll)',
                'description' => 'Monitoring aktivitas promosi, diskon, hadiah, dan materi display brand kompetitor bumbu & rumput laut.',
                'category' => 'competitor',
                'icon' => 'eye',
                'color' => '#7B1FA2',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Nama Brand Kompetitor', 'field_name' => 'brand_kompetitor', 'field_type' => 'dropdown', 'options' => ['SASA', 'AJINOMOTO / SAJIKU', 'KOBE', 'ROYCO (Unilever)', 'BANGO / SEDAAP', 'MAYUMI', 'TAO KAE NOI', 'MANJUN SEAWEED', 'LAINNYA'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk Kompetitor', 'field_name' => 'kategori_kompetitor', 'field_type' => 'dropdown', 'options' => ['Rumput Laut / Seaweed Snack', 'Tepung Bumbu / Roti', 'Saus Tiram & Saus Pasta', 'Mayonais & Salad Dressing', 'Bumbu Masak Instan', 'MSG / Penguat Rasa'], 'is_required' => true],
                    ['field_label' => 'Nama SKU Produk Kompetitor', 'field_name' => 'sku_produk_kompetitor', 'field_type' => 'text', 'placeholder' => 'Contoh: Sajiku Tepung Bumbu Serbaguna 210g / Sasa Santan', 'is_required' => true],
                    ['field_label' => 'Tipe Promo Kompetitor', 'field_name' => 'tipe_promo_kompetitor', 'field_type' => 'dropdown', 'options' => ['Diskon Harga (Price Cut)', 'Beli X Gratis Y', 'Bundling Produk', 'Hadiah Langsung / Gimmick', 'Katalog / Mailer Toko'], 'is_required' => true],
                    ['field_label' => 'Mekanisme & Nilai Promo Kompetitor', 'field_name' => 'mekanisme_promo_kompetitor', 'field_type' => 'text', 'placeholder' => 'Contoh: Normal Rp 18.000 -> Promo Rp 13.900', 'is_required' => true],
                    ['field_label' => 'Display Tambahan yang Digunakan Kompetitor', 'field_name' => 'display_kompetitor', 'field_type' => 'dropdown', 'options' => ['Endcap Khusus', 'Floor Display Island', 'Wing Rack / Side Rack', 'Hanger Sachet', 'Rak Reguler Saja'], 'is_required' => true],
                    ['field_label' => 'Foto Dokumentasi Promo Kompetitor', 'field_name' => 'foto_promo_kompetitor', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Dampak terhadap Penjualan Mamasuka', 'field_name' => 'catatan_dampak_kompetitor', 'field_type' => 'textarea', 'placeholder' => 'Catatan dampak promo kompetitor terhadap produk kita...', 'is_required' => false],
                ]
            ],

            // 6. Check Stock & OOS
            [
                'code' => 'RPT-MAMASUKA-STOCK-OOS-01',
                'title' => 'Laporan Cek Stok & Out of Stock (OOS) Mamasuka',
                'description' => 'Monitoring jumlah stok fisik di rak dan gudang toko, identifikasi barang kosong (OOS), dan tracking estimasi order (PO).',
                'category' => 'stock',
                'icon' => 'archive-box',
                'color' => '#EF6C00',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['MAMASUKA', 'MIWON', 'DAESANG'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['GIM', 'DELISAOS', 'TEPUNG BUMBU', 'MAYONAIS', 'BUMBU INSTAN', 'MSG CP'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk', 'field_name' => 'nama_sku_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Miwon MSG 500g / Mamasuka Gim Bori BBQ', 'is_required' => true],
                    ['field_label' => 'Status Produk Fokus OOS', 'field_name' => 'status_focus_oos', 'field_type' => 'radio', 'options' => ['Focus SKU (Prioritas Tinggi)', 'Regular SKU'], 'is_required' => true],
                    ['field_label' => 'Minimum Stock Standar Toko (Pcs)', 'field_name' => 'minimum_stock_qty', 'field_type' => 'number', 'placeholder' => 'Kebutuhan stok minimal rak', 'is_required' => true],
                    ['field_label' => 'Actual Stock Fisik Saat Ini (Pcs)', 'field_name' => 'actual_stock_qty', 'field_type' => 'number', 'placeholder' => 'Sisa stok fisik di toko', 'is_required' => true],
                    ['field_label' => 'Status Ketersediaan Barang', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['In Stock (Stok Aman)', 'OOS (Out of Stock / Kosong Total)', 'Under Minimum Stock (Menipis)'], 'is_required' => true],
                    ['field_label' => 'Alasan Barang Kosong (Jika OOS / Menipis)', 'field_name' => 'alasan_oos', 'field_type' => 'dropdown', 'options' => ['PO Belum Diterbitkan Buyer Toko', 'Stok Distributor / Gudang Pusat Kosong', 'Pengiriman Pending / Terlambat', 'Barang Rusak / Bad Stock Belum Diganti', 'Barang Tidak Terdistribusi ke Toko Ini'], 'is_required' => false],
                    ['field_label' => 'Estimasi Tanggal PO / Kedatangan Barang', 'field_name' => 'estimasi_po_date', 'field_type' => 'date', 'is_required' => false],
                    ['field_label' => 'Nama PIC Toko / MD yang Dihubungi', 'field_name' => 'nama_pic_toko', 'field_type' => 'text', 'placeholder' => 'Nama kepala seksi / staff gudang toko', 'is_required' => false],
                    ['field_label' => 'Harga Produk (Rp)', 'field_name' => 'harga_produk_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
                    ['field_label' => 'Foto Rak Display / Gudang Penyimpanan', 'field_name' => 'foto_stok_rak', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Tindak Lanjut Pemesanan', 'field_name' => 'catatan_tindak_lanjut', 'field_type' => 'textarea', 'placeholder' => 'Tindakan yang sudah dilakukan untuk mendorong order...', 'is_required' => false],
                ]
            ],

            // 7. Sell Out SPG Reguler & MD
            [
                'code' => 'RPT-MAMASUKA-SELLOUT-REG-01',
                'title' => 'Laporan Penjualan (Sell Out) SPG Reguler & MD',
                'description' => 'Pencatatan pergerakan stok harian/mingguan: Stok Awal, Penerimaan (Sell In), Retur, Stok Akhir, dan Total Qty & Omzet Terjual.',
                'category' => 'sellout',
                'icon' => 'shopping-bag',
                'color' => '#2E7D32',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['MAMASUKA', 'MIWON', 'DAESANG'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['GIM (Rumput Laut)', 'DELISAOS', 'TEPUNG BUMBU & ROTI', 'MAYONAIS', 'BUMBU INSTAN', 'MSG CP'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk', 'field_name' => 'nama_sku_produk', 'field_type' => 'text', 'placeholder' => 'Nama produk yang terjual', 'is_required' => true],
                    ['field_label' => 'Stok Awal Fisik di Toko (First Stock / Pcs)', 'field_name' => 'stok_awal_qty', 'field_type' => 'number', 'placeholder' => 'Jumlah stok awal', 'is_required' => true],
                    ['field_label' => 'Penerimaan Barang Masuk (Sell In / Pcs)', 'field_name' => 'sell_in_qty', 'field_type' => 'number', 'placeholder' => 'Barang baru masuk (0 jika tidak ada)', 'is_required' => true],
                    ['field_label' => 'Retur / Barang Rusak (Return / Pcs)', 'field_name' => 'retur_qty', 'field_type' => 'number', 'placeholder' => 'Barang retur (0 jika tidak ada)', 'is_required' => true],
                    ['field_label' => 'Stok Akhir Fisik di Toko (Last Stock / Pcs)', 'field_name' => 'stok_akhir_qty', 'field_type' => 'number', 'placeholder' => 'Sisa stok akhir', 'is_required' => true],
                    ['field_label' => 'Total Qty Terjual (Sell Out / Pcs)', 'field_name' => 'sell_out_qty', 'field_type' => 'number', 'placeholder' => 'Total pcs produk terjual', 'is_required' => true],
                    ['field_label' => 'Harga Jual Satuan Toko (Rupiah)', 'field_name' => 'harga_satuan_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Total Nilai Penjualan / Omzet (Rupiah)', 'field_name' => 'total_omzet_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Foto Bukti Nota / Kartu Stok / Struk Kasir', 'field_name' => 'foto_bukti_penjualan', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Penjualan Harian', 'field_name' => 'catatan_penjualan', 'field_type' => 'textarea', 'placeholder' => 'Catatan situasi penjualan dan daya beli konsumen...', 'is_required' => false],
                ]
            ],

            // 8. Sell Out SPG Demo & Event
            [
                'code' => 'RPT-MAMASUKA-SELLOUT-DEMO-01',
                'title' => 'Laporan Penjualan (Sell Out) SPG Demo & Event Masak',
                'description' => 'Dokumentasi aktivitas promosi demo masak live, sampling rasa gratis, bazaar event, dan pencapaian target penjualan demo.',
                'category' => 'sellout',
                'icon' => 'sparkles',
                'color' => '#F57C00',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Jenis Aktivitas Promosi', 'field_name' => 'jenis_aktivitas', 'field_type' => 'dropdown', 'options' => ['Demo Masak Live (Cooking Demo)', 'Sampling Rasa Gratis (Dry/Wet Sampling)', 'Bazaar Toko / Pasar Murah', 'Event Khusus / Endorsement Booth'], 'is_required' => true],
                    ['field_label' => 'Menu Resep Masakan yang Didemokan', 'field_name' => 'menu_resep_demo', 'field_type' => 'text', 'placeholder' => 'Contoh: Bakwan Sayur Renyah Mamasuka, Delisaos Pasta Hot Lava, Gim Bori Rice Bowl', 'is_required' => true],
                    ['field_label' => 'Jumlah Porsi Sampling yang Dibagikan (Cup/Porsi)', 'field_name' => 'qty_sampling_dibagikan', 'field_type' => 'number', 'placeholder' => 'Estimasi porsi tester dibagikan ke pengunjung', 'is_required' => true],
                    ['field_label' => 'Total Qty Produk Terjual Selama Event (Pcs)', 'field_name' => 'total_qty_terjual_event', 'field_type' => 'number', 'placeholder' => 'Total pcs produk terjual dari demo', 'is_required' => true],
                    ['field_label' => 'Total Nilai Omzet Penjualan Demo (Rupiah)', 'field_name' => 'total_omzet_demo_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                    ['field_label' => 'Foto 1: Dokumentasi Booth & SPG Memasak / Sampling', 'field_name' => 'foto_demo_booth', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: Antrean / Antusiasme Pengunjung Toko', 'field_name' => 'foto_pengunjung_demo', 'field_type' => 'camera_photo', 'is_required' => false],
                    ['field_label' => 'Feedback & Komentar Konsumen terhadap Rasa Produk', 'field_name' => 'feedback_konsumen', 'field_type' => 'textarea', 'placeholder' => 'Testimoni pengunjung terhadap rasa bumbu/makanan...', 'is_required' => true],
                ]
            ],

            // 9. Expired Date Monitoring
            [
                'code' => 'RPT-MAMASUKA-EXPIRED-01',
                'title' => 'Laporan Monitoring Produk Expired Date Mamasuka',
                'description' => 'Pencatatan tanggal kadaluarsa fisik produk di rak toko, identifikasi stok mendekati ED (< 3 bulan), dan usulan retur/tukar guling.',
                'category' => 'expiry',
                'icon' => 'clock',
                'color' => '#D81B60',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Brand Produk', 'field_name' => 'brand_produk', 'field_type' => 'dropdown', 'options' => ['MAMASUKA', 'MIWON', 'DAESANG'], 'is_required' => true],
                    ['field_label' => 'Kategori Produk', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['GIM (Rumput Laut)', 'DELISAOS', 'TEPUNG BUMBU', 'MAYONAIS', 'BUMBU INSTAN', 'MSG CP'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk', 'field_name' => 'nama_sku_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Mamasuka Rumput Laut Salted Egg 4.5g', 'is_required' => true],
                    ['field_label' => 'Bulan & Tahun Expired Terdekat di Kemasan', 'field_name' => 'tanggal_expired_kemasan', 'field_type' => 'month_year', 'is_required' => true],
                    ['field_label' => 'Jumlah Stok Fisik dengan ED Tersebut (Pcs)', 'field_name' => 'qty_stok_ed', 'field_type' => 'number', 'placeholder' => 'Jumlah pcs', 'is_required' => true],
                    ['field_label' => 'Status Selisih Waktu Menuju Expired', 'field_name' => 'status_selisih_ed', 'field_type' => 'radio', 'options' => ['Kritis (< 1 Bulan Menuju ED)', 'Near Expired (1 - 3 Bulan Menuju ED)', 'Waspada (3 - 6 Bulan Menuju ED)', 'Aman (> 6 Bulan Menuju ED)'], 'is_required' => true],
                    ['field_label' => 'Rekomendasi Tindakan Penanganan', 'field_name' => 'rekomendasi_tindakan', 'field_type' => 'dropdown', 'options' => ['Aman Dibiarkan di Rak Display', 'Ajukan Program Promo Diskon Clearance', 'Proses Retur / Tukar Guling ke Distributor', 'Sudah Ditarik dari Rak Display Toko'], 'is_required' => true],
                    ['field_label' => 'Foto Batch Number & Tanggal Expired di Kemasan', 'field_name' => 'foto_expired_date', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Koordinasi dengan Kepala Toko / MD', 'field_name' => 'catatan_expired', 'field_type' => 'textarea', 'placeholder' => 'Catatan tindak lanjut dengan pihak toko...', 'is_required' => false],
                ]
            ],
        ];

        $hasIconCol = \Illuminate\Support\Facades\Schema::hasColumn('report_templates', 'icon');
        $hasColorCol = \Illuminate\Support\Facades\Schema::hasColumn('report_templates', 'color');

        foreach ($templatesData as $tpl) {
            $fields = $tpl['fields'];
            unset($tpl['fields']);

            if (!$hasIconCol) {
                unset($tpl['icon']);
            }
            if (!$hasColorCol) {
                unset($tpl['color']);
            }

            $template = ReportTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                array_merge($tpl, ['principal_id' => $primaryMamasuka->id])
            );

            // Sync seluruh id principal mamasuka yang matching
            $template->principals()->sync($allMamasukaIds);

            foreach ($fields as $index => $field) {
                ReportFormField::updateOrCreate(
                    ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                    array_merge($field, ['order_index' => $index + 1])
                );
            }
        }
    }

    /**
     * 4. Seed 7 Official Reporting Templates for Wings Surya & Lion Wings
     */
    private function seedWingsTemplates(Principal $primaryWings, array $allWingsIds): void
    {
        $templatesData = [
            // 0. Report Penjualan & Pembagian Hadiah Nuvo (Wings Surya & Lion Wings)
            [
                'code' => 'RPT-WINGS-PENJUALAN-HADIAH-01',
                'title' => 'Report Penjualan & Pembagian Hadiah Nuvo (Wings Surya & Lion Wings)',
                'description' => 'Pencatatan total nominal penjualan produk Nuvo, total jumlah hadiah yang keluar, serta rincian pembagian hadiah (Jaket Bomber, Payung, Mug/Gelas, Toples/Kotak Makan, Mama Lemon 230gr, dan Hadiah Tambahan).',
                'category' => 'offtake',
                'icon' => 'gift',
                'color' => '#E53935',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Produk / Varian Nuvo yang Dijual', 'field_name' => 'produk_terjual', 'field_type' => 'dropdown', 'options' => ['Nuvo Liquid Soap Kemerdekaan 450ml', 'Nuvo Liquid Soap 250ml', 'Nuvo Liquid Soap Family 825ml', 'Nuvo Bar Soap Classic 76g', 'Nuvo Bar Soap Jumbo 110g', 'Nuvo Hand Sanitizer Gel 85ml', 'Nuvo Hand Sanitizer Spray 100ml', 'Nuvo Wet Wipes Antiseptik 50s', 'Mama Lemon Jeruk Nipis 230gr', 'Semua Varian Nuvo & Wings Promo'], 'placeholder' => 'Pilih varian produk...', 'is_required' => true],
                    ['field_label' => 'Total Penjualan Nuvo (Nominal Rupiah)', 'field_name' => 'total_penjualan_nuvo_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'help_text' => 'Total omzet penjualan Nuvo hari ini dalam rupiah (in rupiah)', 'is_required' => true],
                    ['field_label' => 'Total Pembagian Hadiah (Total Pcs)', 'field_name' => 'total_pembagian_hadiah', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Total seluruh pcs hadiah yang dibagikan ke konsumen (in pcs)', 'is_required' => true],
                    ['field_label' => 'Jumlah Hadiah: Jaket Bomber (Pcs)', 'field_name' => 'qty_jaket_bomber', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Jumlah jaket bomber yang keluar (isi 0 jika tidak ada)', 'is_required' => false],
                    ['field_label' => 'Jumlah Hadiah: Payung (Pcs)', 'field_name' => 'qty_payung', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Jumlah payung yang keluar (isi 0 jika tidak ada)', 'is_required' => false],
                    ['field_label' => 'Jumlah Hadiah: Mug / Gelas (Pcs)', 'field_name' => 'qty_mug_gelas', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Jumlah mug / gelas yang keluar (isi 0 jika tidak ada)', 'is_required' => false],
                    ['field_label' => 'Jumlah Hadiah: Toples / Kotak Makan (Pcs)', 'field_name' => 'qty_toples_kotak_makan', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Jumlah toples / kotak makan yang keluar (isi 0 jika tidak ada)', 'is_required' => false],
                    ['field_label' => 'Jumlah Hadiah: Mama Lemon 230gr (Pcs)', 'field_name' => 'qty_mama_lemon', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Jumlah Mama Lemon 230gr yang keluar sebagai hadiah (isi 0 jika tidak ada)', 'is_required' => false],
                    ['field_label' => 'Hadiah Tambahan Lain (Apabila Ada)', 'field_name' => 'hadiah_tambahan_lain', 'field_type' => 'text', 'placeholder' => 'Contoh: Piring Cantik, Tas Belanja, Pouch...', 'help_text' => 'Jenis hadiah tambahan jika ada promosi ekstra di toko', 'is_required' => false],
                    ['field_label' => 'Jumlah Hadiah Tambahan (Pcs)', 'field_name' => 'qty_hadiah_tambahan', 'field_type' => 'number', 'placeholder' => '0', 'help_text' => 'Jumlah pcs hadiah tambahan yang keluar (isi 0 jika tidak ada)', 'is_required' => false],
                    ['field_label' => 'Foto Bukti Struk Kasir / Penyerahan Hadiah', 'field_name' => 'foto_struk_hadiah', 'field_type' => 'camera_photo', 'help_text' => 'Wajib foto struk pembelian dan/atau penyerahan hadiah ke konsumen', 'is_required' => true],
                    ['field_label' => 'Catatan / Respon Konsumen & Event Penjualan', 'field_name' => 'catatan_penjualan', 'field_type' => 'textarea', 'placeholder' => 'Catatan situasi penjualan, respon konsumen terhadap hadiah, atau ketersediaan stok...', 'is_required' => false],
                ]
            ],
            // 1. OOS Wings Food
            [
                'code' => 'RPT-WINGS-OOS-FOOD-01',
                'title' => 'Laporan Cek Stok & OOS Wings Food (Mie Sedaap, Minuman, Snack)',
                'description' => 'Monitoring ketersediaan stok fisik harian produk Wings Food (Mie Sedaap, Floridina, Ale-Ale, Golda, Top Coffee, Potabee, Japota) di rak toko dan identifikasi barang kosong (OOS).',
                'category' => 'stock',
                'icon' => 'archive-box',
                'color' => '#E53935',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Kategori Produk Wings Food', 'field_name' => 'kategori_food', 'field_type' => 'dropdown', 'options' => ['Mie Instant (Mie Sedaap Bag / Cup / Eko Mie)', 'RTD Beverage (Floridina / Golda Coffee / ISOPLUS / Javana / Calpico)', 'Cup Beverage (Ale-Ale / Tea Jus / JasJus / Segar Dingin)', 'Kopi & Coklat (Top Coffee / Kopi Neo / Chocodrink)', 'Snack & Keripik (Potabee / Japota / Krisbee)', 'Bumbu & Bahan Dapur (Kecap Sedaap / Minyak Goreng Sedaap)'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk Wings Food', 'field_name' => 'nama_sku_food', 'field_type' => 'text', 'placeholder' => 'Contoh: Mie Sedaap Goreng 90g / Floridina Orange 350ml', 'is_required' => true],
                    ['field_label' => 'Status Ketersediaan Stok Fisik di Toko', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['OOS (Out of Stock / Kosong Total)', 'Under Minimum Stock (Stok Menipis Kritis)'], 'is_required' => true],
                    ['field_label' => 'Minimum Stock Standar Toko (Pcs / Karton)', 'field_name' => 'minimum_stock_qty', 'field_type' => 'number', 'placeholder' => 'Target minimal pajangan rak toko', 'is_required' => true],
                    ['field_label' => 'Actual Stock Fisik Saat Ini (Pcs / Karton)', 'field_name' => 'actual_stock_qty', 'field_type' => 'number', 'placeholder' => 'Jumlah stok fisik tersisa', 'is_required' => true],
                    ['field_label' => 'Alasan Barang Kosong (Jika OOS / Menipis)', 'field_name' => 'alasan_oos_food', 'field_type' => 'dropdown', 'options' => ['PO Belum Diterbitkan Buyer Toko', 'Stok Gudang Depo Wings / Distributor Kosong', 'Pengiriman Pending / Terlambat Datang', 'Barang Rusak / Bad Stock Belum Diganti', 'SKU Tidak Terdaftar di Master Toko Ini'], 'is_required' => false],
                    ['field_label' => 'Catatan Tindak Lanjut Pemesanan & Permintaan Toko', 'field_name' => 'catatan_stok', 'field_type' => 'textarea', 'placeholder' => 'Catatan tindak lanjut order atau permintaan pihak toko...', 'is_required' => false],
                ]
            ],

            // 2. OOS Wings Care & Lion Wings (Home & Personal Care)
            [
                'code' => 'RPT-WINGS-OOS-CARE-01',
                'title' => 'Laporan Cek Stok & OOS Wings Care & Lion Wings (Home & Personal Care)',
                'description' => 'Monitoring ketersediaan produk deterjen, sabun cuci, sabun mandi, shampoo, popok bayi, pasta gigi (Daia, SoKlin, Ekonomi, Giv, Nuvo, Ciptadent, Baby Happy, Mama Lemon, Zinc, Posh).',
                'category' => 'stock',
                'icon' => 'sparkles',
                'color' => '#1976D2',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Kategori Produk Care & Non-Food', 'field_name' => 'kategori_care', 'field_type' => 'dropdown', 'options' => ['Fabric Care / Deterjen Bubuk (Daia / SoKlin)', 'Fabric Care / Deterjen Cair & Pelembut (SoKlin Liquid / Royale / Proclin)', 'Dishwashing & Pembersih Rumah (Ekonomi / Mama Lemon / WPC / Super Sol)', 'Personal Wash / Sabun Mandi (Giv / Nuvo / Fres)', 'Hair Care / Shampoo (Zinc / Emeron / Serasoft)', 'Oral Care / Gigi & Mulut (Ciptadent / Kodomo / Systema / Zact)', 'Baby Care & Diapers (Baby Happy Diapers / Kodomo Baby)', 'Body Fragrance & Deodorant (Posh Men / Posh Women / Bellagio)'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk Wings Care / Lion Wings', 'field_name' => 'nama_sku_care', 'field_type' => 'text', 'placeholder' => 'Contoh: Daia Deterjen Bunga 850g / SoKlin Liquid Violet 720ml / Baby Happy Pants L30', 'is_required' => true],
                    ['field_label' => 'Status Ketersediaan Stok Fisik di Toko', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['OOS (Out of Stock / Kosong Total)', 'Under Minimum Stock (Stok Menipis Kritis)'], 'is_required' => true],
                    ['field_label' => 'Minimum Stock Standar Toko (Pcs / Karton)', 'field_name' => 'minimum_stock_qty', 'field_type' => 'number', 'placeholder' => 'Target minimal stok pajangan', 'is_required' => true],
                    ['field_label' => 'Actual Stock Fisik Saat Ini (Pcs / Karton)', 'field_name' => 'actual_stock_qty', 'field_type' => 'number', 'placeholder' => 'Jumlah stok fisik tersisa', 'is_required' => true],
                    ['field_label' => 'Alasan Barang Kosong (Jika OOS / Menipis)', 'field_name' => 'alasan_oos_care', 'field_type' => 'dropdown', 'options' => ['PO Belum Diterbitkan Buyer Toko', 'Stok Gudang Depo Wings / Distributor Kosong', 'Pengiriman Pending / Terlambat Datang', 'Barang Rusak / Retur Belum Diganti', 'SKU Tidak Terdaftar di Master Toko Ini'], 'is_required' => false],
                    ['field_label' => 'Catatan Pemesanan & Permintaan Display', 'field_name' => 'catatan_stok', 'field_type' => 'textarea', 'placeholder' => 'Catatan tindak lanjut stok atau komplain toko...', 'is_required' => false],
                ]
            ],

            // 3. Glico Wings Ice Cream
            [
                'code' => 'RPT-WINGS-GLICO-01',
                'title' => 'Laporan Stok & Freezer Es Krim Glico Wings (Report Glico MBR)',
                'description' => 'Monitoring ketersediaan stok es krim Glico Wings (Waku Waku, J-Cone, Frostbite, Haku Monaka) serta audit suhu dan kebersihan freezer toko.',
                'category' => 'stock',
                'icon' => 'cube',
                'color' => '#00ACC1',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Kategori Es Krim Glico Wings', 'field_name' => 'kategori_ice_cream', 'field_type' => 'dropdown', 'options' => ['Waku Waku (Water Ice & Fun Stick)', 'J-Cone (Cone & Mini Cone)', 'Frostbite (Stick, Cup, Mochi & Sandwich)', 'Haku (Monaka Premium & Cup Tiramisu/Blueberry)'], 'is_required' => true],
                    ['field_label' => 'Nama & Varian SKU Glico Wings', 'field_name' => 'nama_sku_glico', 'field_type' => 'text', 'placeholder' => 'Contoh: Waku Waku Sweet Lychee 60ml / Frostbite Choco Lava Mochi / Haku Monaka Matcha 180ml', 'is_required' => true],
                    ['field_label' => 'Kondisi Suhu & Kebersihan Mesin Freezer', 'field_name' => 'kondisi_freezer_suhu', 'field_type' => 'dropdown', 'options' => ['Sangat Baik (-18°C s/d -22°C / Beku Keras & Kaca Bersih)', 'Kurang Dingin (-10°C s/d -15°C / Es Krim Lembek)', 'Bunga Es Terlalu Tebal (Perlu Defrosting Segera)', 'Freezer Mati / Rusak / Aliran Listrik Padam'], 'is_required' => true],
                    ['field_label' => 'Status Ketersediaan Stok Es Krim di Freezer', 'field_name' => 'status_stok_freezer', 'field_type' => 'radio', 'options' => ['Freezer Penuh & Display Rapi', 'Sebagian SKU Kosong (Perlu Refill)', 'Freezer Kosong Total / Kritis'], 'is_required' => true],
                    ['field_label' => 'Actual Stock Tersedia di Freezer (Pcs)', 'field_name' => 'actual_stock_pcs', 'field_type' => 'number', 'placeholder' => 'Jumlah pcs es krim di freezer', 'is_required' => true],
                    ['field_label' => 'Foto 1: Tampak Depan Freezer Glico & Price Tag', 'field_name' => 'foto_freezer_depan', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: Susunan Es Krim di Dalam Basket Freezer', 'field_name' => 'foto_freezer_dalam', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Performa Penjualan & Kondisi Freezer', 'field_name' => 'catatan_freezer', 'field_type' => 'textarea', 'placeholder' => 'Catatan kendala teknis mesin freezer atau varian paling laris...', 'is_required' => false],
                ]
            ],

            // 4. Expired Food & Indikator Lakban
            [
                'code' => 'RPT-WINGS-EXPIRED-FOOD-01',
                'title' => 'Laporan Expired Date & Indikator Lakban Wings Food',
                'description' => 'Monitoring tanggal kadaluarsa produk makanan & minuman Wings (SOP 3 bulan sebelum ED), pengecekan warna lakban karton (biru tua, merah, hijau, kuning, coklat).',
                'category' => 'expiry',
                'icon' => 'clock',
                'color' => '#D81B60',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Kategori & Standar Umur Simpan Produk', 'field_name' => 'kategori_expired_food', 'field_type' => 'dropdown', 'options' => ['Mie Sedaap (Max 8 Bulan - Lakban Biru Tua)', 'Jas Jus / Tea Jus / Segar Dingin / Chocodrink (Max 1.5 Tahun - Lakban Merah/Hijau/Biru Muda)', 'Ale-Ale / Floridina / Golda / ISOPLUS / Javana (Max 9 Bulan - Lakban Coklat/Kuning)', 'Kecap Sedaap & Minyak Goreng Sedaap (Max 2 Tahun)', 'Potabee / Japota / Krisbee Snack (Max 6-7 Bulan)', 'Top Coffee / Kopi Neo / Top White Coffee (Max 1-2 Tahun)'], 'is_required' => true],
                    ['field_label' => 'Nama & SKU Produk Wings Food', 'field_name' => 'nama_sku_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Mie Sedaap Kari Ayam / Floridina Orange 350ml', 'is_required' => true],
                    ['field_label' => 'Bulan & Tahun Expired Terdekat di Kemasan Fisik', 'field_name' => 'tanggal_expired_kemasan', 'field_type' => 'month_year', 'is_required' => true],
                    ['field_label' => 'Warna Lakban pada Karton Produksi', 'field_name' => 'warna_lakban_karton', 'field_type' => 'dropdown', 'options' => ['Biru Tua (Standar Mie Sedaap)', 'Kuning (Standar Floridina / RTD)', 'Coklat (Standar Ale-Ale Cup)', 'Merah (Standar Jas Jus)', 'Hijau (Standar Segar Dingin)', 'Biru Muda (Standar Tea Jus)', 'Lakban Standar Pabrik Lainnya'], 'is_required' => true],
                    ['field_label' => 'Jumlah Stok Mendekati ED: Kemasan Karton / Dus', 'field_name' => 'qty_karton_near_ed', 'field_type' => 'number', 'placeholder' => 'Jumlah karton (0 jika tidak ada)', 'is_required' => true],
                    ['field_label' => 'Jumlah Stok Mendekati ED: Kemasan Pcs / Sachet / Botol', 'field_name' => 'qty_pcs_near_ed', 'field_type' => 'number', 'placeholder' => 'Jumlah pcs (0 jika tidak ada)', 'is_required' => true],
                    ['field_label' => 'Status Selisih Waktu Menuju Kadaluarsa', 'field_name' => 'status_selisih_ed', 'field_type' => 'radio', 'options' => ['Kritis (< 1 Bulan Menuju ED)', 'Near Expired (1 - 3 Bulan Menuju ED)', 'Waspada (3 - 6 Bulan Menuju ED)', 'Aman (> 6 Bulan Menuju ED)'], 'is_required' => true],
                    ['field_label' => 'Rekomendasi Tindakan & Kesepakatan Toko', 'field_name' => 'rekomendasi_tindakan', 'field_type' => 'dropdown', 'options' => ['Aman Dibiarkan di Rak Display (FIFO / FEFO)', 'Pajang di Barisan Terdepan Rak (Push Sell Out)', 'Ajukan Program Promo Diskon Clearance / Mailer', 'Proses Retur / Tukar Guling ke Gudang Depo Wings', 'Sudah Ditarik dari Rak Display Toko'], 'is_required' => true],
                    ['field_label' => 'Foto Batch Number, Tanggal Expired & Lakban Karton', 'field_name' => 'foto_expired_date', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Koordinasi dengan Kepala Toko / MD', 'field_name' => 'catatan_expired', 'field_type' => 'textarea', 'placeholder' => 'Catatan kesepakatan retur atau push penjualan produk...', 'is_required' => false],
                ]
            ],

            // 5. Promo Kompetitor
            [
                'code' => 'RPT-WINGS-PROMO-COMP-01',
                'title' => 'Laporan Aktivitas & Promo Kompetitor Wings (Food & Care)',
                'description' => 'Pencatatan promo pesaing (Indofood/Indomie, Unilever/Rinso/Sunlight, Mayora/Torabika, Kao/Attack, Lion/P&G) di outlet modern trade.',
                'category' => 'competitor',
                'icon' => 'eye',
                'color' => '#7B1FA2',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Divisi Produk yang Bersaing', 'field_name' => 'divisi_kompetitor', 'field_type' => 'radio', 'options' => ['Wings Food Pesaing (Food & Beverage / Snack / Kopi)', 'Wings Care & Lion Wings Pesaing (Deterjen / Sabun / Shampoo / Diapers)'], 'is_required' => true],
                    ['field_label' => 'Nama Perusahaan / Brand Kompetitor Utama', 'field_name' => 'brand_kompetitor', 'field_type' => 'dropdown', 'options' => ['INDOFOOD (Indomie, Pop Mie, Club, Ichi Ocha)', 'UNILEVER (Rinso, Sunlight, Molto, Lifebuoy, Pepsodent, Clear, Bango)', 'MAYORA (Torabika, Le Minerale, Teh Pucuk, Bakmi Mewah)', 'KAO INDONESIA (Attack, Biore, Laurier)', 'P&G INDONESIA (Downy, Pantene, Head & Shoulders, Pampers)', 'MAMASUKA / SASA / AJINOMOTO', 'SWEETY / MAMYPOKO (Diapers Pesaing)', 'LAINNYA'], 'is_required' => true],
                    ['field_label' => 'Nama SKU & Varian Produk Kompetitor', 'field_name' => 'sku_produk_kompetitor', 'field_type' => 'text', 'placeholder' => 'Contoh: Rinso Matic Liquid 750ml / Indomie Goreng Spesial 85g / Torabika Cappuccino', 'is_required' => true],
                    ['field_label' => 'Ukuran Kemasan / Gramatur (Gram / ML)', 'field_name' => 'ukuran_kemasan', 'field_type' => 'text', 'placeholder' => 'Contoh: 85g / 750ml / Pouch 1.5L / Bag 800g', 'is_required' => true],
                    ['field_label' => 'Tipe Program Promo Kompetitor', 'field_name' => 'tipe_promo_kompetitor', 'field_type' => 'dropdown', 'options' => ['Diskon Harga Langsung (Price Cut / Rafaksi)', 'Beli X Gratis Y (Buy 1 Get 1 / Beli 5 Gratis 1)', 'Gimmick Hadiah Pembelian (Mangkok, Piring, Tas Belanja)', 'Mailer Koran / Buku Katalog Toko', 'Banded Pack / Twin Pack Khusus', 'Voucher Cashback / Poin Toko'], 'is_required' => true],
                    ['field_label' => 'Deskripsi Mekanisme Promo Kompetitor', 'field_name' => 'mekanisme_promo', 'field_type' => 'text', 'placeholder' => 'Contoh: Beli 2 Rinso Liquid Diskon 25% / Beli 5 Indomie Gratis 1 Pop Mie Mini', 'is_required' => true],
                    ['field_label' => 'Periode Promo Kompetitor Berlangsung', 'field_name' => 'periode_promo_info', 'field_type' => 'text', 'placeholder' => 'Contoh: 1 Agustus s/d 15 Agustus 2026', 'is_required' => true],
                    ['field_label' => 'Perbandingan Harga (Normal vs Promo)', 'field_name' => 'perbandingan_harga_kompetitor', 'field_type' => 'text', 'placeholder' => 'Contoh: Normal Rp 24.500 -> Promo Rp 18.900', 'is_required' => true],
                    ['field_label' => 'Display Tambahan yang Digunakan Kompetitor', 'field_name' => 'display_kompetitor', 'field_type' => 'dropdown', 'options' => ['Sewa Endcap Gondola Depan', 'Floor Island Display Standee', 'Wing Stage / Side Rack', 'Hanging Sachet Area Kasir', 'Hanya di Rak Reguler Saja'], 'is_required' => true],
                    ['field_label' => 'Foto Dokumentasi Promo Kompetitor di Toko', 'field_name' => 'foto_promo_kompetitor', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Dampak terhadap Penjualan Wings', 'field_name' => 'catatan_dampak_kompetitor', 'field_type' => 'textarea', 'placeholder' => 'Catatan seberapa besar pengaruh promo pesaing terhadap perputaran produk Wings...', 'is_required' => false],
                ]
            ],

            // 6. Share of Display (SOS)
            [
                'code' => 'RPT-WINGS-SHARE-DISPLAY-01',
                'title' => 'Laporan Share of Display (SOS) Wings vs Kompetitor',
                'description' => 'Audit persentase pangsa rak pajangan (Share of Shelf / Display) 10 kategori wajib Wings di Modern Trade (MTI & MTKA).',
                'category' => 'display',
                'icon' => 'chart-bar',
                'color' => '#388E3C',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Tipe Channel & Klasifikasi Toko', 'field_name' => 'tipe_channel_outlet', 'field_type' => 'radio', 'options' => ['MTI (Modern Trade Independent / Local Supermarket)', 'MTKA (Modern Trade Key Account / Hypermarket)', 'Kemitraan (Outlet Mitra Resmi)', 'Non Kemitraan'], 'is_required' => true],
                    ['field_label' => 'Kategori Wajib yang Diaudit (10 Kategori Wings)', 'field_name' => 'kategori_wajib_sos', 'field_type' => 'dropdown', 'options' => ['Powder Detergent (Deterjen Bubuk Daia & SoKlin)', 'Liquid Detergent (Deterjen Cair SoKlin Liquid / Daia)', 'Dishwashing Liquid (Sabun Cuci Piring Ekonomi & Mama Lemon)', 'Fabric Care (Pelembut & Pelicin Royale / SoKlin Softener)', 'Bar Soap (Sabun Batang Giv & Nuvo)', 'Liquid Soap (Sabun Mandi Cair Giv & Nuvo)', 'Baby Diapers (Popok Bayi Baby Happy)', 'Kopi (Top Coffee / Kopi Neo)', 'Mie Pack (Mie Sedaap Bungkus / Eko Mie)', 'Mie Cup (Mie Sedaap Cup)'], 'is_required' => true],
                    ['field_label' => 'Jumlah Baris / Tiers Rak Dikuasai Wings (Pcs / Facing)', 'field_name' => 'jumlah_rak_wings_actual', 'field_type' => 'number', 'placeholder' => 'Jumlah baris/facing produk Wings', 'is_required' => true],
                    ['field_label' => 'Total Baris / Tiers Rak Seluruh Brand Kategori Ini', 'field_name' => 'jumlah_rak_total_kategori', 'field_type' => 'number', 'placeholder' => 'Total facing Wings + Seluruh Kompetitor', 'is_required' => true],
                    ['field_label' => 'Target Share of Display Toko (%)', 'field_name' => 'target_sos_persen', 'field_type' => 'number', 'placeholder' => 'Target % SOS dari prinsiple (contoh: 40%)', 'is_required' => true],
                    ['field_label' => 'Actual Share of Display Terhitung (%)', 'field_name' => 'actual_sos_persen', 'field_type' => 'number', 'placeholder' => 'Persentase penguasaan rak actual', 'is_required' => true],
                    ['field_label' => 'Status Pencapaian Target Share of Shelf (SOS)', 'field_name' => 'status_target_sos', 'field_type' => 'radio', 'options' => ['Mencapai Target (Actual SOS >= Target)', 'Belum Mencapai Target (Actual SOS < Target)'], 'is_required' => true],
                    ['field_label' => 'Foto Tampak Depan Full Gondola Rak Kategori', 'field_name' => 'foto_full_rak_sos', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Hambatan & Kesepakatan Rak dengan Buyer Toko', 'field_name' => 'catatan_sos', 'field_type' => 'textarea', 'placeholder' => 'Catatan negosiasi penambahan facing atau perubahan susunan rak...', 'is_required' => false],
                ]
            ],

            // 7. Additional Display & Endcap
            [
                'code' => 'RPT-WINGS-ADD-DISPLAY-01',
                'title' => 'Laporan Additional Display & Sewa Endcap Wings',
                'description' => 'Monitoring penempatan display sekunder, sewa endcap gondola, floor display island, wing stage, dan hanging sachet Wings di toko.',
                'category' => 'display',
                'icon' => 'squares-plus',
                'color' => '#F57C00',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'fields' => [
                    ['field_label' => 'Divisi Produk Wings', 'field_name' => 'divisi_wings', 'field_type' => 'radio', 'options' => ['Wings Food (Food & Beverage / Snack)', 'Wings Care & Lion Wings (Home & Personal Care)', 'Glico Wings (Ice Cream Freezer Display)'], 'is_required' => true],
                    ['field_label' => 'Brand Utama yang Didisplay', 'field_name' => 'brand_display', 'field_type' => 'dropdown', 'options' => ['Mie Sedaap', 'Floridina / Golda / ISOPLUS', 'Top Coffee', 'Daia / SoKlin', 'Ekonomi / Mama Lemon', 'Giv / Nuvo', 'Baby Happy Diapers', 'Glico Wings Ice Cream', 'Mix Brand Wings'], 'is_required' => true],
                    ['field_label' => 'Tipe Additional Display', 'field_name' => 'tipe_display', 'field_type' => 'dropdown', 'options' => ['Sewa Endcap Gondola Depan (Paid TG)', 'Floor Display / Island Standee', 'Wing Stage / Side Rack', 'Hanging Sachet Kasir / Clip Strip', 'Chiller / Freezer Dedicated', 'Table Top Kasir'], 'is_required' => true],
                    ['field_label' => 'Status Biaya & Kontrak Display', 'field_name' => 'status_kontrak', 'field_type' => 'radio', 'options' => ['Sewa Berbayar (Kontrak Resmi Paid)', 'Display Tambahan Gratis (Free Placement Toko)', 'Bonus Target Penjualan Toko'], 'is_required' => true],
                    ['field_label' => 'Status Realisasi Display di Toko', 'field_name' => 'status_realisasi', 'field_type' => 'radio', 'options' => ['Terpasang Rapi & Terisi Penuh Produk Wings', 'Produk Menipis / Perlu Refill Segera', 'Terhalang / Tercampur Produk Kompetitor', 'Belum Terpasang (Menunggu Approval Toko)'], 'is_required' => true],
                    ['field_label' => 'Lokasi / Posisi Titik Display di Toko', 'field_name' => 'lokasi_display_toko', 'field_type' => 'text', 'placeholder' => 'Contoh: Endcap Lorong 3 Depan Kasir / Island Depan Pintu Masuk', 'is_required' => true],
                    ['field_label' => 'Foto 1: Tampak Depan Full Additional Display', 'field_name' => 'foto_display_depan', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Foto 2: Tampak Samping & Suasana Sekitar Rak', 'field_name' => 'foto_display_samping', 'field_type' => 'camera_photo', 'is_required' => false],
                    ['field_label' => 'Catatan Kebersihan, Stok Refill & Kesepakatan Toko', 'field_name' => 'catatan_display', 'field_type' => 'textarea', 'placeholder' => 'Catatan kondisi display, kebutuhan restock, atau perpanjangan kontrak...', 'is_required' => false],
                ]
            ],
        ];

        $hasIconCol = \Illuminate\Support\Facades\Schema::hasColumn('report_templates', 'icon');
        $hasColorCol = \Illuminate\Support\Facades\Schema::hasColumn('report_templates', 'color');

        foreach ($templatesData as $tpl) {
            $fields = $tpl['fields'];
            unset($tpl['fields']);

            if (!$hasIconCol) {
                unset($tpl['icon']);
            }
            if (!$hasColorCol) {
                unset($tpl['color']);
            }

            $template = ReportTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                array_merge($tpl, ['principal_id' => $primaryWings->id])
            );

            // Sync seluruh id principal wings yang matching
            $template->principals()->sync($allWingsIds);

            foreach ($fields as $index => $field) {
                ReportFormField::updateOrCreate(
                    ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                    array_merge($field, ['order_index' => $index + 1])
                );
            }
        }
    }
}


