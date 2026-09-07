<?php
$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/daily_maintenance.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);
$stmt = $pdo->query('PRAGMA table_info(dm_raw)');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Columns in dm_raw:\n";
foreach ($cols as $c) {
    echo " - " . $c['name'] . " (" . $c['type'] . ")\n";
}

echo "\nSample 5 rows:\n";
$stmt = $pdo->query('SELECT * FROM dm_raw LIMIT 5');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $i => $r) {
    echo "--- Row $i ---\n";
    foreach ($r as $k => $v) {
        echo "  $k => $v\n";
    }
}
