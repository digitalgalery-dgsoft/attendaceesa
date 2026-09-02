<?php

ini_set('memory_limit', '1024M');

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
$zip = new ZipArchive();
if ($zip->open($filePath) !== true) {
    die("Cannot open zip\n");
}

// 1. Load Shared Strings (only 0.04 MB!)
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

// 2. Stream Sheet1 (814 MB) in chunks
$fp = $zip->getStream('xl/worksheets/sheet1.xml');
if (!$fp) die("Cannot open sheet1.xml\n");

$buffer = '';
$totalRows = 0;
$months = [];
$stores = [];
$brands = [];
$sampleRows = [];

$startTime = microtime(true);

while (!feof($fp)) {
    $buffer .= fread($fp, 1024 * 1024 * 4); // Read 4MB at a time
    
    // Process full <row ...>...</row> tags
    while (($startPos = strpos($buffer, '<row ')) !== false) {
        $endPos = strpos($buffer, '</row>', $startPos);
        if ($endPos === false) {
            // Incomplete row in buffer, wait for next chunk
            $buffer = substr($buffer, $startPos);
            break;
        }
        
        $rowLength = ($endPos + 6) - $startPos;
        $rowXmlStr = substr($buffer, $startPos, $rowLength);
        $buffer = substr($buffer, $endPos + 6);
        
        $totalRows++;
        
        // Fast extract cells via regex
        // <c r="A1" t="s"><v>123</v></c> or <c r="B1"><v>2025</v></c>
        preg_match_all('/<c\s+r="([A-Z]+)\d+"(?:[^>]*?t="([^"]*)")?[^>]*>(?:<v>([^<]*)<\/v>|<is><t>([^<]*)<\/t><\/is>)?<\/c>/s', $rowXmlStr, $matches, PREG_SET_ORDER);
        
        $row = [];
        foreach ($matches as $m) {
            $col = $m[1];
            $t = $m[2] ?? '';
            $val = $m[3] ?? ($m[4] ?? '');
            if ($t === 's') {
                $val = $sharedStrings[(int)$val] ?? '';
            }
            $row[$col] = $val;
        }
        
        if ($totalRows === 1) {
            $sampleRows[] = $row;
            continue; // Header
        }
        
        if ($totalRows <= 6) {
            $sampleRows[] = $row;
        }
        
        $m = $row['C'] ?? '';
        $y = $row['B'] ?? '';
        $store = $row['J'] ?? '';
        $brand = $row['M'] ?? ($row['P'] ?? '');
        $vol = (float) ($row['Y'] ?? 0);
        
        if (!empty($m) && !empty($y)) {
            $key = "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT);
            if (!isset($months[$key])) $months[$key] = ['count' => 0, 'volume' => 0.0];
            $months[$key]['count']++;
            $months[$key]['volume'] += $vol;
        }
        if (!empty($store)) {
            $stores[$store] = ($stores[$store] ?? 0) + 1;
        }
        if (!empty($brand)) {
            $brands[$brand] = ($brands[$brand] ?? 0) + 1;
        }
        
        if ($totalRows % 50000 === 0) {
            $elapsed = round(microtime(true) - $startTime, 1);
            echo "Processed {$totalRows} rows in {$elapsed}s..." . PHP_EOL;
        }
    }
}
fclose($fp);
$zip->close();

$elapsed = round(microtime(true) - $startTime, 2);
echo PHP_EOL . "=== PARSING COMPLETED in {$elapsed}s ===" . PHP_EOL;
echo "Total Data Rows: " . ($totalRows - 1) . PHP_EOL;
echo "Total Unique Stores: " . count($stores) . PHP_EOL;
echo "Months Breakdown in 2025:" . PHP_EOL;
ksort($months);
foreach ($months as $k => $d) {
    echo "  Period {$k}: " . number_format($d['count']) . " rows | Total Volume: " . number_format($d['volume'], 2, ',', '.') . " Liters" . PHP_EOL;
}
echo PHP_EOL . "Sample Header (Row 1): " . json_encode($sampleRows[0] ?? []) . PHP_EOL;
echo "Sample Row 2: " . json_encode($sampleRows[1] ?? []) . PHP_EOL;
