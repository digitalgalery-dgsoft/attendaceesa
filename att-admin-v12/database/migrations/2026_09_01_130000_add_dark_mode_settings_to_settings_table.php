<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                    $table->boolean('dark_mode_enabled')->default(true)->nullable();
                }
                if (!Schema::hasColumn('settings', 'dark_mode_theme')) {
                    $table->string('dark_mode_theme')->default('dark_navy')->nullable();
                }
            });

            // Update default values for any null rows
            DB::table('settings')->whereNull('dark_mode_enabled')->update(['dark_mode_enabled' => true]);
            DB::table('settings')->whereNull('dark_mode_theme')->update(['dark_mode_theme' => 'dark_navy']);
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
