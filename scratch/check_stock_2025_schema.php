<?php
$pdo = new PDO("sqlite:" . __DIR__ . "/../att-admin-v12/storage/app/dulux_data/stock_2025.sqlite");
$cols = $pdo->query("PRAGMA table_info(stock_raw)")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

$stmt = $pdo->query("SELECT * FROM stock_raw LIMIT 2");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
