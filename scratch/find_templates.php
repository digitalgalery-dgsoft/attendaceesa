<?php
require __DIR__ . '/../att-admin-v12/vendor/autoload.php';
$app = require_once __DIR__ . '/../att-admin-v12/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$templates = \App\Models\ReportTemplate::all();
foreach ($templates as $t) {
    echo "ID: {$t->id} | Code: {$t->code} | Name: {$t->name} | Fields Count: " . (is_array($t->fields) ? count($t->fields) : 0) . "\n";
}
