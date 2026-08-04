<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::whereNull('password')->orWhere('password', '')->get();
foreach ($users as $user) {
    echo 'Fixing user null password: ' . $user->email . "\n";
    $user->password = Illuminate\Support\Facades\Hash::make('password123');
    $user->save();
}

$employees = App\Models\Employee::whereNull('password')->orWhere('password', '')->get();
foreach ($employees as $employee) {
    echo 'Fixing employee null password: ' . $employee->email . "\n";
    $employee->password = Illuminate\Support\Facades\Hash::make('password123');
    $employee->save();
}
echo "Done.\n";
