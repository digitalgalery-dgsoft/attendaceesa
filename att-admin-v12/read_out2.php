<?php
$str = file_get_contents('test_output2.json');
$str = preg_replace('/^[\x00-\x7F]/', '', $str);
$str = mb_convert_encoding($str, 'UTF-8', 'UTF-16LE');
$j = json_decode($str, true);
if (isset($j['message'])) {
    echo "ERROR: " . $j['message'];
} else {
    echo "SUCCESS, stats: " . json_encode($j['stats'] ?? []);
    echo "\ntoday_logs count: " . count($j['today_logs'] ?? []);
    echo "\ndata count: " . count($j['data'] ?? []);
}
