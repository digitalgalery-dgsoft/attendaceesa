<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    Illuminate\Support\Facades\Schema::table('itinerary_items', function (Illuminate\Database\Schema\Blueprint $table) {
        if (!Illuminate\Support\Facades\Schema::hasColumn('itinerary_items', 'visit_type')) {
            $table->string('visit_type')->nullable()->after('principal_id');
        }
        if (!Illuminate\Support\Facades\Schema::hasColumn('itinerary_items', 'meeting_type')) {
            $table->string('meeting_type')->nullable()->after('visit_type');
        }
        if (!Illuminate\Support\Facades\Schema::hasColumn('itinerary_items', 'agenda')) {
            $table->text('agenda')->nullable()->after('meeting_type');
        }
    });
    echo "Columns added successfully via Laravel Schema\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
