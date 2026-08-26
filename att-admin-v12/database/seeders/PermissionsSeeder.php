<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            'users',
            'roles',
            'companies',
            'branches',
            'departments',
            'positions',
            'areas',
            'principals',
            'shifts',
            'employees',
            'work_locations',
            'working_groups',
            'attendances',
            'itineraries',
            'leave_requests',
            'extra_hours',
            'bap_requests',
            'visit_reports',
            'report_templates',
            'report_submissions',
            'products',
            'settings',
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $action . '_' . $model,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Additional custom permissions
        Permission::firstOrCreate(['name' => 'manage_roles', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_report_templates', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_report_submissions', 'guard_name' => 'web']);

        // 1. Role: Super Admin (All Permissions)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo(Permission::all());

        // 2. Role: Principal PIC / Client Management
        $picRole = Role::firstOrCreate(['name' => 'Principal PIC', 'guard_name' => 'web']);
        $picPermissions = [
            'view_any_employees', 'view_employees',
            'view_any_attendances', 'view_attendances',
            'view_any_visit_reports', 'view_visit_reports',
            'view_any_report_submissions', 'view_report_submissions',
            'view_any_report_templates', 'view_report_templates',
            'view_any_itineraries', 'view_itineraries',
            'view_any_work_locations', 'view_work_locations',
            'view_any_areas', 'view_areas',
        ];
        foreach ($picPermissions as $permName) {
            if (Permission::where('name', $permName)->exists()) {
                $picRole->givePermissionTo($permName);
            }
        }
    }
}
