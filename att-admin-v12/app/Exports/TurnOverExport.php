<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TurnOverExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $year;

    public function __construct(array $data, $year)
    {
        $this->data = $data;
        $this->year = $year;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['Turn Over Report - ' . $this->year],
            ['Bulan', 'Masuk (Join)', 'Keluar (Resign)', 'Net Change']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1');
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            2    => ['font' => ['bold' => true]],
        ];
    }
}
