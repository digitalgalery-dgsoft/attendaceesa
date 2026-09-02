<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRINCIPALS ===" . PHP_EOL;
foreach (\App\Models\Principal::all() as $p) {
    echo "ID: {$p->id} | Name: {$p->name} | Subdomain: {$p->subdomain}" . PHP_EOL;
}

echo PHP_EOL . "=== PRODUCTS (Total: " . \App\Models\Product::count() . ") ===" . PHP_EOL;
foreach (\App\Models\Product::with('principal')->get() as $pr) {
    $prinName = $pr->principal ? $pr->principal->name : 'No Principal';
    echo "ID: {$pr->id} | Principal: {$prinName} ({$pr->principal_id}) | Name: {$pr->name} | Category: {$pr->category} | Active: " . ($pr->is_active ? 'YES' : 'NO') . PHP_EOL;
}

echo PHP_EOL . "=== REPORT TEMPLATES (Total: " . \App\Models\ReportTemplate::count() . ") ===" . PHP_EOL;
foreach (\App\Models\ReportTemplate::with(['principals', 'products'])->get() as $rt) {
    $prinNames = $rt->principals->pluck('name')->implode(', ');
    $prodCount = $rt->products->count();
    echo "ID: {$rt->id} | Title: {$rt->title} | Code: {$rt->code} | Principal ID: {$rt->principal_id} | Principals: [{$prinNames}] | Products Count: {$prodCount}" . PHP_EOL;
}
