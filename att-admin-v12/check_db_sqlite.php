<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$dsn = "sqlite:$dbPath";
try {
    $pdo = new PDO($dsn, "", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check employees for NIK 3528042504850003
    echo "--- Employees with NIK 3528042504850003 ---\n";
    $stmt = $pdo->prepare("SELECT id, employee_no, first_name, employment_status, is_active, deleted_at FROM employees WHERE employee_no = '3528042504850003' ORDER BY id");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
    
    echo "\n--- Employees with name containing Jamil ---\n";
    $stmt = $pdo->prepare("SELECT id, employee_no, first_name, employment_status, is_active, deleted_at FROM employees WHERE first_name LIKE '%jamil%' ORDER BY id");
    $stmt->execute();
    $rows2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows2);
    
    $ids = array_column($rows2, 'id');
    
    echo "\n--- Attendances for Jamil's IDs ---\n";
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $stmt = $pdo->prepare("SELECT id, employee_id, attendance_date, status, clock_in, clock_out FROM attendances WHERE employee_id IN ($idList) AND attendance_date >= '2026-09-01'");
        $stmt->execute();
        $attRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($attRows);
    }
    
    echo "\n--- Schedule Roster for Jamil's IDs ---\n";
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $stmt = $pdo->prepare("SELECT id, employee_id, date, shift_id FROM employee_schedules WHERE employee_id IN ($idList) AND date >= '2026-09-01'");
        $stmt->execute();
        $schRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($schRows);
    }

} catch (PDOException $e) {
    echo $e->getMessage();
}
