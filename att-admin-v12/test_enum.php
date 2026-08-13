<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$conn = DB::connection('pgsql');
$constraints = $conn->select("SELECT conname FROM pg_constraint WHERE conrelid = 'visit_reports'::regclass AND contype = 'c'");
print_r($constraints);
