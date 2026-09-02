<?php

class SimpleXlsx
{
    private $zip;
    private $sharedStrings = [];
    private $sheets = [];

    public function __construct(string $filePath)
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($filePath) !== true) {
            throw new Exception("Cannot open XLSX file: $filePath");
        }
        $this->loadSharedStrings();
        $this->loadWorkbook();
    }

    private function loadSharedStrings()
    {
        $content = $this->zip->getFromName('xl/sharedStrings.xml');
        if (!$content) return;
        
        $xml = simplexml_load_string($content);
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $this->sharedStrings[] = (string) $si->t;
            } elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $this->sharedStrings[] = $text;
            } else {
                $this->sharedStrings[] = '';
            }
        }
    }

    private function loadWorkbook()
    {
        $content = $this->zip->getFromName('xl/workbook.xml');
        if (!$content) return;
        $xml = simplexml_load_string($content);
        
        $rContent = $this->zip->getFromName('xl/_rels/workbook.xml.rels');
        $rels = [];
        if ($rContent) {
            $rXml = simplexml_load_string($rContent);
            foreach ($rXml->Relationship as $rel) {
                $rels[(string)$rel['Id']] = (string)$rel['Target'];
            }
        }

        foreach ($xml->sheets->sheet as $s) {
            $name = (string) $s['name'];
            $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $target = $rels[$rId] ?? '';
            if (strpos($target, '/') === 0) {
                $path = substr($target, 1);
            } else {
                $path = 'xl/' . $target;
            }
            $this->sheets[$name] = $path;
        }
    }

    public function getSheetNames(): array
    {
        return array_keys($this->sheets);
    }

    public function getRows(string $sheetName, int $limit = 0): array
    {
        if (!isset($this->sheets[$sheetName])) {
            return [];
        }
        $content = $this->zip->getFromName($this->sheets[$sheetName]);
        if (!$content) return [];

        $xml = simplexml_load_string($content);
        $rows = [];
        $rowCount = 0;

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r']; // e.g. A1, B1
                // Extract column letters
                preg_match('/([A-Z]+)(\d+)/', $ref, $matches);
                $col = $matches[1] ?? '';
                
                $type = (string) $c['t'];
                $val = (string) $c->v;

                if ($type === 's') {
                    $index = (int) $val;
                    $val = $this->sharedStrings[$index] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                }
                $rowData[$col] = $val;
            }
            $rows[] = $rowData;
            $rowCount++;
            if ($limit > 0 && $rowCount >= $limit) {
                break;
            }
        }
        return $rows;
    }

    public function countRows(string $sheetName): int
    {
        if (!isset($this->sheets[$sheetName])) return 0;
        $content = $this->zip->getFromName($this->sheets[$sheetName]);
        if (!$content) return 0;
        $xml = simplexml_load_string($content);
        return count($xml->sheetData->row);
    }
}

$filePath = 'C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx';
$xlsx = new SimpleXlsx($filePath);
$sheets = $xlsx->getSheetNames();
echo "Found " . count($sheets) . " sheets: " . json_encode($sheets) . PHP_EOL . PHP_EOL;

foreach ($sheets as $sName) {
    $count = $xlsx->countRows($sName);
    echo "=== Sheet: '{$sName}' (Total Rows: {$count}) ===" . PHP_EOL;
    $rows = $xlsx->getRows($sName, 5);
    foreach ($rows as $idx => $r) {
        echo "Row " . ($idx + 1) . ": " . json_encode($r) . PHP_EOL;
    }
    echo PHP_EOL;
}
