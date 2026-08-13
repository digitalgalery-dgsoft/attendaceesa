<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
            // Make itinerary_item_id nullable so visits without strict itinerary can still have reports
            $table->foreignId('itinerary_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropColumn('photo_path');
            $table->foreignId('itinerary_item_id')->nullable(false)->change();
        });
    }
};
