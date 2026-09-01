<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add dark mode settings to the settings table.
     */
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'dark_mode_enabled')) {
                    $table->boolean('dark_mode_enabled')->default(true)->after('theme_color');
                }
                if (!Schema::hasColumn('settings', 'dark_mode_theme')) {
                    $table->string('dark_mode_theme')->default('dark_navy')->after('dark_mode_enabled');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'dark_mode_enabled')) {
                    $table->dropColumn('dark_mode_enabled');
                }
                if (Schema::hasColumn('settings', 'dark_mode_theme')) {
                    $table->dropColumn('dark_mode_theme');
                }
            });
        }
    }
};
