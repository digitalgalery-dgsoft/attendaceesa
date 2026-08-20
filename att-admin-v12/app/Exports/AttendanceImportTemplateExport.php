<?php

namespace App\Exports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceImportTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    public function headings(): array
    {
        return [
            'nik',
            'nama_karyawan',
            'tanggal_mulai',
            'tanggal_akhir',
            'jam_masuk',
            'jam_keluar',
            'status',
            'keterangan',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function array(): array
    {
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        // Fetch sample employees
        $employees = Employee::where('is_active', 1)
            ->with(['branch', 'position'])
            ->limit(3)
            ->get();

        $rows = [];

        if ($employees->isNotEmpty()) {
            foreach ($employees as $idx => $emp) {
                $rows[] = [
                    (string)($emp->employee_no ?? '350521030389000' . ($idx + 1)),
                    $emp->full_name,
                    $today,
                    $today,
                    '08:00',
                    '17:00',
                    'present',
                    'Penyesuaian Manual / Dinas Lapangan',
                ];
            }
        } else {
            $rows[] = [
                '3505210303890001',
                'IRFAN NUR DIANSYAH',
                $today,
                $today,
                '08:00',
                '17:00',
                'present',
                'Penyesuaian Manual / Dinas Lapangan',
            ];
            $rows[] = [
                '3505210303890002',
                'LUKMAN NURHAKIM',
                $today,
                $today,
                '08:30',
                '17:30',
                'present',
                'Lupa Absen Datang',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Header Row Style
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E293B'], // Dark slate
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data Rows styling
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle("A2:H{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFCBD5E1'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Explicitly set column A (NIK) as string
            for ($row = 2; $row <= $highestRow; $row++) {
                $cellValue = $sheet->getCell("A{$row}")->getValue();
                if ($cellValue !== null) {
                    $sheet->getCell("A{$row}")->setValueExplicit((string)$cellValue, DataType::TYPE_STRING);
                }
            }
        }

        return [];
    }
}
