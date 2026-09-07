<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['cache.default' => 'array']);

use App\Models\ReportTemplate;
use App\Http\Controllers\Portal\PrincipalPortalController;

echo "=== TESTING DULUX OOS DASHBOARD CALCULATION ===\n";

$template = (object)[
    'id' => 18,
    'code' => 'RPT-DULUX-OOS-SSO',
    'title' => 'Laporan Out of Stock (OOS) Dulux',
    'category' => 'stock',
    'fields' => collect([])
];
echo "Template mock: {$template->title} ({$template->code})\n";

// Use reflection to invoke protected calculateOosDashboardData
$controller = new PrincipalPortalController();
$reflector = new ReflectionClass($controller);
$method = $reflector->getMethod('calculateOosDashboardData');
$method->setAccessible(true);

echo "\n--- 1. Testing Month 7 (July 2026), Channel: ALL ---\n";
$dataAll = $method->invoke($controller, $template, 7, 2026, 7, 2026, null, null, null, 'ALL', null, 1, 1, 50);

echo "KPIs:\n";
print_r($dataAll['kpis']);

echo "\nReasons Count: " . count($dataAll['reasons']) . "\n";
foreach (array_slice($dataAll['reasons'], 0, 5) as $r) {
    echo " - {$r['reason']}: {$r['store_count']} stores, {$r['incident_count']} cases ({$r['percentage']}%)\n";
}

echo "\nWeekly Rows Count: " . count($dataAll['weekly']['rows']) . " (Total rows in DB: {$dataAll['weekly']['total_rows']}, Total cases: {$dataAll['weekly']['grand_total_cases']})\n";
echo "Active Weeks: " . implode(', ', $dataAll['weeks']) . "\n";
if (!empty($dataAll['weekly']['rows'])) {
    $firstRow = $dataAll['weekly']['rows'][0];
    echo "Sample Weekly Row 1:\n";
    echo " - Store: {$firstRow['store_name']} (SAP: {$firstRow['sap']}, Reg: {$firstRow['region']}, Area: {$firstRow['area']}, Chan: {$firstRow['channel']})\n";
    echo " - Product: {$firstRow['produk']}, Base: {$firstRow['base_color']}, Kemasan: {$firstRow['kemasan_size']}\n";
    echo " - Reason: {$firstRow['alasan_oos']}\n";
    echo " - Week counts: " . json_encode($firstRow['weeks']) . ", Total Cases: {$firstRow['total_cases']}\n";
}

echo "\nSubmissions Raw Count: " . count($dataAll['submissions']['rows']) . " (Total submissions in DB: {$dataAll['submissions']['total']})\n";
if (!empty($dataAll['submissions']['rows'])) {
    $sub1 = $dataAll['submissions']['rows'][0];
    echo "Sample Submission 1:\n";
    echo " - Tanggal OOS: {$sub1['tanggal_oos']}, Week: {$sub1['week']}, Channel: {$sub1['channel']}\n";
    echo " - Store: {$sub1['store_name']} (SAP: {$sub1['sap']}, Area: {$sub1['area']})\n";
    echo " - Product: {$sub1['produk']}, Alasan: {$sub1['alasan_oos']}, Is OOS: {$sub1['is_oos']}\n";
}

echo "\n--- 2. Testing Blade View Compilation ---\n";
if (!enum_exists('SortDirection') && !enum_exists('Illuminate\Database\Eloquent\SortDirection')) {
    enum SortDirection: string { case Ascending = 'asc'; case Descending = 'desc'; }
}
try {
    $rendered = view('portal.partials.oos_dashboard', [
        'oosData' => $dataAll,
        'template' => $template,
        'tenantPrincipal' => (object)['id' => 18, 'name' => 'AkzoNobel Dulux Indonesia'],
        'activeTab' => 'summary'
    ])->render();
    echo "SUCCESS: portal.partials.oos_dashboard rendered successfully! (HTML length: " . strlen($rendered) . " bytes)\n";
} catch (\Throwable $e) {
    echo "ERROR compiling blade view: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== ALL OOS DASHBOARD TESTS PASSED! ===\n";
