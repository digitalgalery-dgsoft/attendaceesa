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
        $fonterra = Principal::where('name', 'LIKE', '%FONTERRA%')
            ->orWhere('code', 'LIKE', '%FONTERRA%')
            ->first();

        if ($fonterra) {
            $this->seedFonterraTemplates($fonterra);
        }

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

    private function seedFonterraTemplates(Principal $principal): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-FONTERRA-POSM-PRICE-01'],
            [
                'principal_id' => $principal->id,
                'title' => 'Laporan POSM & Price Tag Tracking Fonterra',
                'description' => 'Formulir monitoring pemasangan POSM, sticker kemasan, dan validasi harga promo susu Fonterra (Anlene, Boneeto, Anchor).',
                'category' => 'posm',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
            ]
        );

        $template->principals()->sync([$principal->id]);

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
