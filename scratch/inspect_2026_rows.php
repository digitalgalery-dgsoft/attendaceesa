<?php
$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/daily_maintenance.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);

echo "2026 sample rows:\n";
$stmt = $pdo->query('SELECT * FROM dm_raw WHERE year = 2026 ORDER BY id DESC LIMIT 10');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $i => $r) {
    echo "--- 2026 Row $i (id={$r['id']}, store={$r['store_name']}) ---\n";
    foreach ($r as $k => $v) {
        echo "  $k => $v\n";
    }
}
