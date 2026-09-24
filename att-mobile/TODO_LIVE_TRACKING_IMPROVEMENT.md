# Rencana Perbaikan Live Tracking Mobile (Flutter) - Versi Berikutnya

Dokumen ini mencatat daftar perbaikan fitur Live Tracking pada aplikasi mobile (`att-mobile`) yang akan diimplementasikan setelah proses review versi berjalan di Google Play Console selesai dan rilis perdana terbit.

---

## 📌 1. Pemicu Tracking pada Presensi Kunjungan (*Visit-In*)
**Lokasi File**:
- `lib/screens/attendance_location_screen.dart`
- `lib/providers/attendance_provider.dart`

**Masalah Saat Ini**:
- Saat ini `LocationService.startService()` hanya dipanggil jika `type == 'checkin'` atau `type == 'check_in'`.
- Bagi tim sales/lapangan yang menggunakan alur presensi kunjungan (*visit_in*), tracking lokasi tidak pernah aktif.

**Rencana Perbaikan**:
- Tambahkan pemanggilan `LocationService.startService()` ketika `widget.type == 'visit_in'` atau `type == 'visit_in'`.
- Pastikan saat `visit_out`, jika seluruh rangkaian kunjungan hari ini telah selesai atau checkout dilakukan, panggil `LocationService.stopService()`.

---

## 📌 2. Memastikan Status Foreground Service Terkunci di Android
**Lokasi File**:
- `lib/services/location_service.dart` (di dalam fungsi `onStart`)

**Masalah Saat Ini**:
- Di `onStart()`, pemanggilan `service.setAsForegroundService()` hanya ada di dalam *listener event* `setAsForeground`, dan tidak pernah dipanggil secara eksplisit saat inisialisasi awal `onStart()`.
- Di Android 12, 13, dan 14, hal ini dapat menyebabkan OS menurunkan prioritas service menjadi background biasa setelah beberapa menit, sehingga rentan dibunuh oleh penghemat daya sistem.

**Rencana Perbaikan**:
- Di awal `onStart()`, jika `service is AndroidServiceInstance`, panggil langsung:
  ```dart
  if (service is AndroidServiceInstance) {
    service.setAsForegroundService();
  }
  ```

---

## 📌 3. Optimalisasi Pengambilan GPS Saat Layar HP Terkunci (*Doze Mode*)
**Lokasi File**:
- `lib/services/location_service.dart` (di dalam `_getCurrentPosition` & loop cron)

**Masalah Saat Ini**:
- `Geolocator.getCurrentPosition(desiredAccuracy: high, timeLimit: Duration(seconds: 20))` sering mengalami `TimeoutException` saat layar ponsel mati dan CPU masuk mode sleep/Doze di saku celana.
- Saat timeout, nilai koordinat menjadi `null` dan titik tidak dikirim.

**Rencana Perbaikan**:
- Tambahkan penanganan fallback: jika `getCurrentPosition` timeout, coba gunakan `Geolocator.getLastKnownPosition()` dengan validasi usia koordinat (misal usia koordinat < 2 menit).
- Turunkan akurasi ke `LocationAccuracy.balanced` atau `LocationAccuracy.medium` saat background agar penangkapan sinyal lebih cepat dan hemat daya.
- Tingkatkan toleransi akurasi dari `200m` menjadi `300m` saat di area perkotaan/gedung padat agar titik tidak terbuang sia-sia.

---

## 📌 4. Edukasi Izin Notifikasi & Optimasi Baterai Vendor HP
**Lokasi File**:
- `lib/screens/home_screen.dart` / Dialog Pengaturan Presensi

**Masalah Saat Ini**:
- Di vendor HP tertentu (Xiaomi HyperOS/MIUI, Oppo ColorOS, Vivo FuntouchOS, Samsung OneUI), OS membunuh background service jika user belum mengaktifkan "Autostart / Mulai Otomatis" dan "No Restrictions / Tidak Ada Pembatasan Baterai".
- Di Android 13+, jika notifikasi diblokir user, foreground service tidak bisa berjalan.

**Rencana Perbaikan**:
- Tampilkan panduan ramah (banner/dialog edukasi satu kali) bagi karyawan yang menggunakan perangkat Xiaomi/Oppo/Vivo untuk mengaktifkan "Autostart" dan mengecualikan aplikasi dari Battery Saver agar pelacakan tidak terputus di jalan.
