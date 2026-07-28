<?php
$str = file_get_contents('test_output2.json');
$str = preg_replace('/^[\x00-\x7F]/', '', $str);
$str = mb_convert_encoding($str, 'UTF-8', 'UTF-16LE');
echo substr($str, 0, 1000);
