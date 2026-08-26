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
        // 1. Temukan / Buat Principal DAESANG (MAMASUKA / MIWON)
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
                    'theme_color' => '#E53935',
                    'portal_title' => 'Portal Pelaporan & Monitoring Daesang (MamaSuka & Miwon)',
                    'is_active' => true,
                ]
            );
            $mamasukaPrincipals = collect([$primaryMamasuka]);
        } else {
            foreach ($mamasukaPrincipals as $mp) {
                $mp->update([
                    'subdomain' => 'mamasuka',
                    'theme_color' => '#E53935',
                    'portal_title' => 'Portal Pelaporan & Monitoring Daesang (MamaSuka & Miwon)',
                    'is_active' => true,
                ]);
            }
        }

        $primaryMamasuka = $mamasukaPrincipals->first();
        $allMamasukaIds = $mamasukaPrincipals->pluck('id')->toArray();

        // 2. Daftar 9 Formulir Template Reporting Mamasuka Sesuai Raw Data Excel & PPT
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
                    ['field_label' => 'Tanggal Expired Terdekat di Kemasan', 'field_name' => 'tanggal_expired_kemasan', 'field_type' => 'date', 'is_required' => true],
                    ['field_label' => 'Jumlah Stok Fisik dengan ED Tersebut (Pcs)', 'field_name' => 'qty_stok_ed', 'field_type' => 'number', 'placeholder' => 'Jumlah pcs', 'is_required' => true],
                    ['field_label' => 'Status Selisih Waktu Menuju Expired', 'field_name' => 'status_selisih_ed', 'field_type' => 'radio', 'options' => ['Kritis (< 1 Bulan Menuju ED)', 'Near Expired (1 - 3 Bulan Menuju ED)', 'Waspada (3 - 6 Bulan Menuju ED)', 'Aman (> 6 Bulan Menuju ED)'], 'is_required' => true],
                    ['field_label' => 'Rekomendasi Tindakan Penanganan', 'field_name' => 'rekomendasi_tindakan', 'field_type' => 'dropdown', 'options' => ['Aman Dibiarkan di Rak Display', 'Ajukan Program Promo Diskon Clearance', 'Proses Retur / Tukar Guling ke Distributor', 'Sudah Ditarik dari Rak Display Toko'], 'is_required' => true],
                    ['field_label' => 'Foto Batch Number & Tanggal Expired di Kemasan', 'field_name' => 'foto_expired_date', 'field_type' => 'camera_photo', 'is_required' => true],
                    ['field_label' => 'Catatan Koordinasi dengan Kepala Toko / MD', 'field_name' => 'catatan_expired', 'field_type' => 'textarea', 'placeholder' => 'Catatan tindak lanjut dengan pihak toko...', 'is_required' => false],
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or cleanup if necessary
    }
};
