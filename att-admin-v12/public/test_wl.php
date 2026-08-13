<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

$locations = \App\Models\WorkLocation::with('branch')->where('is_active', true)->get()->map(function ($loc) {
    $data = $loc->toArray();
    $data['area'] = $loc->branch ? $loc->branch->name : null;
    return $data;
});

header('Content-Type: application/json');
echo json_encode($locations);
