<?php
$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/daily_maintenance.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);

echo "Top 10 latest submissions in 2026:\n";
$stmt = $pdo->query("SELECT submission_date, tanggal_report, store_name, sap_code, tl_name, machine_type, machine_no, dc_name, tinta_ok, pembersihan_all_ok FROM dm_raw WHERE year = 2026 ORDER BY tanggal_report DESC, id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo sprintf(
        "Tgl: %s | Toko: %-30s | TL: %-20s | Mesin: %-10s | Serial: %-18s | DC: %-15s | Tinta: %d | Bersih: %d\n",
        $r['tanggal_report'],
        substr($r['store_name'], 0, 30),
        substr($r['tl_name'], 0, 20),
        $r['machine_type'],
        $r['machine_no'],
        substr($r['dc_name'], 0, 15),
        $r['tinta_ok'],
        $r['pembersihan_all_ok']
    );
}

echo "\nCheck June 8, 2026 rows:\n";
$stmt = $pdo->query("SELECT submission_date, tanggal_report, store_name, sap_code, tl_name, machine_type, machine_no, dc_name FROM dm_raw WHERE tanggal_report = '08/06/2026' LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo sprintf(
        "Tgl: %s | Toko: %-30s | TL: %-20s | Mesin: %-10s | Serial: %-18s | DC: %s\n",
        $r['tanggal_report'],
        substr($r['store_name'], 0, 30),
        substr($r['tl_name'], 0, 20),
        $r['machine_type'],
        $r['machine_no'],
        $r['dc_name']
    );
}
