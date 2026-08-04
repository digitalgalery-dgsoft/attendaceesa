<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\WorkTarget;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DashboardDemoSeeder extends Seeder
{
    public function run()
    {
        $company = Company::firstOrCreate(['name' => 'ESA Group'], ['code' => 'ESA']);
        
        $branch = Branch::firstOrCreate(
            ['name' => 'HQ'], 
            ['code' => 'HQ']
        );
        
        $department = Department::firstOrCreate(
            ['name' => 'Sales'], 
            ['code' => 'SLS']
        );

        $posMD = Position::firstOrCreate(['name' => 'MD', 'company_id' => $company->id], ['code' => 'MD']);
        $posSPG = Position::firstOrCreate(['name' => 'SPG', 'company_id' => $company->id], ['code' => 'SPG']);
        $posTL = Position::firstOrCreate(['name' => 'TL', 'company_id' => $company->id], ['code' => 'TL']);

        // Create TL first
        $userTL = User::firstOrCreate(['email' => 'tl@esagroup.com'], [
            'name' => 'Team Leader Demo',
            'password' => Hash::make('123456')
        ]);
        
        $empTL = Employee::firstOrCreate(['user_id' => $userTL->id], [
            'employee_no' => 'EMP-TL-001',
            'full_name' => 'Team Leader Demo',
            'email' => 'tl@esagroup.com',
            'phone' => '08111111111',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $posTL->id,
            'employment_status' => 'permanent',
            'password' => Hash::make('123456'), // Based on requirement
        ]);

        // Create MD
        $userMD = User::firstOrCreate(['email' => 'md@esagroup.com'], [
            'name' => 'MD Demo',
            'password' => Hash::make('123456')
        ]);
        
        $empMD = Employee::firstOrCreate(['user_id' => $userMD->id], [
            'employee_no' => 'EMP-MD-001',
            'full_name' => 'MD Demo',
            'email' => 'md@esagroup.com',
            'phone' => '08222222222',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $posMD->id,
            'supervisor_id' => $empTL->id,
            'employment_status' => 'permanent',
            'password' => Hash::make('123456'),
        ]);

        // Create SPG
        $userSPG = User::firstOrCreate(['email' => 'spg@esagroup.com'], [
            'name' => 'SPG Demo',
            'password' => Hash::make('123456')
        ]);
        
        $empSPG = Employee::firstOrCreate(['user_id' => $userSPG->id], [
            'employee_no' => 'EMP-SPG-001',
            'full_name' => 'SPG Demo',
            'email' => 'spg@esagroup.com',
            'phone' => '08333333333',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $posSPG->id,
            'supervisor_id' => $empTL->id,
            'employment_status' => 'permanent',
            'password' => Hash::make('123456'),
        ]);

        // Insert Work Targets
        $monthYear = Carbon::now()->format('Y-m');
        
        WorkTarget::updateOrCreate(
            ['employee_id' => $empTL->id, 'month_year' => $monthYear],
            ['target_hk' => 25]
        );
        
        WorkTarget::updateOrCreate(
            ['employee_id' => $empMD->id, 'month_year' => $monthYear],
            ['target_hk' => 26]
        );
        
        WorkTarget::updateOrCreate(
            ['employee_id' => $empSPG->id, 'month_year' => $monthYear],
            ['target_hk' => 24]
        );

        echo "Seeder successfully ran. Created accounts: md@esagroup.com, spg@esagroup.com, tl@esagroup.com with password '123456'\n";
    }
}
