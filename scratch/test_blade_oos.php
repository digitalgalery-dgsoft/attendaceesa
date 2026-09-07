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
$method = $ref->getMethod('calculateOosDashboardData');
$method->setAccessible(true);

$template = (object)['id' => 999, 'code' => 'RPT-DULUX-OOS-SSO', 'name' => 'Laporan Out of Stock (OOS) Dulux & Catylac'];
$tenantPrincipal = (object)['id' => 18, 'name' => 'PT ICI PAINTS INDONESIA'];

$oosData = $method->invoke($controller, $template, 7, 2026, 7, 2026, '', null, null, 'ALL', false, null, 1, 1, 50);

$viewData = [
    'template' => $template,
    'tenantPrincipal' => $tenantPrincipal,
    'startMonth' => 7,
    'startYear' => 2026,
    'endMonth' => 7,
    'endYear' => 2026,
    'selectedRegion' => '',
    'selectedAreaId' => null,
    'selectedLocationId' => null,
    'selectedChannel' => 'ALL',
    'showNoOos' => false,
    'regions' => ['R1', 'R2', 'R3', 'R4'],
    'areas' => [],
    'workLocations' => [],
    'oosData' => $oosData,
    'activeTab' => 'raw',
    'search' => '',
];

try {
    $rendered = view('portal.partials.oos_dashboard', $viewData)->render();
    echo "Blade compiled & rendered successfully! Length: " . strlen($rendered) . " bytes\n";
    if (str_contains($rendered, 'oos-pagination-controls') && str_contains($rendered, 'btn-chan-filter')) {
        echo "Pagination controls and Status filter buttons present in rendered HTML!\n";
    }
} catch (\Throwable $e) {
    echo "Error rendering blade: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
