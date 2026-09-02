<?php

require_once __DIR__ . '/parse_xlsx_stream.php';

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
$xlsx = new StreamingXlsx($filePath);

$months = [];
$stores = [];
$totalRows = 0;
$totalVolume = 0.0;
$brands = [];

$xlsx->readRows('Submissions', function ($row, $num) use (&$months, &$stores, &$totalRows, &$totalVolume, &$brands) {
    if ($num === 1) return; // Header
    $totalRows++;
    
    $m = $row['C'] ?? 'unknown';
    $y = $row['B'] ?? 'unknown';
    $store = $row['J'] ?? 'unknown';
    $vol = (float) ($row['Y'] ?? 0);
    $brand = $row['M'] ?? $row['P'] ?? 'unknown';

    $key = "$y-$m";
    if (!isset($months[$key])) $months[$key] = ['count' => 0, 'volume' => 0.0];
    $months[$key]['count']++;
    $months[$key]['volume'] += $vol;

    $stores[$store] = ($stores[$store] ?? 0) + 1;
    $brands[$brand] = ($brands[$brand] ?? 0) + 1;
    $totalVolume += $vol;
});

echo "Total Data Rows: $totalRows" . PHP_EOL;
echo "Total Unique Stores: " . count($stores) . PHP_EOL;
echo "Total Volume (L): " . number_format($totalVolume, 2) . PHP_EOL;
echo "Months Breakdown:" . PHP_EOL;
ksort($months);
foreach ($months as $k => $data) {
    echo "  Month {$k}: {$data['count']} transactions, " . number_format($data['volume'], 2) . " Liters" . PHP_EOL;
}
echo PHP_EOL . "Top 10 Products / Subbrands:" . PHP_EOL;
arsort($brands);
foreach (array_slice($brands, 0, 10, true) as $b => $c) {
    echo "  {$b}: {$c} rows" . PHP_EOL;
}
