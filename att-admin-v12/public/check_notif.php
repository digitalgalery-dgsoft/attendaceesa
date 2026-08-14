<?php
// Simple check script - run via browser: /check_notif.php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<pre>";

// Check notifications table
$total = DB::table('notifications')->count();
echo "Total notifikasi di DB: $total\n\n";

$notifs = DB::table('notifications')->orderBy('created_at', 'desc')->take(10)->get();
foreach ($notifs as $n) {
    $data = json_decode($n->data, true);
    echo "ID       : {$n->id}\n";
    echo "Type     : {$n->notifiable_type} #{$n->notifiable_id}\n";
    echo "Read_at  : " . ($n->read_at ?? 'NULL (unread)') . "\n";
    echo "Title    : " . ($data['title'] ?? '-') . "\n";
    echo "Created  : {$n->created_at}\n";
    echo str_repeat('-', 50) . "\n";
}

// Check Users
echo "\nUser admins di DB:\n";
$users = DB::table('users')->select('id', 'name', 'email')->get();
foreach ($users as $u) {
    echo "  ID #{$u->id}: {$u->name} ({$u->email})\n";
}

echo "</pre>";
