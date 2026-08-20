<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            // Master Data
            'view_employees', 'create_employees', 'update_employees', 'delete_employees', 'view_any_employees',
            'view_areas', 'view_branches', 'create_branches', 'update_branches', 'delete_branches', 'create_areas', 'update_areas', 'delete_areas',
            'view_principals', 'create_principals', 'update_principals', 'delete_principals',
            'view_companies', 'create_companies', 'update_companies', 'delete_companies',
            'view_departments', 'create_departments', 'update_departments', 'delete_departments',
            'view_positions', 'create_positions', 'update_positions', 'delete_positions',
            'view_work_locations', 'create_work_locations', 'update_work_locations', 'delete_work_locations',
            'view_shifts', 'create_shifts', 'update_shifts', 'delete_shifts',
            'view_holidays', 'create_holidays', 'update_holidays', 'delete_holidays',
            'view_working_groups', 'create_working_groups', 'update_working_groups', 'delete_working_groups',

            // Attendance & Time Management
            'view_attendance', 'view_attendances', 'create_attendances', 'update_attendances', 'delete_attendances',
            'manage_roster', 'view_employee_schedules', 'create_employee_schedules', 'update_employee_schedules', 'delete_employee_schedules',
            'view_leave_requests', 'create_leave_requests', 'update_leave_requests', 'delete_leave_requests',
            'view_extra_hours', 'create_extra_hours', 'update_extra_hours', 'delete_extra_hours',
            'view_bap_requests', 'create_bap_requests', 'update_bap_requests', 'delete_bap_requests',
            'view_unchecked_monitoring',

            // Field Operations & Sales
            'view_itineraries', 'create_itineraries', 'update_itineraries', 'delete_itineraries',
            'view_visit_reports', 'create_visit_reports', 'update_visit_reports', 'delete_visit_reports',
            'view_sales_reports', 'create_sales_reports', 'update_sales_reports', 'delete_sales_reports',
            'view_work_targets', 'create_work_targets', 'update_work_targets', 'delete_work_targets',
            'view_payslips', 'create_payslips', 'update_payslips', 'delete_payslips',

            // Reports & Analytics
            'view_manpower_report',
            'view_mandays_report',
            'view_turnover_report',
            'view_odoo_sync',

            // System & Settings
            'manage_users', 'view_users', 'create_users', 'update_users', 'delete_users',
            'manage_roles', 'view_roles', 'create_roles', 'update_roles', 'delete_roles',
            'manage_settings', 'view_settings', 'create_settings', 'update_settings', 'delete_settings',
            'view_blast_info', 'create_blast_info', 'update_blast_info', 'delete_blast_info',
            'view_live_chat',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    public function down(): void
    {
        // Keep permissions
    }
};
