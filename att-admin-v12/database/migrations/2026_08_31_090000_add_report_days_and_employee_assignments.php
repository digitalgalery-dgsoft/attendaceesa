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
        // 1. Tambahkan kolom report_days pada tabel report_templates jika belum ada
        if (!Schema::hasColumn('report_templates', 'report_days')) {
            Schema::table('report_templates', function (Blueprint $table) {
                $table->json('report_days')->nullable()->after('category');
            });
        }

        // 2. Tambahkan kolom employee_id pada tabel report_template_assignments jika belum ada
        if (Schema::hasTable('report_template_assignments') && !Schema::hasColumn('report_template_assignments', 'employee_id')) {
            Schema::table('report_template_assignments', function (Blueprint $table) {
                $table->foreignId('employee_id')->nullable()->after('position_id')->constrained('employees')->nullOnDelete();
            });
        }

        // 3. Buat pivot table report_template_position jika belum ada (untuk multi-select jabatan)
        if (!Schema::hasTable('report_template_position')) {
            Schema::create('report_template_position', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
                $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['report_template_id', 'position_id'], 'rt_pos_unique');
            });
        }

        // 4. Buat pivot table report_template_employee jika belum ada (untuk multi-select nama employee)
        if (!Schema::hasTable('report_template_employee')) {
            Schema::create('report_template_employee', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['report_template_id', 'employee_id'], 'rt_emp_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_template_employee');
        Schema::dropIfExists('report_template_position');

        if (Schema::hasTable('report_template_assignments') && Schema::hasColumn('report_template_assignments', 'employee_id')) {
            Schema::table('report_template_assignments', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }

        if (Schema::hasColumn('report_templates', 'report_days')) {
            Schema::table('report_templates', function (Blueprint $table) {
                $table->dropColumn('report_days');
            });
        }
    }
};
