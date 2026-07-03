<?php
$models = [
    'Shift' => '-m',
    'EmployeeSchedule' => '-m',
    'Itinerary' => '-m',
    'ItineraryItem' => '-m',
    'Attendance' => '-m',
    'AttendanceLog' => '-m'
];

foreach ($models as $model => $flag) {
    echo "Creating $model...\n";
    $output = shell_exec("php artisan make:model $model $flag 2>&1");
    echo $output . "\n";
}
echo "Done.\n";
