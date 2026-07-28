<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Permissions
        $this->call(PermissionsSeeder::class);

        // 1. Create Admin User for Filament
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminUser->assignRole($superAdminRole);

        // 2. Master Data Dasar (Company, Branch, Area, Principal)
        $company = Company::updateOrCreate(
            ['code' => 'COMP01'],
            ['name' => 'PT. Demo Attendance', 'address' => 'Jl. Demo No. 123']
        );

        $branch = Branch::updateOrCreate(
            ['code' => 'BR01'],
            ['name' => 'Kantor Pusat', 'address' => 'Jakarta']
        );

        $parentArea = \App\Models\Area::updateOrCreate(
            ['code' => 'AREA-JKT'],
            ['name' => 'Jakarta Raya', 'description' => 'Area Utama Jakarta']
        );

        $subArea = \App\Models\Area::updateOrCreate(
            ['code' => 'AREA-JKT-SEL'],
            ['name' => 'Jakarta Selatan', 'parent_id' => $parentArea->id, 'description' => 'Sub Area Jakarta Selatan']
        );

        $principal = \App\Models\Principal::updateOrCreate(
            ['code' => 'PRNC-01'],
            ['name' => 'Principal Alpha', 'description' => 'Dummy Principal Alpha']
        );

        // 3. Department & Position
        $dept = Department::updateOrCreate(
            ['code' => 'IT'],
            ['company_id' => $company->id, 'name' => 'Information Technology']
        );

        $pos = Position::updateOrCreate(
            ['code' => 'SPV'],
            ['company_id' => $company->id, 'name' => 'Supervisor']
        );

        // 4. Create Shift
        $shiftPagi = Shift::updateOrCreate(
            ['code' => 'SH01'],
            [
                'company_id' => $company->id,
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00'
            ]
        );

        // 5. Create Employee
        $employee = Employee::updateOrCreate(
            ['employee_no' => 'EMP001'],
            [
                'company_id' => $company->id,
                'user_id' => $adminUser->id, // Associate with admin for demo login
                'branch_id' => $branch->id,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Budi Karyawan',
                'email' => 'budi@karyawan.com',
                'phone' => '081234567890',
                'join_date' => '2023-01-01',
                'employment_status' => 'permanent',
                'is_active' => true
            ]
        );

        // 6. Create Work Location / Store
        $storeA = WorkLocation::updateOrCreate(
            ['name' => 'Toko A (Dummy)'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'type' => 'office',
                'address' => 'Jl. Kemang Raya No 1',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius_meter' => 100,
                'is_active' => true
            ]
        );

        // 7. Create Working Group & Rules
        $workingGroup = \App\Models\WorkingGroup::updateOrCreate(
            ['name' => 'Tim Sales Jakarta Selatan'],
            [
                'region' => 'DKI Jakarta',
                'area' => 'Jakarta Selatan',
                'sub_area' => 'Kemang',
                'data_applied_date' => now()->toDateString()
            ]
        );

        // Add Member to Working Group
        \App\Models\WorkingGroupMember::updateOrCreate(
            ['working_group_id' => $workingGroup->id, 'employee_id' => $employee->id],
            [
                'master_shift_id' => $shiftPagi->id,
                'late_tolerance' => 15,
                'first_visit_store_id' => $storeA->id
            ]
        );

        // Add Rule to Working Group (Monday to Friday)
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days as $day) {
            \App\Models\WorkingGroupRule::updateOrCreate(
                ['working_group_id' => $workingGroup->id, 'day_of_week' => $day],
                [
                    'shift_id' => $shiftPagi->id,
                    'late_tolerance' => 15,
                    'store_assignment_id' => $storeA->id,
                    'routing_active' => true
                ]
            );
        }

        echo "Dummy Data Seeded Successfully!\n";
    }
}
