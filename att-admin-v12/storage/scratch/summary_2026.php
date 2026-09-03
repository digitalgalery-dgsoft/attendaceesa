<?php

$dbPath = 'storage/app/dulux_data/offtake_2026.sqlite';
if (!file_exists($dbPath)) {
    die("Database not found: {$dbPath}\n");
}

$pdo = new PDO('sqlite:' . $dbPath);
$stmt = $pdo->query('SELECT month, count(*) as cnt, sum(volume_liter) as total_vol FROM offtake_raw GROUP BY month ORDER BY month');
echo "=== Month Breakdown in 2026 SQLite ===" . PHP_EOL;
$totalRows = 0;
$totalVol = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $totalRows += $row['cnt'];
    $totalVol += $row['total_vol'];
    echo "  Month " . str_pad($row['month'], 2, ' ', STR_PAD_LEFT) . ": " . number_format($row['cnt']) . " rows | " . number_format($row['total_vol'], 2, ',', '.') . " Liters" . PHP_EOL;
}
echo "Total Rows: " . number_format($totalRows) . " | Total Volume: " . number_format($totalVol, 2, ',', '.') . " Liters" . PHP_EOL;

$stmt2 = $pdo->query('SELECT count(DISTINCT name_store) as store_cnt, count(DISTINCT sap) as sap_cnt, count(DISTINCT sub_brand) as brand_cnt, count(DISTINCT trans_date) as date_cnt FROM offtake_raw');
$res2 = $stmt2->fetch(PDO::FETCH_ASSOC);
echo PHP_EOL . "Unique Stores: {$res2['store_cnt']} | Unique SAP: {$res2['sap_cnt']} | Unique Subbrands: {$res2['brand_cnt']} | Unique Dates: {$res2['date_cnt']}" . PHP_EOL;

$stmt3 = $pdo->query('SELECT sub_brand, count(*) as cnt, sum(volume_liter) as vol FROM offtake_raw GROUP BY sub_brand ORDER BY cnt DESC LIMIT 10');
echo PHP_EOL . "Top 10 Subbrands in 2026:" . PHP_EOL;
while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . str_pad($row['sub_brand'], 30) . ": " . number_format($row['cnt']) . " rows (" . number_format($row['vol'], 1) . " L)" . PHP_EOL;
}
