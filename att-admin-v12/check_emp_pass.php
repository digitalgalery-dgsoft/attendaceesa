<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employees = App\Models\Employee::whereRaw('LENGTH(password) < 60')->get();
foreach ($employees as $employee) {
    echo 'Fixing employee: ' . $employee->email . "\n";
    if ($employee->password) {
        $employee->password = Illuminate\Support\Facades\Hash::make($employee->password);
        $employee->save();
    }
}
echo "Done.\n";
