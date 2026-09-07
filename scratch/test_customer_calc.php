<?php

$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/customer_db.sqlite';
$pdo = new PDO("sqlite:" . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Test Basic KPIs
$sYear = 2025;
$eYear = 2026;
$sMonth = 1;
$eMonth = 12;

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

$whereSql = implode(' AND ', $where);

// 1. KPIs
$kpiSql = "
    SELECT 
        COUNT(*) as total_records,
        COALESCE(SUM(value_pembelian), 0) as total_value,
        COALESCE(AVG(value_pembelian), 0) as avg_basket_size,
        COUNT(DISTINCT store_name) as unique_stores,
        COUNT(DISTINCT nama_dc) as unique_dcs,
        COALESCE(SUM(is_switched), 0) as switched_cnt,
        COALESCE(SUM(is_dulux_bought), 0) as dulux_bought_cnt
    FROM cust_raw
    WHERE $whereSql
";
$stmt = $pdo->prepare($kpiSql);
$stmt->execute($params);
$kpis = $stmt->fetch(PDO::FETCH_ASSOC);

$tot = (int)$kpis['total_records'];
$kpis['switched_pct'] = $tot > 0 ? round(((int)$kpis['switched_cnt'] / $tot) * 100, 1) : 0;
$kpis['dulux_bought_pct'] = $tot > 0 ? round(((int)$kpis['dulux_bought_cnt'] / $tot) * 100, 1) : 0;

echo "KPIs: " . json_encode($kpis, JSON_PRETTY_PRINT) . "\n";

// 2. Customer Types
$typeSql = "
    SELECT 
        tipe_pelanggan,
        COUNT(*) as total_count,
        ROUND(COUNT(*) * 100.0 / $tot, 1) as pct,
        COALESCE(SUM(value_pembelian), 0) as total_val,
        COALESCE(AVG(value_pembelian), 0) as avg_val
    FROM cust_raw
    WHERE $whereSql
    GROUP BY tipe_pelanggan
    ORDER BY total_count DESC
";
$stmt = $pdo->prepare($typeSql);
$stmt->execute($params);
echo "\nCustomer Types:\n" . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";

// 3. Reasons
$reasonSql = "
    SELECT 
        alasan,
        COUNT(*) as total_count,
        ROUND(COUNT(*) * 100.0 / $tot, 1) as pct
    FROM cust_raw
    WHERE $whereSql AND alasan IS NOT NULL AND alasan != ''
    GROUP BY alasan
    ORDER BY total_count DESC
";
$stmt = $pdo->prepare($reasonSql);
$stmt->execute($params);
echo "\nReasons:\n" . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";

// 4. Regional Breakdown
$rsmSql = "
    SELECT 
        rsm_area,
        COUNT(*) as total_count,
        COUNT(DISTINCT store_name) as stores,
        COUNT(DISTINCT nama_dc) as dcs,
        COALESCE(SUM(value_pembelian), 0) as total_val,
        COALESCE(AVG(value_pembelian), 0) as avg_val,
        ROUND(COUNT(*) * 100.0 / $tot, 1) as pct
    FROM cust_raw
    WHERE $whereSql
    GROUP BY rsm_area
    ORDER BY total_count DESC
";
$stmt = $pdo->prepare($rsmSql);
$stmt->execute($params);
echo "\nRegional Breakdown:\n" . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";
