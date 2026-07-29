<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = now()->toDateString();
echo "Today: $today\n\n";

// 1. Check all blast_infos
$allInfos = \App\Models\BlastInfo::all();
echo "=== ALL BlastInfos in DB: " . $allInfos->count() . " ===\n";
foreach ($allInfos as $info) {
    echo "ID:{$info->id} | title:{$info->title} | target:{$info->target_type} | active:{$info->is_active} | start:{$info->start_date} | end:{$info->end_date} | dept_id:{$info->department_id}\n";
}

echo "\n=== Active BlastInfos (filtered by date) ===\n";
$activeInfos = \App\Models\BlastInfo::where('is_active', true)
    ->whereDate('start_date', '<=', $today)
    ->whereDate('end_date', '>=', $today)
    ->get();
echo "Count: " . $activeInfos->count() . "\n";
foreach ($activeInfos as $info) {
    echo "ID:{$info->id} | title:{$info->title} | target:{$info->target_type}\n";
}

// 2. Test API response with a real employee
echo "\n=== Testing API with first employee ===\n";
$employee = \App\Models\Employee::where('is_active', true)->first();
if ($employee) {
    echo "Employee: {$employee->name} (email: {$employee->email}, dept_id: {$employee->department_id})\n";
    
    $request = \Illuminate\Http\Request::create('/api/blast-infos', 'GET');
    $request->setUserResolver(fn() => $employee);
    
    $controller = app(\App\Http\Controllers\Api\BlastInfoController::class);
    $response = $controller->index($request);
    echo "API Response: " . $response->getContent() . "\n";
} else {
    echo "No active employee found!\n";
}

// 3. Check table structure
echo "\n=== Table columns ===\n";
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('blast_infos');
echo implode(', ', $columns) . "\n";
