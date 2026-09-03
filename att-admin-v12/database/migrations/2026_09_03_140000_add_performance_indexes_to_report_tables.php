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
        // 1. Composite index pada report_submissions untuk query filtering tanggal & template secepat kilat
        Schema::table('report_submissions', function (Blueprint $table) {
            $table->index(['report_template_id', 'submitted_at'], 'idx_rep_sub_tpl_date');
            $table->index(['report_template_id', 'work_location_id'], 'idx_rep_sub_tpl_loc');
        });

        // 2. Composite index pada report_submission_values untuk percepatan query widget charts & KPI
        Schema::table('report_submission_values', function (Blueprint $table) {
            $table->index(['field_name', 'report_submission_id'], 'idx_rep_val_field_sub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_submissions', function (Blueprint $table) {
            $table->dropIndex('idx_rep_sub_tpl_date');
            $table->dropIndex('idx_rep_sub_tpl_loc');
        });

        Schema::table('report_submission_values', function (Blueprint $table) {
            $table->dropIndex('idx_rep_val_field_sub');
        });
    }
};
