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

35. **Live Chat Helpdesk Real-Time & Self-Service Login Assistance (SELESAI 1 September 2026, APK v1.0.113)**:
    - **Input & Validasi NIK Terintegrasi**: Karyawan yang terkendala login dapat mengakses menu bantuan langsung dari bawah form login. Input NIK otomatis memunculkan kartu verifikasi data karyawan (Nama, Jabatan, Prinsiple/Cabang, dan status keterikatan perangkat HP).
    - **Seleksi Kasus & Form Pembuka**: Karyawan memilih jenis kendala (*Unlock Device / Ganti HP* atau *Lupa Password / Reset PIN*) dan mengisi keterangan singkat sebelum memulai sesi obrolan.
    - **Ruang Live Chat Real-Time**: Sesi chat helpdesk terbuka seketika dengan pesan tiket pembuka terstruktur. Dilengkapi auto-refresh dan event broadcast real-time.
    - **Tombol Aksi Cepat 1-Klik Admin Panel**: Di Admin Live Chat (`/admin/live-chat`), Admin memiliki tombol aksi 1-klik `[Unlock Device]` (langsung membersihkan `device_id` & `fcm_token`) dan `[Reset Password]` (langsung mereset password dan mengirimkan pesan kredensial baru ke chat karyawan).
36. **Penggabungan Form Reporting Dulux & Kategori Tinter (SELESAI 1 September 2026)**:
    - **Penyatuan Form Stock End**: Menggabungkan form *Laporan Tinter & Pasta Warna LSO Dulux* ke dalam *Laporan Stock End Dulux*.
    - **Kategori Dramatone & Acotone**: Menambahkan input pilihan kategori (*Dramatone* dan *Acotone*) dengan daftar varian tinter/pasta warna yang muncul secara dinamis sesuai kategori yang dipilih.
    - **Pembersihan Template Lama**: Menghapus template terpisah `RPT-DULUX-TINTER-LSO` dan merekonstruksi 12 field inputan terintegrasi pada template `RPT-DULUX-STOCK-END`.
    - **Fix PostgreSQL Column Issue**: Memperbaiki migrasi dan seeder agar kompatibel penuh dengan skema database PostgreSQL tanpa referensi kolom `default_value`.

37. **Deduplikasi & Proteksi Integritas Data Karyawan (Single Active NIK) (SELESAI 1 September 2026)**:
    - **Single Active NIK Rule**: Menambahkan hook otomatis pada model `Employee` (`saving` event) yang memastikan bahwa hanya ada 1 NIK aktif di seluruh sistem. Jika ada data baru atau pembaruan NIK, data ganda lama otomatis di-set non-aktif (`employment_status = resigned`).
    - **Fitur Hapus Roster Karyawan**: Menambahkan aksi hapus (*Delete Action & Bulk Delete*) pada halaman *Employee Schedule Roster* di Admin Panel untuk membersihkan jadwal yang tidak lagi valid.

38. **Pengaturan Face Recognition Default OFF & Wajib Master Wajah (SELESAI 1 September 2026, APK v1.0.114)**:
    - **Default Face Recognition OFF**: Mengubah seluruh default pengaturan *Face Recognition* pada level jabatan/posisi (`Position`) menjadi `false` (Non-Aktif).
    - **Aksi Massal Admin**: Menyediakan tombol aksi massal *"Set Semua Face Recognition OFF"* di Admin Panel untuk mereset seluruh posisi secara instan.
    - **Validasi Wajib Master Wajah**: Jika fitur *Face Recognition* diaktifkan (ON) pada suatu jabatan, sistem di backend dan mobile app mewajibkan karyawan mendaftarkan foto master wajah terlebih dahulu. Karyawan diblokir dari Check-In / Visit-In / Meet-In dengan dialog panduan langsung ke menu pendaftaran foto profil master wajah.

39. **Penyempurnaan Lampiran Bukti Permit/Cuti & Tampilan Cuti Peraturan (SELESAI 1 September 2026)**:
    - **Resolusi Foto Lampiran Permit**: Memperbaiki masalah gambar bukti permit yang rusak (*broken icon*) pada halaman *View Leave Request* dengan membuat komponen `permit-attachment.blade.php` yang mendukung rendering foto (.jpg, .png) maupun dokumen PDF (.pdf).
    - **Dedicated Streamer Route**: Menambahkan rute streaming langsung `/attachment-stream/{id}` dan fallback route `/storage/{path}` di `routes/web.php` untuk memastikan file lampiran selalu dapat diakses dan diunduh.
    - **Tampilan Jenis Cuti Peraturan**: Menampilkan badge detail *Jenis Cuti Peraturan* (misal: *Cuti Menikah*, *Cuti Istri Melahirkan*, *Cuti Kematian*, dll.) pada halaman detail permit dan kolom tabel *Leave Requests*.

40. **Modernisasi Dashboard, Statistik Prinsiple Aktif & Multi-Line Attendance Chart (SELESAI 1 September 2026)**:
    - **Koreksi Statistik Active Principals**: Memperbaiki kartu statistik dashboard agar menghitung prinsiple yang aktif secara akurat dan melampirkan keterangan jumlah prinsiple non-aktif.
    - **Attendance Overview Multi-Line Chart**: Mengubah grafik absensi menjadi *Line Chart* interaktif dengan palet warna unik untuk setiap prinsiple.
    - **Pembersihan Legend & Tooltip**: Menghilangkan legenda 100+ entitas yang menumpuk di bawah chart dan menyaring tooltip *hover* hanya untuk prinsiple yang memiliki data absensi aktif (>0).
    - **Native Searchable Filter Popover (Prinsiple & Area)**: Menambahkan tombol filter popover Filament (`HasFiltersSchema` + `InteractsWithActions`) yang bersih, dengan input dropdown pencarian teks langsung (*searchable autocomplete*) yang responsif dan bebas eror.
    - **Kolom Prinsiple & Area di Recent Attendances**: Menambahkan kolom *Prinsiple* dan *Area / Cabang* pada tabel absensi terbaru di dashboard utama.

41. **Multi-Server Production Infrastructure & Full Activation (SELESAI 2 September 2026)**:
    - **Aktivasi 3 Server Production Mandiri**:
      - **Server 1 (PT AMK & Mobile API Gateway)**: IP `38.103.170.235` (Ubuntu 24.04, Nginx, PHP 8.3, PostgreSQL `db_esa_amk`). Domain aktif: `amk.dgsoft.web.id`, `api.dgsoft.web.id`.
      - **Server 2 (PT AKP)**: IP `38.103.170.223` (AlmaLinux 8, Nginx, PHP 8.3, PostgreSQL `db_esa_akp`). Domain aktif: `akp.dgsoft.web.id`.
      - **Server 3 (PT ATK / Gabungan)**: IP `38.103.170.224` (Rocky Linux 8, Nginx, PHP 8.3, PostgreSQL `db_esa_atk`). Domain aktif: `atk.dgsoft.web.id`.
    - **Penyelesaian Masalah Lingkungan Server**:
      - Konfigurasi aaPanel Nginx URL rewrite (`try_files $uri $uri/ /index.php?$query_string;`) untuk routing Laravel yang sempurna.
      - Pembaruan Composer v2.8+ pada Rocky Linux 8 untuk runtime PHP 8.3.
      - Penerbitan sertifikat SSL Let's Encrypt dengan HTTP-01 File Verification.
      - Pengujian konektivitas PostgreSQL dan pengamanan izin direktori `storage/` dan `bootstrap/cache/`.

42. **Cross-Server Auto-Sync API untuk Templat Formulir Laporan (SELESAI 2 September 2026)**:
    - **Arsitektur Sinkronisasi Lintas Server**:
      - File konfigurasi `config/esa_sync.php` memetakan endpoint ketiga server production dan shared secret token (`ESA_SYNC_SECRET`).
      - Layanan `TemplateSyncService.php`: menangani proses serialisasi/ekspor skema templat dan field form laporan, impor aman (upsert), dan *HTTP broadcast* ke server peer.
      - API Controller `TemplateSyncController.php`: mengekspos endpoint aman `POST /api/v1/sync/report-template` dan health check `GET /api/v1/sync/ping`.
    - **Aksi 1-Klik di Filament Web Admin**:
      - Menambahkan tombol aksi `syncToPeers` pada tabel templat laporan (`ReportTemplatesTable.php`) dan tombol header pada halaman edit templat (`EditReportTemplate.php`).
      - Pengguna dapat mengedit templat laporan di satu server (misal Server AMK) dan mendistribusikannya secara otomatis ke Server AKP dan ATK secara real-time.

43. **Perbaikan Multi-Tier Kolom Prinsiple Klien & Command Relasi (SELESAI 2 September 2026)**:
    - **Multi-Tier Fallback Kolom Prinsiple**: Mengembangkan logika penampil data prinsiple klien pada tabel templat laporan (`ReportTemplatesTable.php`) dengan 3 lapis pemeriksaan:
      1. Relasi Many-to-Many (`$record->principals`).
      2. Relasi BelongsTo (`$record->principal`).
      3. Penelusuran kata kunci nama templat terhadap master prinsiple (Fonterra, Dulux, Wings, MamaSuka, Sido Muncul).
    - **Artisan Command & Endpoint Sinkronisasi**: Membuat command `php artisan reporting:link-principals` dan endpoint web `/fix-principals` untuk secara permanen memetakan relasi foreign key antara templat laporan dan prinsiple di database.

44. **Sistem Master Auto-Deployment Terpadu (CLI & Web Console) (SELESAI 2 September 2026)**:
    - **Web Console Deployment 1-Klik (`public/deploy-production.php`)**:
      - Antarmuka web modern dark-mode dengan live streaming log seperti terminal asli.
      - Menampilkan status kartu 3 server production beserta tombol eksekusi deploy paralel.
      - Penanganan izin keamanan *open_basedir* dengan penempatan deploy key khusus di `storage/app/deploy_key/id_rsa`.
      - Pembaruan otomatis script deploy server dev (`public/deploy.php`) terintegrasi dengan migrasi database dan sinkronisasi prinsiple.
    - **Master CLI Script (`deploy.sh` & `deploy-all-production.sh`)**:
      - Menu interaktif di terminal server development `appsend.my.id` untuk deploy lokal dev, deploy 3 server production, atau deploy semua server sekaligus.
      - Skrip pairing kunci SSH otomatis (`setup-ssh-keys.sh`) untuk login tanpa password antar server.

45. **Transisi Domain Utama Produksi (`esa-solutions.id`) & Whitelabel Portal Prinsiple (SELESAI 2 September 2026)**:
    - **Panduan Migrasi Zero-Downtime**: Menyusun dokumen lengkap `PANDUAN_MIGRASI_SWITCH_DOMAIN_UTAMA_ESA.md` yang mencakup pemetaan DNS A Record, penambahan domain di aaPanel, penerbitan SSL, penyesuaian file `.env`, dan checklist verifikasi.
    - **Penyelarasan Domain Portal Prinsiple**:
      - Memperbarui schema form edit master prinsiple (`PrincipalForm.php`): mengubah domain suffix subdomain dari `.appsend.my.id` menjadi **`.esa-solutions.id`**.
      - Memperbarui model `Principal.php` pada atribut `getPortalUrlAttribute()` agar mengarahkan link portal ke `https://{subdomain}.esa-solutions.id`.
      - Menyesuaikan middleware `IdentifyTenantSubdomain` untuk mengenali subdomain kustom tenant dan melindungi subdomain reserved server internal (`amk`, `akp`, `atk`).

46. **Transformasi & Rekonstruksi Laporan Offtake Dulux (SELESAI 4 September 2026)**:
    - **Ingestion Data Historis Excel (Jan - Jul 2026)**: Memproses 8.289 baris data transaksi penjualan, mencakup ratusan toko dan DC dengan total nilai transaksi Rp 37+ Miliar.
    - **High-Performance Storage**: Dioptimalkan menggunakan database SQLite terindeks (`dulux_offtake.sqlite` & `dulux_offtake.sqlite.gz`) dengan latensi kueri ultra-cepat (<5ms).
    - **Dashboard Multi-Tab Interaktif**:
      - **Tab Sheet 1 (Laporan Penjualan Offtake / Raw Submissions)**: Filter tanggal/bulan, area, toko, DC, pencarian, dan pagination.
      - **Tab Sheet 2 (Rekap Volume Toko & Target)**: Tabel pivot bulanan (Jan-Jul), perbandingan volume Dulux vs Catylac vs Lainnya, pencapaian target, dan pertumbuhan MoM (*Month-over-Month*).
      - **Tab SCM (Supply Chain Management)**: Rekap pergerakan stok, distribusi catylac, dan rasio pemenuhan.
      - **Tab Pivotable & Analytics**: Filter dinamis berdasarkan Channel (LSO, SSO), Kategori Produk, dan RSM Area.
    - **Export Multi-Format**: Dukungan ekspor data ke Excel dan CSV untuk setiap tab laporan.

47. **Transformasi & Rekonstruksi Laporan Out of Stock / OOS Dulux (SELESAI 4 September 2026)**:
    - **Ingestion Data Historis Excel OOS 2026**: Memproses 7.671 baris data pencatatan OOS mingguan dan bulanan.
    - **High-Performance Storage**: Database SQLite terindeks (`dulux_oos.sqlite` & `dulux_oos.sqlite.gz`).
    - **Dashboard 3 Tab Interaktif**:
      - **Tab 1: Rekap Alasan & Channel (Summary)**: 6 Kartu KPI Utama (Total Insiden OOS, Toko Terdampak, Item SKU OOS, Estimasi Lost Sales Rp, Rata-rata Durasi OOS, % Toko Bebas OOS), visualisasi analisis akar penyebab OOS (*Distributor Delay, Demand Surge, Factory Limitation, Store PO Delay*), dan sebaran Channel LSO vs SSO.
      - **Tab 2: Matriks Mingguan per Toko (Weekly Matrix)**: Pivot mingguan status OOS per toko (W1–W52), tombol toggle *'Sembunyikan Toko 0 OOS'* vs *'Tampilkan Semua Toko'*, serta paginasi toko.
      - **Tab 3: Data Mentah Submission (Raw Submissions)**: Tabel lengkap 7.671 riwayat pelaporan OOS dengan filter status, channel, area, dan pencarian instan.
    - **Sinkronisasi Template Form**: Menyelaraskan form input mobile/web template `RPT-DULUX-OOS` (SKU Produk, Alasan OOS, Estimasi Kebutuhan, Tindak Lanjut).

48. **Transformasi & Rekonstruksi Laporan Daily Maintenance POST & Mesin Tinting (SELESAI 4 September 2026)**:
    - **Ingestion Data Historis Excel**: Memproses 3.842 baris data riwayat perawatan mesin tinting dan display POST di 324 toko.
    - **High-Performance Storage**: Database SQLite terindeks (`daily_maintenance.sqlite` & `daily_maintenance.sqlite.gz`).
    - **Dashboard 3 Tab Interaktif**:
      - **Tab 1: Ringkasan Pemeliharaan (Executive Summary)**: 6 Kartu KPI (Toko Aktif Mesin, Total Cek Fisik, Kepatuhan Nozzle OK %, Kepatuhan Kalibrasi %, Kesiapan POST %, Skor Kepatuhan Nasional), visualisasi kondisi komponen (*Nozzle Cleaning, Level Canister Tinting, Kalibrasi Timbangan, Display POST, Agitator, Software POS*), dan breakdown kepatuhan regional.
      - **Tab 2: Matriks Toko & Frekuensi (Store Matrix)**: Riwayat perawatan harian per toko, frekuensi perawatan bulanan, identitas mesin (No. Mesin, Model), dan skor kepatuhan toko.
      - **Tab 3: Data Mentah Pemeriksaan (Raw Submissions)**: Tabel detail riwayat checklist harian petugas DC/Promotor.
    - **Sinkronisasi Template Form**: Menyelaraskan field checklist form `RPT-DULUX-DAILY-MAINTENANCE` secara menyeluruh dengan data lapangan.

49. **Transformasi & Rekonstruksi Laporan Data Pelanggan & Konsumen Dulux (SELESAI 4 September 2026)**:
    - **Ingestion Data Historis Excel 2025–2026**: Memproses 5.522 data konsumen unik dengan akumulasi transaksi belanja senilai Rp 37,74 Miliar yang tersebar di 324 toko dan 497 DC/promotor.
    - **High-Performance Storage**: Database SQLite terindeks (`customer_db.sqlite` & `customer_db.sqlite.gz`).
    - **Dashboard 3 Tab Interaktif**:
      - **Tab 1: Profil & Perilaku Konsumen (`tab=insights`)**:
        - 6 Kartu KPI Utama: Total Konsumen (5.522), Total Nilai Belanja (Rp 37,74 Miliar), Rata-rata Belanja / Basket Size (Rp 6,83 Juta/Orang), Toko Aktif (324), DC Terlibat (497), dan Konversi Switch ke Dulux (1.158 Konsumen / 21.0%).
        - Visualisasi Insight: Segmentasi Tipe Pelanggan (Pemilik Rumah 68%, Tukang Cat 14%, Kontraktor 12%, Mitra Dulux 6%), Alasan Memilih Brand (Rekomendasi DC 52.2%, Kualitas 30.8%, Harga 9.9%), Preferensi Brand Ditanyakan vs Dibeli (Analisis Switch Kompetitor Jotun, Nippon, Avian, Mowilex, Propan ke Dulux/Catylac), Kebutuhan Proyek, Dulux Visualizer, dan Painter Loyalty Club.
      - **Tab 2: Analisis Toko & Wilayah (`tab=regional_store`)**: Tabel Matriks Performa 10 RSM Area, Peringkat Top Toko Paginated, dan Top 20 Promotor/DC Teraktif.
      - **Tab 3: Data Mentah Pelanggan (`tab=raw`)**: Tabel 5.522 data mentah konsumen lengkap dengan tombol cepat **🟢 WhatsApp Direct Chat Link** (`wa.me`), filter, pencarian, dan pagination bar.
    - **Perbaikan Rute URL & Error Handling**: Memperbaiki exception `UrlGenerationException` pada rute pagination tabel toko dan data mentah.
50. **Audit & Peningkatan Menyeluruh Form Pelaporan Dulux, Auto-Calculations, Multi-Kategori, dan Release APK v1.0.120 (SELESAI 5 September 2026)**:
    - **Presisi Jenis Input (Input Types & Keyboards)**:
      - Penerapan tipe input dinamis presisi di `dynamic_form_screen.dart`: `number` (kuantiti diskrit), `numberWithOptions(decimal: true)` (volume liter, persentase), `phone` (nomor HP, WA, NIK, No KTP), `currency` (format Rupiah dinamis `Rp`), `photo/camera_photo` (Geotag Watermark permanen toko, waktu, koordinat GPS, nama promotor & NIK), `signature` (dialog canvas tanda tangan digital interaktif), dan `month_year` (dialog bulan-tahun kadaluarsa).
    - **Integrasi Master Produk & Quick Barcode Scanner**:
      - Quick Action Barcode Scanner dan bottom sheet dialog katalog master produk Dulux.
      - Auto-populate otomatis untuk atribut master: Kategori, Kemasan (Galon/Pail/Kaleng), Min Stock, dan verifikasi chip info produk.
    - **Multi-Kategori Kontinu & Dual Action Buttons**:
      - Tombol Aksi Ganda `Kirim & Input Baru` (menyimpan submission kategori aktif, mereset form untuk kategori berikutnya tanpa keluar form, mengunci kategori yang sudah disubmit agar tidak dipilih berulang) vs `Kirim & Selesai`.
      - Banner visual session progress chips untuk menampilkan riwayat kategori yang sudah disimpan dalam sesi pelaporan.
    - **Reactive Auto-Calculations & Read-Only Badge Engine**:
      - Auto-calculation real-time saat mengetik kuantiti/memilih kemasan:
        - Volume Galon (L) = $\text{Qty Galon} \times 2.5\text{ L}$
        - Volume Pail (L) = $\text{Qty Pail} \times 20.0\text{ L}$
        - $\text{Total Unit} = \text{Qty Galon} + \text{Qty Pail}$
        - $\text{Total Volume (Liter)} = \text{Volume Galon (L)} + \text{Volume Pail (L)}$
        - $\text{Total Volume Stok End (Liter)} = (\text{Stok Galon} \times 2.5\text{ L}) + (\text{Stok Pail} \times 20.0\text{ L})$
        - $\text{Estimasi Market Share (\%)} = (\text{Customer Beli Dulux} / \text{Customer Beli Cat}) \times 100\%$
      - Field hasil kalkulasi otomatis dikunci `readOnly: true` dengan badge visual **"Dihitung Otomatis"** dan icon kalkulator.
    - **Backend Migrations & Seeding Presets**:
      - Migration `2026_09_05_210000_update_dulux_form_fields_calculations_and_readonly.php` & seeder `ReportTemplatePresetsSeeder.php` untuk mengunci field kalkulasi dan melengkapi formula pada seluruh template Dulux.
    - **Mobile Versioning & Production APK Build**:
      - Versi mobile dinaikkan ke **`1.0.120+120`** di `pubspec.yaml`.
      - Konfigurasi dependensi Gradle Kotlin DSL (`plugins.withId("com.android.library")`) dan `packaging` excludes untuk `desktop.ini` & `androidx.concurrent:concurrent-futures:1.2.0`.
      - Kompilasi sukses: `app-release-1.0.120.apk` (107.2 MB).
    - **Deployment & Multi-Server Synchronization**:
      - Seluruh kode ter-push ke GitHub (`digitalgalery-dgsoft/attendaceesa` branch `main`).
      - Sinkronisasi template database sukses di-deploy ke Development Server (`appsend.my.id`) dan 3 Production Nodes (`amk.dgsoft.web.id`, `akp.dgsoft.web.id`, `atk.dgsoft.web.id`), seluruhnya terverifikasi aktif dengan status HTTP 200 OK.

51. **Modernisasi Laporan Stock End Dulux: Komparasi Tren Harian Bulanan (CY vs PY) & Filter Brand Dinamis (SELESAI 6 September 2026)**:
    - **Penggantian Metrik YTD ke Komparasi Bulanan**:
      - Mengganti modul YTD lama dengan komparasi data bulanan tahun berjalan (misal Juli 2026) terhadap bulan yang sama pada tahun sebelumnya (Juli 2025).
      - Menghadirkan **Grafik Garis/Area Tren Harian Interaktif (ApexCharts)** dari Tanggal 01 s/d 31 untuk membaca fluktuasi stok secara presisi antara CY (*Current Year*) dan PY (*Previous Year*).
    - **5 Kartu KPI Eksekutif Komparasi Bulanan**:
      - Total Volume Stock Bulan Berjalan (CY) vs Tahun Sebelumnya (PY) beserta persentase pertumbuhan YoY (*Year-over-Year*) dan selisih volume liter.
      - Jumlah Toko Aktif Melapor (CY vs PY).
      - Rata-rata Volume Stock per Toko (CY vs PY).
      - Kontribusi Volume Brand Dulux (Liter & % Share).
      - Kontribusi Volume Brand Catylac (Liter & % Share).
    - **Tabel Rincian Harian & Komparasi Toko Utama**:
      - Tabel pergerakan stok harian Tanggal 01 s/d 31 lengkap dengan volume CY, volume PY, selisih (delta), dan persentase pertumbuhan.
      - Tabel komparasi performa Top 10 Toko dengan volume terbesar pada bulan tersebut.
    - **Filter Brand Terpadu (Semua Brand, Dulux, Catylac)**:
      - Menambahkan dropdown filter Brand di toolbar filter utama yang terhubung secara seamless ke seluruh tab analisis:
        - Tab 1: Tren Harian & Komparasi Bulanan (`tab=monthly`)
        - Tab 2: Rekap Volume Stock Toko (`tab=pivot`)
        - Tab 3: Ringkasan SCM & Stock (`tab=scm`)
        - Tab 4: Raw Data Submissions (`tab=raw`)
      - Penyesuaian otomatis pada kueri SQLite `stock_2026.sqlite` & `stock_2025.sqlite` serta integrasi ke fitur ekspor CSV data komparasi bulanan.

52. **Penyederhanaan Antarmuka Laporan CBP Dulux (SELESAI 6 September 2026)**:
    - **Pembersihan Kartu Statistik Overview CBP**:
      - Menghilangkan 5 kartu ringkasan statistik (Total Monitoring CBP, Rata-Rata MOP Dulux, Rata-Rata MOP Kompetitor, Indeks Rasio Harga, dan Toko Terpantau) pada bagian atas dashboard `cbp_dashboard.blade.php`.
      - Menjadikan antarmuka lebih bersih dan fokus langsung pada navigasi kategori produk (*Wallpaint*, *WTP MCC*, dan *Raw Data*) serta grafik analisis tren MOP harga pasar.

53. **Penyempurnaan Laporan Offtake Dulux: Label Total Akzonobel & Grafik Tren Garis Bulanan (SELESAI 6 September 2026)**:
    - **Penyelarasan Naming Total Akzonobel**:
      - Mengganti label baris total pada tabel komparasi YTD dari *"Total DC"* menjadi **"Total Akzonobel"** di controller dan tampilan view `report_detail.blade.php`.
    - **Grafik Garis/Area Tren Bulanan (ApexCharts)**:
      - Mengganti visualisasi perbandingan YTD dari diagram batang (bar chart) menjadi **Grafik Garis Tren Bulanan (Area Chart)** dari Januari s/d bulan terpilih (misal Juli) yang membandingkan pergerakan volume Tahun Berjalan (*CY 2026*) vs Tahun Sebelumnya (*PY 2025*).
      - Menampilkan kurva tren volume liter yang halus (*smooth curve*), marker titik, indikator gradien warna, dan tooltip interaktif.

54. **Pengaturan Frekuensi Jadwal Form Builder (Daily, Weekly, Monthly) & Monitoring Target Periode Cut-Off (SELESAI 6 September 2026)**:
    - **Database & Model Schema (`report_templates`)**:
      - Migration `2026_09_06_150000_add_schedule_settings_to_report_templates_table.php` menambahkan kolom:
        - `schedule_type`: string default `'daily'` (`daily`, `weekly`, `monthly`).
        - `target_count`: integer default `1` (target pengisian per minggu atau per bulan).
      - Model `ReportTemplate.php` dilengkapi:
        - Fungsi `calculateCutoffTarget($startDate, $endDate)` untuk kalkulasi kuota target dinamis per periode cut-off (harian, mingguan, bulanan).
        - Fungsi `isScheduledForDate($date)` untuk validasi hari aktif pengisian formulir.
    - **Admin Form Builder & Master Table (`ReportTemplateForm.php` & `ReportTemplatesTable.php`)**:
      - Section baru *"🗓️ Pengaturan Frekuensi Jadwal & Target Pengisian Form"*:
        - **Daily (Harian)**: Form aktif & muncul setiap hari / hari kerja yang dipilih.
        - **Weekly (Mingguan)**: Input jumlah target pengisian per minggu (`target_count`, misal: 2x/minggu) dan pilihan hari pelaporan wajib (`report_days`, multi-select: Senin s/d Minggu).
        - **Monthly (Bulanan)**: Input jumlah target pengisian per bulan/cut-off (`target_count`, misal: 1x/bulan) dan pilihan hari pelaporan wajib (`report_days`).
      - Kolom badge jadwal & target pada tabel template master Filament (`📅 Daily (Semua Hari)`, `🗓️ Weekly (2x/mg: Senin, Kamis)`, `📆 Monthly (1x/bln: Jumat)`) beserta filter dropdown frekuensi jadwal.
    - **Backend API Reporting Engine (`/api/v1/reporting/templates`)**:
      - Kalkulasi dinamis periode cut-off karyawan berdasarkan `departments.cutoff_start_date` (misal 26 s/d 25 atau 1 s/d akhir bulan).
      - Menghitung submission aktual karyawan dalam periode cut-off per template dan mengembalikan ringkasan kuota cut-off (`cutoff_info`: daily, weekly, monthly, overall target, aktual, & persentase).
    - **Aplikasi Mobile Karyawan (`att-mobile`)**:
      - **Header Card Overview Cut-Off Target**: Menampilkan banner periode cut-off aktif (misal: `26 Agu 2026 – 25 Sep 2026`), progress bar total laporan diselesaikan, dan 3 kartu metrik target: **Daily**, **Weekly**, dan **Monthly** dengan status kuota (contoh: `1/5 (20%)`).
      - **Template Form Card**: Dilengkapi badge frekuensi (`Daily`, `Weekly`, `Monthly`), progress chip dan bar `Target Periode Cut-Off: 1/5 (20%)`, serta status jadwal hari ini (`(Hari ini)`).
      - **Offline Caching**: Menampung dan menyimpan struktur `cutoff_info` pada local preferences untuk akses offline.
    - **Production APK Build & Multi-Server Synchronization**:
      - Versi mobile dinaikkan ke **`1.0.121+121`** di `pubspec.yaml`.
      - Kompilasi sukses: `app-release.apk` (107.4 MB).
      - Seluruh perubahan ter-push ke GitHub (`main`) dan tersinkronisasi via auto-deploy ke Development Server (`appsend.my.id`) serta 3 Production Nodes (`amk.esa-solutions.id`, `akp.esa-solutions.id`, `atk.esa-solutions.id`), seluruhnya terverifikasi aktif dengan status HTTP 200 OK.

55. **Pembaruan & Ingestion Menyeluruh Dataset Laporan Stock End Dulux 2025 (12 Bulan) & 2026 (7 Bulan) (SELESAI 6 September 2026)**:
    - **Pemrosesan Komprehensif Seluruh File Raw Excel (19 File Master)**:
      - Memproses 19 file raw Excel dari direktori `D:\Data Reporting\Dulux\Stock End`:
        - **Stock 2026 (7 File: Januari s/d Juli 2026)**: 85.967 baris data, 28.311.280,85 Liter total stok.
        - **Stock 2025 (12 File Lengkap: Januari s/d Desember 2025)**: 175.346 baris data, 57.365.944,08 Liter total stok.
        - **Total Keseluruhan Dataset Tergabung**: **261.313 baris data** dan **85.677.224,93 Liter**.
    - **High-Performance OpenXML Ingestion Engine & Resolusi Anomali Kolom**:
      - Engine ETL berbasis C# OpenXML & Windows `winsqlite3.dll` dengan arsitektur streaming untuk memproses dan mengindeks 261k baris dalam waktu ~30 detik tanpa kebocoran memori.
      - Resolusi otomatis anomali header kolom ganda (seperti pada file `202605 Stock End Mei 2026 AMK AKP.xlsx` di mana kolom 15 = Volume (L) dan kolom 16 = Factor Konversi).
      - Resolusi inversi kolom nama Brand dan Produk pada sheet raw data tertentu serta normalisasi kanonikal brand (*Dulux*, *Catylac*, *Catylac Smart Choice*).
    - **Optimalisasi Basis Data SQLite & Chunk JSONL**:
      - Rekonstruksi database berindeks penuh: `stock_2026.sqlite` (29.96 MB / .gz 4.57 MB) dan `stock_2025.sqlite` (61.64 MB / .gz 9.25 MB).
      - Menghasilkan 19 berkas JSONL chunk terkompresi (`stock_2026_m01..07.jsonl.gz` dan `stock_2025_m01..12.jsonl.gz`) di `storage/app/dulux_data/chunks/`.
      - Dilengkapi multi-column indexing presisi (`idx_stock_month`, `idx_stock_sap`, `idx_stock_brand`, `idx_stock_produk`, `idx_stock_perf`, `idx_stock_store`) untuk eksekusi kueri agregasi dashboard sub-millisecond (<10ms).
    - **Penyempurnaan Backend Dynamic Routing & Cache Engine (`PrincipalPortalController.php`)**:
      - Mendukung pemilihan database tahun secara dinamis (`stock_{$selectedYear}.sqlite`) dengan fallback cerdas ke 2026.
      - Penyesuaian rentang bulan aktif dinamis (Bulan 1..12 untuk tahun 2025, Bulan 1..7 untuk tahun 2026).
      - Pembaruan skema key cache dashboard dan filter dropdown (`stock_dash_v3_`, `stock_filter_regions_v2_{year}`, dll) agar data terbaru langsung terefleksi seketika tanpa sisa cache lama.
    - **Deployment & Multi-Server Synchronization**:
      - Database terkompresi `.sqlite.gz` dan seluruh chunk JSONL tersinkronisasi ke repository git dan di-deploy ke server staging/production.

56. **Penyempurnaan & Ingestion Menyeluruh Dataset Laporan CBP (Consumer Buying Price) Dulux 2025 & 2026 (SELESAI 6 September 2026)**:
    - **Pemrosesan Master Dataset Excel CBP**:
      - Memproses data dari master workbook `D:\Data Reporting\Dulux\Laporan CBP\2026\Price CBP 2026\Price CBP 2026\07. Dashboard CBP SSO_MOP July 2026.xlsx`:
        - **CBP 2026 (7 Bulan: Januari s/d Juli 2026)**: **107.472 baris data** monitoring harga bersih terdeduplikasi.
        - **CBP 2025 (12 Bulan Lengkap: Januari s/d Desember 2025)**: **278.599 baris data** monitoring harga bersih terdeduplikasi.
        - **Total Keseluruhan Dataset CBP Tergabung**: **386.071 baris data** tanpa ada data duplikat (0 duplicate records).
    - **Penyelarasan Pemetaan Kolom Harga & Sanitasi Kolom Reason**:
      - Menyelesaikan masalah angka harga (seperti `214.000`, `1.476.000`, `280.000`, `1.350.000`) yang sebelumnya masuk ke kolom `REASON` akibat anomali layout Excel di mana harga diletakkan pada kolom Harga Terendah/kolom bersebelahan.
      - Normalisasi harga Tin, Galon, dan Pail secara presisi: nilai harga otomatis dipetakan ke field harga (`price_tin`, `price_galon`, `price_pail`) dan harga terendah (`lowest_*`).
      - Sanitasi total kolom `REASON` (`reason_tin`, `reason_galon`, `reason_pail`) sehingga angka numerik dan artifak formula Excel dibersihkan (0 numeric reasons), dan hanya teks murni (seperti *Exist Store*, *Not Exist*, *Disc 2%*, *Promo*) yang disimpan.
    - **Deduplikasi Ketat & Normalisasi Kode/Brand**:
      - Deduplikasi multi-kunci berbasis `(sap_member | name_store) + area + product + brand` per bulan untuk memastikan integritas data.
      - Normalisasi brand dan sintesis kode master (`name_store + area + rsm_area + product`).
    - **Pembersihan Artefak Formula Excel & Penstabilan Grafik Tren MOP (`Tren MOP Bulanan & YoY`)**:
      - Menyelesaikan masalah grafik tren garis yang terlihat terputus/kosong pada bulan Jan–Jun akibat lonjakan nilai formula tak wajar (*formula calculation artifact* di master Excel seperti `1.86E+16`, `2.16E+12`, dan `1571055.58...`).
      - Menerapkan batasan ambang batas harga valid pada processor C# (`CleanPrice`): Tin (Rp 1.000 s/d 5.000.000), Galon (Rp 1.000 s/d 5.000.000), Pail (Rp 1.000 s/d 25.000.000).
      - Menghilangkan nilai non-angka pada kolom `REASON` dan menangani baris dengan *shifted column brand* dengan auto-inference ke brand group utama (AN/Dulux, Jotun, Nippon Paint, Avian/Aquaproof, Mowilex).
      - Seluruh 7 bulan di tahun 2026 (Januari – Juli) dan 12 bulan di 2025 kini menghasilkan grafik garis tren yang mulus, stabil, dan akurat (rata-rata harga galon berada di kisaran realistis Rp 220.000 – Rp 320.000).
    - **Basis Data SQLite & JSONL Chunks**:
      - Rekonstruksi database SQLite berindeks penuh: `cbp_2026.sqlite` (47.61 MB / .gz 8.23 MB) dan `cbp_2025.sqlite` (124.04 MB / .gz 21.94 MB).
      - Menghasilkan 19 file chunk JSONL terkompresi (`cbp_2026_m01..07.jsonl.gz` dan `cbp_2025_m01..12.jsonl.gz`) di `storage/app/dulux_data/chunks/`.
      - Multi-column indexing presisi (`idx_cbp_month`, `idx_cbp_brand`, `idx_cbp_area`, `idx_cbp_store`, `idx_cbp_code`, `idx_cbp_month_code`, `idx_cbp_category`, `idx_cbp_perf`) untuk eksekusi kueri agregasi dashboard secepat kilat (<10ms).
    - **Penyempurnaan Controller Portal & View Raw Data (`PrincipalPortalController.php` & `cbp_dashboard.blade.php`)**:
      - Mendukung routing database tahun dinamis `cbp_{$selectedYear}.sqlite` dengan fallback cerdas ke 2026.
      - Penanganan fallback cerdas `price_*` dan `lowest_*` pada tabel Raw Data dan Export CSV.
      - Cache key dinaikkan ke versi `cbp_dash_v8_` dan filter dropdown dinamis per tahun `cbp_filter_regions_v12_{year}`, `cbp_filter_areas_v12_{year}`, `cbp_filter_stores_v12_{year}`.

57. **Optimasi Kecepatan Form Builder (Template Laporan) & Universal Premium Loading Screen Web Admin (SELESAI 6 September 2026)**:
    - **Percepatan Loading Halaman Form Builder (Template Laporan)**:
      - **Penghapusan Preload Berat**: Menghapus `->preload()` pada Select `products`, `employees`, serta penugasan repeater `employee_id` dan `work_location_id` di `ReportTemplateForm.php`. Livewire kini tidak lagi men-dump ribuan SKU produk dan ribuan karyawan ke dalam memory/payload browser pada saat form pertama kali dirender.
      - **Pembersihan Hook Mount**: Menghapus sinkronisasi otomatis `syncDuluxMergedStockEnd()` dari method `mount()` di `ListReportTemplates.php` sehingga halaman daftar template tidak lagi menjalankan puluhan operasi baca/tulis/hapus database yang lambat setiap kali dibuka/di-refresh.
      - **Eliminasi N+1 Query**: Menambahkan eager loading `with(['principals', 'principal'])` pada `ReportTemplateResource::getEloquentQuery()` untuk meload relasi daftar template dalam satu query efisien.
    - **Animasi Loading Universal Web Admin Filament (`admin-loader.blade.php`)**:
      - **Desain Glassmorphic Sejajar Portal Principal**:
        - Frosted glassmorphism backdrop blur (`backdrop-filter: blur(10px)`) dengan ambient radial glow.
        - Dual-orbit animated spinner rings (lingkaran gradien luar berputar searah jarum jam & lingkaran putus-putus dalam berlawanan arah).
        - Center badge logo/icon dengan animasi denyut (*pulse*).
        - Dynamic contextual title & subtitle disertai animasi bouncing dots (`. . .`).
        - Indeterminate shimmer progress bar di bagian bawah modal card.
        - Kompatibel penuh dengan Dark Mode dan Light Mode.
      - **Integrasi Otomatis Seluruh Navigasi & Aksi**:
        - Sidebar navigation, header actions, breadcrumbs, dan pagination tabel.
        - Form submit (Create, Edit, Save Changes).
        - Livewire v3 request hooks (`Livewire.hook('request')`, `livewire:navigating`, `livewire:navigated`) dengan threshold debouncing halus (220ms).
        - Safety timer 15 detik untuk mencegah antarmuka terkunci pada saat unduhan file/ekspor.
      - **Injeksi Global via Render Hook**: Terdaftar otomatis di seluruh halaman Filament Admin via `PanelsRenderHook::BODY_END` pada `AdminPanelProvider.php`.
    - **Deployment & Multi-Server Synchronization**:
      - Seluruh pembaruan kode dan asset telah di-commit, di-push, dan aktif di server Staging (`appsend.my.id`) serta 3/3 server Production Cluster (AMK, AKP, GBS).

58. **Implementasi Form Builder (Template Laporan) Khusus Portal Principal (SELESAI 6 September 2026)**:
    - **Isolasi Penuh Data Template per Principal**:
      - Menyediakan halaman Form Builder lengkap di Portal Principal (`/portal/report-templates`) yang terisolasi ketat hanya menampilkan, mengedit, dan mengelola template form milik prinsiple aktif (`$scopedPrincipalIds`).
      - Admin / PIC Prinsiple kini dapat membuat template form baru, mengedit struktur pertanyaan, mengubah jadwal kuota, dan mengatur penugasan tim lapangan secara mandiri.
    - **Visual Dynamic Form Builder (Google Form Style Editor)**:
      - Tampilan interaktif pembuatan pertanyaan dengan kartu dinamis, auto-slug variable naming, reordering (geser atas/bawah), duplikasi pertanyaan, dan penghapusan aman.
      - Mendukung 18 tipe input interaktif (Pilihan Master Produk SKU, Teks Singkat, Paragraf, Angka/Qty, Nilai Rupiah, Dropdown, Radio Button, Checkbox Multi-Pilihan, Foto Kamera Tunggal, Multi-Foto Before/After, Tanda Tangan Digital, Barcode/QR Scanner, Month/Year, Date, Time, Rating Star, Slider, dan GPS Location).
      - Dilengkapi Options Tag Manager untuk kemudahan menambah/menghapus pilihan dropdown/radio/checkbox secara langsung.
    - **Pengaturan Jadwal Kuota & Target Penugasan Tim**:
      - Pengaturan tipe frekuensi pengisian (Daily, Weekly, Monthly) dengan target kuota dan pemilihan hari aktif (Senin s/d Minggu).
      - Filter parameter produk SKU khusus prinsiple terkait.
      - Target penugasan multi-select berdasarkan jabatan (SPG, MD, TL, dll.) dan personil karyawan spesifik.
28. **Sidebar Minimizable (Collapsible) Portal Prinsiple & Optimasi Form Builder (SELESAI 06 September 2026)**:
    - **Sidebar Minimizable / Collapsible**:
      - Tombol toggle minimalkan/perluas sidebar tersedia di dua posisi strategis: header sidebar (`.btn-sidebar-collapse-toggle`) dan topbar kiri (`.btn-topbar-sidebar-toggle`).
      - Mode Collapsed (~78px icon-rail): ikon menu berada di posisi tengah, nama teks disembunyikan rapi, dan badge count diposisikan sebagai dot/mini-badge di sudut ikon.
      - **Floating Tooltips on Hover**: Setiap item menu pada mode compact menampilkan tooltip melayang hitam modern (`data-title`) dengan panah penunjuk halus di sebelah kanan sidebar.
      - **Anti-Flicker State Persistency**: Status sidebar (terbuka/tertutup) disimpan di `localStorage ('portal_sidebar_collapsed')` dan diinisialisasi seawal mungkin di `<head>` sehingga tidak ada layout flashing saat pergantian halaman.
      - **Auto Trigger Resize Event**: Saat sidebar diminimalkan/diperluas, sistem men-dispatch event resize jendela (`window.dispatchEvent(new Event('resize'))`) sehingga grafik ApexCharts pada dashboard dan analitik langsung menyesuaikan lebar layar secara dinamis tanpa terpotong.
      - **Responsivitas Mobile Utuh**: Pada resolusi tablet/ponsel (`<= 1024px`), sidebar tetap berfungsi sebagai responsive off-canvas drawer dengan backdrop blur tanpa terpengaruh mode collapsed desktop.
29. **Chart Garis Perubahan Employee Aktif Tiap Jam Berdasarkan Sinkronisasi Odoo (SELESAI 06 September 2026)**:
    - **Widget Line Chart Interaktif (`ActiveEmployeesHourlyChartWidget`)**:
      - Ditambahkan langsung pada Dashboard Web Admin Filament (`app/Filament/Widgets/ActiveEmployeesHourlyChartWidget.php`) pada urutan strategis (`sort = 3`, tepat di bawah 4 KPI Overview Stats).
      - Menampilkan tren pergerakan jumlah total karyawan aktif di sistem berdasarkan hasil sinkronisasi Odoo ERP secara berkala tiap jam.
    - **Multi-Dataset Terpadu (Dual-Axis Scale)**:
      - **Dataset 1 (Sumbu Kiri - Primary Area Line)**: *Total Employee Aktif* (misal: 11.130 karyawan) dengan kurva halus (`tension: 0.35`), border 3px biru (`#0F52BA`), dan bayangan area elegan yang mencerminkan volume master karyawan aktif riil.
      - **Dataset 2 (Sumbu Kanan - Dashed Emerald Green)**: *Karyawan Baru (+) Odoo* (`#10B981`) menunjukkan penambahan karyawan baru yang tersinkronisasi dari Odoo pada jam bersangkutan.
      - **Dataset 3 (Sumbu Kanan - Dashed Rose Red)**: *Resign / Non-Aktif (-) Odoo* (`#EF4444`) menunjukkan karyawan yang dinonaktifkan/resign dari Odoo pada jam bersangkutan.
    - **Header KPI Summary Bar & Status Odoo**:
      - *Total Employee Aktif*: Jumlah total karyawan aktif saat ini (11.130) dengan indikator live pulsating green dot 🟢.
      - *Resign / Non-Aktif*: Akumulasi seluruh karyawan non-aktif/resign terdaftar (25.027).
      - *Karyawan Baru (+)*: Akumulasi penambahan karyawan baru dari Odoo pada periode waktu terpilih.
      - *Mutasi Resign (-)*: Akumulasi karyawan resign dari Odoo pada periode waktu terpilih.
      - *Banner Status Odoo Sync*: Menampilkan timestamp sinkronisasi terakhir dan jadwal auto-sync cron 30 menit.
    - **Filter Rentang Waktu & Area Terpadu**:
      - Filter dropdown: *12 Jam Terakhir (Default)*, *24 Jam Terakhir*, *Hari Ini*, *7 Hari Terakhir*, dan *30 Hari Terakhir*.
      - Filter per Prinsiple dan per Area / Cabang terintegrasi penuh dengan hak akses non-superadmin.
      - Peningkatan retensi riwayat log Odoo (`OdooSyncLog::pruneOlderLogs(200)`) agar histori sinkronisasi beberapa hari terakhir tetap tersimpan untuk visualisasi tren.

---

## 📌 Rencana Lanjutan Berikutnya (Next Milestones)

1. **🛡️ Comprehensive Cyber Security Audit & Hardening (Web, API & Mobile App)**:
   - **A. Keamanan Backend, API & Web Admin (Laravel & Filament)**:
     - [ ] **Otorisasi & Manajemen Sesi (RBAC / ABAC)**:
       - Audit ketat hak akses multi-role (Superadmin, HR Admin, Leader, Viewer, Principal Portal).
       - Evaluasi masa aktif token API (*Sanctum token expiration & revocation*) saat user logout, ganti password, atau dinonaktifkan.
       - Implementasi proteksi *Session Fixation* dan cookie beratribut `HttpOnly`, `Secure`, dan `SameSite=Lax/Strict`.
     - [ ] **Mitigasi Kerentanan OWASP Top 10**:
       - **SQL Injection**: Validasi seluruh raw query dan pemanggilan engine database dinamis (termasuk SQLite reporting) dengan parameter binding ketat.
       - **Cross-Site Scripting (XSS)**: Sanitasi input HTML/script pada modul Live Chat, Form Builder kustom, dan catatan visit.
       - **CSRF & CORS**: Pengetatan origin whitelisting CORS hanya untuk domain/origin resmi dan perlindungan CSRF token di seluruh form web.
       - **IDOR (Insecure Direct Object References)**: Proteksi otorisasi pada endpoint unduhan payslip, file template, media foto presensi, dan bukti visit agar tidak bisa diakses user lain via manipulasi ID/parameter URL.
       - **File Upload Security**: Verifikasi ekstensi ganda, MIME-type, dan magic bytes pada upload foto/dokumen; isolasi file upload dan larang eksekusi script PHP di direktori publik.
     - [ ] **Rate Limiting & Anti-Brute Force**:
       - Penerapan throttling ketat pada endpoint sensitif (Login, Reset Password, Request OTP, Check-in/out, dan Ekspor Laporan).
       - Hardening endpoint deployment script (`deploy.php` & `deploy-production.php`) dengan perbandingan token konstan `hash_equals()`, rate limit IP, dan pembatasan akses.
     - [ ] **Enkripsi Data Sensitif**:
       - Enkripsi field data pribadi karyawan (NIK, Nomor Rekening, data gaji) di database menggunakan *Eloquent Encrypted Casts*.
       - Audit berkala rotasi `APP_KEY`, kredensial database, dan API key pihak ketiga.
   - **B. Keamanan Aplikasi Mobile (Flutter Android & iOS)**:
     - [ ] **Device Integrity & Anti-Fraud / Anti-Spoofing**:
       - Audit dan penguatan deteksi Root (Android) dan Jailbreak (iOS) via `safe_device` / `flutter_jailbreak_detection`.
       - Proteksi Fake GPS / Mock Location & deteksi Developer Mode injection yang lebih ketat saat pengiriman absensi dan visit.
       - Deteksi Emulator untuk mencegah automated bot check-in.
     - [ ] **Penyimpanan Lokal Aman (Secure Storage)**:
       - Migrasi seluruh token autentikasi, kredensial pengguna, dan data sensitif dari `SharedPreferences` biasa ke `FlutterSecureStorage` (Android Keystore / iOS Keychain).
       - Enkripsi database lokal (Hive/SQLite) yang digunakan untuk antrean offline (offline queue).
     - [ ] **Transport Layer Security & Anti-MITM**:
       - Penerapan **SSL / TLS Certificate Pinning** pada klien HTTP/Dio untuk mencegah intersepsi data via Man-in-the-Middle (Burp Suite, Charles Proxy, MITM tools).
       - Menonaktifkan Cleartext Traffic (`android:usesCleartextTraffic="false"`).
       - Sanitasi logging produksi: menonaktifkan seluruh `print()` dan network debug log pada release build (`kDebugMode` wrapper).
     - [ ] **Obfuscation & Binary Hardening**:
       - Penerapan Flutter Code Obfuscation saat build APK/AAB release (`--obfuscate --split-debug-info=...`).
       - Konfigurasi ProGuard / R8 code shrinking dan resource shrinking pada Android `build.gradle.kts`.
   - **C. Keamanan Server, Database & Infrastruktur**:
     - [ ] **Web Server Hardening**:
       - Konfigurasi HTTP Security Headers (HSTS, Content-Security-Policy, X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff, Referrer-Policy).
       - Blokir akses publik via Web Server ke berkas tersembunyi/sensitif (`.env`, `.git`, `.sqlite`, `.log`, `.sh`, `.yml`).
     - [ ] **Automated Vulnerability Scanning & Dependencies Audit**:
       - Scan dependensi rutin (`composer audit`, `npm audit`, `flutter pub outdated`) untuk menambal CVE pada pustaka pihak ketiga.
       - Uji penetrasi berkala (Penetration Testing / Dynamic Application Security Testing) menggunakan tool standar industri (OWASP ZAP / Burp Suite).

2. **Lanjutan Monitoring & Rekap Operasional:**
   - Evaluasi integrasi data jadwal visit schedule dan kehadiran di mobile app saat karyawan check-in via lokasi terjadwal visit.
   - Penambahan filter lanjutan pada laporan presensi dan ekspor data audit penyesuaian manual/import.

3. **Peningkatan Skalabilitas & Media Storage:**
   - Integrasi penyimpanan awan (*Cloud Storage S3 / Spaces / GCS*) untuk media foto presensi dan laporan visit.

4. **Penyempurnaan Modul Pelaporan Principal Lainnya:**
   - Sinkronisasi dashboard dan formulir pelaporan untuk principal lainnya (Fonterra, Wings, MamaSuka, Sido Muncul).

---

## ✅ Catatan Rilis & Penyempurnaan Sistem (6 September 2026)

1. **Dashboard Admin - Transformasi Line Chart Karyawan Aktif Berdasarkan Sinkronisasi Odoo**:
   - Menambahkan dan menyempurnakan widget Chart Garis `ActiveEmployeesHourlyChartWidget` di dashboard admin Filament (`/admin`).
   - Mengubah indikator perhitungan chart: tidak lagi sekadar menghitung check-in/check-out presensi harian, melainkan **melacak perubahan total karyawan aktif per jam yang diselaraskan dengan hasil sinkronisasi Odoo (~11.130 karyawan aktif)**.
   - Menampilkan visualisasi penambahan karyawan baru (`+Karyawan Baru`) dan pengurangan karyawan resign/keluar (`-Resign`).
   - Menerapkan **Dual Y-Axis (Chart.js)**:
     - Sumbu Y utama (kiri): Volume total karyawan aktif (skala ~11.000+).
     - Sumbu Y sekunder (kanan): Volume delta mutasi karyawan (`+Baru` dan `-Resign`) sehingga kedua grafik terbaca jelas tanpa saling tumpang tindih.
   - Desain banner atas chart dilengkapi 4 kartu metrik live: **Total Employee Aktif (11.130)** dengan indikator *live pulse*, **Total Resign (25.027)**, **Karyawan Baru Hari Ini**, dan **Karyawan Resign Hari Ini**, serta indikator status Odoo Sync.
   - Menyediakan auto-fallback cerdas yang mengagregasi data dari log sinkronisasi Odoo (`OdooSyncLog`), mutasi database karyawan (`employees`), dan snapshot per jam.
   - Memperpanjang batas retensi pembersihan log sinkronisasi Odoo pada model `OdooSyncLog` (`pruneOlderLogs`) dari 5 log menjadi 200 log agar tren riwayat analitik per jam terjaga.

2. **Dashboard Admin - Eliminasi Animasi Loading pada Auto-Refresh (Khusus Dashboard)**:
   - Memperbaiki isu di mana animasi loading universal terus-menerus muncul akibat auto-refresh background (`wire:poll` widget chart dan `databaseNotificationsPolling('10s')`).
   - Menerapkan isolasi multi-lapis:
     - **PHP & Blade**: Deteksi route dashboard dan menerapkan stylesheet disabler `display: none !important` khusus halaman `/admin`.
     - **JavaScript & Livewire**: Deteksi `isDashboardPage()` untuk membatalkan `showAdminLoader()` saat berada di dashboard.
     - **Filter Polling Background**: Menapis request Livewire yang bersifat auto-polling (`wire:poll`, `databaseNotifications`, dll.) agar tidak memicu loading overlay di background.
     - Mempertahankan animasi loading overlay pada halaman lain dan formulir interaktif di admin panel.

3. **Portal Principal - Form Builder & Sidebar Enhancement**:
   - Menambahkan halaman Form Builder (Template Laporan) di portal principal dengan filter otomatis yang hanya menampilkan template milik principal yang sedang login.
   - Menambahkan kemampuan minimize / collapse sidebar pada portal principal untuk ruang kerja yang lebih lega.
   - Menghapus card "Total Laporan Terkirim" pada halaman Form Builder portal principal sesuai kebutuhan.

4. **Standarisasi Automasi Server & Konfigurasi Cron Job aaPanel**:
   - Dokumentasi dan standarisasi konfigurasi cron job di aaPanel untuk seluruh lingkungan server (Staging `appsend.my.id`, Node 1 AMK `amk.dgsoft.web.id`, Node 2 AKP `akp.dgsoft.web.id`, Node 3 ATK/GBS `atk.dgsoft.web.id`):
     - **Laravel Master Scheduler (Wajib - Setiap 1 Menit)**:
       `cd /www/wwwroot/DOMAIN && /www/server/php/83/bin/php artisan schedule:run >> /dev/null 2>&1`
       Mengotomatisasi seluruh tugas terjadwal aplikasi (`notify:missed-checkin` setiap 08:30 WIB dan `odoo:sync --trigger=cron` setiap 30 menit).
     - **Odoo Sync Daily Dini Hari (Pukul 01:00 WIB)**:
       `cd /www/wwwroot/DOMAIN && /www/server/php/83/bin/php artisan odoo:sync --trigger=cron >> storage/logs/odoo_sync_cron.log 2>&1`
       Sinkronisasi cadangan master data karyawan dan jabatan dari sistem Odoo dengan berkas log terdedikasi.
     - **Pembersihan Cache & Log Mingguan**:
       Otomasi pembersihan cache view dan log aplikasi setiap hari Minggu pukul 02:00 WIB agar kapasitas disk server tetap prima.
     - **Supervisor Queue Worker Daemon**:
       Konfigurasi background worker via Supervisor Manager aaPanel (`/www/server/php/83/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600`) dengan 2–4 proses worker untuk pengiriman notifikasi FCM dan background export secara asynchronous.

5. **Deployment & Sinkronisasi Cluster Multi-Server (Staging & Production)**:
   - Berhasil mendistribusikan pembaruan ke server staging/development (`appsend.my.id`) via webhook streaming deployer (`deploy.php`).
   - Berhasil mendistribusikan pembaruan secara otomatis ke 3 server production (Cluster Multi-Node) via `deploy-production.php`:
     - **Server 1: PT Arina Multi Karya (AMK)**: `38.103.170.235` / `amk.esa-solutions.id` (Status: `HTTP 200 OK`)
     - **Server 2: PT Alva Karya Perkasa (AKP)**: `38.103.170.223` / `akp.esa-solutions.id` (Status: `HTTP 200 OK`)
     - **Server 3: PT Anugrah Talenta Berkarya (ATK)**: `38.103.170.224` / `atk.esa-solutions.id` (Status: `HTTP 200 OK`)
   - Seluruh pipeline mencakup penarikan kode Git terbaru, sinkronisasi lintas seluruh virtual host di `/www/wwwroot`, pembaruan storage symlink, kompilasi aset Livewire, pembersihan cache Laravel (`artisan optimize:clear`), dan restart layanan PHP-FPM secara terkoordinasi.

6. **Portal Principal - Integrasi Real-Time Submisi Mobile (Stock End & Out of Stock / OOS Dulux)**:
   - **Laporan Stock End Dulux (`RPT-DULUX-STOCK-END`)**:
     - Menghubungkan data submisi mobile dari PostgreSQL `report_submissions` ke seluruh tab portal principal: *Tab 1 (Rekap Volume Stock Toko)*, *Tab 2 (Ringkasan SCM & Stock)*, *Tab 3 (Raw Data Submissions)*, dan *Tab 4 (Data Laporan Masuk)*.
     - Memetakan field dinamis form: `brand_produk` (`DULUX` / `CATYLAC`), `kategori_produk`, `kemasan`, `stok_awal`, `stok_masuk`, `penjualan`, dan `stok_akhir`.
     - Menambahkan badge neon `⚡ LIVE` pada toko dan submisi real-time, serta pagination independen `live_page`.
   - **Laporan Out of Stock Dulux (`RPT-DULUX-OOS-SSO`)**:
     - Menghubungkan data checklist OOS promotor/SPG dari mobile ke seluruh tab:
       - **Tab 1 (Ringkasan Eksekutif & Alasan OOS)**: KPI real-time *Total Toko Terpantau*, *Total Kasus OOS Riil*, *Total Submission Laporan*, serta tabel analisis frekuensi dan persentase alasan OOS.
       - **Tab 2 (Rekapitulasi OOS Mingguan Toko)**: Pivot matrix per toko, produk, base warna, kemasan, dan minggu pelaporan (Week 37 dll) dengan badge `⚡ LIVE`.
       - **Tab 3 (Raw Data Submissions)**: 16 kolom data mentah checklist OOS dengan penanda `⚡ LIVE`.
       - **Tab 4 (Data Laporan Masuk)**: Tab dedikasi untuk monitoring submisi live lengkap dengan status radius GPS, rincian produk/alasan, serta tombol aksi **Quick Approve** dan **Quick Reject** (dengan modal input alasan penolakan).
     - Menyelaraskan seluruh slug form: `pilih_produk_dulux_yang_mengalami_out_of_stock_oos`, `base_kategori_warna_yang_kosong`, `kemasan_size_yang_kosong`, `lama_kondisi_barang_kosong_jumlah_hari`, `saran_kuantiti_order_ke_toko_qty_kemasan`, `penyebab_alasan_out_of_stock_oos`, dan `tipe_gerai_channel_toko`.
     - Telah tervalidasi aktif di production: `https://dulux.esa-solutions.id/portal/report/RPT-DULUX-OOS-SSO`.
---

## ✅ Catatan Rilis & Penyempurnaan Sistem (7 September 2026)

1. **Laporan Daily Maintenance Dulux (`RPT-DULUX-DAILY-MAINTENANCE`) - Pilihan Mesin Dinamis Berdasarkan Toko & Auto-Fill Nomor Seri (Aplikasi Mobile & Backend API)**:
   - **Pilihan Mesin Dinamis Berdasarkan Toko/Lokasi**:
     - Field dropdown `tipe_mesin_post` di aplikasi mobile kini otomatis menampilkan daftar mesin tinting yang hanya dimiliki oleh toko yang sedang dipilih/dikunjungi (misalnya pada **Toko Demo Kalilor**, otomatis hanya muncul `Mesin D200 (Automatic Tinting)` dan `Mesin Discovery (Automatic Tinting)`).
     - Selalu menyertakan opsi fallback resmi `"Toko Tidak Memiliki Mesin Tinting"`.
   - **Pengisian Otomatis Nomor Seri Mesin (`no_mesin_post`)**:
     - Ketika form Daily Maintenance dibuka atau toko dipilih, mesin pertama langsung terpilih dan nomor serinya otomatis terisi ke field `no_mesin_post` (misal `POST-2022-SUB-042` untuk D200).
     - Saat pengguna mengganti pilihan mesin di dropdown (misal memilih `Mesin Discovery`), nomor seri langsung berganti seketika ke nomor seri yang sesuai (`POST-2023-SUB-089`).
     - Jika pengguna memilih `"Toko Tidak Memiliki Mesin Tinting"`, nomor seri otomatis terisi `"-"`.
   - **Database & Model Extension**:
     - Menambahkan migration `2026_09_07_220000_add_machines_json_to_work_locations_table.php` untuk menambahkan kolom JSON `machines` pada tabel `work_locations`.
     - Menambahkan casting `'machines' => 'array'` pada model `WorkLocation`.
     - Mengoptimalkan sinkronisasi data historis mesin toko dari SQLite arsip Daily Maintenance ke PostgreSQL.
   - **Backend API Reporting**:
     - Endpoint `/api/v1/reporting/stores` diperkaya dengan atribut `machines` per unit toko.
     - Endpoint submit laporan `/api/v1/reporting/templates/{code}/submit` otomatis menyimpan dan menyinkronkan mesin toko yang diinput promotor/DC ke database `work_locations`.
   - **Mobile Dynamic Form Screen (`dynamic_form_screen.dart`)**:
     - Mengimplementasikan helper `_isDailyMaintenanceTemplate()`, `_getStoreMachinesMap()`, `_autoFillMachineSerial()`, dan `_initStoreMachineForDailyMaintenance()`.
     - Dropdown `tipe_mesin_post` dan text controller `no_mesin_post` tersinkronisasi secara reaktif.

2. **Rilis Aplikasi Mobile Android APK v1.0.125+125**:
   - Versi aplikasi mobile resmi dinaikkan ke **`1.0.125+125`** di `pubspec.yaml`.
   - Kompilasi APK release selesai dengan sukses: `app-release-1.0.125.apk` (112.7 MB).
   - Diunggah ke server unduhan resmi:
     - URL Langsung: `https://appsend.my.id/app-release.apk`
     - Ukuran: 112,770,029 bytes
     - Checksum MD5: `6c095d0cb524117d886b4fd3f2760649`
   - Tersinkronkan otomatis ke direktori download di seluruh server cluster production (`amk.esa-solutions.id`, `akp.esa-solutions.id`, `atk.esa-solutions.id`).

3. **Portal Principal Dulux - Integrasi Real-Time Live Submissions Daily Maintenance & Tab Verifikasi**:
   - **Identifikasi Masalah**: Laporan Daily Maintenance yang di-submit dari mobile berhasil tersimpan di PostgreSQL (`report_submissions`), tetapi di Portal Principal (`https://dulux.esa-solutions.id/portal/report/RPT-DULUX-DAILY-MAINTENANCE`) bernilai 0 karena controller portal sebelumnya hanya membaca arsip historis SQLite (`daily_maintenance.sqlite`) yang berakhir di pertengahan 2026.
   - **Penyelesaian Backend (`PrincipalPortalController.php`)**:
     - Menghubungkan query `getLiveSubmissionsQuery()` langsung ke dalam kalkulasi data dashboard Daily Maintenance.
     - Memetakan field dinamis form: `tipe_mesin_post`, `no_mesin_post`, status checklist (`status_nozzle_cleaning`, `status_sirkulasi_tinter`, `status_software_komputer`, `status_program_mix2win`), rekomendasi teknisi, dan foto bukti (`foto_brush_cleaning`, `foto_mesin_tinting`).
     - Menggabungkan data live secara dinamis ke seluruh komponen: KPI utama, sebaran mesin, kategori toko, RSM area, matriks toko per unit, dan data mentah.
     - Menghitung `$liveSubmissionsCount` dan mengatur `$activeTab` cerdas (otomatis membuka tab live jika terdapat data laporan baru dan data arsip SQLite 0 baris pada filter aktif).
     - Mendukung alasan penolakan (`verification_notes` & `admin_notes`) pada method verifikasi laporan.
     - Menaikkan cache key dashboard ke `dm_dash_v5_` (TTL 60 detik) untuk pembaruan instan.
   - **Pembaruan Blade View Portal (`daily_maintenance_dashboard.blade.php` & `report_detail.blade.php`)**:
     - **Tab 1 (Ringkasan & Kepatuhan)**: Menghitung submission live ke total toko terawat, mesin aktif, dan tingkat kepatuhan prosedur perawatan.
     - **Tab 2 (Matriks Toko & Mesin Tinting)**: Menampilkan baris toko/mesin dari data live dengan badge penanda `⚡ LIVE`.
     - **Tab 3 (Data Mentah Submission)**: Menampilkan rekaman data mentah submission live lengkap dengan status checklist dan badge `⚡ LIVE`.
     - **Tab 4 (Data Laporan Masuk)**: Tab verifikasi interaktif baru dilengkapi badge counter biru, tabel/kartu rincian submission, thumbnail foto bukti (dengan modal preview lightbox), validasi radius GPS, status badge verifikasi, serta tombol aksi cepat **Setujui** (Quick Approve) dan **Tolak Laporan** (Quick Reject dengan modal pop-up alasan penolakan).
     - Memastikan variabel `$submissions`, `$liveSubmissionsCount`, dan `$activeTab` diteruskan secara lengkap ke komponen blade.

4. **Multi-Server Production Deployment & Cluster Synchronisation**:
   - Seluruh perubahan source code (backend API, mobile screen, blade template portal, dan migrations) telah berhasil di-deploy ke seluruh cluster server production via webhook `deploy-production.php`:
     - **Server 1: PT Arina Multi Karya (AMK)**: `38.103.170.235` / `amk.esa-solutions.id` (HTTP 200 OK)
     - **Server 2: PT Alva Karya Perkasa (AKP)**: `38.103.170.223` / `akp.esa-solutions.id` (HTTP 200 OK)
     - **Server 3: PT Anugrah Talenta Berkarya (ATK)**: `38.103.170.224` / `atk.esa-solutions.id` (HTTP 200 OK)
   - Portal Principal Dulux Daily Maintenance telah diverifikasi langsung dan beroperasi normal secara real-time di:
     `https://dulux.esa-solutions.id/portal/report/RPT-DULUX-DAILY-MAINTENANCE`

---

## ✅ Catatan Rilis & Penyempurnaan Sistem (8 September 2026)

1. **Laporan Data Pelanggan Dulux (`RPT-DULUX-DATABASE-PELANGGAN`) - Integrasi Real-Time Submisi Mobile ke Portal Principal & Tab 4 Data Laporan Masuk**:
   - **Identifikasi Masalah**:
     - Laporan Data Pelanggan Dulux yang disubmit promotor/SPG dari aplikasi mobile berhasil tersimpan di database PostgreSQL (`report_submissions`) dan muncul di Admin Dashboard, tetapi di Portal Principal (`https://dulux.esa-solutions.id/portal/report/RPT-DULUX-DATABASE-PELANGGAN`) data bernilai 0 / kosong pada filter periode aktif (September 2026). Hal ini terjadi karena controller portal sebelumnya hanya menghitung agregasi dari arsip historis SQLite (`customer_db.sqlite`) yang datanya hanya ada hingga pertengahan 2026.
     - Portal Principal Laporan Data Pelanggan Dulux sebelumnya belum memiliki **Tab 4 (Data Laporan Masuk)** seperti halnya laporan Daily Maintenance dan OOS SSO untuk monitoring dan verifikasi real-time data yang baru masuk.
   - **Penyelesaian Backend (`PrincipalPortalController.php`)**:
     - Mengintegrasikan query real-time PostgreSQL `getLiveSubmissionsQuery()` langsung ke dalam method kalkulasi `calculateCustomerDbDashboardData()`.
     - Memetakan seluruh field dinamis formulir database pelanggan Dulux:
       - Profil Konsumen: `nama_konsumen_pelanggan`, `no_handphone_whatsapp`, `tipe_konsumen`.
       - Keputusan Pembelian & Brand Switch: `alasan_membeli_cat_dulux`, `merk_cat_yang_dicari`, `merk_cat_yang_dibeli`, `alasan_beralih_ke_merk_lain`.
       - Transaksi & Pengecatan: `tujuan_pengecatan`, `jenis_cat_yang_dibeli`, `total_nilai_pembelian_rp`.
       - Bukti Dokumentasi: `foto_struk_kwitansi_pembelian` dan `foto_kegiatan_konsumen`.
     - Menggabungkan data live secara dinamis ke seluruh ringkasan KPI: *Total Pelanggan Terdata*, *Total Nilai Belanja (Rp)*, *Rata-rata Keranjang Belanja*, *Toko Aktif*, *Distributor Terlibat*, *Pelanggan Switch Merk*, dan *Pelanggan Membeli Dulux*.
     - Menggabungkan data live ke seluruh grafik Consumer Insights (tipe pelanggan, alasan pembelian, perbandingan merk dicari vs dibeli, alasan switch merk, tujuan pengecatan, jenis cat dibeli), Matriks Ranking Wilayah / Regional, Toko dengan Pelanggan Terbanyak (dengan penanda `⚡ LIVE`), dan Raw Data Submissions.
     - Menghitung `$liveSubmissionsCount` dan mengatur active tab fallback cerdas ke Tab 4 (`live_data`) jika ada data live dan data arsip SQLite 0 baris pada filter aktif.
    - **Penyempurnaan Agregasi Regional & Peringkat Promotor**:
      - Memperbaiki kalkulasi kolom `Toko Aktif` dan `DC / Promotor` pada tabel **Kontribusi Database Pelanggan per Region (RSM Area)** agar menghitung toko unik dan DC unik dari submisi live secara akurat (tidak lagi bernilai 0 saat SQLite kosong).
      - Menyelaraskan tabel **Top 20 Promotor / DC Teraktif** dengan menggabungkan data promotor/DC pelapor dari submisi live sehingga peringkat promotor produktif langsung terisi dan terurut berdasarkan jumlah konsumen & nilai transaksi (misal: Citra Dewi Demo dengan 1 konsumen terdata).
      - Menyesuaikan format tampilan card **Total Nilai Transaksi** dan **Rata-Rata Belanja (Basket Size)** agar bersifat dinamis adaptif (menampilkan angka riil seperti `Rp 500.000` jika di bawah Rp 1 Juta, dan otomatis menggunakan akhiran `Juta` atau `Miliar` jika nilai mencapai nominal tersebut, sehingga tidak lagi tampak `Rp 0.00 Miliar` akibat pembagian skala miliaran).
      - Menaikkan cache key kalkulasi dashboard ke `cust_db_v4_` untuk invalidasi instan di seluruh server cluster.
   - **Pembaruan Blade View Portal (`customer_database_dashboard.blade.php` & `report_detail.blade.php`)**:
     - Menambahkan Tab 4 "Data Laporan Masuk" pada navigation toolbar dengan badge counter submisi live.
     - Menyediakan tabel monitoring live komprehensif: Kode Laporan & Waktu Submit, SPG / DC Pelapor, Toko & Kode SAP, Profil Pelanggan & No. HP/WA, Tipe Konsumen & Status Switch Merk, Merk Dicari vs Dibeli, Nilai Belanja (Rp), Thumbnail Foto Struk & Kegiatan Konsumen, Status Radius GPS Toko, Status Approval, dan Tombol Quick Action.
     - Menyediakan tombol aksi cepat **Setujui** (Quick Approve) dan **Tolak Laporan** (Quick Reject dengan modal pop-up alasan penolakan/rejection notes).
     - Menyediakan modal lightbox foto bukti resolusi penuh untuk foto struk dan foto kegiatan konsumen.
     - Menambahkan penanda neon `⚡ LIVE` pada toko di Tab 2 dan Tab 3 yang memiliki laporan masuk dari mobile.

2. **Perbaikan Tampilan Multi-Foto & Form Media di Detail Laporan Admin Dashboard (`ReportSubmissionResource`) & Detail Portal**:
   - **Identifikasi Masalah**:
     - Foto yang diambil dari aplikasi mobile memiliki multiple foto (misal 2 foto: Struk & Kegiatan Konsumen), namun di halaman detail Admin Dashboard hanya 1 foto yang tampil di panel Foto Bukti & Dokumentasi.
     - Field foto yang opsional/kosong muncul di tabel text values sebagai baris teks dengan tanda `-`.
   - **Penyelesaian (`view.blade.php` Filament & `report_submission_detail.blade.php` Portal)**:
     - Memperbaiki pemisahan field: memastikan semua field bertipe `photo`, `camera_photo`, `multi_photo`, dan `signature` dikeluarkan secara bersih dari daftar `$textValues`.
     - Memperbaiki parsing media pada Panel 2 "Foto Bukti & Dokumentasi": secara rekursif mengekstrak semua foto dari `value_json` (array URL), `media_url`, `file_path`, maupun `value_text` (comma-separated string).
     - Menambahkan penanda indeks foto dinamis seperti `(1/2)` dan `(2/2)` pada label header foto jika satu field memiliki multiple foto.
     - Mengaktifkan modal click-to-zoom (lightbox) untuk setiap kartu foto di Admin Dashboard Filament maupun Portal Detail.

3. **Multi-Server Production Deployment & Cluster Synchronisation**:
   - Seluruh perubahan source code (backend API, blade template portal, filament view, dan dokumentasi) telah berhasil di-deploy ke seluruh cluster server production via webhook `deploy-production.php`:
     - **Server 1: PT Arina Multi Karya (AMK)**: `38.103.170.235` / `amk.esa-solutions.id` (HTTP 200 OK)
     - **Server 2: PT Alva Karya Perkasa (AKP)**: `38.103.170.223` / `akp.esa-solutions.id` (HTTP 200 OK)
     - **Server 3: PT Anugrah Talenta Berkarya (ATK)**: `38.103.170.224` / `atk.esa-solutions.id` (HTTP 200 OK)
   - Portal Principal Dulux Data Pelanggan telah diverifikasi langsung dan beroperasi normal secara real-time di:
     `https://dulux.esa-solutions.id/portal/report/RPT-DULUX-DATABASE-PELANGGAN`

4. **Halaman Terpadu Server Monitoring 3 Server Production (`/admin/server-monitoring`)**:
   - **Latar Belakang**: Memantau kesehatan performa 3 server production (PT AMK, PT AKP, PT ATK) secara simultan dalam 1 tampilan tanpa harus membuka console aaPanel masing-masing server secara terpisah.
   - **Fitur Utama**:
     - Status koneksi real-time, hostname, dan IP publik masing-masing node.
     - Utilisasi CPU & RAM dengan progress bar dinamis dan indikator status visual (Normal/Warning/Danger).
     - Utilisasi Disk Storage riil (pemisahan mount root web `/` yang sebenarnya agar kapasitas disk akurat dan tidak bias oleh pseudo-filesystem).
     - Perhitungan proses aktif non-sleeping (Running `R` / Disk Sleep `D`) yang selaras dengan metrik aaPanel, bukan sekadar menghitung total ribuan thread sistem.
     - Metrik Load Average (1m, 5m, 15m) serta Uptime server.
     - Modal drill-down detail spesifikasi server dan daftar proses aktif teratas.

5. **Import Master Data Work Location Inhouse (`Store Inhouse Final.xlsb`)**:
   - Parsing dan impor sebanyak 3.513 data toko/lokasi kerja inhouse dari file Excel XLSB ke database PostgreSQL.
   - Penerapan kode toko acak unik berformat `STR-XXXXXX` secara otomatis untuk menjaga integritas data tanpa konflik ID.
   - Penyesuaian antarmuka tabel master Work Location di Filament agar menampilkan kolom **Code** di kolom pertama tabel.
   - Sinkronisasi data work location inhouse ke seluruh server cluster.

6. **Tab Working Groups Terpadu di Halaman Employee Schedule Roster**:
   - **Integrasi Antarmuka**: Menambahkan tab navigasi interaktif pada halaman Employee Schedule Roster (`/admin/employee-schedules`) yang membagi tampilan menjadi:
     - **Tab 1: Jadwal Roster (Kalender)**
     - **Tab 2: Working Groups (Master Pola Kerja)** dilengkapi badge counter jumlah grup aktif.
   - **Manfaat**: Pengguna dapat melihat daftar seluruh Working Group yang telah dibuat, melakukan pencarian live, dan mengelolanya secara langsung tanpa harus membuat menu baru di sidebar yang memenuhi navigasi.
   - **Fitur Tab Working Groups**:
     - Kolom tabel: No, Nama Working Group (dengan metadata pembuat & waktu), Prinsiple, Area / Cabang, Tgl Berlaku, Shift & Jam Kerja Default, Pola Hari Kerja (visualisasi hari kerja Sen–Jum vs libur Sab–Min), Total Anggota, dan Aksi.
     - Tombol Total Anggota interaktif: Membuka **Modal Popup Rincian Anggota** (NIK, Nama Karyawan, Posisi, Cabang, Shift Khusus, dan Toko Kunjungan).
     - Tombol Aksi Cepat: **Re-Generate Schedule Roster** (mengenerate ulang jadwal presensi seluruh anggota grup hingga akhir tahun), **Edit**, dan **Hapus**.
     - Pembersihan tombol ganda: Menghapus tombol duplikat `+ Buat Working Group Baru` sehingga tersisa satu tombol utama yang jelas, yaitu tombol cyan **`Input via Working Group`** di bagian atas header halaman Roster.

7. **Harmonisasi Form Edit Working Group Menjadi 2-Step Wizard**:
   - **Penyelarasan Desain**: Merombak total halaman Edit Working Group (`/admin/working-groups/{id}/edit`) dari tampilan bawaan Filament repeater standar menjadi **2-Step Wizard** yang identik dan harmonis dengan form pembuatan (`CreateWorkingGroup`).
   - **Step 1: Description & Configuration**:
     - Form terisi otomatis (*pre-filled*) dengan data tersimpan: Nama Grup, Tanggal Berlaku, Area/Cabang (multi-select), Prinsiple (multi-select), Shift Default, Toleransi Keterlambatan, Toko Default, dan konfigurasi toggle hari kerja Senin–Minggu (beserta opsi kustom per hari).
     - Tombol navigasi: *"Batal / Kembali ke Working Groups"* dan *"Lanjut ke Step 2: Implementing Working Group"*.
   - **Step 2: Implementing Working Group**:
     - Tabel seluruh karyawan anggota grup eksisting ditampilkan rapi lengkap dengan foto profil, NIK, jabatan, cabang, tombol hapus anggota, pencarian live, paginasi, dan tombol tambah karyawan baru (individual maupun massal per area/prinsiple).
     - Tombol submit: *"Simpan & Generate Jadwal (Submit)"* memperbarui database, aturan hari kerja, relasi anggota, dan men-generate ulang jadwal presensi hingga akhir tahun, kemudian kembali ke tab Working Groups.
     - Tombol header: *"Hapus Working Group"* (merah) dengan modal konfirmasi untuk penghapusan permanen.

8. **Deployment & Verifikasi Seluruh Cluster Server Production**:
   - Telah di-deploy dan diverifikasi melalui staging (`https://appsend.my.id`) serta didistribusikan ke seluruh 3 server cluster production dengan status 100% Aktif & Sehat (HTTP 200):
     - **Server 1: PT Arina Multi Karya (AMK)**: `38.103.170.235` / `amk.esa-solutions.id` (HTTP 200 OK)
     - **Server 2: PT Alva Karya Perkasa (AKP)**: `38.103.170.223` / `akp.esa-solutions.id` (HTTP 200 OK)
     - **Server 3: PT Anugrah Talenta Berkarya (ATK)**: `38.103.170.224` / `atk.esa-solutions.id` (HTTP 200 OK)

---

## ✅ Catatan Rilis & Penyempurnaan Sistem (9 September 2026)

1. **Penyelarasan Urutan Alur Pelaporan Sekuensial Dulux (Backend API & Aplikasi Mobile)**:
   - Menyelaraskan urutan wajib pelaporan Dulux (6 langkah berurutan dengan gating/kunci langkah):
     1. **Langkah 1: Daily Maintenance POST** (`RPT-DULUX-DAILY-MAINTENANCE`)
     2. **Langkah 2: Offtake** (`RPT-DULUX-OFFTAKE-01`)
     3. **Langkah 3: Out of Stock / OOS** (`RPT-DULUX-OOS-SSO`)
     4. **Langkah 4: Database Pelanggan & Konsumen** (`RPT-DULUX-DATABASE-PELANGGAN`)
     5. **Langkah 5: Stok End (Stock Opname Bulanan)** (`RPT-DULUX-STOCK-END`)
     6. **Langkah 6: CBP (Consumer Buying Price)** (`RPT-DULUX-CBP-PRICING`)
   - **Backend API (`ReportingApiController.php`)**:
     - Memperbarui matriks urutan `$duluxOrder` di method `index()` dan method pengecekan gate `checkPendingReportsStatic()`.
   - **Aplikasi Mobile Flutter (`att-mobile`)**:
     - Menambahkan konstanta `duluxOrderMap` dan helper `applyDuluxSequence` pada `ReportTemplateModel` serta `DynamicReportingProvider` untuk memastikan urutan kartu, penomoran langkah, dan penguncian langkah tetap konsisten dan presisi baik saat online maupun offline cache.
   - **Peningkatan Visual Loading Screen Mobile**:
     - Penambahan widget custom loading maskot ESA (`CustomLoadingIndicator`) dan integrasi nama versi dinamis pada `SplashScreen`.

2. **Kompilasi Rilis APK Mobile Android v1.0.128+128 (Lokal Build)**:
   - Bump versi mobile resmi ke **`1.0.128+128`** di `pubspec.yaml`.
   - Kompilasi APK release selesai sukses:
     - Berkas: `app-release-1.0.128.apk` dan `app-release.apk`
     - Ukuran: 113.380.481 bytes (~108.1 MB)
     - Direktori arsip: `APK/app-release-1.0.128.apk`, root workspace, dan `att-admin-v12/public/`.
   - Sesuai instruksi, APK hanya di-build secara lokal (tidak di-push ke GitHub dan belum di-deploy ke server).

3. **Multi-Mesin Per Toko (Dulux), Mandatory Daily Maintenance Gating & Icon Penanda Lokasi (APK v1.0.129+129)**:
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

4. **Update Patch v1.0.130 (Fix Mesin Dropdown & Label Tombol Daily Maintenance)**:
   - **Resolusi Dropdown Mesin Drop/Missing**:
     - Memperbaiki `ReportingApiController.php` pada logic `submit()` agar tidak menimpa (*overwrite/truncate*) array `machines` pada `work_locations`.
     - Menambahkan migrasi database `2026_09_09_143000_seed_toko_demo_arina_rajawali_machines.php` untuk memastikan Toko Demo Arina Rajawali (ID: 6016) memiliki kedua mesin (`Type Mesin 1` & `Type Mesin 2`).
     - Memperbarui `_getStoreMachinesMap()` di aplikasi mobile untuk membaca hierarki mesin toko dari payload API, template `storeMachines`, dan fallback bawaan store.
   - **Penyelarasan Total Progres & Label Tombol Form**:
     - Mengisolasi template Daily Maintenance dari binding produk (`_isDailyMaintenanceTemplate()` mengembalikan `false` untuk `_hasProductBinding()`).
     - Menempatkan pengecekan mesin di atas pengecekan produk pada tombol form pelaporan sehingga menampilkan **"Progres Laporan Mesin Tinting (X / Y Mesin)"**, tombol **"Kirim & Lanjut Mesin Berikutnya"**, dan **"Kirim & Selesai (Mesin Terakhir ✓)"**.

5. **Pembaruan Master Data Produk Dulux (69 Produk Resmi Excel) & Integrasi Pricing Matrix**:
   - **Pembersihan Deskripsi JSON**: Menghilangkan tampilan raw JSON pada deskripsi produk di Web Portal dan Mobile, menggantinya dengan deskripsi format teks terstruktur.
   - **Matriks Harga Base A-D & Kemasan (Tin, Galon, Pail)**:
     - Menambahkan kolom `pricing_matrix` (JSON) pada tabel `products` via migrasi `2026_09_09_164500_add_pricing_matrix_to_products_table.php`.
     - Menyimpan spesifikasi ukuran kemasan (Tin, Galon, Pail), rasio liter, dan harga acuan per varian Base (Ready Mix, Base A, Base B, Base C, Base D) untuk 69 produk Dulux.
     - Menyediakan modal popup interaktif **"Rincian Base & Kemasan"** pada halaman Master Produk di Portal Principal.

6. **Perombakan Form Laporan Offtake Dulux (`RPT-DULUX-OFFTAKE-01`)**:
   - **Mode Selector `Sale` vs `No Sale`**:
     - Di awal form disediakan segmented toggle `Sale` dan `No Sale`.
     - **Mode No Sale**: Form instan tanpa mewajibkan produk ataupun foto, langsung menampilkan tombol kirim.
   - **Sistem Keranjang Multi-Produk (Cart)**:
     - Pengguna dapat memilih produk dari katalog master, memilih varian base, dan menginput kuantiti Tin, Galon, dan Pail dengan preview harga dan kalkulasi subtotal instan (Volume Liter & Nilai Rp).
     - Tombol `+ Simpan Produk ke Daftar` menyimpan item ke keranjang sementara.
   - **Halaman Review, Traffic Pengunjung & Foto Bukti**:
     - Menampilkan rekap visual keranjang produk yang dibeli, banner Grand Total Liter & Grand Total Penjualan (Rp).
     - Input traffic customer: Customer Masuk, Beli Cat, Beli Dulux (dengan kalkulasi otomatis % market share).
     - Dialog pilihan pengambilan foto: 📸 **Ambil dari Kamera** (Watermark Geotag) atau 🖼️ **Pilih dari Galeri** (Watermark Geotag).
   - **Pembersihan Field Lama**: Field usang `status_transaksi` dan `catatan_penjualan` dihapus dari database template.

7. **Perbaikan Pelaporan Multi-Produk Offtake (1 Baris Per Produk di Dashboard Portal & Raw Data Transaksi)**:
   - **Resolusi 1 Baris Menjadi Multi Baris**:
     - Pada `ReportingApiController.php` (`submit()`), ketika pengguna menginput beberapa produk di keranjang offtake, backend secara otomatis membuat **1 baris `ReportSubmission` terpisah untuk setiap produk** (kode dokumen berseri unik `RPT-...-1`, `RPT-...-2`, dst.) dalam satu transaksi database atomik.
     - Setiap baris menyimpan data Sub Brand, Brand, Kemasan, Qty, Volume Liter, dan Nilai Penjualan (Rp) produk tersebut secara spesifik, dengan tetap melampirkan data traffic dan foto bukti yang sama.
     - Pada Dashboard Portal Principal dan ekspor excel Raw Data Transaksi (`sheet1`), setiap produk yang dibeli langsung terdata sebagai baris transaksi tersendiri.

8. **Perbaikan Pengambilan Foto Bukti (1 Card Offtake + 2 Nota) & Anti-Duplikasi Foto**:
   - **Auto-Sync Struktur Template Server**:
     - Menambahkan method `ReportTemplate::syncDuluxOfftakeTemplate()` yang secara otomatis memastikan field resmi `foto_card_offtake` (photo, 1 foto) dan `foto_nota_penjualan` (multi_photo, multi foto) tersedia dan aktif di seluruh server cluster.
   - **Deduplikasi di Mobile & Backend**:
     - Mobile `dynamic_form_screen.dart` mengirimkan file strictly dengan ID field tunggal, mencegah payload ganda.
     - Backend `saveUploadedPhotos` menerapkan filter hash MD5 (`$seenHashes`) sebelum menyimpan file ke storage, menjamin file yang sama tidak akan pernah tersimpan lebih dari sekali.
     - **Hasil**: 1 foto Card Offtake + 2 foto Nota tersimpan tepat 3 foto tanpa duplikasi.

9. **Multi-Server Production Deployment & Rilis APK Mobile v1.0.131+131**:
   - Seluruh pembaruan backend, database migrations, dan template telah di-deploy dan disinkronkan ke seluruh server cluster:
     - **Server 1: PT Arina Multi Karya (AMK)**: `38.103.170.235` / `amk.esa-solutions.id` (HTTP 200 OK)
     - **Server 2: PT Alva Karya Perkasa (AKP)**: `38.103.170.223` / `akp.esa-solutions.id` (HTTP 200 OK)
     - **Server 3: PT Anugrah Talenta Berkarya (ATK)**: `38.103.170.224` / `atk.esa-solutions.id` (HTTP 200 OK)
     - **Staging Server**: `appsend.my.id` (HTTP 200 OK)
   - APK Mobile resmi versi **`v1.0.131+131`** telah berhasil dibuild (`108.6 MB`), diunggah ke server `https://appsend.my.id/app-release.apk`, dan didistribusikan ke seluruh server node.

10. **Konsolidasi Laporan Offtake 1 Baris & Rincian Kelompok Produk (Selesai 10 September 2026)**:
    - **Penyatuan Submission Multi-Produk Menjadi 1 Baris Tunggal**:
      - Backend `ReportingApiController.php` (`submit()`) disempurnakan sehingga setiap kali petugas mengirimkan laporan offtake dengan banyak produk, sistem mencatatnya sebagai **1 baris dokumen tunggal** (`RPT-YYYYMMDD-XXXX`) dan tidak lagi memecahnya menjadi banyak baris berakhiran `-1`, `-2`.
      - Seluruh item produk tersimpan secara terstruktur di `offtake_items_json`.
      - Nilai global submission diagregasikan secara otomatis ke kolom-kolom `total_volume_unit`, `total_volume_liter`, `total_nilai_sales_rp`, `jml_customer_masuk`, `jml_customer_beli_cat`, `jml_customer_beli_dulux`, dan `estimasi_market_share_persen`.
    - **Banner Metrik Global Akumulatif Transaksi**:
      - Pada halaman detail laporan (`report_submission_detail.blade.php` di Portal Principal & `view.blade.php` di Web Admin Filament), ditambahkan banner 4 kartu ringkasan global:
        1. **Grand Total Penjualan (Rp)**: Akumulasi nilai nominal penjualan seluruh produk.
        2. **Grand Total Volume (Liter)**: Akumulasi volume cat terjual dalam liter.
        3. **Total Kuantiti Terjual (Unit)**: Akumulasi kaleng (Tin + Galon + Pail).
        4. **Traffic & Pangsa Pasar (%)**: Jumlah customer masuk, pembeli cat, pembeli Dulux, dan estimasi market share.
    - **Kartu Rincian Kelompok Produk (Grouped Product Breakdown)**:
      - Rincian produk ditampilkan per kelompok produk yang rapi dan terstruktur:
        - **Header**: Tag Brand (Dulux / Catylac), Nama Sub Brand/Produk, Tag RM / Base, dan Subtotal Nilai Penjualan (Rp).
        - **Harga Standart Acuan**: Menampilkan harga benchmark acuan kemasan Galon, Pail, dan Tin dari Matrix Produk.
        - **Kuantiti Terjual**: Jumlah Galon, Pail, dan Tin yang terjual beserta Total Unit untuk produk tersebut.
        - **Total Volume (Liter)**: Volume liter untuk masing-masing kemasan serta Total Liter untuk produk tersebut.
    - **Pembersihan Parameter Mentah & Galeri Bukti Global**:
      - Menyaring dan menyembunyikan 40+ field mentah redundan (`qty_tin`, `qty_galon`, `total_volume_unit`, `grand_total_...`, dll.) dari tabel parameter generik saat submission memiliki `offtake_items_json`.
      - Foto bukti transaksi (Card Offtake & Nota Penjualan) tetap ditampilkan sebagai galeri dokumentasi global transaksi.
    - **Penyelarasan Dashboard Tabel Portal (`offtake_dashboard.blade.php`)**:
      - Setiap baris multi-produk menampilkan badge `X Produk Terjual`, rincian nama produk, total kuantiti unit kemasan, dan kalkulasi total volume liter yang akurat.

11. **Penyempurnaan Kalkulasi Dinamis Grand Total & Konsolidasi Data Split Submission (10 September 2026)**:
    - **Kalkulasi Dinamis Grand Total Akumulatif**:
      - Memperbaiki kalkulasi metrik global di `report_submission_detail.blade.php` (Portal Principal) dan `view.blade.php` (Filament Admin) agar selalu mengakumulasikan seluruh produk dari `offtake_items_json` (bukan hanya saat nilai record awal <= 0), sehingga menampilkan Grand Total Penjualan (Rp), Grand Total Volume (Liter), dan Total Kuantiti Terjual (Unit) yang tepat dari seluruh item yang dibeli.
      - Menambahkan kalkulasi pintar persentase Pangsa Pasar (Market Share) dari perbandingan pembeli Dulux terhadap total pembeli cat.
    - **Penyelarasan Total Volume di Tabel Dashboard Portal (`offtake_dashboard.blade.php`)**:
      - Kolom Total Volume pada tabel Live Submissions kini selalu mengakumulasikan volume liter seluruh produk dari `offtake_items_json`.
    - **Filter Duplikat Baris di Live Submissions Query (`PrincipalPortalController.php`)**:
      - Menambahkan filter defensif pada query `getLiveSubmissionsQuery()` untuk mengecualikan kode dokumen hasil split sekunder (`RPT-%-[2-9]`).
    - **Database Migration Konsolidasi Data Legacy (`2026_09_10_084500_consolidate_split_offtake_submissions.php`)**:
      - Menggabungkan data split submission legacy (`RPT-...-1`), mengupdate nilai total unit, volume liter, dan sales rp menjadi akumulasi penuh, merename kode dokumen ke base code tanpa suffix `-1`, serta menghapus baris duplikat `-2`, `-3` beserta nilai formulirnya dari database.

12. **Pembaruan Komprehensif Laporan Stock End & Tinter Dulux (`RPT-DULUX-STOCK-END`) (10 September 2026)**:
    - **Penghapusan Field Status Ketersediaan Tinter**:
      - Database migration menghapus field usang `status_ketersediaan_tinter` dan `status_ketersediaan_tinter_di_toko` dari template database.
      - Menambahkan field resmi pelengkap: `stock_items_json` (tipe JSON/Text), `tipe_tinter_warna`, `qty_kaleng_tinta`, `status_akses_gudang`, dan `foto_stok`.
    - **Auto-Fill Data Produk dari Master SKU Dulux**:
      - Memilih produk Dulux/Catylac otomatis mengisi Nama Brand, Kategori, Ukuran Kemasan Galon & Pail, serta kalkulasi estimasi volume (Liter) secara realtime saat kuantiti diketik.
    - **Tampilan Kondisional Field Tinter**:
      - Logika deteksi cerdas kategori dan nama produk otomatis menampilkan field **Tipe Tinter / Warna Pasta Pewarna** dan **Kuantiti Kaleng Tinta** hanya jika produk bertipe Tinta/Tinter. Untuk produk cat reguler, kedua field tersebut disembunyikan.
    - **Sistem Keranjang Bebas (Buka Kunci Sekuensial 69 Produk)**:
      - Membuka kunci alur wajib 69 produk (`_isStockEndTemplate()`). Petugas dapat memilih hanya produk yang dicek di toko ke keranjang multi-item dengan syarat minimal 1 produk dilaporkan.
    - **Alur 2 Langkah (Step Form) + Halaman Review + Geotag Watermark Camera**:
      - Langkah 1: Input produk, kuantiti galon/pail/tinter, keranjang produk.
      - Langkah 2: Review itemized, radio pilihan status akses gudang, catatan kendala stok, dan foto bukti fisik stok gudang dengan kamera ber-watermark otomatis (Nama, NIK, Store, Waktu, Koordinat GPS).
    - **Penyelarasan Tampilan Detail Submission di Seluruh Platform**:
      - **Mobile App (`report_detail_screen.dart`)**: Ringkasan Grid KPI (Total SKU, Total Volume Liter, Total Galon/Pail, Total Kaleng Tinter) dan kartu produk itemized.
      - **Web Admin Filament (`view.blade.php`)**: Grid 4 kartu KPI di bagian atas dan tabel breakdown produk stok akhir.
      - **Web Portal Principal (`stock_dashboard.blade.php` & `report_submission_detail.blade.php`)**: Kolom produk multi-item ber-badge, kartu KPI ringkasan, dan kartu produk itemized dengan penyembunyian field mentah redundan.

13. **Master Produk Kompetitor Tersendiri & Cascading Dropdown Laporan CBP (`RPT-DULUX-CBP-PRICING`) (10 September 2026)**:
    - **Tabel Database & Model Master Produk Kompetitor**:
      - Membuat tabel `competitor_products` via migration `2026_09_10_180000_create_competitor_products_table.php` (`brand`, `subbrand`, `category`, `packaging_sizes`, `benchmark_price_tin`, `benchmark_price_galon`, `benchmark_price_pail`, `is_active`, `order_index`, `principal_id`).
      - Membuat Model Eloquent `CompetitorProduct` dengan scope filter aktif dan relasi principal.
      - Membuat Seeder `CompetitorProductSeeder` memuat katalog lengkap subbrand top kompetitor cat di Indonesia (Jotun, Nippon Paint, Avian Brands / No Drop / Lenkote, Mowilex, Propan, Kansai / Danapaint, Pacific Paint).
    - **Manajemen Master di Filament Admin & Web Portal Principal**:
      - **Filament Admin Resource (`CompetitorProductResource.php`)**: Menu baru *Produk Kompetitor* di grup *Master Data* lengkap dengan filter merk, kategori segmen, status aktif, serta modal tambah & edit.
      - **Web Portal Principal (`/portal/competitor-products`)**: Halaman mandiri Principal Dulux untuk memonitor ringkasan statistik produk pembanding pasar, menambah subbrand baru, dan memperbarui estimasi harga acuan benchmark pasar (Tin, Galon, Pail).
    - **Cascading Dropdowns (Brand -> Subbrand Kompetitor) di Form Mobile**:
      - **Dropdown Merk**: Pilihan brand kompetitor (`JOTUN`, `NIPPON PAINT`, `AVIAN / NO DROP / LENKOTE`, `MOWILEX`, `PROPAN`, `KANSAI / DANAPAINT`, `PACIFIC PAINT`, `MERK LAINNYA`).
      - **Dropdown Subbrand Dinamis**: Pilihan subbrand otomatis menyaring dan hanya menampilkan varian milik merk yang dipilih (misal: memilih Jotun hanya menampilkan *Majestic True Beauty*, *Jotashield*, dll.).
      - **Input Bebas Fallback**: Opsi `LAINNYA / INPUT MANUAL` memunculkan textfield bebas jika promotor menemukan produk baru yang belum tercatat di database.
      - **Auto-Fill Benchmark Price**: Harga acuan pasar Tin, Galon, dan Pail otomatis terisi saat subbrand dipilih.
    - **Laporan CBP Tetap Mengunci ke Seluruh Produk Dulux**:
      - Sequential Locking Flow tetap aktif mengunci ke seluruh 69 produk Dulux dengan progress tracker dan indikator nomor urut (`Produk X dari 69`).
      - Auto-fill data produk Dulux (kategori, ukuran kemasan, harga acuan CBP Dulux) otomatis terisi saat produk aktif dibuka.
    - **Multi-Server Production Deployment & Rilis APK Mobile**:
      - Migrasi dan seeder dieksekusi di Server 1 (AMK), Server 2 (AKP), dan Server 3 (ATK), seluruhnya terverifikasi HTTP 200 OK.
      - Portal Produk Kompetitor aktif di `https://dulux.esa-solutions.id/portal/competitor-products?p=18`.
      - Flutter release APK versi **`v1.0.142+142`** (`110.3 MB`, MD5: `f10c95addf9b9cb40f6529397458ecf5`) berhasil dikompilasi dan live di `https://dulux.esa-solutions.id/app-release.apk`.

14. **Perbaikan Pagination & Normalisasi Ukuran Icon SVG di Portal Produk Kompetitor (10 September 2026)**:
    - **Resolusi Icon Panah Raksasa (Giant Chevron SVG)**:
      - Memperbaiki pemanggilan pagination di `competitor_products.blade.php` agar menggunakan view template resmi portal `links('portal.pagination')` alih-alih default Laravel Tailwind view.
      - Menyelaraskan hal yang sama pada `schedules.blade.php` dan `dashboard.blade.php`.
    - **CSS Global Safeguard**:
      - Menambahkan aturan proteksi SVG di `portal/layout.blade.php` (`width: 1.25rem; height: 1.25rem`) untuk mengunci ukuran elemen SVG pagination default agar tidak meluber memenuhi layar.

15. **Penyelarasan Grafik Sync Odoo per 30 Menit di Dashboard Web Admin Filament (10 September 2026)**:
    - **Penyelarasan Interval Waktu**:
      - Memperbarui widget grafik sinkronisasi karyawan (`ActiveEmployeesHourlyChartWidget.php`) di dashboard web admin dari interval 1 jam menjadi per 30 menit (`*/30 * * * *`).
      - Interval sumbu X grafik kini menampilkan label setiap setengah jam (misal: `08:00`, `08:30`, `09:00`, dst.) secara presisi mencerminkan jadwal cron sync Odoo riil.

16. **Perombakan 6 Metrik KPI Presensi Seimbang & Optimasi Tata Letak Halaman Attendance Roster (10-11 September 2026)**:
    - **6 Metrik KPI Presensi Seimbang**:
      - Merombak kartu KPI pada halaman Attendance Roster (`/admin/attendances`) menjadi 6 metrik utama:
        1. **Total Employee Aktif**: Memuat seluruh karyawan aktif (`is_active = true`), bukan hanya karyawan yang memiliki jadwal.
        2. **Total Hadir (On-Time)**: Check-in tepat waktu sesuai toleransi shift.
        3. **Total Telat**: Check-in melebihi jam mulai shift ditambah toleransi.
        4. **Total Cuti**: Cuti yang telah disetujui pada periode evaluasi.
        5. **Total Ijin / Sakit**: Izin resmi dan surat sakit yang terverifikasi.
        6. **Total Alpha**: Karyawan tanpa presensi / belum absen.
      - **Formula Seimbang (Grand Total)**: Menjamin rumus matematis akurat $\text{Employee Aktif} = \text{Hadir (On-Time)} + \text{Telat} + \text{Cuti} + \text{Ijin/Sakit} + \text{Alpha}$ dengan banner status sinkronisasi presensi.
    - **Penyempurnaan Evaluasi Status Presensi (Telat vs Alpha)**:
      - Menambahkan proteksi *pre-shift midnight fallback* pada `AttendanceRoster.php`: jika sistem diakses pada dini hari (pukul 00:00 - 08:00) sebelum jam kerja dimulai dan belum ada check-in hari ini, sistem otomatis mengevaluasi hari kerja efektif terakhir di periode filter agar status keterlambatan tidak terhapus menjadi Alpha prematur.
      - Mengevaluasi status kehadiran per karyawan di seluruh rentang tanggal filter yang dipilih.
    - **Pemisahan Baris & Pencegahan Teks Menumpuk (Text Overlap Safeguard)**:
      - Memindahkan badge **`[X Terjadwal]`** dari samping angka ke baris tersendiri **tepat di bawah** angka total employee aktif.
      - Menyeragamkan 3 baris di seluruh 6 kartu, padding kartu disesuaikan (`14px 16px`), icon disesuaikan (`42x42px`), serta menerapkan `min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis;` agar angka ribuan (seperti `4,264`) tidak tumpang tindih dengan badge atau ikon.

17. **Perbaikan Statistik "Total Area 0" di Web Utama / Landing Page (11 September 2026)**:
    - **Resolusi Masalah Area 0**:
      - Pada `routes/web.php`, query statistik sebelumnya memanggil model `App\Models\Area::count()` yang menghasilkan nilai 0 karena tabel `areas` belum digunakan, sementara data wilayah operasional dan kantor cabang ESA dikelola di tabel `branches` (`BranchResource` berlabel *"Areas"*).
      - Memperbarui query menjadi `Branch::where('is_active', true)->count()` dengan fallback ke `Branch::count()` dan `Area::count()`.
      - Mengaktifkan statistik secara global (`global_landing_stats_active_v4`) dan menampilkannya secara konsisten di `landing.blade.php` baik untuk subdomain entitas (`amk.esa-solutions.id`) maupun domain utama.
      - Menambahkan pembersihan cache `global_landing_stats_active_v4` pada `Setting.php`, `SettingSyncService.php`, dan `ManageSettings.php`.
    - **Hasil Verifikasi Live di Production Cluster**:
      - Staging (`appsend.my.id`): Menampilkan **40 Area & Cabang Operasional** (`HTTP 200 OK`).
      - Production ([amk.esa-solutions.id](https://amk.esa-solutions.id)): Menampilkan **61 Area & Cabang Operasional** (`HTTP 200 OK`) bersama dengan 11.143 Karyawan Aktif, 34 Prinsiple, dan 3.641 Lokasi Kerja & Toko.
      - Seluruh 3/3 server node production (AMK, AKP, ATK) aktif dan tersinkronisasi 100%.



