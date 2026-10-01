# Rencana Implementasi: Smart Route Optimization Engine Berbasis Real Road Network

Dokumen ini mendokumentasikan spesifikasi teknis, arsitektur sistem, dan rencana bertahap (roadmap) implementasi fitur **Smart Route Optimization Engine** untuk menyusun urutan kunjungan toko (visit store) terdekat bagi SPG, Sales, dan Field Officer berdasarkan **jaringan jalan raya riil (real road network)**.

---

## 1. Latar Belakang & Masalah Lapangan
Saat ini, penentuan jadwal kunjungan SPG/Sales dilakukan secara manual atau mengikuti urutan acak saat pembuatan jadwal. 

### Dampak Buruk dari Metode Lama:
1. **Inefisiensi Rute (Zigzag Routing)**: Tenaga lapangan sering berpindah dari area utara ke selatan, lalu kembali lagi ke utara karena tidak ada panduan urutan rute yang logis.
2. **Waktu Kerja Terbuang**: Rata-rata 1.5 hingga 2.5 jam per hari habis di perjalanan akibat kemacetan dan rute memutar.
3. **Pembengkakan Klaim BBM**: Jarak tempuh harian menjadi 20%–35% lebih boros dari jarak yang seharusnya.
4. **Target Kunjungan Tidak Maksimal**: Waktu produktif di dalam toko (display produk, interaksi konsumen, audit stok) terpangkas signifikan.

### Mengapa Perhitungan Garis Lurus (Euclidean/Haversine) Menyesatkan?
Perhitungan jarak garis lurus (*as the crow flies*) tidak dapat diterapkan untuk operasional kendaraan darat:
- **Jalan Satu Arah (*One-Way*) & Separator Jalan**: Jarak udara dua toko bisa hanya 200 meter, namun karena pembatas jalan dan larangan putar balik, jarak tempuh motor di aspal nyata mencapai 2.5 km.
- **Hambatan Fisik**: Sungai tanpa jembatan terdekat, rel kereta api, jalan buntu, atau jalan protokol yang dilarang bagi kendaraan roda dua.
- **Kesimpulan**: Penentuan urutan rute **wajib** menggunakan jarak dan durasi tempuh jalan raya aspal riil (*real road network*).

---

## 2. Solusi & Spesifikasi Algoritma
Sistem mengadopsi penyelesaian ilmiah untuk masalah **Travelling Salesperson Problem (TSP)** dengan pendekatan:
1. **Distance & Duration Matrix**: Menghitung matriks jarak (meter) dan estimasi durasi tempuh (detik) antar seluruh pasangan titik lokasi:
   $$\text{Titik Awal (Start)} \rightarrow \{\text{Toko}_1, \text{Toko}_2, \dots, \text{Toko}_n\}$$
2. **Nearest Neighbor Heuristic + 2-Opt Optimization**:
   - Untuk $N \le 10$ toko per hari (standar harian SPG): Algoritma menghitung rute berantai terdekat secara rekursif dan menyempurnakannya dengan algoritma pertukaran *2-Opt* untuk memastikan tidak ada garis rute yang saling bersilangan.
   - Waktu komputasi sangat singkat: **< 150 milidetik**.

---

## 3. Arsitektur Komponen Sistem

```
+-------------------------------------------------------------+
|                 WEB ADMIN FILAMENT DASHBOARD                |
|  - Form Input Jadwal Visit (ItineraryForm)                  |
|  - Tombol: "⚡ Optimalkan Rute Kunjungan (Road Network)"     |
|  - Visualisasi Peta Polylines Rute Jalan Raya               |
|  - Switch: Strict Routing (Wajib Berurutan 1, 2, 3...)      |
+------------------------------+------------------------------+
                               |
                               v
+-------------------------------------------------------------+
|               BACKEND ENGINE (LARAVEL 12 API)               |
|  - Service: App\Services\RouteOptimizationService           |
|  - Endpoint: POST /api/v1/itineraries/optimize-route        |
|  - Local Distance Matrix Cache (Table / Redis)              |
+------------------------------+------------------------------+
                               |
              +----------------+----------------+
              |                                 |
              v                                 v
+-----------------------------+   +---------------------------+
|    PRIMARY ENGINE: OSRM     |   |   FALLBACK: GOOGLE MAPS   |
|  (Open Source Routing)      |   |   (Routes / Matrix API)   |
|  - Biaya: Rp 0 (Gratis)     |   |   - Live Traffic          |
|  - Performa Super Cepat     |   |   - Kuota Gratis $200/bln |
+-----------------------------+   +---------------------------+
                               |
                               v
+-------------------------------------------------------------+
|                  MOBILE APP SPG (FLUTTER)                   |
|  - Tampilan Urutan Kunjungan Harian (Badge 1, 2, 3...)      |
|  - Indikator Jarak Tempuh (km) & Estimasi Waktu (menit)     |
|  - Tombol: "📍 Re-optimalkan Rute dari Posisi Saya"         |
|  - 1-Klik Buka Turn-by-Turn Navigation (Google Maps / Waze) |
|  - Validasi Geofence Radius Check-in & Visit Report         |
+-------------------------------------------------------------+
```

---

## 4. Rincian Teknis Implementasi Backend (Laravel 12)

### A. Service Kelas: `App\Services\RouteOptimizationService`
Fungsi utama:
1. `getDistanceMatrix(array $coordinates, string $profile = 'driving'): array`
   - Mengambil data jarak dan durasi tempuh aspal antar titik koordinat.
   - Prioritas ke engine OSRM: `http://router.project-osrm.org/table/v1/driving/{coords}?annotations=distance,duration`.
   - Fallback otomatis ke Google Maps Distance Matrix API jika OSRM tidak merespons dalam 3 detik.
2. `solveTsp(array $startCoord, array $destinations): array`
   - Menghitung urutan terbaik (*best visiting sequence*) dengan total jarak dan waktu minimal.
   - Mengembalikan array ID toko yang telah diurutkan beserta metadata jarak (km) dan durasi (menit) per segmen perjalanan.
3. `getCachedMatrix($locId1, $locId2)`
   - Caching jarak antar lokasi kerja (`work_locations`) yang sudah pernah dihitung agar tidak melakukan panggilan API eksternal berulang untuk toko-toko yang sama.

### B. Endpoint API Baru
- **URL**: `POST /api/v1/itineraries/optimize-route`
- **Payload Request**:
  ```json
  {
    "start_point": {
      "type": "branch", // atau "current_location" / "custom"
      "latitude": -7.2575,
      "longitude": 112.7521
    },
    "location_ids": [102, 45, 88, 12]
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "data": {
      "optimized_sequence": [
        {"sequence": 1, "work_location_id": 45, "distance_km": 1.4, "duration_minutes": 6},
        {"sequence": 2, "work_location_id": 88, "distance_km": 2.1, "duration_minutes": 8},
        {"sequence": 3, "work_location_id": 102, "distance_km": 2.8, "duration_minutes": 11},
        {"sequence": 4, "work_location_id": 12, "distance_km": 3.5, "duration_minutes": 14}
      ],
      "total_distance_km": 9.8,
      "total_duration_minutes": 39,
      "savings_compared_to_unordered": {
        "distance_km_saved": 9.8,
        "percentage": 50.0
      }
    }
  }
  ```

---

## 5. Rincian Teknis Implementasi Web Admin (Filament v4)
Pada formulir jadwal kunjungan ([`ItineraryForm.php`](file:///g:/My%20File/Project%20APlikasi%20Absensi/New/att-admin-v12/app/Filament/Resources/Itineraries/Schemas/ItineraryForm.php)):
1. Menambahkan tombol aksi kustom pada header Section "Daftar Titik / Lokasi Kunjungan Visit":
   - Label: **`⚡ Optimalkan Urutan Rute (Rekomendasi Jalan Raya)`**
   - Modal Konfirmasi: Memilih titik mulai keberangkatan:
     - Opsi 1: Kantor Cabang Karyawan (`employee->branch`)
     - Opsi 2: Titik Toko Pertama yang Dipilih
     - Opsi 3: Koordinat Manual / Lokasi Tertentu
2. Saat tombol diklik:
   - Sistem memanggil `RouteOptimizationService`.
   - Repeater item `items` otomatis diurutkan ulang (*re-indexed*) berdasarkan urutan rute terdekat.
   - Menampilkan notifikasi sukses yang menginformasikan total jarak tempuh aspal dan estimasi waktu perjalanan.
3. Preview Peta: Menampilkan polyline rute jalan raya interaktif pada modal atau accordion.

---

## 6. Rincian Teknis Implementasi Mobile App (Flutter)
Pada aplikasi mobile ([`att-mobile`](file:///g:/My%20File/Project%20APlikasi%20Absensi/New/att-mobile)):
1. **Layar Jadwal Kunjungan Hari Ini**:
   - Menampilkan badge urutan kunjungan bernomor (1, 2, 3...) yang jelas.
   - Menampilkan chip informasi: `🚗 1.8 km (8 mnt dari lokasi sebelumnya)`.
2. **Tombol "Optimalkan dari Posisi Saya Sekarang"**:
   - Jika SPG memulai rute tidak dari kantor melainkan langsung dari titik lapangan tertentu, SPG bisa menekan tombol ini.
   - Aplikasi mengambil koordinat live GPS, mengirim ke endpoint optimasi, dan menyusun ulang sisa toko yang belum dikunjungi.
3. **Integrasi Navigasi Langsung (Turn-by-Turn)**:
   - Di setiap kartu toko pada jadwal, disediakan tombol: **`🗺️ Buka di Google Maps`** / **`Waze`**.
   - Menjalankan intent langsung: `google.navigation:q=latitude,longitude&mode=d`.

---

## 7. Rencana Roadmap Pelaksanaan (5 Tahapan)

| Tahap | Fokus Pekerjaan | Estimasi Waktu | Deliverable |
| :---: | :--- | :---: | :--- |
| **Tahap 1** | **Backend Core Engine & API** | 1 Minggu | • `RouteOptimizationService.php`<br>• Integrasi OSRM & Google Routes fallback<br>• Caching matrix tabel database<br>• Unit test kalkulasi rute & TSP |
| **Tahap 2** | **Integrasi Web Admin Filament** | 1 Minggu | • Tombol optimasi pada `ItineraryForm`<br>• Auto-reorder repeater sequence<br>• Preview rute jalan raya pada peta admin |
| **Tahap 3** | **Integrasi Aplikasi Mobile (Flutter)** | 1 Minggu | • Update UI jadwal visitasi dengan badge urutan & jarak<br>• Tombol live re-route GPS<br>• Integrasi tombol navigasi Google Maps |
| **Tahap 4** | **Pilot Testing Lapangan** | 1 Minggu | • Uji coba pada 1 unit tim/prinsiple (misal Wings Surya Jawa Timur)<br>• Validasi akurasi waktu tempuh vs realita lapangan<br>• Pengumpulan feedback pengguna |
| **Tahap 5** | **Full Rollout & Dashboard Analitik KPI** | 1 Minggu | • Penerapan ke seluruh area dan prinsiple<br>• Widget analitik: Total KM terhemat & efisiensi BBM<br>• Penyusunan SOP operasional tim lapangan |

---

## 8. Metrik Evaluasi & ROI Bisnis
1. **Penghematan Finansial**: Pengurangan klaim BBM operasional sebesar **20% hingga 35%**.
2. **Efisiensi Waktu**: Menghemat rata-rata **45 menit per hari** per tenaga lapangan.
3. **Peningkatan Produktivitas**: Tambahan **1–2 toko visit per hari** per SPG karena waktu tempuh perjalanan yang jauh lebih singkat.
4. **Kepatuhan Rute**: Tingkat keselarasan antara *Planned Route* vs *Actual GPS History* mencapai **> 90%**.
