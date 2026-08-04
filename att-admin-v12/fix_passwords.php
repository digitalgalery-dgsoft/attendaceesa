<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::whereRaw('LENGTH(password) < 60')->get();
foreach ($users as $user) {
    echo 'Fixing user: ' . $user->email . "\n";
    $user->password = Illuminate\Support\Facades\Hash::make($user->password);
    $user->save();
}
echo "Done.\n";
