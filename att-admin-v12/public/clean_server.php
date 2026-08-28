<?php
/**
 * Safe Server Storage Cleaner for aaPanel / Debian / Laravel
 * URL: https://appsend.my.id/clean_server.php?token=dgsoft_rahasia_123
 */

// 1. Security Token Check
$validToken = 'dgsoft_rahasia_123';
$inputToken = $_GET['token'] ?? $_POST['token'] ?? '';

if ($inputToken !== $validToken) {
    http_response_code(403);
    die(json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Token otentikasi tidak valid.',
    ]));
}

// Disable time limit for cleaning process
@set_time_limit(180);
@ini_set('memory_limit', '256M');

header('Content-Type: text/html; charset=utf-8');

function getDiskUsage(): array
{
    // Coba via shell df -P /
    $df = @shell_exec('df -P / 2>&1');
    if ($df && preg_match('/\n\S+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%\s+/', $df, $matches)) {
        $totalBytes = (float)$matches[1] * 1024;
        $usedBytes  = (float)$matches[2] * 1024;
        $freeBytes  = (float)$matches[3] * 1024;
        $percent    = (float)$matches[4];

        return [
            'total' => formatBytes($totalBytes),
            'used' => formatBytes($usedBytes),
            'free' => formatBytes($freeBytes),
            'percent' => $percent,
        ];
    }

    $total = @disk_total_space('/');
    $free = @disk_free_space('/');
    $used = ($total !== false && $free !== false) ? $total - $free : 0;
    $percent = ($total > 0) ? round(($used / $total) * 100, 1) : 0;

    return [
        'total' => formatBytes($total),
        'used' => formatBytes($used),
        'free' => formatBytes($free),
        'percent' => $percent,
    ];
}

function formatBytes($bytes, $precision = 2): string
{
    if (!$bytes || $bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function runCmd($command): string
{
    $output = @shell_exec($command . ' 2>&1');
    return trim((string) $output);
}

$beforeDisk = getDiskUsage();
$logs = [];

// Step 1: Bersihkan Laravel Logs & Views / Cache di project
$projectPath = dirname(__DIR__);
if (file_exists($projectPath . '/artisan')) {
    $logs[] = "🧹 <b>Membersihkan Laravel Cache & Logs...</b>";
    $artisanClean = runCmd("cd " . escapeshellarg($projectPath) . " && php artisan optimize:clear");
    $logs[] = "<pre>" . htmlspecialchars($artisanClean) . "</pre>";
    
    // Truncate laravel logs
    $logFiles = glob($projectPath . '/storage/logs/*.log');
    if ($logFiles) {
        foreach ($logFiles as $lf) {
            @file_put_contents($lf, '');
            $logs[] = "✓ Dikosongkan: " . basename($lf);
        }
    }
}

// Step 2: Bersihkan Log Web Server aaPanel (Nginx / Apache)
$logs[] = "<br>🌐 <b>Membersihkan Log Web Server (aaPanel /www/wwwlogs)...</b>";
// Hapus file arsip log lama (*.gz, *.1, *.tar.gz, *.log.202*)
runCmd("find /www/wwwlogs/ -type f \( -name '*.gz' -o -name '*.1' -o -name '*.2' -o -name '*.log.*' \) -delete");
// Kosongkan file log aktif tanpa menghapus filenya (supaya webserver tidak error)
$wwwLogs = glob('/www/wwwlogs/*.log');
if ($wwwLogs) {
    foreach ($wwwLogs as $wl) {
        @file_put_contents($wl, '');
        $logs[] = "✓ Dikosongkan: " . basename($wl);
    }
} else {
    runCmd("truncate -s 0 /www/wwwlogs/*.log 2>/dev/null");
    $logs[] = "✓ Truncate /www/wwwlogs/*.log dijalankan.";
}

// Step 3: Bersihkan Log aaPanel Control Panel
$logs[] = "<br>⚙️ <b>Membersihkan Log aaPanel Panel...</b>";
runCmd("truncate -s 0 /www/server/panel/logs/*.log 2>/dev/null");
runCmd("find /www/server/panel/logs/ -type f \( -name '*.gz' -o -name '*.1' -o -name '*.log.*' \) -delete 2>/dev/null");
$logs[] = "✓ Log aaPanel panel berhasil dikosongkan.";

// Step 4: Bersihkan aaPanel Recycle Bin (Tempat sampah file manager aaPanel)
$logs[] = "<br>🗑️ <b>Membersihkan aaPanel Recycle Bin...</b>";
runCmd("rm -rf /www/Recycle_bin/* 2>/dev/null");
$logs[] = "✓ Pembersihan /www/Recycle_bin/* selesai.";

// Step 5: Bersihkan Systemd Journal Logs (Debian 12)
$logs[] = "<br>📜 <b>Membersihkan Systemd Journal Log (Debian 12)...</b>";
$journalOut = runCmd("journalctl --vacuum-size=100M 2>/dev/null");
$logs[] = "✓ System journal: " . ($journalOut ?: 'Disimpan max 100MB');

// Step 6: Bersihkan APT Package Cache & Archives
$logs[] = "<br>📦 <b>Membersihkan APT Cache (/var/cache/apt)...</b>";
runCmd("apt-get clean 2>/dev/null");
$logs[] = "✓ APT package cache dibersihkan.";

// Step 7: Bersihkan file Temporary /tmp secara AMAN (Hanya file biasa > 1 hari, TIDAK menyentuh .sock / .pid)
$logs[] = "<br>⏳ <b>Membersihkan Temporary Files (/tmp) Secara Aman...</b>";
// PERHATIAN: JANGAN PERNAH 'rm -rf /tmp/*' karena akan menghapus php-fpm.sock & mysql.sock!
$cleanTmp = runCmd("find /tmp -type f -atime +1 -not -name '*.sock' -not -name '*.pid' -not -name 'mysql*' -delete 2>/dev/null");
$logs[] = "✓ File temporary lama (> 24 jam) dibersihkan tanpa mengganggu socket PHP/MySQL/Nginx.";

$afterDisk = getDiskUsage();
$savedPercent = round($beforeDisk['percent'] - $afterDisk['percent'], 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Storage Clean Up & Optimization</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; border-radius: 16px; padding: 28px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { color: #38bdf8; font-size: 22px; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0; }
        .card { background: #0f172a; border: 1px solid #334155; padding: 16px; border-radius: 12px; }
        .card-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
        .card-val { font-size: 24px; font-weight: bold; margin-top: 6px; }
        .val-danger { color: #f87171; }
        .val-success { color: #4ade80; }
        .progress-container { background: #334155; border-radius: 8px; height: 14px; overflow: hidden; margin-top: 8px; }
        .progress-bar { height: 100%; transition: width 0.5s ease; }
        .log-box { background: #090d16; border: 1px solid #1e293b; border-radius: 10px; padding: 16px; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto; color: #cbd5e1; }
        pre { background: #1e293b; padding: 10px; border-radius: 6px; overflow-x: auto; color: #38bdf8; margin: 8px 0; }
        .btn { display: inline-block; background: #0284c7; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; margin-top: 20px; text-align: center; }
        .btn:hover { background: #0369a1; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .badge-safe { background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Server Storage Cleanup Result <span class="badge badge-safe">SAFE MODE</span></h1>
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">
            Pembersihan log, cache, dan file sampah selesai dijalankan pada <b><?= date('d F Y, H:i:s') ?> WIB</b>.
        </p>

        <div class="stats-grid">
            <div class="card">
                <div class="card-label">Disk Sebelum Pembersihan</div>
                <div class="card-val val-danger"><?= $beforeDisk['percent'] ?>%</div>
                <div style="font-size: 12px; color: #94a3b8;">Terpakai: <?= $beforeDisk['used'] ?> / <?= $beforeDisk['total'] ?></div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= $beforeDisk['percent'] ?>%; background: #f87171;"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-label">Disk Sesudah Pembersihan</div>
                <div class="card-val val-success"><?= $afterDisk['percent'] ?>%</div>
                <div style="font-size: 12px; color: #94a3b8;">Sisa Free: <?= $afterDisk['free'] ?> (Hemat <?= $savedPercent ?>%)</div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= $afterDisk['percent'] ?>%; background: #4ade80;"></div>
                </div>
            </div>
        </div>

        <h3 style="color: #f1f5f9; font-size: 16px; margin-top: 24px; margin-bottom: 10px;">📋 Rincian Eksekusi Pembersihan:</h3>
        <div class="log-box">
            <?php foreach ($logs as $line): ?>
                <div><?= $line ?></div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; margin-top: 20px;">
            <a href="clean_server.php?token=<?= htmlspecialchars($validToken) ?>" class="btn">🔄 Jalankan Pembersihan Ulang</a>
            <span style="color: #64748b; font-size: 12px;">✅ Server tetap online stabil tanpa perlu restart manual.</span>
        </div>
    </div>
</body>
</html>
