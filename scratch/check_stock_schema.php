<?php
$pdo = new PDO("sqlite:" . __DIR__ . "/../att-admin-v12/storage/app/dulux_data/stock_2026.sqlite");
$cols = $pdo->query("PRAGMA table_info(stock_raw)")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

$months = $pdo->query("SELECT DISTINCT month FROM stock_raw")->fetchAll(PDO::FETCH_COLUMN);
echo "Months in stock_raw: " . json_encode($months) . "\n";
