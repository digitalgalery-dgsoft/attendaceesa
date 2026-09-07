<?php
$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/daily_maintenance.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);

$stmt = $pdo->query("SELECT * FROM dm_raw WHERE machine_no LIKE '%/2026%' OR machine_no LIKE '%/2025%' LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count of machine_no having dates: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "--- ID: {$r['id']}, Year: {$r['year']}, Month: {$r['month']} ---\n";
    foreach ($r as $k => $v) {
        echo "  $k => $v\n";
    }
}
