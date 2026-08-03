<?php
// Skrip Auto-Deploy Webhook Custom
// Anda dapat mengganti token rahasia ini
$secretToken = "dgsoft_rahasia_123";

if (!isset($_GET['token']) || $_GET['token'] !== $secretToken) {
    http_response_code(403);
    die("Akses Ditolak.");
}

// Daftar perintah yang akan dijalankan persis seperti script manual Anda
$commands = [
    'cd /home/wabotbiz/dgsoft.web.id && git pull https://github.com/digitalgalery-dgsoft/attendaceesa.git main',
    'cp -a /home/wabotbiz/dgsoft.web.id/att-admin-v12/. /home/wabotbiz/dgsoft.web.id/',
    '/opt/cpanel/ea-php83/root/usr/bin/php /home/wabotbiz/dgsoft.web.id/artisan migrate --force',
    '/opt/cpanel/ea-php83/root/usr/bin/php /home/wabotbiz/dgsoft.web.id/artisan optimize:clear',
];

$output = '';
foreach($commands as $cmd){
    $output .= "<span style=\"color: #6BE236;\">\$</span> <span style=\"color: #729FCF;\">{$cmd}</span>\n";
    $output .= htmlentities(trim(shell_exec($cmd . " 2>&1"))) . "\n\n";
}

echo "<pre style=\"background-color: #222; color: #FFF; padding: 15px; border-radius: 5px;\">$output</pre>";
