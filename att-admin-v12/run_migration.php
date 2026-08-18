<?php
try {
    $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=attendance_pg', 'postgres', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('ALTER TABLE itinerary_items ADD COLUMN IF NOT EXISTS visit_type VARCHAR(255) NULL;');
    $pdo->exec('ALTER TABLE itinerary_items ADD COLUMN IF NOT EXISTS meeting_type VARCHAR(255) NULL;');
    $pdo->exec('ALTER TABLE itinerary_items ADD COLUMN IF NOT EXISTS agenda TEXT NULL;');
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2026_08_18_094400_add_visit_details_to_itinerary_items_table', 5) ON CONFLICT DO NOTHING;");
    echo "Columns added successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
