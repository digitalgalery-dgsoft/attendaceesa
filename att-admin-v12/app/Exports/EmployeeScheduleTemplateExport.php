<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeScheduleTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'nik',
            'nama_karyawan',
            'tanggal',
            'shift',
            'lokasi_kerja',
            'tipe_jadwal',
        ];
    }

    public function array(): array
    {
        $rows = [];
        
        // Ambil beberapa contoh karyawan sesuai hak akses
        $employeeQuery = Employee::where('is_active', 1);
        if (auth()->check()) {
            $employeeQuery = \App\Traits\ScopesUserData::applyUserAccessScope($employeeQuery);
        }
        $sampleEmployees = $employeeQuery->limit(3)->get();

        $sampleShift = Shift::where('is_active', 1)->first();
        $sampleShiftName = $sampleShift ? $sampleShift->name : 'Morning';
        
        $sampleLocation = WorkLocation::first();
        $sampleLocationName = $sampleLocation ? $sampleLocation->name : 'Head Office';

        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        if ($sampleEmployees->isNotEmpty()) {
            foreach ($sampleEmployees as $index => $emp) {
                $rows[] = [
                    'nik' => (string) $emp->employee_no,
                    'nama_karyawan' => $emp->full_name,
                    'tanggal' => $today,
                    'shift' => $sampleShiftName,
                    'lokasi_kerja' => $sampleLocationName,
                    'tipe_jadwal' => 'workday',
                ];
                $rows[] = [
                    'nik' => (string) $emp->employee_no,
                    'nama_karyawan' => $emp->full_name,
                    'tanggal' => $tomorrow,
                    'shift' => ($index === 2) ? 'OFF' : $sampleShiftName,
                    'lokasi_kerja' => $sampleLocationName,
                    'tipe_jadwal' => ($index === 2) ? 'dayoff' : 'workday',
                ];
            }
        } else {
            // Fallback contoh data jika belum ada karyawan
            $rows[] = [
                'nik' => '3576011407000003',
                'nama_karyawan' => 'Contoh Karyawan 1',
                'tanggal' => $today,
                'shift' => $sampleShiftName,
                'lokasi_kerja' => $sampleLocationName,
                'tipe_jadwal' => 'workday',
            ];
            $rows[] = [
                'nik' => '3576011407000003',
                'nama_karyawan' => 'Contoh Karyawan 1',
                'tanggal' => $tomorrow,
                'shift' => 'OFF',
                'lokasi_kerja' => $sampleLocationName,
                'tipe_jadwal' => 'dayoff',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F46E5'],
                ],
            ],
        ];
    }
}
