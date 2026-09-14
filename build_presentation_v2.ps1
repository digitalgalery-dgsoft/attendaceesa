$ErrorActionPreference = 'Stop'

# ==============================================================================
# SCRIPT GENERATOR PRESENTASI PPTX RESMI ESA GROUPS (V2 - ENHANCED DESIGN)
# Tema: Resume Penerapan Cyber Security & Kesiapan Publikasi Google Play Store
# Format: Widescreen 16:9 (960 x 540 pt)
# Engine: Microsoft PowerPoint COM Automation
# ==============================================================================

function Get-Rgb([int]$r, [int]$g, [int]$b) {
    return [int]($r + ($g * 256) + ($b * 65536))
}

# Color Palette Constants
$C_NAVY_DEEP   = Get-Rgb 10 25 47      # #0A192F (Executive Navy)
$C_NAVY_CARD   = Get-Rgb 17 34 64      # #112240
$C_NAVY_TEXT   = Get-Rgb 15 23 42      # #0F172A (Slate 900)
$C_BG_LIGHT    = Get-Rgb 248 250 252   # #F8FAFC
$C_WHITE       = Get-Rgb 255 255 255   # #FFFFFF
$C_BORDER      = Get-Rgb 226 232 240   # #E2E8F0
$C_BORDER_ACC  = Get-Rgb 203 213 225   # #CBD5E1
$C_BODY        = Get-Rgb 51 65 85      # #334155 (Slate 700)
$C_BODY_DARK   = Get-Rgb 30 41 59      # #1E293B (Slate 800)
$C_MUTED       = Get-Rgb 100 116 139   # #64748B (Slate 500)

# Accent Colors
$C_BLUE        = Get-Rgb 2 132 199     # #0284C7 (Sky 600)
$C_BLUE_LIGHT  = Get-Rgb 224 242 254   # #E0F2FE (Sky 100)
$C_CYAN        = Get-Rgb 56 189 248    # #38BDF8 (Sky 400)
$C_EMERALD     = Get-Rgb 5 150 105     # #059669 (Emerald 600)
$C_EMERALD_LT  = Get-Rgb 209 250 229   # #D1FAE5 (Emerald 100)
$C_AMBER       = Get-Rgb 217 119 6     # #D97706 (Amber 600)
$C_AMBER_LT    = Get-Rgb 254 243 199   # #FEF3C7 (Amber 100)
$C_PURPLE      = Get-Rgb 124 58 237    # #7C3AED (Violet 600)
$C_PURPLE_LT   = Get-Rgb 237 233 254   # #EDE9FE (Violet 100)
$C_RED         = Get-Rgb 220 38 38     # #DC2626 (Red 600)
$C_RED_LT      = Get-Rgb 254 226 226   # #FEE2E2 (Red 100)

Write-Output "Memulai inisialisasi Microsoft PowerPoint COM..."
$ppt = New-Object -ComObject PowerPoint.Application
$prs = $ppt.Presentations.Add(0)
$prs.PageSetup.SlideWidth  = 960
$prs.PageSetup.SlideHeight = 540

$logoPath = "d:\Project\attendace\Logo_ESA.png"
$totalSlides = 10

# ------------------------------------------------------------------------------
# HELPER FUNCTIONS
# ------------------------------------------------------------------------------

function Add-Header($slide, $category, $title, $subtitle) {
    # 1. Pill Badge
    $badge = $slide.Shapes.AddShape(5, 50, 22, 220, 20)
    $badge.Fill.Solid()
    $badge.Fill.ForeColor.RGB = $C_BLUE_LIGHT
    $badge.Line.Visible = 0
    $btf = $badge.TextFrame
    $btf.MarginLeft = 8; $btf.MarginRight = 8; $btf.MarginTop = 1; $btf.MarginBottom = 1
    $btr = $btf.TextRange
    $btr.Text = $category.ToUpper()
    $btr.Font.Name = "Segoe UI"
    $btr.Font.Size = 8.5
    $btr.Font.Bold = -1
    $btr.Font.Color.RGB = $C_BLUE
    $badge.Adjustments.Item(1) = 0.5

    # 2. Slide Title
    $tbTitle = $slide.Shapes.AddTextbox(1, 50, 45, 750, 28)
    $ttf = $tbTitle.TextFrame
    $ttf.MarginLeft = 0; $ttf.MarginTop = 0; $ttf.MarginRight = 0; $ttf.MarginBottom = 0
    $ttr = $ttf.TextRange
    $ttr.Text = $title
    $ttr.Font.Name = "Segoe UI"
    $ttr.Font.Size = 20
    $ttr.Font.Bold = -1
    $ttr.Font.Color.RGB = $C_NAVY_TEXT

    # 3. Subtitle
    $tbSub = $slide.Shapes.AddTextbox(1, 50, 75, 750, 20)
    $stf = $tbSub.TextFrame
    $stf.MarginLeft = 0; $stf.MarginTop = 0; $stf.MarginRight = 0; $stf.MarginBottom = 0
    $str = $tbSub.TextFrame.TextRange
    $str.Text = $subtitle
    $str.Font.Name = "Segoe UI"
    $str.Font.Size = 10.5
    $str.Font.Color.RGB = $C_MUTED

    # 4. Header Divider Line
    $line = $slide.Shapes.AddLine(50, 100, 910, 100)
    $line.Line.ForeColor.RGB = $C_BORDER
    $line.Line.Weight = 1

    # 5. Top Right Small Logo
    if (Test-Path $logoPath) {
        $logo = $slide.Shapes.AddPicture($logoPath, 0, -1, 825, 22, 85, 45)
    }
}

function Add-Footer($slide, $slideNum) {
    # Divider line
    $line = $slide.Shapes.AddLine(50, 498, 910, 498)
    $line.Line.ForeColor.RGB = $C_BORDER
    $line.Line.Weight = 1

    # Footer Left
    $tbL = $slide.Shapes.AddTextbox(1, 50, 506, 550, 20)
    $tfL = $tbL.TextFrame
    $tfL.MarginLeft = 0; $tfL.MarginTop = 0
    $trL = $tfL.TextRange
    $trL.Text = "ESA Groups Mobile  |  Resume Penerapan Cyber Security & Kesiapan Publikasi Play Store"
    $trL.Font.Name = "Segoe UI"
    $trL.Font.Size = 9
    $trL.Font.Color.RGB = $C_MUTED

    # Footer Right
    $tbR = $slide.Shapes.AddTextbox(1, 750, 506, 160, 20)
    $tfR = $tbR.TextFrame
    $tfR.MarginRight = 0; $tfR.MarginTop = 0
    $trR = $tfR.TextRange
    $trR.Text = "Slide $slideNum of $totalSlides  |  v1.0.153"
    $trR.ParagraphFormat.Alignment = 3
    $trR.Font.Name = "Segoe UI"
    $trR.Font.Size = 9
    $trR.Font.Color.RGB = $C_MUTED
}

function Set-LightBg($slide) {
    $bg = $slide.Shapes.AddShape(1, 0, 0, 960, 540)
    $bg.Fill.Solid()
    $bg.Fill.ForeColor.RGB = $C_BG_LIGHT
    $bg.Line.Visible = 0
    return $bg
}

# ==============================================================================
# SLIDE 1: COVER / TITLE SLIDE
# ==============================================================================
Write-Output "Membuat Slide 1: Cover Slide..."
$s1 = $prs.Slides.Add(1, 12)

$s1Bg = $s1.Shapes.AddShape(1, 0, 0, 960, 540)
$s1Bg.Fill.Solid()
$s1Bg.Fill.ForeColor.RGB = $C_NAVY_DEEP
$s1Bg.Line.Visible = 0

$s1Acc = $s1.Shapes.AddShape(1, 0, 0, 960, 6)
$s1Acc.Fill.Solid()
$s1Acc.Fill.ForeColor.RGB = $C_BLUE
$s1Acc.Line.Visible = 0

if (Test-Path $logoPath) {
    $s1Logo = $s1.Shapes.AddPicture($logoPath, 0, -1, 50, 48, 140, 70)
}

$s1Badge = $s1.Shapes.AddShape(5, 50, 145, 300, 24)
$s1Badge.Fill.Solid()
$s1Badge.Fill.ForeColor.RGB = $C_NAVY_CARD
$s1Badge.Line.Visible = -1
$s1Badge.Line.ForeColor.RGB = $C_BLUE
$s1Badge.Line.Weight = 1
$s1btf = $s1Badge.TextFrame
$s1btf.MarginLeft = 12; $s1btf.MarginTop = 2
$s1btr = $s1btf.TextRange
$s1btr.Text = "LAPORAN KEAMANAN & REGULASI RILIS"
$s1btr.Font.Name = "Segoe UI"
$s1btr.Font.Size = 9.5
$s1btr.Font.Bold = -1
$s1btr.Font.Color.RGB = $C_CYAN
$s1Badge.Adjustments.Item(1) = 0.5

$s1TbT = $s1.Shapes.AddTextbox(1, 50, 180, 860, 90)
$s1TfT = $s1TbT.TextFrame
$s1TfT.WordWrap = -1
$s1TfT.MarginLeft = 0; $s1TfT.MarginTop = 0
$s1TrT = $s1TfT.TextRange
$s1TrT.Text = "Resume Penerapan Cyber Security &`r`nKesiapan Publikasi Google Play Store"
$s1TrT.Font.Name = "Segoe UI"
$s1TrT.Font.Size = 30
$s1TrT.Font.Bold = -1
$s1TrT.Font.Color.RGB = $C_WHITE

$s1TbS = $s1.Shapes.AddTextbox(1, 50, 285, 840, 50)
$s1TfS = $s1TbS.TextFrame
$s1TfS.WordWrap = -1
$s1TfS.MarginLeft = 0; $s1TfS.MarginTop = 0
$s1TrS = $s1TfS.TextRange
$s1TrS.Text = "Audit Hardening Keamanan Web Backend & Mobile Client, Penetapan Digital Keystore Resmi, serta Penyesuaian Kepatuhan Kebijakan Pengembang Google Play untuk Aplikasi ESA Groups Mobile."
$s1TrS.Font.Name = "Segoe UI"
$s1TrS.Font.Size = 13
$s1TrS.Font.Color.RGB = Get-Rgb 203 213 225

$metaW = 273; $metaH = 80; $metaY = 385
$metaData = @(
    @{ Title = "VERSI & ARTEFAK RILIS"; Val = "ESA Mobile v1.0.153 (Build 153)"; Desc = "Format: AAB (94 MB) & APK (117 MB)"; Color = $C_BLUE },
    @{ Title = "STATUS HARDENING"; Val = "Production Hardened & Tested"; Desc = "Full HTTPS, Security Headers, R8 Obfuscation"; Color = $C_EMERALD },
    @{ Title = "TARGET DISTRIBUSI"; Val = "Google Play Store Console"; Desc = "PT Arina Multi Karya (Induk), PT AKP, PT ATK"; Color = $C_PURPLE }
)

for ($i = 0; $i -lt 3; $i++) {
    $mX = 50 + ($i * ($metaW + 20))
    $mCard = $s1.Shapes.AddShape(5, $mX, $metaY, $metaW, $metaH)
    $mCard.Fill.Solid()
    $mCard.Fill.ForeColor.RGB = $C_NAVY_CARD
    $mCard.Line.Visible = -1
    $mCard.Line.ForeColor.RGB = Get-Rgb 30 58 95
    $mCard.Line.Weight = 1
    
    $mLine = $s1.Shapes.AddShape(1, $mX, $metaY, 4, $metaH)
    $mLine.Fill.Solid()
    $mLine.Fill.ForeColor.RGB = $metaData[$i].Color
    $mLine.Line.Visible = 0

    $mtb = $s1.Shapes.AddTextbox(1, $mX + 14, $metaY + 8, $metaW - 20, $metaH - 16)
    $mtf = $mtb.TextFrame
    $mtf.MarginLeft = 0; $mtf.MarginTop = 0; $mtf.MarginRight = 0; $mtf.MarginBottom = 0
    $mtr = $mtf.TextRange
    $mtr.Text = "$($metaData[$i].Title)`r`n$($metaData[$i].Val)`r`n$($metaData[$i].Desc)"
    
    $mtr.Paragraphs(1).Font.Name = "Segoe UI"; $mtr.Paragraphs(1).Font.Size = 8; $mtr.Paragraphs(1).Font.Bold = -1; $mtr.Paragraphs(1).Font.Color.RGB = $C_CYAN
    $mtr.Paragraphs(2).Font.Name = "Segoe UI"; $mtr.Paragraphs(2).Font.Size = 11.5; $mtr.Paragraphs(2).Font.Bold = -1; $mtr.Paragraphs(2).Font.Color.RGB = $C_WHITE
    $mtr.Paragraphs(3).Font.Name = "Segoe UI"; $mtr.Paragraphs(3).Font.Size = 8.5; $mtr.Paragraphs(3).Font.Color.RGB = Get-Rgb 148 163 184
}

$s1Foot = $s1.Shapes.AddTextbox(1, 50, 498, 860, 25)
$s1fTr = $s1Foot.TextFrame.TextRange
$s1fTr.Text = "ESA Groups Security Taskforce  |  Dokumentasi Teknis Rilis Produksi  |  September 2026"
$s1fTr.Font.Name = "Segoe UI"
$s1fTr.Font.Size = 9.5
$s1fTr.Font.Color.RGB = Get-Rgb 100 116 139


# ==============================================================================
# SLIDE 2: EXECUTIVE SUMMARY
# ==============================================================================
Write-Output "Membuat Slide 2: Executive Summary..."
$s2 = $prs.Slides.Add(2, 12)
Set-LightBg $s2 | Out-Null
Add-Header $s2 "RINGKASAN EKSEKUTIF" "Status Kesiapan Keamanan & Kepatuhan Rilis Aplikasi ESA Mobile" "Konsolidasi menyeluruh penguatan keamanan sistem backend, stabilitas digital signature, dan regulasi Google Play Store."
Add-Footer $s2 2

# 4 Stat / Highlight Metric Cards across top
$metW = 200; $metH = 70; $metY = 112
$statItems = @(
    @{ Val = "100% SECURE"; Lbl = "Backend & Web Panel"; Sub = "Zero Backdoor & Full Headers"; Col = $C_BLUE },
    @{ Val = "OFFICIAL KEY"; Lbl = "Digital Keystore"; Sub = "PKCS12 RSA 2048-bit (s/d 2054)"; Col = $C_EMERALD },
    @{ Val = "COMPLIANT"; Lbl = "Google Play Policy"; Sub = "Privacy Policy & In-App Disclosure"; Col = $C_PURPLE },
    @{ Val = "OPERASIONAL"; Lbl = "Geofencing & Tracking"; Sub = "100% Aktif via Foreground Svc"; Col = $C_AMBER }
)

for ($i = 0; $i -lt 4; $i++) {
    $sX = 50 + ($i * ($metW + 20))
    $sCard = $s2.Shapes.AddShape(5, $sX, $metY, $metW, $metH)
    $sCard.Fill.Solid(); $sCard.Fill.ForeColor.RGB = $C_WHITE
    $sCard.Line.Visible = -1; $sCard.Line.ForeColor.RGB = $C_BORDER; $sCard.Line.Weight = 1
    
    $sTopLine = $s2.Shapes.AddShape(1, $sX, $metY, $metW, 4)
    $sTopLine.Fill.Solid(); $sTopLine.Fill.ForeColor.RGB = $statItems[$i].Col; $sTopLine.Line.Visible = 0

    $stb = $s2.Shapes.AddTextbox(1, $sX + 10, $metY + 8, $metW - 20, $metH - 12)
    $stf = $stb.TextFrame
    $stf.MarginLeft = 0; $stf.MarginTop = 0; $stf.MarginRight = 0; $stf.MarginBottom = 0
    $str = $stf.TextRange
    $str.Text = "$($statItems[$i].Val)`r`n$($statItems[$i].Lbl)`r`n$($statItems[$i].Sub)"
    
    $str.Paragraphs(1).Font.Name = "Segoe UI"; $str.Paragraphs(1).Font.Size = 13.5; $str.Paragraphs(1).Font.Bold = -1; $str.Paragraphs(1).Font.Color.RGB = $statItems[$i].Col
    $str.Paragraphs(2).Font.Name = "Segoe UI"; $str.Paragraphs(2).Font.Size = 9.5; $str.Paragraphs(2).Font.Bold = -1; $str.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT
    $str.Paragraphs(3).Font.Name = "Segoe UI"; $str.Paragraphs(3).Font.Size = 8; $str.Paragraphs(3).Font.Color.RGB = $C_MUTED
}

# 2 Large Columns below
$cW = 418; $cH = 285; $cY = 196

# Left Card: Transformasi Keamanan
$c1 = $s2.Shapes.AddShape(5, 50, $cY, $cW, $cH)
$c1.Fill.Solid(); $c1.Fill.ForeColor.RGB = $C_WHITE
$c1.Line.Visible = -1; $c1.Line.ForeColor.RGB = $C_BORDER; $c1.Line.Weight = 1

$c1Bar = $s2.Shapes.AddShape(1, 50, $cY, $cW, 4)
$c1Bar.Fill.Solid(); $c1Bar.Fill.ForeColor.RGB = $C_BLUE; $c1Bar.Line.Visible = 0

# Left Card Header
$c1Hdr = $s2.Shapes.AddTextbox(1, 68, $cY + 10, $cW - 36, 26)
$c1Hdr.TextFrame.MarginLeft = 0; $c1Hdr.TextFrame.MarginTop = 0
$c1HdrTr = $c1Hdr.TextFrame.TextRange
$c1HdrTr.Text = "Transformasi Penguatan Cyber Security"
$c1HdrTr.Font.Name = "Segoe UI"; $c1HdrTr.Font.Size = 13; $c1HdrTr.Font.Bold = -1; $c1HdrTr.Font.Color.RGB = $C_BLUE

# Left Card Body
$c1Tb = $s2.Shapes.AddTextbox(1, 68, $cY + 38, $cW - 36, $cH - 48)
$c1Tf = $c1Tb.TextFrame; $c1Tf.WordWrap = -1; $c1Tf.MarginLeft = 0; $c1Tf.MarginTop = 0
$c1Tr = $c1Tf.TextRange
$c1Tr.Text = @'
[1] Penutupan Seluruh Celah Bypass:
Menghapus total route debug, login bypass, serta isolasi ketat hak akses Filament Web Panel dan otorisasi API Sanctum.

[2] Pembersihan Direktori Publik:
Mengeliminasi seluruh skrip diagnostik/investigasi dari folder public/ serta mengunci file sensitif (.env, .git, storage) via .htaccess.

[3] Security Headers Middleware:
Penerapan HSTS, X-Frame-Options (SAMEORIGIN), dan X-Content-Type-Options (nosniff) untuk menangkal serangan Clickjacking & MIME exploits.

[4] Hardening Mobile App:
Enforce komunikasi full HTTPS (usesCleartextTraffic=false) dan R8/Proguard code obfuscation guna mencegah dekompilasi aplikasi.
'@
$c1Tr.Font.Name = "Segoe UI"; $c1Tr.Font.Size = 9; $c1Tr.Font.Color.RGB = $C_BODY


# Right Card: Kepatuhan Regulasi & Stabilitas Deployment
$c2 = $s2.Shapes.AddShape(5, 492, $cY, $cW, $cH)
$c2.Fill.Solid(); $c2.Fill.ForeColor.RGB = $C_WHITE
$c2.Line.Visible = -1; $c2.Line.ForeColor.RGB = $C_BORDER; $c2.Line.Weight = 1

$c2Bar = $s2.Shapes.AddShape(1, 492, $cY, $cW, 4)
$c2Bar.Fill.Solid(); $c2Bar.Fill.ForeColor.RGB = $C_EMERALD; $c2Bar.Line.Visible = 0

# Right Card Header
$c2Hdr = $s2.Shapes.AddTextbox(1, 510, $cY + 10, $cW - 36, 26)
$c2Hdr.TextFrame.MarginLeft = 0; $c2Hdr.TextFrame.MarginTop = 0
$c2HdrTr = $c2Hdr.TextFrame.TextRange
$c2HdrTr.Text = "Kepatuhan Google Play & Stabilitas Rilis"
$c2HdrTr.Font.Name = "Segoe UI"; $c2HdrTr.Font.Size = 13; $c2HdrTr.Font.Bold = -1; $c2HdrTr.Font.Color.RGB = $C_EMERALD

# Right Card Body
$c2Tb = $s2.Shapes.AddTextbox(1, 510, $cY + 38, $cW - 36, $cH - 48)
$c2Tf = $c2Tb.TextFrame; $c2Tf.WordWrap = -1; $c2Tf.MarginLeft = 0; $c2Tf.MarginTop = 0
$c2Tr = $c2Tf.TextRange
$c2Tr.Text = @'
[1] Eliminasi Bentrok Paket (Signature Conflict):
Penerapan Keystore Produksi upload-keystore.jks (RSA 2048-bit) permanen menghilangkan error INSTALL_FAILED_UPDATE_INCOMPATIBLE.

[2] Kebijakan Play Store 100% Terpenuhi:
Menonaktifkan self-updating APK ilegal, menghapus izin background location terlarang, dan menerapkan Prominent In-App Disclosure.

[3] Privacy Policy Resmi Aktif:
Tersedia dokumen kebijakan privasi lengkap di domain utama: https://esa-solutions.id/privacy-policy (Status HTTP 200 OK).

[4] Sinkronisasi 3 Server Produksi:
Seluruh pembaruan telah ter-deploy ke AMK, AKP, dan ATK dengan Health Check sukses dan auto-reload service PHP-FPM.
'@
$c2Tr.Font.Name = "Segoe UI"; $c2Tr.Font.Size = 9; $c2Tr.Font.Color.RGB = $C_BODY


# ==============================================================================
# SLIDE 3: PENERAPAN CYBER SECURITY - BACKEND & WEB PANEL
# ==============================================================================
Write-Output "Membuat Slide 3: Backend & Web Security..."
$s3 = $prs.Slides.Add(3, 12)
Set-LightBg $s3 | Out-Null
Add-Header $s3 "CYBER SECURITY | BACKEND & WEB" "Penguatan Lapisan Keamanan Server & API Laravel Filament" "Mitigasi kerentanan informasi, perlindungan endpoint API, dan isolasi aset sistem dari akses tidak sah."
Add-Footer $s3 3

$cardW3 = 273; $cardH3 = 370; $cardY3 = 112

$secBackendData = @(
    @{
        Col = $C_BLUE; Tag = "PENUTUPAN CELAH AKSES"; Title = "Penutupan Backdoor & Debug Routes"
        Badge = "STATUS: TERTUTUP (SECURE)"; BadgeCol = $C_BLUE_LIGHT; BadgeTxt = $C_BLUE
        Body = @'
- Penghapusan Route Testing:
  Seluruh route dev seperti /debug-login, /dev-bypass, dan bypass otentikasi telah dihapus sepenuhnya dari kode sumber.

- Sanctum Bearer Token Auth:
  Semua endpoint API dilindungi token otentikasi Sanctum dengan masa berlaku terkelola dan verifikasi ketat.

- Filament RBAC Hardening:
  Hak akses web admin panel dibatasi hanya untuk user resmi dengan otorisasi role terverifikasi.
'@
    },
    @{
        Col = $C_PURPLE; Tag = "ISOLASI ASSET SENSITIF"; Title = "Pembersihan & Proteksi File Publik"
        Badge = "STATUS: TERISOLASI"; BadgeCol = $C_PURPLE_LT; BadgeTxt = $C_PURPLE
        Body = @'
- Karantina Skrip Diagnostik:
  Menghapus skrip investigasi database dan skrip ad-hoc dari folder public/ guna mencegah kebocoran informasi.

- Rule Blokir Web Server:
  Memperketat konfigurasi .htaccess & Nginx untuk memblokir akses ke file .env, repositori .git, dan folder logs.

- Sanitasi Input & Parameter:
  Memastikan query database menggunakan binding prepared statement untuk mencegah serangan SQL Injection.
'@
    },
    @{
        Col = $C_EMERALD; Tag = "PROTEKSI PROTOKOL"; Title = "Security Headers Middleware"
        Badge = "STATUS: A+ SECURITY RATING"; BadgeCol = $C_EMERALD_LT; BadgeTxt = $C_EMERALD
        Body = @'
- X-Frame-Options: SAMEORIGIN
  Mencegah serangan Clickjacking dan pencegahan embedding pada iframe situs pihak ketiga.

- X-Content-Type-Options: nosniff
  Menolak browser mengeksekusi payload berkedok tipe file MIME yang dimanipulasi.

- Strict-Transport-Security (HSTS)
  Memaksa seluruh traffic berkomunikasi via koneksi SSL/TLS terenkripsi penuh.
'@
    }
)

for ($i = 0; $i -lt 3; $i++) {
    $item = $secBackendData[$i]
    $cX = 50 + ($i * ($cardW3 + 20))
    $c = $s3.Shapes.AddShape(5, $cX, $cardY3, $cardW3, $cardH3)
    $c.Fill.Solid(); $c.Fill.ForeColor.RGB = $C_WHITE
    $c.Line.Visible = -1; $c.Line.ForeColor.RGB = $C_BORDER; $c.Line.Weight = 1
    
    $acc = $s3.Shapes.AddShape(1, $cX, $cardY3, $cardW3, 5)
    $acc.Fill.Solid(); $acc.Fill.ForeColor.RGB = $item.Col; $acc.Line.Visible = 0

    # Header Box
    $tbHdr = $s3.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 12, $cardW3 - 28, 48)
    $tfHdr = $tbHdr.TextFrame; $tfHdr.WordWrap = -1; $tfHdr.MarginLeft = 0; $tfHdr.MarginTop = 0
    $trHdr = $tfHdr.TextRange
    $trHdr.Text = "$($item.Tag)`r`n$($item.Title)"
    $trHdr.Paragraphs(1).Font.Name = "Segoe UI"; $trHdr.Paragraphs(1).Font.Size = 8; $trHdr.Paragraphs(1).Font.Bold = -1; $trHdr.Paragraphs(1).Font.Color.RGB = $item.Col
    $trHdr.Paragraphs(2).Font.Name = "Segoe UI"; $trHdr.Paragraphs(2).Font.Size = 12; $trHdr.Paragraphs(2).Font.Bold = -1; $trHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

    # Body Box
    $tbBody = $s3.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 64, $cardW3 - 28, 255)
    $tfBody = $tbBody.TextFrame; $tfBody.WordWrap = -1; $tfBody.MarginLeft = 0; $tfBody.MarginTop = 0
    $trBody = $tfBody.TextRange
    $trBody.Text = $item.Body
    $trBody.Font.Name = "Segoe UI"; $trBody.Font.Size = 9; $trBody.Font.Color.RGB = $C_BODY

    # Status Pill Box at bottom
    $pill = $s3.Shapes.AddShape(5, $cX + 14, $cardY3 + 332, $cardW3 - 28, 24)
    $pill.Fill.Solid(); $pill.Fill.ForeColor.RGB = $item.BadgeCol
    $pill.Line.Visible = 0
    $pillTr = $pill.TextFrame.TextRange
    $pillTr.Text = $item.Badge
    $pillTr.Font.Name = "Segoe UI"; $pillTr.Font.Size = 8.5; $pillTr.Font.Bold = -1; $pillTr.Font.Color.RGB = $item.BadgeTxt
    $pillTr.ParagraphFormat.Alignment = 2 # Center
    $pill.Adjustments.Item(1) = 0.5
}


# ==============================================================================
# SLIDE 4: PENERAPAN CYBER SECURITY - APLIKASI MOBILE (FLUTTER)
# ==============================================================================
Write-Output "Membuat Slide 4: Mobile App Security..."
$s4 = $prs.Slides.Add(4, 12)
Set-LightBg $s4 | Out-Null
Add-Header $s4 "CYBER SECURITY | MOBILE CLIENT" "Proteksi Client-Side, Enkripsi Jaringan, & Anti-Reverse Engineering" "Hardening aplikasi Flutter Android untuk melindungi kode sumber, kredensial, dan integritas data transaksi absensi."
Add-Footer $s4 4

$secMobileData = @(
    @{
        Col = $C_BLUE; Tag = "ENKRIPSI TRAFFIC DATA"; Title = "Full HTTPS Enforced (Anti-MitM)"
        Badge = "STATUS: CLEAR TEXT BLOCKED"; BadgeCol = $C_BLUE_LIGHT; BadgeTxt = $C_BLUE
        Body = @'
- android:usesCleartextTraffic='false'
  Ditetapkan di AndroidManifest.xml untuk memblokir seluruh koneksi HTTP tanpa enkripsi.

- Proteksi di Jaringan Terbuka:
  Mencegah serangan Man-in-the-Middle (MitM) dan sniffing data koordinat GPS, foto selfie, serta password saat menggunakan Wi-Fi publik.

- Standar TLS 1.3 Transport:
  Menjamin seluruh komunikasi payload transaksi absensi terenkripsi end-to-end ke server ESA.
'@
    },
    @{
        Col = $C_AMBER; Tag = "ANTI-DEKOMPILASI & CRACKING"; Title = "Code Obfuscation & Shrinking (R8)"
        Badge = "STATUS: R8 OBFUSCATION ON"; BadgeCol = $C_AMBER_LT; BadgeTxt = $C_AMBER
        Body = @'
- isMinifyEnabled & isShrinkResources:
  Diaktifkan penuh pada konfigurasi Gradle build release aplikasi.

- Pengacakan Logika Kode (R8/Proguard):
  Nama kelas, method, dan variabel diacak sehingga source code tidak dapat dibaca kembali melalui tools decompile seperti Jadx atau APKTool.

- Proteksi Algoritma Absensi:
  Logika validasi jarak radius GPS dan parsing token aman dari manipulasi pihak luar.
'@
    },
    @{
        Col = $C_EMERALD; Tag = "MANAJEMEN KREDENSIAL"; Title = "Session Security & Device Binding"
        Badge = "STATUS: KEYSTORE ENCRYPTED"; BadgeCol = $C_EMERALD_LT; BadgeTxt = $C_EMERALD
        Body = @'
- Encrypted Secure Storage:
  Penyimpanan token otentikasi bearer menggunakan Android Keystore hardware-backed terenkripsi.

- Validasi Device ID Unik:
  Memastikan akun karyawan terikat secara valid dengan perangkat resmi, mencegah penggandaan akun antar ponsel.

- Sesi Otomatis Expire:
  Mewajibkan re-autentikasi berkala saat sesi login terdeteksi tidak aktif atau terjadi reset kredensial.
'@
    }
)

for ($i = 0; $i -lt 3; $i++) {
    $item = $secMobileData[$i]
    $cX = 50 + ($i * ($cardW3 + 20))
    $c = $s4.Shapes.AddShape(5, $cX, $cardY3, $cardW3, $cardH3)
    $c.Fill.Solid(); $c.Fill.ForeColor.RGB = $C_WHITE
    $c.Line.Visible = -1; $c.Line.ForeColor.RGB = $C_BORDER; $c.Line.Weight = 1
    
    $acc = $s4.Shapes.AddShape(1, $cX, $cardY3, $cardW3, 5)
    $acc.Fill.Solid(); $acc.Fill.ForeColor.RGB = $item.Col; $acc.Line.Visible = 0

    $tbHdr = $s4.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 12, $cardW3 - 28, 48)
    $tfHdr = $tbHdr.TextFrame; $tfHdr.WordWrap = -1; $tfHdr.MarginLeft = 0; $tfHdr.MarginTop = 0
    $trHdr = $tfHdr.TextRange
    $trHdr.Text = "$($item.Tag)`r`n$($item.Title)"
    $trHdr.Paragraphs(1).Font.Name = "Segoe UI"; $trHdr.Paragraphs(1).Font.Size = 8; $trHdr.Paragraphs(1).Font.Bold = -1; $trHdr.Paragraphs(1).Font.Color.RGB = $item.Col
    $trHdr.Paragraphs(2).Font.Name = "Segoe UI"; $trHdr.Paragraphs(2).Font.Size = 12; $trHdr.Paragraphs(2).Font.Bold = -1; $trHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

    $tbBody = $s4.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 64, $cardW3 - 28, 255)
    $tfBody = $tbBody.TextFrame; $tfBody.WordWrap = -1; $tfBody.MarginLeft = 0; $tfBody.MarginTop = 0
    $trBody = $tfBody.TextRange
    $trBody.Text = $item.Body
    $trBody.Font.Name = "Segoe UI"; $trBody.Font.Size = 9; $trBody.Font.Color.RGB = $C_BODY

    $pill = $s4.Shapes.AddShape(5, $cX + 14, $cardY3 + 332, $cardW3 - 28, 24)
    $pill.Fill.Solid(); $pill.Fill.ForeColor.RGB = $item.BadgeCol
    $pill.Line.Visible = 0
    $pillTr = $pill.TextFrame.TextRange
    $pillTr.Text = $item.Badge
    $pillTr.Font.Name = "Segoe UI"; $pillTr.Font.Size = 8.5; $pillTr.Font.Bold = -1; $pillTr.Font.Color.RGB = $item.BadgeTxt
    $pillTr.ParagraphFormat.Alignment = 2
    $pill.Adjustments.Item(1) = 0.5
}


# ==============================================================================
# SLIDE 5: SOLUSI BENTROK PAKET & DIGITAL KEYSTORE PRODUKSI
# ==============================================================================
Write-Output "Membuat Slide 5: Digital Signature & Keystore..."
$s5 = $prs.Slides.Add(5, 12)
Set-LightBg $s5 | Out-Null
Add-Header $s5 "DIGITAL SIGNATURE & KEYSTORE" "Solusi Masalah Bentrok Paket & Penetapan Keystore Resmi" "Resolusi tuntas error INSTALL_FAILED_UPDATE_INCOMPATIBLE serta standarisasi tanda tangan digital jangka panjang."
Add-Footer $s5 5

$cW5 = 418; $cH5 = 370; $cY5 = 112

# Left Card: Root Cause Analysis
$c5L = $s5.Shapes.AddShape(5, 50, $cY5, $cW5, $cH5)
$c5L.Fill.Solid(); $c5L.Fill.ForeColor.RGB = $C_WHITE
$c5L.Line.Visible = -1; $c5L.Line.ForeColor.RGB = $C_BORDER; $c5L.Line.Weight = 1

$c5LBar = $s5.Shapes.AddShape(1, 50, $cY5, $cW5, 5)
$c5LBar.Fill.Solid(); $c5LBar.Fill.ForeColor.RGB = $C_RED; $c5LBar.Line.Visible = 0

$tb5LHdr = $s5.Shapes.AddTextbox(1, 68, $cY5 + 12, $cW5 - 36, 46)
$tb5LHdr.TextFrame.MarginLeft = 0; $tb5LHdr.TextFrame.MarginTop = 0
$tr5LHdr = $tb5LHdr.TextFrame.TextRange
$tr5LHdr.Text = "AKAR MASALAH (ROOT CAUSE ANALYSIS)`r`nMengapa Terjadi Error Bentrok Paket?"
$tr5LHdr.Paragraphs(1).Font.Name = "Segoe UI"; $tr5LHdr.Paragraphs(1).Font.Size = 8.5; $tr5LHdr.Paragraphs(1).Font.Bold = -1; $tr5LHdr.Paragraphs(1).Font.Color.RGB = $C_RED
$tr5LHdr.Paragraphs(2).Font.Name = "Segoe UI"; $tr5LHdr.Paragraphs(2).Font.Size = 13; $tr5LHdr.Paragraphs(2).Font.Bold = -1; $tr5LHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

$tb5LBody = $s5.Shapes.AddTextbox(1, 68, $cY5 + 62, $cW5 - 36, 245)
$tb5LBody.TextFrame.WordWrap = -1; $tb5LBody.TextFrame.MarginLeft = 0; $tb5LBody.TextFrame.MarginTop = 0
$tr5LBody = $tb5LBody.TextFrame.TextRange
$tr5LBody.Text = @'
- Gejala Kegagalan Instalasi:
  Saat user mencoba update aplikasi di atas versi lama, Android menampilkan pesan error: 'Aplikasi bentrok dengan paket sebelumnya' (INSTALL_FAILED_UPDATE_INCOMPATIBLE).

- Penyebab Teknis (Signature Mismatch):
  1. APK sebelumnya di-build menggunakan Debug Keystore bawaan laptop developer.
  2. APK pembaruan ditandatangani menggunakan sertifikat yang berbeda.
  3. Sistem Keamanan Android mewajibkan setiap aplikasi dengan Package Name sama (com.attendance.att_mobile) memiliki sertifikat digital identik.

- Dampak Jika Dibiarkan:
  Pengguna harus uninstall aplikasi lama secara manual sehingga data offline lokal terhapus, serta aplikasi berisiko ditolak update di Google Play Store.
'@
$tr5LBody.Font.Name = "Segoe UI"; $tr5LBody.Font.Size = 9.1; $tr5LBody.Font.Color.RGB = $C_BODY

# Left Card Warning Box
$wBox = $s5.Shapes.AddShape(5, 68, $cY5 + 312, $cW5 - 36, 42)
$wBox.Fill.Solid(); $wBox.Fill.ForeColor.RGB = $C_RED_LT
$wBox.Line.Visible = -1; $wBox.Line.ForeColor.RGB = Get-Rgb 252 165 165; $wBox.Line.Weight = 1
$wTr = $wBox.TextFrame.TextRange
$wTr.Text = "Peringatan: Debug Key dilarang keras digunakan untuk rilis publik karena signature tidak permanen dan rentan penolakan rilis."
$wTr.Font.Name = "Segoe UI"; $wTr.Font.Size = 8.5; $wTr.Font.Color.RGB = $C_RED; $wTr.Font.Bold = -1
$wBox.Adjustments.Item(1) = 0.2


# Right Card: Solution & Official Keystore
$c5R = $s5.Shapes.AddShape(5, 492, $cY5, $cW5, $cH5)
$c5R.Fill.Solid(); $c5R.Fill.ForeColor.RGB = $C_WHITE
$c5R.Line.Visible = -1; $c5R.Line.ForeColor.RGB = $C_BORDER; $c5R.Line.Weight = 1

$c5RBar = $s5.Shapes.AddShape(1, 492, $cY5, $cW5, 5)
$c5RBar.Fill.Solid(); $c5RBar.Fill.ForeColor.RGB = $C_EMERALD; $c5RBar.Line.Visible = 0

$tb5RHdr = $s5.Shapes.AddTextbox(1, 510, $cY5 + 12, $cW5 - 36, 46)
$tb5RHdr.TextFrame.MarginLeft = 0; $tb5RHdr.TextFrame.MarginTop = 0
$tr5RHdr = $tb5RHdr.TextFrame.TextRange
$tr5RHdr.Text = "SOLUSI RESMI & STANDARISASI KEYSTORE`r`nImplementasi upload-keystore.jks Permanen"
$tr5RHdr.Paragraphs(1).Font.Name = "Segoe UI"; $tr5RHdr.Paragraphs(1).Font.Size = 8.5; $tr5RHdr.Paragraphs(1).Font.Bold = -1; $tr5RHdr.Paragraphs(1).Font.Color.RGB = $C_EMERALD
$tr5RHdr.Paragraphs(2).Font.Name = "Segoe UI"; $tr5RHdr.Paragraphs(2).Font.Size = 13; $tr5RHdr.Paragraphs(2).Font.Bold = -1; $tr5RHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

$tb5RBody = $s5.Shapes.AddTextbox(1, 510, $cY5 + 62, $cW5 - 36, 245)
$tb5RBody.TextFrame.WordWrap = -1; $tb5RBody.TextFrame.MarginLeft = 0; $tb5RBody.TextFrame.MarginTop = 0
$tr5RBody = $tb5RBody.TextFrame.TextRange
$tr5RBody.Text = @'
- Pembuatan Keystore Produksi Resmi:
  Membuat berkas upload-keystore.jks standar industri PKCS12 dengan algoritma RSA 2048-bit dan validitas 10.000 hari (aktif s/d tahun 2054).

- Konfigurasi Terintegrasi Otomatis:
  Kredensial tersimpan aman dalam key.properties dan terhubung ke signingConfigs.release pada android/app/build.gradle.kts.

- Identitas Sertifikat Resmi ESA:
  Alias: upload  |  Organisasi: PT Arina Multi Karya (Induk ESA Groups)
  Fingerprint SHA-1 & SHA-256 tersimpan untuk Google Play App Signing.

- Hasil & Keuntungan Jangka Panjang:
  Seluruh update mendatang terjamin 100% kompatibel dan dapat di-update secara mulus tanpa bentrok tanda tangan di semua perangkat.
'@
$tr5RBody.Font.Name = "Segoe UI"; $tr5RBody.Font.Size = 9.1; $tr5RBody.Font.Color.RGB = $C_BODY

# Right Card Success Box
$sBox = $s5.Shapes.AddShape(5, 510, $cY5 + 312, $cW5 - 36, 42)
$sBox.Fill.Solid(); $sBox.Fill.ForeColor.RGB = $C_EMERALD_LT
$sBox.Line.Visible = -1; $sBox.Line.ForeColor.RGB = Get-Rgb 110 231 183; $sBox.Line.Weight = 1
$sTr = $sBox.TextFrame.TextRange
$sTr.Text = "Jaminan: Masa berlaku s/d 2054 (10.000 hari). Bebas bentrok paket untuk rilis publik Google Play Store."
$sTr.Font.Name = "Segoe UI"; $sTr.Font.Size = 8.5; $sTr.Font.Color.RGB = $C_EMERALD; $sTr.Font.Bold = -1
$sBox.Adjustments.Item(1) = 0.2


# ==============================================================================
# SLIDE 6: PEMENUHAN KEBIJAKAN GOOGLE PLAY STORE (3 SYARAT UTAMA)
# ==============================================================================
Write-Output "Membuat Slide 6: Google Play Store Policy..."
$s6 = $prs.Slides.Add(6, 12)
Set-LightBg $s6 | Out-Null
Add-Header $s6 "GOOGLE PLAY STORE COMPLIANCE" "Tiga Penyesuaian Kunci Terhadap Regulasi Google Play Store" "Langkah mitigasi pelanggaran kebijakan pengembang untuk menjamin kelulusan review Google Play Console."
Add-Footer $s6 6

$secPlayData = @(
    @{
        Col = $C_BLUE; Tag = "POIN 1: ATURAN UPDATE"; Title = "Penonaktifan Self-Updating Mandiri"
        Badge = "STATUS: PLAY STORE REDIRECT"; BadgeCol = $C_BLUE_LIGHT; BadgeTxt = $C_BLUE
        Body = @'
- Aturan Google Play:
  Device and Network Abuse Policy melarang keras aplikasi mengunduh dan menginstal file APK dari luar Play Store.

- Tindakan Hardening:
  Menghapus izin REQUEST_INSTALL_PACKAGES dari AndroidManifest.xml.

- Solusi Terintegrasi:
  Modul UpdateManager diubah: Saat versi baru rilis, aplikasi menampilkan dialog konfirmasi lalu me-redirect pengguna ke halaman Google Play Store resmi via market://details.
'@
    },
    @{
        Col = $C_PURPLE; Tag = "POIN 2: ATURAN LOKASI"; Title = "Prominent In-App Disclosure"
        Badge = "STATUS: IN-APP DISCLOSURE ON"; BadgeCol = $C_PURPLE_LT; BadgeTxt = $C_PURPLE
        Body = @'
- Aturan Google Play:
  Akses background location ditolak jika tanpa justifikasi mutlak. Edukasi transparan wajib muncul sebelum dialog izin sistem Android.

- Tindakan Hardening:
  Menghapus izin ACCESS_BACKGROUND_LOCATION dari AndroidManifest.xml.

- Solusi Terintegrasi:
  Membuat LocationDisclosureHelper yang menjelaskan fungsi GPS untuk radius absensi dan pelacakan rute kerja sebelum permission sistem diminta.
'@
    },
    @{
        Col = $C_EMERALD; Tag = "POIN 3: KEBIJAKAN PRIVASI"; Title = "Tautan Privacy Policy Publik Aktif"
        Badge = "STATUS: LIVE (HTTP 200 OK)"; BadgeCol = $C_EMERALD_LT; BadgeTxt = $C_EMERALD
        Body = @'
- Aturan Google Play:
  Aplikasi pengumpul data sensitif (GPS, Kamera selfie, Device ID) wajib memiliki dokumen kebijakan privasi yang dapat diakses publik.

- Implementasi Resmi:
  Dibuatkan landing page resmi di domain utama:
  https://esa-solutions.id/privacy-policy
  (Terverifikasi HTTP 200 OK).

- Konten Dokumen:
  Memuat tujuan pemrosesan GPS, hak data pengguna, retensi data, dan kontak resmi DPO ESA.
'@
    }
)

for ($i = 0; $i -lt 3; $i++) {
    $item = $secPlayData[$i]
    $cX = 50 + ($i * ($cardW3 + 20))
    $c = $s6.Shapes.AddShape(5, $cX, $cardY3, $cardW3, $cardH3)
    $c.Fill.Solid(); $c.Fill.ForeColor.RGB = $C_WHITE
    $c.Line.Visible = -1; $c.Line.ForeColor.RGB = $C_BORDER; $c.Line.Weight = 1
    
    $acc = $s6.Shapes.AddShape(1, $cX, $cardY3, $cardW3, 5)
    $acc.Fill.Solid(); $acc.Fill.ForeColor.RGB = $item.Col; $acc.Line.Visible = 0

    $tbHdr = $s6.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 12, $cardW3 - 28, 48)
    $tfHdr = $tbHdr.TextFrame; $tfHdr.WordWrap = -1; $tfHdr.MarginLeft = 0; $tfHdr.MarginTop = 0
    $trHdr = $tfHdr.TextRange
    $trHdr.Text = "$($item.Tag)`r`n$($item.Title)"
    $trHdr.Paragraphs(1).Font.Name = "Segoe UI"; $trHdr.Paragraphs(1).Font.Size = 8; $trHdr.Paragraphs(1).Font.Bold = -1; $trHdr.Paragraphs(1).Font.Color.RGB = $item.Col
    $trHdr.Paragraphs(2).Font.Name = "Segoe UI"; $trHdr.Paragraphs(2).Font.Size = 12; $trHdr.Paragraphs(2).Font.Bold = -1; $trHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

    $tbBody = $s6.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 64, $cardW3 - 28, 255)
    $tfBody = $tbBody.TextFrame; $tfBody.WordWrap = -1; $tfBody.MarginLeft = 0; $tfBody.MarginTop = 0
    $trBody = $tfBody.TextRange
    $trBody.Text = $item.Body
    $trBody.Font.Name = "Segoe UI"; $trBody.Font.Size = 9; $trBody.Font.Color.RGB = $C_BODY

    $pill = $s6.Shapes.AddShape(5, $cX + 14, $cardY3 + 332, $cardW3 - 28, 24)
    $pill.Fill.Solid(); $pill.Fill.ForeColor.RGB = $item.BadgeCol
    $pill.Line.Visible = 0
    $pillTr = $pill.TextFrame.TextRange
    $pillTr.Text = $item.Badge
    $pillTr.Font.Name = "Segoe UI"; $pillTr.Font.Size = 8.5; $pillTr.Font.Bold = -1; $pillTr.Font.Color.RGB = $item.BadgeTxt
    $pillTr.ParagraphFormat.Alignment = 2
    $pill.Adjustments.Item(1) = 0.5
}


# ==============================================================================
# SLIDE 7: KLARIFIKASI GEOFENCING & LIVE TRACKING TETAP AKTIF
# ==============================================================================
Write-Output "Membuat Slide 7: Geofencing & Live Tracking Assurance..."
$s7 = $prs.Slides.Add(7, 12)
Set-LightBg $s7 | Out-Null
Add-Header $s7 "FITUR OPERASIONAL & REGULASI" "Jaminan Operasional: Geofencing & Live Tracking Tetap 100% Aktif" "Klarifikasi teknis: Penyesuaian izin Google Play tidak menghentikan fungsi radius absensi maupun rute surveyor/sales."
Add-Footer $s7 7

# Left: Geofencing
$c7L = $s7.Shapes.AddShape(5, 50, $cY5, $cW5, $cH5)
$c7L.Fill.Solid(); $c7L.Fill.ForeColor.RGB = $C_WHITE
$c7L.Line.Visible = -1; $c7L.Line.ForeColor.RGB = $C_BORDER; $c7L.Line.Weight = 1

$c7LBar = $s7.Shapes.AddShape(1, 50, $cY5, $cW5, 5)
$c7LBar.Fill.Solid(); $c7LBar.Fill.ForeColor.RGB = $C_BLUE; $c7LBar.Line.Visible = 0

$tb7LHdr = $s7.Shapes.AddTextbox(1, 68, $cY5 + 12, $cW5 - 36, 46)
$tb7LHdr.TextFrame.MarginLeft = 0; $tb7LHdr.TextFrame.MarginTop = 0
$tr7LHdr = $tb7LHdr.TextFrame.TextRange
$tr7LHdr.Text = "FITUR 1: GEOFENCING RADIUS ABSENSI`r`nStatus: 100% Berfungsi Normal & Aman"
$tr7LHdr.Paragraphs(1).Font.Name = "Segoe UI"; $tr7LHdr.Paragraphs(1).Font.Size = 8.5; $tr7LHdr.Paragraphs(1).Font.Bold = -1; $tr7LHdr.Paragraphs(1).Font.Color.RGB = $C_BLUE
$tr7LHdr.Paragraphs(2).Font.Name = "Segoe UI"; $tr7LHdr.Paragraphs(2).Font.Size = 13; $tr7LHdr.Paragraphs(2).Font.Bold = -1; $tr7LHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

$tb7LBody = $s7.Shapes.AddTextbox(1, 68, $cY5 + 62, $cW5 - 36, 245)
$tb7LBody.TextFrame.WordWrap = -1; $tb7LBody.TextFrame.MarginLeft = 0; $tb7LBody.TextFrame.MarginTop = 0
$tr7LBody = $tb7LBody.TextFrame.TextRange
$tr7LBody.Text = @'
- Basis Izin: ACCESS_FINE_LOCATION (GPS Presisi Tinggi)
  Mengambil titik koordinat akurat perangkat saat karyawan melakukan Check-in dan Check-out di kantor atau outlet.

- Mode Eksekusi: Foreground (Saat Aplikasi Aktif)
  Karyawan membuka aplikasi saat clock-in. Sistem menghitung radius meter menggunakan formula Haversine terhadap koordinat lokasi kerja yang terdaftar.

- Kepatuhan Penuh Terhadap Regulasi Google:
  Penggunaan izin Foreground Location saat aplikasi aktif TIDAK terkena pembatasan kebijakan background location Google.

- Kesimpulan Operasional:
  Fitur absensi radius kantor berjalan normal tanpa kendala dan bebas risiko penolakan review Google Play Store.
'@
$tr7LBody.Font.Name = "Segoe UI"; $tr7LBody.Font.Size = 9.1; $tr7LBody.Font.Color.RGB = $C_BODY

$geoBox = $s7.Shapes.AddShape(5, 68, $cY5 + 312, $cW5 - 36, 42)
$geoBox.Fill.Solid(); $geoBox.Fill.ForeColor.RGB = $C_BLUE_LIGHT
$geoBox.Line.Visible = -1; $geoBox.Line.ForeColor.RGB = Get-Rgb 147 197 253; $geoBox.Line.Weight = 1
$gTr = $geoBox.TextFrame.TextRange
$gTr.Text = "Status Geofencing: 100% Aktif & Aman. Tidak memerlukan izin background location khusus."
$gTr.Font.Name = "Segoe UI"; $gTr.Font.Size = 8.5; $gTr.Font.Color.RGB = $C_BLUE; $gTr.Font.Bold = -1
$geoBox.Adjustments.Item(1) = 0.2


# Right: Live Tracking
$c7R = $s7.Shapes.AddShape(5, 492, $cY5, $cW5, $cH5)
$c7R.Fill.Solid(); $c7R.Fill.ForeColor.RGB = $C_WHITE
$c7R.Line.Visible = -1; $c7R.Line.ForeColor.RGB = $C_BORDER; $c7R.Line.Weight = 1

$c7RBar = $s7.Shapes.AddShape(1, 492, $cY5, $cW5, 5)
$c7RBar.Fill.Solid(); $c7RBar.Fill.ForeColor.RGB = $C_EMERALD; $c7RBar.Line.Visible = 0

$tb7RHdr = $s7.Shapes.AddTextbox(1, 510, $cY5 + 12, $cW5 - 36, 46)
$tb7RHdr.TextFrame.MarginLeft = 0; $tb7RHdr.TextFrame.MarginTop = 0
$tr7RHdr = $tb7RHdr.TextFrame.TextRange
$tr7RHdr.Text = "FITUR 2: LIVE TRACKING SURVEYOR / SALES`r`nStatus: 100% Aktif & Legal via Foreground Service"
$tr7RHdr.Paragraphs(1).Font.Name = "Segoe UI"; $tr7RHdr.Paragraphs(1).Font.Size = 8.5; $tr7RHdr.Paragraphs(1).Font.Bold = -1; $tr7RHdr.Paragraphs(1).Font.Color.RGB = $C_EMERALD
$tr7RHdr.Paragraphs(2).Font.Name = "Segoe UI"; $tr7RHdr.Paragraphs(2).Font.Size = 13; $tr7RHdr.Paragraphs(2).Font.Bold = -1; $tr7RHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

$tb7RBody = $s7.Shapes.AddTextbox(1, 510, $cY5 + 62, $cW5 - 36, 245)
$tb7RBody.TextFrame.WordWrap = -1; $tb7RBody.TextFrame.MarginLeft = 0; $tb7RBody.TextFrame.MarginTop = 0
$tr7RBody = $tb7RBody.TextFrame.TextRange
$tr7RBody.Text = @'
- Basis Izin: FOREGROUND_SERVICE_LOCATION
  Memungkinkan aplikasi melacak rute perjalanan tugas meskipun layar ponsel mati atau aplikasi diminimalkan.

- Notifikasi Status Bar Persisten:
  Saat tugas kunjungan dimulai, sistem menampilkan notifikasi aktif: 'Aplikasi sedang melacak perjalanan dinas'. Notifikasi ini menjadi syarat mutlak Google agar pelacakan transparan bagi pengguna.

- Kepatuhan Legalitas Regulasi Google:
  Google mengizinkan penuh pelacakan lokasi via Foreground Service asalkan memiliki notifikasi terlihat dan use case kerja yang jelas.

- Kesimpulan Operasional:
  Riwayat rute dan jarak tempuh salesman/surveyor tetap terekam akurat di server tanpa melanggar kebijakan privasi Google.
'@
$tr7RBody.Font.Name = "Segoe UI"; $tr7RBody.Font.Size = 9.1; $tr7RBody.Font.Color.RGB = $C_BODY

$liveBox = $s7.Shapes.AddShape(5, 510, $cY5 + 312, $cW5 - 36, 42)
$liveBox.Fill.Solid(); $liveBox.Fill.ForeColor.RGB = $C_EMERALD_LT
$liveBox.Line.Visible = -1; $liveBox.Line.ForeColor.RGB = Get-Rgb 110 231 183; $liveBox.Line.Weight = 1
$lTr = $liveBox.TextFrame.TextRange
$lTr.Text = "Status Live Tracking: 100% Aktif & Legal via Foreground Service dengan notifikasi aktif di status bar."
$lTr.Font.Name = "Segoe UI"; $lTr.Font.Size = 8.5; $lTr.Font.Color.RGB = $C_EMERALD; $lTr.Font.Bold = -1
$liveBox.Adjustments.Item(1) = 0.2


# ==============================================================================
# SLIDE 8: STATUS ARTEFAK RILIS & CHECKLIST PLAY CONSOLE
# ==============================================================================
Write-Output "Membuat Slide 8: Release Artifacts & Play Console..."
$s8 = $prs.Slides.Add(8, 12)
Set-LightBg $s8 | Out-Null
Add-Header $s8 "ARTIFACTS & PLAY CONSOLE" "Status Artefak Build & Panduan Pengisian Google Play Console" "Rincian paket rilis siap upload dan checklist langkah demi langkah pengisian form keamanan data pengembang."
Add-Footer $s8 8

$cW8L = 380; $cW8R = 456

# Left Card: Release Artifacts
$c8L = $s8.Shapes.AddShape(5, 50, $cY5, $cW8L, $cH5)
$c8L.Fill.Solid(); $c8L.Fill.ForeColor.RGB = $C_WHITE
$c8L.Line.Visible = -1; $c8L.Line.ForeColor.RGB = $C_BORDER; $c8L.Line.Weight = 1

$c8LBar = $s8.Shapes.AddShape(1, 50, $cY5, $cW8L, 5)
$c8LBar.Fill.Solid(); $c8LBar.Fill.ForeColor.RGB = $C_BLUE; $c8LBar.Line.Visible = 0

$tb8LHdr = $s8.Shapes.AddTextbox(1, 68, $cY5 + 12, $cW8L - 36, 46)
$tb8LHdr.TextFrame.MarginLeft = 0; $tb8LHdr.TextFrame.MarginTop = 0
$tr8LHdr = $tb8LHdr.TextFrame.TextRange
$tr8LHdr.Text = "STATUS ARTEFAK RILIS RESMI`r`nPaket Siap Distribusi (v1.0.153)"
$tr8LHdr.Paragraphs(1).Font.Name = "Segoe UI"; $tr8LHdr.Paragraphs(1).Font.Size = 8.5; $tr8LHdr.Paragraphs(1).Font.Bold = -1; $tr8LHdr.Paragraphs(1).Font.Color.RGB = $C_BLUE
$tr8LHdr.Paragraphs(2).Font.Name = "Segoe UI"; $tr8LHdr.Paragraphs(2).Font.Size = 13; $tr8LHdr.Paragraphs(2).Font.Bold = -1; $tr8LHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

# Mini Box AAB
$boxAab = $s8.Shapes.AddShape(5, 68, $cY5 + 64, $cW8L - 36, 85)
$boxAab.Fill.Solid(); $boxAab.Fill.ForeColor.RGB = $C_BG_LIGHT
$boxAab.Line.Visible = -1; $boxAab.Line.ForeColor.RGB = $C_BORDER; $boxAab.Line.Weight = 1
$tbAab = $s8.Shapes.AddTextbox(1, 78, $cY5 + 70, $cW8L - 56, 75)
$tbAab.TextFrame.MarginLeft = 0; $tbAab.TextFrame.MarginTop = 0
$trAab = $tbAab.TextFrame.TextRange
$trAab.Text = "Android App Bundle (.AAB)  [SYARAT PLAY STORE]`r`nFile: d:\Project\attendace\app-release.aab`r`nUkuran: 94.0 MB  |  Target: Google Play Console (Production)"
$trAab.Paragraphs(1).Font.Name = "Segoe UI"; $trAab.Paragraphs(1).Font.Size = 10; $trAab.Paragraphs(1).Font.Bold = -1; $trAab.Paragraphs(1).Font.Color.RGB = $C_BLUE
$trAab.Paragraphs(2).Font.Name = "Segoe UI"; $trAab.Paragraphs(2).Font.Size = 8.5; $trAab.Paragraphs(2).Font.Color.RGB = $C_BODY
$trAab.Paragraphs(3).Font.Name = "Segoe UI"; $trAab.Paragraphs(3).Font.Size = 8.5; $trAab.Paragraphs(3).Font.Color.RGB = $C_MUTED

# Mini Box APK
$boxApk = $s8.Shapes.AddShape(5, 68, $cY5 + 160, $cW8L - 36, 85)
$boxApk.Fill.Solid(); $boxApk.Fill.ForeColor.RGB = $C_BG_LIGHT
$boxApk.Line.Visible = -1; $boxApk.Line.ForeColor.RGB = $C_BORDER; $boxApk.Line.Weight = 1
$tbApk = $s8.Shapes.AddTextbox(1, 78, $cY5 + 166, $cW8L - 56, 75)
$tbApk.TextFrame.MarginLeft = 0; $tbApk.TextFrame.MarginTop = 0
$trApk = $tbApk.TextFrame.TextRange
$trApk.Text = "Android Application Package (.APK)  [INTERNAL TESTING]`r`nFile: d:\Project\attendace\app-release.apk`r`nUkuran: 117.2 MB  |  Target: UAT & Direct Device Testing"
$trApk.Paragraphs(1).Font.Name = "Segoe UI"; $trApk.Paragraphs(1).Font.Size = 10; $trApk.Paragraphs(1).Font.Bold = -1; $trApk.Paragraphs(1).Font.Color.RGB = $C_PURPLE
$trApk.Paragraphs(2).Font.Name = "Segoe UI"; $trApk.Paragraphs(2).Font.Size = 8.5; $trApk.Paragraphs(2).Font.Color.RGB = $C_BODY
$trApk.Paragraphs(3).Font.Name = "Segoe UI"; $trApk.Paragraphs(3).Font.Size = 8.5; $trApk.Paragraphs(3).Font.Color.RGB = $C_MUTED

    # Left Card Specs List
    $tb8LSpecs = $s8.Shapes.AddTextbox(1, 68, $cY5 + 258, $cW8L - 36, 100)
    $tb8LSpecs.TextFrame.MarginLeft = 0; $tb8LSpecs.TextFrame.MarginTop = 0
    $tr8LSpecs = $tb8LSpecs.TextFrame.TextRange
    $tr8LSpecs.Text = "Spesifikasi Paket Rilis:`r`n" +
    "- Package Name : com.attendance.att_mobile`r`n" +
    "- Versi Rilis   : 1.0.153 (VersionCode: 153)`r`n" +
    "- Target SDK    : 34 / 35 (Android 14 / Android 15)`r`n" +
    "- Signed By     : upload-keystore.jks (Official Production)"
    $tr8LSpecs.Paragraphs(1).Font.Name = "Segoe UI"; $tr8LSpecs.Paragraphs(1).Font.Size = 9.5; $tr8LSpecs.Paragraphs(1).Font.Bold = -1; $tr8LSpecs.Paragraphs(1).Font.Color.RGB = $C_NAVY_TEXT
    for ($p = 2; $p -le $tr8LSpecs.Paragraphs().Count; $p++) {
        $tr8LSpecs.Paragraphs($p).Font.Name = "Segoe UI"; $tr8LSpecs.Paragraphs($p).Font.Size = 8.5; $tr8LSpecs.Paragraphs($p).Font.Color.RGB = $C_BODY
    }


    # Right Card: Play Console Checklist
    $c8R = $s8.Shapes.AddShape(5, 454, $cY5, $cW8R, $cH5)
    $c8R.Fill.Solid(); $c8R.Fill.ForeColor.RGB = $C_WHITE
    $c8R.Line.Visible = -1; $c8R.Line.ForeColor.RGB = $C_BORDER; $c8R.Line.Weight = 1

    $c8RBar = $s8.Shapes.AddShape(1, 454, $cY5, $cW8R, 5)
    $c8RBar.Fill.Solid(); $c8RBar.Fill.ForeColor.RGB = $C_PURPLE; $c8RBar.Line.Visible = 0

    $tb8RHdr = $s8.Shapes.AddTextbox(1, 472, $cY5 + 12, $cW8R - 36, 46)
    $tb8RHdr.TextFrame.MarginLeft = 0; $tb8RHdr.TextFrame.MarginTop = 0
    $tr8RHdr = $tb8RHdr.TextFrame.TextRange
    $tr8RHdr.Text = "CHECKLIST PENGISIAN GOOGLE PLAY CONSOLE`r`nPanduan Form Kebijakan & Keamanan Data"
    $tr8RHdr.Paragraphs(1).Font.Name = "Segoe UI"; $tr8RHdr.Paragraphs(1).Font.Size = 8.5; $tr8RHdr.Paragraphs(1).Font.Bold = -1; $tr8RHdr.Paragraphs(1).Font.Color.RGB = $C_PURPLE
    $tr8RHdr.Paragraphs(2).Font.Name = "Segoe UI"; $tr8RHdr.Paragraphs(2).Font.Size = 13; $tr8RHdr.Paragraphs(2).Font.Bold = -1; $tr8RHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

    $tb8RBody = $s8.Shapes.AddTextbox(1, 472, $cY5 + 62, $cW8R - 36, 295)
    $tb8RBody.TextFrame.WordWrap = -1; $tb8RBody.TextFrame.MarginLeft = 0; $tb8RBody.TextFrame.MarginTop = 0
    $tr8RBody = $tb8RBody.TextFrame.TextRange
    $tr8RBody.Text = @'
[SIAP] Checklist 1: Privacy Policy URL
Tautan resmi: https://esa-solutions.id/privacy-policy
Status server: HTTP 200 OK (Wajib didaftarkan pada menu App Privacy).

[SIAP] Checklist 2: App Access (Akses Aplikasi)
Pilih "All or some functionality is restricted". Sediakan akun testing demo untuk reviewer Google Play memeriksa fungsionalitas login absensi.

[SIAP] Checklist 3: Data Safety Form (Keamanan Data)
- Location: Approximate & Precise Location (Fungsi: App Functionality).
- Photos/Videos: Akses Kamera untuk verifikasi selfie absensi karyawan.
- Personal Info: Nama & Employee ID (Fungsi: Account Management).

[SIAP] Checklist 4: Location Declaration & Target Audience
Deklarasikan penggunaan Foreground Service Location untuk pelacakan rute sales. Target Audience: Karyawan Perusahaan (Usia 18+). Bebas iklan (No Ads).
'@
    $tr8RBody.Font.Name = "Segoe UI"; $tr8RBody.Font.Size = 9; $tr8RBody.Font.Color.RGB = $C_BODY


    # ==============================================================================
    # SLIDE 9: ARSITEKTUR MULTI-SERVER & STATUS DEPLOYMENT
    # ==============================================================================
    Write-Output "Membuat Slide 9: Multi-Server Architecture..."
    $s9 = $prs.Slides.Add(9, 12)
    Set-LightBg $s9 | Out-Null
    Add-Header $s9 "INFRASTRUKTUR & DEPLOYMENT" "Topologi Multi-Server & Status Deploy Terkini" "Distribusi pembaruan sistem dan verifikasi health check pada seluruh node server ESA Groups."
    Add-Footer $s9 9

    $sW = 418; $sH = 175

    $srvData = @(
        @{
            X = 50; Y = 112; Col = $C_BLUE
            Tag = "DEVELOPMENT & STAGING NODE"
            Title = "Server Staging (appsend.my.id)"
            Badge = "STATUS: DEPLOYED (HTTP 200)"; BadgeCol = $C_BLUE_LIGHT; BadgeTxt = $C_BLUE
            Desc = "- Host Domain : https://appsend.my.id`r`n" +
                   "- Status Node : Active & Synced with GitHub origin/main`r`n" +
                   "- Fungsionalitas: Sandbox validasi API, staging testing, dan simulasi migrasi skema database sebelum push rilis."
        },
        @{
            X = 492; Y = 112; Col = $C_EMERALD
            Tag = "PRODUCTION CLUSTER 1"
            Title = "Server 1: PT AMK (amk.dgsoft.web.id)"
            Badge = "HEALTH CHECK: HTTP 200 OK"; BadgeCol = $C_EMERALD_LT; BadgeTxt = $C_EMERALD
            Desc = "- Node IP     : 38.103.170.235  |  Path: /wwwroot/amk.dgsoft.web.id`r`n" +
                   "- Auto Deploy : Webhook Git SSH Automation Aktif`r`n" +
                   "- Optimasi    : Auto-reload PHP-FPM 8.3, Livewire asset clear, dan query database attendance optimization."
        },
        @{
            X = 50; Y = 300; Col = $C_PURPLE
            Tag = "PRODUCTION CLUSTER 2"
            Title = "Server 2: PT AKP (akp.dgsoft.web.id)"
            Badge = "HEALTH CHECK: HTTP 200 OK"; BadgeCol = $C_PURPLE_LT; BadgeTxt = $C_PURPLE
            Desc = "- Node IP     : 38.103.170.223  |  Path: /wwwroot/akp.dgsoft.web.id`r`n" +
                   "- Auto Deploy : Webhook Git SSH Automation Aktif`r`n" +
                   "- Optimasi    : Sinkronisasi master data karyawan, route binding, dan reporting itinerary kunjungan sales."
        },
        @{
            X = 492; Y = 300; Col = $C_AMBER
            Tag = "PRODUCTION CLUSTER 3"
            Title = "Server 3: PT ATK (atk.dgsoft.web.id)"
            Badge = "HEALTH CHECK: HTTP 200 OK"; BadgeCol = $C_AMBER_LT; BadgeTxt = $C_AMBER
            Desc = "- Node IP     : 38.103.170.224  |  Path: /wwwroot/atk.dgsoft.web.id`r`n" +
                   "- Auto Deploy : Webhook Git SSH Automation Aktif`r`n" +
                   "- Optimasi    : Indexing kehadiran harian, isolasi storage file publik, dan proteksi server middleware."
        }
    )

    for ($i = 0; $i -lt 4; $i++) {
    $sd = $srvData[$i]
    $card = $s9.Shapes.AddShape(5, $sd.X, $sd.Y, $sW, $sH)
    $card.Fill.Solid(); $card.Fill.ForeColor.RGB = $C_WHITE
    $card.Line.Visible = -1; $card.Line.ForeColor.RGB = $C_BORDER; $card.Line.Weight = 1
    
    $bar = $s9.Shapes.AddShape(1, $sd.X, $sd.Y, $sW, 4)
    $bar.Fill.Solid(); $bar.Fill.ForeColor.RGB = $sd.Col; $bar.Line.Visible = 0

    # Server Card Header
    $tbHdr = $s9.Shapes.AddTextbox(1, $sd.X + 14, $sd.Y + 10, $sW - 28, 42)
    $tfHdr = $tbHdr.TextFrame; $tfHdr.WordWrap = -1; $tfHdr.MarginLeft = 0; $tfHdr.MarginTop = 0
    $trHdr = $tfHdr.TextRange
    $trHdr.Text = "$($sd.Tag)`r`n$($sd.Title)"
    $trHdr.Paragraphs(1).Font.Name = "Segoe UI"; $trHdr.Paragraphs(1).Font.Size = 7.5; $trHdr.Paragraphs(1).Font.Bold = -1; $trHdr.Paragraphs(1).Font.Color.RGB = $sd.Col
    $trHdr.Paragraphs(2).Font.Name = "Segoe UI"; $trHdr.Paragraphs(2).Font.Size = 11.5; $trHdr.Paragraphs(2).Font.Bold = -1; $trHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

    # Server Card Body
    $tbDesc = $s9.Shapes.AddTextbox(1, $sd.X + 14, $sd.Y + 54, $sW - 28, 85)
    $tfDesc = $tbDesc.TextFrame; $tfDesc.WordWrap = -1; $tfDesc.MarginLeft = 0; $tfDesc.MarginTop = 0
    $trDesc = $tfDesc.TextRange
    $trDesc.Text = $sd.Desc
    $trDesc.Font.Name = "Segoe UI"; $trDesc.Font.Size = 8.8; $trDesc.Font.Color.RGB = $C_BODY

    # Status pill bottom
    $pill = $s9.Shapes.AddShape(5, $sd.X + 14, $sd.Y + 144, $sW - 28, 22)
    $pill.Fill.Solid(); $pill.Fill.ForeColor.RGB = $sd.BadgeCol
    $pill.Line.Visible = 0
    $pillTr = $pill.TextFrame.TextRange
    $pillTr.Text = $sd.Badge
    $pillTr.Font.Name = "Segoe UI"; $pillTr.Font.Size = 8; $pillTr.Font.Bold = -1; $pillTr.Font.Color.RGB = $sd.BadgeTxt
    $pillTr.ParagraphFormat.Alignment = 2
    $pill.Adjustments.Item(1) = 0.5
}


# ==============================================================================
# SLIDE 10: REKOMENDASI & ROADMAP PUBLIKASI (PENUTUP)
# ==============================================================================
Write-Output "Membuat Slide 10: Roadmap & Recommendations..."
$s10 = $prs.Slides.Add(10, 12)
Set-LightBg $s10 | Out-Null
Add-Header $s10 "ROADMAP PUBLIKASI" "Rekomendasi Tindak Lanjut & Timeline Rilis Google Play Store" "Langkah terstruktur menuju rilis publik yang aman, stabil, dan memenuhi kepatuhan regulasi."
Add-Footer $s10 10

$stepsData = @(
    @{
        Col = $C_BLUE; Step = "TAHAP 1: INTERNAL TESTING"; Title = "Upload AAB & Pre-Launch Test"
        Badge = "ESTIMASI: HARI 1"; BadgeCol = $C_BLUE_LIGHT; BadgeTxt = $C_BLUE
        Body = @'
- Upload app-release.aab:
  Unggah berkas AAB ke jalur Internal Testing di Google Play Console.

- Pre-Launch Report Otomatis:
  Memeriksa hasil pengujian otomatis Google Cloud Test Lab untuk mendeteksi crash, ANR, atau isu layout di puluhan varian smartphone Android.

- Pengujian Terbatas QA Internal:
  Verifikasi alur login, absensi radius geofencing, dan live tracking pada perangkat riil tim lapangan.
'@
    },
    @{
        Col = $C_PURPLE; Step = "TAHAP 2: REVIEW SUBMISSION"; Title = "Pengisian Form & Submit Review"
        Badge = "ESTIMASI: HARI 2 - 3"; BadgeCol = $C_PURPLE_LT; BadgeTxt = $C_PURPLE
        Body = @'
- Melengkapi Seluruh Form Regulasi:
  Mengisi Data Safety, Content Rating, Target Audience (Karyawan 18+), dan deklarasi Foreground Service.

- Verifikasi Privacy Policy:
  Memastikan URL https://esa-solutions.id/privacy-policy aktif dan terinput di Play Console.

- Pengajuan Review Tim Google:
  Submit rilis ke tim review pengembang Google Play (estimasi proses evaluasi: 24 hingga 72 jam).
'@
    },
    @{
        Col = $C_EMERALD; Step = "TAHAP 3: PRODUCTION ROLLOUT"; Title = "Peluncuran Publik & Sosialisasi"
        Badge = "ESTIMASI: HARI 4 - 5"; BadgeCol = $C_EMERALD_LT; BadgeTxt = $C_EMERALD
        Body = @'
- Peluncuran Bertahap (Staged Rollout):
  Setelah disetujui Google, aktifkan peluncuran rilis secara bertahap (20% -> 50% -> 100%) untuk memitigasi risiko tak terduga.

- Pengumuman & Sosialisasi Cabang:
  Distribusi panduan pembaruan resmi kepada seluruh manajemen dan karyawan di PT AMK, PT AKP, dan PT ATK.

- Pemantauan Crash & Vitals:
  Monitoring metrik kestabilan aplikasi secara berkala via Firebase Crashlytics & Play Vitals.
'@
    }
)

for ($i = 0; $i -lt 3; $i++) {
    $item = $stepsData[$i]
    $cX = 50 + ($i * ($cardW3 + 20))
    $c = $s10.Shapes.AddShape(5, $cX, $cardY3, $cardW3, $cardH3)
    $c.Fill.Solid(); $c.Fill.ForeColor.RGB = $C_WHITE
    $c.Line.Visible = -1; $c.Line.ForeColor.RGB = $C_BORDER; $c.Line.Weight = 1
    
    $acc = $s10.Shapes.AddShape(1, $cX, $cardY3, $cardW3, 5)
    $acc.Fill.Solid(); $acc.Fill.ForeColor.RGB = $item.Col; $acc.Line.Visible = 0

    $tbHdr = $s10.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 12, $cardW3 - 28, 48)
    $tfHdr = $tbHdr.TextFrame; $tfHdr.WordWrap = -1; $tfHdr.MarginLeft = 0; $tfHdr.MarginTop = 0
    $trHdr = $tfHdr.TextRange
    $trHdr.Text = "$($item.Step)`r`n$($item.Title)"
    $trHdr.Paragraphs(1).Font.Name = "Segoe UI"; $trHdr.Paragraphs(1).Font.Size = 8; $trHdr.Paragraphs(1).Font.Bold = -1; $trHdr.Paragraphs(1).Font.Color.RGB = $item.Col
    $trHdr.Paragraphs(2).Font.Name = "Segoe UI"; $trHdr.Paragraphs(2).Font.Size = 12; $trHdr.Paragraphs(2).Font.Bold = -1; $trHdr.Paragraphs(2).Font.Color.RGB = $C_NAVY_TEXT

    $tbBody = $s10.Shapes.AddTextbox(1, $cX + 14, $cardY3 + 64, $cardW3 - 28, 255)
    $tfBody = $tbBody.TextFrame; $tfBody.WordWrap = -1; $tfBody.MarginLeft = 0; $tfBody.MarginTop = 0
    $trBody = $tfBody.TextRange
    $trBody.Text = $item.Body
    $trBody.Font.Name = "Segoe UI"; $trBody.Font.Size = 9; $trBody.Font.Color.RGB = $C_BODY

    $pill = $s10.Shapes.AddShape(5, $cX + 14, $cardY3 + 332, $cardW3 - 28, 24)
    $pill.Fill.Solid(); $pill.Fill.ForeColor.RGB = $item.BadgeCol
    $pill.Line.Visible = 0
    $pillTr = $pill.TextFrame.TextRange
    $pillTr.Text = $item.Badge
    $pillTr.Font.Name = "Segoe UI"; $pillTr.Font.Size = 8.5; $pillTr.Font.Bold = -1; $pillTr.Font.Color.RGB = $item.BadgeTxt
    $pillTr.ParagraphFormat.Alignment = 2
    $pill.Adjustments.Item(1) = 0.5
}

# ==============================================================================
# SAVE & FINALIZE
# ==============================================================================
$outputPath = "d:\Project\attendace\Resume_Cyber_Security_dan_PlayStore_ESA_Mobile.pptx"
if (Test-Path $outputPath) {
    Remove-Item $outputPath -Force
}

Write-Output "Menyimpan file presentasi ke: $outputPath"
$prs.SaveAs($outputPath)
$prs.Close()
$ppt.Quit()

[System.Runtime.Interopservices.Marshal]::ReleaseComObject($prs) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($ppt) | Out-Null
[System.GC]::Collect()
[System.GC]::WaitForPendingFinalizers()

Write-Output "SUKSES: File presentasi V2 berhasil dibuat sempurna di $outputPath"
