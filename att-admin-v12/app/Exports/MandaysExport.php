<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MandaysExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $period;

    public function __construct(array $data, $period)
    {
        $this->data = $data;
        $this->period = $period;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['Mandays Report - ' . $this->period],
            ['Nama Karyawan', 'Region / Cabang', 'Principal', 'Target HK', 'Aktual HK', 'Pencapaian (%)']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:F1');
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            2    => ['font' => ['bold' => true]],
        ];
    }
}
