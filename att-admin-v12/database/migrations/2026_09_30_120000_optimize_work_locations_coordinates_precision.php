<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengubah presisi latitude dan longitude di work_locations dari (10, 7) menjadi (11, 8)
     * agar dapat menampung hingga 8 digit desimal dan mencegah numeric overflow.
     */
    public function up(): void
    {
        if (Schema::hasTable('work_locations')) {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE work_locations ALTER COLUMN latitude TYPE NUMERIC(11, 8)');
                DB::statement('ALTER TABLE work_locations ALTER COLUMN longitude TYPE NUMERIC(11, 8)');
            } elseif ($driver === 'mysql') {
                DB::statement('ALTER TABLE work_locations MODIFY latitude DECIMAL(11, 8)');
                DB::statement('ALTER TABLE work_locations MODIFY longitude DECIMAL(11, 8)');
            } else {
                Schema::table('work_locations', function (Blueprint $table) {
                    $table->decimal('latitude', 11, 8)->change();
                    $table->decimal('longitude', 11, 8)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('work_locations')) {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE work_locations ALTER COLUMN latitude TYPE NUMERIC(10, 7)');
                DB::statement('ALTER TABLE work_locations ALTER COLUMN longitude TYPE NUMERIC(10, 7)');
            } elseif ($driver === 'mysql') {
                DB::statement('ALTER TABLE work_locations MODIFY latitude DECIMAL(10, 7)');
                DB::statement('ALTER TABLE work_locations MODIFY longitude DECIMAL(10, 7)');
            } else {
                Schema::table('work_locations', function (Blueprint $table) {
                    $table->decimal('latitude', 10, 7)->change();
                    $table->decimal('longitude', 10, 7)->change();
                });
            }
        }
    }
};
