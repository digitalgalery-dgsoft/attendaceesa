<?php
// Quick debug script
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;

$today = Carbon::today()->toDateString();
echo "Today: $today\n";

$emp = Employee::where('name', 'like', '%Abdurrahman%')->first();
if (!$emp) {
    echo "Employee not found!\n";
    exit;
}
echo "Employee ID: {$emp->id}, Name: {$emp->name}\n";

$schedule = EmployeeSchedule::where('employee_id', $emp->id)
    ->where('schedule_date', $today)
    ->first();
echo "Schedule today: " . ($schedule ? json_encode($schedule->toArray()) : 'NULL - TIDAK ADA JADWAL') . "\n";

$itinerary = Itinerary::where('employee_id', $emp->id)
    ->where('date', $today)
    ->with('items')
    ->first();
echo "Itinerary today: " . ($itinerary ? json_encode($itinerary->toArray()) : 'NULL') . "\n";

// Check all itineraries for this month
$itineraries = Itinerary::where('employee_id', $emp->id)
    ->whereYear('date', 2026)
    ->whereMonth('date', 7)
    ->orderBy('date')
    ->get();
echo "\nAll July itineraries (" . $itineraries->count() . " records):\n";
foreach ($itineraries as $itin) {
    echo "  - {$itin->date} | status: {$itin->status}\n";
}
