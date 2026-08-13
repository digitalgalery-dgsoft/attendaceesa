<?php
$conn = pg_connect("host=127.0.0.1 port=5432 dbname=absensi user=postgres password=postgres");
$res = pg_query($conn, "SELECT id, log_type, metadata FROM attendance_logs WHERE log_type = 'visit_in' AND logged_at >= current_date");
while ($row = pg_fetch_assoc($res)) {
    echo json_encode($row) . "\n";
}
