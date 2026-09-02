<?php

require_once __DIR__ . '/fast_parse.php';

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
$zip = new ZipArchive();
$zip->open($filePath);

$fp = $zip->getStream('xl/worksheets/sheet1.xml');
$buffer = '';
$uniqueStoreDates = [];
$totalRows = 0;

while (!feof($fp)) {
    $buffer .= fread($fp, 1024 * 1024 * 4);
    while (($startPos = strpos($buffer, '<row ')) !== false) {
        $endPos = strpos($buffer, '</row>', $startPos);
        if ($endPos === false) break;
        
        $rowXmlStr = substr($buffer, $startPos, ($endPos + 6) - $startPos);
        $buffer = substr($buffer, $endPos + 6);
        
        $totalRows++;
        if ($totalRows === 1) continue;
        
        preg_match_all('/<c\s+r="([A-Z]+)\d+"(?:[^>]*?t="([^"]*)")?[^>]*>(?:<v>([^<]*)<\/v>|<is><t>([^<]*)<\/t><\/is>)?<\/c>/s', $rowXmlStr, $matches, PREG_SET_ORDER);
        $row = [];
        foreach ($matches as $m) {
            $col = $m[1];
            $t = $m[2] ?? '';
            $val = $m[3] ?? ($m[4] ?? '');
            if ($t === 's') $val = $sharedStrings[(int)$val] ?? '';
            $row[$col] = $val;
        }
        
        $date = $row['A'] ?? '';
        $store = $row['J'] ?? '';
        $year = $row['B'] ?? '';
        
        if ($year === '2025' && !empty($date) && !empty($store)) {
            $key = "$store|$date";
            $uniqueStoreDates[$key] = true;
        }
    }
}
fclose($fp);
$zip->close();

echo "Unique Store-Date Submissions in 2025: " . number_format(count($uniqueStoreDates)) . PHP_EOL;
