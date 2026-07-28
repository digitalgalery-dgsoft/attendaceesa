<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
                \Spatie\Permission\Models\Permission::firstOrCreate([
                    'name' => $action . '_' . $model,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Additional custom permissions
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'manage_roles',
            'guard_name' => 'web',
        ]);
        
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'manage_settings',
            'guard_name' => 'web',
        ]);
    }
}
