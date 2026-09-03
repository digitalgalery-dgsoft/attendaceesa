<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_rep_sub_tpl_subdate ON report_submissions (report_template_id, submitted_at DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_rep_sub_tpl_workloc ON report_submissions (report_template_id, work_location_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_rep_sub_tpl_emp ON report_submissions (report_template_id, employee_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_val_field_sub ON report_submission_values (field_name, report_submission_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_val_field_num ON report_submission_values (field_name, value_number)');
        } else {
            Schema::table('report_submissions', function (Blueprint $table) {
                $table->index(['report_template_id', 'submitted_at'], 'idx_rep_sub_tpl_subdate');
                $table->index(['report_template_id', 'work_location_id'], 'idx_rep_sub_tpl_workloc');
                $table->index(['report_template_id', 'employee_id'], 'idx_rep_sub_tpl_emp');
            });

            Schema::table('report_submission_values', function (Blueprint $table) {
                $table->index(['field_name', 'report_submission_id'], 'idx_val_field_sub');
                $table->index(['field_name', 'value_number'], 'idx_val_field_num');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_rep_sub_tpl_subdate');
            DB::statement('DROP INDEX IF EXISTS idx_rep_sub_tpl_workloc');
            DB::statement('DROP INDEX IF EXISTS idx_rep_sub_tpl_emp');
            DB::statement('DROP INDEX IF EXISTS idx_val_field_sub');
            DB::statement('DROP INDEX IF EXISTS idx_val_field_num');
        } else {
            Schema::table('report_submissions', function (Blueprint $table) {
                $table->dropIndex('idx_rep_sub_tpl_subdate');
                $table->dropIndex('idx_rep_sub_tpl_workloc');
                $table->dropIndex('idx_rep_sub_tpl_emp');
            });

            Schema::table('report_submission_values', function (Blueprint $table) {
                $table->dropIndex('idx_val_field_sub');
                $table->dropIndex('idx_val_field_num');
            });
        }
    }
};
