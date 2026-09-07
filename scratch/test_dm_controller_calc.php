<?php
$sqlitePath = "G:\\My File\\Project APlikasi Absensi\\New\\att-admin-v12\\storage\\app\\dulux_data\\daily_maintenance.sqlite";

$pdo = new PDO("sqlite:" . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sMonth = 1; $eMonth = 7; $sYear = 2026; $eYear = 2026;
$selectedRegion = ''; $selectedAreaName = null; $selectedStoreName = null;
$selectedMachineType = ''; $selectedCategory = ''; $search = null;
$storePage = 1; $rawPage = 1; $perPage = 50;

$where = [];
$params = [];

if ($sYear === $eYear) {
    $where[] = "year = ?";
    $params[] = $sYear;
    $where[] = "month >= ? AND month <= ?";
    $params[] = $sMonth;
    $params[] = $eMonth;
} else {
    $where[] = "((year = ? AND month >= ?) OR (year = ? AND month <= ?) OR (year > ? AND year < ?))";
    $params[] = $sYear; $params[] = $sMonth;
    $params[] = $eYear; $params[] = $eMonth;
    $params[] = $sYear; $params[] = $eYear;
}

if ($selectedRegion) {
    $where[] = "rsm_area = ?";
    $params[] = $selectedRegion;
}
if ($selectedAreaName) {
    $where[] = "area = ?";
    $params[] = $selectedAreaName;
}
if ($selectedStoreName) {
    $where[] = "store_name = ?";
    $params[] = $selectedStoreName;
}
if ($selectedMachineType) {
    $where[] = "machine_type = ?";
    $params[] = $selectedMachineType;
}
if ($selectedCategory) {
    $where[] = "category = ?";
    $params[] = $selectedCategory;
}
if ($search) {
    $where[] = "(store_name LIKE ? OR sap_code LIKE ? OR machine_no LIKE ? OR dc_name LIKE ? OR tl_name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$whereSql = implode(' AND ', $where);

// 1. KPIs
$kpiSql = "
    SELECT 
        COUNT(*) as total_submissions,
        COUNT(DISTINCT store_name) as total_stores,
        COUNT(DISTINCT machine_no) as total_machines,
        SUM(tinta_ok) as sum_tinta,
        SUM(CASE WHEN d200_nozzle_ok = 1 OR discovery_brush_ok = 1 OR manual_nozzle_ok = 1 THEN 1 ELSE 0 END) as sum_nozzle,
        SUM(CASE WHEN mix2win_steps_ok >= 10 THEN 1 ELSE 0 END) as sum_mix2win,
        SUM(pembersihan_all_ok) as sum_pembersihan
    FROM dm_raw
    WHERE $whereSql
";
$stmt = $pdo->prepare($kpiSql);
$stmt->execute($params);
$kpiRow = $stmt->fetch(PDO::FETCH_ASSOC);

$totSub = (int)($kpiRow['total_submissions'] ?? 0);
$kpis = [
    'total_submissions' => $totSub,
    'total_stores' => (int)($kpiRow['total_stores'] ?? 0),
    'total_machines' => (int)($kpiRow['total_machines'] ?? 0),
    'tinta_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_tinta'] / $totSub) * 100, 1) : 0,
    'nozzle_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_nozzle'] / $totSub) * 100, 1) : 0,
    'mix2win_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_mix2win'] / $totSub) * 100, 1) : 0,
    'pembersihan_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_pembersihan'] / $totSub) * 100, 1) : 0,
];

echo "KPIs Calculated:\n";
print_r($kpis);

// 2. Breakdown per Machine Type
$mTypeSql = "
    SELECT 
        machine_type,
        COUNT(*) as submissions,
        COUNT(DISTINCT store_name) as stores,
        COUNT(DISTINCT machine_no) as machines,
        ROUND(AVG(tinta_ok) * 100, 1) as avg_tinta,
        ROUND(AVG(pembersihan_all_ok) * 100, 1) as avg_clean
    FROM dm_raw
    WHERE $whereSql
    GROUP BY machine_type
    ORDER BY submissions DESC
";
$stmt = $pdo->prepare($mTypeSql);
$stmt->execute($params);
$byMachine = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nBreakdown by Machine Type:\n";
foreach ($byMachine as $bm) {
    echo " - {$bm['machine_type']}: {$bm['submissions']} submissions, {$bm['stores']} stores, {$bm['machines']} machines\n";
}

// 3. Store Matrix Pagination
$storeCountSql = "
    SELECT COUNT(*) FROM (
        SELECT store_name, machine_no FROM dm_raw WHERE $whereSql GROUP BY store_name, machine_no
    )
";
$stmt = $pdo->prepare($storeCountSql);
$stmt->execute($params);
$totalStoreMatrix = (int)$stmt->fetchColumn();

$storeOffset = ($storePage - 1) * $perPage;
$storeMatrixSql = "
    SELECT 
        store_name, sap_code, category, rsm_area, area,
        machine_type, machine_no,
        COUNT(*) as total_checks,
        MAX(tanggal_report) as last_date,
        SUM(tinta_ok) as tinta_ok_cnt,
        SUM(pembersihan_all_ok) as clean_ok_cnt,
        ROUND(AVG(tinta_ok) * 100, 1) as compliance_pct
    FROM dm_raw
    WHERE $whereSql
    GROUP BY store_name, machine_no
    ORDER BY total_checks DESC, store_name ASC
    LIMIT $perPage OFFSET $storeOffset
";
$stmt = $pdo->prepare($storeMatrixSql);
$stmt->execute($params);
$storeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nStore Matrix Total: {$totalStoreMatrix} rows. Sample first 3 rows:\n";
foreach (array_slice($storeRows, 0, 3) as $sr) {
    echo " - {$sr['store_name']} ({$sr['sap_code']}) | {$sr['machine_type']} | Checks: {$sr['total_checks']} | Last: {$sr['last_date']} | Rate: {$sr['compliance_pct']}%\n";
}
