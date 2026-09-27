#!/usr/bin/env bash
# ==============================================================================
# 🧹 ESA GROUPS - PRODUCTION STORAGE CLEANER & SAFE LOG ROTATOR
# Sistem: Rocky Linux 8 / CentOS / AlmaLinux + aaPanel + PostgreSQL + Laravel
# Fitur: Membersihkan Log PostgreSQL, Log Nginx, Log Laravel, Cache, dan Recycle Bin
# ==============================================================================

set -u

echo "================================================================="
echo "   ESA PRODUCTION STORAGE CLEANUP & POSTGRESQL LOG ROTATOR       "
echo "================================================================="
echo "Waktu Mulai: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# 1. Tampilkan Penggunaan Disk SEBELUM Pembersihan
echo ">> [1/9] Status Disk SEBELUM Pembersihan:"
df -h /
echo ""

SPACE_BEFORE=$(df -m / | awk 'NR==2 {print $3}')

# 2. BERSIHKAN LOG DATABASE POSTGRESQL (Paling sering memakan puluhan GB!)
echo ">> [2/9] Memeriksa & Membersihkan Log Database PostgreSQL..."
PG_LOG_DIRS=(
    "/www/server/pgsql/logs"
    "/www/server/pgsql/data/log"
    "/www/server/pgsql/data/pg_log"
    "/var/lib/pgsql/data/log"
    "/var/lib/pgsql/data/pg_log"
    "/var/lib/pgsql/15/data/log"
    "/var/lib/pgsql/16/data/log"
    "/var/lib/pgsql/14/data/log"
    "/var/log/postgresql"
)

FOUND_PG_LOGS=0
for pdir in "${PG_LOG_DIRS[@]}"; do
    if [ -d "$pdir" ]; then
        FOUND_PG_LOGS=1
        PDIR_SZ=$(du -sh "$pdir" 2>/dev/null | awk '{print $1}')
        echo "   -> Ditemukan folder log PostgreSQL: $pdir (Ukuran: $PDIR_SZ)"
        
        # Hapus file log/csv/gz lama yang lebih tua dari 1 hari
        find "$pdir" -type f \( -name "*.log" -o -name "*.csv" -o -name "*.gz" \) -mtime +1 -exec rm -f {} + 2>/dev/null || true
        
        # Truncate log aktif hari ini jika ukurannya > 30MB
        find "$pdir" -type f \( -name "*.log" -o -name "*.csv" \) -size +30M | while read -r act_log; do
            echo "      Memangkas log aktif: $act_log"
            truncate -s 0 "$act_log" 2>/dev/null || true
        done
        
        PDIR_AFTER=$(du -sh "$pdir" 2>/dev/null | awk '{print $1}')
        echo "      Ukuran setelah dibersihkan: $PDIR_AFTER"
    fi
done

if [ "$FOUND_PG_LOGS" -eq 0 ]; then
    echo "   [INFO] Folder standar PostgreSQL log tidak ditemukan di path biasa."
    # Pencarian dinamis jika path custom
    find /www/server/pgsql/ /var/lib/pgsql/ -type f -name "*.log" -size +50M 2>/dev/null | while read -r custom_pg_log; do
        echo "   -> Memangkas custom PostgreSQL log: $custom_pg_log"
        truncate -s 0 "$custom_pg_log" 2>/dev/null || true
    done
fi
echo "   [OK] Log PostgreSQL selesai diproses."

# 3. Bersihkan aaPanel Recycle Bin (Tempat sampah file/backup aaPanel)
echo ">> [3/9] Mengosongkan aaPanel Recycle Bin (/www/Recycle_bin/)..."
if [ -d "/www/Recycle_bin" ]; then
    TRASH_SIZE=$(du -sh /www/Recycle_bin 2>/dev/null | awk '{print $1}')
    echo "   Ukuran sampah aaPanel: $TRASH_SIZE"
    rm -rf /www/Recycle_bin/* 2>/dev/null || true
    echo "   [OK] aaPanel Recycle Bin berhasil dikosongkan."
else
    echo "   [SKIP] Folder /www/Recycle_bin tidak ditemukan."
fi

# 4. Truncate & Bersihkan Nginx Web Access & Error Logs (/www/wwwlogs/)
echo ">> [4/9] Mengoptimalkan Nginx Web Logs (/www/wwwlogs/)..."
if [ -d "/www/wwwlogs" ]; then
    WWWLOGS_SZ=$(du -sh /www/wwwlogs 2>/dev/null | awk '{print $1}')
    echo "   Ukuran /www/wwwlogs: $WWWLOGS_SZ"
    
    # Hapus arsip log lama (*.gz, *.tar.gz, *.1, *.2) yang lebih tua dari 3 hari
    find /www/wwwlogs/ -type f \( -name "*.gz" -o -name "*.tar.gz" -o -name "*.[0-9]" -o -name "*.[0-9][0-9]" \) -mtime +3 -exec rm -f {} + 2>/dev/null || true
    
    # Truncate file log yang ukurannya lebih dari 50MB (sisakan 0 byte agar space langsung kembali)
    find /www/wwwlogs/ -type f -name "*.log" -size +50M | while read -r logfile; do
        LOG_SZ=$(du -sh "$logfile" | awk '{print $1}')
        echo "   -> Memangkas log besar ($LOG_SZ): $logfile"
        truncate -s 0 "$logfile"
    done

    # Beri sinyal re-open log ke Nginx tanpa restart layanan (Zero Downtime)
    if [ -f "/www/server/nginx/logs/nginx.pid" ]; then
        kill -USR1 $(cat /www/server/nginx/logs/nginx.pid) 2>/dev/null || true
    fi
    echo "   [OK] Nginx Web Logs berhasil dipangkas."
fi

# 5. Bersihkan Systemd Journal Logs (Log OS Rocky Linux 8)
echo ">> [5/9] Merapikan Systemd Journal Logs (Batas 100MB / 3 hari)..."
if command -v journalctl &>/dev/null; then
    journalctl --vacuum-time=3d 2>/dev/null || true
    journalctl --vacuum-size=100M 2>/dev/null || true
    echo "   [OK] Systemd Journal berhasil dirapikan."
fi

# 6. Bersihkan Log Aplikasi Laravel (/www/wwwroot/*/storage/logs/)
echo ">> [6/9] Memeriksa & Membersihkan Laravel Application Logs..."
find /www/wwwroot/ -maxdepth 4 -type d -name "logs" | grep "storage/logs" | while read -r laravel_log_dir; do
    echo "   -> Memeriksa direktori: $laravel_log_dir"
    
    # Hapus log harian laravel lama (> 7 hari)
    find "$laravel_log_dir" -type f -name "laravel-*.log" -mtime +7 -exec rm -f {} + 2>/dev/null || true
    
    # Truncate laravel.log utama jika ukurannya > 30MB
    if [ -f "$laravel_log_dir/laravel.log" ]; then
        LARAVEL_SZ=$(du -sh "$laravel_log_dir/laravel.log" | awk '{print $1}')
        FILE_BYTES=$(stat -c%s "$laravel_log_dir/laravel.log" 2>/dev/null || stat -f%z "$laravel_log_dir/laravel.log" 2>/dev/null || echo 0)
        if [ "$FILE_BYTES" -gt 31457280 ]; then
            echo "      Memangkas laravel.log ($LARAVEL_SZ)..."
            tail -n 20000 "$laravel_log_dir/laravel.log" > "$laravel_log_dir/laravel.log.tmp"
            mv "$laravel_log_dir/laravel.log.tmp" "$laravel_log_dir/laravel.log"
            chmod 664 "$laravel_log_dir/laravel.log" 2>/dev/null || true
            chown www:www "$laravel_log_dir/laravel.log" 2>/dev/null || true
        fi
    fi
done
echo "   [OK] Laravel Logs aman dan optimal."

# 7. Bersihkan Backup Otomatis Lama aaPanel di /www/backup/
echo ">> [7/9] Memeriksa Backup Otomatis aaPanel yang Lebih Tua dari 7 Hari..."
if [ -d "/www/backup" ]; then
    BACKUP_SZ=$(du -sh /www/backup 2>/dev/null | awk '{print $1}')
    echo "   Ukuran folder backup: $BACKUP_SZ"
    find /www/backup/database/ /www/backup/site/ -type f \( -name "*.sql" -o -name "*.sql.gz" -o -name "*.zip" -o -name "*.tar.gz" \) -mtime +7 -exec rm -f {} + 2>/dev/null || true
    echo "   [OK] Backup kadaluwarsa (> 7 hari) dirapikan."
fi

# 8. Bersihkan Cache Paket DNF / YUM Package Manager
echo ">> [8/9] Membersihkan Cache RPM Package Manager (DNF/YUM)..."
if command -v dnf &>/dev/null; then
    dnf clean all -q -y 2>/dev/null || true
elif command -v yum &>/dev/null; then
    yum clean all -q -y 2>/dev/null || true
fi
echo "   [OK] Cache DNF/YUM dibersihkan."

# 9. Analisis Top 10 Direktori / File Terbesar (Diagnostik Transparan)
echo ""
echo ">> [9/9] Analisis 10 Direktori Terbesar di /www/ (Sumber Penggunaan Disk):"
du -h --max-depth=2 /www 2>/dev/null | sort -rh | head -n 11
echo ""

# Tampilkan Penggunaan Disk SESUDAH Pembersihan
echo "================================================================="
echo ">> Ringkasan Hasil Pembersihan:"
echo "================================================================="
df -h /
echo ""

SPACE_AFTER=$(df -m / | awk 'NR==2 {print $3}')
SAVED_MB=$((SPACE_BEFORE - SPACE_AFTER))

if [ "$SAVED_MB" -gt 0 ]; then
    echo "🎉 BERHASIL! Ruang storage yang berhasil dilegakan: ${SAVED_MB} MB (~$((SAVED_MB / 1024)) GB)"
else
    echo "ℹ️  Pembersihan selesai."
fi

echo "Waktu Selesai: $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================================================="
exit 0
