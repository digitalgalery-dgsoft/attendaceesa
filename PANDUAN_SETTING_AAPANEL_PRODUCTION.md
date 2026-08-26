# PANDUAN LENGKAP SETTING SERVER PRODUCTION DENGAN AAPANEL
## Sistem Presensi Mobile & Pelaporan ESA Groups (23.511 Karyawan)
**Stack:** Ubuntu 22.04 LTS • aaPanel (LNMP) • Nginx 1.24+ • PHP 8.2 • PostgreSQL / MySQL • Redis • Supervisor

---

## 📋 DAFTAR ISI
1. [Langkah 1: Instalasi aaPanel pada VPS Ubuntu 22.04](#langkah-1-instalasi-aapanel-pada-vps-ubuntu-2204)
2. [Langkah 2: Instalasi Komponen Environment (App Store aaPanel)](#langkah-2-instalasi-komponen-environment-app-store-aapanel)
3. [Langkah 3: Konfigurasi & Ekstensi PHP 8.2](#langkah-3-konfigurasi--ekstensi-php-82)
4. [Langkah 4: Setup Website & Domain di aaPanel](#langkah-4-setup-website--domain-di-aapanel)
5. [Langkah 5: Clone Project & Konfigurasi Environment (.env)](#langkah-5-clone-project--konfigurasi-environment-env)
6. [Langkah 6: Setting Supervisor (Antrean Job Queue) di aaPanel](#langkah-6-setting-supervisor-antrean-job-queue-di-aapanel)
7. [Langkah 7: Setting Cron Job Otomatis (Odoo Sync & Scheduler)](#langkah-7-setting-cron-job-otomatis-odoo-sync--scheduler)
8. [Langkah 8: Pengamanan Firewall & Jaringan Inter-VPC](#langkah-8-pengamanan-firewall--jaringan-inter-vpc)

---

## Langkah 1: Instalasi aaPanel pada VPS Ubuntu 22.04

1. Login ke VPS via SSH (sebagai `root`):
   ```bash
   ssh root@IP_SERVER_VPS
   ```
2. Jalankan perintah instalasi resmi aaPanel:
   ```bash
   URL=https://www.aapanel.com/script/install_6.0_en.sh && if [ -f /usr/bin/curl ];then curl -ksSO "$URL" ;else wget --no-check-certificate -O install_6.0_en.sh "$URL";fi;bash install_6.0_en.sh aapanel
   ```
3. Tekan **`y`** saat diminta konfirmasi instalasi ke direktori `/www`.
4. Setelah instalasi selesai (sekitar 2–3 menit), catat informasi login:
   - **aaPanel Internet Address:** `https://IP_SERVER:7800/xxxxxx`
   - **Username:** `xxxxxxxx`
   - **Password:** `xxxxxxxx`
5. Buka URL tersebut di browser dan login ke aaPanel.

---

## Langkah 2: Instalasi Komponen Environment (App Store aaPanel)

Saat pertama kali login, aaPanel akan menampilkan rekomendasi paket. Pilih metode **LNMP (Fast/Compiled)** dengan komponen:
* **Nginx:** `1.24` (atau versi stabil terbaru)
* **PHP:** `8.2` (Wajib PHP 8.2 untuk kompatibilitas Laravel 11 & Filament v3)
* **Database:** `PostgreSQL 15` atau `MySQL 8.0`
* **phpMyAdmin / phpPgAdmin:** (Optional untuk kelola database visual)
* **FTP:** Pure-Ftpd (Opsional)

### Instalasi Aplikasi Tambahan dari menu "App Store" aaPanel:
1. Cari dan Install **Redis 7.0+** (Untuk cache session, locking, dan antrean super cepat).
2. Cari dan Install **Supervisor: Process Manager** (Untuk menjalankan antrean Laravel Queue worker di latar belakang).
3. Cari dan Install **Fail2ban** (Untuk proteksi anti-bruteforce SSH & Web login).

---

## Langkah 3: Konfigurasi & Ekstensi PHP 8.2

Buka menu **App Store** $\rightarrow$ cari **PHP 8.2** $\rightarrow$ klik **Setting**:

### A. Install Extensions (Wajib):
Masuk ke tab **Install extensions**, klik tombol install pada:
1. `fileinfo` (Wajib untuk validasi upload foto selfie presensi)
2. `redis` (Wajib untuk koneksi cache Redis)
3. `pgsql` & `pdo_pgsql` (Jika menggunakan PostgreSQL) ATAU `mysqli` & `pdo_mysql` (Jika menggunakan MySQL)
4. `gd` / `imagick` (Untuk kompresi foto kamera presensi)
5. `zip` (Untuk export Excel/ZIP reporting)
6. `xmlrpc` / `xml` (Untuk engine Odoo XML-RPC Sync)
7. `bcmath`
8. `opcache` (Untuk performa komputasi PHP maksimal)

### B. Configuration Tuning (`php.ini`):
Masuk ke tab **Configuration**, sesuaikan nilai parameter berikut untuk menangani traffic ribuan karyawan:
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

## Langkah 4: Setup Website & Domain di aaPanel

Buka menu **Website** $\rightarrow$ klik **Add site**:

1. **Domain name:**
   * Server 1 (PT AMK): `amk.esagroups.id`, `*.amk.esagroups.id`, `api.esagroups.id`
   * Server 2 (ATB+ATK+ABO): `atb.esagroups.id`, `*.atb.esagroups.id`
   * Server 3 (PT AKP): `akp.esagroups.id`, `*.akp.esagroups.id`
2. **Path / Root Directory:**
   ```text
   /www/wwwroot/att-admin-v12
   ```
3. **Database:** Buat Database baru (misal: `db_esa_amk` atau `db_esa_atb`).
4. **PHP Version:** Pilih `PHP-82`.
5. Klik **Submit**.

### Konfigurasi Lanjutan Website (Klik Setting pada nama website):

* **A. Site Directory (Document Root):**
  - **Running directory:** Pilih `/public` (Sangat penting agar mengarah ke `att-admin-v12/public`).
  - Klik **Save**.
* **B. URL Rewrite:**
  - Pilih preset template: **`laravel5`** (atau `laravel`).
  - Isinya adalah:
    ```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```
  - Klik **Save**.
* **C. SSL Certificate:**
  - Masuk ke tab **SSL** $\rightarrow$ pilih **Let's Encrypt**.
  - Centang seluruh domain dan subdomain $\rightarrow$ klik **Apply** untuk mengaktifkan HTTPS gratis otomatis.
  - Aktifkan toggle **Force HTTPS**.

---

## Langkah 5: Clone Project & Konfigurasi Environment (.env)

1. Masuk ke terminal VPS via SSH atau menu **Terminal** di aaPanel:
   ```bash
   cd /www/wwwroot/att-admin-v12
   ```
2. Clone kode dari repository GitHub:
   ```bash
   git clone https://github.com/digitalgalery-dgsoft/attendaceesa.git .
   ```
3. Install dependensi PHP (Composer):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Salin file `.env` dan sesuaikan konfigurasi database:
   ```bash
   cp .env.example .env
   nano .env
   ```
   **Contoh setting `.env` penting:**
   ```env
   APP_NAME="ESA Presensi & Reporting"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://amk.esagroups.id

   CURRENT_SERVER_ID=server_1
   SERVER_GATEWAY_URL=https://api.esagroups.id
   MEDIA_STORAGE_URL=https://storage.esagroups.id

   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=db_esa_amk
   DB_USERNAME=user_esa_amk
   DB_PASSWORD=PasswordRahasia123!

   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```
5. Generate Application Key, Migration Database, dan Cache:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   php artisan filament:optimize
   ```
6. Set permission folder web server:
   ```bash
   chown -R www:www /www/wwwroot/att-admin-v12
   chmod -R 775 /www/wwwroot/att-admin-v12/storage /www/wwwroot/att-admin-v12/bootstrap/cache
   ```

---

## Langkah 6: Setting Supervisor (Antrean Job Queue) di aaPanel

Buka menu **App Store** $\rightarrow$ cari **Supervisor** $\rightarrow$ klik **Setting** $\rightarrow$ klik **Add Daemon**:

* **Name:** `laravel-worker`
* **Run User:** `www`
* **Run Dir:** `/www/wwwroot/att-admin-v12`
* **Start Command:**
  ```bash
  php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
  ```
* **Process Num:** `4` (Menjalankan 4 worker paralel untuk memproses notifikasi & background export)
* Klik **Confirm**.

---

## Langkah 7: Setting Cron Job Otomatis (Odoo Sync & Scheduler)

Buka menu **Cron** di panel samping aaPanel $\rightarrow$ tambahkan 2 Task:

### Task 1: Laravel Master Scheduler (Wajib - Setiap Menit)
* **Type of Task:** `Shell Script`
* **Name of Task:** `Laravel Schedule Runner`
* **Period:** `N Minutes` $\rightarrow$ `1 Minute`
* **Script Content:**
  ```bash
  cd /www/wwwroot/att-admin-v12 && php artisan schedule:run >> /dev/null 2>&1
  ```
* Klik **Add task**.

### Task 2: Background Odoo Employee Sync Dini Hari (Setiap Pukul 01:00 WIB)
* **Type of Task:** `Shell Script`
* **Name of Task:** `Odoo Sync Auto Daily`
* **Period:** `Day` $\rightarrow$ Hour `1`, Minute `0`
* **Script Content:**
  ```bash
  cd /www/wwwroot/att-admin-v12 && php artisan odoo:sync-employees >> /www/wwwroot/att-admin-v12/storage/logs/odoo_sync_cron.log 2>&1
  ```
* Klik **Add task**.

---

## Langkah 8: Pengamanan Firewall & Jaringan Inter-VPC

Buka menu **Security** di aaPanel:
1. **Buka Port Publik Esensial Saja:**
   * `80` (HTTP) & `443` (HTTPS)
   * `7800` (Port Panel aaPanel - *Bisa diubah ke port custom untuk keamanan*)
   * `22` (SSH - *Disarankan gunakan SSH Key Authentication*)
2. **Kunci Port Database & Redis dari Akses Publik:**
   * Port `5432` (PostgreSQL), `3306` (MySQL), dan `6379` (Redis) **DITUTUP dari Public IP**.
   * Hanya izinkan koneksi database antar server melalui **Jalur Private Network / Inter-VPC (10.0.1.x)**.

---

### ✅ SELESAI
Server kini siap 100% melayani operasional presensi, pelaporan kunjungan toko, Odoo sync otomatis, dan portal subdomain prinsiple dengan performa tinggi dan stabil!
