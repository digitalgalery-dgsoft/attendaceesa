<?php

$bladeContent = file_get_contents(__DIR__ . '/../att-admin-v12/resources/views/portal/partials/customer_database_dashboard.blade.php');
echo "Checking PHP syntax in customer_database_dashboard.blade.php... Length: " . strlen($bladeContent) . " bytes\n";

$tmpFile = __DIR__ . '/../att-admin-v12/storage/framework/views/test_cust_blade.php';
file_put_contents($tmpFile, $bladeContent);

$output = [];
$returnVar = 0;
exec("php -l \"$tmpFile\"", $output, $returnVar);

echo implode("\n", $output) . "\n";
@unlink($tmpFile);

if ($returnVar === 0) {
    echo "Blade syntax check PASSED!\n";
} else {
    echo "Blade syntax check FAILED with code $returnVar\n";
}
