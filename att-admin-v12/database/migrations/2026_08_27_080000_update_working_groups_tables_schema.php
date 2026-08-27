<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('working_groups', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('area')->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('working_groups', 'principal_id')) {
                $table->foreignId('principal_id')->nullable()->after('branch_id')->constrained('principals')->nullOnDelete();
            }
            if (!Schema::hasColumn('working_groups', 'default_shift_id')) {
                $table->foreignId('default_shift_id')->nullable()->after('data_applied_date')->constrained('shifts')->nullOnDelete();
            }
            if (!Schema::hasColumn('working_groups', 'default_late_tolerance')) {
                $table->integer('default_late_tolerance')->default(15)->after('default_shift_id');
            }
            if (!Schema::hasColumn('working_groups', 'default_work_location_id')) {
                $table->foreignId('default_work_location_id')->nullable()->after('default_late_tolerance')->constrained('work_locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('working_groups', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('default_work_location_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('working_group_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('working_group_rules', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('day_of_week');
            }
            if (!Schema::hasColumn('working_group_rules', 'has_custom_option')) {
                $table->boolean('has_custom_option')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('working_group_rules', function (Blueprint $table) {
            if (Schema::hasColumn('working_group_rules', 'has_custom_option')) {
                $table->dropColumn('has_custom_option');
            }
            if (Schema::hasColumn('working_group_rules', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::table('working_groups', function (Blueprint $table) {
            if (Schema::hasColumn('working_groups', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('working_groups', 'default_work_location_id')) {
                $table->dropConstrainedForeignId('default_work_location_id');
            }
            if (Schema::hasColumn('working_groups', 'default_late_tolerance')) {
                $table->dropColumn('default_late_tolerance');
            }
            if (Schema::hasColumn('working_groups', 'default_shift_id')) {
                $table->dropConstrainedForeignId('default_shift_id');
            }
            if (Schema::hasColumn('working_groups', 'principal_id')) {
                $table->dropConstrainedForeignId('principal_id');
            }
            if (Schema::hasColumn('working_groups', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
