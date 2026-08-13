<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // sub_type2 to differentiate cuti_peraturan sub-types
            if (!Schema::hasColumn('leave_requests', 'cuti_peraturan_type')) {
                $table->string('cuti_peraturan_type')->nullable()->after('sub_type');
            }
        });

        // Create annual leave quotas table
        if (!Schema::hasTable('employee_leave_quotas')) {
            Schema::create('employee_leave_quotas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->integer('year');
                $table->integer('total_quota')->default(12);
                $table->timestamps();
                $table->unique(['employee_id', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'cuti_peraturan_type')) {
                $table->dropColumn('cuti_peraturan_type');
            }
        });
        Schema::dropIfExists('employee_leave_quotas');
    }
};
