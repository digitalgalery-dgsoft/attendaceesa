<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $count = \App\Models\Attendance::count();
    echo "Total attendances: " . $count . "\n";
    $latest = \App\Models\Attendance::orderBy('id', 'desc')->first();
    if ($latest) {
        echo "Latest attendance ID: " . $latest->id . "\n";
        echo "Latest checkin: " . $latest->checkin_at . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
