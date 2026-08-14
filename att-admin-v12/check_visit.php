<?php
// Check actual data in visit_reports table (PostgreSQL)
$conn = pg_connect("host=127.0.0.1 port=5432 dbname=attendance_pg user=postgres password=");
if (!$conn) {
    die("Connection failed\n");
}

// Show columns of visit_reports
$res = pg_query($conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'visit_reports' ORDER BY ordinal_position");
echo "=== visit_reports columns ===\n";
while ($row = pg_fetch_assoc($res)) {
    echo $row['column_name'] . " (" . $row['data_type'] . ") " . ($row['is_nullable'] === 'YES' ? 'nullable' : 'not null') . "\n";
}

echo "\n=== Data in visit_reports ===\n";
$res2 = pg_query($conn, "
    SELECT vr.id, vr.employee_id, vr.itinerary_item_id, vr.status,
           e.first_name, e.last_name,
           ii.work_location_id,
           wl.name as store_name
    FROM visit_reports vr
    LEFT JOIN employees e ON vr.employee_id = e.id
    LEFT JOIN itinerary_items ii ON vr.itinerary_item_id = ii.id
    LEFT JOIN work_locations wl ON ii.work_location_id = wl.id
    LIMIT 5
");
while ($row = pg_fetch_assoc($res2)) {
    echo json_encode($row) . "\n";
}

// Check employees table name columns
echo "\n=== employees name columns ===\n";
$res3 = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name = 'employees' AND column_name LIKE '%name%'");
while ($row = pg_fetch_assoc($res3)) {
    echo $row['column_name'] . "\n";
}
