#!/usr/bin/env bash
# ==============================================================================
# 🚀 ESA GROUPS - MASTER PRODUCTION AUTO-DEPLOYMENT SCRIPT
# Server Development: appsend.my.id
# Target: 3 Production Cloud Servers (AMK, AKP, ATK)
# ==============================================================================

set -e

# Warna Terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}================================================================${NC}"
echo -e "${CYAN}   🚀 ESA GROUPS - DEPLOY KE 3 SERVER PRODUCTION SIMULTAN       ${NC}"
echo -e "${CYAN}================================================================${NC}"
echo -e "Server Development: ${YELLOW}appsend.my.id${NC}"
echo -e "Waktu Eksekusi:     ${YELLOW}$(date '+%Y-%m-%d %H:%M:%S')${NC}\n"

# Konfigurasi 3 Server Production
# Format: ID|NAME|IP|WEB_PATH|PING_URL
SERVERS=(
    "amk|Server 1 (PT AMK)|38.103.170.235|/www/wwwroot/amk.dgsoft.web.id|https://amk.dgsoft.web.id/api/v1/sync/ping"
    "akp|Server 2 (PT AKP)|38.103.170.223|/www/wwwroot/akp.dgsoft.web.id|https://akp.dgsoft.web.id/api/v1/sync/ping"
    "atk|Server 3 (PT ATK)|38.103.170.219|/www/wwwroot/atk.dgsoft.web.id|https://atk.dgsoft.web.id/api/v1/sync/ping"
)

# 1. Pastikan repo di server dev sudah ter-push ke GitHub
echo -e "${BLUE}[1/3] Memeriksa status Git di Development Server...${NC}"
if git status --porcelain | grep -q .; then
    echo -e "${YELLOW}⚠️  Ada perubahan lokal di server dev yang belum di-commit/push.${NC}"
    read -p "Apakah Anda ingin commit & push otomatis ke GitHub? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git add -A
        git commit -m "Auto-deploy release: $(date '+%Y-%m-%d %H:%M:%S')"
        git push origin main
        echo -e "${GREEN}✅ Perubahan berhasil di-push ke GitHub.${NC}"
    fi
else
    echo -e "${GREEN}✅ Repository Git lokal bersih dan tersinkronisasi.${NC}"
fi

echo -e "\n${BLUE}[2/3] Memulai proses deploy ke 3 server production...${NC}"

SUCCESS_COUNT=0
TOTAL_SERVERS=${#SERVERS[@]}

for item in "${SERVERS[@]}"; do
    IFS="|" read -r S_ID S_NAME S_IP S_PATH S_PING <<< "$item"

    echo -e "\n${CYAN}------------------------------------------------------------${NC}"
    echo -e "${YELLOW}▶ Menghubungi ${S_NAME} (${S_IP})...${NC}"
    echo -e "${CYAN}------------------------------------------------------------${NC}"

    # Perintah yang dijalankan secara remote di server target
    REMOTE_CMD="
        set -e
        # 1. Update kode terbaru dari GitHub
        if [ ! -d /root/att-admin-v12 ]; then
            git clone https://github.com/digitalgalery-dgsoft/attendaceesa.git /root/att-admin-v12
        fi
        cd /root/att-admin-v12
        git fetch origin main
        git reset --hard origin/main

        # 2. Salin kode ke folder website
        \cp -rf /root/att-admin-v12/att-admin-v12/. ${S_PATH}/

        # 3. Setup aset & link storage
        cd ${S_PATH}
        php artisan storage:link 2>/dev/null || true
        php artisan livewire:publish --assets 2>/dev/null || true
        mkdir -p public/livewire
        \cp -rf vendor/livewire/livewire/dist/* public/livewire/ 2>/dev/null || true
        rm -f public/hot

        # 4. Bersihkan seluruh cache
        php artisan optimize:clear >/dev/null 2>&1

        # 5. Reload PHP-FPM
        systemctl reload php-fpm-83 2>/dev/null || /etc/init.d/php-fpm-83 reload 2>/dev/null || true
    "

    if ssh -o BatchMode=yes -o ConnectTimeout=8 -o StrictHostKeyChecking=no "root@${S_IP}" "$REMOTE_CMD" 2>&1; then
        echo -e "${GREEN}✅ Deploy berhasil di ${S_NAME}!${NC}"
        
        # Uji Ping API Kesehatan Server
        HTTP_CODE=$(curl -k -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$S_PING" || echo "000")
        if [ "$HTTP_CODE" == "200" ]; then
            echo -e "${GREEN}   ↳ Health Check Ping OK (HTTP 200)${NC}"
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        else
            echo -e "${YELLOW}   ↳ Deploy selesai, respon health check: HTTP ${HTTP_CODE}${NC}"
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        fi
    else
        echo -e "${RED}❌ Gagal terhubung ke ${S_NAME} (${S_IP}). Pastikan SSH Key sudah terpasang.${NC}"
    fi
done

echo -e "\n${CYAN}================================================================${NC}"
echo -e "${CYAN}   📊 RINGKASAN HASIL DEPLOYMENT PRODUKSI                       ${NC}"
echo -e "${CYAN}================================================================${NC}"
if [ "$SUCCESS_COUNT" -eq "$TOTAL_SERVERS" ]; then
    echo -e "${GREEN}🎉 SEMPURNA! Seluruh ${SUCCESS_COUNT}/${TOTAL_SERVERS} server production berhasil di-deploy!${NC}"
else
    echo -e "${YELLOW}⚠️  Selesai: ${SUCCESS_COUNT}/${TOTAL_SERVERS} server berhasil di-deploy.${NC}"
fi
echo -e "${CYAN}================================================================${NC}\n"
