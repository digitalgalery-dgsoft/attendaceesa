<?php
$host = '127.0.0.1';
$db = 'attendance_pg';
$user = 'postgres';
$pass = '';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $stmt = $pdo->query('SELECT * FROM work_locations LIMIT 3');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
