<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fields = \App\Models\ReportFormField::whereHas('template', function($q) {
    $q->where('code', 'RPT-WINGS-PENJUALAN-HADIAH-01');
})->get();

foreach($fields as $f) {
    echo $f->field_name . ' (' . $f->field_label . ') => ' . $f->field_type . PHP_EOL;
}
