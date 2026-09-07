<?php
$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/daily_maintenance.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);

$stmt = $pdo->query("SELECT * FROM dm_raw WHERE sap_code = '328531' OR store_name LIKE '%SUGENG%' LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Rows for 328531 / SUGENG:\n";
foreach ($rows as $r) {
    echo "--- ID: {$r['id']} ---\n";
    foreach ($r as $k => $v) {
        echo "  $k => $v\n";
    }
}
