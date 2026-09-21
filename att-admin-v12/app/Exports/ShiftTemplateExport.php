<?php

namespace App\Exports;

use App\Models\Principal;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShiftTemplateExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    public function bindValue(Cell $cell, $value)
    {
        // Paksa kolom teks dan waktu agar selalu berformat string murni
        if (in_array($cell->getColumn(), ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'J', 'K', 'L', 'M']) && $cell->getRow() > 1) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'prinsiple',
            'kode_shift',
            'nama_shift',
            'jam_masuk',
            'jam_pulang',
            'jam_istirahat_mulai',
            'jam_istirahat_selesai',
            'toleransi_masuk_menit',
            'toleransi_pulang_menit',
            'lintas_hari',
            'wajib_checkin',
            'wajib_checkout',
            'status_aktif',
        ];
    }

    public function array(): array
    {
        try {
            $defaultPrincipal = Principal::where('is_active', true)->first()?->name ?? 'PT ARINA MULTI KARYA';
        } catch (\Throwable $e) {
            $defaultPrincipal = 'PT ARINA MULTI KARYA';
        }

        return [
            [
                $defaultPrincipal,
                'SHF-PAGI-01',
                'Shift Pagi Regular (08:00 - 17:00)',
                '08:00',
                '17:00',
                '12:00',
                '13:00',
                15,
                0,
                'Tidak',
                'Ya',
                'Ya',
                'Ya',
            ],
            [
                $defaultPrincipal,
                'SHF-SIANG-01',
                'Shift Siang (13:00 - 21:00)',
                '13:00',
                '21:00',
                '17:00',
                '18:00',
                15,
                0,
                'Tidak',
                'Ya',
                'Ya',
                'Ya',
            ],
            [
                $defaultPrincipal,
                'SHF-MALAM-01',
                'Shift Malam Lintas Hari (22:00 - 06:00)',
                '22:00',
                '06:00',
                '02:00',
                '03:00',
                15,
                0,
                'Ya',
                'Ya',
                'Ya',
                'Ya',
            ],
            [
                $defaultPrincipal,
                'SHF-OFFICE-01',
                'Office Normal (08:30 - 17:30)',
                '08:30',
                '17:30',
                '12:00',
                '13:00',
                0,
                0,
                'Tidak',
                'Ya',
                'Ya',
                'Ya',
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header baris 1
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'], // Dark Navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Border tipis ke seluruh data
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A1:M{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        return [];
    }
}
