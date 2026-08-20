<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeamUncheckedExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $data;
    protected string $period;

    public function __construct(array $data, string $period)
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
            ['Laporan Monitoring Tim Belum Check-In (7 Hari Terakhir) - ' . $this->period],
            ['No', 'Nama Karyawan', 'NIK / No. Karyawan', 'Jabatan', 'Prinsiple', 'Area / Cabang', 'Jumlah Hari Bolos (7 Hari)', 'Daftar Tanggal Tidak Check-In', 'Terakhir Hadir']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1');
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 13],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ],
        ];
    }
}
