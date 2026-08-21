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

### 3. Tahapan Eksekusi (Milestones)
- [ ] **Fase 1**: Migrasi database (`principals` branding columns, `report_templates`, `report_form_fields`, `report_submissions`, `report_submission_values`) & Eloquent Models.
- [ ] **Fase 2**: Google Form Style Builder di Super Admin Panel Filament (manajemen template, repeater builder, validasi, preview).
- [ ] **Fase 3**: Konfigurasi Multi-Tenant Subdomain Panel Prinsiple (`PrincipalPanelProvider`, dynamic theme & logo, scoped queries).
- [ ] **Fase 4**: Halaman Laporan, Galeri Bukti Foto, Peta GPS, dan Ekspor Excel/PDF Dinamis di portal prinsiple.
- [ ] **Fase 5**: REST API Endpoint untuk sinkronisasi schema form dan submission data.
- [ ] **Fase 6**: Dynamic Form Renderer di Flutter Mobile App (render field dinamis, kamera, tanda tangan, GPS, offline storage & sync).
- [ ] **Fase 7**: Pengujian komprehensif, verifikasi subdomain di live server, build & deploy.

---

## 📌 Rencana Lanjutan Berikutnya (Next Milestones)
1. **Lanjutan Monitoring & Rekap Operasional:**
   - Evaluasi integrasi data jadwal visit schedule dan kehadiran di mobile app saat karyawan check-in via lokasi terjadwal visit.
   - Penambahan filter lanjutan pada laporan presensi dan ekspor data audit penyesuaian manual/import.
2. **Peningkatan Skalabilitas & Media Storage:**
   - Integrasi penyimpanan awan (*Cloud Storage S3 / Spaces / GCS*) untuk media foto presensi dan laporan visit.



