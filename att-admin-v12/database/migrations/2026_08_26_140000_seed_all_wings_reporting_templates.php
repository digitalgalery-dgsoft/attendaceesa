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
        // 1. Temukan / Buat Principal WINGS SURYA / LION WINGS
        $wingsPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS%')
              ->orWhere('name', 'LIKE', '%SURYA%')
              ->orWhere('name', 'LIKE', '%LION WINGS%')
              ->orWhere('name', 'LIKE', '%SAYAP MAS%')
              ->orWhere('code', 'LIKE', '%WINGS%')
              ->orWhere('code', 'LIKE', '%LION%')
              ->orWhere('subdomain', 'LIKE', '%wings%');
        })->get();

        if ($wingsPrincipals->isEmpty()) {
            $primaryWings = Principal::firstOrCreate(
                ['code' => 'PR-WINGS-SURYA'],
                [
                    'name' => 'PT WINGS SURYA (WINGS GROUP & LION WINGS)',
                    'subdomain' => 'wings',
                    'theme_color' => '#D32F2F',
                    'portal_title' => 'Portal Pelaporan & Monitoring Wings Surya & Lion Wings',
                    'is_active' => true,
                ]
            );
            $wingsPrincipals = collect([$primaryWings]);
        } else {
            foreach ($wingsPrincipals as $wp) {
                $wp->update([
                    'subdomain' => 'wings',
                    'theme_color' => '#D32F2F',
                    'portal_title' => 'Portal Pelaporan & Monitoring Wings Surya & Lion Wings',
                    'is_active' => true,
                ]);
            }
        }

        $primaryWings = $wingsPrincipals->first();
        $allWingsIds = $wingsPrincipals->pluck('id')->toArray();

        // 2. Daftar 7 Template Laporan Resmi Wings Surya & Lion Wings
        $templatesData = [
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
                    ['field_label' => 'Status Ketersediaan Stok Fisik di Toko', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['In Stock (Stok Aman di Rak & Gudang)', 'OOS (Out of Stock / Kosong Total)', 'Under Minimum Stock (Stok Menipis Kritis)'], 'is_required' => true],
                    ['field_label' => 'Minimum Stock Standar Toko (Pcs / Karton)', 'field_name' => 'minimum_stock_qty', 'field_type' => 'number', 'placeholder' => 'Target minimal pajangan rak toko', 'is_required' => true],
                    ['field_label' => 'Actual Stock Fisik Saat Ini (Pcs / Karton)', 'field_name' => 'actual_stock_qty', 'field_type' => 'number', 'placeholder' => 'Jumlah stok fisik tersisa', 'is_required' => true],
                    ['field_label' => 'Alasan Barang Kosong (Jika OOS / Menipis)', 'field_name' => 'alasan_oos_food', 'field_type' => 'dropdown', 'options' => ['PO Belum Diterbitkan Buyer Toko', 'Stok Gudang Depo Wings / Distributor Kosong', 'Pengiriman Pending / Terlambat Datang', 'Barang Rusak / Bad Stock Belum Diganti', 'SKU Tidak Terdaftar di Master Toko Ini'], 'is_required' => false],
                    ['field_label' => 'Estimasi Tanggal PO / Kedatangan Barang Baru', 'field_name' => 'estimasi_po_date', 'field_type' => 'date', 'is_required' => false],
                    ['field_label' => 'Nama PIC Toko / Staff Order yang Dihubungi', 'field_name' => 'nama_pic_toko', 'field_type' => 'text', 'placeholder' => 'Nama kepala seksi / PIC order toko', 'is_required' => false],
                    ['field_label' => 'Foto Rak Display / Gudang Penyimpanan Makanan', 'field_name' => 'foto_rak_stok', 'field_type' => 'camera_photo', 'is_required' => true],
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
                    ['field_label' => 'Status Ketersediaan Stok Fisik di Toko', 'field_name' => 'status_ketersediaan_stok', 'field_type' => 'radio', 'options' => ['In Stock (Stok Aman di Rak & Gudang)', 'OOS (Out of Stock / Kosong Total)', 'Under Minimum Stock (Stok Menipis Kritis)'], 'is_required' => true],
                    ['field_label' => 'Minimum Stock Standar Toko (Pcs / Karton)', 'field_name' => 'minimum_stock_qty', 'field_type' => 'number', 'placeholder' => 'Target minimal stok pajangan', 'is_required' => true],
                    ['field_label' => 'Actual Stock Fisik Saat Ini (Pcs / Karton)', 'field_name' => 'actual_stock_qty', 'field_type' => 'number', 'placeholder' => 'Jumlah stok fisik tersisa', 'is_required' => true],
                    ['field_label' => 'Alasan Barang Kosong (Jika OOS / Menipis)', 'field_name' => 'alasan_oos_care', 'field_type' => 'dropdown', 'options' => ['PO Belum Diterbitkan Buyer Toko', 'Stok Gudang Depo Wings / Distributor Kosong', 'Pengiriman Pending / Terlambat Datang', 'Barang Rusak / Retur Belum Diganti', 'SKU Tidak Terdaftar di Master Toko Ini'], 'is_required' => false],
                    ['field_label' => 'Estimasi Tanggal PO / Kedatangan Barang Baru', 'field_name' => 'estimasi_po_date', 'field_type' => 'date', 'is_required' => false],
                    ['field_label' => 'Nama PIC Toko / MD yang Dihubungi', 'field_name' => 'nama_pic_toko', 'field_type' => 'text', 'placeholder' => 'Nama staff / buyer toko', 'is_required' => false],
                    ['field_label' => 'Foto Rak Display / Gudang Non-Food', 'field_name' => 'foto_rak_stok', 'field_type' => 'camera_photo', 'is_required' => true],
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
                    ['field_label' => 'Tanggal Expired Terdekat di Kemasan Fisik', 'field_name' => 'tanggal_expired_kemasan', 'field_type' => 'date', 'is_required' => true],
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

        $hasIconCol = Schema::hasColumn('report_templates', 'icon');
        $hasColorCol = Schema::hasColumn('report_templates', 'color');

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
