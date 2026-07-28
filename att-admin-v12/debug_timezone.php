<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Attendance;
use App\Models\AttendanceLog;
use Carbon\Carbon;

$employeeId = 30;

// HISTORY LOGIC
$now = Carbon::now('Asia/Jakarta');
if ($now->day >= 26) {
    $startDate = $now->copy()->startOfDay();
    $endDate   = $now->copy()->addMonth()->setDay(25)->endOfDay();
} else {
    $startDate = $now->copy()->subMonth()->setDay(26)->startOfDay();
    $endDate   = $now->copy()->setDay(25)->endOfDay();
}

$attendances = Attendance::where('employee_id', $employeeId)
    ->whereBetween('attendance_date', [
        $startDate->toDateString(),
        $endDate->toDateString(),
    ])
    ->orderBy('attendance_date', 'desc')
    ->get();

echo "ATTENDANCES COUNT: " . $attendances->count() . "\n";

$logs = AttendanceLog::where('employee_id', $employeeId)
    ->whereBetween('logged_at', [$startDate, $endDate])
    ->orderBy('logged_at', 'asc')
    ->get();

echo "LOGS COUNT: " . $logs->count() . "\n";

$todayLocal = Carbon::now('Asia/Jakarta')->toDateString();
$todayStart = Carbon::createFromFormat('Y-m-d', $todayLocal, 'Asia/Jakarta')->startOfDay()->utc();
$todayEnd   = Carbon::createFromFormat('Y-m-d', $todayLocal, 'Asia/Jakarta')->endOfDay()->utc();

echo "TODAY START: " . $todayStart->toDateTimeString() . "\n";
echo "TODAY END: " . $todayEnd->toDateTimeString() . "\n";

$todayLogs = AttendanceLog::where('employee_id', $employeeId)
    ->whereBetween('logged_at', [$todayStart, $todayEnd])
    ->orderBy('id', 'desc')
    ->get();

echo "TODAY LOGS COUNT: " . $todayLogs->count() . "\n";
