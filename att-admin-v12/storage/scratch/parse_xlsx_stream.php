<?php

ini_set('memory_limit', '1024M');

class StreamingXlsx
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
        $fp = $this->zip->getStream('xl/sharedStrings.xml');
        if (!$fp) return;

        $reader = new XMLReader();
        $reader->open('zip://' . $this->zip->filename . '#xl/sharedStrings.xml');

        $current = '';
        $inSi = false;
        while ($reader->read()) {
            if ($reader->name === 'si') {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $inSi = true;
                    $current = '';
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                    $this->sharedStrings[] = $current;
                    $inSi = false;
                }
            } elseif ($inSi && $reader->name === 't' && $reader->nodeType === XMLReader::ELEMENT) {
                $current .= $reader->readString();
            }
        }
        $reader->close();
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

    public function readRows(string $sheetName, callable $callback, int $limit = 0)
    {
        if (!isset($this->sheets[$sheetName])) {
            return;
        }
        $targetFile = $this->sheets[$sheetName];

        $reader = new XMLReader();
        $reader->open('zip://' . $this->zip->filename . '#' . $targetFile);

        $rowCount = 0;
        $inRow = false;
        $rowData = [];
        $currentCol = '';
        $currentType = '';
        $currentVal = '';
        $inCell = false;

        while ($reader->read()) {
            if ($reader->name === 'row') {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $inRow = true;
                    $rowData = [];
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                    $rowCount++;
                    $shouldContinue = $callback($rowData, $rowCount);
                    $inRow = false;
                    if ($shouldContinue === false || ($limit > 0 && $rowCount >= $limit)) {
                        break;
                    }
                }
            } elseif ($inRow && $reader->name === 'c') {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $inCell = true;
                    $ref = $reader->getAttribute('r');
                    $currentType = $reader->getAttribute('t');
                    preg_match('/([A-Z]+)(\d+)/', $ref, $matches);
                    $currentCol = $matches[1] ?? '';
                    $currentVal = '';
                    if ($reader->isEmptyElement) {
                        $inCell = false;
                        $rowData[$currentCol] = '';
                    }
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                    if ($currentType === 's') {
                        $currentVal = $this->sharedStrings[(int)$currentVal] ?? '';
                    }
                    $rowData[$currentCol] = $currentVal;
                    $inCell = false;
                }
            } elseif ($inCell && ($reader->name === 'v' || $reader->name === 't') && $reader->nodeType === XMLReader::ELEMENT) {
                $currentVal = $reader->readString();
            }
        }
        $reader->close();
    }
}
