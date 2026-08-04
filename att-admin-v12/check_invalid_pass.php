<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('password', 'NOT LIKE', '$2y$%')->get();
foreach ($users as $user) {
    echo 'Fixing invalid password for user: ' . $user->email . ' (Length: ' . strlen($user->password) . ")\n";
    $user->password = Illuminate\Support\Facades\Hash::make($user->password ?? 'password123');
    $user->save();
}
echo "Done Users.\n";

$employees = App\Models\Employee::where('password', 'NOT LIKE', '$2y$%')->get();
foreach ($employees as $employee) {
    echo 'Fixing invalid password for employee: ' . $employee->email . ' (Length: ' . strlen($employee->password) . ")\n";
    $employee->password = Illuminate\Support\Facades\Hash::make($employee->password ?? 'password123');
    $employee->save();
}
echo "Done Employees.\n";
