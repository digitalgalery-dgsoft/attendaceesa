<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('itinerary_items') && !Schema::hasColumn('itinerary_items', 'is_checkin_location')) {
            Schema::table('itinerary_items', function (Blueprint $table) {
                $table->boolean('is_checkin_location')->default(false)->after('sequence');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('itinerary_items') && Schema::hasColumn('itinerary_items', 'is_checkin_location')) {
            Schema::table('itinerary_items', function (Blueprint $table) {
                $table->dropColumn('is_checkin_location');
            });
        }
    }
};
