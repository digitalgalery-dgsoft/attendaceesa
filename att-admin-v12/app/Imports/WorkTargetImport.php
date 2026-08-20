<?php

namespace App\Imports;

use App\Models\WorkTarget;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WorkTargetImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Cari karyawan berdasarkan NIK (employee_no)
        $nik = trim((string)($row['nik'] ?? ''));
        $employee = Employee::where('employee_no', $nik)->first();

        if ($employee) {
            // Update atau create berdasarkan employee_id dan month_year
            return WorkTarget::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'month_year' => $row['bulan_tahun'], // Format misal: 08-2026
                ],
                [
                    'target_hk' => $row['target_hk'],
                ]
            );
        }

        return null;
    }
}
