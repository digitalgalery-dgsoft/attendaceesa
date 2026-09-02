<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$principals = \App\Models\Principal::all();
foreach ($principals as $p) {
    $empQuery = \App\Models\Employee::where('principal_id', $p->id);
    $empCount = (clone $empQuery)->count();
    if ($empCount > 0) {
        $distinctAreaId = (clone $empQuery)->whereNotNull('area_id')->distinct()->count('area_id');
        $distinctBranchId = (clone $empQuery)->whereNotNull('branch_id')->distinct()->count('branch_id');
        $distinctWorkLocId = (clone $empQuery)->whereNotNull('work_location_id')->distinct()->count('work_location_id');
        
        // Count branches via branch_id
        $branchIds = (clone $empQuery)->whereNotNull('branch_id')->pluck('branch_id')->unique();
        $branchesWithArea = \App\Models\Branch::whereIn('id', $branchIds)->whereNotNull('area_id')->distinct()->count('area_id');

        echo "Principal #{$p->id} '{$p->name}':\n";
        echo "  Employees: {$empCount}\n";
        echo "  Employee.area_id distinct: {$distinctAreaId}\n";
        echo "  Employee.branch_id distinct: {$distinctBranchId}\n";
        echo "  Employee.work_location_id distinct: {$distinctWorkLocId}\n";
        echo "  Branches distinct areas: {$branchesWithArea}\n";
    }
}
unlink(__FILE__);
