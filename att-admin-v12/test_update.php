<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $attendance = \App\Models\Attendance::find(437);
    if (!$attendance) {
        echo "Attendance 437 not found.\n";
        exit;
    }
    
    $attendance->update([
        'checkout_at' => '2026-08-03 10:03:08',
        'checkout_log_id' => 478,
        'work_duration_minutes' => 22
    ]);
    echo "Update successful.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
        echo "Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}
