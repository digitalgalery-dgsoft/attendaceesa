<?php
require __DIR__ . '/../att-admin-v12/vendor/autoload.php';
$app = require_once __DIR__ . '/../att-admin-v12/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['cache.default' => 'array']);

$controller = new \App\Http\Controllers\Portal\PrincipalPortalController();
$ref = new \ReflectionClass($controller);
$method = $ref->getMethod('calculateOosDashboardData');
$method->setAccessible(true);

$template = (object)['id' => 999, 'code' => 'RPT-DULUX-OOS-SSO'];

// 1. Default (hide No OOS)
$res1 = $method->invoke($controller, $template, 7, 2026, 7, 2026, '', null, null, 'ALL', false, null, 1, 1, 10);
print_r(array_keys($res1['submissions']));
echo "Default (No OOS hidden) - Total Raw: " . $res1['submissions']['total'] . PHP_EOL;

// 2. Show No OOS
$res2 = $method->invoke($controller, $template, 7, 2026, 7, 2026, '', null, null, 'ALL', true, null, 1, 1, 10);
echo "Show No OOS - Total Raw: " . $res2['submissions']['total'] . PHP_EOL;

if (!empty($res1['submissions']['records'])) {
    echo "First 3 items (Default):\n";
    foreach (array_slice($res1['submissions']['records'], 0, 3) as $r) {
        echo " - " . $r['tanggal_oos'] . " | " . $r['store_name'] . " | " . $r['produk'] . " | " . $r['base_color'] . PHP_EOL;
    }
}
if (!empty($res2['submissions']['records'])) {
    echo "First 3 items (Show All):\n";
    foreach (array_slice($res2['submissions']['records'], 0, 3) as $r) {
        echo " - " . $r['tanggal_oos'] . " | " . $r['store_name'] . " | " . $r['produk'] . " | " . $r['base_color'] . PHP_EOL;
    }
}
