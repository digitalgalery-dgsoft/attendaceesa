<?php
require __DIR__ . '/../att-admin-v12/vendor/autoload.php';
if (!enum_exists('SortDirection')) {
    enum SortDirection: string {
        case Ascending = 'asc';
        case Descending = 'desc';
    }
}
$app = require_once __DIR__ . '/../att-admin-v12/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['cache.default' => 'array']);

$controller = new \App\Http\Controllers\Portal\PrincipalPortalController();
$ref = new \ReflectionClass($controller);
$method = $ref->getMethod('calculateDailyMaintenanceDashboardData');
$method->setAccessible(true);

$template = (object)[
    'id' => 888, 
    'code' => 'RPT-DULUX-DAILY-MAINTENANCE', 
    'name' => 'Laporan Daily Maintenance POST & Mesin Tinting Dulux', 
    'title' => 'Laporan Daily Maintenance POST & Mesin Tinting Dulux',
    'fields' => collect([])
];
$tenantPrincipal = (object)[
    'id' => 18, 
    'name' => 'PT ICI PAINTS INDONESIA', 
    'portal_title' => 'Dulux One Report',
    'theme_color' => '#0F52BA',
    'logo_url' => null,
];

$dmData = $method->invoke($controller, $template, 1, 2026, 7, 2026, '', null, null, '', '', null, 1, 1, 50);

$tabs = ['summary', 'stores', 'raw'];

foreach ($tabs as $t) {
    $viewData = [
        'template' => $template,
        'tenantPrincipal' => $tenantPrincipal,
        'tenantPrincipalsAll' => collect([$tenantPrincipal]),
        'brandColor' => '#0F52BA',
        'activeTemplates' => collect([$template]),
        'submissions' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1),
        'totalTemplateSubmissions' => 0,
        'uniqueStores' => 0,
        'startMonth' => 1,
        'startYear' => 2026,
        'endMonth' => 7,
        'endYear' => 2026,
        'search' => '',
        'selectedRegion' => '',
        'selectedAreaId' => null,
        'selectedLocationId' => null,
        'selectedMachineType' => '',
        'selectedCategory' => '',
        'regions' => ['Bali Nusra', 'Central Java', 'East Java', 'Greater Jakarta', 'Kalimantan', 'West Java'],
        'areas' => [
            ['id' => 'Bandung', 'name' => 'Bandung', 'region' => 'West Java'],
            ['id' => 'Bekasi', 'name' => 'Bekasi', 'region' => 'Greater Jakarta']
        ],
        'workLocations' => [],
        'machineTypes' => ['D200', 'Discovery', 'Manual', 'X-Smart', 'Xprotint', 'Other'],
        'categories' => ['Bluestore', 'LSO', 'MTI', 'SSO'],
        'setting' => null,
        'dashboardConfig' => [],
        'widgetResults' => [],
        'isYtdReport' => false,
        'ytdData' => [],
        'isCbpReport' => false,
        'isOfftakeReport' => false,
        'isStockReport' => false,
        'isOosReport' => false,
        'isDailyMaintenanceReport' => true,
        'dailyMaintenanceData' => $dmData,
        'dmData' => $dmData,
        'activeTab' => $t,
    ];

    try {
        $rendered = view('portal.report_detail', $viewData)->render();
        echo "Tab [{$t}] rendered successfully! Length: " . strlen($rendered) . " bytes\n";
    } catch (\Throwable $e) {
        echo "Error rendering tab [{$t}]: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
