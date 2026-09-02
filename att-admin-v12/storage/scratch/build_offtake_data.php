<?php

ini_set('memory_limit', '1024M');
set_time_limit(600);

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
$zip = new ZipArchive();
if ($zip->open($filePath) !== true) {
    die("Cannot open zip\n");
}

// 1. Load Shared Strings
$sharedStrings = [];
$ssContent = $zip->getFromName('xl/sharedStrings.xml');
if ($ssContent) {
    $xml = simplexml_load_string($ssContent);
    foreach ($xml->si as $si) {
        if (isset($si->t)) {
            $sharedStrings[] = (string) $si->t;
        } elseif (isset($si->r)) {
            $text = '';
            foreach ($si->r as $r) $text .= (string) $r->t;
            $sharedStrings[] = $text;
        } else {
            $sharedStrings[] = '';
        }
    }
}
echo "Loaded " . count($sharedStrings) . " shared strings." . PHP_EOL;

// 2. Prepare SQLite Database
$dbDir = __DIR__ . '/../../storage/app/dulux_data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}
$dbPath = $dbDir . '/offtake_2025.sqlite';
if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = new PDO("sqlite:" . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA synchronous = OFF");
$pdo->exec("PRAGMA journal_mode = MEMORY");

$pdo->exec("
CREATE TABLE IF NOT EXISTS offtake_raw (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trans_date TEXT,
    year INTEGER,
    month INTEGER,
    week INTEGER,
    region TEXT,
    area TEXT,
    category_store TEXT,
    name_store TEXT,
    sap TEXT,
    sub_brand TEXT,
    brand TEXT,
    kemasan_galon TEXT,
    qty_galon REAL,
    kemasan_pail TEXT,
    qty_pail REAL,
    volume_liter REAL
);
CREATE INDEX idx_offtake_date ON offtake_raw (trans_date);
CREATE INDEX idx_offtake_store ON offtake_raw (name_store);
CREATE INDEX idx_offtake_sap ON offtake_raw (sap);
CREATE INDEX idx_offtake_month ON offtake_raw (year, month);
");

$insertStmt = $pdo->prepare("
INSERT INTO offtake_raw (
    trans_date, year, month, week, region, area, category_store, name_store, sap,
    sub_brand, brand, kemasan_galon, qty_galon, kemasan_pail, qty_pail, volume_liter
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$fp = $zip->getStream('xl/worksheets/sheet1.xml');
$buffer = '';
$totalProcessed = 0;
$total2025 = 0;
$startTime = microtime(true);

$pdo->beginTransaction();

while (!feof($fp)) {
    $buffer .= fread($fp, 1024 * 1024 * 4);
    while (($startPos = strpos($buffer, '<row ')) !== false) {
        $endPos = strpos($buffer, '</row>', $startPos);
        if ($endPos === false) {
            $buffer = substr($buffer, $startPos);
            break;
        }
        
        $rowXmlStr = substr($buffer, $startPos, ($endPos + 6) - $startPos);
        $buffer = substr($buffer, $endPos + 6);
        
        $totalProcessed++;
        if ($totalProcessed === 1) continue; // Skip header
        
        preg_match_all('/<c\s+r="([A-Z]+)\d+"(?:[^>]*?t="([^"]*)")?[^>]*>(?:<v>([^<]*)<\/v>|<is><t>([^<]*)<\/t><\/is>)?<\/c>/s', $rowXmlStr, $matches, PREG_SET_ORDER);
        $row = [];
        foreach ($matches as $m) {
            $col = $m[1];
            $t = $m[2] ?? '';
            $val = $m[3] ?? ($m[4] ?? '');
            if ($t === 's') $val = $sharedStrings[(int)$val] ?? '';
            $row[$col] = $val;
        }
        
        $year = (int) ($row['B'] ?? 0);
        if ($year !== 2025) continue; // Only 2025
        
        $total2025++;
        
        // Excel serial date to YYYY-MM-DD
        $excelSerial = (int) ($row['A'] ?? 0);
        $transDate = $excelSerial > 0 ? gmdate('Y-m-d', ($excelSerial - 25569) * 86400) : null;
        
        $month = (int) ($row['C'] ?? 0);
        $week = (int) ($row['D'] ?? 0);
        $region = trim($row['E'] ?? '');
        $area = trim($row['G'] ?? '');
        $catStore = trim($row['I'] ?? '');
        $nameStore = trim($row['J'] ?? '');
        $sap = trim($row['K'] ?? '');
        $subBrand = trim($row['M'] ?? '');
        $brand = trim($row['P'] ?? '');
        $kemasanGalon = trim($row['R'] ?? '');
        $qtyGalon = (float) ($row['S'] ?? 0);
        $kemasanPail = trim($row['U'] ?? '');
        $qtyPail = (float) ($row['V'] ?? 0);
        $volLiter = (float) ($row['Y'] ?? 0);
        
        $insertStmt->execute([
            $transDate,
            $year,
            $month,
            $week,
            $region,
            $area,
            $catStore,
            $nameStore,
            $sap,
            $subBrand,
            $brand,
            $kemasanGalon,
            $qtyGalon,
            $kemasanPail,
            $qtyPail,
            $volLiter
        ]);
        
        if ($total2025 % 20000 === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            $elapsed = round(microtime(true) - $startTime, 1);
            echo "Inserted {$total2025} rows into SQLite in {$elapsed}s..." . PHP_EOL;
        }
    }
}

$pdo->commit();
fclose($fp);
$zip->close();

$elapsed = round(microtime(true) - $startTime, 2);
echo PHP_EOL . "=== SQLITE BUILD COMPLETED in {$elapsed}s ===" . PHP_EOL;
echo "Total 2025 Rows Inserted: " . number_format($total2025) . PHP_EOL;
echo "SQLite File Path: {$dbPath}" . PHP_EOL;
echo "SQLite File Size: " . round(filesize($dbPath) / 1024 / 1024, 2) . " MB" . PHP_EOL;
