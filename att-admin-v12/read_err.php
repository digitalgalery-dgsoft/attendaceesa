<?php
$str = file_get_contents('test_output.json');
// Remove BOM if present (since powershell might add it)
$str = preg_replace('/^[\x00-\x7F]/', '', $str);
// Actually it's UTF-16LE, let's just do:
$str = mb_convert_encoding($str, 'UTF-8', 'UTF-16LE');
$j = json_decode($str, true);
echo $j['message'] ?? substr($str, 0, 500);
