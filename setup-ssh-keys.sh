#!/usr/bin/env bash
# ==============================================================================
# 🔑 ESA GROUPS - ONE-TIME SSH KEY SETUP HELPER
# Jalankan sekali di server development (appsend.my.id)
# ==============================================================================

set -e

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${CYAN}================================================================${NC}"
echo -e "${CYAN}   🔑 SETUP KUNCI SSH OTOMATIS: APPSEND -> 3 SERVER PRODUCTION   ${NC}"
echo -e "${CYAN}================================================================${NC}"

# 1. Buat SSH key jika belum ada
if [ ! -f ~/.ssh/id_rsa.pub ]; then
    echo -e "${YELLOW}Membuat pasangan SSH Key baru di server development...${NC}"
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa
    echo -e "${GREEN}✅ SSH Key berhasil dibuat!${NC}"
else
    echo -e "${GREEN}✅ SSH Key sudah tersedia di ~/.ssh/id_rsa.pub${NC}"
fi

# Salin juga ke direktori yang bisa diakses user web server (www) untuk deploy via browser
mkdir -p /www/server/deploy_key 2>/dev/null || true
cp -f ~/.ssh/id_rsa* /www/server/deploy_key/ 2>/dev/null || true
chown -R www:www /www/server/deploy_key 2>/dev/null || true
chmod 600 /www/server/deploy_key/id_rsa 2>/dev/null || true
chmod 644 /www/server/deploy_key/id_rsa.pub 2>/dev/null || true

SERVERS=(
    "Server 1 (AMK)|38.103.170.235"
    "Server 2 (AKP)|38.103.170.223"
    "Server 3 (ATK)|38.103.170.224"
)

echo -e "\nSekarang kita akan mendaftarkan public key ke masing-masing server."
echo -e "Anda hanya perlu memasukkan password root server sekali saja untuk tiap server.\n"

for item in "${SERVERS[@]}"; do
    IFS="|" read -r S_NAME S_IP <<< "$item"
    echo -e "${CYAN}------------------------------------------------------------${NC}"
    echo -e "${YELLOW}▶ Menghubungkan ke ${S_NAME} (${S_IP})...${NC}"
    echo -e "${CYAN}------------------------------------------------------------${NC}"
    ssh-copy-id -o StrictHostKeyChecking=no "root@${S_IP}" || true
done

echo -e "\n${GREEN}================================================================${NC}"
echo -e "${GREEN}🎉 SETUP SELESAI!${NC}"
echo -e "Sekarang Anda dapat menjalankan deploy ke 3 server tanpa password:"
echo -e "${YELLOW}bash deploy-all-production.sh${NC}"
echo -e "${GREEN}================================================================${NC}\n"
