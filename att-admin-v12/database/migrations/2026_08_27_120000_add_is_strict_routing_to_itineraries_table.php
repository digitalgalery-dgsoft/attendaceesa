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
        if (Schema::hasTable('itineraries') && !Schema::hasColumn('itineraries', 'is_strict_routing')) {
            Schema::table('itineraries', function (Blueprint $table) {
                $table->boolean('is_strict_routing')->default(false)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('itineraries') && Schema::hasColumn('itineraries', 'is_strict_routing')) {
            Schema::table('itineraries', function (Blueprint $table) {
                $table->dropColumn('is_strict_routing');
            });
        }
    }
};
