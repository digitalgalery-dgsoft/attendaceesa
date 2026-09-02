<?php

$zip = new ZipArchive();
if ($zip->open('C:/Users/amk-19 laptop/Downloads/Offtake Jan - Des 2025 (With data Dist Store).xlsx') === true) {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (strpos($stat['name'], 'sheet') !== false || strpos($stat['name'], 'shared') !== false) {
            echo $stat['name'] . ': ' . round($stat['size'] / 1024 / 1024, 2) . ' MB (compressed: ' . round($stat['comp_size'] / 1024 / 1024, 2) . ' MB)' . PHP_EOL;
        }
    }
}
