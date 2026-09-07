<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Principal;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDuluxDummyEmployeesCommand extends Command
{
    protected $signature = 'dulux:seed-dummy-employees {--force : Force recreate existing dummy employees}';
    protected $description = 'Membuat 5 data karyawan dummy untuk prinsiple Dulux (ICI PAINTS) di server AMK area Surabaya (1 TL & 4 DC)';

    public function handle(): int
    {
        $this->info('======================================================================');
        $this->info('  MEMBUAT 5 KARYAWAN DUMMY DULUX (ICI PAINTS) - AREA SURABAYA (AMK)   ');
        $this->info('======================================================================');

        // 1. Dapatkan Company AMK (ID 1)
        $company = Company::find(1) ?? Company::where('name', 'ilike', '%arina%')->first() ?? Company::first();
        if (!$company) {
            $this->error('Company AMK tidak ditemukan!');
            return 1;
        }
        $this->line("✔ Company: {$company->name} (ID: {$company->id})");

        // 2. Dapatkan Principal ICI PAINTS (Dulux)
        $principal = Principal::where(function ($q) {
            $q->where('name', 'ilike', '%ici%')
              ->orWhere('name', 'ilike', '%dulux%');
        })->first();

        if (!$principal) {
            $principal = Principal::firstOrCreate(
                ['code' => 'PR-ICI-PAINTS'],
                [
                    'name' => 'PT ICI PAINTS INDONESIA (DULUX)',
                    'subdomain' => 'dulux',
                    'portal_title' => 'Portal Pelaporan & Monitoring Dulux (ICI Paints)',
                    'theme_color' => '#0F52BA',
                    'company_id' => $company->id,
                    'is_active' => true,
                ]
            );
        } else {
            $principal->is_active = true;
            if (!$principal->company_id) {
                $principal->company_id = $company->id;
            }
            $principal->save();
        }
        $this->line("✔ Principal: {$principal->name} (ID: {$principal->id})");

        // 3. Dapatkan / Buat Branch Surabaya
        $branch = Branch::where('name', 'ilike', '%surabaya%')->first();

        if (!$branch) {
            $branch = Branch::firstOrCreate(
                ['name' => 'Surabaya'],
                [
                    'code' => 'SBY',
                    'region' => 'Region 3',
                    'is_active' => true,
                ]
            );
        }
        $this->line("✔ Branch / Area: {$branch->name} (ID: {$branch->id})");

        // 4. Dapatkan / Buat Department
        $department = Department::firstOrCreate(
            ['name' => 'Field Operations Dulux', 'principal_id' => $principal->id],
            [
                'code' => 'DEP-DULUX',
                'company_id' => $company->id,
                'is_active' => true,
            ]
        );
        $this->line("✔ Department: {$department->name} (ID: {$department->id})");

        // 5. Dapatkan / Buat Position: TL (Team Leader)
        $positionTl = Position::firstOrCreate(
            ['name' => 'Team Leader (TL)', 'principal_id' => $principal->id],
            [
                'code' => 'TL-DULUX',
                'company_id' => $company->id,
                'department_id' => $department->id,
                'is_active' => true,
            ]
        );
        $this->line("✔ Position TL: {$positionTl->name} (ID: {$positionTl->id})");

        // 6. Dapatkan / Buat Position: DC (Decorative Consultant)
        $positionDc = Position::firstOrCreate(
            ['name' => 'Decorative Consultant (DC)', 'principal_id' => $principal->id],
            [
                'code' => 'DC-DULUX',
                'company_id' => $company->id,
                'department_id' => $department->id,
                'is_active' => true,
            ]
        );
        $this->line("✔ Position DC: {$positionDc->name} (ID: {$positionDc->id})");

        // 7. Ambil daftar store Dulux untuk penempatan
        $stores = WorkLocation::where('principal_id', $principal->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->take(10)
            ->get();

        $defaultStore = $stores->first();

        // 8. Definisi 5 Karyawan Dummy
        $dummyList = [
            [
                'type' => 'TL',
                'nik' => 'DULUX-TL-001',
                'name' => 'Ahmad Fauzi (TL Surabaya)',
                'email' => 'tl.surabaya@dulux-demo.com',
                'phone' => '081234560001',
                'gender' => 'male',
                'position_id' => $positionTl->id,
                'is_supervisor' => true,
                'store' => $stores->get(0) ?? $defaultStore,
            ],
            [
                'type' => 'DC',
                'nik' => 'DULUX-DC-001',
                'name' => 'Budi Santoso (DC Surabaya 1)',
                'email' => 'dc1.surabaya@dulux-demo.com',
                'phone' => '081234560002',
                'gender' => 'male',
                'position_id' => $positionDc->id,
                'is_supervisor' => false,
                'store' => $stores->get(1) ?? $defaultStore,
            ],
            [
                'type' => 'DC',
                'nik' => 'DULUX-DC-002',
                'name' => 'Citra Dewi (DC Surabaya 2)',
                'email' => 'dc2.surabaya@dulux-demo.com',
                'phone' => '081234560003',
                'gender' => 'female',
                'position_id' => $positionDc->id,
                'is_supervisor' => false,
                'store' => $stores->get(2) ?? $defaultStore,
            ],
            [
                'type' => 'DC',
                'nik' => 'DULUX-DC-003',
                'name' => 'Dedi Pratama (DC Surabaya 3)',
                'email' => 'dc3.surabaya@dulux-demo.com',
                'phone' => '081234560004',
                'gender' => 'male',
                'position_id' => $positionDc->id,
                'is_supervisor' => false,
                'store' => $stores->get(3) ?? $defaultStore,
            ],
            [
                'type' => 'DC',
                'nik' => 'DULUX-DC-004',
                'name' => 'Eka Rahmawati (DC Surabaya 4)',
                'email' => 'dc4.surabaya@dulux-demo.com',
                'phone' => '081234560005',
                'gender' => 'female',
                'position_id' => $positionDc->id,
                'is_supervisor' => false,
                'store' => $stores->get(4) ?? $defaultStore,
            ],
        ];

        $tlEmployee = null;
        $createdResults = [];

        foreach ($dummyList as $item) {
            // 8a. Buat / Update User
            $user = User::where('email', $item['email'])->first();
            if (!$user) {
                $user = User::create([
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'password' => Hash::make('password'),
                ]);
            } else {
                $user->update([
                    'name' => $item['name'],
                    'password' => Hash::make('password'),
                ]);
            }

            // Sync User relations
            $user->principals()->syncWithoutDetaching([$principal->id]);
            $user->branches()->syncWithoutDetaching([$branch->id]);

            // 8b. Buat / Update Employee
            $employee = Employee::where('employee_no', $item['nik'])
                ->orWhere('email', $item['email'])
                ->first();

            $employeeData = [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'principal_id' => $principal->id,
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'position_id' => $item['position_id'],
                'supervisor_id' => $item['is_supervisor'] ? null : ($tlEmployee ? $tlEmployee->id : null),
                'work_location_id' => $item['store'] ? $item['store']->id : null,
                'employee_no' => $item['nik'],
                'full_name' => $item['name'],
                'gender' => $item['gender'],
                'birth_date' => '1995-05-15',
                'join_date' => '2026-01-01',
                'employment_status' => 'permanent',
                'phone' => $item['phone'],
                'email' => $item['email'],
                'password' => Hash::make('password'),
                'is_active' => true,
                'address' => 'Surabaya, Jawa Timur',
            ];

            if (!$employee) {
                $employee = Employee::create($employeeData);
            } else {
                $employee->update($employeeData);
            }

            if ($item['is_supervisor']) {
                $tlEmployee = $employee;
            }

            $createdResults[] = [
                'type' => $item['type'],
                'nik' => $employee->employee_no,
                'name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'branch' => $branch->name,
                'position' => $item['type'] === 'TL' ? 'Team Leader (TL)' : 'Decorative Consultant (DC)',
                'supervisor' => $item['is_supervisor'] ? '-' : ($tlEmployee ? $tlEmployee->full_name : '-'),
                'store' => $item['store'] ? $item['store']->name . ' (' . ($item['store']->code ?? '-') . ')' : 'Lokasi Terpusat',
            ];
        }

        // Pastikan relasi supervisor DC terupdate jika TL dibuat duluan
        if ($tlEmployee) {
            Employee::whereIn('employee_no', ['DULUX-DC-001', 'DULUX-DC-002', 'DULUX-DC-003', 'DULUX-DC-004'])
                ->update(['supervisor_id' => $tlEmployee->id]);
        }

        $this->newLine();
        $this->info('======================================================================');
        $this->info('  5 KARYAWAN DUMMY DULUX BERHASIL DIBUAT & DIHUBUNGKAN KE SERVER AMK  ');
        $this->info('======================================================================');
        
        $this->table(
            ['Jabatan', 'NIK', 'Nama Karyawan', 'Area / Branch', 'Email Login', 'Password', 'Supervisor', 'Penempatan Store'],
            array_map(fn($r) => [
                $r['type'],
                $r['nik'],
                $r['name'],
                $r['branch'],
                $r['email'],
                'password',
                $r['supervisor'],
                $r['store']
            ], $createdResults)
        );

        $this->info('Seluruh karyawan dapat login via Mobile App / Web menggunakan Email/NIK dan Password: password');
        return 0;
    }
}
