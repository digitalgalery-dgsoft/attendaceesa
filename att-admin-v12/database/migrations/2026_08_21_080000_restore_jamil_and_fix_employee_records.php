<?php

use App\Models\Employee;
use App\Models\Principal;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations to restore Abdurrahman Jamil's record and separate Eka Septiani.
     */
    public function up(): void
    {
        // 1. Cari record yang saat ini bernama 'Eka Septiani' tetapi memiliki device TECNO KM7 / foto Abdurrahman Jamil
        $jamilRecord = Employee::withTrashed()
            ->where(function ($q) {
                $q->where('device_name', 'like', '%TECNO%')
                  ->orWhere('photo', 'like', '%employees/%');
            })
            ->where('full_name', 'Eka Septiani')
            ->first();

        if (!$jamilRecord) {
            // Coba cari by ID 1 jika ID 1 bernama Eka Septiani
            $firstEmp = Employee::withTrashed()->find(1);
            if ($firstEmp && $firstEmp->full_name === 'Eka Septiani' && !empty($firstEmp->device_name)) {
                $jamilRecord = $firstEmp;
            }
        }

        if ($jamilRecord) {
            // Restore data Abdurrahman Jamil
            $jamilRecord->update([
                'full_name'   => 'Abdurrahman Jamil',
                'employee_no' => 'EMP-JAMIL-001',
                'email'       => 'abdurrahman.jamil@dgsoft.id',
                'odoo_id'     => null, // Reset Odoo ID agar tidak terikat nomor urut sembarangan
                'is_active'   => true,
            ]);

            // 2. Buat / Pastikan Eka Septiani memiliki record baru yang bersih khusus untuknya
            $existingEka = Employee::where('employee_no', '7402256409960001')->first();
            if (!$existingEka) {
                // Cari Company PT ALVA KARYA PERKASA & Principal PT SARIHUSADA
                $akpCompany = Company::where('name', 'like', '%ALVA%')->first() ?: Company::first();
                $sarihusada = Principal::where('name', 'like', '%SARIHUSADA%')->first() ?: Principal::first();

                Employee::create([
                    'company_id'        => $akpCompany?->id ?? 1,
                    'principal_id'      => $sarihusada?->id,
                    'department_id'     => $jamilRecord->department_id,
                    'position_id'       => $jamilRecord->position_id,
                    'branch_id'         => $jamilRecord->branch_id,
                    'employee_no'       => '7402256409960001',
                    'full_name'         => 'Eka Septiani',
                    'password'          => Hash::make('123456'),
                    'gender'            => 'female',
                    'employment_status' => 'contract',
                    'is_active'         => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for data fix
    }
};
