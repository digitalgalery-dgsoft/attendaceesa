<?php

use App\Models\ReportTemplate;
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
        // 1. Tambah kolom machine_type & machine_serial_no ke work_locations
        if (Schema::hasTable('work_locations')) {
            Schema::table('work_locations', function (Blueprint $table) {
                if (!Schema::hasColumn('work_locations', 'machine_type')) {
                    $table->string('machine_type')->nullable()->after('type');
                }
                if (!Schema::hasColumn('work_locations', 'machine_serial_no')) {
                    $table->string('machine_serial_no')->nullable()->after('machine_type');
                }
            });
        }

        // 2. Jalankan pembaruan template form Dulux
        try {
            ReportTemplate::syncDuluxMergedStockEnd();
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('work_locations')) {
            Schema::table('work_locations', function (Blueprint $table) {
                if (Schema::hasColumn('work_locations', 'machine_serial_no')) {
                    $table->dropColumn('machine_serial_no');
                }
                if (Schema::hasColumn('work_locations', 'machine_type')) {
                    $table->dropColumn('machine_type');
                }
            });
        }
    }
};
