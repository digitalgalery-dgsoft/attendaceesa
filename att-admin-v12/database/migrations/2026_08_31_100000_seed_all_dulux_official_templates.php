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

        // 1. Tinter Report LSO
        $this->seedTinterLso($primaryDulux, $allDuluxIds);

        // 2. CBP / New Pricing Report
        $this->seedCbpPricing($primaryDulux, $allDuluxIds);

        // 3. New Offtake Report
        $this->seedOfftake($primaryDulux, $allDuluxIds);

        // 4. Stock End Report (Stock Opname Bulanan)
        $this->seedStockEnd($primaryDulux, $allDuluxIds);

        // 5. Out of Stock (OOS) SSO Report (Hari Sabtu)
        $this->seedOosSso($primaryDulux, $allDuluxIds);

        // 6. Out of Stock (OOS) LSO Report
        $this->seedOosLso($primaryDulux, $allDuluxIds);

        // 7. Data Pelanggan Dulux
        $this->seedDataPelanggan($primaryDulux, $allDuluxIds);

        // 8. Trafik Pembeli Toko
        $this->seedTrafikPembeli($primaryDulux, $allDuluxIds);

        // 9. Registrasi New MD (Mitra Dulux)
        $this->seedRegistrasiMitra($primaryDulux, $allDuluxIds);

        // 10. Daily Maintenance POST Mesin Tinting
        $this->seedDailyMaintenance($primaryDulux, $allDuluxIds);
    }

    private function seedTinterLso(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-TINTER-LSO'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Tinter & Pasta Warna LSO Dulux',
                'description' => 'Pencatatan mutasi dan ketersediaan stok pasta pewarna / tinter mesin tinting di modern store (Ace Hardware, Depo Bangunan, Mitra 10, dll).',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Kategori / Akun Modern Trade (LSO)', 'field_name' => 'account_lso', 'field_type' => 'dropdown', 'options' => ['Ace Hardware', 'Depo Bangunan', 'Mitra 10', 'Mega Depo Bangunan', 'Modern Trade Lainnya'], 'is_required' => true],
            ['field_label' => 'Tipe Tinter / Warna Pasta Pewarna', 'field_name' => 'tipe_tinter_warna', 'field_type' => 'dropdown', 'options' => ['White (W1)', 'Black (B1)', 'Yellow Oxide (Y1)', 'Red Oxide (R1)', 'Organic Yellow (Y2)', 'Organic Red (R2)', 'Blue (BL)', 'Green (GR)', 'Magenta (MG)', 'Orange (OR)', 'Violet (VT)', 'Semua Warna / Full Set'], 'is_required' => true],
            ['field_label' => 'Kuantiti / Jumlah Kaleng Tinta Tinter', 'field_name' => 'qty_kaleng_tinta', 'field_type' => 'number', 'placeholder' => 'Jumlah kaleng tinter', 'is_required' => true],
            ['field_label' => 'Status Ketersediaan Tinter di Toko', 'field_name' => 'status_ketersediaan_tinter', 'field_type' => 'radio', 'options' => ['Stok Aman (Siap Oplos)', 'Stok Menipis (Perlu Order Ulang)', 'Stok Habis (Mesin Tidak Bisa Oplos)'], 'is_required' => true],
            ['field_label' => 'Foto Stok Tinter & Mesin Oplos LSO', 'field_name' => 'foto_stok_tinter', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Catatan / Keterangan Tambahan Tinter', 'field_name' => 'catatan_tinter', 'field_type' => 'textarea', 'placeholder' => 'Keterangan status tinter atau request restock...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedCbpPricing(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-CBP-PRICING'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan CBP (Consumer Buying Price) & Cek Harga Dulux',
                'description' => 'Monitoring harga beli konsumen (CBP) produk Dulux, Catylac, serta harga brand kompetitor di toko (Maksimal diinput tgl 22 tiap bulan).',
                'category' => 'pricing',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Pilih Produk Dulux yang Dicek Harganya', 'field_name' => 'produk_dulux_cbp', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Kemasan Produk', 'field_name' => 'kemasan_produk', 'field_type' => 'dropdown', 'options' => ['1 Liter / 1 Kg (Small Tin)', '2.5 Liter / 4 Kg / 5 Kg (Galon)', '20 Liter / 25 Kg (Pail Besar)'], 'is_required' => true],
            ['field_label' => 'Harga Jual Toko ke Konsumen (CBP Dulux Rp)', 'field_name' => 'harga_cbp_dulux_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
            ['field_label' => 'Harga Jual Kompetitor Sejenis: JOTUN (Rp)', 'field_name' => 'harga_kompetitor_jotun_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Harga Jual Kompetitor Sejenis: NIPPON PAINT (Rp)', 'field_name' => 'harga_kompetitor_nippon_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Harga Jual Kompetitor Sejenis: AVIAN / NO DROP / LENKOTE (Rp)', 'field_name' => 'harga_kompetitor_avian_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Harga Jual Kompetitor Sejenis: MOWILEX / LAINNYA (Rp)', 'field_name' => 'harga_kompetitor_mowilex_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
            ['field_label' => 'Program Promo Harga / Diskon Toko yang Berlaku', 'field_name' => 'program_promo_toko', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan promo cashback, potongan harga toko, atau bundling...', 'is_required' => false],
            ['field_label' => 'Foto Price Tag / Label Harga di Rak Toko', 'field_name' => 'foto_price_tag', 'field_type' => 'camera_photo', 'is_required' => true],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedOfftake(Principal $primaryDulux, array $allDuluxIds): void
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
            ['field_label' => 'Pilih Produk Terjual (Dulux / Catylac)', 'field_name' => 'produk_terjual', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Kemasan Galon (Liter/Kg)', 'field_name' => 'kemasan_galon', 'field_type' => 'dropdown', 'options' => ['0.25 Liter', '0.75 Liter', '0.8 Liter', '0.9 Liter', '1 Liter', '2.5 Liter', '4 Kg', '5 Kg', 'Tidak Ada Galon'], 'is_required' => true],
            ['field_label' => 'Jumlah Galon Terjual (Qty)', 'field_name' => 'qty_galon', 'field_type' => 'number', 'placeholder' => '0', 'is_required' => false],
            ['field_label' => 'Kemasan Pail (Liter/Kg)', 'field_name' => 'kemasan_pail', 'field_type' => 'dropdown', 'options' => ['18.5 Liter', '20 Liter', '21 Liter', '22 Liter', '25 Kg', 'Tidak Ada Pail'], 'is_required' => true],
            ['field_label' => 'Jumlah Pail Terjual (Qty)', 'field_name' => 'qty_pail', 'field_type' => 'number', 'placeholder' => '0', 'is_required' => false],
            ['field_label' => 'Estimasi Total Volume Penjualan (Liter)', 'field_name' => 'total_volume_liter', 'field_type' => 'number', 'placeholder' => 'Total liter terjual', 'is_required' => true],
            ['field_label' => 'Total Nilai Penjualan (Rupiah)', 'field_name' => 'total_nilai_sales_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
            ['field_label' => 'Tipe Pembeli / Customer', 'field_name' => 'tipe_customer', 'field_type' => 'radio', 'options' => ['End User (Pemilik Rumah Langsung)', 'Tukang Cat / Mandor Bangunan', 'Kontraktor / Aplikator Proyek', 'Mitra Dulux Terdaftar', 'Toko Pengecer / Retailer'], 'is_required' => true],
            ['field_label' => 'Foto Bukti Offtake Card / Nota Penjualan', 'field_name' => 'foto_nota_penjualan', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Foto Nota Khusus Produk Promo (Aquashield / Weathershield / Ambiance / Catylac / PEP / PIP)', 'field_name' => 'foto_nota_promo_khusus', 'field_type' => 'multi_photo', 'is_required' => false],
            ['field_label' => 'Jumlah Customer Datang ke Toko Hari Ini', 'field_name' => 'traffic_customer_datang', 'field_type' => 'number', 'placeholder' => 'Jumlah customer datang', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Membeli Cat', 'field_name' => 'traffic_customer_beli_cat', 'field_type' => 'number', 'placeholder' => 'Jumlah pembeli cat', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Membeli Dulux', 'field_name' => 'traffic_customer_beli_dulux', 'field_type' => 'number', 'placeholder' => 'Jumlah pembeli Dulux', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Ditawari Aquashield', 'field_name' => 'traffic_ditawari_aquashield', 'field_type' => 'number', 'placeholder' => 'Jumlah ditawari Aquashield', 'is_required' => false],
            ['field_label' => 'Jumlah Customer yang Membeli Aquashield', 'field_name' => 'traffic_beli_aquashield', 'field_type' => 'number', 'placeholder' => 'Jumlah beli Aquashield', 'is_required' => false],
            ['field_label' => 'Catatan Penjualan & Program Promo Toko', 'field_name' => 'catatan_penjualan', 'field_type' => 'textarea', 'placeholder' => 'Catatan tambahan transaksi hari ini...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedStockEnd(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-STOCK-END'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Stock End (Stock Opname Bulanan) Dulux',
                'description' => 'Pencatatan sisa stok fisik akhir bulan (Stock End) seluruh SKU Dulux & Catylac di toko (Mulai tgl 20 s/d 28).',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Pilih Produk Dulux / Catylac yang Dicek', 'field_name' => 'produk_stock_end', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Base / Tipe Warna', 'field_name' => 'base_warna', 'field_type' => 'dropdown', 'options' => ['Base A (Putih/Light)', 'Base B (Medium)', 'Base C (Dark)', 'Base D (Clear/Deep)', 'Ready Mix (Warna Jadi Pabrik)', 'Cat Dasar Primer'], 'is_required' => true],
            ['field_label' => 'Stok Fisik Kemasan Galon (Qty)', 'field_name' => 'stok_qty_galon', 'field_type' => 'number', 'placeholder' => 'Jumlah galon', 'is_required' => true],
            ['field_label' => 'Stok Fisik Kemasan Pail (Qty)', 'field_name' => 'stok_qty_pail', 'field_type' => 'number', 'placeholder' => 'Jumlah pail', 'is_required' => true],
            ['field_label' => 'Estimasi Total Volume Stok di Toko (Liter)', 'field_name' => 'total_volume_stok_liter', 'field_type' => 'number', 'placeholder' => 'Total volume liter', 'is_required' => true],
            ['field_label' => 'Status Akses Pengecekan Gudang Toko', 'field_name' => 'status_akses_gudang', 'field_type' => 'radio', 'options' => ['Full Access (Bisa Cek Rak & Gudang Toko Bebas)', 'Half Access (Hanya Cek Rak Depan Toko)', 'No Access (Toko Menolak Cek Fisik / Data Estimasi)'], 'is_required' => true],
            ['field_label' => 'Foto Fisik Rak Display & Tumpukan Stok Gudang', 'field_name' => 'foto_stok_gudang', 'field_type' => 'multi_photo', 'is_required' => true],
            ['field_label' => 'Keterangan / Kendala Stok Toko', 'field_name' => 'keterangan_stok_toko', 'field_type' => 'textarea', 'placeholder' => 'Catatan status stok lambat laku (slow moving) atau kelebihan stok...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedOosSso(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-OOS-SSO'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Out of Stock (OOS) SSO Dulux',
                'description' => 'Pencatatan barang kosong (Out of Stock) di toko Specialist / Traditional (SSO) setiap hari Sabtu.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => ['sabtu'],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Pilih Produk Dulux yang Mengalami OOS / Kosong', 'field_name' => 'produk_oos_sso', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Base / Tipe Warna yang Kosong', 'field_name' => 'base_warna_oos', 'field_type' => 'dropdown', 'options' => ['Base A', 'Base B', 'Base C', 'Base D', 'Ready Mix / Warna Jadi', 'Alkali Primer', 'Semua Base'], 'is_required' => true],
            ['field_label' => 'Kemasan / Size yang Kosong', 'field_name' => 'kemasan_size_oos', 'field_type' => 'dropdown', 'options' => ['Small Tin (1L/1Kg)', 'Galon (2.5L / 4-5Kg)', 'Pail (20L / 25Kg)', 'Semua Kemasan'], 'is_required' => true],
            ['field_label' => 'Lama Kondisi Barang Kosong (Jumlah Hari)', 'field_name' => 'lama_oos_hari', 'field_type' => 'number', 'placeholder' => 'Contoh: 7 (hari)', 'is_required' => true],
            ['field_label' => 'Saran Kuantiti Order ke Toko (Qty Galon/Pail)', 'field_name' => 'saran_qty_order', 'field_type' => 'number', 'placeholder' => 'Saran kuantiti order', 'is_required' => false],
            ['field_label' => 'Penyebab / Alasan Out of Stock (OOS)', 'field_name' => 'alasan_oos_sso', 'field_type' => 'dropdown', 'options' => ['1. Sudah buka PO namun belum ada pengiriman ke toko', '2. Sudah buka PO namun kendala stock di distributor/pabrik', '3. Kendala pembayaran toko (limit kredit / kiriman diblokir)', '4. Barang sedang dalam proses pengiriman ke toko', '5. Toko belum bersedia reorder / menunggu omset'], 'is_required' => true],
            ['field_label' => 'Foto Rak Kosong / Void Display Toko', 'field_name' => 'foto_rak_kosong', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Tindak Lanjut & Rencana Order Toko', 'field_name' => 'tindak_lanjut_oos', 'field_type' => 'textarea', 'placeholder' => 'Kapan toko rencana order kembali atau hasil konfirmasi ke PIC toko...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedOosLso(Principal $primaryDulux, array $allDuluxIds): void
    {
        $template = ReportTemplate::updateOrCreate(
            ['code' => 'RPT-DULUX-OOS-LSO'],
            [
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Out of Stock (OOS) LSO Dulux',
                'description' => 'Pencatatan barang kosong (Out of Stock) di gerai Modern Trade (LSO) setiap libur / 1x seminggu.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Account Modern Trade (LSO)', 'field_name' => 'account_lso_oos', 'field_type' => 'dropdown', 'options' => ['Ace Hardware', 'Depo Bangunan', 'Mitra 10', 'Mega Depo Bangunan', 'BJ Home', 'Mitra 10 Express', 'Lainnya'], 'is_required' => true],
            ['field_label' => 'Pilih Produk Dulux yang Kosong di LSO', 'field_name' => 'produk_oos_lso', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Base / Color yang Kosong', 'field_name' => 'base_color_lso', 'field_type' => 'dropdown', 'options' => ['Base A', 'Base B', 'Base C', 'Base D', 'Ready Mix / Warna Jadi', 'Cat Dasar Primer'], 'is_required' => true],
            ['field_label' => 'Kemasan yang Kosong', 'field_name' => 'kemasan_oos_lso', 'field_type' => 'dropdown', 'options' => ['Tin (1L)', 'Galon (2.5L / 4-5Kg)', 'Pail (20L / 25Kg)', 'Semua Kemasan'], 'is_required' => true],
            ['field_label' => 'Keterangan Lama OOS (Hari)', 'field_name' => 'lama_oos_lso_hari', 'field_type' => 'number', 'placeholder' => 'Jumlah hari barang kosong', 'is_required' => true],
            ['field_label' => 'Alasan Out of Stock (OOS) LSO', 'field_name' => 'alasan_oos_lso', 'field_type' => 'dropdown', 'options' => ['Stok DC / Hub Modern Trade Kosong', 'PO Toko Belum Diterbitkan Buyer', 'Keterlambatan Ekspedisi / Pengiriman Logistik', 'Barang Transit di Gudang Toko Belum Dipajang', 'Kendala Sistem Inventory Toko'], 'is_required' => true],
            ['field_label' => 'Foto Rak Kosong di Modern Store LSO', 'field_name' => 'foto_rak_oos_lso', 'field_type' => 'camera_photo', 'is_required' => true],
            ['field_label' => 'Catatan Koordinasi dengan Merchandiser / Buyer LSO', 'field_name' => 'catatan_koordinasi_lso', 'field_type' => 'textarea', 'placeholder' => 'Hasil follow up dengan PIC toko modern...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedDataPelanggan(Principal $primaryDulux, array $allDuluxIds): void
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
                'version' => 1,
                'report_days' => [],
            ]
        );
        $template->principals()->sync($allDuluxIds);

        $fields = [
            ['field_label' => 'Nama Lengkap Pelanggan', 'field_name' => 'nama_pelanggan', 'field_type' => 'text', 'placeholder' => 'Nama konsumen / pembeli', 'is_required' => true],
            ['field_label' => 'Nomor HP / WhatsApp Pelanggan', 'field_name' => 'no_hp_pelanggan', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx', 'is_required' => true],
            ['field_label' => 'Alamat / Domisili Pelanggan', 'field_name' => 'alamat_pelanggan', 'field_type' => 'text', 'placeholder' => 'Alamat atau lokasi proyek konsumen', 'is_required' => false],
            ['field_label' => 'Tipe / Kategori Pelanggan', 'field_name' => 'tipe_pelanggan', 'field_type' => 'radio', 'options' => ['Pemilik Rumah (End User)', 'Tukang Cat & Bangunan', 'Mandor Proyek', 'Kontraktor / Aplikator', 'Mitra Dulux Terdaftar'], 'is_required' => true],
            ['field_label' => 'Tujuan Datang ke Toko', 'field_name' => 'tujuan_ke_toko', 'field_type' => 'dropdown', 'options' => ['Membeli Cat Dulux / Catylac', 'Membeli Cat Merk Lain', 'Membeli Bahan Bangunan Lainnya', 'Konsultasi / Tanya Warna', 'Komplain Produk'], 'is_required' => true],
            ['field_label' => 'Brand Cat yang Awalnya Dicari / Ditanyakan', 'field_name' => 'brand_dicari', 'field_type' => 'text', 'placeholder' => 'Contoh: Dulux, Jotun, Nippon, No Drop...', 'is_required' => true],
            ['field_label' => 'Brand Cat yang Akhirnya Dibeli', 'field_name' => 'brand_dibeli', 'field_type' => 'dropdown', 'options' => ['DULUX (Pentalite/Weathershield/EasyClean/Ambiance)', 'CATYLAC (Interior/Exterior/Plamur)', 'AQUASHIELD (Pelapis Anti Bocor)', 'JOTUN', 'NIPPON PAINT', 'AVIAN / NO DROP / LENKOTE', 'MOWILEX', 'PROPAN', 'Tidak Jadi Beli Cat'], 'is_required' => true],
            ['field_label' => 'Alasan Konsumen Memilih Brand Tersebut', 'field_name' => 'alasan_pilih_brand', 'field_type' => 'dropdown', 'options' => ['Kualitas dan Daya Tahan Terbukti', 'Merk Terkenal / Rekomendasi Arsitek', 'Rekomendasi SPG / DC Dulux di Toko', 'Rekomendasi Pemilik / Karyawan Toko', 'Harga Lebih Terjangkau / Diskon Promo', 'Warna Sesuai Pilihan (Bisa Oplos)', 'Tukang Cat Terbiasa Pakai Merk Tersebut'], 'is_required' => true],
            ['field_label' => 'Tipe Pekerjaan Pengecatan', 'field_name' => 'tipe_pengecatan', 'field_type' => 'radio', 'options' => ['Pengecatan Rumah Baru (Tembok Baru)', 'Pengecatan Ulang / Renovasi (Repainting)', 'Pengecatan Proyek Komersial / Ruko'], 'is_required' => true],
            ['field_label' => 'Apakah Memerlukan Preview Warna Visualizer?', 'field_name' => 'memerlukan_preview', 'field_type' => 'radio', 'options' => ['Ya (Dibuatkan Visualisasi Warna / Demo)', 'Tidak (Sudah Memiliki Pilihan Warna Pasti)'], 'is_required' => true],
            ['field_label' => 'Total Estimasi Nilai Pembelian (Rupiah)', 'field_name' => 'value_pembelian_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
            ['field_label' => 'Foto Interaksi Konsumen di Toko / Nota Belanja', 'field_name' => 'foto_interaksi_pelanggan', 'field_type' => 'camera_photo', 'is_required' => false],
            ['field_label' => 'Catatan Khusus Pelanggan', 'field_name' => 'catatan_pelanggan', 'field_type' => 'textarea', 'placeholder' => 'Catatan preferensi warna atau rencana belanja lanjutan...', 'is_required' => false],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                ['report_template_id' => $template->id, 'field_name' => $field['field_name']],
                array_merge($field, ['order_index' => $index + 1])
            );
        }
    }

    private function seedTrafikPembeli(Principal $primaryDulux, array $allDuluxIds): void
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
            ['field_label' => 'Tipe Gerai / Toko', 'field_name' => 'tipe_toko', 'field_type' => 'radio', 'options' => ['SSO (Specialist / Traditional Store)', 'LSO (Modern Outlet / Depo Bahan Bangunan)'], 'is_required' => true],
            ['field_label' => 'Jumlah Total Customer Datang ke Toko Hari Ini', 'field_name' => 'jml_customer_datang', 'field_type' => 'number', 'placeholder' => 'Jumlah pengunjung toko', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Membeli Cat (Semua Brand)', 'field_name' => 'jml_customer_beli_cat', 'field_type' => 'number', 'placeholder' => 'Jumlah pembeli cat', 'is_required' => true],
            ['field_label' => 'Jumlah Customer yang Membeli Produk Dulux / Catylac', 'field_name' => 'jml_customer_beli_dulux', 'field_type' => 'number', 'placeholder' => 'Jumlah pembeli Dulux', 'is_required' => true],
            ['field_label' => 'Estimasi Persentase Market Share Dulux di Toko Hari Ini (%)', 'field_name' => 'estimasi_market_share_persen', 'field_type' => 'number', 'placeholder' => 'Contoh: 60 (%)', 'is_required' => false],
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

    private function seedRegistrasiMitra(Principal $primaryDulux, array $allDuluxIds): void
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

    private function seedDailyMaintenance(Principal $primaryDulux, array $allDuluxIds): void
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
            ['field_label' => 'Kategori / Tipe Gerai Toko', 'field_name' => 'kategori_toko_post', 'field_type' => 'radio', 'options' => ['SSO (Specialist Store)', 'LSO (Modern Trade Outlet)', 'MTI (Modern Trade Independent)'], 'is_required' => true],
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = [
            'RPT-DULUX-TINTER-LSO',
            'RPT-DULUX-CBP-PRICING',
            'RPT-DULUX-OFFTAKE-01',
            'RPT-DULUX-STOCK-END',
            'RPT-DULUX-OOS-SSO',
            'RPT-DULUX-OOS-LSO',
            'RPT-DULUX-DATABASE-PELANGGAN',
            'RPT-DULUX-TRAFIK-PEMBELI',
            'RPT-DULUX-REGISTRASI-MITRA',
            'RPT-DULUX-DAILY-MAINTENANCE',
        ];

        $templates = ReportTemplate::whereIn('code', $codes)->get();
        foreach ($templates as $t) {
            $t->fields()->delete();
            $t->principals()->detach();
            $t->delete();
        }
    }
};
