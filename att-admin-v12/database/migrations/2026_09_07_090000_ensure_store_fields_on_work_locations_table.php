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
        if (Schema::hasTable('work_locations')) {
            Schema::table('work_locations', function (Blueprint $table) {
                if (!Schema::hasColumn('work_locations', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                if (!Schema::hasColumn('work_locations', 'category')) {
                    $table->string('category')->nullable()->after('type');
                }
                if (!Schema::hasColumn('work_locations', 'machine_type')) {
                    $table->string('machine_type')->nullable()->after('category');
                }
                if (!Schema::hasColumn('work_locations', 'machine_serial_no')) {
                    $table->string('machine_serial_no')->nullable()->after('machine_type');
                }
                if (!Schema::hasColumn('work_locations', 'store_code')) {
                    $table->string('store_code')->nullable()->after('code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('work_locations')) {
            Schema::table('work_locations', function (Blueprint $table) {
                if (Schema::hasColumn('work_locations', 'store_code')) {
                    $table->dropColumn('store_code');
                }
            });
        }
    }
};
