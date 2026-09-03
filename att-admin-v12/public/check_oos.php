<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if (($_GET['token'] ?? '') !== 'dgsoft_rahasia_123') {
    die('Unauthorized');
}

header('Content-Type: text/plain');

$templates = \App\Models\ReportTemplate::where('code', 'LIKE', '%OOS%')
    ->orWhere('code', 'LIKE', '%DULUX%')
    ->orderBy('id')
    ->get();

echo "=== DULUX TEMPLATES ON SERVER ===\n";
foreach ($templates as $t) {
    echo "ID: {$t->id} | Code: {$t->code} | Title: {$t->title} | Active: " . ($t->is_active ? 'YES' : 'NO') . " | Submissions: " . \App\Models\ReportSubmission::where('report_template_id', $t->id)->count() . "\n";
    $fields = \App\Models\ReportFormField::where('report_template_id', $t->id)->orderBy('order_index')->get();
    foreach ($fields as $f) {
        echo "   - [{$f->field_name}] ({$f->field_type}): {$f->field_label}\n";
    }
}
