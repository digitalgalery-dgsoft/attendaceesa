<?php

$sqlitePath = 'storage/app/dulux_data/stock_2026.sqlite';
$sqlite2025 = 'storage/app/dulux_data/stock_2025.sqlite';
$offtakePath = 'storage/app/dulux_data/offtake_2026.sqlite';

$pdo = new PDO("sqlite:" . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (file_exists($sqlite2025)) {
    $pdo->exec("ATTACH DATABASE '$sqlite2025' AS py_db;");
}
if (file_exists($offtakePath)) {
    $pdo->exec("ATTACH DATABASE '$offtakePath' AS offtake_db;");
}

// 1. Pivotable test
$stmt = $pdo->query("
    SELECT 
        sap, store_name, region, area,
        SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_vol,
        SUM(CASE WHEN brand = 'Catylac Smart Choice' THEN volume_liter ELSE 0 END) as catylac_sc_vol,
        SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as catylac_vol,
        SUM(volume_liter) as total_vol
    FROM stock_raw
    WHERE month BETWEEN 1 AND 7
    GROUP BY sap, store_name, region, area
    ORDER BY total_vol DESC
    LIMIT 3
");
$piv = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "=== PIVOTABLE TEST ===\n";
print_r($piv);

// 2. Summ test (with SCM)
$stmt = $pdo->query("
    SELECT 
        st.sap, st.store_name, st.region, st.area,
        st.dulux_stock, st.catylac_stock, st.total_stock,
        COALESCE(ot.dulux_offtake, 0) as dulux_offtake,
        COALESCE(ot.catylac_offtake, 0) as catylac_offtake,
        COALESCE(ot.total_offtake, 0) as total_offtake
    FROM (
        SELECT 
            sap, store_name, region, area,
            SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_stock,
            SUM(CASE WHEN brand IN ('Catylac', 'Catylac Smart Choice') THEN volume_liter ELSE 0 END) as catylac_stock,
            SUM(volume_liter) as total_stock
        FROM stock_raw
        WHERE month BETWEEN 1 AND 7
        GROUP BY sap, store_name, region, area
    ) st
    LEFT JOIN (
        SELECT 
            sap, name_store,
            SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_offtake,
            SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as catylac_offtake,
            SUM(volume_liter) as total_offtake
        FROM offtake_db.offtake_raw
        WHERE month BETWEEN 1 AND 7
        GROUP BY sap, name_store
    ) ot ON (st.sap = ot.sap AND st.sap != '') OR (st.store_name = ot.name_store)
    ORDER BY st.total_stock DESC
    LIMIT 3
");
$summ = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== SUMM TEST ===\n";
print_r($summ);

// 3. YTD Product Test
$stmt = $pdo->query("
    SELECT 
        b.brand,
        COALESCE(cy.total_vol, 0) as cy_vol,
        COALESCE(py.total_vol, 0) as py_vol
    FROM (
        SELECT DISTINCT brand FROM stock_raw
        UNION SELECT DISTINCT brand FROM py_db.stock_raw
    ) b
    LEFT JOIN (
        SELECT brand, SUM(volume_liter) as total_vol
        FROM stock_raw
        WHERE month <= 7
        GROUP BY brand
    ) cy ON b.brand = cy.brand
    LEFT JOIN (
        SELECT brand, SUM(volume_liter) as total_vol
        FROM py_db.stock_raw
        WHERE month <= 7
        GROUP BY brand
    ) py ON b.brand = py.brand
    ORDER BY cy_vol DESC
");
$ytdProd = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== YTD PRODUCT TEST ===\n";
print_r($ytdProd);

echo "\nPHP calculation test passed!\n";
