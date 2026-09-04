# 🗺️ Project Roadmap & Changelog Sistem Attendance ESA & Portal Principal

Dokumen ini merangkum seluruh progres pekerjaan yang telah diselesaikan, arsitektur yang telah diimplementasikan, serta rencana pengembangan lanjutan untuk sistem **Attendance ESA & Portal Principal**.

---

## 📌 Status Ringkasan Proyek

| Kategori | Status | Keterangan |
| :--- | :---: | :--- |
| **Portal Principal Dulux (PT ICI Paints Indonesia)** | 🟢 Aktif / Live | `https://dulux.esa-solutions.id/portal` |
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

## 🎯 Rencana Pengembangan Selanjutnya (Next Milestones)

| No | Target Fitur / Peningkatan | Prioritas | Estimasi / Keterangan |
| :---: | :--- | :---: | :--- |
| 1 | **Optimasi Export Excel Dataset Masif**: Menambahkan queue background job untuk ekspor data di atas 50.000 baris agar tidak membebani web worker. | Medium | Laravel Queues / Spatie Simple Excel |
| 2 | **Odoo Studio Drag-and-Drop Enhancement**: Penyempurnaan simpan tata letak kustom widget dashboard untuk pengguna portal non-teknis. | Medium | Penyempurnaan UI Modal Studio |
| 3 | **Filter Multi-Select Outlet / Toko**: Pilihan multi-toko via Select2/TomSelect dengan AJAX remote search untuk mencari langsung dari 14.000 toko. | Low | Peningkatan usability dropdown toko |
| 4 | **Dashboard Analitik Tren Penjualan Tahunan**: Visualisasi perbandingan offtake YoY (Year-over-Year) antara tahun 2024 vs 2025. | Low | ApexCharts integrasi |

---

*Terakhir diperbarui: 4 September 2026*  
*Pengembang: Digital Galery / DGSoft - Tim Attendance ESA*
