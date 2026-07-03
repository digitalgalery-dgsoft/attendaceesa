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
        // 1. Create Admin User for Filament
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // password
            ]
        );

        // 2. Create Company & Branch
        $company = Company::updateOrCreate(
            ['code' => 'COMP01'],
            ['name' => 'PT. Demo Attendance', 'address' => 'Jl. Demo No. 123']
        );

        $branch = Branch::updateOrCreate(
            ['code' => 'BR01'],
            ['company_id' => $company->id, 'name' => 'Kantor Pusat', 'address' => 'Jakarta']
        );

        // 3. Create Department & Position
        $dept = Department::updateOrCreate(
            ['code' => 'IT'],
            ['company_id' => $company->id, 'name' => 'Information Technology']
        );

        $pos = Position::updateOrCreate(
            ['code' => 'SPV'],
            ['company_id' => $company->id, 'name' => 'Supervisor']
        );

        // 4. Create Shift
        $shift = Shift::updateOrCreate(
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
                'user_id' => User::first()->id, // Associate with admin for demo login
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
        WorkLocation::updateOrCreate(
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

        echo "Dummy Data Seeded Successfully!\n";
    }
}
