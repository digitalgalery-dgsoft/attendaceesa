# Proyek Aplikasi Absensi (att-mobile & att-admin)

## Catatan Penting Pengembangan (Update Terakhir: 31 Juli 2026)

Ini adalah catatan untuk memastikan AI Assistant atau tim developer selalu memiliki konteks yang sama sebelum melanjutkan pengembangan aplikasi ini.

### 1. Backend & Database (PostgreSQL)
- **Tipe Data JSON**: Selalu gunakan jsonb (bukan json) ketika membuat atau mengubah kolom database di migration Laravel. Aplikasi ini ketat menggunakan PostgreSQL, dan penggunaan json akan menyebabkan error.

### 2. Aplikasi Mobile (Flutter - att-mobile)
- **Versi Terakhir**: v1.0.10
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
