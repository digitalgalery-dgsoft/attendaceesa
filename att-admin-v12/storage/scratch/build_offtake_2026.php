<?php

ini_set('memory_limit', '1024M');
set_time_limit(600);

$filePath = 'C:/Users/jamil/Downloads/Data Dulux/Raw Offtake 2026.xlsx';
if (!file_exists($filePath)) {
    die("Error: File not found: {$filePath}\n");
}

$zip = new ZipArchive();
if ($zip->open($filePath) !== true) {
    die("Error: Cannot open zip {$filePath}\n");
}

echo "=== BUILDING DULUX OFFTAKE 2026 DATASETS ===" . PHP_EOL;

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

// 2. Prepare Output Directories
$baseDir = __DIR__ . '/../../storage/app/dulux_data';
$chunksDir = $baseDir . '/chunks';
if (!is_dir($chunksDir)) {
    mkdir($chunksDir, 0777, true);
}

// 3. Prepare SQLite Database
$dbPath = $baseDir . '/offtake_2026.sqlite';
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

// 4. Prepare Monthly Chunk File Pointers (.jsonl.gz)
$chunkFp = [];
for ($m = 1; $m <= 7; $m++) {
    $mPad = str_pad($m, 2, '0', STR_PAD_LEFT);
    $chunkPath = $chunksDir . "/offtake_2026_m{$mPad}.jsonl.gz";
    if (file_exists($chunkPath)) unlink($chunkPath);
    $chunkFp[$m] = gzopen($chunkPath, 'wb9');
}

// 5. Stream Sheet1
$fp = $zip->getStream('xl/worksheets/sheet1.xml');
if (!$fp) {
    die("Error: Cannot open sheet1.xml in zip\n");
}

$buffer = '';
$totalProcessed = 0;
$total2026 = 0;
$monthCounts = [];
$monthVolumes = [];
$stores = [];
$saps = [];
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

        $excelSerial = (int) ($row['A'] ?? 0);
        $transDate = $excelSerial > 0 ? gmdate('Y-m-d', ($excelSerial - 25569) * 86400) : '2026-01-01';
        $year = (int) date('Y', strtotime($transDate));
        $month = (int) date('n', strtotime($transDate));

        // Fallback if needed
        if ($year < 2026) $year = 2026;
        if ($month < 1 || $month > 7) {
            $month = (int) ($row['C'] ?? 1);
            if ($month < 1 || $month > 7) $month = 1;
        }

        $total2026++;

        $week = (int) ($row['D'] ?? 0);
        $region = trim($row['E'] ?? '');
        $area = trim($row['F'] ?? '');
        $catStore = trim($row['H'] ?? '');
        $nameStore = trim($row['I'] ?? '');
        $sap = trim($row['J'] ?? '');
        $subBrand = trim($row['L'] ?? '');
        $brand = trim($row['O'] ?? '');
        $kemasanGalon = trim($row['Q'] ?? '');
        $qtyGalon = (float) ($row['R'] ?? 0);
        $kemasanPail = trim($row['T'] ?? '');
        $qtyPail = (float) ($row['U'] ?? 0);
        $volLiter = (float) ($row['X'] ?? 0);

        // Insert to SQLite
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

        // Write to monthly JSONL.GZ chunk
        $record = [
            'id' => (string) $total2026,
            'trans_date' => $transDate,
            'year' => (string) $year,
            'month' => (string) $month,
            'week' => (string) $week,
            'region' => $region,
            'area' => $area,
            'category_store' => $catStore,
            'name_store' => $nameStore,
            'sap' => $sap,
            'sub_brand' => $subBrand,
            'brand' => $brand,
            'kemasan_galon' => $kemasanGalon,
            'qty_galon' => (string) $qtyGalon,
            'kemasan_pail' => $kemasanPail,
            'qty_pail' => (string) $qtyPail,
            'volume_liter' => (string) $volLiter,
        ];

        if (isset($chunkFp[$month])) {
            gzwrite($chunkFp[$month], json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
        }

        $monthCounts[$month] = ($monthCounts[$month] ?? 0) + 1;
        $monthVolumes[$month] = ($monthVolumes[$month] ?? 0.0) + $volLiter;
        if ($nameStore) $stores[$nameStore] = true;
        if ($sap) $saps[$sap] = true;

        if ($total2026 % 25000 === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            $elapsed = round(microtime(true) - $startTime, 1);
            echo "Processed {$total2026} rows into SQLite & Chunks in {$elapsed}s..." . PHP_EOL;
        }
    }
}

$pdo->commit();
fclose($fp);
$zip->close();

// Close all gzip chunk handles
for ($m = 1; $m <= 7; $m++) {
    if (isset($chunkFp[$m])) {
        gzclose($chunkFp[$m]);
    }
}

$elapsed = round(microtime(true) - $startTime, 2);
echo PHP_EOL . "=== OFFTAKE 2026 BUILD COMPLETED in {$elapsed}s ===" . PHP_EOL;
echo "Total Rows Processed: " . number_format($total2026) . PHP_EOL;
echo "Unique Stores: " . number_format(count($stores)) . PHP_EOL;
echo "Unique SAP Codes: " . number_format(count($saps)) . PHP_EOL;
echo "SQLite DB: {$dbPath} (" . round(filesize($dbPath) / 1024 / 1024, 2) . " MB)" . PHP_EOL;

echo PHP_EOL . "--- Breakdown per Bulan (Jan - Jul 2026) ---" . PHP_EOL;
ksort($monthCounts);
$totalVolAll = 0;
foreach ($monthCounts as $m => $cnt) {
    $vol = $monthVolumes[$m] ?? 0;
    $totalVolAll += $vol;
    $mPad = str_pad($m, 2, '0', STR_PAD_LEFT);
    $cSize = round(filesize($chunksDir . "/offtake_2026_m{$mPad}.jsonl.gz") / 1024 / 1024, 2);
    echo "  Month {$mPad} (2026-{$mPad}): " . number_format($cnt) . " rows | " . number_format($vol, 2, ',', '.') . " Liters | Chunk: {$cSize} MB" . PHP_EOL;
}
echo "Total Volume 2026: " . number_format($totalVolAll, 2, ',', '.') . " Liters" . PHP_EOL;

// 6. Also create Gzipped SQLite copy
echo PHP_EOL . "Compressing SQLite database to .sqlite.gz..." . PHP_EOL;
$gzSqlitePath = $dbPath . '.gz';
$src = fopen($dbPath, 'rb');
$dst = gzopen($gzSqlitePath, 'wb6');
while (!feof($src)) {
    gzwrite($dst, fread($src, 1024 * 512));
}
fclose($src);
gzclose($dst);
echo "Gzipped SQLite created: {$gzSqlitePath} (" . round(filesize($gzSqlitePath) / 1024 / 1024, 2) . " MB)" . PHP_EOL;
