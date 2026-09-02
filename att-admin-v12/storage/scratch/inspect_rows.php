<?php

require_once __DIR__ . '/fast_parse.php';

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
$zip = new ZipArchive();
$zip->open($filePath);

// Shared strings already loaded in fast_parse
$fp = $zip->getStream('xl/worksheets/sheet1.xml');
$buffer = '';
$count = 0;

echo PHP_EOL . "=== 15 SAMPLE ROWS ===" . PHP_EOL;

while (!feof($fp) && $count < 15) {
    $buffer .= fread($fp, 1024 * 512);
    while (($startPos = strpos($buffer, '<row ')) !== false) {
        $endPos = strpos($buffer, '</row>', $startPos);
        if ($endPos === false) break;
        
        $rowXmlStr = substr($buffer, $startPos, ($endPos + 6) - $startPos);
        $buffer = substr($buffer, $endPos + 6);
        
        $count++;
        preg_match_all('/<c\s+r="([A-Z]+)\d+"(?:[^>]*?t="([^"]*)")?[^>]*>(?:<v>([^<]*)<\/v>|<is><t>([^<]*)<\/t><\/is>)?<\/c>/s', $rowXmlStr, $matches, PREG_SET_ORDER);
        $row = [];
        foreach ($matches as $m) {
            $col = $m[1];
            $t = $m[2] ?? '';
            $val = $m[3] ?? ($m[4] ?? '');
            if ($t === 's') $val = $sharedStrings[(int)$val] ?? '';
            $row[$col] = $val;
        }
        echo "Row {$count}: " . json_encode($row) . PHP_EOL;
        if ($count >= 15) break;
    }
}
fclose($fp);
$zip->close();
