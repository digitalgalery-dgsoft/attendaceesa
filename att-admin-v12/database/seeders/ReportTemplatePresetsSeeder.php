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
                    'portal_title' => 'Portal Pelaporan & Monitoring Dulux (ICI Paints)',
                    'is_active' => true,
                ]);
            }
        }

        $primaryDulux = $duluxPrincipals->first();
        $allDuluxIds = $duluxPrincipals->pluck('id')->toArray();

        // Seed 5 Template Resmi Dulux (ICI Paints)
        $this->seedDuluxOfftakeTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxStockOosTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxMarketShareTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxTintingDisplayTemplate($primaryDulux, $allDuluxIds);
        $this->seedDuluxDatabaseProfileTLTemplate($primaryDulux, $allDuluxIds);

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
                    'theme_color' => '#1E88E5',
                    'portal_title' => 'Portal Pelaporan & Monitoring Fonterra Brands',
                    'is_active' => true,
                ]
            );
            $fonterraPrincipals = collect([$primaryFonterra]);
        } else {
            foreach ($fonterraPrincipals as $fp) {
                $fp->update([
                    'subdomain' => 'fonterra',
                    'theme_color' => '#1E88E5',
                    'portal_title' => 'Portal Pelaporan & Monitoring Fonterra Brands',
                    'is_active' => true,
                ]);
            }
        }

        $primaryFonterra = $fonterraPrincipals->first();
        $allFonterraIds = $fonterraPrincipals->pluck('id')->toArray();

        $this->seedFonterraTemplates($primaryFonterra, $allFonterraIds);

        // 3. Mamasuka
        $mamasuka = Principal::where('name', 'LIKE', '%MAMASUKA%')
            ->orWhere('name', 'LIKE', '%DAESANG%')
            ->orWhere('code', 'LIKE', '%MAMASUKA%')
            ->first();

        if ($mamasuka) {
            $this->seedMamasukaTemplates($mamasuka);
        }
    }

    /**
     * 1. Template Offtake / Penjualan Harian Dulux
     */
    private function seedDuluxOfftakeTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-OFFTAKE-01'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Offtake / Penjualan Harian Dulux',
                'description' => 'Formulir pencatatan penjualan produk Dulux & Catylac harian oleh SPG/SPB/MD di toko.',
                'category' => 'offtake',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Kategori Produk Dulux / Catylac', 'field_name' => 'kategori_produk', 'field_type' => 'dropdown', 'options' => ['Dulux Interior (Pentalite / EasyClean / Ambiance)', 'Dulux Exterior (Weathershield / Powerflexx)', 'Catylac Cat Tembok Interior', 'Catylac Cat Tembok Exterior', 'Dulux Cat Dasar / Primer Alkali Resisting', 'Catylac Cat Dasar / Primer', 'Dulux Aquashield (Cat Pelapis Bocor)', 'Dulux Wood & Metal (Cat Kayu & Besi Gloss/Satin)'], 'is_required' => true],
            ['field_label' => 'SKU / Nama Warna / Kode Warna', 'field_name' => 'sku_warna_produk', 'field_type' => 'text', 'placeholder' => 'Contoh: Dulux Pentalite Brilliant White 20L / 44556', 'is_required' => true],
            ['field_label' => 'Kemasan Produk', 'field_name' => 'kemasan_produk', 'field_type' => 'dropdown', 'options' => ['1 Liter / 1 Kg (Kaleng Kecil)', '2.5 Liter / 4 Kg / 5 Kg (Galon)', '20 Liter / 25 Kg (Pail Besar)'], 'is_required' => true],
            ['field_label' => 'Jumlah Terjual (Galon / Kaleng Kecil)', 'field_name' => 'qty_galon', 'field_type' => 'number', 'placeholder' => 'Jumlah kaleng/galon terjual', 'is_required' => false],
            ['field_label' => 'Jumlah Terjual (Pail 20L / Besar)', 'field_name' => 'qty_pail', 'field_type' => 'number', 'placeholder' => 'Jumlah pail besar terjual', 'is_required' => false],
            ['field_label' => 'Total Nilai Penjualan (Rupiah)', 'field_name' => 'total_nilai_sales_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
            ['field_label' => 'Tipe Pembeli / Customer', 'field_name' => 'tipe_customer', 'field_type' => 'radio', 'options' => ['End User (Pemilik Rumah Langsung)', 'Tukang Cat / Mandor Bangunan', 'Kontraktor / Aplikator Proyek', 'Toko Pengecer / Retailer'], 'is_required' => true],
            ['field_label' => 'Foto Bukti Nota / Struk Penjualan / Surat Jalan', 'field_name' => 'foto_nota_penjualan', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Catatan Penjualan / Program Khusus', 'field_name' => 'catatan_penjualan', 'field_type' => 'textarea', 'placeholder' => 'Catatan tambahan terkait promo toko atau permintaan pembeli...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    /**
     * 2. Template Cek Stok & OOS Dulux
     */
    private function seedDuluxStockOosTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-STOCK-OOS-01'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Cek Stok & OOS (Barang Kosong) Dulux',
                'description' => 'Monitoring ketersediaan stok fisik produk Dulux & Catylac di rak toko serta pencatatan alasan barang kosong.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Kategori Produk yang Dicek', 'field_name' => 'kategori_produk_stok', 'field_type' => 'dropdown', 'options' => ['Dulux Interior', 'Dulux Exterior', 'Catylac Interior', 'Catylac Exterior', 'Cat Dasar Primer', 'Aquashield', 'Wood & Metal'], 'is_required' => true],
            ['field_label' => 'Total Stok Fisik Galon di Toko', 'field_name' => 'total_stok_galon', 'field_type' => 'number', 'placeholder' => 'Jumlah galon', 'is_required' => true],
            ['field_label' => 'Total Stok Fisik Pail di Toko', 'field_name' => 'total_stok_pail', 'field_type' => 'number', 'placeholder' => 'Jumlah pail', 'is_required' => true],
            ['field_label' => 'Status Ketersediaan Stok', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['Stok Aman / Ready Lengkap', 'Stok Menipis (Hampir Habis)', 'Out of Stock / Kosong Total'], 'is_required' => true],
            ['field_label' => 'Penyebab Barang Kosong (Jika OOS)', 'field_name' => 'penyebab_oos', 'field_type' => 'dropdown', 'options' => ['Tidak Ada (Stok Ready)', 'Kosong di Distributor / DC Pusat', 'Keterlambatan Pengiriman PO Toko', 'Toko Over Limit Kredit / Piutang Tertahan', 'SKU Discontinue / Ganti Kemasan'], 'is_required' => true],
            ['field_label' => 'Keterangan Order & Tanggapan PIC Toko', 'field_name' => 'keterangan_order_toko', 'field_type' => 'textarea', 'placeholder' => 'Apakah toko sudah buat PO atau menunggu jatuh tempo...', 'is_required' => false],
            ['field_label' => 'Foto Rak Display & Gudang Stok Toko', 'field_name' => 'foto_gudang_rak_stok', 'field_type' => 'multi_photo', 'is_required' => true],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    /**
     * 3. Template Market Share & Kompetitor Tracking Dulux
     */
    private function seedDuluxMarketShareTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-MARKET-SHARE-01'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Market Share & Kompetitor Dulux',
                'description' => 'Pencatatan estimasi nilai penjualan brand kompetitor cat (Jotun, Nippon, Mowilex, Avian, dll) di toko.',
                'category' => 'competitor',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Total Penjualan DULUX Hari Ini (Rp)', 'field_name' => 'sales_dulux_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
            ['field_label' => 'Estimasi Penjualan JOTUN (Rp)', 'field_name' => 'sales_jotun_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Estimasi Penjualan NIPPON PAINT (Rp)', 'field_name' => 'sales_nippon_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Estimasi Penjualan MOWILEX (Rp)', 'field_name' => 'sales_mowilex_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Estimasi Penjualan AVIAN / LENKOTE / SUNGUARD (Rp)', 'field_name' => 'sales_avian_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Estimasi Penjualan DANAPAINTS / PROPAN / LAINNYA (Rp)', 'field_name' => 'sales_kompetitor_lainnya_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Brand Cat Paling Laku di Toko Hari Ini', 'field_name' => 'brand_terlaris_toko', 'field_type' => 'dropdown', 'options' => ['DULUX / CATYLAC', 'JOTUN', 'NIPPON PAINT', 'MOWILEX', 'AVIAN / LENKOTE', 'PROPAN', 'DANAPAINTS', 'TOA'], 'is_required' => true],
            ['field_label' => 'Alasan Konsumen Memilih Brand Kompetitor', 'field_name' => 'alasan_pilih_kompetitor', 'field_type' => 'dropdown', 'options' => ['Harga Kompetitor Lebih Murah / Promo Diskon', 'Program Hadiah Langsung / Cashback Tukang', 'Tukang Cat Sudah Terbiasa (Fanatik Brand Lain)', 'Stok Dulux di Toko Kosong / Tidak Tersedia', 'Display Kompetitor Lebih Menarik'], 'is_required' => false],
            ['field_label' => 'Info Promo & Program Kompetitor yang Sedang Aktif', 'field_name' => 'info_promo_kompetitor', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan rincian promo kompetitor di toko ini...', 'is_required' => false],
            ['field_label' => 'Foto Display & Materi Promo Kompetitor', 'field_name' => 'foto_promo_kompetitor', 'field_type' => 'camera_photo', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    /**
     * 4. Template Mesin Oplos & Tinting Machine Dulux
     */
    private function seedDuluxTintingDisplayTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-TINTING-DISPLAY-01'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Mesin Tinting & Display Toko Dulux',
                'description' => 'Monitoring kondisi mesin oplos warna (Tinting Machine), stok pasta pewarna (colorant), dan kebersihan display Dulux.',
                'category' => 'display',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Kondisi Mesin Tinting Dulux', 'field_name' => 'kondisi_mesin_tinting', 'field_type' => 'radio', 'options' => ['Berfungsi Normal & Siap Digunakan', 'Nozzle Tersumbat / Butuh Kalibrasi', 'Software / Komputer Tinting Error', 'Mesin Rusak / Mati Total', 'Toko Tidak Memiliki Mesin Tinting'], 'is_required' => true],
            ['field_label' => 'Ketersediaan Stok Pasta Pewarna (Colorant)', 'field_name' => 'stok_pasta_colorant', 'field_type' => 'radio', 'options' => ['Lengkap & Cukup untuk Oplos', 'Ada 1-2 Pasta Pewarna Menipis', 'Pasta Pewarna Utama Habis (Tidak Bisa Oplos)'], 'is_required' => true],
            ['field_label' => 'Jumlah Kaleng Dioplos Hari Ini', 'field_name' => 'jumlah_kaleng_oplos_hari_ini', 'field_type' => 'number', 'placeholder' => 'Jumlah kaleng dioplos', 'is_required' => true],
            ['field_label' => 'Kondisi Stand Kartu Warna & Display Rak', 'field_name' => 'kondisi_display_color_card', 'field_type' => 'radio', 'options' => ['Bersih, Rapi & Kartu Warna Lengkap', 'Kartu Warna Kurang / Banyak Sobek', 'Rak Display Kotor / Tertutup Barang Lain'], 'is_required' => true],
            ['field_label' => 'Foto Mesin Tinting Dulux', 'field_name' => 'foto_mesin_tinting', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Foto Rak Display & Color Card Dulux', 'field_name' => 'foto_display_rak', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Catatan Kebutuhan Maintenance / Form Tambahan', 'field_name' => 'catatan_maintenance', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan kendala teknis atau pasta yang perlu di-restock...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    /**
     * 5. Template Database Profil Toko & Kunjungan Team Leader (TL) Dulux
     */
    private function seedDuluxDatabaseProfileTLTemplate(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-DATABASE-TL-01'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Kunjungan TL & Database Profil Toko Dulux',
                'description' => 'Formulir survey profil toko cat/bangunan, evaluasi performa SPG Dulux, dan kesepakatan program promosi oleh Team Leader.',
                'category' => 'survey',
                'require_gps' => true,
                'require_signature' => true,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Nama Toko / Depo Bangunan', 'field_name' => 'nama_toko_depo', 'field_type' => 'text', 'placeholder' => 'Contoh: TB Sumber Rejeki Cat', 'is_required' => true],
            ['field_label' => 'Tipe Toko / Channel', 'field_name' => 'tipe_toko_channel', 'field_type' => 'dropdown', 'options' => ['Paint Specialist (Toko Khusus Cat)', 'Modern Outlet / Depo Bahan Bangunan', 'Traditional Market (Toko Bahan Bangunan Biasa)', 'Retailer / Toko Grosir'], 'is_required' => true],
            ['field_label' => 'Nama Owner / Kepala Toko (PIC)', 'field_name' => 'nama_owner_pic', 'field_type' => 'text', 'placeholder' => 'Nama PIC yang ditemui', 'is_required' => true],
            ['field_label' => 'Nomor HP / WhatsApp Owner Toko', 'field_name' => 'no_hp_owner', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx', 'is_required' => true],
            ['field_label' => 'Kelas / Potensi Toko Dulux', 'field_name' => 'kelas_potensi_toko', 'field_type' => 'dropdown', 'options' => ['Kelas A (Platinum - Omset > 100 Jt/Bulan)', 'Kelas B (Gold - Omset 50 - 100 Jt/Bulan)', 'Kelas C (Silver - Omset 20 - 50 Jt/Bulan)', 'Kelas D (Bronze - Omset < 20 Jt/Bulan)'], 'is_required' => true],
            ['field_label' => 'Evaluasi Kehadiran & Standby SPG Dulux', 'field_name' => 'evaluasi_kehadiran_spg', 'field_type' => 'radio', 'options' => ['SPG Hadir & Standby di Lokasi', 'SPG Izin / Sakit / Off', 'Toko Belum Ada Penempatan SPG'], 'is_required' => true],
            ['field_label' => 'Evaluasi Kinerja & Pemahaman Produk SPG', 'field_name' => 'evaluasi_kinerja_spg', 'field_type' => 'dropdown', 'options' => ['Sangat Baik (Aktif Menawarkan & Paham Produk)', 'Cukup Baik (Standby & Menjawab Pertanyaan)', 'Perlu Pembinaan / Product Knowledge Training'], 'is_required' => false],
            ['field_label' => 'Catatan Kesepakatan Program & Arahan TL', 'field_name' => 'catatan_arahan_tl', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan hasil diskusi dengan pemilik toko dan instruksi untuk SPG...', 'is_required' => true],
            ['field_label' => 'Foto Pertemuan dengan Owner Toko / Fasad Toko', 'field_name' => 'foto_pertemuan_toko', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Tanda Tangan Digital Owner / PIC Toko', 'field_name' => 'tanda_tangan_owner_toko', 'field_type' => 'signature', 'is_required' => true],
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
                    ['field_label' => 'Tanggal Expired Date Terdekat pada Kemasan', 'field_name' => 'tanggal_expired', 'field_type' => 'date', 'is_required' => true],
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

    private function seedMamasukaTemplates(Principal $principal): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-MAMASUKA-RENT-EXP-01'],
            [
                'principal_id' => $principal->id,
                'title' => 'Laporan Rent Display & Expired Date Mamasuka',
                'description' => 'Pencatatan realisasi sewa display, monitoring kadaluarsa produk bumbu/rumput laut, dan promo kompetitor.',
                'category' => 'display',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync([$principal->id]);

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
