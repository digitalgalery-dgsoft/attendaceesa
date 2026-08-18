<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->string('visit_type')->nullable()->after('principal_id');
            $table->string('meeting_type')->nullable()->after('visit_type');
            $table->text('agenda')->nullable()->after('meeting_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->dropColumn(['visit_type', 'meeting_type', 'agenda']);
        });
    }
};
