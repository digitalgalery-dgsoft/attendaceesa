<?php

require __DIR__ . '/../att-admin-v12/vendor/autoload.php';
$app = require_once __DIR__ . '/../att-admin-v12/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['cache.default' => 'array']);

echo "Testing PrincipalPortalController Stock Methods...\n";

try {
    $controller = app(\App\Http\Controllers\Portal\PrincipalPortalController::class);

    $template = new \App\Models\ReportTemplate();
    $template->id = 1;
    $template->title = 'Laporan Stock End Dulux';
    $template->code = 'RPT-DULUX-STOCK-END';
    $template->fields = collect([]);

    echo "Template: {$template->title} ({$template->code})\n";

    // Test reflection to call protected calculateStockDashboardData
    $reflector = new \ReflectionClass($controller);
    $dashMethod = $reflector->getMethod('calculateStockDashboardData');
    $dashMethod->setAccessible(true);

    $stockData = $dashMethod->invoke($controller, $template, 1, 2026, 7, 2026, null, null, null, null, 1, 1, 1, 50);

    echo "Stock Data Calculated Successfully!\n";
    echo "- Active Months: " . count($stockData['months']) . "\n";
    echo "- Pivot Total Stores: " . $stockData['pivotable']['total_stores'] . "\n";
    echo "- Pivot Stores on page 1: " . count($stockData['pivotable']['rows']) . "\n";
    echo "- Grand Total All: " . number_format($stockData['pivotable']['grand_total_all'], 2) . " L\n";
    echo "- Summ Stores on page 1: " . count($stockData['summ']['rows']) . " (Total Stock: " . number_format($stockData['summ']['total_stock'], 2) . " L, Offtake: " . number_format($stockData['summ']['total_offtake'], 2) . " L, SCM: " . $stockData['summ']['avg_scm'] . " bln)\n";
    echo "- Raw Data Submissions on page 1: " . count($stockData['submissions']['rows']) . " (Total: {$stockData['submissions']['total']})\n";

    // Test YTD calculation
    $ytdMethod = $reflector->getMethod('calculateStockYtdData');
    $ytdMethod->setAccessible(true);
    $ytdData = $ytdMethod->invoke($controller, $template, 7, 2026, null, null, null, null);

    echo "Stock YTD Data Calculated Successfully!\n";
    echo "- Brand Details Count: " . count($ytdData['details']) . "\n";
    echo "- CY Total Volume: " . number_format($ytdData['total']['cy_volume'], 2) . " L\n";
    echo "- PY Total Volume: " . number_format($ytdData['total']['py_volume'], 2) . " L\n";
    echo "- Total YoY Growth: " . number_format($ytdData['total']['growth'], 2) . "%\n";
    echo "- Store Details Count: " . count($ytdData['stores']['details']) . "\n";
    echo "- Top 10 Stores: " . count($ytdData['stores']['top10']) . "\n";

    // Test blade view compilation
    $tenantPrincipal = (object)[
        'id' => 18,
        'name' => 'Dulux (PT ICI Paints Indonesia)',
        'subdomain' => 'dulux',
        'theme_color' => '#004B93'
    ];
    $tenantPrincipalsAll = collect([$tenantPrincipal]);
    $brandColor = '#004B93';
    $activeTemplates = collect([$template]);
    $submissions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
    $totalTemplateSubmissions = 0;
    $uniqueStores = 0;
    $startMonth = 1;
    $startYear = 2026;
    $endMonth = 7;
    $endYear = 2026;
    $search = '';
    $selectedRegion = null;
    $selectedAreaId = null;
    $selectedLocationId = null;
    $regions = ['R1', 'R2', 'R3', 'R4'];
    $areas = [];
    $workLocations = [];
    $setting = new \App\Models\Setting(['app_name' => 'Attendance ESA']);
    $dashboardConfig = [];
    $widgetResults = [];
    $isYtdReport = true;
    $isCbpReport = false;
    $isOfftakeReport = false;
    $isStockReport = true;
    $activeTab = 'pivot';

    $html = view('portal.partials.stock_dashboard', compact(
        'tenantPrincipal',
        'template',
        'stockData',
        'activeTab'
    ))->render();

    echo "Blade View Compiled and Rendered Successfully! HTML Length: " . strlen($html) . " bytes\n";
    if (strpos($html, 'Rekap Volume Stock Toko') !== false) {
        echo "Found 'Rekap Volume Stock Toko' in HTML output!\n";
    }
    if (strpos($html, 'Ringkasan SCM & Stock Bulanan') !== false) {
        echo "Found 'Ringkasan SCM & Stock Bulanan' in HTML output!\n";
    }
    if (strpos($html, 'Raw Data Submissions') !== false) {
        echo "Found 'Raw Data Submissions' in HTML output!\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
