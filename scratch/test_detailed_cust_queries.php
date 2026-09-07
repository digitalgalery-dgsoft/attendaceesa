<?php

$sqlitePath = __DIR__ . '/../att-admin-v12/storage/app/dulux_data/customer_db.sqlite';
$pdo = new PDO("sqlite:" . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$whereSql = "1=1";
$params = [];

// 1. Store rankings
$stmt = $pdo->prepare("
    SELECT 
        store_name, sap_code, rsm_area, area,
        COUNT(*) as total_customers,
        COALESCE(SUM(value_pembelian), 0) as total_val,
        COALESCE(AVG(value_pembelian), 0) as avg_val,
        COALESCE(SUM(is_switched), 0) as switched_cnt,
        COUNT(DISTINCT nama_dc) as total_dcs
    FROM cust_raw
    WHERE $whereSql
    GROUP BY store_name
    ORDER BY total_val DESC, total_customers DESC
    LIMIT 5
");
$stmt->execute($params);
echo "Top 5 Stores by Value:\n" . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";

// 2. DC rankings
$stmt = $pdo->prepare("
    SELECT 
        nama_dc,
        MIN(store_name) as store_name,
        MIN(rsm_area) as rsm_area,
        COUNT(*) as total_customers,
        COALESCE(SUM(value_pembelian), 0) as total_val,
        COALESCE(SUM(is_switched), 0) as switched_cnt
    FROM cust_raw
    WHERE $whereSql AND nama_dc IS NOT NULL AND nama_dc != ''
    GROUP BY nama_dc
    ORDER BY total_customers DESC
    LIMIT 5
");
$stmt->execute($params);
echo "\nTop 5 DCs by Customers:\n" . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";

// 3. Brand Sought vs Brand Bought Top
$stmt = $pdo->prepare("
    SELECT 
        brand_dicari,
        COUNT(*) as cnt
    FROM cust_raw
    WHERE $whereSql AND brand_dicari IS NOT NULL AND brand_dicari != ''
    GROUP BY brand_dicari
    ORDER BY cnt DESC
    LIMIT 6
");
$stmt->execute($params);
echo "\nTop 6 Brands Sought:\n" . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";
