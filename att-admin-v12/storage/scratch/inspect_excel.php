<?php

require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

$reader = IOFactory::createReaderForFile($filePath);
$reader->setReadDataOnly(true);
$sheetNames = $reader->listWorksheetNames($filePath);
echo "Sheet Names: " . json_encode($sheetNames, JSON_PRETTY_PRINT) . PHP_EOL;

$spreadsheet = $reader->load($filePath);

foreach ($sheetNames as $sName) {
    $sheet = $spreadsheet->getSheetByName($sName);
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();
    echo PHP_EOL . "=== Sheet: {$sName} (Rows: {$highestRow}, Cols: {$highestCol}) ===" . PHP_EOL;
    
    for ($r = 1; $r <= min(8, $highestRow); $r++) {
        $rowData = [];
        $colIndex = 1;
        while ($colIndex <= 20) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $val = $sheet->getCell($colLetter . $r)->getValue();
            $rowData[$colLetter] = $val;
            $colIndex++;
        }
        echo "Row {$r}: " . json_encode($rowData) . PHP_EOL;
    }
}
