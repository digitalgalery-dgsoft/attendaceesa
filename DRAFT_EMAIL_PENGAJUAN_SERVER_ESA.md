# DRAFT EMAIL PENGAJUAN PENGADAAN SERVER PRODUKSI
**Project:** Aplikasi Presensi & Pelaporan Lapangan (ESA Groups)  
**Target Populasi:** 23.511 Karyawan (3 Group Entitas)  
**Lampiran:** `Pengajuan_Server_Project_Attendance_Reporting_ESA_2026.pptx`

---

**Kepada:**  
Yth. Bapak/Ibu Manajemen & Direksi  
Yth. Kepala Divisi IT & Finance  
ESA Groups  

**Dari:**  
Tim Pengembang & Infrastruktur IT  

**Perihal:**  
Pengajuan Pengadaan Infrastruktur Cloud Server untuk Sistem Presensi & Pelaporan Lapangan (ESA Groups)

---

Dengan hormat,

Sehubungan dengan tahap finalisasi pengembangan dan persiapan *Go-Live* sistem **Aplikasi Presensi Mobile, Pelaporan Kunjungan (Visit & Sales Reports), serta Portal Manajemen ESA Groups**, bersama ini kami mengajukan permohonan pengadaan infrastruktur **Cloud Server Produksi (Dedicated Cloud VPS)** guna menunjang operasional harian seluruh entitas perusahaan.

### 1. Latar Belakang & Analisis Beban Kerja
Berdasarkan hasil pemetaan data terbaru, total populasi pengguna aktif sistem tercatat sebanyak **23.511 Karyawan** yang terdistribusi ke dalam 3 grup entitas:
1. **PT Arina Multi Karya:** 11.687 Karyawan (49,7% total populasi)
2. **Gabungan 3 PT (ATB + ATK + ABO):** 7.424 Karyawan (31,6% total populasi)
   - *PT Anugrah Talenta Berkarya: 2.915 Karyawan*
   - *PT Anugrah Terpercaya Kerja: 2.804 Karyawan*
   - *PT Abadi Berkat Odelia: 1.705 Karyawan*
3. **PT Alva Karya Perkasa:** 4.400 Karyawan (18,7% total populasi)

Pada operasional lapangan, sistem akan menghadapi lonjakan transaksi yang sangat tinggi pada **Jam Sibuk Presensi (*Peak Window*)**, yaitu pada pukul **06:30 – 08:30 WIB (Pagi)** dan **16:30 – 18:30 WIB (Sore)** dengan estimasi beban transaksi mencapai **500 – 800 request per detik secara serentak** (mencakup verifikasi selfie kamera, validasi geofencing GPS, live tracking rute, dan sinkronisasi laporan kerja).

---

### 2. Rekomendasi Arsitektur: Pemisahan 3 Unit Cloud Server
Untuk menjamin kehandalan sistem dan meminimalisir risiko kegagalan operasional, kami merekomendasikan **pemisahan menjadi 3 unit Cloud Server dedicated** (tidak digabung dalam 1 server tunggal) dengan pertimbangan:
* **Keamanan & Isolasi Data:** Database dan dokumen tiap grup perusahaan terpisah mandiri, menjamin kerahasiaan dan kepatuhan privasi data.
* **Bebas Antrean (*Zero Bottleneck*):** Tingginya transaksi di satu entitas besar (seperti PT AMK) tidak akan mengganggu kelancaran presensi di entitas lainnya.
* **Efisiensi & Fleksibilitas *Upgrade*:** Penambahan kapasitas RAM, CPU, atau Storage dapat dilakukan secara independen per server sesuai pertumbuhan karyawan masing-masing entitas tanpa *downtime*.

---

### 3. Rekapitulasi Spesifikasi & Estimasi Biaya (Spek Minimal Multiverse Ultra)
Pengajuan ini mengacu pada paket **Multiverse Ultra (Cloud VPS High Performance)** dengan alokasi spesifikasi minimal yang sudah disesuaikan secara proporsional terhadap jumlah karyawan:

| Unit Server | Alokasi Entitas | Jumlah Karyawan | Spesifikasi Hardware | Biaya / Bulan | Estimasi Biaya / Tahun |
| :--- | :--- | :---: | :--- | :---: | :---: |
| **Server 1** | **PT Arina Multi Karya** | 11.687 | • **CPU:** 8 Core<br>• **RAM:** 16 GB<br>• **Storage:** 250 GB NVMe SSD | **Rp 1.575.000** | **Rp 18.900.000** |
| **Server 2** | **Gabungan 3 PT**<br>*(ATB + ATK + ABO)* | 7.424 | • **CPU:** 8 Core<br>• **RAM:** 16 GB<br>• **Storage:** 200 GB NVMe SSD | **Rp 1.500.000** | **Rp 18.000.000** |
| **Server 3** | **PT Alva Karya Perkasa** | 4.400 | • **CPU:** 8 Core<br>• **RAM:** 8 GB<br>• **Storage:** 150 GB NVMe SSD | **Rp 1.025.000** | **Rp 12.300.000** |
| **TOTAL** | **3 Server Produksi** | **23.511** | **24 Core • 40 GB RAM • 600 GB NVMe** | **Rp 4.100.000 / bln** | **Rp 49.200.000 / thn** |

**Fasilitas & Bonus Tambahan Termasuk:**
* ✅ **Gratis 3x 100 GB S3 Object Storage** (Senilai Rp 300.000/bln atau **Hemat Rp 3.600.000/tahun**) untuk penyimpanan foto selfie presensi dan bukti kunjungan toko.
* ✅ **Free 3x Domain `.cloud`** (Senilai Rp 330.000) untuk akses resmi portal masing-masing entitas.
* ✅ Jaringan berkecepatan tinggi **1 Gbps Port Speed** dengan jaminan **99.9% Uptime SLA**.

---

### 4. Rencana Implementasi (*Timeline*)
1. **Persetujuan & Pengadaan Server:** 1 – 2 Hari Kerja
2. **Setup Environment & Security Hardening (OS, Nginx, Database, Redis, SSL):** 2 Hari Kerja
3. **Migrasi Data & Sinkronisasi Odoo:** 1 – 2 Hari Kerja
4. **User Acceptance Testing (UAT) & Stress Test:** 2 Hari Kerja
5. **Peluncuran Penuh (*Go-Live*):** Sesuai jadwal operasional manajemen

---

### 5. Lampiran Pendukung
Bersama email ini, kami lampirkan dokumen presentasi resmi:
📎 **`Pengajuan_Server_Project_Attendance_Reporting_ESA_2026.pptx`**  
*(Berisi analisis lengkap beban kerja, rincian teknis alokasi memori buffer database, serta bukti screenshot simulasi harga penyedia server).*

Besar harapan kami agar usulan pengadaan infrastruktur server ini dapat disetujui guna mendukung kelancaran, kecepatan, dan stabilitas operasional presensi seluruh karyawan ESA Groups.

Demikian pengajuan ini kami sampaikan. Atas perhatian dan persetujuan Bapak/Ibu Manajemen, kami ucapkan terima kasih.

---

Hormat kami,  
**Tim Pengembang & Infrastruktur IT**  
Project Presensi & Pelaporan ESA Groups  
