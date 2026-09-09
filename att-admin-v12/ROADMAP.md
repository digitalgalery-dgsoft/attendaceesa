# 🗺️ Project Roadmap & Changelog Sistem Attendance ESA & Portal Principal

Dokumen ini merangkum seluruh progres pekerjaan yang telah diselesaikan, arsitektur yang telah diimplementasikan, serta rencana pengembangan lanjutan untuk sistem **Attendance ESA & Portal Principal**.

---

## 📌 Status Ringkasan Proyek

| Kategori | Status | Keterangan |
| :--- | :---: | :--- |
| **Urutan Pelaporan Dulux (6 Langkah Wajib)** | 🟢 Selesai & Rilis | Urutan: Daily Maint -> Offtake -> OOS -> DB Pelanggan -> Stok End -> CBP |
| **Multi-Mesin Per Toko & Machine Gating** | 🟢 Selesai (100%) | 1 Store bisa 2+ mesin, kunci ke toko check-in, seluruh mesin wajib lapor |
| **Icon Penanda Lokasi AppBar (3 Warna)** | 🟢 Selesai (100%) | Card lokasi dihapus, ganti icon interaktif: Merah (Belum Check-in), Orange (Luar Radius), Hijau (Dalam Radius) |
| **Single-Product Submission & Disable Terlapor** | 🟢 Selesai (100%) | 1 submission per item, disable & tandai produk terlapor hari itu, tombol dinamis |
| **Attendance Gate (Check-Out & Visit-Out)** | 🟢 Selesai (100%) | Blokir check-out / visit-out jika ada laporan, produk, atau mesin belum lengkap |
| **Rilis APK Mobile v1.0.129 (Multi-Mesin & Icon Lokasi)** | 🟢 Rilis & Live | Sinkron ke Staging (appsend) & 3 Node Production (AMK, AKP, ATK) |
| **Tab Data Laporan Masuk & Approval Offtake** | 🟢 Selesai & Live (100%) | Tab live submissions, quick approve/reject modal, sticky action & horizontal scroll |
| **Resolusi Query Live Offtake & Out-of-Memory** | 🟢 Selesai (100%) | Eliminasi silent SQL error & filter batch import, query cepat (<1 detik, memori 65MB) |
| **Multi-Kompetitor Form CBP Mobile (v1.0.124)** | 🟢 Rilis & Live | Input multi-brand kompetitor per toko & rilis APK v1.0.124 |
| **Laporan Offtake Dulux (Sheet 1 & 2, SCM, Pivotable)** | 🟢 Selesai (100%) | 8.289 data transaksi (Jan–Jul 2026), Rp 37+ Miliar, multi-tab & pivot MoM |
| **Laporan Out of Stock / OOS Dulux** | 🟢 Selesai (100%) | 7.671 riwayat OOS 2026, analisis alasan, matriks mingguan W1–W52 |
| **Laporan Daily Maintenance POST & Tinting** | 🟢 Selesai (100%) | 3.842 riwayat cek fisik mesin 324 toko, skor kepatuhan & matriks toko |
| **Laporan Data Pelanggan & Konsumen Dulux** | 🟢 Selesai (100%) | 5.522 konsumen 2025–2026, Rp 37,74 Miliar, analisis switch, WA direct chat |
| **Perombakan Dashboard CBP (Dashboard 1 & 2)** | 🟢 Selesai (100%) | Multi-tab Cat Tembok, Enamel, Waterproofing & Indeks Harga 100% Acuan |
| **Impor Data Historis Offtake 2025** | 🟢 Selesai (100%) | 843.455 baris data (Jan–Des 2025) berhasil dimigrasi |
| **Ekstraksi & Impor Data Offtake 2026** | 🟢 Selesai (100%) | 439.819 baris data (Jan–Jul 2026) berhasil diproses ke JSONL chunks & SQLite |
| **Ekstraksi & Impor Data CBP 2026** | 🟢 Selesai (100%) | 117.012 baris monitoring harga (Jan–Jul 2026) di-stream ke JSONL chunks, SQLite & DB |
| **Ekstraksi & Impor Data Stock End 2026** | 🟢 Selesai (100%) | 85.967 baris data stock fisik & tinter (Jan–Jul 2026, 28,3M L) ke JSONL chunks & DB |
| **Penyesuaian Formulir Pelaporan ICI Paint** | 🟢 Selesai (100%) | CBP, OOS LSO/SSO, Data Pelanggan, Stock End & Tinter |
| **Optimasi Performa & Query Skala Besar** | 🟢 Selesai | Mengakomodasi 14.000+ store & jutaan data submission |
| **UI/UX Loading Screen Layar Tengah** | 🟢 Selesai | Animasi glassmorphism & dual-orbit loader otomatis |
| **AI Biometrik Wajah Mobile (v1.0.119)** | 🟢 Rilis & Live | Rekalibrasi 24 landmarks, threshold 75%, +20.6% separation margin |
| **Resolusi CPU Overload & Looping Cluster** | 🟢 Selesai (100%) | Eliminasi rekursif cURL loop `/storage`, beban server kembali stabil 1-3% |
| **Konfirmasi IP Publik Server 1 AMK** | 🟢 Live di 38.103.170.235 | Klarifikasi IP inbound publik (38.103.170.235) vs NAT egress aaPanel |
| **Pipeline Otomatisasi Deploy Multi-Server** | 🟢 Aktif | CI/CD sync multi-vhost + auto syntax check (`php -l`) |
| **Chart Perubahan Karyawan Aktif Odoo** | 🟢 Selesai & Live | Widget line chart dual-axis volume ~11.130 karyawan & mutasi per jam |
| **Deployment Cluster 3 Node Production** | 🟢 Live (100%) | Sync otomatis ke Staging (appsend) & 3 Node Prod (AMK, AKP, ATK) |

---

## ✅ Riwayat Progres & Pekerjaan yang Telah Diselesaikan

### 1. Sinkronisasi & Penertiban Portal Principal ICI Paint / Dulux
- [x] **Audit Template Form Laporan**: Menertibkan 9 form template resmi milik PT ICI Paints Indonesia di Server 1 AMK (`38.103.170.235`, database PostgreSQL `db_esa_amk`).
- [x] **Pembersihan Laporan Nyasar**: Menghapus form template yang tidak sesuai dari portal Dulux (misal form laporan "Mamasuka").
- [x] **Perbaikan Error Sinkronisasi Antar-Server**: Memperbaiki mekanisme replikasi dan sinkronisasi template form laporan antar node/server.

---

### 2. Kustomisasi & Standardisasi Form Template Laporan Dulux
Sesuai arahan dan kebutuhan operasional lapangan Dulux:
- [x] **Laporan CBP (Competitor Brand Price)**:
  - Subbrand dan kemasan kompetitor (Tin/Kaleng, Galon, Pail) dibuat dinamis dengan master list merk & subbrand.
  - Inputan promo harga / diskon mendukung pilihan nominal (Rp) dan persentase (%).
  - Inputan upload foto bukti dihapus untuk mempercepat input petugas di lapangan.
  - **Migrasi Data CBP 2026**: 117.012 baris transaksi monitoring harga riil (Januari – Juli 2026) dari 7 file bulanan Excel diolah menggunakan parser XMLReader/ZipArchive streaming ke 7 chunk JSONL terkompresi dan SQLite database.
- [x] **Laporan OOS - SSO (Out of Stock - Share of Shelf)**:
  - Inputan kemasan kosong dihapus.
  - Pilihan upload foto bukti dihapus.
  - Menambahkan dropdown dinamis untuk pilihan varian warna *ready mix*.
  - Menghapus pilihan "semua kemasan" dan field catatan.
  - **Penggabungan Formulir**: Menggabungkan form OOS LSO (Line Stock Out) dan OOS SSO menjadi satu formulir terpadu yang komprehensif.
- [x] **Laporan Data Pelanggan**:
  - Pilihan *Brand Cat yang Dicari* diubah menjadi dropdown pilihan dinamis.
  - Input foto dan catatan dihapus.
- [x] **Laporan Daily Maintenance**:
  - Menambahkan persistensi data otomatis untuk *Tipe Mesin*, *Tipe Mesin Tinting*, *Nomor Seri*, dan *Nomor Mesin* per toko sehingga otomatis tersimpan dan terisi kembali saat kunjungan toko berikutnya.

---

### 3. Migrasi & Impor Data Laporan Offtake Tahun 2025 (843.455 Baris)
- [x] **Parsing File Sumber**: Mengolah file arsip `Offtake Jan - Des 2025 (With data Dist Store).xlsx` (117 MB).
- [x] **Normalisasi & Pemetaan Data**:
  - Relasi outlet/toko: Pemetaan ke `work_locations` (pencocokan nama toko, kode SAP, dan pembuatan lokasi baru jika belum ada).
  - Relasi petugas SPG: Pemetaan ke data `employees` dan penugasan principal Dulux (`principal_id = 18`).
  - Pemetaan field template `RPT-DULUX-OFFTAKE-01` (`produk_terjual`, `kemasan_galon`, `qty_galon`, `kemasan_pail`, `qty_pail`, `total_volume_liter`, `total_nilai_sales_rp`).
- [x] **Eksekusi Impor Bertahap (Chunking Batch)**:
  - **Januari 2025**: 73.375 baris
  - **Februari 2025**: 75.991 baris
  - **Maret 2025**: 89.764 baris
  - **April 2025**: 58.317 baris
  - **Mei 2025**: 72.486 baris
  - **Juni 2025**: 52.937 baris
  - **Juli 2025**: 81.858 baris
  - **Agustus 2025**: 69.911 baris
  - **September 2025**: 66.472 baris
  - **Oktober 2025**: 68.393 baris
  - **November 2025**: 65.143 baris
  - **Desember 2025**: 68.808 baris
  - **Total**: **843.455 record laporan** tersimpan di tabel `report_submissions` dan `report_submission_values`.

---

### 3.1 Pemrosesan & Impor Data Laporan Offtake Tahun 2026 (439.819 Baris)
- [x] **Parsing File Sumber**: Mengolah file arsip `Raw Offtake 2026.xlsx` (55.4 MB) menggunakan streaming XML berkecepatan tinggi (`build_offtake_2026.php`).
- [x] **Normalisasi Kolom & Koreksi Data**:
  - Menyesuaikan pergeseran kolom (Area di Kolom F, Store di Kolom I, SAP di Kolom J, Sub Brand di Kolom L, Brand di Kolom O, Kemasan Galon/Pail di Q/T, Volume di Kolom X).
  - Mengoreksi 8.864 baris yang salah ketik tahun 2025 pada Kolom B menjadi tahun 2026 berdasarkan tanggal transaksi riil (Kolom A).
- [x] **Generasi Dataset & Chunking Terkompresi**:
  - Membangun SQLite database `storage/app/dulux_data/offtake_2026.sqlite` (84.6 MB) & `offtake_2026.sqlite.gz` (17.5 MB).
  - Membagi dataset menjadi 7 file batch bulanan terkompresi di `storage/app/dulux_data/chunks/`:
    - **Januari 2026**: 65.046 baris | Volume: 1.756.563,13 L | Chunk: 0.84 MB
    - **Februari 2026**: 66.577 baris | Volume: 2.125.002,81 L | Chunk: 0.86 MB
    - **Maret 2026**: 58.529 baris | Volume: 1.664.950,57 L | Chunk: 0.74 MB
    - **April 2026**: 57.537 baris | Volume: 1.914.587,11 L | Chunk: 0.76 MB
    - **Mei 2026**: 61.530 baris | Volume: 2.026.805,76 L | Chunk: 0.81 MB
    - **Juni 2026**: 62.666 baris | Volume: 2.147.997,50 L | Chunk: 0.82 MB
    - **Juli 2026**: 67.934 baris | Volume: 2.273.081,99 L | Chunk: 0.88 MB
    - **Total**: **439.819 baris** | **13.908.988,86 Liter** | **629 Toko Unik**
- [x] **Peningkatan Artisan Command & Skrip Deploy**:
  - `ImportDuluxOfftakeCommand.php` diperbarui mendukung opsi `--year=2026` (atau `--year=2025`) dan deteksi otomatis file chunk.
  - Skrip deploy produksi `public/deploy-production.php` dan `public/deploy.php` mendukung parameter `year` untuk eksekusi fleksibel.

---

### 4. Pembaruan Desain Tampilan & Filter Laporan Portal
- [x] **Filter Rentang Bulan**: Mengganti filter bulan tunggal menjadi rentang fleksibel `Bulan & Tahun Awal` s/d `Bulan & Tahun Akhir` (contoh: Januari 2025 s/d Desember 2025).
- [x] **Filter SPG Digantikan Hierarki Wilayah**:
  - Dropdown **Region** (Semua Region / Filter per Region).
  - Dropdown **Area / Cabang** (Semua Cabang / Filter per Cabang).
  - Dropdown **Store / Outlet** (Filter per Outlet Toko).
  - Field **Pencarian Cepat (Search)** untuk nama petugas SPG, NIK, dan nama toko.
- [x] **Optimasi Query & Performa Skala Besar**:
  - Mengatasi kendala lag akibat 14.000+ data outlet dengan melimit query default ke 300 data saat belum difilter, dan meload toko spesifik sesuai region/area terpilih.
  - Memperbaiki pengecualian PostgreSQL `ERROR: column "code" does not exist` pada tabel `work_locations`.
  - Mengoptimalkan pembacaan rincian submission (`/portal/report/{code}/submission/{id}`) dengan eager-loading relasi dan penyesuaian scope tenant.

---

### 5. Peningkatan UI/UX: Center-Screen Professional Loading Animation
- [x] **Desain Glassmorphism Overlay**:
  - Menambahkan modal animasi di tengah layar dengan efek *backdrop blur* halus (`backdrop-filter: blur(10px)`).
  - Kartu indikator melayang modern bergradien lembut dengan bayangan kedalaman (*depth shadow*).
- [x] **Komponen Visual Berkelas**:
  - *Dual-Orbit Rotating Spinner*: Cincin putar ganda dengan aksen warna korporat principal.
  - *Animated Center Badge*: Ikon brand dengan efek denyut halus (*breathing pulse*).
  - *Indeterminate Shimmer Bar*: Bilah progres gradien berkilau yang bergerak dinamis.
  - *Dynamic Status Message*: Teks kontekstual (misal: *"Menerapkan Filter Laporan..."*, *"Memperbarui Region Toko..."*, *"Memuat Halaman..."*, *"Menyiapkan Ekspor Data..."*) lengkap dengan *bouncing dots*.
- [x] **Integrasi Event Otomatis**:
  - Otomatis aktif saat memilih filter dropdown (Region, Area, Toko, Bulan/Tahun).
  - Otomatis aktif saat klik tombol Filter, Reset, tombol paginasi halaman, tombol ekspor Excel, maupun saat membuka detail submission.
  - Failsafe timeout 60 detik dan auto-dismiss saat navigasi cache browser (`pageshow/bfcache`).

---

### 6. Otomatisasi Infrastruktur & Multi-Server Deployment
- [x] **Pipeline Deployment Produksi**:
  - Sinkronisasi otomatis dari Git repository ke dev server (`appsend.my.id`) dan server produksi Server 1 AMK (`38.68.69.225`).
  - Replikasi otomatis ke seluruh folder virtual host di bawah `/www/wwwroot/` (`amk.esa-solutions.id`, `dulux.esa-solutions.id`, `api.esa-solutions.id`, `amk.dgsoft.web.id`).
  - Otomatisasi verifikasi sintaks PHP (`php -l`) sebelum proses deployment dinyatakan tuntas untuk mencegah error sintaks / 500 fatal di production.
  - Pembersihan dan penyegaran cache framework (`config`, `route`, `view`, `blade-icons`).

---

### 7. Rekalibrasi Biometrik Wajah AI & Peningkatan Mobile App (v1.0.117 – v1.0.119)
- [x] **Investigasi & Resolusi False Negative (Wajah Asli Ditolak)**:
  - Mengatasi kendala di mana wajah karyawan sendiri tidak cocok saat absensi karena fluktuasi landmark mata akibat kedipan, bayangan, atau sudut pencahayaan kamera depan.
  - **Evolusi Algoritma Biometrik**:
    - *Anchor Eyes Exclusion*: Mengecualikan rasio jarak mata-ke-mata dari penghitungan error, menggantikannya sebagai titik jangkar normalisasi rotasi & skala wajah.
    - *24 Stable Key-Distances*: Menggunakan 24 pasangan jarak anatomi stabil (Hidung, Kontur Bibir Atas/Bawah, Sudut Mulut, Tulang Pipi, dan Kontur Rahang).
    - *Dynamic Outlier Tolerance*: Menetapkan toleransi deviasi per landmark sebesar 24% dan penalti bertahap (`penaltyFactor = 0.5`) sehingga fluktuasi ekspresi minor tidak membatalkan kecocokan wajah.
    - *Threshold Kalibrasi Optimal*: Menetapkan batas kelulusan kecocokan dinamis di angka **75%** (menggantikan formula ketat 82% yang memicu false rejection).
- [x] **Validasi Monte Carlo & Uji Separasi Biometrik**:
  - Menjalankan simulasi Monte Carlo 1.000 iterasi terhadap dataset wajah aktual:
    - Rata-rata skor kecocokan wajah asli (*Genuine Match*): **88.2%** (Lulus 100%).
    - Rata-rata skor penolakan wajah orang lain (*Imposter / Different Face*): **67.6%** (Ditolak 100%).
    - Margin pemisah (*Separation Margin*): **+20.6%** tanpa overlap.
- [x] **Penyempurnaan Face Enrollment & Resolusi Multi-Cluster**:
  - Mengatasi kegagalan pendaftaran wajah lewat Smart Gateway Relay dengan menambahkan multipart upload & fallback `photo_base64`.
  - Kompresi gambar otomatis sisi mobile sebelum pengiriman data wajah untuk menghemat bandwidth.
  - *Multi-Cluster Photo Resolver*: Aplikasi mobile mampu mengunduh master face photo secara otomatis lintas node cluster (`amk`, `atk`, `akp.esa-solutions.id`) dan menyimpannya di cache lokal perangkat.
- [x] **Distribusi Rilis APK v1.0.119**:
  - Berhasil dikompilasi ke `app-release.apk` (44.6 MB) dan didistribusikan ke seluruh node cluster production via skrip upload chunked.

---

### 8. Investigasi & Mitigasi Krisis CPU Overload 100% Cluster Server
- [x] **Diagnosa Lonjakan Beban (CPU 100% di 8 Core, Load Avg 56.55)**:
  - Ditemukan lonjakan ribuan request PHP-FPM yang berasal dari request gambar karyawan `/storage/employees/GGK3wfBgib4GVodoojt09bgDkEYff12WLEvl3FEv.jpg` (3.571+ request).
- [x] **Identifikasi Root Cause (Infinite Mutual cURL Loop)**:
  - Pada update commit `89de626`, ditambahkan route fallback `/storage/{folder}/{filename}` di `routes/web.php` yang memicu cURL HEAD request ke seluruh server peer (`atk`, `amk`, `akp`).
  - Karena Server 1 (`amk`) juga memanggil dirinya sendiri dan file tersebut tidak ada di disk fisik, terjadi **badai request HTTP rekursif (DDoS internal antar-server)** yang saling memanggil tanpa henti dan menahan socket PHP-FPM.
- [x] **Remediasi & Pembersihan Total**:
  - Menghapus total blok route fallback `/storage/{folder}/{filename}` dari `routes/web.php` (`commit 672ee11`).
  - Menjalankan pembersihan cache menyeluruh (`optimize:clear`) dan me-reset service PHP-FPM & Nginx.
  - **Hasil**: Beban server seketika turun dari 100% (Load 56.55) menjadi dingin dan stabil normal di kisaran **1% – 3% CPU**.

---

### 9. Klarifikasi & Resolusi IP Server 1 AMK (38.103.170.235 vs 38.68.69.225)
- [x] **Investigasi Perbedaan IP Dashboard VPS vs aaPanel**:
  - Ditemukan mengapa aaPanel menampilkan `38.68.69.225`: aaPanel mendeteksi IP keluar (*outbound egress / NAT gateway*) server saat melakukan ping keluar ke API Baota/ipify.
  - Sedangkan IP publik fisik yang mendengarkan koneksi masuk (*inbound listening interface*) sesuai dashboard VPS Jagoan Hosting adalah **`38.103.170.235`**.
- [x] **Verifikasi Normalisasi DNS**:
  - Mengarahkan domain `amk.esa-solutions.id` kembali ke IP aslinya `38.103.170.235`.
  - Website `https://amk.esa-solutions.id` langsung dapat diakses normal, cepat, dan lancar (HTTP 200 OK).
- [x] **Penyesuaian Konfigurasi Cluster**:
  - Memastikan seluruh skrip deploy (`deploy-production.php`), konfigurasi multi-server (`config/multiserver.php`), dan landing page cluster tetap konsisten menggunakan IP resmi `38.103.170.235`.

---

### 10. Perombakan Tampilan Dashboard CBP Dulux & Standardisasi Field Form Sesuai Raw Data
- [x] **Standardisasi Field Form CBP (`RPT-DULUX-CBP-PRICING`)**:
  - Menyelaraskan form input pelaporan dengan kolom sheet *Raw Data* Excel:
    1. `kategori_produk` (Kategori Segmen Cat: Super Premium Interior, Dulux Interior, Mass Interior, Super Premium Exterior, Premium Exterior, Enamel, Waterproofing, Sealer)
    2. `brand_cat` (Brand Cat: AN Dulux vs JOTUN, NIPPON PAINT, AVIAN/LENKOTE, MOWILEX, SIKA, AQUAPROOF, PROPAN, dll.)
    3. `subbrand_produk` (Nama Sub Brand / Produk spesifik yang dicek di toko)
    4. `harga_tin_rp` & `harga_terendah_tin_rp` (Harga Normal & Promo Kemasan Tin 1L/1Kg)
    5. `harga_galon_rp` & `harga_terendah_galon_rp` (Harga Normal & Promo Kemasan Galon 2.5L/4-5Kg)
    6. `harga_pail_rp` & `harga_terendah_pail_rp` (Harga Normal & Promo Kemasan Pail 20L/25Kg)
  - Mengeliminasi field keterangan promo sesuai instruksi operasional.
  - Menyiapkan migration resmi `2026_09_04_080000_align_dulux_cbp_template_fields.php`.
- [x] **Implementasi Antarmuka Eksekutif Multi-Tab Portal**:
  - **Tab 1: Cat Tembok (Dashboard 1)**:
    - Grafik tren harga MOP bulanan & YoY (ApexCharts) membandingkan Dulux, Jotun, Nippon Paint, Avian/Aquaproof, dan Mowilex.
    - 6 Matriks Kategori: Super Premium Interior (100% = Ambiance), Dulux Interior (100% = Pentalite), Washable Segment (100% = EasyClean), Super Premium Exterior (100% = DWS PWF), Premium Exterior (100% = DWS Core), dan Mass Interior (100% = Catylac).
    - Toggle dinamis per kategori: **Tabel Rata-Rata Harga MOP (Rp)** vs **Tabel Price Index to AN Brands (%)** dengan badge acuan 100% dan indikator harga kompetitor lebih murah / mahal.
  - **Tab 2: Enamel & Waterproofing (Dashboard 2)**:
    - Kategori Enamel: Kemasan Tin 1L/1Kg (100% = V-Gloss High Gloss).
    - Kategori Waterproofing: Kemasan Galon 4-5Kg (100% = Aquashield).
    - Dilengkapi tabel rata-rata harga riil dan tabel perbandingan indeks harga.
  - **Tab 3: Data Rincian Submisi (Raw Data Table)**:
    - Tabel rincian submission data per outlet / SPG dengan paginasi, pencarian, dan tombol ekspor Excel.
- [x] **Optimasi Mesin Agregasi Backend**:
  - Agregasi analitik instan berbasis `cbp_2026.sqlite` (117.012 baris) dengan response time 450 ms (Cold) dan < 1 ms (Cached 300s).
  - Terintegrasi penuh dengan filter hirarkis wilayah (**Region ➔ Area ➔ Toko**) dan rentang periode bulan.

---

### 11. Transformasi & Rekonstruksi Laporan Offtake Dulux (`RPT-DULUX-OFFTAKE-PROMOTOR` & `RPT-DULUX-OFFTAKE-TOKO-ALL`)
- [x] **Ingestion Data Historis Excel (Januari – Juli 2026)**:
  - Memproses 8.289 baris data transaksi penjualan dari ratusan toko dan DC dengan total nilai transaksi Rp 37+ Miliar.
  - Membangun database SQLite terindeks (`dulux_offtake.sqlite` & `dulux_offtake.sqlite.gz`) di `storage/app/dulux_data/` dengan query sub-milidetik.
- [x] **Dashboard Multi-Tab Interaktif**:
  - **Tab Sheet 1 (Laporan Penjualan Offtake / Raw Submissions)**: Filter periode bulan, RSM Region, Area, Toko, Promotor/DC, pencarian, dan pagination.
  - **Tab Sheet 2 (Rekap Volume Toko & Target)**: Tabel pivot volume bulanan (Jan-Jul), perbandingan volume Dulux vs Catylac vs Lainnya, pencapaian target, dan pertumbuhan MoM (*Month-over-Month*).
  - **Tab SCM (Supply Chain Management)**: Rekap pergerakan stok, distribusi catylac, dan rasio pemenuhan.
  - **Tab Pivotable & Analytics**: Filter dinamis Channel (LSO, SSO), Kategori Produk, dan RSM Area.
- [x] **Ekspor Multi-Format**: Fitur ekspor Excel dan CSV untuk setiap tab laporan.

---

### 12. Transformasi & Rekonstruksi Laporan Out of Stock / OOS Dulux (`RPT-DULUX-OOS`)
- [x] **Ingestion Data Historis Excel OOS 2026**:
  - Memproses 7.671 baris data pencatatan OOS mingguan dan bulanan ke SQLite terindeks (`dulux_oos.sqlite` & `dulux_oos.sqlite.gz`).
- [x] **Dashboard 3 Tab Interaktif**:
  - **Tab 1: Rekap Alasan & Channel (Summary)**: 6 Kartu KPI Utama (Total Insiden OOS, Toko Terdampak, Item SKU OOS, Estimasi Lost Sales Rp, Rata-rata Durasi OOS, % Toko Bebas OOS), visualisasi akar penyebab OOS (*Distributor Delay, Demand Surge, Factory Limitation, Store PO Delay*), dan sebaran Channel LSO vs SSO.
  - **Tab 2: Matriks Mingguan per Toko (Weekly Matrix)**: Pivot mingguan status OOS per toko (W1–W52), tombol toggle *'Sembunyikan Toko 0 OOS'* vs *'Tampilkan Semua Toko'*, serta paginasi toko.
  - **Tab 3: Data Mentah Submission (Raw Submissions)**: Tabel lengkap 7.671 riwayat pelaporan OOS dengan filter status, channel, area, dan pencarian instan.
- [x] **Sinkronisasi Form Template**: Menyelaraskan form input template `RPT-DULUX-OOS` (SKU Produk, Alasan OOS, Estimasi Kebutuhan, Tindak Lanjut).

---

### 13. Transformasi & Rekonstruksi Laporan Daily Maintenance POST & Mesin Tinting (`RPT-DULUX-DAILY-MAINTENANCE`)
- [x] **Ingestion Data Historis Excel**:
  - Memproses 3.842 baris data riwayat perawatan mesin tinting dan display POST di 324 toko ke SQLite terindeks (`daily_maintenance.sqlite` & `daily_maintenance.sqlite.gz`).
- [x] **Dashboard 3 Tab Interaktif**:
  - **Tab 1: Ringkasan Pemeliharaan (Executive Summary)**: 6 Kartu KPI (Toko Aktif Mesin, Total Cek Fisik, Kepatuhan Nozzle OK %, Kepatuhan Kalibrasi %, Kesiapan POST %, Skor Kepatuhan Nasional), visualisasi kondisi komponen (*Nozzle Cleaning, Level Canister Tinting, Kalibrasi Timbangan, Display POST, Agitator, Software POS*), dan breakdown kepatuhan regional.
  - **Tab 2: Matriks Toko & Frekuensi (Store Matrix)**: Riwayat perawatan harian per toko, frekuensi perawatan bulanan, identitas mesin (No. Mesin, Model), dan skor kepatuhan toko.
  - **Tab 3: Data Mentah Pemeriksaan (Raw Submissions)**: Tabel detail riwayat checklist harian petugas DC/Promotor.
- [x] **Sinkronisasi Form Template**: Menyelaraskan field checklist form `RPT-DULUX-DAILY-MAINTENANCE` secara menyeluruh dengan parameter pemeriksaan mesin asli.

---

### 14. Transformasi & Rekonstruksi Laporan Data Pelanggan & Konsumen Dulux (`RPT-DULUX-DATABASE-PELANGGAN`)
- [x] **Ingestion Data Historis Excel (2025–2026)**:
  - Memproses 5.522 data konsumen unik dengan akumulasi transaksi belanja senilai Rp 37,74 Miliar di 324 toko dan 497 DC/promotor ke SQLite terindeks (`customer_db.sqlite` & `customer_db.sqlite.gz`).
- [x] **Dashboard 3 Tab Interaktif**:
  - **Tab 1: Profil & Perilaku Konsumen (`tab=insights`)**:
    - 6 Kartu KPI Utama: Total Konsumen (5.522), Total Nilai Belanja (Rp 37,74 Miliar), Rata-rata Belanja / Basket Size (Rp 6,83 Juta/Orang), Toko Aktif (324), DC Terlibat (497), dan Konversi Switch ke Dulux (1.158 Konsumen / 21.0%).
    - Visualisasi Insight: Segmentasi Tipe Pelanggan (Pemilik Rumah 68%, Tukang Cat 14%, Kontraktor 12%, Mitra Dulux 6%), Alasan Memilih Brand (Rekomendasi DC 52.2%, Kualitas 30.8%, Harga 9.9%), Preferensi Brand Ditanyakan vs Dibeli (Analisis Switch Kompetitor Jotun, Nippon, Avian, Mowilex, Propan ke Dulux/Catylac), Kebutuhan Proyek, Dulux Visualizer, dan Painter Loyalty Club.
  - **Tab 2: Analisis Toko & Wilayah (`tab=regional_store`)**: Tabel Matriks Performa 10 RSM Area, Peringkat Top Toko Paginated, dan Top 20 Promotor/DC Teraktif.
  - **Tab 3: Data Mentah Pelanggan (`tab=raw`)**: Tabel 5.522 data mentah konsumen lengkap dengan tombol cepat **🟢 WhatsApp Direct Chat Link** (`wa.me`), filter, pencarian, dan pagination bar.
- [x] **Perbaikan Rute URL & Error Handling**:
  - Memperbaiki exception `UrlGenerationException` pada rute pagination tabel toko dan data mentah.
- [x] **Sinkronisasi Form Template**:
  - Menyelaraskan form input mobile/web template `RPT-DULUX-DATABASE-PELANGGAN` dengan kolom Excel asli (Nama Konsumen, No Kontak, Tipe Konsumen, Alamat Proyek, Alasan Memilih, Brand Ditanyakan, Brand Dibeli, Jenis Cat, Total Transaksi, Dulux Visualizer, Painter Loyalty Club).

---

### 15. Transformasi Widget Chart Perubahan Karyawan Aktif Odoo & Auto-Deploy Multi-Server
- [x] **Chart Perubahan Karyawan Aktif Tiap Jam (`ActiveEmployeesHourlyChartWidget`)**:
  - Merekonstruksi widget chart garis pada Dashboard Filament (`/admin`) agar menampilkan fluktuasi total karyawan aktif per jam dari sinkronisasi Odoo (~11.130 karyawan), menggantikan indikator kehadiran presensi.
  - Menerapkan **Dual Y-Axis**: Sumbu Y kiri untuk baseline total karyawan aktif (~11.000+) dan sumbu Y kanan untuk delta mutasi (`+Karyawan Baru` dan `-Resign`).
  - Menambahkan kartu ringkasan KPI live di atas widget: Total Aktif (11.130 dengan animasi *live pulsing*), Total Resign (25.027), Baru Hari Ini, Resign Hari Ini, serta status sinkronisasi Odoo.
  - Multi-tier auto fallback dari log sinkronisasi (`OdooSyncLog`), mutasi database karyawan (`employees`), dan snapshot per jam.
  - Memperluas retensi log sinkronisasi Odoo pada model `OdooSyncLog` (`pruneOlderLogs`) dari 5 menjadi 200 riwayat log.
- [x] **Eliminasi Loader Universal pada Dashboard Admin**:
  - Mengisolasi auto-refresh dashboard (`wire:poll`, `databaseNotifications`) sehingga tidak memicu animasi loading layar tengah yang mengganggu.
- [x] **Pipeline Deployment Multi-Server (Staging & Production)**:
  - Sukses deploy ke server Staging (`appsend.my.id`) via webhook streaming.
  - Sukses deploy multi-server otomatis via SSH streaming ke 3 Node Production:
    - Server 1: AMK (`38.103.170.235` / `amk.esa-solutions.id`) -> HTTP 200 OK.
    - Server 2: AKP (`38.103.170.223` / `akp.esa-solutions.id`) -> HTTP 200 OK.
    - Server 3: ATK (`38.103.170.224` / `atk.esa-solutions.id`) -> HTTP 200 OK.

---

### 16. Resolusi Query Offtake Live, Tab Data Laporan Masuk & Sistem Approval (7 September 2026)
- [x] **Investigasi & Resolusi Masalah Data Inputan Live Offtake Tidak Muncul**:
  - **Akar Masalah 1 (Silent SQL Exception)**: Menemukan dan memperbaiki query data live PostgreSQL pada `PrincipalPortalController.php` yang sebelumnya memilih kolom `submission_date` (kolom yang valid di PostgreSQL adalah `submitted_at`), memicu exception SQL yang tertangkap try-catch dan mengosongkan volume live.
  - **Akar Masalah 2 (Fatal Out-of-Memory HTTP 500)**: Menemukan bahwa query perbandingan YTD (Jan–Sep 2026) mencoba memuat seluruh baris PostgreSQL tanpa filter, yang memuat **439.819** record data batch migrasi lama (`SUB-OFFTAKE-2026-...`) ke memori PHP. Diatasi dengan menambahkan filter tegas `->where('submission_code', 'NOT LIKE', 'SUB-OFFTAKE%')` sehingga hanya inputan asli aplikasi mobile (`RPT-...`) yang di-query dari PostgreSQL, mengembalikan eksekusi query menjadi sangat cepat (< 1 detik, memori hanya ~65 MB).
  - **Normalisasi Field Mapping Dinamis**: Memastikan pemetaan field `brand_rm_base`, `sub_brand_base`, `total_volume_liter`, `kuantiti_galon_terjual_unit`, dan `kuantiti_pail_terjual_unit` terpetakan sempurna ke agregasi Rekap Volume Toko (Sheet 2) dan Raw Data Transaksi (Sheet 1).
- [x] **Penambahan Tab "Data Laporan Masuk" pada Portal Offtake Dulux**:
  - Menambahkan tab navigasi ke-3 pada toolbar utama laporan offtake (`tab=live`) dengan indikator badge counter jumlah laporan masuk secara real-time.
  - Menyediakan tabel terstruktur dengan rincian: *No, Kode Laporan (link detail), Waktu Submit (WIB), Promotor / SPG (Nama & NIK), Nama Toko / Outlet (Nama & SAP), Area & RSM, Brand & Sub Brand, Kemasan & Qty (Galon & Pail), Total Volume (L), Radius GPS (Valid/Luar), Status (Menunggu/Terverifikasi/Ditolak), dan Aksi*.
- [x] **Implementasi Sistem Approval & Verifikasi Laporan Offtake**:
  - **Tombol Quick Approve (Hijau)**: Memungkinkan Principal langsung memverifikasi dan menyetujui laporan dari baris tabel secara instan (`status = approved`).
  - **Tombol Quick Reject (Merah)**: Membuka modal dialog interaktif untuk memasukkan catatan atau alasan penolakan sebelum status diubah ke `rejected`.
  - **Tombol Detail (Biru)**: Menghubungkan ke halaman dokumen detail submisi lengkap dengan foto struk/nota, peta koordinat GPS, rincian 16 parameter, dan tombol verifikasi.
  - **Navigasi Seamless**: Tombol "Kembali" pada halaman detail otomatis mengembalikan pengguna ke tab `Data Laporan Masuk` (`tab=live`).
- [x] **Peningkatan UX Horizontal Scroll & Sticky Action Column**:
  - Menggunakan wrapper `.offtake-table-viewport` dengan `overflow-x: auto;` dan lebar minimum tabel 1.480px agar seluruh kolom memiliki ruang lega dan tabel dapat digeser horizontal dengan halus.
  - Menerapkan **Sticky Action Column** (`.col-sticky-action`) di sisi paling kanan tabel dengan bayangan pemisah (`box-shadow`), memastikan tombol aksi (*Detail*, *Setujui*, *Tolak*) **selalu terlihat utuh dan tidak terpotong** di semua ukuran layar.
  - Menambahkan badge petunjuk visual `↔ Geser untuk melihat kolom aksi`.

---

### 17. Multi-Kompetitor Form CBP Mobile & Rilis APK v1.0.124 (7 September 2026)
- [x] **Dukungan Multi-Entry Kompetitor Laporan CBP**:
  - Menyesuaikan arsitektur pelaporan Competitor Brand Price (CBP) pada aplikasi mobile agar petugas SPG / Promotor dapat menginput beberapa merk kompetitor (Jotun, Nippon, Avian, Mowilex, Propan) dalam satu kunjungan tanpa menimpa (*overwrite*) data kompetitor sebelumnya.
  - Menyempurnakan parsing dan visualisasi data multi-kompetitor pada portal web dan riwayat submission.
- [x] **Rilis APK Attendance ESA Mobile v1.0.124**:
  - Build dan perilisan file instalasi APK versi terbaru `app-release-v1.0.124.apk` yang terintegrasi dengan validasi biometrik wajah dan form laporan multi-kompetitor.

---

### 18. Resolusi Laporan Stock End Dulux (Stock Opname Bulanan & Tinter), Tab Data Laporan Masuk & Unifikasi Live Data (7 September 2026)
- [x] **Investigasi & Analisis Akar Masalah (Root Cause Analysis)**:
  - **Data Tidak Masuk ke Portal Principal**: Dashboard Stock End sebelumnya (`calculateStockDashboardData` & `calculateStockMonthlyCompareData`) hanya membaca file SQLite statis (`stock_2026.sqlite` yang hanya berisi data historis bulan 1 s/d 7). Data transaksi live dari aplikasi mobile (`RPT-2026...`) disimpan di database utama PostgreSQL (`report_submissions` dan `report_submission_values`) dan belum terhubung ke kalkulasi dashboard stock.
  - **Ketiadaan Tab Data Masuk & Fitur Approval**: Berbeda dengan modul Offtake, halaman laporan Stock End (`stock_dashboard.blade.php`) belum memiliki tab "Data Laporan Masuk" dan mekanisme verifikasi/approval untuk tim Principal.
- [x] **Unifikasi Database (PostgreSQL Live Submissions + Historical SQLite)**:
  - Mengimplementasikan `getLiveSubmissionsQuery()` pada `calculateStockDashboardData` dan `calculateStockMonthlyCompareData` di `PrincipalPortalController.php`.
  - Normalisasi nama field dinamis dari form input aplikasi mobile:
    - Produk: `produk_stock_end`, `produk`, `pilih_produk_dulux_catylac_yang_dicek`, `nama_produk`.
    - Brand: `brand`, `brand_cat`, dengan deteksi otomatis (*Dulux*, *Catylac*, *Catylac Smart Choice*, *Maxilite*).
    - Base / Warna: `base_tipe_warna`, `base_warna`, `warna`, `base`.
    - Kemasan & Kuantiti: `stok_fisik_kemasan_galon_qty`, `stok_qty_galon`, `stok_fisik_kemasan_pail_qty`, `stok_qty_pail`.
    - Volume (L): `total_volume_stok_liter` atau rumus kalkulasi presisi `(Galon * 2.5) + (Pail * 20.0)`.
    - Tinter & Mesin: `kategori_tinter_mesin_tinting`, `tipe_tinter_warna_pasta_pewarna`, `kuantiti_jumlah_kaleng_tinta_tinter`, `status_ketersediaan_tinter_di_toko`.
    - Gudang & Keterangan: `status_akses_pengecekan_gudang_toko`, `keterangan_kendala_stok_tinter_toko`.
  - Menggabungkan data live ke seluruh visualisasi Stock End:
    - **Tab 1: Tren Harian & Komparasi Bulanan**: Volume harian, KPI total volume, perbandingan pertumbuhan, dan Top 10 Toko.
    - **Tab 2: Rekap Volume Stock Toko (Pivot)**: Baris toko live, akumulasi volume bulanan per toko, dan Grand Total.
    - **Tab 3: Ringkasan SCM & Stock**: Akumulasi total stok, status aman/kritis, dan monitoring toko.
    - **Tab 4: Raw Data Submissions**: Entri baris transaksi live dengan label `⚡ LIVE` berstatus terverifikasi/menunggu.
- [x] **Penambahan Tab "Data Laporan Masuk" pada Portal Stock End**:
  - Menambahkan tab navigasi ke-5 (`id="btn_stock_tab_live"`, `tab=live`) dengan badge penghitung real-time jumlah laporan masuk.
  - Jika membuka periode bulan berjalan (e.g. September 2026) di mana SQLite kosong dan terdapat submisi baru dari mobile, portal secara cerdas otomatis membuka tab `Data Laporan Masuk`.
  - Menampilkan tabel komprehensif (16 kolom): *No, Kode Laporan, Waktu Submit, Promotor / SPG, Nama Toko / Outlet (SAP), Area & RSM, Produk & Brand, Base / Warna, Stok Fisik Kemasan (Galon & Pail Qty), Total Volume (L), Mesin & Tinta Tinter, Akses Gudang, Keterangan Kendala, Radius GPS, Status, dan Aksi*.
- [x] **Implementasi Sistem Approval & Verifikasi Stock End**:
  - **Tombol Quick Approve (Hijau)**: Langsung memverifikasi laporan dari baris tabel secara instan.
  - **Tombol Quick Reject (Merah)**: Membuka modal dialog penolakan (`stock_reject_modal`) untuk memasukkan alasan penolakan.
  - **Tombol Detail (Biru)**: Mengarahkan ke halaman detail dokumen submisi lengkap dengan foto bukti fisik rak display/gudang/mesin tinter, koordinat GPS, dan 11 parameter isian.
  - **Sticky Action Column**: Kolom aksi menempel di sisi kanan tabel (`.col-sticky-action`) sehingga tidak pernah terpotong saat digeser secara horizontal.

### 19. Alur Pelaporan Sekuensial Berjenjang Dulux, Single-Product Submission & Attendance Gate (8 September 2026)
- [x] **Alur Pelaporan Berjenjang Wajib (Strict Sequential Workflow 6 Langkah)**:
  - Mengurutkan 6 laporan wajib Dulux:
    1. **Offtake** (`RPT-DULUX-OFFTAKE-01`)
    2. **Stok End** (`RPT-DULUX-STOCK-END`)
    3. **OOS** (`RPT-DULUX-OOS-SSO`)
    4. **CBP** (`RPT-DULUX-CBP-PRICING`)
    5. **Daily Maintenance** (`RPT-DULUX-DAILY-MAINTENANCE`)
    6. **Database Pelanggan** (`RPT-DULUX-DATABASE-PELANGGAN`)
  - Setiap laporan berstatus terkunci (`is_step_locked`) dan hanya terbuka otomatis jika laporan pada langkah sebelumnya telah diselesaikan seluruhnya pada hari tersebut.
  - Pada layar hub pelaporan (`ReportingHubScreen`), ditambahkan nomor langkah visual (*Langkah 1 s/d 6*), badge status terkunci, dan modal peringatan interaktif jika mengklik langkah yang belum terbuka.
- [x] **Single-Product Submission Per Form & Disable Produk Terlapor**:
  - Setiap produk dikirim sebagai 1 data submission mandiri ke backend (`ReportSubmission`), bukan digabung menjadi 1 payload besar.
  - Produk yang sudah dikirimkan laporannya hari itu dibuat disabled (`enabled: false`), dicoret teksnya (*strikethrough*), dan ditandai badge hijau terang: **`Sudah Dilaporkan Hari Ini ✓`** baik di Dropdown maupun di Bottom Sheet Catalog Picker.
  - Jika pengguna mengetikkan atau memilih produk yang telah dilaporkan, form menampilkan banner peringatan amber dan tombol submit memblokir duplikasi.
  - **Tombol Dinamis & Kartu Progres Produk**:
    - Menampilkan kartu progres `X / Y Produk` dengan progress bar real-time.
    - Ketika sisa produk > 1: Tombol utama berbunyi **"Kirim & Lanjut Produk Berikutnya"** (mengirim 1 data produk, mengosongkan input produk, dan otomatis berpindah ke produk berikutnya). Tombol kedua **"Kirim & Selesai"** dalam status terkunci/disabled.
    - Ketika berada pada produk terakhir (sisa <= 1): Tombol berubah menjadi hijau menonjol: **"Kirim & Selesai (Produk Terakhir ✓)"**. Submisi ini menyelesaikan langkah dan kembali ke layar hub untuk membuka langkah selanjutnya.
- [x] **Pembatasan Check-Out & Visit-Out (Attendance Gate)**:
  - Karyawan tidak dapat melakukan Check-Out kehadiran ataupun Visit-Out kunjungan toko jika seluruh laporan wajib hari itu (termasuk seluruh produk wajib) belum tuntas dilaporkan.
  - Endpoint backend validasi absensi (`checkout` & `visit_out`) menolak aksi dengan HTTP 422 `PENDING_REPORTS_REQUIRED` dan daftar nama laporan yang masih pending.
  - Aplikasi mobile memvalidasi kepatuhan secara pre-emptive di `DashboardScreen` dan menangani response 422 di `AttendanceLocationScreen`, memunculkan dialog peringatan dengan tombol cepat **"Isi Laporan Sekarang"** yang langsung membuka layar pelaporan.
- [x] **Kalkulasi Target Cut-off Berdasarkan Hari Kerja Efektif**:
  - Menyesuaikan kalkulasi target cut-off di `ReportTemplate::calculateCutoffTarget` untuk hanya menghitung hari kerja efektif karyawan (`schedule_type == 'workday'`), mengabaikan hari libur nasional (`Holiday`) dan hari libur shift (`dayoff`).
- [x] **Kompilasi & Rilis APK Mobile v1.0.126 (8 September 2026)**:
  - Bump versi mobile ke `v1.0.126+126` pada `att-mobile/pubspec.yaml`.
  - Berhasil melakukan kompilasi release APK (`flutter build apk --release`, 107.7MB, 112.950.361 bytes).
  - Mengunggah file APK via chunked uploader 22 parts ke server staging (`https://appsend.my.id/app-release.apk`).
  - Menjalankan sinkronisasi multi-server production deployment cluster, mendistribusikan APK `v1.0.126` ke 3 server production:
    - **Server 1 (AMK)**: `https://amk.esa-solutions.id/app-release.apk`
    - **Server 2 (AKP)**: `https://akp.esa-solutions.id/app-release.apk`
    - **Server 3 (ATK)**: `https://atk.esa-solutions.id/app-release.apk`
  - Seluruh server production dan staging telah aktif menyajikan APK rilis `v1.0.126` dengan verifikasi health check ping OK (HTTP 200).
- [x] **Halaman Terpadu Server Monitoring 3 Server Production (`/admin/server-monitoring`)**:
  - Monitoring metrik CPU, RAM, Disk Storage riil, Active Processes (non-sleeping sesuai aaPanel), Load Average, dan Uptime untuk 3 server production (AMK, AKP, ATK) dalam 1 layar.
- [x] **Import Master Data Work Location Inhouse (`Store Inhouse Final.xlsb`)**:
  - Berhasil mengimpor 3.513 lokasi toko inhouse dengan kode acak unik `STR-XXXXXX`, dan menyelaraskan tabel agar menampilkan kolom Code di posisi pertama.
- [x] **Tab Working Groups di Halaman Employee Schedule Roster & 2-Step Wizard Edit**:
  - Menambahkan Tab Working Groups langsung di halaman Roster (`/admin/employee-schedules`) tanpa membuat menu sidebar baru.
  - Menampilkan tabel pola kerja lengkap, modal pop-up rincian seluruh anggota karyawan, serta aksi re-generate, edit, dan hapus.
  - Merapikan tombol header sehingga hanya ada 1 tombol utama: `Input via Working Group`.
  - Menyelaraskan form **Edit Working Group** menjadi **2-Step Wizard** yang identik dengan form pembuatan (Step 1: Description & Configuration; Step 2: Implementing Working Group).
- [x] **Penyelarasan Urutan Alur Pelaporan Sekuensial Dulux & Rilis APK v1.0.128 (9 September 2026)**:
  - Menyelaraskan urutan wajib pelaporan Dulux (6 langkah berurutan dengan gating/kunci langkah):
    1. **Langkah 1: Daily Maintenance POST** (`RPT-DULUX-DAILY-MAINTENANCE`)
    2. **Langkah 2: Offtake** (`RPT-DULUX-OFFTAKE-01`)
    3. **Langkah 3: Out of Stock / OOS** (`RPT-DULUX-OOS-SSO`)
    4. **Langkah 4: Database Pelanggan & Konsumen** (`RPT-DULUX-DATABASE-PELANGGAN`)
    5. **Langkah 5: Stok End (Stock Opname Bulanan)** (`RPT-DULUX-STOCK-END`)
    6. **Langkah 6: CBP (Consumer Buying Price)** (`RPT-DULUX-CBP-PRICING`)
  - **Backend API (`ReportingApiController.php`)**: Update `$duluxOrder` di method `index()` dan `checkPendingReportsStatic()`.
  - **Aplikasi Mobile Flutter (`att-mobile`)**: Menambahkan `duluxOrderMap` dan helper `applyDuluxSequence` di `ReportTemplateModel` dan `DynamicReportingProvider` untuk konsistensi sorting dan gating baik online maupun offline cache.
- [x] **Multi-Mesin Per Toko (Dulux), Mandatory Daily Maintenance Gating & Icon Penanda Lokasi (9 September 2026)**:
  - **Multi-Mesin per Store di Web Admin Filament (`WorkLocationForm.php`)**:
    - Menambahkan `Repeater::make('machines')` pada form Master Work Location.
    - Setiap toko kini dapat memiliki 2 atau lebih mesin tinting terdaftar, mencakup tipe mesin (Corob D200, Fast & Fluid HA480/HA680, Santint, Hero, dll.) dan Nomor Seri mesin.
    - Model `WorkLocation` dilengkapi aksesor cerdas `normalized_machines` yang secara transparan menyatukan data multi-mesin JSON array dengan fallback data mesin skalar tunggal.
  - **Dynamic Machine Binding & Reporting Gate (`ReportingApiController.php` & `AttendanceController.php`)**:
    - Backend secara dinamis mengidentifikasi toko tempat karyawan check-in/visit-in hari ini.
    - Laporan Daily Maintenance POST (`RPT-DULUX-DAILY-MAINTENANCE`) mengunci daftar mesin sesuai yang terdaftar pada toko tersebut.
    - Menghitung jumlah mesin terlapor vs total mesin (`has_machine_binding`, `submitted_machines`, `total_machines_count`, `remaining_machines_count`).
    - Laporan Daily Maintenance hanya berstatus selesai (`is_completed_today = true`) jika **seluruh mesin** telah dilaporkan. Jika belum, Langkah 2 (Offtake) tetap terkunci dan Check-out / Visit-out diblokir dengan peringatan sisa mesin.
  - **Pembaruan Antarmuka Mobile Form Laporan (`DynamicFormScreen`)**:
    - **Card Info Lokasi / Store Dihilangkan**: Menghilangkan kartu lokasi besar di bagian atas form untuk memaksimalkan ruang kerja visual petugas.
    - **Icon Penanda Lokasi Interaktif di AppBar**:
      - 🔴 **Merah**: Belum Check-in / Visit-in (Form terkunci, tombol submit nonaktif).
      - 🟠 **Orange**: Diluar Radius Lokasi / Store (Menampilkan jarak riil meter dan toleransi radius).
      - 🟢 **Hijau**: Dalam Radius Lokasi / Store (Posisi GPS aman dalam radius).
      - Dilengkapi modal dialog detail saat icon diklik yang menampilkan informasi toko, koordinat GPS, dan status radius secara transparan.
    - **Alur Pelaporan Multi-Mesin**:
      - Dropdown tipe mesin otomatis menonaktifkan (*disabled & strikethrough*) mesin yang telah dilaporkan hari itu (`✓ Sudah Dilaporkan`).
      - Otomatis memilih mesin pertama yang belum dilaporkan dan mengisi nomor seri secara otomatis.
      - Menampilkan progress bar mesin dan tombol dinamis **"Kirim & Lanjut Mesin Berikutnya"** hingga mesin terakhir yang bertuliskan **"Kirim & Selesai (Mesin Terakhir ✓)"**.
  - **Kompilasi & Distribusi APK v1.0.129+129**:
    - Bump versi ke `v1.0.129+129`.
    - Kompilasi Flutter release APK dan distribusi ke Staging (`appsend.my.id`) serta 3 Node Cluster Production (`AMK`, `AKP`, `ATK`).

---

## 🎯 Rencana Pengembangan Selanjutnya (Next Milestones)

| No | Target Fitur / Peningkatan | Prioritas | Estimasi / Keterangan |
| :---: | :--- | :---: | :--- |
| 1 | **Optimasi Export Excel Dataset Masif**: Menambahkan queue background job untuk ekspor data di atas 50.000 baris agar tidak membebani web worker. | Medium | Laravel Queues / Spatie Simple Excel |
| 2 | **Odoo Studio Drag-and-Drop Enhancement**: Penyempurnaan simpan tata letak kustom widget dashboard untuk pengguna portal non-teknis. | Medium | Penyempurnaan UI Modal Studio |
| 3 | **Filter Multi-Select Outlet / Toko**: Pilihan multi-toko via Select2/TomSelect dengan AJAX remote search untuk mencari langsung dari 14.000 toko. | Low | Peningkatan usability dropdown toko |
| 4 | **Dashboard Analitik Tren Penjualan Tahunan**: Visualisasi perbandingan offtake YoY (Year-over-Year) antara tahun 2024 vs 2025. | Low | ApexCharts integrasi |

---

*Terakhir diperbarui: 9 September 2026*  
*Pengembang: Digital Galery / DGSoft - Tim Attendance ESA*
