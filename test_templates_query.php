<?php

require __DIR__ . '/att-admin-v12/vendor/autoload.php';
$app = require_once __DIR__ . '/att-admin-v12/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Principal;
use App\Models\ReportTemplate;

$dulux = Principal::where('name', 'LIKE', '%ICI PAINTS%')->first();
echo "Principal: " . ($dulux ? $dulux->name . " (ID: {$dulux->id}, Subdomain: {$dulux->subdomain})" : "NOT FOUND") . "\n";

$employee = Employee::where('principal_id', $dulux?->id)->first();
if (!$employee) {
    $employee = Employee::first();
}
echo "Sample Employee: " . ($employee ? $employee->full_name . " (ID: {$employee->id}, Principal ID: {$employee->principal_id})" : "NOT FOUND") . "\n";

$principalId = $employee ? $employee->principal_id : ($dulux ? $dulux->id : null);

$templatesQuery = ReportTemplate::with(['fields' => function ($q) {
    $q->orderBy('order_index', 'asc');
}])->where('is_active', true);

if ($principalId) {
    $principal = Principal::find($principalId);
    $allMatchingPrincipalIds = [$principalId];
    if ($principal && !empty($principal->subdomain)) {
        $allMatchingPrincipalIds = Principal::where('subdomain', $principal->subdomain)->pluck('id')->toArray();
    }
    echo "Matching Principal IDs: " . implode(',', $allMatchingPrincipalIds) . "\n";

    $templatesQuery->where(function ($q) use ($allMatchingPrincipalIds, $principalId) {
        $q->whereHas('principals', function ($pq) use ($allMatchingPrincipalIds) {
            $pq->whereIn('principals.id', $allMatchingPrincipalIds);
        })
        ->orWhereIn('principal_id', $allMatchingPrincipalIds)
        ->orWhere('principal_id', $principalId)
        ->orWhereNull('principal_id');
    });
}

$templates = $templatesQuery->orderBy('id', 'asc')->get();
echo "Total Found Templates: " . $templates->count() . "\n";
foreach ($templates as $t) {
    echo " - [{$t->code}] {$t->title} ({$t->fields->count()} fields)\n";
}
