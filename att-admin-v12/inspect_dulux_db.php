<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$templates = App\Models\ReportTemplate::with(['fields', 'principals', 'positions', 'employees'])
    ->where('code', 'LIKE', '%DULUX%')
    ->orWhere('title', 'LIKE', '%Dulux%')
    ->get();

echo "Total Dulux Templates in DB: " . $templates->count() . "\n\n";

foreach ($templates as $t) {
    echo "ID: {$t->id} | Code: {$t->code}\n";
    echo "Title: {$t->title}\n";
    echo "Category: {$t->category}\n";
    echo "Report Days: " . json_encode($t->report_days) . "\n";
    echo "Assigned Positions: " . $t->positions->pluck('name')->implode(', ') . "\n";
    echo "Assigned Employees: " . $t->employees->pluck('full_name')->implode(', ') . "\n";
    echo "Total Fields: " . $t->fields->count() . "\n";
    foreach ($t->fields as $f) {
        echo "  - [{$f->field_type}] {$f->field_label} ({$f->field_name})" . ($f->is_required ? ' *' : '') . ($f->is_readonly ? ' [READONLY]' : '') . "\n";
        if (!empty($f->options)) {
            echo "      Options: " . implode(', ', array_slice($f->options, 0, 5)) . (count($f->options) > 5 ? ' ... (' . count($f->options) . ' options)' : '') . "\n";
        }
    }
    echo "--------------------------------------------------------\n\n";
}
