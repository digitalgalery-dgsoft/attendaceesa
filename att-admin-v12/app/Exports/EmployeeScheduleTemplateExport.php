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
        // Pastikan KTP / NIK tidak diubah jadi scientific notation (5.311E+15)
        if (is_numeric($value) && strlen((string) $value) >= 8) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        $headers = [
            'KTP',
            'NAME',
            'STORE CODE',
            'STORE NAME',
            'ACCOUNT',
            'NOTES',
            'MONTH',
            'YEAR',
        ];

        for ($i = 1; $i <= 31; $i++) {
            $headers[] = (string) $i;
        }

        return $headers;
    }

    public function array(): array
    {
        $rows = [];
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // Ambil beberapa contoh karyawan sesuai hak akses
        $employeeQuery = Employee::where('is_active', 1)->with(['branch', 'principal']);
        if (auth()->check()) {
            $employeeQuery = \App\Traits\ScopesUserData::applyUserAccessScope($employeeQuery);
        }
        $sampleEmployees = $employeeQuery->limit(10)->get();

        $sampleShift = Shift::where('is_active', 1)->first();
        $sampleShiftName = $sampleShift ? ($sampleShift->code ?: $sampleShift->name) : 'FLEKSIBEL01';

        if ($sampleEmployees->isNotEmpty()) {
            foreach ($sampleEmployees as $index => $emp) {
                $storeCode = $emp->branch ? strtoupper(substr($emp->branch->name, 0, 3)) . ($emp->branch->id) : 'BE9';
                $storeName = $emp->branch?->name ?? 'Outlet / Toko Area';
                $principalName = $emp->principal?->name ?? 'PRINCIPAL';

                $row = [
                    'KTP' => (string) ($emp->employee_no ?: $emp->nik),
                    'NAME' => $emp->full_name,
                    'STORE CODE' => $storeCode,
                    'STORE NAME' => $storeName,
                    'ACCOUNT' => $principalName,
                    'NOTES' => '',
                    'MONTH' => $currentMonth,
                    'YEAR' => $currentYear,
                ];

                for ($d = 1; $d <= 31; $d++) {
                    $date = Carbon::createFromDate($currentYear, $currentMonth, min($d, 28));
                    // Jika hari libur akhir pekan untuk contoh variasi
                    if ($d % 7 === 0) {
                        $row[(string)$d] = 'OFF';
                    } else {
                        $row[(string)$d] = $sampleShiftName;
                    }
                }

                $rows[] = $row;
            }
        } else {
            // Fallback contoh data jika belum ada data karyawan
            $row1 = [
                'KTP' => '5311016705020004',
                'NAME' => 'Achrisya Rambu Lingga Wandal',
                'STORE CODE' => 'BE9',
                'STORE NAME' => 'beachwalk',
                'ACCOUNT' => 'SAFF & CO',
                'NOTES' => '',
                'MONTH' => $currentMonth,
                'YEAR' => $currentYear,
            ];
            for ($d = 1; $d <= 31; $d++) {
                $row1[(string)$d] = ($d % 7 === 0) ? 'OFF' : 'FLEKSIBEL01';
            }
            $rows[] = $row1;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Format kolom KTP/NIK sebagai Text
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0F52BA'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ],
        ];
    }
}
