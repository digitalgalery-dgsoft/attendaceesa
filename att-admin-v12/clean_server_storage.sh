#!/usr/bin/env bash
# ==============================================================================
# 🧹 ESA GROUPS - PRODUCTION STORAGE CLEANER & SAFE LOG ROTATOR
# Sistem: Rocky Linux 8 / CentOS / AlmaLinux + aaPanel + Laravel + PostgreSQL
# Fungsi: Membersihkan log, cache, dan file sampah tanpa mengganggu jalannya sistem
# ==============================================================================

set -u

echo "================================================================="
echo "   ESA PRODUCTION STORAGE CLEANUP & LOG ROTATION UTILITY         "
echo "================================================================="
echo "Waktu Mulai: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# 1. Tampilkan Penggunaan Disk SEBELUM Pembersihan
echo ">> [1/8] Status Disk SEBELUM Pembersihan:"
df -h /
echo ""

SPACE_BEFORE=$(df -m / | awk 'NR==2 {print $3}')

# 2. Bersihkan aaPanel Recycle Bin (Tempat sampah file/backup aaPanel)
echo ">> [2/8] Mengosongkan aaPanel Recycle Bin (/www/Recycle_bin/)..."
if [ -d "/www/Recycle_bin" ]; then
    TRASH_SIZE=$(du -sh /www/Recycle_bin 2>/dev/null | awk '{print $1}')
    echo "   Ukuran sampah aaPanel: $TRASH_SIZE"
    rm -rf /www/Recycle_bin/* 2>/dev/null || true
    echo "   [OK] aaPanel Recycle Bin berhasil dikosongkan."
else
    echo "   [SKIP] Folder /www/Recycle_bin tidak ditemukan."
fi

# 3. Truncate & Bersihkan Nginx Web Access & Error Logs (/www/wwwlogs/)
# CATATAN TEKNIS: Log yang aktif tidak boleh di-rm karena Nginx masih memegang
# file descriptor-nya (disk space tidak berkurang jika di-rm).
# Gunakan 'truncate -s 0' atau pangkas file > 100MB menjadi 5MB terakhir!
echo ">> [3/8] Mengoptimalkan Nginx Web Logs (/www/wwwlogs/)..."
if [ -d "/www/wwwlogs" ]; then
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

# 4. Bersihkan Systemd Journal Logs (Log OS Rocky Linux 8)
echo ">> [4/8] Merapikan Systemd Journal Logs (Batas 100MB / 3 hari)..."
if command -v journalctl &>/dev/null; then
    journalctl --vacuum-time=3d 2>/dev/null || true
    journalctl --vacuum-size=100M 2>/dev/null || true
    echo "   [OK] Systemd Journal berhasil dirapikan."
fi

# 5. Bersihkan Log Aplikasi Laravel (/www/wwwroot/*/storage/logs/)
echo ">> [5/8] Memeriksa & Membersihkan Laravel Application Logs..."
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
            # Sisakan 20.000 baris terakhir untuk kebutuhan debugging terkini
            tail -n 20000 "$laravel_log_dir/laravel.log" > "$laravel_log_dir/laravel.log.tmp"
            mv "$laravel_log_dir/laravel.log.tmp" "$laravel_log_dir/laravel.log"
            chmod 664 "$laravel_log_dir/laravel.log" 2>/dev/null || true
            chown www:www "$laravel_log_dir/laravel.log" 2>/dev/null || true
        fi
    fi
done
echo "   [OK] Laravel Logs aman dan optimal."

# 6. Bersihkan Cache Blade & Cache Framework Laravel yang Sudah Kedaluwarsa
echo ">> [6/8] Membersihkan Cache Blade View yang Kedaluwarsa..."
find /www/wwwroot/ -maxdepth 4 -type d -path "*/storage/framework/views" | while read -r view_dir; do
    # Hapus view compiled yang tidak diakses lebih dari 5 hari (Laravel akan generate otomatis saat dibuka)
    find "$view_dir" -type f -name "*.php" -atime +5 -exec rm -f {} + 2>/dev/null || true
done
echo "   [OK] Cache View usang dibersihkan."

# 7. Bersihkan Cache Paket DNF / YUM Package Manager
echo ">> [7/8] Membersihkan Cache RPM Package Manager (DNF/YUM)..."
if command -v dnf &>/dev/null; then
    dnf clean all -q -y 2>/dev/null || true
elif command -v yum &>/dev/null; then
    yum clean all -q -y 2>/dev/null || true
fi
echo "   [OK] Cache DNF/YUM dibersihkan."

# 8. Bersihkan File Temporary Sistem (/tmp dan /var/tmp)
echo ">> [8/8] Membersihkan File Temporary Sistem yang Lebih Tua dari 3 Hari..."
find /tmp /var/tmp -maxdepth 2 -type f -atime +3 -not -name ".*" -exec rm -f {} + 2>/dev/null || true
echo "   [OK] Direktori temporary bersih."

# Tampilkan Penggunaan Disk SESUDAH Pembersihan
echo ""
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
    echo "ℹ️  Pembersihan selesai. Storage sistem dalam kondisi optimal."
fi

echo "Waktu Selesai: $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================================================="
exit 0
