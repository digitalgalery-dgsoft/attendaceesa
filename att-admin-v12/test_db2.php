<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $constraints = \DB::select("SELECT conname, pg_get_constraintdef(c.oid) as def FROM pg_constraint c JOIN pg_namespace n ON n.oid = c.connamespace WHERE conrelid = 'visit_reports'::regclass");
    print_r($constraints);
} catch (\Exception $e) {
    echo $e->getMessage();
}
