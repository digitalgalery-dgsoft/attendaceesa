<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

$request = Request::create('/api/attendance/history', 'GET', ['start_date' => '2026-06-23', 'end_date' => '2026-07-23']);
$request->headers->set('Authorization', 'Bearer 25|oqpkGtMEL3tQpKc1QRR4eexAwfXoej8XBkUS5ij143e7ae55');
$request->headers->set('Accept', 'application/json');

$response = app()->handle($request);
echo $response->getContent();
