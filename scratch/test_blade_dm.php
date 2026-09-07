<?php
require_once __DIR__ . '/../att-admin-v12/vendor/autoload.php';
$app = require_once __DIR__ . '/../att-admin-v12/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['cache.default' => 'array']);

$template = new \App\Models\ReportTemplate();
$template->id = 18;
$template->code = 'RPT-DULUX-DAILY-MAINTENANCE';
$template->title = 'Laporan Daily Maintenance POST & Mesin Tinting Dulux';

$controller = new \App\Http\Controllers\Portal\PrincipalPortalController();
$ref = new \ReflectionClass($controller);
$method = $ref->getMethod('calculateDailyMaintenanceDashboardData');
$method->setAccessible(true);

$dmData = $method->invoke($controller, $template, 1, 2026, 7, 2026, '', '', '', '', '', '', 1, 1, 50);

echo "KPI Total Submissions: " . ($dmData['kpis']['total_submissions'] ?? 0) . "\n";
echo "KPI Tinta Rate: " . ($dmData['kpis']['tinta_rate'] ?? 0) . "%\n";
echo "KPI Nozzle Rate: " . ($dmData['kpis']['nozzle_rate'] ?? 0) . "%\n";
echo "KPI Mix2Win Rate: " . ($dmData['kpis']['mix2win_rate'] ?? 0) . "%\n";
echo "KPI Pembersihan Rate: " . ($dmData['kpis']['pembersihan_rate'] ?? 0) . "%\n";

$html = view('portal.partials.daily_maintenance_dashboard', [
    'template' => $template,
    'tenantPrincipal' => (object)['id' => 18, 'name' => 'PT ICI PAINTS INDONESIA'],
    'activeTab' => 'summary',
    'startYear' => 2026,
    'dmData' => $dmData,
    'selectedRegion' => '',
    'selectedAreaId' => '',
    'selectedMachineType' => '',
    'selectedCategory' => '',
    'machineTypes' => ['D200', 'Discovery', 'Manual', 'X-Smart', 'Xprotint', 'Other'],
    'categories' => ['Bluestore', 'LSO', 'MTI', 'SSO'],
    'regions' => ['Greater Jakarta', 'West Java'],
    'areas' => [['id' => 'Bandung', 'name' => 'Bandung']],
    'workLocations' => [],
    'search' => ''
])->render();

echo "Rendered HTML length: " . strlen($html) . " bytes\n";
echo "SUCCESS!\n";
