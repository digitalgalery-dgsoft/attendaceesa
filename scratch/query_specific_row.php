<?php
$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/daily_maintenance.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);

$stmt = $pdo->query("SELECT * FROM dm_raw WHERE sap_code = '328531' AND tanggal_report LIKE '%08/06/2026%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Rows for 328531 on 08/06/2026:\n";
foreach ($rows as $r) {
    foreach ($r as $k => $v) {
        echo "  $k => $v\n";
    }
}
