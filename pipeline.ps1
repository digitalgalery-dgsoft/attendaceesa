<#
.SYNOPSIS
    Automated CI/CD & Knowledge Graph Pipeline for ESA Groups Attendance & Reporting System
    Workflow:
      1. Update Graphify Knowledge Graph Memory (graph.json, wiki, report)
      2. Git Commit & Push to GitHub main (includes code + fresh memory + skills)
      3. Deploy to Staging (appsend.my.id)
      4. Test & Health Check Staging
      5. Deploy to Production Cluster (Server 1 AMK, Server 2 AKP, Server 3 ATK)
      6. Health Check All 3 Production Nodes
#>

param(
    [string]$CommitMessage = "Update system features and configuration"
)

$ErrorActionPreference = "Stop"

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "   ESA GROUPS - AUTOMATED PIPELINE & GRAPHIFY MEMORY SYNC       " -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "Waktu      : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Yellow
Write-Host "Komit Pesan: $CommitMessage" -ForegroundColor Yellow
Write-Host ""

$TOKEN = "dgsoft_rahasia_123"
$STAGING_URL = "https://appsend.my.id"
$PROD_SERVERS = @(
    @{ Name = "Server 1: PT AMK"; Url = "https://amk.esa-solutions.id/api/v1/sync/ping" },
    @{ Name = "Server 2: PT AKP"; Url = "https://akp.esa-solutions.id/api/v1/sync/ping" },
    @{ Name = "Server 3: PT ATK"; Url = "https://atk.esa-solutions.id/api/v1/sync/ping" }
)

# -----------------------------------------------------------------------------
# STEP 1: RUN GRAPHIFY UPDATE MEMORY (Ensure Graph Memory is Fresh Before Git)
# -----------------------------------------------------------------------------
Write-Host ">> [Step 1/4] Memperbarui Memory Knowledge Graph dengan Graphify..." -ForegroundColor Blue
try {
    python scratch/build_knowledge_graph.py
    Write-Host "[OK] Memory Codebase (graphify-out/graph.json) berhasil disinkronkan!" -ForegroundColor Green
} catch {
    Write-Host "[WARN] Catatan pembaruan graphify memory: $_" -ForegroundColor Yellow
}

# -----------------------------------------------------------------------------
# STEP 2: GIT COMMIT & PUSH TO GITHUB (Code + Graph Memory + Skills)
# -----------------------------------------------------------------------------
Write-Host "`n>> [Step 2/4] Memeriksa & Mengirim Perubahan ke GitHub (Code + Memory)..." -ForegroundColor Blue
git add -A
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "-> Menyimpan perubahan ke Git..." -ForegroundColor Gray
    git commit -m "$CommitMessage"
    Write-Host "-> Mendorong ke GitHub origin/main..." -ForegroundColor Gray
    git push origin main
    Write-Host "[OK] Berhasil di-push ke GitHub." -ForegroundColor Green
} else {
    Write-Host "[INFO] Tidak ada perubahan berkas baru. Melanjutkan pipeline..." -ForegroundColor DarkYellow
}

# -----------------------------------------------------------------------------
# STEP 3: DEPLOY & TEST STAGING SERVER (appsend.my.id)
# -----------------------------------------------------------------------------
Write-Host "`n>> [Step 3/4] Memicu Deploy ke Server Staging (appsend.my.id)..." -ForegroundColor Blue
$deployDevUrl = "$STAGING_URL/deploy.php?token=$TOKEN"

try {
    $deployResponse = Invoke-WebRequest -Uri $deployDevUrl -Method Post -TimeoutSec 180 -UseBasicParsing
    Write-Host "-> Deploy Staging Trigger: HTTP $($deployResponse.StatusCode)" -ForegroundColor Gray
} catch {
    Write-Host "[WARN] Catatan pada trigger deploy staging: $_" -ForegroundColor Yellow
}

Write-Host "-> Pengujian Health Check Server Staging..." -ForegroundColor Blue
Start-Sleep -Seconds 3
$devPingUrl = "$STAGING_URL/api/v1/sync/ping"
try {
    $pingResponse = Invoke-WebRequest -Uri $devPingUrl -Method Get -TimeoutSec 15 -UseBasicParsing
    if ($pingResponse.StatusCode -eq 200) {
        Write-Host "[OK] Server Staging (appsend.my.id) SEHAT (HTTP 200 OK)." -ForegroundColor Green
        Write-Host "     Respon: $($pingResponse.Content)" -ForegroundColor DarkGray
    } else {
        throw "Server Staging merespons status non-200: $($pingResponse.StatusCode)"
    }
} catch {
    Write-Host "[ERROR] Server Staging tidak lolos pengujian: $_" -ForegroundColor Red
    Write-Host "[STOP] Deployment ke server production DIBATALKAN demi keamanan." -ForegroundColor Red
    exit 1
}

# -----------------------------------------------------------------------------
# STEP 4: DEPLOY TO 3 PRODUCTION CLUSTERS
# -----------------------------------------------------------------------------
Write-Host "`n>> [Step 4/4] Mengirim Perubahan ke 3 Server Production (AMK, AKP, ATK)..." -ForegroundColor Blue
$deployProdUrl = "$STAGING_URL/deploy-production.php?token=$TOKEN"

try {
    $prodResponse = Invoke-WebRequest -Uri $deployProdUrl -Method Post -TimeoutSec 240 -UseBasicParsing
    Write-Host "-> Multi-Server Production Deploy Selesai dieksekusi." -ForegroundColor Gray
} catch {
    Write-Host "[WARN] Catatan respon streaming production: $_" -ForegroundColor Yellow
}

Write-Host "`n-> Memverifikasi Health Check seluruh 3 node Production..." -ForegroundColor Blue
Start-Sleep -Seconds 3
$allProdHealthy = $true

foreach ($server in $PROD_SERVERS) {
    try {
        $res = Invoke-WebRequest -Uri $server.Url -Method Get -TimeoutSec 15 -UseBasicParsing
        if ($res.StatusCode -eq 200) {
            Write-Host "   [OK] $($server.Name): HTTP 200 OK" -ForegroundColor Green
        } else {
            Write-Host "   [FAIL] $($server.Name): HTTP $($res.StatusCode)" -ForegroundColor Red
            $allProdHealthy = $false
        }
    } catch {
        Write-Host "   [FAIL] $($server.Name): Gagal dihubungi ($($_))" -ForegroundColor Red
        $allProdHealthy = $false
    }
}

if (-not $allProdHealthy) {
    Write-Host "[WARN] Beberapa server production memerlukan pengecekan manual." -ForegroundColor Yellow
} else {
    Write-Host "[SUCCESS] Seluruh Cluster Server Production (3/3) beroperasi 100% Normal!" -ForegroundColor Green
}

Write-Host "`n================================================================" -ForegroundColor Cyan
Write-Host "  PIPELINE SELESAI: MEMORY -> GITHUB -> STAGING -> PRODUCTION   " -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
