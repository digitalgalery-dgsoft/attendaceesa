<?php
// ==============================================================================
// 🚀 ESA GROUPS - PRODUCTION WEB DEPLOYMENT CONSOLE
// URL: https://appsend.my.id/deploy-production.php?token=dgsoft_rahasia_123
// ==============================================================================

@ini_set('display_errors', 1);
@error_reporting(E_ALL);
@set_time_limit(600);
@ini_set('memory_limit', '512M');

$secretToken = "dgsoft_rahasia_123";

if (!isset($_GET['token']) || $_GET['token'] !== $secretToken) {
    http_response_code(403);
    die("Akses Ditolak: Token tidak valid.");
}

$servers = [
    [
        'id' => 'amk',
        'name' => 'Server 1: PT Arina Multi Karya (AMK)',
        'ip' => '38.103.170.235',
        'path' => '/www/wwwroot/amk.dgsoft.web.id',
        'domain' => 'amk.esa-solutions.id',
        'ping' => 'https://amk.esa-solutions.id/api/v1/sync/ping',
    ],
    [
        'id' => 'akp',
        'name' => 'Server 2: PT Alva Karya Perkasa (AKP)',
        'ip' => '38.103.170.223',
        'path' => '/www/wwwroot/akp.dgsoft.web.id',
        'domain' => 'akp.esa-solutions.id',
        'ping' => 'https://akp.esa-solutions.id/api/v1/sync/ping',
    ],
    [
        'id' => 'atk',
        'name' => 'Server 3: PT Anugrah Talenta Berkarya (ATK / Gabungan)',
        'ip' => '38.103.170.224',
        'path' => '/www/wwwroot/atk.dgsoft.web.id',
        'domain' => 'atk.esa-solutions.id',
        'ping' => 'https://atk.esa-solutions.id/api/v1/sync/ping',
    ],
];

// Jika request adalah POST, jalankan deployment secara streaming
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/octet-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    header('X-LiteSpeed-Buffer: no');
    
    echo "<!--" . str_repeat(' ', 4096) . "-->\n";
    @flush();

    echo "<span style=\"color: #00bcd4; font-weight: bold;\">=== MEMULAI MULTI-SERVER PRODUCTION DEPLOYMENT ===</span>\n";
    echo "Waktu: " . date('Y-m-d H:i:s') . "\n\n";
    echo "<!--" . str_repeat(' ', 4096) . "-->";
    @flush();

    $runMigration = isset($_GET['migrate']) && $_GET['migrate'] === '1';
    $runPrincipals = isset($_GET['principals']) && $_GET['principals'] === '1';
    $runImportOfftake = isset($_GET['import_offtake']) && $_GET['import_offtake'] === '1';
    $runImportCbp = (isset($_GET['import_cbp']) && $_GET['import_cbp'] === '1') || (isset($_GET['type']) && $_GET['type'] === 'cbp');
    $runImportStock = (isset($_GET['import_stock']) && $_GET['import_stock'] === '1') || (isset($_GET['type']) && $_GET['type'] === 'stock');
    $runImportOos = (isset($_GET['import_oos']) && $_GET['import_oos'] === '1') || (isset($_GET['type']) && $_GET['type'] === 'oos');
    $runImportDaily = (isset($_GET['import_daily']) && $_GET['import_daily'] === '1') || (isset($_GET['type']) && in_array($_GET['type'], ['daily', 'maintenance', 'daily_maintenance']));

    if ($runImportDaily) {
        $importCmd = 'dulux:import-daily-maintenance';
        $importTitle = 'DAILY MAINTENANCE DULUX';
    } elseif ($runImportOos) {
        $importCmd = 'dulux:import-oos';
        $importTitle = 'OOS DULUX (LSO & SSO)';
    } elseif ($runImportStock) {
        $importCmd = 'dulux:import-stock';
        $importTitle = 'STOCK END DULUX';
    } elseif ($runImportCbp) {
        $importCmd = 'dulux:import-cbp';
        $importTitle = 'CBP DULUX';
    } else {
        $importCmd = 'dulux:import-offtake';
        $importTitle = 'OFFTAKE DULUX';
    }

    $targetServer = isset($_GET['server']) ? intval($_GET['server']) : (isset($_GET['only_server']) ? intval($_GET['only_server']) : 0);
    $onlyImport = isset($_GET['only_import']) && $_GET['only_import'] === '1';
    $offtakeYear = isset($_GET['year']) ? ' --year=' . escapeshellarg($_GET['year']) : '';
    $offtakeMonth = isset($_GET['month']) ? ' --month=' . escapeshellarg($_GET['month']) : '';
    $offtakeLimit = isset($_GET['limit']) ? ' --limit=' . intval($_GET['limit']) : '';

    $successCount = 0;

    foreach ($servers as $idx => $srv) {
        $num = $idx + 1;
        if ($targetServer > 0 && $num !== $targetServer) {
            continue;
        }

        echo "\n<span style=\"color: #fbc02d; font-weight: bold;\">----------------------------------------------------------------------</span>\n";
        echo "<span style=\"color: #fbc02d; font-weight: bold;\">▶ [{$num}/3] " . ($onlyImport ? "IMPORT {$importTitle} PADA" : "DEPLOY KE") . " {$srv['name']} ({$srv['ip']})</span>\n";
        echo "<span style=\"color: #fbc02d; font-weight: bold;\">----------------------------------------------------------------------</span>\n";
        echo "<!--" . str_repeat(' ', 4096) . "-->";
        @flush();

        // Susun perintah remote
        $runClean = isset($_GET['clean']) && $_GET['clean'] === '1' ? ' --clean' : '';
        $artisanCmd = isset($_GET['artisan_cmd']) ? trim($_GET['artisan_cmd']) : '';
        if (!empty($artisanCmd)) {
            $remoteScript = "
                PHP_BIN=\"/www/server/php/83/bin/php -d extension=pgsql.so -d extension=pdo_pgsql.so\"
                cd {$srv['path']}
                echo '=== MENJALANKAN ARTISAN COMMAND: {$artisanCmd} ==='
                \$PHP_BIN artisan {$artisanCmd}
                echo 'DEPLOY_SUCCESS_FLAG'
            ";
        } elseif ($onlyImport) {
            $remoteScript = "
                set -e
                PHP_BIN=\"/www/server/php/83/bin/php -d extension=pgsql.so -d extension=pdo_pgsql.so\"
                cd {$srv['path']}
                echo '=== MENJALANKAN IMPORT {$importTitle} ==='
                \$PHP_BIN artisan {$importCmd}{$offtakeYear}{$offtakeMonth}{$offtakeLimit}{$runClean}
                echo 'DEPLOY_SUCCESS_FLAG'
            ";
        } else {
            $remoteScript = "
                set -e
                echo '0. Memastikan DNS Cluster di /etc/hosts (Membersihkan loopback)...'
            sed -i '/atk\.esa-solutions\.id/d' /etc/hosts 2>/dev/null || true
            sed -i '/akp\.esa-solutions\.id/d' /etc/hosts 2>/dev/null || true
            sed -i '/amk\.esa-solutions\.id/d' /etc/hosts 2>/dev/null || true
            sed -i '/api\.esa-solutions\.id/d' /etc/hosts 2>/dev/null || true
            echo '38.103.170.224 atk.esa-solutions.id' >> /etc/hosts
            echo '38.103.170.223 akp.esa-solutions.id' >> /etc/hosts
            echo '38.103.170.235 amk.esa-solutions.id api.esa-solutions.id' >> /etc/hosts

            echo 'DNS Check dulux.esa-solutions.id: '
            host dulux.esa-solutions.id || nslookup dulux.esa-solutions.id || true

            echo '1. Mengunduh kode terbaru dari GitHub...'
            if [ ! -d /root/att-admin-v12 ]; then
                git clone https://github.com/digitalgalery-dgsoft/attendaceesa.git /root/att-admin-v12
            fi
            cd /root/att-admin-v12
            git fetch origin main
            git reset --hard origin/main

            echo '1b. Memastikan database SQLite CBP & Dulux ter-ekstrak di SRC_DIR...'
            for gz in \$SRC_DIR/storage/app/dulux_data/*.sqlite.gz; do
                if [ -f \"\$gz\" ]; then
                    target=\"\${gz%.gz}\"
                    if [ ! -f \"\$target\" ] || [ \"\$gz\" -nt \"\$target\" ] || [ \$(wc -c < \"\$target\" 2>/dev/null || echo 0) -lt 50000000 ]; then
                        echo \"  ↳ Extracting \$gz -> \$target...\"
                        gzip -dc \"\$gz\" > \"\$target.tmp\" && mv -f \"\$target.tmp\" \"\$target\" && chmod 0666 \"\$target\" || true
                    fi
                fi
            done

            echo '=== DAFTAR DIREKTORI DI /www/wwwroot/ ==='
            ls -la /www/wwwroot/

            echo '=== VHOST NGINX UNTUK DULUX ==='
            grep -rn 'dulux.esa-solutions.id' /www/server/panel/vhost/nginx/ 2>/dev/null || true

            echo '2. Menyalin file ke {$srv['path']}...'
            SRC_DIR=\"/root/att-admin-v12\"
            if [ -d \"/root/att-admin-v12/att-admin-v12\" ]; then
                SRC_DIR=\"/root/att-admin-v12/att-admin-v12\"
            fi
            
            if [ -d '{$srv['path']}' ]; then
                \\cp -rf \$SRC_DIR/. {$srv['path']}/
                chmod -R 777 {$srv['path']}/storage {$srv['path']}/bootstrap/cache 2>/dev/null || true
                chown -R www:www {$srv['path']} 2>/dev/null || true
                
                # Ekstrak database SQLite CBP jika belum ada file uncompressed
                echo '2a. Memastikan database SQLite CBP & Dulux ter-ekstrak...'
                for gz in {$srv['path']}/storage/app/dulux_data/*.sqlite.gz; do
                    if [ -f \"\$gz\" ]; then
                        target=\"\${gz%.gz}\"
                        if [ ! -f \"\$target\" ] || [ \"\$gz\" -nt \"\$target\" ] || [ \$(wc -c < \"\$target\" 2>/dev/null || echo 0) -lt 50000000 ]; then
                            echo \"  ↳ Extracting \$gz -> \$target...\"
                            gzip -dc \"\$gz\" > \"\$target.tmp\" && mv -f \"\$target.tmp\" \"\$target\" && chmod 0666 \"\$target\" || true
                        fi
                    fi
                done
            fi

            # Sync ke semua website Laravel/aaPanel di /www/wwwroot/
            echo '2b. Menyinkronkan ke seluruh virtual host di /www/wwwroot/...'
            for site_dir in /www/wwwroot/*; do
                if [ -d \"\$site_dir\" ] && [ \"\$site_dir\" != \"\$SRC_DIR\" ]; then
                    if [ -f \"\$site_dir/artisan\" ] || [ -f \"\$site_dir/public/index.php\" ] || [ -d \"\$site_dir/app\" ]; then
                        echo \"  ↳ Syncing code ke: \$site_dir\"
                        \\cp -rf \$SRC_DIR/. \$site_dir/ 2>/dev/null || true
                        if [ ! -d \"\$site_dir/vendor\" ] && [ -d \"{$srv['path']}/vendor\" ]; then
                            ln -sfn {$srv['path']}/vendor \$site_dir/vendor 2>/dev/null || true
                        fi
                        if [ ! -f \"\$site_dir/.env\" ] && [ -f \"{$srv['path']}/.env\" ]; then
                            \\cp -f {$srv['path']}/.env \$site_dir/.env 2>/dev/null || true
                        fi
                        mkdir -p \$site_dir/storage/app/dulux_data
                        for gz in \$site_dir/storage/app/dulux_data/*.sqlite.gz; do
                            if [ -f \"\$gz\" ]; then
                                target=\"\${gz%.gz}\"
                                if [ ! -f \"\$target\" ] || [ \"\$gz\" -nt \"\$target\" ] || [ \$(wc -c < \"\$target\" 2>/dev/null || echo 0) -lt 50000000 ]; then
                                    echo \"  ↳ Extracting in \$site_dir: \$gz -> \$target...\"
                                    gzip -dc \"\$gz\" > \"\$target.tmp\" && mv -f \"\$target.tmp\" \"\$target\" && chmod 0666 \"\$target\" || true
                                fi
                            fi
                        done
                        for sq in {$srv['path']}/storage/app/dulux_data/*.sqlite; do
                            if [ -f \"\$sq\" ] && [ ! -f \"\$site_dir/storage/app/dulux_data/\$(basename \"\$sq\")\" ]; then
                                ln -sf \"\$sq\" \"\$site_dir/storage/app/dulux_data/\$(basename \"\$sq\")\" 2>/dev/null || \\cp -f \"\$sq\" \"\$site_dir/storage/app/dulux_data/\" 2>/dev/null || true
                            fi
                        done
                        chmod -R 777 \$site_dir/storage \$site_dir/bootstrap/cache 2>/dev/null || true
                        chown -R www:www \$site_dir 2>/dev/null || true
                        if [ -f \"\$site_dir/artisan\" ]; then
                            /www/server/php/83/bin/php \$site_dir/artisan optimize:clear 2>/dev/null || true
                        fi
                    fi
                fi
            done
            echo '=== CEK SINTAKS PHP SELURUH PrincipalPortalController.php ==='
            find /www/wwwroot -name 'PrincipalPortalController.php' -exec /www/server/php/83/bin/php -l {} \;

            # Pastikan ekstensi pgsql & pdo_pgsql aktif jika .so tersedia
            EXT_DIR=\$(/www/server/php/83/bin/php -r \"echo ini_get('extension_dir');\" 2>/dev/null || echo \"/www/server/php/83/lib/php/extensions/no-debug-non-zts-20230831\")
            if [ -f \"\$EXT_DIR/pdo_pgsql.so\" ] || [ -f \"/www/server/php/83/lib/php/extensions/no-debug-non-zts-20230831/pdo_pgsql.so\" ]; then
                if ! grep -q \"pdo_pgsql\" /www/server/php/83/etc/php.ini 2>/dev/null; then
                    echo \"extension=pgsql\" >> /www/server/php/83/etc/php.ini
                    echo \"extension=pdo_pgsql\" >> /www/server/php/83/etc/php.ini
                fi
            fi

            PHP_BIN=\"/www/server/php/83/bin/php -d extension=pgsql.so -d extension=pdo_pgsql.so\"
            echo \"Menggunakan PHP binary: \$PHP_BIN\"
            echo \"Verifikasi pgsql pada PHP CLI:\"
            \$PHP_BIN -m 2>/dev/null | grep -i pgsql || echo \"  PERINGATAN: pgsql belum terdeteksi\"

            echo '3. Merapikan aset Livewire & storage link...'
            cd {$srv['path']}
            \$PHP_BIN artisan storage:link 2>/dev/null || true
            \$PHP_BIN artisan livewire:publish --assets 2>/dev/null || true
            mkdir -p public/livewire
            \\cp -rf vendor/livewire/livewire/dist/* public/livewire/ 2>/dev/null || true
            rm -f public/hot

            echo '3b. Memeriksa ketersediaan file app-release.apk...'
            if [ ! -f \"{$srv['path']}/public/app-release.apk\" ] || [ \$(stat -c%s \"{$srv['path']}/public/app-release.apk\" 2>/dev/null || echo 0) -lt 10000000 ]; then
                echo '  ↳ Mengunduh file APK dari server master (appsend.my.id)...'
                curl -skL -m 120 'https://appsend.my.id/app-release.apk' -o \"{$srv['path']}/public/app-release.apk\" 2>/dev/null || true
                chmod 644 \"{$srv['path']}/public/app-release.apk\" 2>/dev/null || true
            fi
            if [ -d '/www/wwwroot/esa-solutions.id/public' ] && [ -f \"{$srv['path']}/public/app-release.apk\" ]; then
                cp -f \"{$srv['path']}/public/app-release.apk\" '/www/wwwroot/esa-solutions.id/public/app-release.apk' 2>/dev/null || true
                chmod 644 '/www/wwwroot/esa-solutions.id/public/app-release.apk' 2>/dev/null || true
            fi

            " . ($runMigration ? "echo '4. Menjalankan Database Migration...' && (\$PHP_BIN artisan migrate --force || true)\n" : "") . "
            " . ($runPrincipals ? "echo '4b. Menyinkronkan Relasi Principal...' && (\$PHP_BIN artisan reporting:link-principals || true)\n" : "") . "
            " . ($runImportOfftake ? "echo '4c. Mengimpor Data Offtake Dulux...' && (\$PHP_BIN artisan dulux:import-offtake{$offtakeYear}{$offtakeMonth}{$offtakeLimit} || true)\n" : "") . "

            echo '5. Membersihkan cache...'
            chmod -R 777 storage bootstrap/cache 2>/dev/null || true
            \$PHP_BIN artisan optimize:clear || true

            echo '6. Me-restart PHP-FPM...'
            /etc/init.d/php-fpm-83 restart 2>/dev/null || systemctl restart php-fpm-83 2>/dev/null || /etc/init.d/php-fpm-82 restart 2>/dev/null || systemctl restart php-fpm-82 2>/dev/null || true
            echo 'DEPLOY_SUCCESS_FLAG'
        ";
        }

        // Cek lokasi private key yang aman di dalam folder website (sesuai aturan open_basedir)
        $keyOptions = "";
        $baseDir = dirname(__DIR__);
        $possibleKeys = [
            $baseDir . '/storage/app/deploy_key/id_rsa',
            $baseDir . '/storage/app/deploy_key',
            $baseDir . '/.deploy_key/id_rsa',
            '/www/server/deploy_key/id_rsa',
        ];
        foreach ($possibleKeys as $pk) {
            if (!empty($pk) && @file_exists($pk) && @is_readable($pk)) {
                @chmod(dirname($pk), 0700);
                @chmod($pk, 0600);
                $keyOptions = "-i " . escapeshellarg($pk);
                break;
            }
        }

        // Eksekusi via SSH
        $escapedScript = escapeshellarg($remoteScript);
        $sshCmd = "ssh {$keyOptions} -o BatchMode=yes -o ConnectTimeout=10 -o StrictHostKeyChecking=no root@{$srv['ip']} {$escapedScript} 2>&1";

        $output = [];
        $returnVar = 0;
        
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = @proc_open($sshCmd, $descriptorspec, $pipes);

        if (is_resource($process)) {
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            
            $isRunning = true;
            $fullOutput = '';
            while ($isRunning) {
                $status = proc_get_status($process);
                $isRunning = $status['running'];
                
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);
                
                if (!empty($out)) {
                    $fullOutput .= $out;
                    echo htmlentities($out);
                    echo "<!--" . str_repeat(' ', 4096) . "-->";
                    @flush();
                }
                if (!empty($err)) {
                    $fullOutput .= $err;
                    echo "<span style=\"color: #ff5555;\">" . htmlentities($err) . "</span>";
                    echo "<!--" . str_repeat(' ', 4096) . "-->";
                    @flush();
                }
                usleep(50000);
            }

            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            if (!empty($out)) {
                $fullOutput .= $out;
                echo htmlentities($out);
            }
            if (!empty($err)) {
                $fullOutput .= $err;
                echo "<span style=\"color: #ff5555;\">" . htmlentities($err) . "</span>";
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnVar = proc_close($process);

            if ($returnVar === 0 || str_contains($fullOutput, 'DEPLOY_SUCCESS_FLAG')) {
                echo "<span style=\"color: #4caf50; font-weight: bold;\">✔ DEPLOY SELESAI PADA {$srv['name']}!</span>\n";
                
                // Ping test
                $ch = curl_init($srv['ping']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode == 200) {
                    echo "<span style=\"color: #4caf50;\">  ↳ Health Check Ping OK (HTTP 200)</span>\n";
                } else {
                    echo "<span style=\"color: #fbc02d;\">  ↳ Respon Ping: HTTP {$httpCode}</span>\n";
                }
                $successCount++;
            } else {
                echo "<span style=\"color: #ff5555; font-weight: bold;\">✘ GAGAL DEPLOY KE {$srv['name']}. Pastikan SSH Key sudah terpasang.</span>\n";
            }
        } else {
            echo "<span style=\"color: #ff5555;\">Gagal menjalankan proses SSH.</span>\n";
        }

        echo "<!--" . str_repeat(' ', 4096) . "-->";
        @flush();
    }

    echo "\n<span style=\"color: #00bcd4; font-weight: bold;\">======================================================================</span>\n";
    if ($successCount === count($servers)) {
        echo "<span style=\"color: #4caf50; font-weight: bold; font-size: 1.1em;\">🎉 SEMPURNA! Seluruh ({$successCount}/" . count($servers) . ") server production berhasil di-deploy & aktif!</span>\n";
    } else {
        echo "<span style=\"color: #fbc02d; font-weight: bold;\">⚠️ Selesai: {$successCount}/" . count($servers) . " server berhasil di-deploy.</span>\n";
    }
    echo "<span style=\"color: #00bcd4; font-weight: bold;\">======================================================================</span>\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESA Groups - 3 Server Production Deployer</title>
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 24px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #2d2d2d;
        }
        .title-area h1 { margin: 0; font-size: 1.4rem; color: #fff; display: flex; align-items: center; gap: 8px; }
        .title-area p { margin: 4px 0 0 0; font-size: 0.85rem; color: #888; }
        .server-badges {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .badge-card {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #4caf50;
            display: inline-block;
        }
        .options {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            background: #1a1a1a;
            padding: 10px 16px;
            border-radius: 6px;
            border: 1px solid #2a2a2a;
        }
        .options label { cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .btn-deploy {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 0.95rem;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            transition: all 0.2s;
        }
        .btn-deploy:hover { background: linear-gradient(135deg, #0369a1 0%, #075985 100%); }
        .btn-deploy:disabled { background: #333; color: #777; cursor: not-allowed; box-shadow: none; }
        .btn-dev {
            background: transparent;
            color: #888;
            border: 1px solid #333;
            padding: 10px 16px;
            font-size: 0.85rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-dev:hover { background: #222; color: #fff; }
        #terminal {
            flex-grow: 1;
            background-color: #0a0a0a;
            color: #d4d4d4;
            font-family: 'Courier New', Courier, monospace;
            padding: 16px;
            border-radius: 8px;
            overflow-y: auto;
            white-space: pre-wrap;
            border: 1px solid #222;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.8);
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="title-area">
            <h1>🚀 ESA Production Deployer (3 Server)</h1>
            <p>Deploy perubahan dari GitHub ke Server 1 (AMK), Server 2 (AKP), & Server 3 (ATK) secara bersamaan.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="deploy.php?token=<?= htmlspecialchars($secretToken) ?>" class="btn-dev">🖥️ Console Dev (appsend)</a>
            <button id="runBtn" class="btn-deploy" onclick="runProductionDeploy()">🚀 Deploy ke 3 Server Production</button>
        </div>
    </div>

    <div class="server-badges">
        <?php foreach ($servers as $s): ?>
            <div class="badge-card">
                <span class="badge-dot"></span>
                <div>
                    <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                    <span style="color: #888; font-size: 0.75rem;">IP: <?= $s['ip'] ?> | <?= $s['domain'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="options">
        <span style="font-weight: 600; color: #fff;">Opsi Tambahan:</span>
        <label>
            <input type="checkbox" id="optMigrate" checked> Jalankan Database Migration (<code>artisan migrate</code>)
        </label>
        <label>
            <input type="checkbox" id="optPrincipals" checked> Sinkronkan Relasi Principal (<code>artisan reporting:link-principals</code>)
        </label>
    </div>
    
    <div id="terminal">System Ready. Klik tombol "🚀 Deploy ke 3 Server Production" di kanan atas untuk memulai deployment.</div>

    <script>
        async function runProductionDeploy() {
            const btn = document.getElementById('runBtn');
            const terminal = document.getElementById('terminal');
            const optMigrate = document.getElementById('optMigrate').checked ? '1' : '0';
            const optPrincipals = document.getElementById('optPrincipals').checked ? '1' : '0';
            
            if (!confirm('Apakah Anda yakin ingin men-deploy kode terbaru dari GitHub ke SELURUH (3) SERVER PRODUCTION sekarang?')) {
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Sedang Deploying...';
            terminal.innerHTML = '<span style="color: #00bcd4;">Memulai koneksi deployment ke 3 server production...</span>\n';
            
            try {
                const targetUrl = window.location.pathname + '?token=<?= $secretToken ?>&migrate=' + optMigrate + '&principals=' + optPrincipals;
                const response = await fetch(targetUrl, {
                    method: 'POST'
                });
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    const chunk = decoder.decode(value, { stream: true });
                    terminal.innerHTML += chunk;
                    terminal.scrollTop = terminal.scrollHeight;
                }
            } catch (err) {
                terminal.innerHTML += '\n<span style="color: #ff5555;">[Error] Koneksi terputus: ' + err.message + '</span>';
            } finally {
                btn.disabled = false;
                btn.innerText = '🚀 Deploy ke 3 Server Production Lagi';
            }
        }
    </script>
</body>
</html>
