<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Principal;
use App\Models\WorkLocation;
use Carbon\Carbon;
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

class VisitScheduleTemplateExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    public function bindValue(Cell $cell, $value)
    {
        // Paksa kolom NIK (Kolom A) agar selalu berformat text murni
        if ($cell->getColumn() === 'A' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
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
            'lokasi_visit',
            'urutan',
            'aturan_routing',
            'prinsiple',
            'tipe_visit',
            'jadikan_lokasi_checkin',
            'catatan',
        ];
    }

    public function array(): array
    {
        $today = Carbon::now()->format('d/m/Y');
        $sampleRows = [];

        // Ambil beberapa sample karyawan aktif
        $employees = Employee::where('is_active', 1)->take(3)->get();
        $sampleWorkLocation = WorkLocation::first()?->name ?? 'LOTTEMART SURABAYA';
        $samplePrincipal = Principal::first()?->name ?? 'ARINA MULTI KARYA';

        if ($employees->isNotEmpty()) {
            $seq = 1;
            foreach ($employees as $emp) {
                $sampleRows[] = [
                    (string)($emp->employee_no ?? '3528042504850003'),
                    $emp->full_name,
                    $today,
                    $today,
                    $sampleWorkLocation,
                    (string)$seq,
                    'Berurutan', // aturan_routing: Berurutan / Bebas
                    $samplePrincipal,
                    'Store Visit',
                    $seq === 1 ? 'Ya' : 'Tidak',
                    'Kunjungan rutin toko dan display produk',
                ];
                $seq++;
            }
        } else {
            $sampleRows[] = [
                '3528042504850003',
                'CONTOH KARYAWAN',
                $today,
                $today,
                $sampleWorkLocation,
                '1',
                'Berurutan',
                $samplePrincipal,
                'Store Visit',
                'Ya',
                'Kunjungan toko dan monitoring display',
            ];
        }

        return $sampleRows;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header baris 1
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'], // Navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Border tipis ke seluruh data
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A1:K{$highestRow}")->applyFromArray([
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
