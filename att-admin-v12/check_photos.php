<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reports = \App\Models\VisitReport::latest()->take(5)->get(['id', 'photo_path'])->toArray();
print_r($reports);
