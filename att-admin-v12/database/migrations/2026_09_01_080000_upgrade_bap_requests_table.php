<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bap_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('bap_requests', 'principal_id')) {
                $table->foreignId('principal_id')->nullable()->after('employee_id')->constrained('principals')->nullOnDelete();
            }
            if (!Schema::hasColumn('bap_requests', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('principal_id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('bap_requests', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('bap_requests', 'employee_schedule_id')) {
                $table->foreignId('employee_schedule_id')->nullable()->after('branch_id')->constrained('employee_schedules')->nullOnDelete();
            }
            if (!Schema::hasColumn('bap_requests', 'work_location_id')) {
                $table->foreignId('work_location_id')->nullable()->after('employee_schedule_id')->constrained('work_locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('bap_requests', 'attendance_id')) {
                $table->foreignId('attendance_id')->nullable()->after('work_location_id')->constrained('attendances')->nullOnDelete();
            }
            if (!Schema::hasColumn('bap_requests', 'checkin_time')) {
                $table->string('checkin_time', 20)->nullable()->default('08:00')->after('date');
            }
            if (!Schema::hasColumn('bap_requests', 'checkout_time')) {
                $table->string('checkout_time', 20)->nullable()->default('17:00')->after('checkin_time');
            }
            if (!Schema::hasColumn('bap_requests', 'issue_category')) {
                $table->string('issue_category', 50)->nullable()->default('app_error')->after('type');
            }
            if (!Schema::hasColumn('bap_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('bap_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bap_requests', function (Blueprint $table) {
            $table->dropForeign(['principal_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['employee_schedule_id']);
            $table->dropForeign(['work_location_id']);
            $table->dropForeign(['attendance_id']);
            $table->dropColumn([
                'principal_id',
                'company_id',
                'branch_id',
                'employee_schedule_id',
                'work_location_id',
                'attendance_id',
                'checkin_time',
                'checkout_time',
                'issue_category',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
