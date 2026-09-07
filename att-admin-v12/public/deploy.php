<?php
// Skrip Auto-Deploy Webhook Custom dengan UI Terminal
@ini_set('display_errors', 1);
@error_reporting(E_ALL);
@set_time_limit(300);
@ini_set('memory_limit', '256M');

// Anda dapat mengganti token rahasia ini
$secretToken = "dgsoft_rahasia_123";

if (!isset($_GET['token']) || $_GET['token'] !== $secretToken) {
    http_response_code(403);
    die("Akses Ditolak.");
}

// Info endpoint
if (isset($_GET['info']) && $_GET['token'] === $secretToken) {
    header('Content-Type: application/json');
    echo json_encode([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'apk_size' => file_exists('/www/wwwroot/appsend.my.id/public/app-release.apk') ? filesize('/www/wwwroot/appsend.my.id/public/app-release.apk') : 0,
        'apk_mtime' => file_exists('/www/wwwroot/appsend.my.id/public/app-release.apk') ? date('Y-m-d H:i:s', filemtime('/www/wwwroot/appsend.my.id/public/app-release.apk')) : null,
    ]);
    exit;
}

// Handler chunked upload untuk file APK besar
if (isset($_GET['chunk_upload']) && $_GET['token'] === $secretToken) {
    $chunkIndex = intval($_POST['chunk_index'] ?? 0);
    $totalChunks = intval($_POST['total_chunks'] ?? 1);
    $filename = basename($_POST['filename'] ?? 'app-release.apk');
    $tempDir = sys_get_temp_dir() . '/apk_chunks';
    if (!is_dir($tempDir)) @mkdir($tempDir, 0777, true);
    
    $chunkFile = $tempDir . '/' . $filename . '.part' . $chunkIndex;
    if (isset($_FILES['chunk']) && is_uploaded_file($_FILES['chunk']['tmp_name'])) {
        move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFile);
    } elseif (!empty($_POST['data'])) {
        file_put_contents($chunkFile, base64_decode($_POST['data']));
    }
    
    if ($chunkIndex === $totalChunks - 1) {
        $target1 = '/www/wwwroot/appsend.my.id/public/' . $filename;
        $target2 = '/www/wwwroot/appsend.my.id/' . $filename;
        $out = fopen($target1, 'wb');
        for ($i = 0; $i < $totalChunks; $i++) {
            $part = $tempDir . '/' . $filename . '.part' . $i;
            if (file_exists($part)) {
                $in = fopen($part, 'rb');
                while ($buff = fread($in, 1048576)) {
                    fwrite($out, $buff);
                }
                fclose($in);
                @unlink($part);
            }
        }
        fclose($out);
        @chmod($target1, 0644);
        @copy($target1, $target2);
        @chmod($target2, 0644);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'size' => filesize($target1), 'md5' => md5_file($target1)]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'chunk_saved', 'chunk' => $chunkIndex]);
    exit;
}

// Handler khusus upload file APK baru (single file)
if (isset($_GET['upload_apk']) && $_GET['upload_apk'] === '1' && isset($_FILES['apk'])) {
    $target1 = '/www/wwwroot/appsend.my.id/public/app-release.apk';
    $target2 = '/www/wwwroot/appsend.my.id/app-release.apk';
    if (move_uploaded_file($_FILES['apk']['tmp_name'], $target1)) {
        @chmod($target1, 0644);
        @copy($target1, $target2);
        @chmod($target2, 0644);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'size' => filesize($target1), 'md5' => md5_file($target1)]);
        exit;
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file upload']);
        exit;
    }
}

// Jika request adalah POST, jalankan proses deploy (streaming output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bersihkan semua output buffer yang mungkin aktif
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/octet-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no'); // Nginx
    header('X-LiteSpeed-Buffer: no'); // LiteSpeed
    
    // Kirim padding (HTML comment) untuk memaksa server me-render buffer pertama (minimum 4KB untuk Nginx/FPM)
    echo "<!--" . str_repeat(' ', 4096) . "-->\n";
    @flush();

    $commands = [
        'cd /www/wwwroot/appsend.my.id && git sparse-checkout init --cone',
        'cd /www/wwwroot/appsend.my.id && git sparse-checkout set att-admin-v12',
        'cd /www/wwwroot/appsend.my.id && git fetch origin main',
        'cd /www/wwwroot/appsend.my.id && git reset --hard origin/main',
        'cd /www/wwwroot/appsend.my.id && git pull origin main',
        'cp -a /www/wwwroot/appsend.my.id/att-admin-v12/. /www/wwwroot/appsend.my.id/',
        'for gz in /www/wwwroot/appsend.my.id/storage/app/dulux_data/*.sqlite.gz; do target="${gz%.gz}"; if [ -f "$gz" ]; then if [ ! -f "$target" ] || [ "$gz" -nt "$target" ] || [ $(wc -c < "$target" 2>/dev/null || echo 0) -lt 50000000 ]; then echo "Extracting $gz -> $target..."; gzip -dc "$gz" > "$target.tmp" && mv -f "$target.tmp" "$target" && chmod 0666 "$target" || true; fi; fi; done',
        'chmod -R 777 /www/wwwroot/appsend.my.id/storage 2>/dev/null || true',
        'chown -R www:www /www/wwwroot/appsend.my.id/storage 2>/dev/null || true',
        'ls -lh /www/wwwroot/appsend.my.id/storage/app/dulux_data/*.sqlite 2>&1 || true',
        '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan storage:link',
        '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan migrate --force',
        '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan reporting:link-principals',
        '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan cache:clear',
        '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan optimize:clear',
    ];

    if (isset($_GET['import_offtake']) && $_GET['import_offtake'] === '1') {
        $y = isset($_GET['year']) ? ' --year=' . intval($_GET['year']) : '';
        $m = isset($_GET['month']) ? ' --month=' . escapeshellarg($_GET['month']) : '';
        $lim = isset($_GET['limit']) ? ' --limit=' . intval($_GET['limit']) : '';
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan dulux:import-offtake' . $y . $m . $lim;
    }

    if (isset($_GET['import_cbp']) && $_GET['import_cbp'] === '1') {
        $y = isset($_GET['year']) ? ' --year=' . intval($_GET['year']) : '';
        $m = isset($_GET['month']) ? ' --month=' . escapeshellarg($_GET['month']) : '';
        $lim = isset($_GET['limit']) ? ' --limit=' . intval($_GET['limit']) : '';
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan dulux:import-cbp' . $y . $m . $lim;
    }

    if (isset($_GET['import_daily']) && $_GET['import_daily'] === '1') {
        $y = isset($_GET['year']) ? ' --year=' . intval($_GET['year']) : '';
        $m = isset($_GET['month']) ? ' --month=' . escapeshellarg($_GET['month']) : '';
        $lim = isset($_GET['limit']) ? ' --limit=' . intval($_GET['limit']) : '';
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan dulux:import-daily-maintenance' . $y . $m . $lim;
    }

    if (isset($_GET['clean_dulux_locations']) && $_GET['clean_dulux_locations'] === '1') {
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan dulux:clean-work-locations --force';
    }

    if (isset($_GET['artisan_cmd']) && !empty($_GET['artisan_cmd'])) {
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan ' . trim($_GET['artisan_cmd']);
    }

    if (isset($_GET['import_stock']) && $_GET['import_stock'] === '1') {
        $y = isset($_GET['year']) ? ' --year=' . intval($_GET['year']) : '';
        $m = isset($_GET['month']) ? ' --month=' . escapeshellarg($_GET['month']) : '';
        $lim = isset($_GET['limit']) ? ' --limit=' . intval($_GET['limit']) : '';
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan dulux:import-stock' . $y . $m . $lim;
    }

    if (isset($_GET['import_oos']) && $_GET['import_oos'] === '1') {
        $y = isset($_GET['year']) ? ' --year=' . intval($_GET['year']) : '';
        $m = isset($_GET['month']) ? ' --month=' . escapeshellarg($_GET['month']) : '';
        $lim = isset($_GET['limit']) ? ' --limit=' . intval($_GET['limit']) : '';
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan dulux:import-oos' . $y . $m . $lim;
    }

    if (isset($_GET['seed']) && $_GET['seed'] === '1') {
        $commands[] = '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/artisan db:seed --class=ReportTemplatePresetsSeeder --force';
    }

    if (isset($_GET['composer']) && $_GET['composer'] === '1') {
        array_splice($commands, 6, 0, [
            'cd /www/wwwroot/appsend.my.id && curl -sS https://getcomposer.org/installer | /www/server/php/83/bin/php',
            '/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/composer.phar install --working-dir=/www/wwwroot/appsend.my.id --no-dev --optimize-autoloader',
        ]);
    }

    foreach($commands as $cmd){
        // Format command agar terlihat seperti di terminal asli (tanpa menampilkan path cd yang panjang)
        $displayCmd = preg_replace('/^cd [^&]+ && /', '', $cmd);
        $displayCmd = str_replace('/www/server/php/83/bin/php /www/wwwroot/appsend.my.id/', 'php ', $displayCmd);
        
        echo "\n<span style=\"color: #6BE236;\">admin@server</span>:<span style=\"color: #729FCF;\">~/appsend</span>$ <span style=\"color: #FFFFFF;\">{$displayCmd}</span>\n";
        echo "<!--" . str_repeat(' ', 4096) . "-->"; // Padding flush
        @flush();

        // Menggunakan proc_open untuk stream output real-time
        $descriptorspec = [
           0 => ["pipe", "r"],  // stdin
           1 => ["pipe", "w"],  // stdout
           2 => ["pipe", "w"]   // stderr
        ];
        
        $process = @proc_open($cmd, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            
            $isRunning = true;
            while ($isRunning) {
                $status = proc_get_status($process);
                $isRunning = $status['running'];
                
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);
                
                if (!empty($out)) {
                    echo htmlentities($out);
                    echo "<!--" . str_repeat(' ', 4096) . "-->";
                    @flush();
                }
                if (!empty($err)) {
                    echo "<span style=\"color: #ff5555;\">" . htmlentities($err) . "</span>";
                    echo "<!--" . str_repeat(' ', 4096) . "-->";
                    @flush();
                }
                usleep(50000); // 50ms sleep
            }
            
            // Baca sisa output setelah proses selesai
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            if (!empty($out)) {
                echo htmlentities($out);
                echo "<!--" . str_repeat(' ', 4096) . "-->";
                @flush();
            }
            if (!empty($err)) {
                echo "<span style=\"color: #ff5555;\">" . htmlentities($err) . "</span>";
                echo "<!--" . str_repeat(' ', 4096) . "-->";
                @flush();
            }
            
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            @proc_close($process);
        } else {
            // Fallback
            $output = @shell_exec($cmd . " 2>&1");
            echo htmlentities(trim($output)) . "\n";
            echo "<!--" . str_repeat(' ', 4096) . "-->";
            @flush();
        }
    }
    echo "\n<span style=\"color: #6BE236;\">=== DEPLOYMENT SELESAI ===</span>\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Deploy Console</title>
    <style>
        body {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        h1 { margin: 0; font-size: 1.5rem; color: #fff; }
        button {
            background-color: #007acc;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        button:hover { background-color: #005999; }
        button:disabled { background-color: #555; cursor: not-allowed; }
        #terminal {
            flex-grow: 1;
            background-color: #000;
            padding: 15px;
            border-radius: 6px;
            overflow-y: auto;
            white-space: pre-wrap;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.5);
            font-size: 14px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>🚀 Deployment Console</h1>
        <button id="runBtn" onclick="runDeploy()">Run Deploy</button>
    </div>
    
    <div id="terminal">System Ready. Waiting to start deployment...</div>

    <script>
        async function runDeploy() {
            const btn = document.getElementById('runBtn');
            const terminal = document.getElementById('terminal');
            
            btn.disabled = true;
            btn.innerText = 'Deploying...';
            terminal.innerHTML = 'Starting deployment process...\n';
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST'
                });
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    const chunk = decoder.decode(value, { stream: true });
                    terminal.innerHTML += chunk;
                    terminal.scrollTop = terminal.scrollHeight; // Auto-scroll to bottom
                }
            } catch (err) {
                terminal.innerHTML += '\n<span style="color: #ff5555;">[Error] Connection lost or failed: ' + err.message + '</span>';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Run Deploy Again';
            }
        }
    </script>
</body>
</html>
