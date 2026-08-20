<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeScheduleTemplateExport extends DefaultValueBinder implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    public function bindValue(Cell $cell, $value)
    {
        // Pastikan NIK dan teks panjang tidak diubah jadi scientific notation
        if (is_numeric($value) && strlen((string) $value) >= 8) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'nik',
            'nama_karyawan',
            'tanggal_mulai',
            'tanggal_akhir',
            'shift',
            'lokasi_kerja',
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
        $sampleShiftName = $sampleShift ? $sampleShift->name : 'OFFICE';
        
        $sampleLocation = WorkLocation::first();
        $sampleLocationName = $sampleLocation ? $sampleLocation->name : 'INHOUSE AMK SURABAYA';

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        if ($sampleEmployees->isNotEmpty()) {
            foreach ($sampleEmployees as $index => $emp) {
                $rows[] = [
                    'nik' => (string) ($emp->employee_no ?: $emp->nik),
                    'nama_karyawan' => $emp->full_name,
                    'tanggal_mulai' => $startOfMonth,
                    'tanggal_akhir' => $endOfMonth,
                    'shift' => ($index === 2) ? 'OFF' : $sampleShiftName,
                    'lokasi_kerja' => $sampleLocationName,
                ];
            }
        } else {
            // Fallback contoh data jika belum ada data karyawan
            $rows[] = [
                'nik' => '3571011407000003',
                'nama_karyawan' => 'ZAIN AZIIZ',
                'tanggal_mulai' => $startOfMonth,
                'tanggal_akhir' => $endOfMonth,
                'shift' => $sampleShiftName,
                'lokasi_kerja' => $sampleLocationName,
            ];
            $rows[] = [
                'nik' => '3671081407000005',
                'nama_karyawan' => 'Nitta Tridadi',
                'tanggal_mulai' => $startOfMonth,
                'tanggal_akhir' => $endOfMonth,
                'shift' => 'OFF',
                'lokasi_kerja' => $sampleLocationName,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Format kolom NIK sebagai Text
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A8A'],
                ],
            ],
        ];
    }
}
