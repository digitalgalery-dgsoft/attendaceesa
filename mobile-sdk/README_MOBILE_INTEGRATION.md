# PANDUAN INTEGRASI MOBILE CLIENT (MULTI-SERVER ROUTING)
## Project Aplikasi Presensi & Pelaporan ESA Groups (23.511 Karyawan)

---

### 1. Konsep Dynamic Multi-Server Routing

Aplikasi Mobile (Android/iOS) menggunakan pendekatan **Single Entrypoint Gateway dengan Dynamic URL Switching**:
1. Domain default aplikasi di-hardcode ke: `https://api.esagroups.id`.
2. Saat karyawan login dengan NIK & Password, gateway server mengembalikan metadata:
   - `token`: Bearer authentication token.
   - `routing.api_base_url`: URL server khusus untuk entitas karyawan tersebut (contoh: `https://atb.esagroups.id/api` atau `https://amk.esagroups.id/api`).
3. Seluruh request presensi selfie, kalkulasi geofence GPS, laporan kunjungan (*visit reports*), dan tracking lokasi selanjutnya dikirim **langsung ke server entitas terkait**.

---

### 2. File SDK yang Disediakan

File siap pakai: [`mobile-sdk/dynamic_server_client.dart`](file:///g:/My%20File/Project%20APlikasi%20Absensi/New/mobile-sdk/dynamic_server_client.dart)

#### Dependensi Flutter yang Dibutuhkan (`pubspec.yaml`):
```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.4.0
  flutter_secure_storage: ^9.0.0
```

---

### 3. Cara Penggunaan di Flutter / Dart

#### A. Inisialisasi pada `main.dart`:
```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Inisialisasi Dynamic Server Client
  await DynamicServerClient().init();
  
  runApp(const MyApp());
}
```

#### B. Proses Login di Layar `LoginScreen`:
```dart
Future<void> handleLogin(String nik, String password) async {
  try {
    final result = await DynamicServerClient().login(nik, password);
    
    // Login berhasil! Data employee dan base URL server otomatis tersimpan.
    print("Selamat datang: ${result['employee']['name']}");
    print("Terkoneksi ke server: ${result['routing']['server_name']}");
    
    // Navigasi ke Dashboard Utama
    Navigator.pushReplacementNamed(context, '/dashboard');
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text("Login gagal: $e")),
    );
  }
}
```

#### C. Melakukan Presensi Selfie & GPS:
```dart
Future<void> handleClockIn(Position position, String imagePath) async {
  try {
    final response = await DynamicServerClient().submitAttendance(
      latitude: position.latitude,
      longitude: position.longitude,
      type: 'in',
      photoFilePath: imagePath,
      notes: 'Presensi Masuk Toko',
    );
    
    print("Presensi sukses: ${response.data}");
  } catch (e) {
    print("Gagal presensi: $e");
  }
}
```

#### D. Approval Lintas Entitas (Khusus Supervisor / Head):
```dart
// 1. Ambil daftar bawahan lintas entitas
List<dynamic> teamMembers = await DynamicServerClient().getCrossEntitySubordinates();

// 2. Eksekusi Persetujuan (Cuti / Lembur / Kunjungan)
bool isSuccess = await DynamicServerClient().submitCrossEntityApproval(
  type: 'permit',
  id: 105,
  action: 'approve',
  note: 'Disetujui oleh SPV Lintas Entitas',
);
```

---

### 4. Ringkasan Endpoint API Backend

| Method | Endpoint | Deskripsi | Hak Akses |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/gateway/discover` | Menemukan server entitas berdasarkan NIK | Public |
| `POST` | `/api/v1/gateway/login` | Login terpadu & penentuan API Base URL | Public |
| `GET` | `/api/v1/cross-entity/subordinates` | Mengambil bawahan lintas entitas | Auth (SPV) |
| `POST` | `/api/v1/cross-entity/approve` | Eksekusi persetujuan cuti/lembur lintas entitas | Auth (SPV) |
| `POST` | `/api/attendance` | Mengirim presensi selfie + GPS | Auth (Staf) |
| `POST` | `/api/attendance/visit-report` | Mengirim laporan kunjungan toko | Auth (Staf) |
