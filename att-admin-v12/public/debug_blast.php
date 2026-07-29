<?php
// Temporary debug endpoint - REMOVE AFTER TESTING
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: application/json');

$today = now()->toDateString();

$allInfos = \App\Models\BlastInfo::all();
$activeInfos = \App\Models\BlastInfo::where('is_active', true)
    ->whereDate('start_date', '<=', $today)
    ->whereDate('end_date', '>=', $today)
    ->get();

echo json_encode([
    'today' => $today,
    'total_in_db' => $allInfos->count(),
    'active_in_range' => $activeInfos->count(),
    'all_data' => $allInfos->toArray(),
    'controller_file' => file_exists(base_path('app/Http/Controllers/Api/BlastInfoController.php')) ? 'EXISTS' : 'MISSING',
    'controller_md5' => file_exists(base_path('app/Http/Controllers/Api/BlastInfoController.php')) ? md5_file(base_path('app/Http/Controllers/Api/BlastInfoController.php')) : 'N/A',
], JSON_PRETTY_PRINT);
