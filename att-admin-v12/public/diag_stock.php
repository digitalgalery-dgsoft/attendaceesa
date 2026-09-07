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

    echo "Testing calculateStockDashboardData with CACHE BYPASS...\n";
    $controller = new \App\Http\Controllers\Portal\PrincipalPortalController();
    $reflection = new \ReflectionClass($controller);
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

    echo "RESULT AFTER CACHE FLUSH:\n";
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
