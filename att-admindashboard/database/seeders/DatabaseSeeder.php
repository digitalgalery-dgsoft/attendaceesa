<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create a Company
        $company = Company::create([
            'name' => 'PT Prima Attendance',
            'code' => 'PT-PRIMA',
            'email' => 'admin@prima.com',
            'timezone' => 'Asia/Jakarta'
        ]);

        // 2. Create a Branch
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Kantor Pusat',
            'code' => 'HQ',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meter' => 100
        ]);

        // 3. Create a User for Employee
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 4. Create an Employee
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'employee_no' => 'EMP-001',
            'full_name' => 'John Doe',
            'gender' => 'male',
            'employment_status' => 'permanent',
            'email' => 'johndoe@example.com',
            'is_active' => true,
        ]);
        
        // 5. Create a Web Admin User
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);
    }
}
