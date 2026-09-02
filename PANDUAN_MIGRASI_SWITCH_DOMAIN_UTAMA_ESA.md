# 🌐 PANDUAN SWITCH / MIGRASI KE DOMAIN UTAMA (esa-solution.id)
## Zero-Downtime Transition Guide untuk 3 Server Production ESA Groups

Dokumen ini berisi panduan teknis langkah demi langkah untuk mengalihkan (*switch*) sistem absensi dan pelaporan ESA Groups dari domain sementara (`dgsoft.web.id`) ke **Domain Utama Produksi (`esa-solution.id`)** tanpa *downtime* (tanpa mematikan layanan yang sedang berjalan).

---

## 📋 1. RINGKASAN PEMETAAN IP SERVER PRODUCTION

Berikut adalah daftar IP resmi dari ketiga server production yang saat ini telah aktif dan berjalan:

| Entitas Server | Fungsi & Peran | IP Server | OS & Webstack | Database PostgreSQL |
| :--- | :--- | :---: | :---: | :---: |
| **SERVER 1 (PT AMK)** | Server Utama AMK & Gateway Mobile App | **`38.103.170.235`** | Ubuntu 24.04 / Nginx / PHP 8.3 | `db_esa_amk` |
| **SERVER 2 (PT AKP)** | Server Operasional PT AKP | **`38.103.170.223`** | AlmaLinux 8 / Nginx / PHP 8.3 | `db_esa_akp` |
| **SERVER 3 (PT ATK)** | Server Operasional ATK / ATB / ABO | **`38.103.170.219`** | Rocky Linux 8 / Nginx / PHP 8.3 | `db_esa_atk` |

---

## 🌐 2. LANGKAH 1: SETTING DNS RECORD DI DASHBOARD `esa-solution.id`

Seluruh routing domain utama dilakukan pada **1 dashboard DNS** tempat domain `esa-solution.id` terdaftar (misalnya Cloudflare, Niagahoster, Rumahweb, atau penyedia domain Anda).

Tambahkan atau sesuaikan **DNS A Record** berikut:

### A. Routing ke Server 1 (PT AMK & Gateway Mobile):
| Tipe | Nama / Subdomain | Target / Nilai IP | TTL | Keterangan |
| :---: | :--- | :---: | :---: | :--- |
| **`A`** | **`amk`** | `38.103.170.235` | Auto / 300 | Web Admin & Portal PT AMK |
| **`A`** | **`*.amk`** | `38.103.170.235` | Auto / 300 | Wildcard Portal Prinsiple AMK (Dulux, Wings, dll) |
| **`A`** | **`api`** | `38.103.170.235` | Auto / 300 | **Gateway Utama Mobile Apps** |
| **`A`** | **`@`** *(root)* | `38.103.170.235` | Auto / 300 | Landing Page / Web Utama Perusahaan |

### B. Routing ke Server 2 (PT AKP):
| Tipe | Nama / Subdomain | Target / Nilai IP | TTL | Keterangan |
| :---: | :--- | :---: | :---: | :--- |
| **`A`** | **`akp`** | `38.103.170.223` | Auto / 300 | Web Admin & Portal PT AKP |
| **`A`** | **`*.akp`** | `38.103.170.223` | Auto / 300 | Wildcard Portal Prinsiple AKP |

### C. Routing ke Server 3 (PT ATK / Gabungan):
| Tipe | Nama / Subdomain | Target / Nilai IP | TTL | Keterangan |
| :---: | :--- | :---: | :---: | :--- |
| **`A`** | **`atk`** | `38.103.170.219` | Auto / 300 | Web Admin & Portal PT ATK |
| **`A`** | **`*.atk`** | `38.103.170.219` | Auto / 300 | Wildcard Portal Prinsiple ATK |

> [!TIP]
> **Jika menggunakan Cloudflare:**  
> Pastikan proxy awan diset ke status **DNS Only (ikon awan abu-abu)** saat pertama kali menerbitkan sertifikat SSL Let's Encrypt agar verifikasi HTTP-01 berjalan instan tanpa terhalang proxy.

---

## ⚙️ 3. LANGKAH 2: TAMBAHKAN DOMAIN DI AAPANEL MASING-MASING SERVER

Buka dashboard aaPanel di masing-masing server, masuk ke menu **Website** $\rightarrow$ klik nama website Anda $\rightarrow$ buka tab **Domain Manager**.

> [!IMPORTANT]
> **Trik Zero-Downtime:** Jangan hapus domain lama (`*.dgsoft.web.id`). Biarkan domain lama tetap terdaftar agar sistem bisa diakses dari kedua domain sekaligus selama masa transisi!

### 1. Di aaPanel Server 1 (`38.103.170.235`):
Tambahkan domain ini satu per satu pada tab **Domain Manager**:
- `amk.esa-solution.id`
- `api.esa-solution.id`
- `esa-solution.id`
*(Klik tombol **Add**).*

### 2. Di aaPanel Server 2 (`38.103.170.223`):
Tambahkan domain ini:
- `akp.esa-solution.id`
*(Klik tombol **Add**).*

### 3. Di aaPanel Server 3 (`38.103.170.219`):
Tambahkan domain ini:
- `atk.esa-solution.id`
*(Klik tombol **Add**).*

---

## 🔒 4. LANGKAH 3: TERBITKAN SERTIFIKAT SSL LET'S ENCRYPT

Di jendela pengaturan website yang sama pada aaPanel masing-masing server:

1. Klik tab **SSL** $\rightarrow$ pilih sub-tab **Let's Encrypt**.
2. Pilih metode: **`File Verification`**.
3. Centang domain baru yang ingin dipasangi SSL (misal: `amk.esa-solution.id` dan `api.esa-solution.id`).
   > ⚠️ **Catatan:** Jangan mencentang domain wildcard bertanda bintang (`*.amk...`) jika menggunakan *File Verification*, cukup centang nama domain utama.
4. Klik tombol hijau **Apply**.
5. Setelah SSL berhasil terbit, aktifkan toggle **`Force HTTPS`** di pojok kanan atas tab SSL.

---

## 📝 5. LANGKAH 4: UPDATE `APP_URL` DI FILE `.ENV` & BERSIHKAN CACHE

Agar link notifikasi, ekspor laporan, dan aset sistem menggunakan domain baru `esa-solution.id`, sesuaikan file `.env` di masing-masing server melalui terminal SSH:

### A. Terminal Server 1 (PT AMK):
```bash
sed -i "s|APP_URL=.*|APP_URL=https://amk.esa-solution.id|g" /www/wwwroot/amk.dgsoft.web.id/.env
cd /www/wwwroot/amk.dgsoft.web.id && php artisan optimize:clear
```

### B. Terminal Server 2 (PT AKP):
```bash
sed -i "s|APP_URL=.*|APP_URL=https://akp.esa-solution.id|g" /www/wwwroot/akp.dgsoft.web.id/.env
cd /www/wwwroot/akp.dgsoft.web.id && php artisan optimize:clear
```

### C. Terminal Server 3 (PT ATK):
```bash
sed -i "s|APP_URL=.*|APP_URL=https://atk.esa-solution.id|g" /www/wwwroot/atk.dgsoft.web.id/.env
cd /www/wwwroot/atk.dgsoft.web.id && php artisan optimize:clear
```

---

## ✅ 6. LANGKAH 5: PENGUJIAN & VERIFIKASI AKHIR

Setelah 4 langkah di atas selesai, buka browser dan uji akses seluruh layanan berikut:

1. 🏢 **Dashboard Admin PT AMK:**  
   👉 `https://amk.esa-solution.id/admin`
2. 🏢 **Dashboard Admin PT AKP:**  
   👉 `https://akp.esa-solution.id/admin`
3. 🏢 **Dashboard Admin PT ATK:**  
   👉 `https://atk.esa-solution.id/admin`
4. 📱 **Gateway Mobile Application:**  
   👉 `https://api.esa-solution.id` *(akan merespon status aktif backend API)*

---

## 🛠️ 7. CATATAN TROUBLESHOOTING

| Masalah | Penyebab | Solusi |
| :--- | :--- | :--- |
| **`ERR_CONNECTION_TIMED_OUT`** | Laptop masih menyimpan cache DNS IP lama | Buka CMD di Windows &rarr; ketik `ipconfig /flushdns`, atau buka via Chrome Incognito (`Ctrl + Shift + N`). |
| **Menu Sidebar / Dashboard Kosong** | Spatie permission cache belum sinkron ke domain baru | Buka URL ini di browser sekali: `https://[subdomain].esa-solution.id/fix-admin-access`. |
| **Livewire JS 404** | File fisik `livewire.js` belum disalin ke folder `public/livewire` | Di terminal jalankan: `mkdir -p public/livewire && \cp -rf vendor/livewire/livewire/dist/* public/livewire/`. |
| **Bypass Login Cepat** | Lupa password setelah migrasi | Buka URL: `https://[subdomain].esa-solution.id/login-as-admin` untuk login instan. |
