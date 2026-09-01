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

- **✅ Penyempurnaan Integrasi Odoo & Bug Fix (18 - 19 Agustus 2026):**
  - **Bug Fix**: Memperbaiki isu kehabisan memori (*Memory Exhausted*) pada form Edit Employee di web Admin dengan menerapkan *searchable()* pada seluruh field Dropdown (Select) berelasi besar.
  - **Odoo Sync (Paginasi)**: Menambahkan logika paginasi (*do-while loop*) 500 data/siklus pada `OdooSyncService` untuk sanggup menarik lebih dari 1000 data (menghindari limitasi *request* XML-RPC).
  - **Odoo Sync (Pencocokan NIK / No. KTP & Anti-Duplikat)**:
    - Mengubah algoritma pencarian karyawan pada sinkronisasi Odoo agar memprioritaskan pencocokan via **NIK / No. KTP** (`identification_id` / `employee_no`) secara global lintas company.
    - Jika karyawan dengan NIK yang sama sudah ada, data lama akan **diperbarui (ter-replace)** ke Company & Jabatan baru, dengan tetap mempertahankan foto profil, password login mobile, dan relasi data absensi/aktivitas lama.
    - Menambahkan fitur **Auto-Merge Duplikat & Tombol Pembersih NIK Duplikat** pada halaman Odoo Sync untuk otomatis mendeteksi, menggabungkan riwayat, dan membersihkan baris ganda di tabel database.
  - **Dashboard & Tabel**: Memperbaiki label *Dashboard* dan kolom tabel Employee agar menggunakan relasi nama Principal alih-alih Company. Mengubah desain halaman *Odoo Sync* menggunakan komponen Filament bawaan.
  - **Lembur (Overtime)**: Memperbaiki sistem zona waktu (*timezone*) pada validasi Lembur dan menerapkan *bypass backend* sementara untuk menginvestigasi *hardcoded restriction* di aplikasi *mobile*.
  - **Odoo Sync (Automasi Cron & Halaman Laporan Sync - SELESAI 19 Agustus 2026)**:
    - [x] Implementasi Artisan Command `php artisan odoo:sync` dengan auto-loop seluruh active company yang terisi konfigurasi Odoo (skip otomatis untuk company kosong).
    - [x] Pendaftaran Schedule Task harian otomatis di `routes/console.php` (setiap 02:00 AM).
    - [x] Tabel database `odoo_sync_logs` & model `OdooSyncLog` untuk mencatat metrik per batch: `new_count`, `update_count`, `resign_count`, `total_employee_count`, durasi, serta snapshot detail nama karyawan.
    - [x] Pembuatan Filament Page & View **Laporan Sinkronisasi Odoo** (`/admin/odoo-sync-report`) dengan 4 kolom utama: **Data Employee New**, **Data Employee Update**, **Data Employee Resign**, dan **Total Employee per Company** sesuai mockup.
    - [x] Tabel Riwayat Sinkronisasi (Sync History) dengan modal detail individual per batch.
    - [x] Endpoint Web Cron `/cron/odoo-sync` untuk kemudahan automasi via URL-task aaPanel / cPanel.
  - **Odoo Sync (Dirty Checking & Default Password 123456 - SELESAI 19 Agustus 2026)**:
    - [x] Implementasi *Dirty Checking* (`$primary->getDirty()`) pada `OdooSyncService` agar kategori **Data Employee Update** hanya menghitung karyawan yang benar-benar mengalami perubahan data riil (promosi, mutasi cabang/area, ganti nomor kontak, dll.), mencegah penggelembungan angka update ke seluruh populasi karyawan.
    - [x] Penetapan password default **`123456`** (`Hash::make('123456')`) untuk seluruh akun karyawan baru maupun karyawan lama yang belum memiliki password saat disinkronkan dari Odoo.

---

## ✅ Tahap 6: Fitur Absensi & Laporan Meeting (SELESAI 19 Agustus 2026, APK v1.0.89 - v1.0.91)
*Tahap ini menyediakan fitur penjadwalan meeting, presensi Meet-In/Meet-Out, dan pelaporan hasil rapat.*
1. **Database & Backend Models**:
   - Membuat tabel `meetings`, `meeting_participants`, dan `meeting_attendances`.
   - Mendukung tipe `online` (dengan kolom `meeting_link` Zoom / GMeet / Teams) dan `offline` (dengan master lokasi, koordinat, dan `radius_meter` lock).
2. **Web Admin (Filament Resource `MeetingResource`)**:
   - Penjadwalan meeting lengkap: Judul, Tanggal, Jam Mulai/Selesai, Jenis (Online/Offline), Link / Lokasi & Radius Lock.
   - Filter peserta dinamis: Dropdown pembantu filter by **Principal** dan **Area/Branch**.
   - Multi-select peserta dengan pencarian cepat *case-insensitive* berdasarkan **Nama Karyawan** atau **No. KTP / NIK**.
3. **Backend API (`MeetingController`)**:
   - `GET /api/meetings/today`: Mengambil daftar meeting karyawan hari ini beserta status kehadiran.
   - `POST /api/meetings/meet-in`: Validasi radius untuk meeting offline (bypass radius untuk online), pencatatan koordinat & foto, dan update log aktivitas.
   - `POST /api/meetings/meet-out`: Pengiriman catatan / notulensi hasil meeting, foto bukti, durasi, dan penyelesaian meeting.
   - `GET /api/meetings/history`: Riwayat meeting karyawan.
   - Integrasi otomatis aktivitas `meet_in` dan `meet_out` ke *Aktivitas Hari Ini* dan *History*.
4. **Mobile App (Flutter `att-mobile`)**:
   - Card **Jadwal Meeting Hari Ini** di Dashboard dengan informasi waktu, lokasi/link, status, dan tombol **Meet-In**.
   - **(v1.0.90)** Proses Meet-In diselaraskan penuh dengan Check-In / Visit-In: Menampilkan Peta (Maps), Geofence visual, Radius GPS, dan Wajib Foto Selfie Kamera.
   - **(v1.0.91)** Validasi jadwal meeting yang telah lewat jamnya otomatis disembunyikan/tidak dapat di-Meet-In lagi.
   - **(v1.0.91)** Proteksi akses: Menyembunyikan tombol "Lihat Laporan Hasil Meeting" dan halaman hasil meeting dari aplikasi karyawan.
   - **(v1.0.91)** Integrasi log aktivitas `meet_in` dan `meet_out` ke timeline **Detail Aktivitas** di Halaman History.

---

## ✅ Tahap 7: Check-In Lokasi Terjadwal & Perbaikan Form Admin (SELESAI 19 Agustus 2026, APK v1.0.92)
1. **Check-In Lokasi Terjadwal (Mobile)**:
   - Mengganti tab "Lokasi Sekitar" pada halaman Check-In menjadi **"Lokasi Terjadwal"**.
   - Menampilkan daftar lokasi dinamis berdasarkan jadwal visit atau jadwal meeting karyawan pada hari tersebut (di luar lokasi check-in utama).
   - Validasi wajib mengisi **Catatan** ketika karyawan melakukan check-in menggunakan Lokasi Terjadwal.
   - Render dinamis polygon / lingkaran geofence sesuai koordinat lokasi terjadwal yang dipilih.
2. **Penyelarasan Form Employee (Web Admin)**:
   - Menyelaraskan field Company pada form detail/edit employee agar menampilkan nama Principal yang sama persis dengan tabel data karyawan.
   - Otomatisasi sinkronisasi `company_id` dari `principal_id` pada Create & Edit Employee.
   - Memperbaiki query relasi Department pada form karyawan.
3. **Pencarian Peserta Meeting**:
   - Mengubah query pencarian peserta rapat di Admin Filament menjadi *case-insensitive* untuk fleksibilitas pencarian nama karyawan.

---

## ✅ Tahap 8: Monitoring Tim Belum Check-In & Stabilisasi History (SELESAI 19 - 20 Agustus 2026, APK v1.0.93)
1. **Stabilisasi Endpoint Riwayat Absensi (`/api/attendance/history`)**:
   - Memperbaiki error handling dan parsing metadata JSON pada log aktivitas meeting & presensi.
   - Mencegah error 500 jika terjadi kegagalan pembacaan tanggal cutoff departemen atau null relationship.
   - Memastikan Dashboard dan Halaman Riwayat di aplikasi mobile selalu menampilkan data jam check-in/out dan timeline aktivitas dengan stabil.
2. **Perubahan Judul Card Dashboard ("Tim Belum Check-In")**:
   - Mengubah label card grid pada widget Team Overview dari `Vacant (Kosong)` menjadi **`Tim Belum Check-In`**.
3. **Halaman Khusus Monitoring Tim Belum Check-In Mobile (`TeamUncheckedScreen`)**:
   - Menampilkan daftar anggota tim yang tidak melakukan check-in dalam 7 hari terakhir secara lengkap:
     - Nama Lengkap & NIK
     - Jabatan
     - Prinsiple
     - Area / Cabang
     - Tanggal & Status Terakhir Check-In
     - Rincian chip tanggal tidak hadir dalam 7 hari terakhir
   - Dilengkapi fitur **Pencarian Real-Time** dan **Filter Cepat** (*Semua*, *Belum Check-In Hari Ini*, *≥ 3 Hari Tidak Hadir*, *Belum Pernah Hadir*).
4. **Backend API**:
   - Menambahkan endpoint `GET /api/dashboard/team-unchecked` dengan eager loading relasi lengkap dan agregasi presensi 7 hari terakhir.
5. **Rilis APK Versi 1.0.93**:
   - Sukses build APK release `app-release-1.0.93.apk`.
6. **Halaman Monitoring Tim Belum Check-In Web Admin (`/admin/team-unchecked-monitoring`)**:
   - **Tampilan 1 (Matriks Prinsiple vs Area)**: Matriks pivot tabel interaktif dengan baris Prinsiple dan kolom Area/Cabang. Cell menampilkan jumlah karyawan belum check-in dan dapat diklik untuk mem-filter rincian data karyawan secara instan.
   - **Tampilan 2 (Rincian Data Karyawan)**: Tabel detail lengkap dengan kolom `Nama Karyawan`, `Jabatan`, `Prinsiple`, `Area`, dan kumpulan chip `Tgl Tidak Check-in (7 Hari Terakhir)`.
   - **Filter & Kontrol Lengkap**: Filter dropdown Prinsiple, filter dropdown Area/Cabang, Filter Cepat Status, Pencarian Real-Time (Live Search), dan Export ke Excel (`.xlsx`).
   - **Penyelarasan Tema**: Menggunakan palette warna dan styling native Filament (Light Mode & Dark Mode).

---

## ✅ Tahap 9: Visit Schedule Kalender, Import Attendance, Diagnostik Odoo & Bulk SPV (SELESAI 20 Agustus 2026)
*Tahap ini berfokus pada perombakan modul Visit Schedule, penyesuaian data absensi massal, sinkronisasi metrik Odoo, dan manajemen data karyawan.*

1. **Perhitungan Work Targets (Target HK) Berdasarkan Periode Cut-Off:**
   - Perhitungan pencapaian Target HK karyawan kini dihitung secara akurat berdasarkan rentang tanggal periode cut-off aktif departemen (bukan sekadar awal dan akhir bulan kalender).

2. **Perombakan Modul Visit Schedule (Jadwal Kunjungan Lapangan):**
   - **Penyelarasan Label:** Mengganti semua label *Itineraries* di seluruh sistem Web Admin menjadi **Visit Schedule**.
   - **Tampilan Kalender Interaktif (Calendar View):**
     - Mengubah halaman Visit Schedule menjadi kalender bulanan interaktif.
     - Mengklik tanggal kosong akan langsung membuka modal *Form Add Visit Schedule*.
     - Menampilkan chip jadwal kunjungan dengan format: `Nama Karyawan (Jabatan - Area)`.
     - Mengklik nama karyawan membuka Modal Detail Schedule Visit berukuran lebar (980px) dengan daftar lokasi, status, dan penanda check-in.
   - **Pembersihan Tombol Lama:** Menghapus tombol *Create For Department* dan *Create For Working Groups*.
   - **Fitur Jadikan Lokasi Check-In (`is_checkin_location`):**
     - Menambahkan opsi penanda pada Form Tambah Jadwal Visit dan Import Excel agar lokasi visit dapat berfungsi sebagai lokasi check-in otomatis jika karyawan belum memiliki jadwal kerja di tanggal tersebut.
   - **Import Excel Visit Schedule:**
     - Pembuatan template resmi `Template_Import_Visit_Schedule.xlsx` dengan header NIK, multi-tanggal, master lokasi, dan penanda lokasi check-in.

3. **Fitur Import Data Attendance (Penyesuaian Absensi / Safe Adjustment):**
   - Menyediakan fitur import file Excel untuk mengisi atau me-replace data kehadiran karyawan yang tercatat tidak check-in / ALPHA / kosong.
   - Mendukung input multi-tanggal (`tanggal_mulai` s/d `tanggal_akhir`), jam masuk, jam keluar, status, dan catatan alasan penyesuaian.
   - **Proteksi Data Asli Mobile (*Safe Guard*):** Sistem secara otomatis mendeteksi dan **TIDAK AKAN menimpa/me-replace** data karyawan yang sudah memiliki jam check-in asli dari aplikasi mobile.
   - **Penanda Khusus (*Distinct Badge*):**
     - Menampilkan badge ungu **`⚡ IMPORT`** pada sel matriks Attendance Roster.
     - Menampilkan banner alert info penyesuaian manual pada Modal Detail Absensi.
     - Menampilkan icon petir ungu (`⚡`) pada tabel data Attendances.
   - Pembuatan template resmi `Template_Import_Attendance.xlsx`.

4. **Optimasi & Diagnostik Sinkronisasi Odoo ERP:**
   - **Pencocokan NIK + Prinsiple & Proteksi Lintas Entitas (*Cross-Entity Active Protection*):**
     - Pencocokan pembaruan data karyawan kini diprioritaskan menggunakan kombinasi **NIK (`employee_no`) + Prinsiple (`principal_id`)**.
     - **Aturan Proteksi Akun Aktif:** Jika seorang karyawan saat ini **aktif di entitas/prinsiple lain (misal: PT ATB)**, data arsip / riwayat *resign* lama dari entitas lain (misal: PT AMK) **TIDAK AKAN menimpa (*overwrite*) akun aktifnya menjadi resign**.
   - **Auto-Trim Parameter:** Membersihkan spasi tak kasat mata dari URL, Database Name, Username/Email, dan API Key Odoo.
   - **Diagnostik Database Odoo:** Menambahkan pendeteksi otomatis daftar database yang aktif di server Odoo jika terjadi `KeyError` saat autentikasi XML-RPC.
   - **Deteksi Karyawan Resign / Archived:** Mengaktifkan parameter konteks `'context' => ['active_test' => false]` dan pembacaan `departure_date` pada Odoo XML-RPC `search_read` sehingga data karyawan yang benar-benar resign di entitasnya otomatis masuk ke kategori **Data Employee Resign**.
   - **Refleksi Data Resign Real-Time:** Menampilkan jumlah dan detail daftar nama karyawan resign per entitas secara dinamis pada halaman Laporan Odoo Sync.
   - **Sinkronisasi Grand Total Real-Time:** Menyelaraskan angka grand total karyawan di Dashboard utama (24.190 Aktif • 2.810 Resign/Non-Aktif) dengan Halaman Laporan Odoo Sync secara real-time.

5. **Penyempurnaan Manajemen Karyawan (Employees Resource):**
   - **Filter Status Karyawan & Isolasi Pencarian (*Search Isolation*):**
     - Menambahkan filter dropdown **Status Karyawan** dengan pilihan: `Aktif (Default)`, `Resign / Non-Aktif`, dan `Semua Status`.
     - Mode pencarian (*Search Bar*) terisolasi: Saat filter Aktif dipilih, pencarian hanya memproses karyawan aktif (tidak tercampur dengan data karyawan resign). Untuk mencari karyawan resign, user cukup memilih filter `Resign / Non-Aktif`.
   - **Kolom Device Terhubung:** Mengganti kolom *Employment Status* dengan kolom **Device** yang menampilkan model handphone yang terhubung ke akun karyawan (dengan icon status dan tooltip Device ID).
   - **Atur SPV / Leader Massal (*Bulk Action*):** Fitur bagi Admin untuk mencentang banyak karyawan sekaligus dan menetapkan nama Supervisor / Leader secara serentak via modal pemilihan SPV.
   - **Hapus SPV Massal (*Bulk Action*):** Fitur untuk mengosongkan supervisor pada banyak karyawan yang dipilih sekaligus.
   - **Kolom Supervisor Aktif:** Mengaktifkan tampilan kolom *Supervisor / Leader* pada tabel utama Employees agar langsung terlihat.
   - **Fitur Hapus Data Karyawan Resign (Header Action & Bulk Action):**
      - Header Action `Hapus Karyawan Resign` dengan modal filter lengkap (Prinsiple, Company, Area, Tanggal Resign), preview jumlah karyawan resign vs aktif, opsi *Soft Delete* (Trash) atau *Permanent / Force Delete*, serta konfirmasi proteksi data.
      - Bulk Action `Hapus Karyawan Resign Terpilih` untuk menghapus data resign dari baris tabel yang dicentang (otomatis melewati karyawan aktif).
      - Row Actions `DeleteAction`, `ForceDeleteAction`, dan `RestoreAction` pada setiap baris data tabel.

## ✅ Tahap 10: Penguncian Parameter Odoo Sync (NIK + Prinsiple) & Restorasi Akun (SELESAI 21 Agustus 2026)
*Tahap ini memastikan tidak ada data karyawan yang tertimpa secara keliru akibat nomor urut Odoo.*

1. **Penguncian Parameter Pencocokan Odoo Sync (NIK + Prinsiple Wajib Sama):**
   - Mengubah mekanisme pencarian akun karyawan pada `OdooSyncService` agar **WAJIB HANYA** mencocokkan jika **NIK (`employee_no`)** DAN **Prinsiple (`principal_id`)** keduanya sama persis.
   - Menghapus pencocokan bebas `OR where('odoo_id', ...)` lintas entitas/NIK yang sebelumnya dapat menimpa akun lokal yang memiliki nomor urut Odoo sama.
   - Jika kombinasi NIK + Prinsiple belum ada, sistem secara otomatis membuat record baru (`Employee::create`).
   - Penyesuaian `cleanupAllDuplicateEmployees` agar hanya membersihkan duplikat yang memiliki NIK dan Prinsiple yang sama persis.

2. **Konsolidasi Akun & Pengalihan Riwayat Presensi ke NIK Asli (`3528042504850003`):**
   - Menetapkan akun **Abdurrahman Jamil** pada **PT ANUGRAH TALENTA BERKARYA (IT Surabaya)** sebagai akun **AKTIF TUNGGAL** yang terhubung ke perangkat `TECNO TECNO KM7`, foto profil, dan seluruh riwayat presensi/aktivitas.
   - Mengubah status akun lama di **PT ARINA MULTI KARYA** menjadi **Non-Aktif / Resign** serta melepas binding perangkatnya.
   - Menghapus record sementara (`EMP-JAMIL-001`) dan memastikan data **Eka Septiani** (NIK `7402256409960001`) tetap terdaftar bersih pada entitas aslinya (PT ALVA KARYA PERKASA - ELINA Makassar).

---

## ✅ Tahap 11: Pengikatan Department ke Prinsiple & Auto-Create via Odoo Sync (SELESAI 21 Agustus 2026)
*Tahap ini mengubah relasi Department agar terikat langsung ke Prinsiple dan dibuat otomatis saat sync data karyawan Odoo.*

1. **Relasi Department ke Prinsiple:**
   - Menambahkan kolom `principal_id` pada tabel `departments` dan membuat relasi `principal()` pada model `Department` serta `departments()` pada model `Principal`.
   - Mengganti kolom tampilan dan form pemilihan dari `Company` menjadi **Prinsiple** pada menu Master Data **Departments**.
   - Menambahkan filter pencarian berdasarkan Prinsiple pada tabel Departments.
   - Menambahkan filter dinamis pada form Employee agar pilihan Department dan Position menyesuaikan Prinsiple yang dipilih.

2. **Auto-Create Department via Odoo Sync:**
   - Menyesuaikan `OdooSyncService` agar secara otomatis membuat (*firstOrCreate*) record Department berdasarkan data department dari Odoo (`rec['department_id']`) yang terikat ke `principal_id` karyawan terkait.

---

## ✅ Tahap 11.2: UI/UX Maskot 3D Superhero Time Card Dashboard, Sizing Server 23.511 Karyawan & Presentasi PPTX (SELESAI 21 Agustus 2026)
*Tahap ini mencakup pembaruan visual kartu jam dashboard aplikasi mobile dengan maskot 3D Superhero, analisis kapasitas & spesifikasi server untuk kuota 23.511 karyawan, serta pembuatan file presentasi PowerPoint eksekutif.*

1. **Pembaruan UI/UX Kartu Jam (Time Card) Dashboard Mobile:**
   - Menambahkan grafis maskot 3D ESA bertema superhero dengan pose **tersenyum & mengedipkan mata (*winking*) sambil mengarahkan tangan (*pointing*) ke arah jam digital**.
   - Maskot diekstrak dengan transparansi halus (*alpha channel*) beresolusi tinggi sehingga menyatu sempurna dengan gradien warna kartu yang dinamis mengikuti tema General Setting.
   - **Informasi Real-Time Dinamis**: Jam digital format `HH:mm:ss` monospaced tebal warna putih (berdetik setiap detik), hari & tanggal bilingual (`EEEE, dd MMMM yyyy`), serta lokasi cabang dan zona waktu (`📍 [Cabang] · [WIB / WITA / WIT]`).
   - Kompilasi build rilis APK versi 1.0.95 dan deploy live ke `https://appsend.my.id/app-release.apk`.

2. **Analisis Sizing Infrastruktur Server untuk Kuota 23.511 Karyawan (3 Group Company):**
   - **Group 1: PT ARINA MULTI KARYA (11.687 Karyawan — 49.7% populasi)**:
     - *Peak Traffic*: ~250 - 400 Request/detik.
     - *Spesifikasi IDEAL*: **16 vCPU (EPYC/Xeon Gold), 32 GB RAM** (16GB DB Buffer + 8GB Redis + 8GB App), **500 GB NVMe SSD**, 1 Gbps Port.
   - **Group 2: GABUNGAN 3 PT [ATB + ATK + ABO] (7.424 Karyawan — 31.6% populasi)**:
     - Rincian: PT Anugrah Talenta Berkarya (2.915) + PT Anugrah Terpercaya Kerja (2.804) + PT Abadi Berkat Odelia (1.705).
     - *Peak Traffic*: ~160 - 250 Request/detik.
     - *Spesifikasi IDEAL*: **10 - 12 vCPU, 24 - 32 GB RAM**, **350 - 500 GB NVMe SSD**, 1 Gbps Port.
   - **Group 3: PT ALVA KARYA PERKASA (4.400 Karyawan — 18.7% populasi)**:
     - *Peak Traffic*: ~100 - 150 Request/detik.
     - *Spesifikasi IDEAL*: **8 vCPU, 16 GB RAM**, **250 GB NVMe SSD**, 1 Gbps Port.
   - **Opsi Alternatif 1 Dedicated Server Fisik (Bare Metal All-in-One)**:
     - **32 Core / 64 Thread (AMD EPYC / Dual Xeon), 64 - 128 GB RAM ECC, 2x 1TB NVMe RAID-1**. Jauh lebih hemat biaya dan praktis dikelola terpusat via 1 dashboard aaPanel/CloudPanel.
   - **Optimasi Software Stack**: Laravel Octane (FrankenPHP/Swoole) untuk throughput 5x–10x lebih cepat, Redis in-memory cache & queue, MySQL 8.0 InnoDB buffer pool 50%–60% RAM, serta Cloud Object Storage (S3/Wasabi/Spaces) untuk menampung ~1,1 juta foto absen/bulan.

3. **Dokumen Presentasi PowerPoint Eksekutif (`Spesifikasi_Server_Absensi_ESA.pptx`):**
   - Dibuat dalam format modern widescreen 16:9 siap presentasi ke stakeholder/manajemen.
   - Tersimpan di direktori lokal `Spesifikasi_Server_Absensi_ESA.pptx` dan di-deploy ke server live di `https://appsend.my.id/Spesifikasi_Server_Absensi_ESA.pptx`.

---

## ✅ Tahap 11.3: Penambahan Nama Prinsiple di Header, Desain Proporsional Team Overview, & Pengetatan Validasi Roster/Visit (SELESAI 24 Agustus 2026, APK v1.0.96)
*Tahap ini menyempurnakan informasi profil karyawan di dashboard mobile, merapikan proporsi grid Team Overview, dan memperbaiki penanganan jadwal roster & visit.*

1. **Penambahan Nama Prinsiple di Header Profil Dashboard:**
   - Menyempurnakan API `AuthController` (`login`, `me`, `updateProfile`) agar selalu melakukan eager loading relasi `principal`.
   - Menambahkan tampilan nama prinsiple di baris kedua profil karyawan di bawah nama lengkap: `[Jabatan] · [Area/Cabang] · [Nama Prinsiple]` (contoh: `TL · Surabaya · PT ANUGRAH TALENTA BERKARYA`).

2. **Perapian Tata Letak Grid Team Overview:**
   - Memperbaiki proporsi kartu pada `TeamStatsWidget` (`childAspectRatio: 1.85`).
   - Menghapus jarak renggang berlebih (`Spacer`) dan menggantinya dengan layout kartu metrik yang rapi, padat, dan proporsional.
   - Menambahkan badge icon modern, label jelas, dan tipografi angka tebal yang seimbang.

3. **Pengetatan Validasi Tombol Check-In & Bagian Kunjungan Lapangan:**
   - **Tombol Check-In**: Jika karyawan tidak memiliki jadwal roster aktif hari ini (misal status libur/off atau tidak ada jadwal sama sekali), tombol secara tegas berubah status menjadi **"Tidak Ada Jadwal Kerja"** dalam warna abu-abu (disabled) dengan icon `Icons.event_busy`, dan mencegah akses check-in.
   - **Bagian Kunjungan Lapangan**: Bagian card Kunjungan Lapangan beserta tombol `Visit-in`, `Laporan`, dan `Visit-out` **HANYA TAMPIL** jika karyawan memiliki jadwal kunjungan (itinerary) aktif pada hari tersebut yang belum selesai (atau sedang dalam sesi kunjungan). Jika tidak ada jadwal visit atau seluruh kunjungan hari ini sudah selesai, card kunjungan otomatis disembunyikan.
   - **Perbaikan Cache**: Memperbaiki logika penanganan response HTTP 403 (tidak ada jadwal) pada `AttendanceProvider` agar otomatis menimpa cache usang.

---

## 🚀 Tahap 12: Fitur Reporting Khusus Prinsiple (Dynamic Form Builder ala Google Forms & Multi-Tenant Subdomain Dashboard) (PLANNED)
*Tahap ini merupakan pembaruan arsitektur besar untuk menyediakan fitur reporting yang dapat dikustomisasi per prinsiple secara fleksibel seperti Google Form, lengkap dengan portal admin mandiri berbasis Subdomain untuk masing-masing prinsiple.*

### 1. Arsitektur & Gambaran Solusi
- **Super Admin Panel (`/admin`)**:
  - Manajemen Prinsiple & Konfigurasi Subdomain (`subdomain`, `theme_color`, `logo_path`, `banner_path`, `portal_title`).
  - **Dynamic Form Builder (ala Google Forms)**: Super Admin dapat mendesain form pelaporan fleksibel (tambah pertanyaan/field dinamis, opsi pilihan, validasi, dan preview form).
  - Penugasan template form ke prinsiple tertentu atau multi-prinsiple.
- **Portal Admin Khusus Prinsiple (`{subdomain}.appsend.my.id` atau `/portal/{subdomain}`)**:
  - Subdomain & data isolation multi-tenant (Admin Prinsiple A hanya bisa melihat data dan laporan milik Prinsiple A).
  - **Branding Dinamis**: Logo, nama brand portal, dan tema warna dashboard otomatis mengikuti identitas prinsiple yang sedang login.
  - **Dashboard Ringkasan & KPI**: Total laporan masuk, outlet aktif terkunjungi, performa karyawan.
  - **Tabel Laporan Masuk Dinamis**: Menampilkan rekap jawaban form dinamis, filter tanggal/outlet/karyawan, modal rincian bukti foto, tanda tangan, dan peta koordinat GPS.
  - **Validasi & Approval Laporan**: Fitur verifikasi (Approve / Reject dengan catatan verifikator).
  - **Ekspor Excel & PDF Fleksibel**: Kolom Excel otomatis menyesuaikan field-field yang dibuat pada Form Builder.
- **Flutter Mobile App (Dynamic Form Engine)**:
  - Mengambil skema field form aktif dari API secara dinamis berdasarkan prinsiple/assignment karyawan.
  - Merender UI input secara *on-the-fly* (Teks, Angka, Dropdown, Multi-Foto Kamera, Tanda Tangan Digital, Titik GPS, Barcode Scanner, dll.).
  - **Offline Storage & Auto-Sync**: Form tetap dapat diisi saat tanpa sinyal dan tersinkronisasi otomatis saat online.

### 2. Struktur Database & Model Baru
- **Penambahan kolom pada `principals`**: `subdomain`, `custom_domain`, `theme_color`, `logo_path`, `banner_path`, `portal_title`, `is_active`.
- **Tabel `report_templates`**: Master template form pelaporan (relasi ke `principal_id`, judul, kategori, permission GPS/Foto/Tanda Tangan, dll.).
- **Tabel `report_form_fields`**: Elemen input dinamis form (`field_label`, `field_name`, `field_type`: text/textarea/number/currency/dropdown/radio/checkbox/date/time/camera_photo/multi_photo/signature/gps_location/barcode_scanner/rating_star, `options`, `is_required`, `placeholder`, `order_index`, `validation_rules`).
- **Tabel `report_submissions`**: Header hasil laporan karyawan (`submission_code`, `principal_id`, `employee_id`, `work_location_id`, `latitude`, `longitude`, `status`, `verified_by`, dll.).
- **Tabel `report_submission_values`**: Nilai detail isian form dinamis (`report_submission_id`, `report_form_field_id`, `field_name`, `field_type`, `field_value`).

### 3. Tahapan Eksekusi & Timeline Development (Roadmap 5 Minggu / 20 - 25 Hari Kerja)
- [x] **Fase 1 (Minggu 1 / Hari 1 - 4)**: Migrasi Database (`principals` branding columns, `report_templates`, `report_form_fields`, `report_submissions`, `report_submission_values`, `report_template_assignments`), Eloquent Models, Subdomain Routing Middleware, & Tenant Scoping Enforcer. (SELESAI 24 Agustus 2026)
- [x] **Fase 2 (Minggu 1 - 2 / Hari 5 - 9)**: Google Form Style Visual Builder di Super Admin Panel Filament (manajemen template, 15+ input types repeater builder, validation rules, assignment rules, instant live preview). (SELESAI 24 Agustus 2026)
- [x] **Fase 3 (Minggu 2 - 3 / Hari 10 - 14)**: Mobile Dynamic Form Engine di Flutter:
  - [x] Dynamic Form Schema Renderer (Teks, Angka, Rupiah, Dropdown, Radio, Checkbox, Rating).
  - [x] **Interactive Date Picker & Time Picker**: Widget kalender interaktif dengan format lokal Indonesia (`dd MMMM yyyy`) dan jam (`HH:mm`) lengkap dengan validasi field wajib (*).
  - [x] **Geotag Watermark Camera**: Otomatis membubuhkan watermark permanen (Nama, NIK, Nama Toko Terpilih, Timestamp, Koordinat GPS, dan Status Radius) pada foto struk, rak, POSM, dan display.
  - [x] **Pemilih Store Berjenjang per Area**: Toko terikat ke prinsiple, filter bertingkat Area -> Toko dengan kalkulasi jarak radius GPS otomatis.
  - [x] **Restriksi Akses Menu Reporting**: Menu Pelaporan hanya tampil untuk karyawan di bawah prinsiple yang memiliki template aktif di Form Builder.
  - [x] **11 Template Form Fonterra**: Offtake SPG, Offtake SPT, Stok & OOS, Expired Date FEFO, SOS, Promo Fonterra, Promo Competitor, Price Monitoring, Kemasan & Sticker, POSM, dan Additional Display. (SELESAI 24 Agustus 2026)
- [ ] **Fase 4 (Minggu 3 - 4 / Hari 15 - 19)**: Portal Khusus Multi-Tenant Subdomain Prinsiple (`{subdomain}.appsend.my.id`), Dynamic Theme & Whitelabel Branding, Tabel Laporan Masuk Dinamis, Detail GPS/Foto, Approval & Verification Flow, serta Dynamic Excel/PDF Export with Queue.
- [ ] **Fase 5 (Minggu 4 - 5 / Hari 20 - 22)**: Pengujian Menyeluruh (End-to-End Testing), Audit Keamanan Isolasi Data Tenant, Load Testing Query & Ekspor Laporan, serta UAT bersama Tim Prinsiple.
- [ ] **Fase 6 (Minggu 5 / Hari 23 - 25)**: Konfigurasi Wildcard Subdomain DNS (*.appsend.my.id) & SSL di aaPanel, Deployment Production Live, Rilis Update Mobile App, serta Penyusunan User Manual & Handover.

### 4. Log Progress Harian (24 - 26 Agustus 2026)
1. **Restriksi Menu Reporting di Mobile**:
   - Menambahkan accessor `has_reporting_templates` pada model `Employee.php`.
   - Mengubah `dashboard_screen.dart` dan `visit_report_screen.dart` agar menu dan banner Form Pelaporan disembunyikan untuk karyawan non-prinsiple / prinsiple tanpa form builder.
2. **Pemilihan Lokasi Toko & Area Berjenjang**:
   - Memperbaiki `work_locations` agar terikat ke data prinsiple karyawan.
   - Mengimplementasikan alur pemilihan: Pilih Area (default area karyawan atau ganti area lain) -> Muncul toko terdaftar -> Hitung otomatis jarak radius GPS terhadap titik toko.
3. **Kamera Watermark Geotag Real-Time**:
   - Menampilkan info Toko Terpilih, GPS, Nama, NIK, dan Waktu permanen di atas hasil foto laporan.
4. **11 Form Pelaporan Lengkap Prinsiple Fonterra**:
   - Menganalisis dokumen Excel & PPTX Fonterra (Anlene, Boneeto, Anchor).
   - Membuat migration `2026_08_24_172000_seed_all_fonterra_reporting_templates.php` dan memperbarui `ReportTemplatePresetsSeeder.php` untuk memasukkan 11 jenis laporan lengkap dengan seluruh opsi pilihan dan validasi.
5. **Interactive Date Picker & Time Picker di Mobile**:
   - Memperbaiki field bertipe `date`, `datepicker`, `time`, `timepicker`, dan `datetime` pada `dynamic_form_screen.dart` dari yang semula text biasa menjadi widget pemilih tanggal/jam interaktif.
6. **Real-Time Terminal Streaming Engine Odoo Sync (SELESAI 26 Agustus 2026)**:
   - Mengganti arsitektur request sinkronisasi Odoo yang semula AJAX Livewire sinkron (rentan timeout 503 / layar hitam) menjadi **Server-Sent Events (SSE) Real-Time Streaming**.
   - Pembuatan controller `OdooSyncStreamController.php` dengan endpoint `/admin/odoo-sync/stream`.
   - Menghubungkan callback progress langsung pada `OdooSyncService` (`syncPrincipals`, `syncEmployees`, `syncAllConfiguredCompanies`, `cleanupAllDuplicateEmployees`, `testConnection`).
   - Merombak halaman `Odoo Sync` dengan tampilan **Jendela Konsol Terminal Linux** interaktif lengkap dengan kontrol window bar (🔴🟡🟢), status pills, counter metrik dinamis (Diproses, Baru, Update, Resign, Error), progress bar, auto-scroll toggle, salin log, tombol stop, serta tombol **Sync Semua Perusahaan Sekaligus**.
   - Berhasil di-push ke GitHub dan di-deploy ke server live.
7. **9 Form Pelaporan Lengkap Prinsiple Daesang / MamaSuka (SELESAI 26 Agustus 2026)**:
   - Menganalisis file `Copy of RAW DATA - REPORTING ATTANDANCE MAMASUKA.xlsx` dan `PPT - ALL REPORT DAESANG (MAMASUKA).pptx`.
   - Membuat migration `2026_08_26_130000_seed_all_mamasuka_reporting_templates.php` dan mengupdate `ReportTemplatePresetsSeeder.php`.
   - Menghasilkan 9 template form dinamis terverifikasi:
     1. **Rental Display Mamasuka (`RPT-MAMASUKA-RENT-DISPLAY-01`)**: 11 fields (Brand, Kategori, SKU, Tipe Rental TG/Wing/Floor, Periode Kontrak, Implementasi, POSM, Foto Before/After, Remarks).
     2. **Additional Display Mamasuka (`RPT-MAMASUKA-ADD-DISPLAY-01`)**: 8 fields (Brand, Kategori, Tipe Side Rack/Hanger/Island, Posisi Toko, Status Propose/Approve/Reject, Alasan Reject, Foto).
     3. **Pricing & Price Tag Mamasuka (`RPT-MAMASUKA-PRICING-01`)**: 11 fields (Kategori, SKU, Harga Normal, Harga Promo, Tipe Promo, Status Price Tag, Focus SKU, Ketersediaan, Foto).
     4. **Tracking Program Promo Mamasuka (`RPT-MAMASUKA-PROMO-OWN-01`)**: 11 fields (Kode Promo, Kategori, Tipe Diskon/Banded/Gimmick, Mekanisme, Periode, Implementasi, Status POP, Foto).
     5. **Promo Kompetitor (`RPT-MAMASUKA-PROMO-COMP-01`)**: 8 fields (Brand Pesaing Sasa/Ajinomoto/Kobe/Royco/dll, Kategori, SKU, Tipe & Mekanisme Promo, Display Tambahan, Foto).
     6. **Cek Stok & Out of Stock / OOS (`RPT-MAMASUKA-STOCK-OOS-01`)**: 13 fields (Kategori, SKU, Focus OOS, Min Stock, Actual Stock, Status OOS, Alasan PO/Gudang/Distributor, Estimasi PO, PIC Toko, Nilai Stok, Foto Rak).
     7. **Sell Out SPG Reguler & MD (`RPT-MAMASUKA-SELLOUT-REG-01`)**: 12 fields (Stok Awal, Sell In, Retur, Stok Akhir, Total Qty Terjual, Harga Jual, Omzet Rp, Foto Struk/Nota).
     8. **Sell Out SPG Demo & Event Masak (`RPT-MAMASUKA-SELLOUT-DEMO-01`)**: 8 fields (Jenis Demo/Sampling/Bazaar, Menu Masakan, Porsi Tester Dibagikan, Total Qty & Omzet Terjual, Foto Booth, Feedback Konsumen).
     9. **Monitoring Expired Date (`RPT-MAMASUKA-EXPIRED-01`)**: 9 fields (Kategori, SKU, Tanggal ED, Qty Fisik, Selisih Bulan Kritis/Near ED/Aman, Rekomendasi Tindakan Clearance/Retur, Foto Batch & ED).
   - Di-assign secara otomatis ke principal **PT DAESANG AGUNG INDONESIA / MAMASUKA / MIWON**.
   - Berhasil dideploy dan diverifikasi langsung pada server live (`https://appsend.my.id/seed-templates-now`).
8. **7 Form Pelaporan Lengkap Prinsiple Wings Surya & Lion Wings (SELESAI 26 Agustus 2026)**:
   - Menganalisis file `FORM REPORT MBR.xlsx`, `Report Exp Date Food.pdf`, `Report MBR OOS.pdf`, dan `Report Promo Kompetitor.pdf`.
   - Membuat migration `2026_08_26_140000_seed_all_wings_reporting_templates.php` dan mengupdate `ReportTemplatePresetsSeeder.php`.
   - Menghasilkan 7 template form dinamis terverifikasi:
     1. **Cek Stok & OOS Wings Food (`RPT-WINGS-OOS-FOOD-01`)**: 10 fields (Kategori Food Mie/RTD/Cup/Kopi/Snack/Bumbu, SKU, Status Stok Aman/OOS/Menipis, Min Stock, Actual Stock, Alasan OOS PO/Gudang/Distributor/Bad Stock, Estimasi PO, PIC Toko, Foto Rak).
     2. **Cek Stok & OOS Wings Care & Lion Wings (`RPT-WINGS-OOS-CARE-01`)**: 10 fields (Kategori Fabric Care/Dishwashing/Personal Wash/Hair Care/Oral Care/Baby Diapers/Fragrance, SKU Daia/SoKlin/Giv/Nuvo/Ciptadent/Baby Happy, Status Stok, Min Stock, Actual Stock, Alasan OOS, Estimasi PO, PIC Toko, Foto).
     3. **Stok & Freezer Es Krim Glico Wings (`RPT-WINGS-GLICO-01`)**: 8 fields (Kategori Waku Waku/J-Cone/Frostbite/Haku, SKU, Kondisi Suhu & Kebersihan Freezer -18°C s/d -22°C, Status Stok di Basket Freezer, Actual Stock Pcs, Foto Freezer Depan/Dalam).
     4. **Expired Date & Indikator Lakban Wings Food (`RPT-WINGS-EXPIRED-FOOD-01`)**: 10 fields (Kategori Umur Simpan, SKU, Tanggal ED, Warna Lakban Karton Biru Tua/Kuning/Coklat/Merah/Hijau/Biru Muda, Qty Karton, Qty Pcs, Selisih ED Kritis/Near ED, Rekomendasi FIFO/Clearance/Retur, Foto Batch & Lakban).
     5. **Aktivitas & Promo Kompetitor Wings (`RPT-WINGS-PROMO-COMP-01`)**: 11 fields (Divisi Food vs Care, Brand Pesaing Indofood/Unilever/Mayora/Kao/P&G/Sweety/dll, SKU Pesaing, Ukuran Kemasan, Tipe Diskon/Buy 1 Get 1/Gimmick/Mailer, Mekanisme, Periode, Harga Normal vs Promo, Display Tambahan, Foto).
     6. **Share of Display / SOS Wings vs Kompetitor (`RPT-WINGS-SHARE-DISPLAY-01`)**: 9 fields (Channel MTI/MTKA/Kemitraan, 10 Kategori Wajib Wings, Jumlah Tiers Rak Wings Actual, Total Tiers Rak Kategori, Target % SOS Toko, Actual % SOS Terhitung, Status Pencapaian Target SOS, Foto Full Gondola Rak).
     7. **Additional Display & Sewa Endcap Wings (`RPT-WINGS-ADD-DISPLAY-01`)**: 9 fields (Divisi Food/Care/Glico, Brand Display, Tipe Sewa Endcap TG/Floor Island/Wing Stage/Hanging Kasir, Status Kontrak Paid/Free/Bonus, Status Realisasi, Lokasi Toko, Foto Depan & Samping).
   - Di-assign secara otomatis ke entitas **PT WINGS SURYA**, **PT LION WINGS**, **PT SAYAP MAS UTAMA**, dan **CV SINAR SURYA**.
   - Berhasil dideploy dan diverifikasi langsung pada server live (`https://appsend.my.id/seed-templates-now`).

9. **Penyelesaian Whitelabel Subdomain Portal Prinsiple (SELESAI 26 Agustus 2026)**:
    - **Isolasi Multi-Tenant & Scoping Data Ketat**: Mengimplementasikan pemfilteran data berbasis `$scopedPrincipalIds` pada seluruh modul portal:
      - **Karyawan (Employees)**: Hanya menampilkan SPG/Promotor di bawah prinsiple aktif.
      - **Area / Cabang (Areas)**: Hanya menampilkan cabang yang memiliki promotor aktif di bawah prinsiple tersebut.
      - **Lokasi Kerja / Toko (Work Locations)**: Hanya menampilkan toko yang didaftarkan langsung di bawah prinsiple atau yang ditugaskan dalam roster jadwal dan itinerari.
      - **Shift Kerja (Shifts)**: Hanya menampilkan shift kerja yang digunakan oleh tim promotor prinsiple terkait.
    - **Modul Katalog SKU Produk & Import Excel**:
      - Menyediakan fitur manajemen SKU Produk lengkap (Nama, SKU, Brand, Kategori, Harga Jual, UOM, Barcode, Deskripsi).
      - Fitur upload & preview foto produk interaktif.
      - Fitur Import massal produk via template file Excel (.xlsx).
    - **Sistem Branding & Gradasi Warna Dinamis**:
      - Dukungan logo, nama prinsiple, judul portal, dan gradasi 2 warna (`theme_color` & `theme_color_secondary`) yang otomatis diterapkan ke sidebar, header, tombol aksi, dan grafik.
    - **Dynamic Role & Permission Navigation**:
      - Sidebar menu beradaptasi secara otomatis mengikuti izin/hak akses Role pengguna (`view_employees`, `view_attendance`, `view_work_locations`, `view_manpower_report`, dll.).
    - **Halaman Khusus Whitelabel Portal (Full Light Mode)**:
      - Membuat dan menyelaraskan 13 halaman portal mandiri berdesain premium (tidak beralih ke backend gelap Filament):
        1. `/portal/dashboard` (Sales Summary Dashboard & Charts)
        2. `/portal/products` (Katalog SKU Produk & Import Excel)
        3. `/portal/employees` (Daftar Karyawan/Promotor)
        4. `/portal/areas` (Area & Cabang)
        5. `/portal/work-locations` (Lokasi Kerja / Toko)
        6. `/portal/shifts` (Shift Kerja)
        7. `/portal/attendances` (Monitoring Presensi Harian & GPS)
        8. `/portal/schedules` (Roster Jadwal Kerja)
        9. `/portal/leaves` (Pengajuan Cuti / Izin)
        10. `/portal/extra-hours` (Pengajuan Lembur)
        11. `/portal/unchecked` (Monitoring Karyawan Belum Absen)
        12. `/portal/visit-reports` (Laporan Kunjungan Lapangan)
        13. `/portal/itineraries` (Jadwal Kunjungan & Rute Toko Promotor)
        14. `/portal/manpower-report` (Laporan Rekapitulasi Headcount Jan - Des)
        15. `/portal/mandays-report` (Laporan Target vs Realisasi Mandays & Efektivitas %)
        16. `/portal/turnover-report` (Statistik Masuk/Keluar Promotor & Turnover Rate %)
    - **Komponen Navigasi Pagination Modern**:
      - Mengganti link raw pagination menjadi komponen tombol pill modern dengan active state bergradasi warna prinsiple, disabled buttons, dan info jumlah total data yang rapi.
    - **Verifikasi & Deployment Live**: Berhasil dideploy dan aktif pada domain `https://wings.appsend.my.id` dan `https://appsend.my.id`.

10. **Pembaruan Aplikasi Mobile Android (APK Release v1.0.97 - SELESAI 26 Agustus 2026)**:
    - **Filter Ketat Radius 1.000 Meter (1 km) pada Form Reporting**:
      - Pemilihan lokasi/toko pada form pelaporan dinamis (`dynamic_form_screen.dart`) hanya menampilkan toko yang berada dalam radius maksimal 1.000 meter (1 km) dari titik koordinat GPS posisi user secara real-time.
      - Toko diurutkan dari yang paling dekat dengan posisi user.
      - Badge counter pada header area selector otomatis menghitung jumlah toko yang memenuhi syarat jarak radius (`x toko (≤ 1km)`).
      - Menampilkan pesan informatif jika user berada di luar perimeter seluruh toko terdaftar.
    - **URL Server Dinamis (Arsitektur Multi-Server)**:
      - Normalisasi otomatis endpoint dan dukungan fallback default ke `https://appsend.my.id/api`.
      - Kemudahan konfigurasi server tujuan melalui layar `server_config_screen.dart`.
    - **Adaptasi Warna Tema Dinamis Sesuai Prinsiple (Whitelabel Theme)**:
      - `AuthProvider` secara otomatis mengekstrak properti `theme_color` prinsiple karyawan saat login / me profile.
      - Warna branding disimpan ke dalam `SharedPreferences` (`cached_principal_theme_color`) sehingga langsung aktif sejak splash screen dibuka.
      - Seluruh UI Flutter (`MaterialApp`, `ThemeData`, `AppBar`, buttons, tabs, accent icons) menyesuaikan warna primer secara dinamis mengikuti identitas prinsiple karyawan (Merah untuk Wings Surya, Biru untuk Lion Wings / Dulux, Hijau untuk Fonterra, dsb.).
    - **Build & Distribusi APK v1.0.97**:
      - Versi aplikasi ditingkatkan menjadi `1.0.97+97`.
      - File installer `app-release-1.0.97.apk` (94.6 MB) berhasil dikompilasi dan didistribusikan ke server live (`https://wings.appsend.my.id/app-release.apk` dan `https://appsend.my.id/downloads/app-release-1.0.97.apk`).

11. **Metode Input Employee Schedule Roster via Working Group 2-Step Wizard (SELESAI 27 Agustus 2026)**:
    - **Step 1: Description & Configuration**:
      - Input Nama Working Group, Date Applied, Area / Branch, dan Prinsiple.
      - General Configuration: Default Shift / Working Hour, Late Tolerance (menit), Store / Work Location.
      - Action Cepat: `Select All` (7 hari aktif) dan `Work Days` (Senin s/d Jumat aktif, Sabtu-Minggu libur).
      - **Custom Option per Hari**: Accordion ekspansi sub-form per hari kerja aktif untuk menentukan Shift khusus, Toleransi Terlambat khusus, dan Lokasi Toko khusus hari tersebut.
    - **Step 2: Implementing Working Group**:
      - Autocomplete selector `Select employee to be added` dengan instant auto-add.
      - Fitur massal `Tambah Semua di Area/Prinsiple Ini` dan `Kosongkan`.
      - Data table anggota terpilih lengkap dengan avatar, Nama, NIK, Posisi/Area, live search, pagination, dan tombol hapus.
    - **Schedule Generator Engine Sepanjang Tahun Berjalan**:
      - Tombol `Simpan & Generate Jadwal (Submit)` secara otomatis menyimpan data Working Group, aturan 7 hari, anggota, serta mengenerate seluruh data `employee_schedules` untuk semua karyawan anggota dari `Date Applied` hingga akhir tahun berjalan (`endOfYear()`).
    - **Akses Langsung dari Roster**:
      - Menambahkan tombol aksi **`Input via Working Group`** langsung pada header halaman Matriks Roster Jadwal Kerja (`EmployeeScheduleRoster`).

12. **Penyelarasan Form Roster, 2 Template Import & Kalender Visit Schedule di Portal Prinsiple (SELESAI 27 Agustus 2026)**:
    - **Penyelarasan Form Create Employee Schedule Roster**:
      - Menyamakan form Create Roster di Portal Prinsiple persis seperti di Web Admin (pilihan Single Employee, Massal per Area/Prinsiple, dan integrasi input via Working Group).
      - Dua Opsi Template Import Excel:
        1. **Template Import Roster Tahunan (Full Year)**: Format template Excel lengkap 365 hari per karyawan.
        2. **Template Import Matriks Grid Bulanan (Monthly Grid)**: Format template Excel horizontal 1 s/d 31 hari per bulan.
      - Desain dialog unduh template dan upload file Excel dibuat modern dan profesional.
    - **Penyelarasan Halaman Visit Schedule (Kalender Interaktif & Form Input)**:
      - Tampilan halaman Visit Schedule di Portal Prinsiple diubah menjadi tampilan kalender interaktif (`FullCalendar`) persis seperti di Web Admin.
      - Form Create Visit Schedule diselaraskan: dukungan penugasan jadwal kunjungan per karyawan atau per Working Group, multi-toko dalam 1 tanggal, rute kunjungan, dan validasi radius lokasi toko.

13. **Perbaikan Multi-Foto Form Reporting & Pemulihan Foto Rusak (SELESAI 27 Agustus 2026, APK v1.0.100 - v1.0.102)**:
    - **Dukungan Multi-Foto Pelaporan Dinamis**:
      - Pengambilan foto berturut-turut pada field foto laporan (misal: Hadiah Nuvo, Display Toko, Bukti Promo) tersimpan lengkap ke array file upload (`_multiPhotoFiles`).
    - **Penyelesaian Anomali Path Lokal Android**:
      - Menemukan dan mengatasi akar masalah di mana path cache lokal perangkat Android (`/data/user/0/.../cache/wm_...jpg`) sempat masuk ke payload JSON `values` dan menimpa `value_json` & `media_url` di database.
      - `ReportingApiController.php` mengisolasi total seluruh field media (`photo`, `camera_photo`, `multi_photo`, `signature`) agar tidak tertimpa input teks.
      - Menambahkan mekanisme fallback otomatis disk scanning (`glob(storage_path('app/public/reports/...'))`) pada backend API dan view Web Admin (`report_submission_detail.blade.php`).
    - **Database Migration Pemulihan Data**:
      - Menjalankan migrasi `2026_08_27_163000_repair_corrupted_local_paths_in_report_values.php` di server produksi, memulihkan seluruh foto laporan lama ke path file fisik asli (diverifikasi dengan status HTTP 200 OK).

14. **Penguncian Lokasi Laporan Otomatis Sesuai Sesi Presensi & Penonaktifan Submit Laporan (SELESAI 27 Agustus 2026, APK v1.0.102)**:
    - **Penghapusan Pemilih Manual Area & Toko**:
      - Selector manual Area dan Toko pada formulir pelaporan dinamis (`dynamic_form_screen.dart`) dihapus.
      - Lokasi pelaporan kini **terkunci otomatis (*bound automatically*)** mengikuti sesi absensi aktif:
        - **Sesi Visit In**: Laporan otomatis mengikat outlet/toko kunjungan aktif yang sedang dikunjungi (`LOKASI VISIT AKTIF`).
        - **Sesi Check In**: Laporan otomatis mengikat lokasi kerja / cabang presensi karyawan (`LOKASI CHECK-IN`).
        - **Mode Edit**: Laporan mengikat toko laporan awal yang sedang diedit (`LOKASI LAPORAN`).
      - Header formulir menampilkan kartu info lokasi terikat yang elegan dengan badge status, nama outlet/toko, alamat lengkap, jarak geofence radius, koordinat GPS live, dan watermark geotag otomatis.
    - **Penonaktifan Tombol Submit Jika Belum Check-In / Visit-In**:
      - Jika karyawan belum melakukan Check-In atau Visit-In:
        - Menampilkan banner peringatan terkunci (Merah/Oranye): *"Belum Check-In / Visit-In. Laporan terkunci & tidak dapat dikirim."*
        - Tombol submit formulir pelaporan dinonaktifkan (`disabled`) dengan label *"Wajib Check-In / Visit-In Terlebih Dahulu"* dan ikon gembok 🔒.
      - Jika sudah Check-In atau Visit-In: formulir terbuka dan tombol submit aktif normal.
    - **Auto-Resolve Lokasi di Backend API**:
      - `ReportingApiController.php` secara otomatis meng-infer lokasi toko dan ID lokasi kerja dari log visit aktif atau log check-in hari ini jika payload toko kosong.
      - Telah dideploy langsung ke server produksi (`https://appsend.my.id/`).
    - **Build & Rilis APK v1.0.102**:
      - Versi aplikasi dinaikkan ke **`v1.0.102+102`**.
      - File APK rilis siap pasang berhasil dikompilasi: `build/app/outputs/flutter-apk/app-release-v1.0.102.apk` (94.8 MB).

15. **Penyelarasan Tampilan & Kartu Metrik Detail Laporan Portal Prinsiple (SELESAI 28 Agustus 2026)**:
    - **Metrik Dinamis Periode Berjalan**:
      - Label card ringkasan diubah menjadi dinamis sesuai filter bulan/tahun yang aktif (`Total Laporan Periode {Bulan Tahun}`, misal: `Total Laporan Periode Agustus 2026`).
    - **Penyederhanaan Kartu Ringkasan (Mini Stats)**:
      - Menghapus card "Wajib Titik GPS" dan "Wajib Tanda Tangan" agar tampilan ringkas, fokus pada performa data laporan masuk dan toko terjangkau.
      - Grid metrik disesuaikan menjadi 2 kolom responsif.
    - **Peningkatan Navigasi Pagination**:
      - Integrasi komponen pagination custom `portal.pagination` dengan query filter preserved.
    - **Deployment Live**: Berhasil dipush ke GitHub dan dideploy langsung ke server `https://appsend.my.id/`.

16. **Integrasi Master Produk Prinsiple, Barcode Scanner & Auto-Select Kategori (SELESAI 28 Agustus 2026, APK v1.0.103)**:
    - **Multi-Tenant Scoping Master Produk per Prinsiple**:
      - `ReportingApiController.php` secara ketat mengisolasi master produk per `principal_id` karyawan (Wings, Dulux, Fonterra, MamaSuka, dll.).
      - Payload API mengirimkan `id`, `name`, `sku_code`, `barcode`, `category`, `brand`, `price`, `formatted_price`, dan `uom`.
      - Cross-Entity auto-resolver untuk grup entitas bersama (misal: Wings Surya & Lion Wings).
    - **Scanner Barcode Fisik Kemasan Produk Real-Time**:
      - Pembuatan widget dialog scanner kamera interaktif `BarcodeScannerDialog` menggunakan `mobile_scanner: ^7.4.0`.
      - Dilengkapi overlay reticle corners, animasi laser garis merah, tombol toggle flashlight/senter, tombol rotasi kamera, dan modal input manual.
    - **Picker Katalog Master Produk (Searchable Bottom Sheet)**:
      - Bottom sheet interaktif pencarian real-time (Nama Produk, SKU, Barcode, Brand).
      - Filter chips kategori dinamis ("Semua", "Food & Beverage", "Detergent", "Ice Cream", dll.).
      - Tombol pintas scan barcode langsung dari search bar katalog.
    - **Deteksi Cerdas Input Produk & Isolasi Field Kemasan/Kategori**:
      - Field tipe `product_select`, `product`, `barcode_scanner`, ataupun field teks/dropdown bernama `produk`/`sku` otomatis memunculkan tombol **Scan Barcode** 📷 dan **Katalog** 📦.
      - Pengecualian ketat (`isExcluded`) agar field turunan seperti `Kemasan Produk`, `Kategori Produk`, `Foto`, `Stok`, `Qty`, `Harga` tidak ikut berubah menjadi selector produk.
    - **Algoritma Multi-Attribute Auto-Fill Kategori & Kemasan**:
      - Pencocokan kategori cerdas berbasis Kategori, Brand, dan Kata Kunci Nama Produk (misal: *Dulux Aquashield Pelapis Anti Bocor* otomatis memilih opsi `Dulux Aquashield (Cat Pelapis Bocor)`).
      - Auto-select field Kemasan Produk berdasarkan token ukuran produk (*misal 4kg otomatis memilih opsi `2.5 Liter / 4 Kg / 5 Kg (Galon)`*).
    - **Build & Rilis APK v1.0.103**:
      - Versi aplikasi dinaikkan ke **`v1.0.103+103`**.
      - File APK rilis siap pasang berhasil dikompilasi: `att-mobile/build/app/outputs/flutter-apk/app-release.apk` (105.5 MB).

17. **Optimasi & Pembersihan Kapasitas Storage Server Linux / aaPanel (SELESAI 28 Agustus 2026)**:
    - **Investigasi Kapasitas Disk (91% / 35.4 GB)**:
      - Analisis direktori menemukan 18 GB penggunaan disk berasal dari file log database PostgreSQL di `/www/server/pgsql/logs/`.
    - **Perbaikan Skrip Pembersih Server Shell (Bash Script)**:
      - Menghapus perintah berbahaya `rm -rf /tmp/*` yang sebelumnya menghapus UNIX socket aktif (PHP-FPM, MySQL, aaPanel) dan menyebabkan server macet/harus restart manual.
      - Membuat skrip bash baru yang aman dengan pembersihan log Web Server (Nginx), log PostgreSQL, log aaPanel, Laravel cache & log (`optimize:clear`), Systemd Journal (`journalctl --vacuum-size=100M`), APT package cache, dan safe temporary cleanup (`find /tmp -atime +1 -not -name "*.sock"`).
      - Pengaturan jadwal Cron Harian otomatis (pukul 02:00 WIB) untuk perawatan server rutin tanpa downtime.
    - **Skrip Pembersih Otomatis Web Browser (`clean_server.php`)**:
      - Pembuatan endpoint pembersih `att-admin-v12/public/clean_server.php` dengan otentikasi token keamanan `dgsoft_rahasia_123`.
      - Dilengkapi kartu statistik visual persentase penggunaan disk sebelum vs sesudah dan log eksekusi pembersihan real-time.
18. **Penyempurnaan Form Builder: Expired Date (Bulan & Tahun), Parameter Read Only & Rilis APK v1.0.104 (SELESAI 31 Agustus 2026)**:
    - **Penyempurnaan Expired Date (Hanya Bulan & Tahun / `month_year`)**:
      - Pembuatan tipe input baru `month_year` pada Form Builder Filament Admin (`🗓️ Pilih Bulan & Tahun (MM/YYYY - Expired Date)`).
      - Migrasi database `2026_08_31_080000_add_is_readonly_and_update_month_year_fields.php` untuk mengonversi seluruh field expired date template (Fonterra, MamaSuka, Wings) menjadi `month_year`.
      - Pembuatan widget Dialog Pemilih Bulan & Tahun interaktif di Flutter (`dynamic_form_screen.dart`) dengan pemilih tahun dan grid 12 bulan (Januari - Desember) berformat standar `MM/yyyy`.
    - **Parameter "Read Only (Hanya Baca)" untuk Form Builder**:
      - Kolom `is_readonly` (boolean) ditambahkan ke tabel `report_form_fields` dan schema Form Builder Filament Admin.
      - Field yang diatur *Read Only* akan menampilkan badge status visual `🔒 Read Only` di aplikasi mobile dan mengunci input manual agar terproteksi dari perubahan manual oleh user saat terisi otomatis.
19. **Penyempurnaan Form Builder: Parameter Hari Pelaporan, Penugasan Multi-Select Jabatan & Employee, dan Rilis APK v1.0.105 (SELESAI 31 Agustus 2026)**:
    - **Parameter Jadwal Hari Pelaporan Wajib (`report_days`)**:
      - Ditambahkan kolom `report_days` (JSON) pada tabel `report_templates` dan komponen multi-select pada Filament Admin Form Builder.
      - Pilihan hari: `Senin`, `Selasa`, `Rabu`, `Kamis`, `Jumat`, `Sabtu`, `Minggu` (atau kosong untuk setiap hari).
      - Integrasi ke API dan tampilan badge jadwal pengisian pada kartu template di mobile app (`reporting_hub_screen.dart`).
    - **Penugasan Form Template Multi-Select (Jabatan & Nama Employee)**:
      - Pembuatan relasi pivot `report_template_position` dan `report_template_employee` serta kolom `employee_id` di `report_template_assignments`.
      - Penugasan form di Filament Form Builder mendukung pemilihan multipel **Target Jabatan** (SPG, MD, TL, dll.) dan **Target Nama Employee Spesifik** (dengan NIK & Prinsiple).
      - Filter cerdas di backend API untuk memastikan karyawan hanya melihat form template yang relevan dengan tugasnya.
20. **Integrasi 10 Form Template Pelaporan Resmi Dulux (ICI Paints / AkzoNobel) (SELESAI 31 Agustus 2026)**:
    - **Penyelarasan 10 Formulir Resmi Dulux (JotForm)**:
      1. `RPT-DULUX-TINTER-LSO`: Laporan Tinter & Pasta Warna LSO Dulux (Modern Trade: Ace Hardware, Depo Bangunan, Mitra 10).
      2. `RPT-DULUX-CBP-PRICING`: Laporan CBP (Consumer Buying Price) & Cek Harga Dulux vs Kompetitor (Jotun, Nippon, Avian, Mowilex).
      3. `RPT-DULUX-OFFTAKE-01`: Laporan Offtake / Penjualan Harian & Multi-Foto Nota Khusus (Aquashield, Weathershield, Ambiance, Catylac, PEP/PIP) serta metrik traffic customer.
      4. `RPT-DULUX-STOCK-END`: Laporan Stock End (Stock Opname Bulanan tgl 20-28) dengan pilihan Base warna dan status akses gudang.
      5. `RPT-DULUX-OOS-SSO`: Laporan Out of Stock (OOS) SSO dengan jadwal wajib hari Sabtu (`report_days = ['sabtu']`).
      6. `RPT-DULUX-OOS-LSO`: Laporan Out of Stock (OOS) LSO untuk akun modern trade.
      7. `RPT-DULUX-DATABASE-PELANGGAN`: Laporan Data Pelanggan & Konsumen (Profil, No HP, Tipe, Brand Dicari vs Brand Dibeli, Preview Visualizer).
      8. `RPT-DULUX-TRAFIK-PEMBELI`: Laporan Trafik Pembeli Toko Dulux (Quick Traffic: Pengunjung, Pembeli Cat, Pembeli Dulux).
      9. `RPT-DULUX-REGISTRASI-MITRA`: Laporan Registrasi New MD (Mitra Dulux Non-Incentive) dengan foto KTP, foto Painter + DC/DGO, foto proyek, nota pertama, dan TTD digital.
      10. `RPT-DULUX-DAILY-MAINTENANCE`: Laporan Daily Maintenance POST & Mesin Tinting (Checklist D200/Discovery/XProtint, foto brush cleaning, status Mix2Win).
    - **Standarisasi Input Data Otomatis**:
      - Nama employee, area, jabatan, lokasi store/toko, dan alamat otomatis diambil dari data akun login & check-in lokasi aktif.
      - Inputan produk tetap mengarah ke master produk Dulux (`product_select`) dengan opsi barcode scanner.
    - **Migrasi & Seeder**:
      - Migrasi `2026_08_31_100000_seed_all_dulux_official_templates.php` dan `ReportTemplatePresetsSeeder.php` telah disinkronkan.

21. **Pengikatan Shift ke Prinsiple & Tampilan Nama Prinsiple pada Master Data (SELESAI 31 Agustus 2026)**:
    - **Relasi Shift ke Prinsiple**:
      - Struktur `shifts` diubah agar terikat langsung ke `principal_id` (bukan `company_id`).
      - Migrasi `2026_08_31_130000_update_shifts_table_bind_to_principal.php` dan pembaruan Model `Shift.php` (`belongsTo(Principal::class)`).
    - **Tampilan Tabel & Form Shift**:
      - Tabel daftar shift (`ShiftsTable.php`) langsung menampilkan **Nama Prinsiple** (dengan filter dan search cerdas), bukan ID numerik.
      - Form shift (`ShiftForm.php`) menggunakan dropdown select Prinsiple aktif.

22. **Fitur "Request New Location" oleh Karyawan dengan Approval Administrator (SELESAI 31 Agustus 2026, APK v1.0.106 - v1.0.107)**:
    - **Struktur Database & Backend**:
      - Pembuatan tabel `location_requests` dengan status (`pending`, `approved`, `rejected`), data nama lokasi, alamat, link Google Maps, koordinat GPS (lat, lng), radius, dan catatan admin.
      - Pembuatan endpoint API `/api/location-requests` (GET & POST) dengan auto-assign employee, prinsiple, branch, dan company.
    - **Panel Admin Approval (`LocationRequestResource`)**:
      - Menu baru di Admin Panel untuk mengelola permohonan lokasi baru.
      - Action Approval otomatis membuat master `work_locations` baru dan menghubungkannya dengan relasi multi-tenant yang sesuai.
      - Notifikasi database (lonceng) dan email otomatis terkirim saat permohonan dibuat atau disetujui.
    - **Antarmuka Mobile (`request_location_screen.dart`)**:
      - Form pengajuan lokasi baru dengan panduan/instruksi interaktif cara menyalin link dari Google Maps.
      - Auto-resolving link Google Maps (shortlink `maps.app.goo.gl` maupun URL koordinat lengkap) serta opsi tombol *Ambil Koordinat GPS Saat Ini*.
      - Penyelarasan desain visual antarmuka selaras dengan halaman lainnya (Light/Dark mode, Banner Header, Card Elevation).

23. **Penyempurnaan Logika Status Shift Belum Sampai Waktu Kerja pada List & Matriks Kehadiran (SELESAI 31 Agustus 2026)**:
    - **Logika Status Kehadiran Adaptif Jam Masuk Shift**:
      - Karyawan dengan jadwal shift siang/malam yang dicek sebelum jam shiftnya tiba tidak lagi ditampilkan sebagai `ALPHA`, melainkan menampilkan **Nama / Kode Shift** aslinya (misal: `S2`, `S3`, `NS`, `OFF`).
      - Status hanya berubah menjadi `ALPHA` jika jam kerja shift tersebut telah terlewati dan karyawan belum melakukan check-in.
    - **Penerapan pada Web Roster & Export Excel**:
      - Diperbarui pada `AttendanceRoster.php` (Blade view) dan `AttendanceRosterMatrixExport.php` (Excel export).

24. **Standarisasi Kode "LR" untuk Permit / Leave Request yang Belum di-Approve (SELESAI 31 Agustus 2026)**:
    - **Status "LR" (Leave Request)**:
      - Pengajuan izin / cuti / sakit yang berstatus `pending` (belum disetujui) pada matriks kehadiran kini menampilkan badge kode **`LR`** dengan warna oranye/kuning (tidak dihitung sebagai Alpha).
      - Menampilkan subteks jenis izin (`Izin`, `Cuti`, `Sakit`) pada tampilan web dan output kode `LR` pada ekspor Excel matriks.

25. **Sistem Face Recognition Adaptive per Jabatan Karyawan (SELESAI 31 Agustus 2026, APK v1.0.108 - v1.0.110)**:
    - **Pengaturan per Jabatan di Admin Panel**:
      - Ditambahkan toggle `require_face_recognition` (boolean, default: true) pada menu *Master Data > Positions*.
      - Kolom indikator visual `Wajib Face AI` pada tabel daftar jabatan.
    - **Standarisasi Kamera Liveness Wajib (Anti-Fraud & Anti-Spoofing)**:
      - Seluruh pengambilan foto presensi (baik jabatan wajib biometrik maupun opsional) **wajib melalui deteksi wajah AI & kedip mata (Liveness Detection)** secara real-time.
      - **Peniadaan Tombol Jepret Manual**: Tombol manual shutter ditiadakan dari kamera presensi agar karyawan tidak dapat mengambil foto sembarangan (misal: foto tembok, benda mati, atau foto cetak).
      - **Matriks Kebijakan Presensi Wajah**:
        | Fitur / Parameter | Wajib Face Recognition (Aktif) | Opsional Face Recognition (Non-Aktif) |
        | :--- | :--- | :--- |
        | **Kamera Presensi** | Wajib AI Liveness (Deteksi Wajah & Kedip) | Wajib AI Liveness (Deteksi Wajah & Kedip) |
        | **Tombol Manual Shutter** | ❌ Ditiadakan | ❌ Ditiadakan |
        | **Foto Master Wajah** | ⚠️ Wajib Terdaftar (Ada Notifikasi Wajib) | ℹ️ Bebas / Tidak Wajib Terdaftar |
        | **Tujuan Keamanan** | Verifikasi Biometrik Identitas Penuh | Memastikan Kehadiran Fisik Orang Asli |

26. **Sistem Registrasi & Notifikasi Wajah Master (Face Master Enrollment) (SELESAI 31 Agustus 2026, APK v1.0.109 - v1.0.110)**:
    - **Dashboard Banner & Notifikasi**:
      - Untuk jabatan yang wajib Face Recognition dan belum memiliki foto master wajah, muncul **Banner Alert Merah / Oranye** di Dashboard: *"Registrasi Wajah Master Diperlukan (WAJIB)"* lengkap dengan tombol aksi langsung: **`📸 Daftarkan Wajah Master Sekarang`**.
      - Toast peringatan otomatis muncul saat aplikasi dibuka.
    - **Menu Cepat Dashboard & Profil**:
      - Penambahan menu cepat **"Wajah Master"** pada menu lainnya di Dashboard.
      - Halaman Profil dilengkapi kartu khusus status biometrik Face Recognition (`Wajah Master Terdaftar ✅` vs `Belum Terdaftar ⚠️`) dan opsi pendaftaran via Kamera AI Liveness, Kamera Biasa, atau Galeri.
    - **Monitoring Admin Panel**:
      - Label form edit karyawan disempurnakan menjadi **"Foto Master Wajah / Profil (Face Recognition Reference)"**.
      - Kolom `Face Master` dengan status boolean pada tabel daftar Karyawan (`EmployeesTable.php`).
    - **Rilis APK v1.0.110**:
      - Versi aplikasi dinaikkan ke **`v1.0.110+110`**.
      - File APK rilis siap pasang berhasil dikompilasi: `app-release-1.0.110.apk` dan `app-release.apk` (106.0 MB).

27. **Perbaikan Syntax Token "catch" pada Endpoint Absensi Mobile (SELESAI 1 September 2026)**:
    - Menutup bracket kurung kurawal `}` yang belum tertutup pada blok validasi `if ($request->type === 'checkin')` di `AttendanceController.php`.
    - Mengeliminasi error `syntax error, unexpected token "catch"` saat karyawan melakukan Check-Out di aplikasi mobile.

28. **Resolusi Tampilan Logo Prinsiple di Master Data Admin & Pewarisan Logo Subdomain (SELESAI 1 September 2026)**:
    - **Penyelarasan Kolom Logo di Admin Panel**: Mengubah pemanggilan `ImageColumn` pada `PrincipalsTable.php` agar menggunakan accessor `logo_url` (`route('portal.logo')`) dengan fallback UI Avatar dinamis sesuai warna tema prinsiple.
    - **Pewarisan Logo Lintas Entitas (Subdomain Sibling Inheritance)**: Jika sebuah entitas prinsiple belum mengupload logo langsung (misal: PT ICI PAINT ALVA / TSM), sistem secara otomatis mewarisi (*inherit*) logo dari entitas saudara yang berada di bawah subdomain yang sama (`dulux.appsend.my.id`).
    - **Multi-Path Candidate Resolver**: Menyempurnakan resolver file aset di backend agar mendukung berbagai jalur penyimpanan fisik di server (public, private, storage symlink).

29. **Fitur Pengajuan BAP / Bukti Absensi Manual (SELESAI 1 September 2026, APK v1.0.111)**:
    - **Form Pengajuan 2-Tab di Mobile App (`bap_screen.dart`)**:
      - **Tab 1 (Form Pengajuan)**: Pilihan tanggal terjadwal otomatis (roster/visit) yang belum absen dalam 30 hari terakhir, Time Picker jam masuk & jam pulang, dropdown 5 kategori kendala teknis (Aplikasi Error, Sinyal/GPS, HP Rusak/Baterai, Server Error, Lainnya), upload bukti screenshot kamera timestamp / GPS map camera dari Galeri/Kamera, dan textfield alasan detail kendala teknis.
      - **Tab 2 (Riwayat Pengajuan)**: Kartu riwayat dengan badge status dinamis (Kuning: Menunggu Verifikasi, Hijau: Disetujui, Merah: Ditolak beserta alasan penolakan), jam masuk/pulang, dan dialog pembesar thumbnail bukti screenshot.
    - **Admin Panel Filament (`AttendanceBapResource`)**:
      - Menu baru **Attendance & Kehadiran > Pengajuan BAP (Bukti Absen)** dengan badge counter notifikasi pending.
      - Aksi **Setujui (Approve)**: Otomatis mencatat/memperbarui record `attendances` menjadi `present` (hadir) beserta `attendance_logs` (`source: bap_manual`), menghilangkan status Alpha di laporan presensi.
      - Aksi **Tolak (Reject)**: Modal input alasan penolakan dan push notifikasi ke aplikasi mobile.
    - **Backend REST API**:
      - `GET /api/baps/eligible-dates`: Mengambil tanggal jadwal belum absen tanpa duplikasi pengajuan.
      - `POST /api/baps`: Validasi upload bukti dan input jam kerja.
      - `GET /api/baps/history`: Daftar riwayat pengajuan karyawan.
      - Route `/portal-assets/bap-evidence/{id}` untuk streaming preview file bukti yang aman.
30. **Redesain Landing Page Utama (Clean Modern Enterprise) & Filter Karyawan Aktif (SELESAI 1 September 2026)**:
    - **Filter Total Karyawan Aktif**: Menyesuaikan query statistik global di `routes/web.php` agar hanya menghitung karyawan aktif yang terlindungi (`where('is_active', true)->whereNull('deleted_at')`), serta prinsiple aktif dan lokasi kerja aktif.
    - **Tampilan Clean & Modern SaaS**: Memperbarui antarmuka utama (`landing.blade.php`) dengan desain bernuansa bersih (light clean background, tipografi modern *Plus Jakarta Sans*, navbar responsif dengan logo fallback, dan CTA download APK langsung).
    - **Katalog 10 Fitur Lengkap Sistem**: Menampilkan seluruh modul unggulan yang telah dibuat:
      1. *Live Geofencing & GPS Tracking*
      2. *AI Liveness Face Recognition*
      3. *Dynamic Form Reporting Hub (10+ Templates)*
      4. *Adaptive Roster & Multi-Shift Scheduling*
      5. *Itinerary & Route Visit Management*
      6. *BAP & Bukti Absensi Manual*
      7. *Whitelabel Principal Portal*
      8. *Request Lokasi Toko Baru*
      9. *Extra Hours & Lembur Real-Time*
31. **Redesain Halaman Login Admin Panel (Clean Modern Aesthetic) (SELESAI 1 September 2026)**:
    - **Penyelarasan Tampilan Clean Light Theme**: Menghilangkan dark theme gelap pada halaman login Admin Filament (`/admin/login`) dengan mengaktifkan `darkMode(false)` dan menyuntikkan custom CSS layout bernuansa terang elegan (`#F8FAFC`, kartu putih `#FFFFFF`, border `#E2E8F0`, rounded 24px, soft shadow).
    - **Kustomisasi Header & Kontrol**: Membuat custom Login class `App\Filament\Pages\Auth\Login` dengan judul *"Masuk ke Admin Panel"* dan subjudul *"Sistem Presensi & Manajemen Kinerja Terintegrasi"*, input form modern, tombol gradient primary, serta link navigasi kembali ke halaman utama.

32. **Penyempurnaan Header & Segmented Tab Layar Pengajuan BAP (SELESAI 1 September 2026, APK v1.0.112)**:
    - **Resolusi Header Hilang di Mobile**: Memperbaiki `bap_screen.dart` dengan memisahkan AppBar dari Tab Selector. Menyediakan AppBar standar dengan judul tebal *"Pengajuan BAP (Bukti Absen)"*, tombol kembali (`arrow_back`), dan tombol segarkan data (`refresh`).
    - **Segmented Tab Control**: Menempatkan tombol tab switch di bagian atas `body` berbalut kartu kontainer modern dan bayangan halus.
    - **Rilis APK v1.0.112**: Versi mobile dinaikkan ke **`v1.0.112+112`**.

33. **Direct Login Routing untuk Subdomain Portal Prinsiple (SELESAI 1 September 2026)**:
    - **Bypass Landing Page Subdomain**: Ketika prinsiple/klien mengakses URL subdomain khusus (misal: `https://dulux.appsend.my.id/`), sistem langsung mengarahkan pengguna ke formulir login portal whitelabel (`TenantAuthController@showLoginForm`) tanpa perlu melalui landing page.
    - **Auto-Redirect Dashboard**: Jika sesi pengguna sudah terotentikasi, sistem langsung mengarahkan ke dashboard portal (`/portal`).

34. **Smart Single-Door Login System (Sistem Login Satu Pintu Cerdas) (SELESAI 1 September 2026)**:
    - **Deteksi Role & Entitas Otomatis**: Pengguna dapat masuk melalui pintu login manapun (`/login` maupun `/admin/login`). Sistem secara cerdas mendeteksi tipe akun yang sedang melakukan autentikasi.
    - **Smart Redirect User Prinsiple**: Jika user yang masuk adalah akun *Principal PIC / Client User*, sistem otomatis mengarahkan ke *Executive Dashboard Portal Prinsiple* (`/portal` dengan parameter entitas atau subdomain yang sesuai).
    - **Smart Redirect Admin & Super Admin**: Jika user yang masuk adalah internal Admin / HR / Super Admin, sistem secara otomatis mengarahkan ke *Admin Panel Filament* (`/admin`).
    - **Guard & Fallback Perlindungan**: Menambahkan custom `LoginResponse` dan dashboard interceptor di Filament sehingga user prinsiple tidak terdampar di halaman panel admin melainkan selalu dialihkan ke portal analitik mereka.

---


## 📌 Rencana Lanjutan Berikutnya (Next Milestones)
1. **Lanjutan Monitoring & Rekap Operasional:**
   - Evaluasi integrasi data jadwal visit schedule dan kehadiran di mobile app saat karyawan check-in via lokasi terjadwal visit.
   - Penambahan filter lanjutan pada laporan presensi dan ekspor data audit penyesuaian manual/import.
2. **Peningkatan Skalabilitas & Media Storage:**
   - Integrasi penyimpanan awan (*Cloud Storage S3 / Spaces / GCS*) untuk media foto presensi dan laporan visit.


