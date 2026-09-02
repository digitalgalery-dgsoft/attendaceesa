#!/usr/bin/env bash
# ==============================================================================
# 🚀 ESA GROUPS - UNIFIED DEPLOY SCRIPT (DEV & 3 PRODUCTION SERVERS)
# Server Development: appsend.my.id
# Target Production: Server 1 (AMK), Server 2 (AKP), Server 3 (ATK)
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
echo -e "${CYAN}        🚀 ESA GROUPS - MASTER UNIFIED DEPLOYMENT               ${NC}"
echo -e "${CYAN}================================================================${NC}"
echo -e "Waktu Eksekusi: ${YELLOW}$(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "Host Server:    ${YELLOW}$(hostname) ($(curl -s ifconfig.me 2>/dev/null || echo 'local'))${NC}\n"

# Konfigurasi 3 Server Production
# Format: ID|NAME|IP|WEB_PATH|PING_URL
PROD_SERVERS=(
    "amk|Server 1 (PT AMK)|38.103.170.235|/www/wwwroot/amk.dgsoft.web.id|https://amk.dgsoft.web.id/api/v1/sync/ping"
    "akp|Server 2 (PT AKP)|38.103.170.223|/www/wwwroot/akp.dgsoft.web.id|https://akp.dgsoft.web.id/api/v1/sync/ping"
    "atk|Server 3 (PT ATK)|38.103.170.219|/www/wwwroot/atk.dgsoft.web.id|https://atk.dgsoft.web.id/api/v1/sync/ping"
)

# Menentukan Target Deployment
TARGET="${1:-interactive}"

if [ "$TARGET" == "interactive" ]; then
    echo -e "Pilih target deployment:"
    echo -e "  ${GREEN}1)${NC} 🖥️  Deploy ke Server Development (appsend.my.id) saja"
    echo -e "  ${CYAN}2)${NC} 🏢 Deploy ke 3 Server Production (AMK, AKP, ATK)"
    echo -e "  ${YELLOW}3)${NC} 🌐 Deploy ke SEMUANYA (Dev + 3 Server Production) [Rekomendasi]"
    echo -e "  ${BLUE}4)${NC} 🔑 Setup SSH Key Pairing ke 3 Server Production"
    echo -e "  ${RED}5)${NC} ❌ Batal"
    read -p "Masukkan nomor pilihan [1-5] (default: 3): " CHOICE
    CHOICE="${CHOICE:-3}"
elif [ "$TARGET" == "--prod" ] || [ "$TARGET" == "-p" ] || [ "$TARGET" == "2" ]; then
    CHOICE=2
elif [ "$TARGET" == "--all" ] || [ "$TARGET" == "-a" ] || [ "$TARGET" == "3" ]; then
    CHOICE=3
elif [ "$TARGET" == "--dev" ] || [ "$TARGET" == "-d" ] || [ "$TARGET" == "1" ]; then
    CHOICE=1
elif [ "$TARGET" == "--setup" ] || [ "$TARGET" == "4" ]; then
    CHOICE=4
else
    CHOICE=3
fi

# ==============================================================================
# FUNGSI 1: SETUP SSH KEY PAIRING
# ==============================================================================
setup_ssh_pairing() {
    echo -e "\n${BLUE}▶ Menjalankan Setup SSH Key Pairing...${NC}"
    if [ ! -f ~/.ssh/id_rsa.pub ]; then
        echo -e "${YELLOW}Membuat pasangan SSH Key baru di server ini...${NC}"
        mkdir -p ~/.ssh && chmod 700 ~/.ssh
        ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa
        echo -e "${GREEN}✅ SSH Key dibuat.${NC}"
    fi

    for item in "${PROD_SERVERS[@]}"; do
        IFS="|" read -r S_ID S_NAME S_IP S_PATH S_PING <<< "$item"
        echo -e "${CYAN}------------------------------------------------------------${NC}"
        echo -e "${YELLOW}Menghubungkan ke ${S_NAME} (${S_IP})...${NC}"
        ssh-copy-id -o StrictHostKeyChecking=no "root@${S_IP}" || true
    done
    echo -e "${GREEN}🎉 Setup SSH Key selesai!${NC}\n"
}

# ==============================================================================
# FUNGSI 2: DEPLOY LOKAL (DEV SERVER appsend.my.id)
# ==============================================================================
deploy_local_dev() {
    echo -e "\n${BLUE}[DEV] Memperbarui Server Development (appsend.my.id)...${NC}"
    
    # Git commit & push jika ada perubahan
    if git status --porcelain | grep -q .; then
        echo -e "${YELLOW}⚠️  Menyimpan perubahan lokal ke GitHub...${NC}"
        git add -A
        git commit -m "Auto-deploy dev release: $(date '+%Y-%m-%d %H:%M:%S')" || true
        git push origin main || true
        echo -e "${GREEN}✅ Perubahan lokal berhasil di-push ke GitHub.${NC}"
    fi

    # Jalankan optimasi lokal
    php artisan storage:link 2>/dev/null || true
    php artisan livewire:publish --assets 2>/dev/null || true
    mkdir -p public/livewire
    \cp -rf vendor/livewire/livewire/dist/* public/livewire/ 2>/dev/null || true
    rm -f public/hot
    php artisan optimize:clear >/dev/null 2>&1
    systemctl reload php-fpm-83 2>/dev/null || /etc/init.d/php-fpm-83 reload 2>/dev/null || true
    echo -e "${GREEN}✅ Server Development (appsend.my.id) berhasil diperbarui!${NC}"
}

# ==============================================================================
# FUNGSI 3: DEPLOY KE 3 SERVER PRODUCTION (AMK, AKP, ATK)
# ==============================================================================
deploy_three_prod_servers() {
    echo -e "\n${BLUE}[PROD] Memulai proses deploy ke 3 server production...${NC}"
    
    # Pastikan git origin main terupdate
    if git status --porcelain | grep -q .; then
        echo -e "${YELLOW}⚠️  Menyimpan perubahan ke GitHub sebelum deploy production...${NC}"
        git add -A
        git commit -m "Auto-deploy prod release: $(date '+%Y-%m-%d %H:%M:%S')" || true
        git push origin main || true
    fi

    SUCCESS_COUNT=0
    TOTAL_SERVERS=${#PROD_SERVERS[@]}

    for item in "${PROD_SERVERS[@]}"; do
        IFS="|" read -r S_ID S_NAME S_IP S_PATH S_PING <<< "$item"

        echo -e "\n${CYAN}------------------------------------------------------------${NC}"
        echo -e "${YELLOW}▶ Mengupdate ${S_NAME} (${S_IP})...${NC}"
        echo -e "${CYAN}------------------------------------------------------------${NC}"

        REMOTE_CMD="
            set -e
            # 1. Update dari GitHub
            if [ ! -d /root/att-admin-v12 ]; then
                git clone https://github.com/digitalgalery-dgsoft/attendaceesa.git /root/att-admin-v12
            fi
            cd /root/att-admin-v12
            git fetch origin main
            git reset --hard origin/main

            # 2. Salin kode ke webroot
            \cp -rf /root/att-admin-v12/att-admin-v12/. ${S_PATH}/

            # 3. Setup aset & permission
            cd ${S_PATH}
            php artisan storage:link 2>/dev/null || true
            php artisan livewire:publish --assets 2>/dev/null || true
            mkdir -p public/livewire
            \cp -rf vendor/livewire/livewire/dist/* public/livewire/ 2>/dev/null || true
            rm -f public/hot

            # 4. Clear cache & reload PHP
            php artisan optimize:clear >/dev/null 2>&1
            systemctl reload php-fpm-83 2>/dev/null || /etc/init.d/php-fpm-83 reload 2>/dev/null || true
        "

        if ssh -o BatchMode=yes -o ConnectTimeout=8 -o StrictHostKeyChecking=no "root@${S_IP}" "$REMOTE_CMD" 2>&1; then
            echo -e "${GREEN}✅ Deploy berhasil di ${S_NAME}!${NC}"
            
            # Health check ping
            HTTP_CODE=$(curl -k -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$S_PING" || echo "000")
            if [ "$HTTP_CODE" == "200" ]; then
                echo -e "${GREEN}   ↳ Health Check Ping OK (HTTP 200)${NC}"
            else
                echo -e "${YELLOW}   ↳ Selesai (Respon Ping: HTTP ${HTTP_CODE})${NC}"
            fi
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        else
            echo -e "${RED}❌ Gagal terhubung ke ${S_NAME} (${S_IP}). Jalankan pilihan 4 untuk setup SSH Key.${NC}"
        fi
    done

    echo -e "\n${CYAN}================================================================${NC}"
    echo -e "${CYAN}   📊 RINGKASAN HASIL DEPLOYMENT PRODUKSI                       ${NC}"
    echo -e "${CYAN}================================================================${NC}"
    if [ "$SUCCESS_COUNT" -eq "$TOTAL_SERVERS" ]; then
        echo -e "${GREEN}🎉 SEMPURNA! Seluruh ${SUCCESS_COUNT}/${TOTAL_SERVERS} server production berhasil diperbarui!${NC}"
    else
        echo -e "${YELLOW}⚠️  ${SUCCESS_COUNT}/${TOTAL_SERVERS} server berhasil diperbarui.${NC}"
    fi
    echo -e "${CYAN}================================================================${NC}\n"
}

# ==============================================================================
# EKSEKUSI SESUAI PILIHAN
# ==============================================================================
case "$CHOICE" in
    1)
        deploy_local_dev
        ;;
    2)
        deploy_three_prod_servers
        ;;
    3)
        deploy_local_dev
        deploy_three_prod_servers
        ;;
    4)
        setup_ssh_pairing
        ;;
    *)
        echo -e "${YELLOW}Deployment dibatalkan.${NC}"
        exit 0
        ;;
esac
