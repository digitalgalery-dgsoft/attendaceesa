# 🗺️ Project Roadmap & Changelog Sistem Attendance ESA & Portal Principal

Dokumen ini merangkum seluruh progres pekerjaan yang telah diselesaikan, arsitektur yang telah diimplementasikan, serta rencana pengembangan lanjutan untuk sistem **Attendance ESA & Portal Principal**.

---

## 📌 Status Ringkasan Proyek

| Kategori | Status | Keterangan |
| :--- | :---: | :--- |
| **Portal Principal Dulux (PT ICI Paints Indonesia)** | 🟢 Aktif / Live | `https://dulux.esa-solutions.id/portal` |
| **Impor Data Historis Offtake 2025** | 🟢 Selesai (100%) | 843.455 baris data (Jan–Des 2025) berhasil dimigrasi |
| **Ekstraksi & Impor Data Offtake 2026** | 🟢 Selesai (100%) | 439.819 baris data (Jan–Jul 2026) berhasil diproses ke JSONL chunks & SQLite |
| **Ekstraksi & Impor Data CBP 2026** | 🟢 Selesai (100%) | 117.012 baris monitoring harga (Jan–Jul 2026) di-stream ke JSONL chunks, SQLite & DB |
| **Ekstraksi & Impor Data Stock End 2026** | 🟢 Selesai (100%) | 85.967 baris data stock fisik & tinter (Jan–Jul 2026, 28,3M L) ke JSONL chunks & DB |
| **Penyesuaian Formulir Pelaporan ICI Paint** | 🟢 Selesai (100%) | CBP, OOS LSO/SSO, Data Pelanggan, Stock End & Tinter |
| **Optimasi Performa & Query Skala Besar** | 🟢 Selesai | Mengakomodasi 14.000+ store & jutaan data submission |
| **UI/UX Loading Screen Layar Tengah** | 🟢 Selesai | Animasi glassmorphism & dual-orbit loader otomatis |
| **AI Biometrik Wajah Mobile (v1.0.119)** | 🟢 Rilis & Live | Rekalibrasi 24 landmarks, threshold 75%, +20.6% separation margin |
| **Resolusi CPU Overload & Looping Cluster** | 🟢 Selesai (100%) | Eliminasi rekursif cURL loop `/storage`, beban server kembali stabil 1-3% |
| **Migrasi Konfigurasi IP Server 1 AMK** | 🟢 Diperbarui | Transisi IP ke `38.68.69.225`, konfigurasi cluster & deploy terupdate |
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

### 9. Pembaruan Konfigurasi & Migrasi IP Server 1 (PT AMK)
- [x] **Transisi IP Address Node Server 1**:
  - Mengupdate IP Server 1 AMK (Jagoan Hosting / Beon Intermedia) dari IP lama `38.103.170.235` ke IP baru **`38.68.69.225`**.
- [x] **Sinkronisasi Kode & Konfigurasi Cluster**:
  - `public/deploy-production.php`: Menyesuaikan target deployment IP dan entri `/etc/hosts` cluster ke `38.68.69.225`.
  - `config/multiserver.php`: Menyesuaikan `SERVER_1_INTERNAL_IP` ke `38.68.69.225`.
  - `routes/web.php`: Menyesuaikan IP node Server 1 pada landing page portal cluster (`commit 785d05a`).
- [x] **Verifikasi Jaringan & Port Firewall**:
  - Panduan membuka port 80 (HTTP) dan port 443 (HTTPS) pada UFW/iptables dan modul Security aaPanel untuk memastikan akses publik `amk.esa-solutions.id` dapat dijangkau dari internet luar.

---

## 🎯 Rencana Pengembangan Selanjutnya (Next Milestones)

| No | Target Fitur / Peningkatan | Prioritas | Estimasi / Keterangan |
| :---: | :--- | :---: | :--- |
| 1 | **Optimasi Export Excel Dataset Masif**: Menambahkan queue background job untuk ekspor data di atas 50.000 baris agar tidak membebani web worker. | Medium | Laravel Queues / Spatie Simple Excel |
| 2 | **Odoo Studio Drag-and-Drop Enhancement**: Penyempurnaan simpan tata letak kustom widget dashboard untuk pengguna portal non-teknis. | Medium | Penyempurnaan UI Modal Studio |
| 3 | **Filter Multi-Select Outlet / Toko**: Pilihan multi-toko via Select2/TomSelect dengan AJAX remote search untuk mencari langsung dari 14.000 toko. | Low | Peningkatan usability dropdown toko |
| 4 | **Dashboard Analitik Tren Penjualan Tahunan**: Visualisasi perbandingan offtake YoY (Year-over-Year) antara tahun 2024 vs 2025. | Low | ApexCharts integrasi |

---

*Terakhir diperbarui: 3 September 2026*  
*Pengembang: Digital Galery / DGSoft - Tim Attendance ESA*
