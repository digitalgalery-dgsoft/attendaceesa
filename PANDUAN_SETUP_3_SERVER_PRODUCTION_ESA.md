# PANDUAN LENGKAP SETTING & DEPLOYMENT 3 SERVER PRODUCTION
## Sistem Presensi Mobile & Pelaporan Lapangan ESA Groups (23.511 Karyawan)
**Status:** Production Ready • **Arsitektur:** 3 Cloud VPS Terpisah • **Stack:** Ubuntu 22.04 • aaPanel (LNMP) • Nginx 1.24+ • PHP 8.2 • PostgreSQL/MySQL • Redis 7 • Supervisor

---

## 🖥️ DATA & PEMETAAN 3 SERVER PRODUCTION

Berdasarkan data dari file slide presentasi (`Panduan_Arsitektur_dan_Setting_Production_3_Server_ESA.pptx`):

| Node Server | Entitas / PT | Kapasitas Server (VPS) | Public IP | Gateway / Subnet | Domain Target (Layanan Terpisah) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **SERVER 1** | **PT Arina Multi Karya (AMK)**<br/>*(11.687 Karyawan)* | 8 vCPU / 16 GiB RAM<br/>`srv-67622203.servername.com` | `38.103.170.222` | Gateway: `38.103.170.1`<br/>Netmask: `255.255.255.0` | `amk.esagroups.id`<br/>`*.amk.esagroups.id`<br/>*(Layanan DNS 1)* |
| **SERVER 2** | **PT Alva Karya Perkasa (AKP)**<br/>*(4.400 Karyawan)* | 8 vCPU / 8 GiB RAM<br/>`srv-76042671.servername.com` | `38.103.170.223` | Gateway: `38.103.170.1`<br/>Netmask: `255.255.255.0` | `akp.esagroups.id`<br/>`*.akp.esagroups.id`<br/>*(Layanan DNS 2)* |
| **SERVER 3** | **Gabungan (ATB, ATK, ABO)**<br/>*(7.424 Karyawan - Multi-Tenant)* | 8 vCPU / 8 GiB RAM<br/>*(Node Gabungan Multi-Tenant)* | `38.103.170.224`* *(sesuaikan IP fisik)* | Gateway: `38.103.170.1`<br/>Netmask: `255.255.255.0` | `atb.esagroups.id`<br/>`*.atb.esagroups.id`<br/>*(Layanan DNS 3)* |

> [!NOTE]
> Pada slide 11 presentasi, gambar panel tertempel IP `38.103.170.223` (sama dengan Server 2). Jika Server 3 menggunakan IP berbeda (misal `38.103.170.224`), gunakan IP fisik Server 3 tersebut pada DNS dan konfigurasi di bawah.

---

## 🌐 LANGKAH 1: KONFIGURASI DNS PADA LAYANAN DOMAIN TERPISAH

Karena domain masing-masing server berada di penyedia layanan terpisah (misal: Cloudflare, Niagahoster, Domainesia, Idwebhost, dll.), lakukan penambahan **DNS Record** di dashboard penyedia domain masing-masing:

### A. Layanan Domain 1 (Untuk Server 1 - AMK):
Arahkan domain utama dan **Wildcard Subdomain `*`** (untuk portal prinsiple seperti `dulux.amk.esagroups.id`, `wings.amk.esagroups.id`) ke IP Server 1:
- **Tipe:** `A` | **Name:** `@` (atau `amk`) | **Content / IP:** `38.103.170.222` | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `*` (Wildcard) | **Content / IP:** `38.103.170.222` | **TTL:** Auto / 300
- *(Opsional Gateway Mobile)* **Tipe:** `A` | **Name:** `api` | **Content / IP:** `38.103.170.222` | **TTL:** Auto / 300

### B. Layanan Domain 2 (Untuk Server 2 - AKP):
Arahkan domain utama dan Wildcard Subdomain ke IP Server 2:
- **Tipe:** `A` | **Name:** `@` (atau `akp`) | **Content / IP:** `38.103.170.223` | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `*` (Wildcard) | **Content / IP:** `38.103.170.223` | **TTL:** Auto / 300

### C. Layanan Domain 3 (Untuk Server 3 - ATB, ATK, ABO):
Arahkan domain utama dan Wildcard Subdomain ke IP Server 3:
- **Tipe:** `A` | **Name:** `@` (atau `atb`) | **Content / IP:** `38.103.170.224` *(IP Server 3)* | **TTL:** Auto / 300
- **Tipe:** `A` | **Name:** `*` (Wildcard) | **Content / IP:** `38.103.170.224` *(IP Server 3)* | **TTL:** Auto / 300

> [!TIP]
> Jika menggunakan Cloudflare di salah satu penyedia, pastikan status **Proxy (Orange Cloud)** dimatikan (menjadi **DNS Only / Grey Cloud**) terlebih dahulu saat instalasi SSL awal agar verifikasi Let's Encrypt berjalan mulus.

---

## ⚙️ LANGKAH 2: INSTALASI AAPANEL PADA MASING-MASING SERVER (1, 2, & 3)

Buka terminal SSH (via PuTTY, terminal lokal, atau fitur **Xterm.js Console** pada panel VPS masing-masing server):

1. Login sebagai `root`:
   ```bash
   ssh root@38.103.170.222   # Untuk Server 1
   # Ulangi untuk Server 2 (38.103.170.223) dan Server 3
   ```
2. Jalankan perintah instalasi resmi aaPanel:
   ```bash
   URL=https://www.aapanel.com/script/install_6.0_en.sh && if [ -f /usr/bin/curl ];then curl -ksSO "$URL" ;else wget --no-check-certificate -O install_6.0_en.sh "$URL";fi;bash install_6.0_en.sh aapanel
   ```
3. Tekan **`y`** saat diminta konfirmasi target direktori `/www`.
4. Setelah instalasi selesai (sekitar 2–3 menit), catat informasi login:
   - **URL aaPanel:** `http://IP-SERVER:7800/xxxxxx`
   - **Username:** `xxxxxx`
   - **Password:** `xxxxxx`
5. Buka URL tersebut di browser dan login ke aaPanel masing-masing server.

---

## 📦 LANGKAH 3: INSTALASI LNMP & EKSTENSI PHP 8.2

### A. Paket Rekomendasi Awal (Saat pertama login aaPanel):
Pilih metode instalasi **LNMP (Fast)**:
- **Nginx:** `1.24` (atau rilis stabil terbaru)
- **PHP:** `8.2` *(Wajib 8.2 untuk kompatibilitas Laravel 11 & Filament v3)*
- **PostgreSQL / MySQL:** `PostgreSQL 15` (atau `MySQL 8.0`)
- **phpPgAdmin / phpMyAdmin:** Opsional
- Klik **One-Click Install**.

### B. Aplikasi Tambahan (Menu App Store aaPanel):
1. Cari dan Install **Redis 7.0+** *(Wajib untuk caching antrean dan performa)*.
2. Cari dan Install **Supervisor: Process Manager** *(Wajib untuk daemon Laravel Queue Worker)*.

### C. Instalasi Ekstensi Wajib PHP 8.2:
Buka **App Store** $\rightarrow$ cari **PHP 8.2** $\rightarrow$ klik **Setting** $\rightarrow$ tab **Install extensions**:
Install ekstensi berikut:
- `fileinfo` (Validasi upload foto presensi kamera)
- `redis` (Koneksi Redis Cache & Queue)
- `pgsql` & `pdo_pgsql` (Driver PostgreSQL) ATAU `mysqli` & `pdo_mysql` (Driver MySQL)
- `gd` atau `imagick` (Kompresi foto selfie presensi)
- `zip` (Export Excel/PDF reporting)
- `xml` / `xmlrpc` (Engine Odoo XML-RPC Sync)
- `bcmath`
- `opcache` (Komputasi cepat PHP)

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

## 🌐 LANGKAH 4: PEMBUATAN WEBSITE & SSL DI AAPANEL

Lakukan langkah ini pada masing-masing server sesuai domainnya:

### 1. Tambah Website (Menu Website $\rightarrow$ Add site):
- **Server 1 (AMK):**
  - Domain name:
    ```text
    amk.esagroups.id
    *.amk.esagroups.id
    ```
  - Root directory: `/www/wwwroot/att-admin-v12`
  - Database: Buat Database baru (misal: `db_esa_amk`)
  - PHP Version: `PHP-82`
  - Klik **Submit**.

- **Server 2 (AKP):**
  - Domain name:
    ```text
    akp.esagroups.id
    *.akp.esagroups.id
    ```
  - Root directory: `/www/wwwroot/att-admin-v12`
  - Database: `db_esa_akp`
  - PHP Version: `PHP-82`
  - Klik **Submit**.

- **Server 3 (ATB/Gabungan):**
  - Domain name:
    ```text
    atb.esagroups.id
    *.atb.esagroups.id
    ```
  - Root directory: `/www/wwwroot/att-admin-v12`
  - Database: `db_esa_atb`
  - PHP Version: `PHP-82`
  - Klik **Submit**.

### 2. Pengaturan Lanjutan Website (Klik Setting pada nama website):
- **A. Site Directory:**
  - Running directory: Ganti dari `/` menjadi **`/public`** *(Wajib agar mengarah ke file index Laravel)*.
  - Klik **Save**.
- **B. URL Rewrite:**
  - Pilih preset: **`laravel5`** (atau `laravel`):
    ```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```
  - Klik **Save**.
- **C. SSL Certificate (Let's Encrypt Wildcard):**
  - Masuk ke tab **SSL** $\rightarrow$ pilih **Let's Encrypt**.
  - Centang domain utama dan wildcard `*.domain`.
  - Pilih metode verifikasi **DNS Verification** (atau File Verification jika tanpa wildcard).
  - Klik **Apply** dan aktifkan toggle **Force HTTPS**.

---

## 📂 LANGKAH 5: CLONE SOURCE CODE & KONFIGURASI ENVIRONMENT (.ENV)

Buka **Terminal** di aaPanel masing-masing server:

1. Masuk ke direktori web:
   ```bash
   cd /www/wwwroot/att-admin-v12
   ```
2. Clone repository GitHub proyek:
   ```bash
   git clone https://github.com/digitalgalery-dgsoft/attendaceesa.git .
   ```
3. Install vendor Composer:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Salin file environment:
   ```bash
   cp .env.example .env
   nano .env
   ```

### Konfigurasi `.env` Spesifik Per Server:

#### Server 1 (`.env` PT AMK):
```env
APP_NAME="ESA Presensi - PT AMK"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://amk.esagroups.id

CURRENT_SERVER_ID=server_1
SERVER_GATEWAY_URL=https://amk.esagroups.id
MEDIA_STORAGE_URL=https://storage.esagroups.id

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
APP_URL=https://akp.esagroups.id

CURRENT_SERVER_ID=server_2
SERVER_GATEWAY_URL=https://akp.esagroups.id
MEDIA_STORAGE_URL=https://storage.esagroups.id

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

#### Server 3 (`.env` Gabungan ATB/ATK/ABO):
```env
APP_NAME="ESA Presensi - Gabungan ATB ATK ABO"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://atb.esagroups.id

CURRENT_SERVER_ID=server_3
SERVER_GATEWAY_URL=https://atb.esagroups.id
MEDIA_STORAGE_URL=https://storage.esagroups.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_esa_atb
DB_USERNAME=user_esa_atb
DB_PASSWORD=PasswordDbRahasia123!

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

5. Generate Key, Migrasi Database, & Build Cache:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   php artisan filament:optimize
   ```

6. Atur Permission Folder Web:
   ```bash
   chown -R www:www /www/wwwroot/att-admin-v12
   chmod -R 775 /www/wwwroot/att-admin-v12/storage /www/wwwroot/att-admin-v12/bootstrap/cache
   ```

---

## ⚡ LANGKAH 6: SETTING SUPERVISOR (QUEUE WORKER REDIS)

Agar notifikasi push, proses email, dan background export Excel tidak membebani web browser:

1. Di menu **App Store** aaPanel $\rightarrow$ cari **Supervisor** $\rightarrow$ klik **Setting** $\rightarrow$ **Add Daemon**:
   - **Name:** `laravel-queue-worker`
   - **Run User:** `www`
   - **Run Dir:** `/www/wwwroot/att-admin-v12`
   - **Start Command:**
     ```bash
     php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
     ```
   - **Process Num:** `4` *(Untuk Server 1)* atau `2` *(Untuk Server 2 & 3)*
2. Klik **Confirm**.
3. Pastikan statusnya berubah menjadi hijau (**RUNNING**).

---

## ⏰ LANGKAH 7: SETTING CRON JOB OTOMATIS (SCHEDULER & ODOO SYNC)

Buka menu **Cron** di panel samping aaPanel masing-masing server, lalu tambahkan 2 Task:

### Task 1: Master Scheduler Laravel (Wajib - Berjalan Tiap Menit)
- **Type of Task:** `Shell Script`
- **Name of Task:** `Laravel Schedule Runner`
- **Period:** `N Minutes` $\rightarrow$ `1 Minute`
- **Script Content:**
  ```bash
  cd /www/wwwroot/att-admin-v12 && php artisan schedule:run >> /dev/null 2>&1
  ```
- Klik **Add task**.

### Task 2: Background Odoo Sync Dini Hari (Pukul 01:00 WIB)
- **Type of Task:** `Shell Script`
- **Name of Task:** `Odoo Sync Daily Automatic`
- **Period:** `Day` $\rightarrow$ Jam `1`, Menit `0`
- **Script Content:**
  ```bash
  cd /www/wwwroot/att-admin-v12 && php artisan odoo:sync-employees >> /www/wwwroot/att-admin-v12/storage/logs/odoo_sync_cron.log 2>&1
  ```
- Klik **Add task**.

---

## 🔒 LANGKAH 8: SECURITY & JALUR KOMUNIKASI LINTAS SERVER

Karena ketiga server berada dalam subnet yang sama (`38.103.170.x` dengan gateway `38.103.170.1`):

1. **Firewall aaPanel (Menu Security):**
   - Port publik yang dibuka: `80` (HTTP), `443` (HTTPS), `7800` (aaPanel), `22` (SSH).
   - Port Database (`5432` / `3306`) dan Redis (`6379`) **JANGAN DIBUKA KE PUBLIK (0.0.0.0)**.
2. **Akses Antar Server (Whitelist IP):**
   - Jika Server 1 butuh query langsung ke Server 2/3, cukup izinkan IP internal sesama server di firewall:
     - Allow IP `38.103.170.222`
     - Allow IP `38.103.170.223`
     - Allow IP `38.103.170.224`

---

## 📱 LANGKAH 9: VERIFIKASI MOBILE CLIENT & DYNAMIC ROUTING

Aplikasi mobile ESA Presensi dirancang dengan konsep **Single Entrypoint Gateway**:
1. Karyawan cukup memasukkan NIK dan Password di aplikasi mobile.
2. Gateway mendeteksi entitas asal karyawan berdasarkan NIK:
   - Jika karyawan PT AMK $\rightarrow$ otomatis terhubung ke `https://amk.esagroups.id/api`
   - Jika karyawan PT AKP $\rightarrow$ otomatis terhubung ke `https://akp.esagroups.id/api`
   - Jika karyawan ATB/ATK/ABO $\rightarrow$ otomatis terhubung ke `https://atb.esagroups.id/api`
3. Karyawan tidak perlu memilih URL atau server secara manual; seluruh pengalaman pengguna berjalan mulus (*seamless*).

---

## ✅ CHECKLIST STATUS KESIAPAN DEPLOYMENT

- [ ] DNS A Record & Wildcard `*` terarah ke IP server masing-masing.
- [ ] aaPanel terpasang dan LNMP (Nginx, PHP 8.2, PostgreSQL/MySQL, Redis) aktif.
- [ ] Ekstensi PHP 8.2 terpasang lengkap (`fileinfo`, `redis`, `pgsql`, `gd`, `zip`, `xmlrpc`, dll).
- [ ] Running directory website sudah diset ke `/public` dan URL Rewrite `laravel5`.
- [ ] SSL Let's Encrypt aktif dengan status Force HTTPS.
- [ ] Database termigrasi dan `php artisan optimize` berhasil dijalankan.
- [ ] Supervisor Queue Worker berjalan (status RUNNING).
- [ ] Cron Job Laravel Scheduler aktif setiap 1 menit.
