<?php

$src = 'storage/app/dulux_data/offtake_2025.sqlite';
$dst = 'storage/app/dulux_data/offtake_2025.sqlite.gz';
$fp_out = gzopen($dst, 'wb9');
$fp_in = fopen($src, 'rb');
while (!feof($fp_in)) {
    gzwrite($fp_out, fread($fp_in, 1024 * 512));
}
fclose($fp_in);
gzclose($fp_out);
echo "Original: " . round(filesize($src) / 1024 / 1024, 2) . " MB | Gzipped: " . round(filesize($dst) / 1024 / 1024, 2) . " MB" . PHP_EOL;
