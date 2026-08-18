# Master Implementation Plan (Roadmap Lanjutan)

Berdasarkan pengecekan ulang sistem pada 5 Agustus 2026 sesuai dengan panduan PPTX, berikut adalah Roadmap fitur-fitur yang akan diimplementasikan secara bertahap:

---

## ✅ Tahap 1: Penyelarasan Antarmuka (UI Overhaul) & Vacant Status (SELESAI)
*Tahap ini berfokus menyelesaikan PR dari Fase 1 agar seluruh aplikasi mobile tampil seragam.*
1. **Penyelarasan Desain UI Mobile**:
   - Mengubah style Halaman **Login**, **Check-in/out**, **Itinerary**, **Permit**, **Tracking History**, dan **Profile** agar desainnya se-modern Dashboard baru (penggunaan Card, border-radius, font dinamis sesuai `appColor`).
2. **Perbaikan "Vacant Status" TL**:
   - Memperbaiki perhitungan "Vacant" di `DashboardApiController` agar menampilkan posisi apa yang kosong dan sudah berapa lama kosongnya (berdasarkan data histori karyawan/jabatan).

## ✅ Tahap 2: Manajemen Target HK & Payslip (SELESAI)
*Tahap ini menghubungkan data operasional dasar antara Admin dan Karyawan.*
1. **Menu Target HK (Admin)**:
   - Membuat *Filament Resource* untuk tabel `work_targets` agar Admin / HR bisa menginput target HK per karyawan atau massal per bulan. (Termasuk fitur import Excel).
2. **Sistem Payslip (Slip Gaji)**:
   - **Backend**: Membuat tabel `payslips` (Bulan, Tahun, Karyawan, File PDF/Data Gaji).
   - **Admin**: Membuat *Filament Resource* untuk manajemen upload payslip karyawan.
   - **Mobile**: Membuat halaman `payslip_screen.dart` agar karyawan bisa mendownload/melihat slip gaji mereka.

## ✅ Tahap 3: Laporan Sales (OOS, Plano, Promo) & Pembersihan Fitur Lama (SELESAI)
*Tahap ini berfokus pada pekerjaan lapangan (Sales/SPG/MD).*
1. **Backend & Admin**:
   - Membuat tabel database untuk menyimpan form laporan toko: `Out of Stock (OOS)`, `Planogram (Plano)`, dan `Promotion (Promo)`.
   - Membuat *Filament Resource* untuk melihat dan memonitor hasil laporannya.
   - Menghapus menu dan fitur lama *Sales Pipeline* (B2B CRM) yang sudah tidak terpakai.
2. **Mobile**:
   - Menghidupkan halaman `sales_report_screen.dart` dan menyambungkannya ke API untuk mengirim form OOS, Plano, dan Promo beserta bukti fotonya.
   - Menyelaraskan desain *UI Sales Report* agar sama dengan tema *History* Nexa Attendance (Light/Dark mode, app bar, font, dan format form yang rapi).
   - Menghapus *navigasi* dan *file* yang terkait dengan *Sales Pipeline*.

## ✅ Tahap 4: Laporan Tren Analitik (Web Admin) (SELESAI)
*Tahap ini berfokus pada permintaan laporan kompleks di presentasi PPTX.*
1. **Laporan Man Power**: Tren jumlah orang per perusahaan, region, dan TL (Jan-Des).
2. **Laporan Turn Over**: Tren keluar/masuk karyawan per bulan.
3. **Laporan Mandays**: Target vs Aktual hari kerja per region/perusahaan.
   - Diimplementasikan menggunakan *Filament Custom Page & Chart* dan bisa diekspor ke Excel (Export Maatwebsite).

---
*Catatan Keamanan & Penyempurnaan Sistem (Selesai & Aman):*
- *No Fake GPS & Developer Mode terdeteksi dan diblokir menggunakan `safe_device`.*
- *Akses Galeri untuk Absen sudah diblokir, hanya menggunakan Kamera (`ImageSource.camera`).*
- *Pesan dari pusat sudah dicover menggunakan fitur Blast Info.*
- **✅ Peningkatan Live Tracking (Selesai 6 Agustus 2026):** Menerapkan mekanisme **Offline Queue** menggunakan `SharedPreferences` untuk mengatasi kehilangan sinyal saat tracking, serta menambah **Data Quality Filter** (akurasi GPS) untuk mencegah titik koordinat yang melompat. Aplikasi mobile berhasil di-build ke versi **1.0.39**.
- **✅ Pembaruan Laporan & Optimasi (Selesai 11 Agustus 2026):** 
  - Menyelesaikan 3 jenis laporan (Man Power, Turn Over, Mandays).
  - Melakukan perombakan total pada query Database menggunakan pendekatan Array Mapping/In-Memory Calculation untuk mengatasi beban lambat (N+1 queries) di ketiga laporan. 
  - Memperbaiki Error 500 di Dashboard utama akibat bentrok Widget (*isDiscovered* = false).
  - Merapikan gaya desain (CSS/Padding) tabel khusus untuk *custom views* pada Filament sehingga lebih profesional dan rapi saat dieksekusi browser.
- **✅ Perbaikan Bug, Filter Visit & Sistem Kuota Cuti (Selesai 13 Agustus 2026):**
  - **Logo Admin**: Diperbaiki menggunakan metode *RenderHook* Filament alih-alih brandLogo agar support injeksi *base64 image*.
  - **Filter Lokasi Visit**: Menyempurnakan API mobile agar lokasi visit yang sudah dikunjungi hari ini otomatis hilang dari daftar dropdown.
  - **Hari Ini (Log Absensi)**: Melampirkan data *work location* ke log *Check-in* dan *Check-out* berdasarkan jadwal harian karyawan.
  - **Deploy Script**: Memperbaiki fungsi output streaming `deploy.php` dengan *buffer padding* agar berjalan *real-time* seperti terminal.
  - **Sistem Kuota Cuti (APK v1.0.80 (2026-08-18)):**
    - [x] Perombakan metode visit: Add Area, Work Location, and Brand (Principal) selection.
    - [x] "Visit Now" feature to immediately execute visit.
    - [x] "Scheduled" visit with future date selection.
    - [x] Visit Report Form locked until submitted.
    - [x] Backend API overhaul for Visit Now and auto-visit-out in `storeVisitReport`.
    - [x] Added `met_with` and `position` to Visit Reports.
  - **v1.0.79 (2026-08-18)**: 
    - Penambahan logika backend dan frontend untuk Kuota Cuti Tahunan (maks 12 hari/tahun, minimal kerja 1 tahun, pengajuan H-14). 
    - Penambahan validasi batasan maksimal hari untuk setiap kategori Cuti Peraturan (misal: Menikah maks 3 hari, Istri Melahirkan maks 2 hari, dll).
    - *Dropdown* Cuti Tahunan secara otomatis disembunyikan apabila masa kerja pengguna belum mencapai 1 tahun.
  - **Versi 1.0.82 (Current)**:
    - [x] Tambahan input form `Target Report (Qty / Values)`, `Actual (Qty / Value)`, `Deadline` di form Laporan Visit (Visit-Out).
  - **Versi 1.0.81**:
    - [x] Validasi tombol Visit Now hanya bisa digunakan jika sudah check-in.
    - [x] Tambah inputan Type Visit, Type Meeting, dan Agenda pada Form Visit.
    - [x] Ubah label "Itinerary" menjadi "Visit" di seluruh aplikasi.
    - [x] Ubah label "Nexa Attendance" menjadi "ESA groups" di Menu Lainnya.
  - **Penyempurnaan Fitur Lembur & UI (APK v1.0.74)**:
    - Penambahan logika validasi "Mulai Lembur" hanya bisa dilakukan 1 jam setelah jam pulang (kecuali Driver yang dapat langsung memulai).
    - Perbaikan *bug Unauthenticated* pada saat karyawan mengajukan lembur.
    - Sinkronisasi desain halaman *Lembur* dan *Laporan Visit* agar selaras dengan gaya visual (minimalis, mode gelap/terang, warna dinamis) halaman lainnya.
    - Perbaikan *refresh* real-time di Itinerary sehingga tombol *Visit* langsung muncul sesaat setelah jadwal dibuat tanpa perlu merestart aplikasi.
    - Fitur Live Chat (APK v1.0.76)**:
    - Penambahan fitur komunikasi real-time antara Karyawan dan Admin.
    - Karyawan dapat memulai obrolan via *Floating Action Button* berdesain ala WhatsApp di halaman utama (Dashboard).
    - Halaman Admin dilengkapi antarmuka *Live Chat* responsif menggunakan Filament dan Livewire.
    - Menggunakan *Laravel Echo* dan *Pusher/Reverb* untuk sinkronisasi pesan secara instan.
    - Fix Live Chat admin layout to be full width.
    - Menyelaraskan desain *Chat Screen* di aplikasi mobile agar konsisten dengan tema gelap (dark mode) dan visual keseluruhan.
  - **Optimasi & Bug Fix Live Chat (Selesai 14 Agustus 2026):**
    - Memindahkan eksekusi pengiriman *Push Notification* (FCM) dan *WebSocket Broadcast* ke `app()->terminating()` agar antarmuka Live Chat Admin tidak mengalami jeda (delay) saat membalas pesan.
    - Memperbaiki bug pada *Sidebar* Live Chat Admin agar data Jabatan dan Area Karyawan tampil dengan benar dengan mengoptimalkan *Eager Loading* dan menangani *null safety*.
    - Memperbaiki notifikasi Lonceng (Database Notification) di Web Admin yang gagal masuk karena pemanggilan class `Filament\Notifications\Actions\Action` yang tidak tersedia di konteks API.
    - Mengoptimalkan perhitungan lencana (badge) pesan belum terbaca agar tidak menimbulkan N+1 Query dengan memanfaatkan *collection filter*.
    - Memperbaiki bug tata letak (CSS Flexbox) yang menyebabkan *field input* chat tersembunyi di luar layar ketika daftar pesan sudah sangat panjang.
  - **Perbaikan UI Live Chat & Update (Selesai 18 Agustus 2026, APK v1.0.79):**
    - Membatasi kemunculan *Floating Action Button* (FAB) Live Chat hanya di halaman Dashboard (index 0) agar tidak menutupi tombol pada halaman lain.
    - Mengganti label header di halaman Live Chat dari "Admin / HR Support" menjadi "IT Helpdesk".
  - **Penyelarasan Prosedur Visit Baru (Selesai 18 Agustus 2026, APK v1.0.80 - v1.0.84):**
    - Menambahkan fitur penghitung mundur/naik durasi kunjungan (Visit-In ke Visit-Out).
    - Membatasi pemilihan tanggal jadwal kunjungan hanya untuk hari ini dan hari ke depan (tidak bisa backdate).
    - Melarang Visit-In sebelum Check-In.
    - Menyesuaikan *naming convention* di seluruh aplikasi ("Itinerary" menjadi "Visit", "Nexa Attendance" menjadi "ESA groups").
    - Memperbarui Form Visit Report secara bertahap dengan kolom-kolom baru (Target Qty, Target Value, Actual Qty, Actual Value, Deadline, Notes).
    - Menambahkan migration database untuk tipe target laporan dan merefleksikannya di Filament Web Admin.
    - **(v1.0.84)** Memperbaiki logika tombol Check-Out agar tidak tertutup (disable) selama belum melakukan Visit-In, meskipun ada jadwal kunjungan.
    - **(v1.0.84)** Menambahkan tombol **Batalkan Jadwal** pada menu Visit (Itinerary) agar pengguna dapat membatalkan rencana kunjungan yang belum dilaksanakan.
    - **(v1.0.84)** Menyembunyikan card menu Kunjungan Lapangan di Dashboard otomatis jika semua kunjungan hari ini sudah selesai dilaksanakan (Visit Out).
    - **(v1.0.84)** Memperbaiki isu layar hitam blank setelah proses submit laporan kunjungan dengan melakukan rerouting ulang ke halaman utama (Dashboard).
    - **(v1.0.85)** Memperbaiki fitur Batal Jadwal dengan menyembunyikan tombol Cancel pada jadwal kunjungan yang sudah diselesaikan (Visit-Out).
    - **(v1.0.86)** Mengotomatisasi perpindahan layar langsung ke halaman form `Visit-In` ketika pengguna memilih jenis *Visit Now* dari form *Add Itinerary*.
    - **(v1.0.86)** Menonaktifkan (bypass) validasi radius batas lokasi saat `Visit-In` khusus jika `Meeting Type` yang dipilih di itinerary adalah "Online" (koordinat riil tetap tercatat).
    - **(v1.0.87)** Memperbarui `TeamStatsWidget` ("Team Overview") agar dapat diakses dan terlihat oleh semua karyawan yang memiliki sub-ordinat, bukan hanya karyawan dengan posisi "TL".
    - **(v1.0.87)** Menambahkan tampilan detail informasi jarak (radius actual) terhadap lokasi jadwal pada riwayat `Detail Aktivitas` (saat Check-in, Check-out, Visit-In, Visit-out).
    - **(v1.0.88)** Memperbaiki isu notifikasi push yang tampil ganda (duplikat) di sistem Android dengan memfilter token *device* yang duplikat di backend dan menyatukan ID notifikasi lokal berdasarkan judul pesan.

---

## 🟡 Tahap Khusus: Peningkatan Infrastruktur (Skala 20.000 Pengguna) (Sebagian Selesai)
*Tahap ini memastikan aplikasi tidak down saat menerima beban ribuan request absensi di jam sibuk.*
1. ✅ **Instalasi Laravel Octane (SELESAI)**: 
   - Mengubah engine server dari standar ke Octane (Swoole / FrankenPHP) untuk performa tinggi.
2. ✅ **Penerapan Redis (SELESAI)**:
   - Mengalihkan Session, Cache, dan Queue agar menggunakan Redis, bukan File lokal.
3. 🔴 **Penyimpanan Cloud (S3) (BELUM)**:
   - Integrasi sistem penyimpanan (AWS S3 / GCS / DigitalOcean Spaces) agar foto absensi tidak membebani server aplikasi.

---

## 🟡 Tahap 5: Face Recognition & Realtime Notification (FCM) (Sebagian Selesai)
*Tahap ini berfokus pada peningkatan akurasi absensi dan penyampaian informasi real-time.*
1. 🔴 **Face Recognition (BELUM)**:
   - Integrasi sistem deteksi dan pengenalan wajah pada saat Check-in / Check-out.
   - Pendaftaran wajah karyawan via admin atau aplikasi (enrollment).
2. ✅ **Realtime Notifications (Firebase Cloud Messaging / FCM) (SELESAI)**:
   - Integrasi FCM pada aplikasi mobile (Flutter) untuk menerima notifikasi.
   - Penyesuaian backend (Laravel) untuk mengirim push notification (blast, alert absensi, approval).

---
- **✅ Penyempurnaan Integrasi Odoo & Bug Fix (18 Agustus 2026):**
  - **Bug Fix**: Memperbaiki isu kehabisan memori (*Memory Exhausted*) pada form Edit Employee di web Admin dengan menerapkan *searchable()* pada seluruh field Dropdown (Select) berelasi besar.
  - **Odoo Sync**: Menambahkan logika paginasi (*do-while loop*) 500 data/siklus pada `OdooSyncService` untuk sanggup menarik lebih dari 1000 data (menghindari limitasi *request* XML-RPC).
  - **Dashboard & Tabel**: Memperbaiki label *Dashboard* dan kolom tabel Employee agar menggunakan relasi nama Principal alih-alih Company. Mengubah desain halaman *Odoo Sync* menggunakan komponen Filament bawaan.
  - **Lembur (Overtime)**: Memperbaiki sistem zona waktu (*timezone*) pada validasi Lembur dan menerapkan *bypass backend* sementara untuk menginvestigasi *hardcoded restriction* di aplikasi *mobile*.
  - **Rencana Mendatang (Automated Odoo Sync)**: Telah merancang arsitektur implementasi *Task Scheduler* dan injeksi filter `write_date >= odoo_last_sync_at` untuk sinkronisasi Odoo otomatis & inkremental di *background*.
