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
        Schema::table('principals', function (Blueprint $table) {
            if (!Schema::hasColumn('principals', 'theme_color_secondary')) {
                $table->string('theme_color_secondary', 50)->nullable()->after('theme_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('principals', function (Blueprint $table) {
            if (Schema::hasColumn('principals', 'theme_color_secondary')) {
                $table->dropColumn('theme_color_secondary');
            }
        });
    }
};
