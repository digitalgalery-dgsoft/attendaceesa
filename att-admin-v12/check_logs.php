<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$today = \Carbon\Carbon::today('Asia/Jakarta')->toDateString();
$logs = \App\Models\AttendanceLog::where('log_type', 'visit_in')
    ->whereDate('logged_at', $today)
    ->get();
foreach ($logs as $log) {
    echo "Log ID: {$log->id}, Employee: {$log->employee_id}, Metadata: " . json_encode($log->metadata) . "\n";
}
