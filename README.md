# Proyek Aplikasi Absensi (att-mobile & att-admin)

## Catatan Penting Pengembangan (Update Terakhir: 4 Agustus 2026)

Ini adalah catatan untuk memastikan AI Assistant atau tim developer selalu memiliki konteks yang sama sebelum melanjutkan pengembangan aplikasi ini.

### 1. Backend & Database (PostgreSQL)
- **Tipe Data JSON**: Selalu gunakan jsonb (bukan json) ketika membuat atau mengubah kolom database di migration Laravel. Aplikasi ini ketat menggunakan PostgreSQL, dan penggunaan json akan menyebabkan error.

### 2. Aplikasi Mobile (Flutter - att-mobile)
- **Versi Terakhir**: v1.0.26
- **Library Penting**: `flutter_background_service` (v5.1.0) digunakan untuk Live Tracking (melacak lokasi secara berkala).

#### Aturan Ketat Android 14 (Background Service & Permissions):
- Android 14 memblokir aplikasi untuk menyalakan *Foreground Service* secara tersembunyi dari latar belakang (Background) atau pada saat inisialisasi awal UI (*startup*).
- **JANGAN PERNAH** menyalakan LocationService.startService() secara otomatis saat aplikasi baru di-buka (misalnya di dalam initState, atau fungsi checkAttendanceStatus yang dipanggil saat *fetch initial data*). Jika ini dilakukan, aplikasi akan mengalami *Force Close* secara native dari OS dengan pesan error ForegroundServiceStartNotAllowedException.
- Pelacakan (*Live Tracking*) hanya boleh dijalankan **setelah ada interaksi fisik dari user** (misalnya menekan tombol Check-In) dan dipastikan aplikasi sudah 100% berada dalam kondisi *Foreground* (UI telah terbuka sepenuhnya).
- Konfigurasi AndroidConfiguration di Dart **harus** memiliki `foregroundServiceTypes: [AndroidForegroundType.location]`.
- Penggunaan `autoStart: true` dan `autoStartOnBoot: true` dimatikan karena akan memicu crash yang sama saat HP baru menyala / restart.
- Saat menyalakan service, kita juga wajib memeriksa Permission.location dan Permission.notification terlebih dahulu. 
- Di AndroidManifest.xml, tipe *foreground service location* (FOREGROUND_SERVICE_LOCATION) sudah dimasukkan, bersamaan dengan izin POST_NOTIFICATIONS dan ACCESS_BACKGROUND_LOCATION.

### 3. Aturan Penting: AttendanceLog (31 Juli 2026)
- **AttendanceLog::create() HARUS dipanggil SETELAH semua validasi bisnis lolos.** Jangan pernah membuat log sebelum validasi (geofence check, status check, dll). Ini untuk mencegah "log zombie" — baris log yang masuk ke database tapi operasinya gagal.
- Semua pemanggilan `LocationService.startService()` dan `stopService()` di mobile HARUS di-wrap dalam `try-catch` karena bisa throw exception jika app belum sepenuhnya di foreground.
- **Geolocator API**: Gunakan `locationSettings: LocationSettings(accuracy: LocationAccuracy.high)` — **JANGAN** gunakan parameter deprecated `desiredAccuracy`.

### 4. Sanctum & Auth Guard
- `$request->user()` pada API routes mengembalikan **Employee** langsung (bukan User). Guard sanctum menggunakan `employees` provider. Jangan pernah akses `$request->user()->employee->id` — langsung pakai `$request->user()->id`.

---

## Riwayat Update (Changelog)

### v1.0.26 (4 Agustus 2026) - Fase 1: Overhaul Dashboard Mobile
**Fitur & Perubahan Baru:**
1. **API Backend Dashboard (Laravel):**
   - Menambahkan tabel dan model `work_targets` (pencatatan target HK per bulan).
   - Menambahkan endpoint `GET /api/dashboard/stats` (menampilkan Target HK Pribadi, Running Rate, Kehadiran, Sakit, Cuti).
   - Menambahkan endpoint `GET /api/dashboard/team-stats` khusus Team Leader (rekapitulasi absensi anggota tim: Hadir, Sakit, Kosong/Vacant).
2. **UI & State Mobile (Flutter):**
   - Mengimplementasikan `DashboardProvider` untuk manajemen data statistik dari API.
   - Merombak `dashboard_screen.dart` agar menampilkan `DashboardStatsWidget` (untuk peran SPG, MD, TL) dan `TeamStatsWidget` (khusus TL) secara dinamis di tengah halaman, tanpa merusak Menu atas dan Navbar bawah.
3. **Seeder Demo & Perbaikan:**
   - Menambahkan `DashboardDemoSeeder.php` berisi akun `md@esagroup.com`, `spg@esagroup.com`, `tl@esagroup.com` (Password: `123456`). Memperbaiki error ketidakcocokan skema tabel di mana kolom yang benar adalah `employee_no` dan `full_name`.
   - **PENTING (Catatan Build APK):** Jika melakukan *bumping version* di `pubspec.yaml`, selalu jalankan `flutter pub get` dan `flutter clean` terlebih dahulu sebelum `flutter build apk --release` agar versi internal di *binary* APK benar-benar terupdate dan tidak menimbulkan *loop* notifikasi update di HP user.

### v1.0.10 (31 Juli 2026)
**Perbaikan Kritis (Bug Fixes):**
1. **Force Close setelah Check-In**: Membungkus pemanggilan `LocationService.startService()` dengan `try-catch` dan `await` di aplikasi Flutter untuk menangani `ForegroundServiceStartNotAllowedException` di Android 14.
2. **Check-Out Error ("Failed to record attendance")**: Menata ulang alur di `AttendanceController@store`. `AttendanceLog` kini hanya dibuat SETELAH semua validasi bisnis (termasuk pengecekan geofence) berhasil dilewati. Ini mencegah munculnya "log zombie" di database dan memungkinkan pesan error yang spesifik (seperti "Di luar radius") dikirim ke pengguna.
3. **Live Tracking Tidak Berjalan**: Memperbaiki penggunaan API `Geolocator` yang sempat salah penamaan parameter pada versi 11.1.0 (`desiredAccuracy` digunakan kembali menggantikan `locationSettings` untuk method `getCurrentPosition`).
4. **Crash pada Tracking API**: Memperbaiki cara pemanggilan model User di `TrackingController`. Mengganti `$request->user()->employee->id` menjadi `$request->user()->id` karena guard Sanctum sudah menggunakan model Employee.
5. **Visit Out Disabled**: Mengatasi masalah validasi Visit Out yang bergantung pada urutan log dan ketersediaan itinerary, memastikan koordinat Visit Out dibandingkan dengan tepat berdasarkan lokasi Visit In sebelumnya.
