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

// Jika request adalah POST, jalankan proses deploy (streaming output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Disable output buffering
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/octet-stream');
    header('Cache-Control: no-cache');
    
    $commands = [
        'cd /home/wabotbiz/dgsoft.web.id && git fetch origin main',
        'cd /home/wabotbiz/dgsoft.web.id && git reset --hard origin/main',
        'cd /home/wabotbiz/dgsoft.web.id && git clean -fd',
        'cd /home/wabotbiz/dgsoft.web.id && git pull origin main',
        'cp -a /home/wabotbiz/dgsoft.web.id/att-admin-v12/. /home/wabotbiz/dgsoft.web.id/',
        '/opt/cpanel/ea-php83/root/usr/bin/php /home/wabotbiz/dgsoft.web.id/artisan migrate --force',
        '/opt/cpanel/ea-php83/root/usr/bin/php /home/wabotbiz/dgsoft.web.id/artisan optimize:clear',
    ];

    foreach($commands as $cmd){
        echo "\n<span style=\"color: #6BE236;\">$</span> <span style=\"color: #729FCF;\">{$cmd}</span>\n";
        @flush();

        // Menggunakan proc_open untuk stream output real-time (lebih aman dari shell_exec)
        $descriptorspec = [
           0 => ["pipe", "r"],  // stdin
           1 => ["pipe", "w"],  // stdout
           2 => ["pipe", "w"]   // stderr
        ];
        
        $process = @proc_open($cmd, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            
            while (!feof($pipes[1]) || !feof($pipes[2])) {
                $out = fgets($pipes[1]);
                $err = fgets($pipes[2]);
                
                if ($out !== false) {
                    echo htmlentities($out);
                    @flush();
                }
                if ($err !== false) {
                    echo "<span style=\"color: #ff5555;\">" . htmlentities($err) . "</span>";
                    @flush();
                }
                usleep(10000); // 10ms sleep to prevent high CPU usage
            }
            
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            @proc_close($process);
        } else {
            // Fallback jika proc_open disabled
            $output = @shell_exec($cmd . " 2>&1");
            echo htmlentities(trim($output)) . "\n";
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
