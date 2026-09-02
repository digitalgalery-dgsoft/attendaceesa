# PANDUAN LENGKAP SETTING & DEPLOYMENT 3 SERVER PRODUCTION
## Sistem Presensi Mobile & Pelaporan Lapangan ESA Groups (23.511 Karyawan)
**Domain Resmi:** `https://esa-solution.id` • **Arsitektur:** 1 Domain Utama, 3 Subdomain Entitas di 3 Cloud VPS Terpisah
**Stack:** Ubuntu 22.04 LTS • aaPanel (LNMP) • Nginx 1.24+ • PHP 8.2 • PostgreSQL/MySQL • Redis 7 • Supervisor

---

## 🖥️ DATA & PEMETAAN 3 SERVER PRODUCTION

Berdasarkan konfirmasi domain resmi **`esa-solution.id`** dan data VPS:

| Node Server | Entitas / PT | Kapasitas Server (VPS) | Public IP | Gateway / Subnet | Domain / Subdomain Resmi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **SERVER 1** | **PT Arina Multi Karya (AMK)**<br/>*(11.687 Karyawan)* | 8 vCPU / 16 GiB RAM<br/>`srv-67622203.servername.com` | `38.103.170.222` | Gateway: `38.103.170.1`<br/>Netmask: `255.255.255.0` | `amk.esa-solution.id`<br/>`*.amk.esa-solution.id`<br/>*(Portal Prinsiple AMK)* |
| **SERVER 2** | **PT Alva Karya Perkasa (AKP)**<br/>*(4.400 Karyawan)* | 8 vCPU / 8 GiB RAM<br/>`srv-76042671.servername.com` | `38.103.170.223` | Gateway: `38.103.170.1`<br/>Netmask: `255.255.255.0` | `akp.esa-solution.id`<br/>`*.akp.esa-solution.id`<br/>*(Portal Prinsiple AKP)* |
| **SERVER 3** | **Gabungan (ATK, ATB, ABO)**<br/>*(7.424 Karyawan - Multi-Tenant)* | 8 vCPU / 8 GiB RAM<br/>*(Node Gabungan Multi-Tenant)* | `38.103.170.224`* *(sesuaikan IP fisik)* | Gateway: `38.103.170.1`<br/>Netmask: `255.255.255.0` | `atk.esa-solution.id`<br/>`*.atk.esa-solution.id`<br/>*(Portal Prinsiple ATK/ATB)* |

---

## 🌐 LANGKAH 1: KONFIGURASI DNS (HANYA DI 1 DASHBOARD DNS `esa-solution.id`)

Karena Anda menggunakan **1 Domain Utama (`esa-solution.id`)**, seluruh konfigurasi DNS dilakukan pada **1 tempat** (di dashboard penyedia domain tempat `esa-solution.id` terdaftar atau di Cloudflare).

Buka menu **DNS Management / DNS Records** pada domain `esa-solution.id`, lalu tambahkan DNS Records berikut:

### 1. Routing ke SERVER 1 (PT AMK - IP `38.103.170.222`):
- **Tipe:** `A` | **Name:** `amk` | **Target / IP:** `38.103.170.222` | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `*.amk` *(Wildcard)* | **Target / IP:** `38.103.170.222` | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `api` *(Mobile Gateway)* | **Target / IP:** `38.103.170.222` | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `@` *(Root Domain)* | **Target / IP:** `38.103.170.222` | **TTL:** Auto / 300

### 2. Routing ke SERVER 2 (PT AKP - IP `38.103.170.223`):
- **Tipe:** `A` | **Name:** `akp` | **Target / IP:** `38.103.170.223` | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `*.akp` *(Wildcard)* | **Target / IP:** `38.103.170.223` | **TTL:** Auto / 300

### 3. Routing ke SERVER 3 (Gabungan ATK/ATB/ABO - IP Server 3):
- **Tipe:** `A` | **Name:** `atk` | **Target / IP:** `38.103.170.224` *(IP Server 3)* | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `*.atk` *(Wildcard)* | **Target / IP:** `38.103.170.224` *(IP Server 3)* | **TTL:** Auto / 300
- *(Opsional alias)* **Tipe:** `A` | **Name:** `atb` | **Target / IP:** `38.103.170.224` *(IP Server 3)* | **TTL:** Auto / 300

> [!TIP]
> Jika menggunakan Cloudflare, pastikan ikon awan berstatus **DNS Only (Abu-abu)** saat instalasi awal SSL Let's Encrypt agar validasi sertifikat SSL berjalan lancar tanpa kendala.

---

## ⚙️ LANGKAH 2: INSTALASI AAPANEL PADA 3 SERVER

Akses terminal SSH (atau menu **Xterm.js Console** di panel VPS masing-masing):

1. Login sebagai `root`:
   ```bash
   ssh root@38.103.170.222   # Server 1
   ssh root@38.103.170.223   # Server 2
   # Dan IP Server 3
   ```
2. Jalankan script instalasi resmi aaPanel:
   ```bash
   URL=https://www.aapanel.com/script/install_6.0_en.sh && if [ -f /usr/bin/curl ];then curl -ksSO "$URL" ;else wget --no-check-certificate -O install_6.0_en.sh "$URL";fi;bash install_6.0_en.sh aapanel
   ```
3. Tekan **`y`** saat konfirmasi instalasi ke direktori `/www`.
4. Setelah selesai, catat:
   - **URL Panel:** `http://IP-SERVER:7800/xxxxxx`
   - **Username:** `xxxxxx`
   - **Password:** `xxxxxx`
5. Login ke web dashboard aaPanel masing-masing server.

---

## 📦 LANGKAH 3: INSTALASI LNMP & EKSTENSI PHP 8.2

### A. Paket Rekomendasi Awal (Saat pertama login aaPanel):
Pilih metode instalasi **LNMP (Fast)**:
- **Nginx:** `1.24` (atau versi stabil terbaru)
- **PHP:** `8.2` *(Wajib PHP 8.2 untuk kompatibilitas Laravel 11 & Filament v3)*
- **Database:** `PostgreSQL 15` (atau `MySQL 8.0`)
- **phpPgAdmin / phpMyAdmin:** Opsional
- Klik **One-Click Install**.

### B. Install Aplikasi Tambahan (Menu App Store aaPanel):
1. **Redis 7.0+** *(Wajib untuk session, caching cepat, dan antrean queue)*.
2. **Supervisor: Process Manager** *(Wajib untuk menjalankan worker Laravel)*.

### C. Ekstensi Wajib PHP 8.2:
Buka **App Store** $\rightarrow$ cari **PHP 8.2** $\rightarrow$ klik **Setting** $\rightarrow$ tab **Install extensions**:
Install ekstensi berikut:
- `fileinfo` (Validasi upload foto presensi kamera)
- `redis` (Koneksi Redis Cache & Queue)
- `pgsql` & `pdo_pgsql` (Driver PostgreSQL) ATAU `mysqli` & `pdo_mysql` (Driver MySQL)
- `gd` atau `imagick` (Kompresi foto selfie presensi)
- `zip` (Export Excel/PDF reporting)
- `xml` / `xmlrpc` (Engine Odoo XML-RPC Sync)
- `bcmath`
- `opcache` (Performa maksimal komputasi PHP)

### D. Tuning Performa PHP 8.2 (`php.ini`):
Di jendela setting PHP 8.2 $\rightarrow$ tab **Configuration**:
```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 1024M
post_max_size = 50M
upload_max_filesize = 50M
max_input_vars = 5000
```
Klik **Save**, lalu masuk ke tab **Service** dan klik **Restart** PHP 8.2.

---

## 🌐 LANGKAH 4: PEMBUATAN WEBSITE & SSL DI MASING-MASING SERVER

### 1. Tambah Website (Menu Website $\rightarrow$ Add site):

#### Di SERVER 1 (AMK):
- **Domain name:**
  ```text
  amk.esa-solution.id
  *.amk.esa-solution.id
  api.esa-solution.id
  ```
- **Root directory:** `/www/wwwroot/att-admin-v12`
- **Database:** Buat Database baru (misal: `db_esa_amk`)
- **PHP Version:** `PHP-82`
- Klik **Submit**.

#### Di SERVER 2 (AKP):
- **Domain name:**
  ```text
  akp.esa-solution.id
  *.akp.esa-solution.id
  ```
- **Root directory:** `/www/wwwroot/att-admin-v12`
- **Database:** `db_esa_akp`
- **PHP Version:** `PHP-82`
- Klik **Submit**.

#### Di SERVER 3 (ATK / Gabungan):
- **Domain name:**
  ```text
  atk.esa-solution.id
  *.atk.esa-solution.id
  atb.esa-solution.id
  ```
- **Root directory:** `/www/wwwroot/att-admin-v12`
- **Database:** `db_esa_atk`
- **PHP Version:** `PHP-82`
- Klik **Submit**.

---

### 2. Pengaturan Lanjutan Website (Klik Setting pada nama website):
- **A. Site Directory:**
  - Running directory: Ganti dari `/` menjadi **`/public`** *(Sangat penting)*.
  - Klik **Save**.
- **B. URL Rewrite:**
  - Pilih preset template: **`laravel5`** (atau `laravel`):
    ```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```
  - Klik **Save**.
- **C. SSL Certificate (Let's Encrypt Wildcard):**
  - Masuk ke tab **SSL** $\rightarrow$ pilih **Let's Encrypt**.
  - Centang domain utama dan wildcard `*`.
  - Klik **Apply** dan setelah berhasil, aktifkan toggle **Force HTTPS**.

---

## 📂 LANGKAH 5: CLONE SOURCE CODE & KONFIGURASI `.ENV`

Buka **Terminal** di aaPanel masing-masing server:

```bash
cd /www/wwwroot/att-admin-v12
git clone https://github.com/digitalgalery-dgsoft/attendaceesa.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
```

### Penyesuaian Nilai `.env` Masing-Masing Server:

#### Server 1 (`.env` PT AMK):
```env
APP_NAME="ESA Presensi - PT AMK"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://amk.esa-solution.id

CURRENT_SERVER_ID=server_1
SERVER_GATEWAY_URL=https://api.esa-solution.id
MEDIA_STORAGE_URL=https://storage.esa-solution.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_esa_amk
DB_USERNAME=user_esa_amk
DB_PASSWORD=PasswordDbRahasia123!

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### Server 2 (`.env` PT AKP):
```env
APP_NAME="ESA Presensi - PT AKP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://akp.esa-solution.id

CURRENT_SERVER_ID=server_2
SERVER_GATEWAY_URL=https://akp.esa-solution.id
MEDIA_STORAGE_URL=https://storage.esa-solution.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_esa_akp
DB_USERNAME=user_esa_akp
DB_PASSWORD=PasswordDbRahasia123!

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### Server 3 (`.env` Gabungan PT ATK, ATB, ABO):
```env
APP_NAME="ESA Presensi - Gabungan ATK ATB ABO"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://atk.esa-solution.id

CURRENT_SERVER_ID=server_3
SERVER_GATEWAY_URL=https://atk.esa-solution.id
MEDIA_STORAGE_URL=https://storage.esa-solution.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_esa_atk
DB_USERNAME=user_esa_atk
DB_PASSWORD=PasswordDbRahasia123!

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Setelah file `.env` disimpan, jalankan perintah setup:
```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan filament:optimize
chown -R www:www /www/wwwroot/att-admin-v12
chmod -R 775 /www/wwwroot/att-admin-v12/storage /www/wwwroot/att-admin-v12/bootstrap/cache
```

---

## ⚡ LANGKAH 6: SETTING SUPERVISOR (QUEUE WORKER REDIS)

Buka **App Store** $\rightarrow$ cari **Supervisor** $\rightarrow$ klik **Setting** $\rightarrow$ **Add Daemon**:
- **Name:** `laravel-queue-worker`
- **Run User:** `www`
- **Run Dir:** `/www/wwwroot/att-admin-v12`
- **Start Command:**
  ```bash
  php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
  ```
- **Process Num:** `4` *(untuk Server 1)* / `2` *(untuk Server 2 & 3)*
- Klik **Confirm** dan pastikan status **RUNNING**.

---

## ⏰ LANGKAH 7: SETTING CRON JOB OTOMATIS

Buka menu **Cron** di panel samping aaPanel masing-masing server:

### Task 1: Master Scheduler Laravel (Tiap 1 Menit)
- **Type of Task:** `Shell Script`
- **Name of Task:** `Laravel Schedule Runner`
- **Period:** `N Minutes` $\rightarrow$ `1 Minute`
- **Script Content:**
  ```bash
  cd /www/wwwroot/att-admin-v12 && php artisan schedule:run >> /dev/null 2>&1
  ```
- Klik **Add task**.

### Task 2: Background Odoo Sync Karyawan (Pukul 01:00 WIB Dini Hari)
- **Type of Task:** `Shell Script`
- **Name of Task:** `Odoo Sync Auto Daily`
- **Period:** `Day` $\rightarrow$ Jam `1`, Menit `0`
- **Script Content:**
  ```bash
  cd /www/wwwroot/att-admin-v12 && php artisan odoo:sync-employees >> /www/wwwroot/att-admin-v12/storage/logs/odoo_sync_cron.log 2>&1
  ```
- Klik **Add task**.

---

## 📱 LANGKAH 8: INTEGRASI CLIENT MOBILE & PORTAL PRINSIPLE

### A. Dynamic Server Routing di Mobile App:
1. Mobile App di-build menggunakan gateway utama: `https://api.esa-solution.id`.
2. Karyawan login hanya menggunakan **NIK** & **Password**.
3. Sistem mendeteksi entitas:
   - Jika karyawan PT AMK $\rightarrow$ otomatis diarahkan ke `https://amk.esa-solution.id/api`
   - Jika karyawan PT AKP $\rightarrow$ otomatis diarahkan ke `https://akp.esa-solution.id/api`
   - Jika karyawan ATK/ATB/ABO $\rightarrow$ otomatis diarahkan ke `https://atk.esa-solution.id/api`
4. Seluruh transaksi presensi selfie kamera, verifikasi GPS, dan laporan langsung menuju ke server masing-masing secara cepat dan tanpa antrean.

### B. Portal Pelaporan Subdomain Prinsiple:
Dengan konfigurasi wildcard DNS `*.amk.esa-solution.id`, `*.akp.esa-solution.id`, dan `*.atk.esa-solution.id`, portal klien prinsiple dapat langsung dibuka:
- 🌐 `https://dulux.amk.esa-solution.id` (Portal Reporting Dulux PT AMK)
- 🌐 `https://wings.amk.esa-solution.id` (Portal Reporting Wings PT AMK)
- 🌐 `https://fonterra.atk.esa-solution.id` (Portal Reporting Fonterra PT ATK)
- 🌐 `https://sidomuncul.akp.esa-solution.id` (Portal Reporting PT AKP)
