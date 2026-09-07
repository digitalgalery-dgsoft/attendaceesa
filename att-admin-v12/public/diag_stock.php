<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

header('Content-Type: text/plain');

// Print last 5000 chars of laravel.log
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $content = file_get_contents($logPath);
    echo "=== LAST LOG ===\n" . substr($content, -4000) . "\n=== END LOG ===\n\n";
} else {
    echo "No log file found.\n";
}

try {
    $template = \App\Models\ReportTemplate::where('code', 'RPT-DULUX-STOCK-END')->first();
    echo "Template: {$template->code} (ID: {$template->id})\n";

    $controller = new \App\Http\Controllers\Portal\PrincipalPortalController();
    $reflection = new \ReflectionClass($controller);

    $startDate = \Carbon\Carbon::createFromDate(2026, 9, 1)->startOfMonth();
    $endDate   = \Carbon\Carbon::createFromDate(2026, 9, 1)->endOfMonth();
    $liveQueryMethod = $reflection->getMethod('getLiveSubmissionsQuery');
    $liveQueryMethod->setAccessible(true);
    $liveQuery = $liveQueryMethod->invokeArgs($controller, [$template, $startDate, $endDate, null, null, null, null]);
    
    echo "LiveQuery SQL: " . $liveQuery->toSql() . "\n";
    echo "LiveQuery Bindings: " . json_encode($liveQuery->getBindings()) . "\n";
    echo "LiveQuery Count: " . $liveQuery->count() . "\n";
    
    $liveSubs = $liveQuery->orderBy('submitted_at', 'desc')->get();
    echo "Retrieved liveSubs count: " . $liveSubs->count() . "\n";
    foreach ($liveSubs as $sub) {
        echo "--- Sub: {$sub->submission_code}, Status: {$sub->status}, Submitted: {$sub->submitted_at}, Loc: " . ($sub->workLocation?->name ?? 'null') . " ---\n";
        foreach ($sub->values as $val) {
            echo "  Field: [{$val->field_name}] Slug: [" . ($val->formField?->field_name ?? '') . "] Label: [" . ($val->formField?->field_label ?? '') . "] Val: " . ($val->value_number ?? $val->value_text ?? $val->value_json ?? '') . "\n";
        }
    }

    echo "\nTesting calculateStockDashboardData with CACHE BYPASS...\n";
    $method = $reflection->getMethod('calculateStockDashboardData');
    $method->setAccessible(true);

    // Clear cache first
    \Illuminate\Support\Facades\Cache::flush();

    $res = $method->invokeArgs($controller, [
        $template,
        9, 2026, 9, 2026,
        null, null, null,
        'ALL', null, 1, 1, 1, 50
    ]);

    echo "\nRESULT AFTER CACHE FLUSH:\n";
    echo "pivotable total_stores: " . ($res['pivotable']['total_stores'] ?? 'null') . "\n";
    echo "pivotable rows count: " . count($res['pivotable']['rows'] ?? []) . "\n";
    echo "summ total_stores: " . ($res['summ']['total_stores'] ?? 'null') . "\n";
    echo "summ rows count: " . count($res['summ']['rows'] ?? []) . "\n";
    echo "submissions total: " . ($res['submissions']['total'] ?? 'null') . "\n";
    echo "submissions rows count: " . count($res['submissions']['rows'] ?? []) . "\n";
    if (!empty($res['submissions']['rows'])) {
        echo "FIRST RAW ROW:\n";
        print_r($res['submissions']['rows'][0]);
    }
    if (!empty($res['pivotable']['rows'])) {
        echo "FIRST PIVOT ROW:\n";
        print_r($res['pivotable']['rows'][0]);
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
