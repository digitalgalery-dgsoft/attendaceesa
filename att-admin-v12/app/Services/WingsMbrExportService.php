<?php

namespace App\Services;

use App\Models\ReportTemplate;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WingsMbrExportService
{
    /**
     * Color Theme Constants (Professional Corporate Palette)
     */
    protected const COLOR_PRIMARY_DARK = '1E3A8A';    // Deep Blue Navy (Wings Corporate)
    protected const COLOR_PRIMARY_LIGHT = '2563EB';   // Vibrant Blue (Table Headers)
    protected const COLOR_SUBHEADER = 'DBEAFE';       // Soft Blue (Sub headers)
    protected const COLOR_ZEBRA_ROW = 'F8FAFC';       // Slate 50 (Alternating rows)
    protected const COLOR_TOTAL_ROW = 'F1F5F9';       // Slate 100 (Summary row)
    protected const COLOR_BORDER = 'CBD5E1';          // Slate 300 (Clean borders)
    protected const COLOR_BORDER_DARK = '64748B';     // Slate 500 (Thicker borders)

    protected const COLOR_SUCCESS_BG = 'DCFCE7';      // Emerald 100
    protected const COLOR_SUCCESS_TEXT = '166534';    // Emerald 800
    protected const COLOR_DANGER_BG = 'FEE2E2';       // Rose 100
    protected const COLOR_DANGER_TEXT = '991B1B';     // Rose 800
    protected const COLOR_WARN_BG = 'FEF3C7';         // Amber 100
    protected const COLOR_WARN_TEXT = '92400E';       // Amber 800

    /**
     * 1. Export Wings MBR Sales (Penjualan) to Multi-Sheet Excel
     */
    public function exportSales(ReportTemplate $template, array $data, array $filters): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Wings Surya Portal Principal')
            ->setLastModifiedBy('Wings Surya Portal Principal')
            ->setTitle('Laporan Penjualan MBR - ' . ($filters['period_label'] ?? ''))
            ->setSubject('Rekapitulasi Penjualan MBR Wings Surya')
            ->setDescription('Laporan Eksekutif Penjualan Event MBR PT Wings Surya');

        // Sheet 1: Ringkasan & KPI
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan & KPI');
        $this->buildSalesSummarySheet($sheet1, $template, $data, $filters);

        // Sheet 2: Top Mitra SPG
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Top Mitra SPG');
        $this->buildSalesTopMitraSheet($sheet2, $template, $data, $filters);

        // Sheet 3: Penjualan per Produk
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Penjualan per Produk');
        $this->buildSalesProductSheet($sheet3, $template, $data, $filters);

        // Sheet 4: Performa Area & Region
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Performa Area & Region');
        $this->buildSalesAreaRegionSheet($sheet4, $template, $data, $filters);

        // Sheet 5: Data Submisi Transaksi
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Data Submisi Transaksi');
        $this->buildSalesRawSubmissionsSheet($sheet5, $template, $data, $filters);

        // Default to active Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $cleanFilename = 'laporan-penjualan-wings-mbr-' . ($filters['filename_suffix'] ?? date('Ymd_His')) . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $cleanFilename);
    }

    /**
     * 2. Export Wings MBR Free Taste / Sampling to Multi-Sheet Excel
     */
    public function exportFreeTaste(ReportTemplate $template, array $data, array $filters): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Wings Surya Portal Principal')
            ->setLastModifiedBy('Wings Surya Portal Principal')
            ->setTitle('Laporan Free Taste MBR - ' . ($filters['period_label'] ?? ''))
            ->setSubject('Rekapitulasi Free Taste MBR Wings Surya')
            ->setDescription('Laporan Eksekutif Free Taste / Sampling Event MBR PT Wings Surya');

        // Sheet 1: Ringkasan & KPI
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan & KPI');
        $this->buildFreeTasteSummarySheet($sheet1, $template, $data, $filters);

        // Sheet 2: Top Mitra Sampling
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Top Mitra Sampling');
        $this->buildFreeTasteTopMitraSheet($sheet2, $template, $data, $filters);

        // Sheet 3: Sampling per Produk
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Sampling per Produk');
        $this->buildFreeTasteProductSheet($sheet3, $template, $data, $filters);

        // Sheet 4: Performa Area & Region
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Performa Area & Region');
        $this->buildFreeTasteAreaRegionSheet($sheet4, $template, $data, $filters);

        // Sheet 5: Data Submisi Sampling
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Data Submisi Sampling');
        $this->buildFreeTasteRawSubmissionsSheet($sheet5, $template, $data, $filters);

        // Default to active Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $cleanFilename = 'laporan-free-taste-wings-mbr-' . ($filters['filename_suffix'] ?? date('Ymd_His')) . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $cleanFilename);
    }

    /**
     * 3. Export Wings MBR Tools / Peralatan to Multi-Sheet Excel
     */
    public function exportTools(ReportTemplate $template, array $data, array $filters): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Wings Surya Portal Principal')
            ->setLastModifiedBy('Wings Surya Portal Principal')
            ->setTitle('Laporan Tools MBR - ' . ($filters['period_label'] ?? ''))
            ->setSubject('Rekapitulasi Tools & Properti MBR Wings Surya')
            ->setDescription('Laporan Eksekutif Inspeksi Tools & Properti Free Taste Event MBR PT Wings Surya');

        // Sheet 1: Ringkasan & KPI
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan & KPI');
        $this->buildToolsSummarySheet($sheet1, $template, $data, $filters);

        // Sheet 2: Status 13 Tools Standar
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Status 13 Tools Standar');
        $this->buildTools13ItemsSheet($sheet2, $template, $data, $filters);

        // Sheet 3: Daftar Temuan Alat Rusak
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Daftar Temuan Alat Rusak');
        $this->buildToolsDefectsSheet($sheet3, $template, $data, $filters);

        // Sheet 4: Aktivitas Mitra
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Aktivitas Mitra');
        $this->buildToolsMitraActivitySheet($sheet4, $template, $data, $filters);

        // Sheet 5: Data Submisi Inspeksi
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Data Submisi Inspeksi');
        $this->buildToolsRawSubmissionsSheet($sheet5, $template, $data, $filters);

        // Default to active Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $cleanFilename = 'laporan-tools-properti-wings-mbr-' . ($filters['filename_suffix'] ?? date('Ymd_His')) . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $cleanFilename);
    }

    // =========================================================================
    // SALES (PENJUALAN) BUILDERS
    // =========================================================================

    protected function buildSalesSummarySheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $kpis = $data['kpis'] ?? [];

        // Banner Header (Row 1 - 5)
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'EXECUTIVE SUMMARY - LAPORAN PENJUALAN MBR',
            $filters,
            6
        );

        // KPI Summary Section
        $sheet->setCellValue('A6', '1. RINGKASAN METRIK KINERJA PENJUALAN (KPI)');
        $this->applySectionHeaderStyle($sheet, 'A6:F6');

        $headersKpi = ['Metrik Kinerja Utama', 'Nilai / Kuantitas', 'Satuan', 'Keterangan Analitis'];
        $this->applyTableHeaders($sheet, 7, $headersKpi, ['A' => 1, 'B' => 2, 'C' => 1, 'D' => 2]);

        $totalQty = (float)($kpis['total_qty_penjualan'] ?? 0);
        $totalVal = (float)($kpis['total_value_penjualan_rp'] ?? 0);
        $totalBooth = (float)($kpis['total_bayar_di_booth_rp'] ?? 0);
        $totalKasir = (float)($kpis['total_bayar_di_kasir_rp'] ?? 0);
        $totalSubs = (int)($kpis['total_submissions'] ?? 0);
        $uniqueStores = (int)($kpis['unique_stores'] ?? 0);
        $uniqueProducts = (int)($kpis['total_produk_penjualan'] ?? 0);
        $todayQty = (int)($kpis['total_penjualan_hari_ini'] ?? 0);

        $kpiRows = [
            ['Total Kuantitas Terjual', $totalQty, 'Pcs', 'Total akumulasi seluruh produk mie terjual'],
            ['Total Nilai Penjualan (Omset)', $totalVal, 'IDR (Rp)', 'Akumulasi omset bruto penjualan dari transaksi'],
            ['Rata-rata Nilai per Transaksi', $totalSubs > 0 ? round($totalVal / $totalSubs, 0) : 0, 'IDR (Rp)', 'Rata-rata per transaksi (Basket Size)'],
            ['Total Transaksi / Submisi Masuk', $totalSubs, 'Transaksi', 'Jumlah formulir transaksi penjualan terverifikasi'],
            ['Jumlah Toko / Outlet Terjangkau', $uniqueStores, 'Outlet', 'Jumlah outlet toko yang aktif berpartisipasi'],
            ['Jumlah Varian Produk / SKU Terjual', $uniqueProducts, 'SKU', 'Varian rasa mie yang menghasilkan penjualan'],
            ['Penjualan Hari Ini', $todayQty, 'Pcs', 'Realisasi penjualan pada tanggal berjalan'],
            ['Total Pembayaran di Booth SPG', $totalBooth, 'IDR (Rp)', 'Transaksi dibayar tunai / langsung di booth'],
            ['Total Pembayaran di Kasir Toko', $totalKasir, 'IDR (Rp)', 'Transaksi dibayar melalui struk kasir toko resmi'],
        ];

        $currRow = 8;
        foreach ($kpiRows as $idx => $r) {
            $sheet->setCellValue('A' . $currRow, $r[0]);
            $sheet->setCellValue('B' . $currRow, $r[1]);
            $sheet->mergeCells("B{$currRow}:C{$currRow}");
            $sheet->setCellValue('D' . $currRow, $r[2]);
            $sheet->setCellValue('E' . $currRow, $r[3]);
            $sheet->mergeCells("E{$currRow}:F{$currRow}");

            if (str_contains($r[2], 'IDR')) {
                $sheet->getStyle("B{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            } else {
                $sheet->getStyle("B{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            }

            $sheet->getStyle("A{$currRow}:F{$currRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("D{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Zebra styling
            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }
        $this->applyDataBorders($sheet, 7, $currRow - 1, 1, 6);

        // Section 2: Komposisi Metode Pembayaran
        $currRow += 2;
        $sheet->setCellValue('A' . $currRow, '2. KOMPOSISI METODE PEMBAYARAN');
        $this->applySectionHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $currRow++;

        $sheet->setCellValue("A{$currRow}", 'Metode Pembayaran');
        $sheet->mergeCells("A{$currRow}:B{$currRow}");
        $sheet->setCellValue("C{$currRow}", 'Nilai Penjualan (Rp)');
        $sheet->mergeCells("C{$currRow}:D{$currRow}");
        $sheet->setCellValue("E{$currRow}", 'Porsi Kontribusi (%)');
        $sheet->mergeCells("E{$currRow}:F{$currRow}");
        $this->applyRowHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $tableHeaderMethodRow = $currRow;
        $currRow++;

        $pctBooth = $totalVal > 0 ? round(($totalBooth / $totalVal) * 100, 1) : 0;
        $pctKasir = $totalVal > 0 ? round(($totalKasir / $totalVal) * 100, 1) : 0;

        $methodData = [
            ['Bayar Langsung di Booth SPG', $totalBooth, $pctBooth / 100],
            ['Bayar di Kasir Toko (Struk Kasir)', $totalKasir, $pctKasir / 100],
        ];

        foreach ($methodData as $idx => $m) {
            $sheet->setCellValue("A{$currRow}", $m[0]);
            $sheet->mergeCells("A{$currRow}:B{$currRow}");
            $sheet->setCellValue("C{$currRow}", $m[1]);
            $sheet->mergeCells("C{$currRow}:D{$currRow}");
            $sheet->setCellValue("E{$currRow}", $m[2]);
            $sheet->mergeCells("E{$currRow}:F{$currRow}");

            $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("E{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }
        $this->applyDataBorders($sheet, $tableHeaderMethodRow, $currRow - 1, 1, 6);

        // Section 3: Tren Penjualan Harian
        $currRow += 2;
        $sheet->setCellValue('A' . $currRow, '3. TREN REALISASI PENJUALAN HARIAN');
        $this->applySectionHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $currRow++;

        $sheet->setCellValue("A{$currRow}", 'No');
        $sheet->setCellValue("B{$currRow}", 'Tanggal');
        $sheet->mergeCells("B{$currRow}:C{$currRow}");
        $sheet->setCellValue("D{$currRow}", 'Kuantitas Terjual (Pcs)');
        $sheet->setCellValue("E{$currRow}", 'Total Nilai Omset (Rp)');
        $sheet->mergeCells("E{$currRow}:F{$currRow}");
        $this->applyRowHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $trendHeaderRow = $currRow;
        $currRow++;

        $dailyLabels = $data['chart']['daily']['labels'] ?? [];
        $dailyQtys = $data['chart']['daily']['qtys'] ?? [];
        $dailyVals = $data['chart']['daily']['values'] ?? [];

        $startTrendDataRow = $currRow;
        $trendCounter = 1;
        foreach ($dailyLabels as $i => $label) {
            $q = $dailyQtys[$i] ?? 0;
            $v = $dailyVals[$i] ?? 0;
            if ($q == 0 && $v == 0) continue;

            $sheet->setCellValue("A{$currRow}", $trendCounter++);
            $sheet->setCellValue("B{$currRow}", $label);
            $sheet->mergeCells("B{$currRow}:C{$currRow}");
            $sheet->setCellValue("D{$currRow}", (float)$q);
            $sheet->setCellValue("E{$currRow}", (float)$v);
            $sheet->mergeCells("E{$currRow}:F{$currRow}");

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');

            if ($trendCounter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > $startTrendDataRow) {
            $this->applyDataBorders($sheet, $trendHeaderRow, $currRow - 1, 1, 6);
        } else {
            $sheet->setCellValue("A{$currRow}", 'Tidak ada data transaksi pada periode yang dipilih');
            $sheet->mergeCells("A{$currRow}:F{$currRow}");
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currRow++;
        }

        $this->autoFitColumns($sheet, 6);
    }

    protected function buildSalesTopMitraSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'LEADERBOARD PERFORMA MITRA SPG / PROMOTOR PENJUALAN MBR',
            $filters,
            9
        );

        $headers = [
            'Rank',
            'Nama Mitra SPG',
            'NIK Petugas',
            'Toko Penugasan',
            'Area / Cabang',
            'Region / Wilayah',
            'Qty Terjual (Pcs)',
            'Nilai Penjualan (Rp)',
            'Kontribusi (%)'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $topMitra = $data['top_mitra'] ?? [];
        $totalVal = (float)($data['kpis']['total_value_penjualan_rp'] ?? 0);
        $totalQty = (float)($data['kpis']['total_qty_penjualan'] ?? 0);

        $currRow = 7;
        $sumQty = 0;
        $sumVal = 0;

        foreach ($topMitra as $idx => $m) {
            $rank = $idx + 1;
            $name = $m['name'] ?? '-';
            $nik = $m['nik'] ?? '-';
            $store = $m['store_name'] ?? '-';
            $area = $m['area'] ?? ($m['branch'] ?? '-');
            $region = $m['region'] ?? '-';
            $qty = (float)($m['qty'] ?? 0);
            $val = (float)($m['value_rp'] ?? ($m['value'] ?? 0));
            $pct = $totalVal > 0 ? ($val / $totalVal) : 0;

            $sumQty += $qty;
            $sumVal += $val;

            $sheet->setCellValue("A{$currRow}", $rank);
            $sheet->setCellValueExplicit("B{$currRow}", $name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currRow}", $nik, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currRow}", $store, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $area, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValue("G{$currRow}", $qty);
            $sheet->setCellValue("H{$currRow}", $val);
            $sheet->setCellValue("I{$currRow}", $pct);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight top 3
            if ($rank === 1) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF9C3');
                $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
            } elseif ($rank === 2) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F1F5F9');
            } elseif ($rank === 3) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDD5');
            } elseif ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        // Summary row
        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI');
            $sheet->mergeCells("A{$currRow}:F{$currRow}");
            $sheet->setCellValue("G{$currRow}", $sumQty);
            $sheet->setCellValue("H{$currRow}", $sumVal);
            $sheet->setCellValue("I{$currRow}", $totalVal > 0 ? ($sumVal / $totalVal) : 1);

            $this->applySummaryRowStyle($sheet, $currRow, 9);
            $sheet->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 9);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data mitra terdaftar');
            $sheet->mergeCells("A7:I7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 9);
    }

    protected function buildSalesProductSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'REKAPITULASI PENJUALAN PRODUK & SKU MBR',
            $filters,
            6
        );

        $headers = [
            'Rank',
            'Nama Varian Produk / SKU Mie',
            'Kuantitas Terjual (Pcs)',
            'Total Nilai Penjualan (Rp)',
            'Harga Rata-rata / Pcs (Rp)',
            'Kontribusi Omset (%)'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $topProducts = $data['top_products'] ?? [];
        $totalVal = (float)($data['kpis']['total_value_penjualan_rp'] ?? 0);
        $totalQty = (float)($data['kpis']['total_qty_penjualan'] ?? 0);

        $currRow = 7;
        $sumQty = 0;
        $sumVal = 0;

        foreach ($topProducts as $idx => $p) {
            $rank = $idx + 1;
            $name = $p['name'] ?? '-';
            $qty = (float)($p['qty'] ?? 0);
            $val = (float)($p['value_rp'] ?? ($p['value'] ?? 0));
            $avgPrice = $qty > 0 ? round($val / $qty, 0) : 0;
            $pct = $totalVal > 0 ? ($val / $totalVal) : 0;

            $sumQty += $qty;
            $sumVal += $val;

            $sheet->setCellValue("A{$currRow}", $rank);
            $sheet->setCellValueExplicit("B{$currRow}", $name, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $qty);
            $sheet->setCellValue("D{$currRow}", $val);
            $sheet->setCellValue("E{$currRow}", $avgPrice);
            $sheet->setCellValue("F{$currRow}", $pct);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI');
            $sheet->mergeCells("A{$currRow}:B{$currRow}");
            $sheet->setCellValue("C{$currRow}", $sumQty);
            $sheet->setCellValue("D{$currRow}", $sumVal);
            $sheet->setCellValue("E{$currRow}", $sumQty > 0 ? round($sumVal / $sumQty, 0) : 0);
            $sheet->setCellValue("F{$currRow}", $totalVal > 0 ? ($sumVal / $totalVal) : 1);

            $this->applySummaryRowStyle($sheet, $currRow, 6);
            $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 6);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data produk');
            $sheet->mergeCells("A7:F7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 6);
    }

    protected function buildSalesAreaRegionSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'DISTRIBUSI PENJUALAN BERDASARKAN AREA & REGION',
            $filters,
            6
        );

        $totalVal = (float)($data['kpis']['total_value_penjualan_rp'] ?? 0);
        $salesByArea = $data['sales_by_area'] ?? [];
        $salesByRegion = $data['sales_by_region'] ?? [];

        // Section 1: Area / Cabang
        $sheet->setCellValue('A6', '1. PERFORMA PENJUALAN PER AREA / CABANG');
        $this->applySectionHeaderStyle($sheet, 'A6:F6');

        $headersArea = ['No', 'Nama Cabang / Area', 'Region / Wilayah', 'Qty Terjual (Pcs)', 'Total Nilai Penjualan (Rp)', 'Kontribusi (%)'];
        $this->applyTableHeaders($sheet, 7, $headersArea);

        $currRow = 8;
        $sumAreaQty = 0;
        $sumAreaVal = 0;
        foreach ($salesByArea as $idx => $a) {
            $num = $idx + 1;
            $area = $a['area'] ?? '-';
            $region = $a['region'] ?? '-';
            $qty = (float)($a['qty'] ?? 0);
            $val = (float)($a['value_rp'] ?? ($a['value'] ?? 0));
            $pct = $totalVal > 0 ? ($val / $totalVal) : 0;

            $sumAreaQty += $qty;
            $sumAreaVal += $val;

            $sheet->setCellValue("A{$currRow}", $num);
            $sheet->setCellValueExplicit("B{$currRow}", $area, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValue("D{$currRow}", $qty);
            $sheet->setCellValue("E{$currRow}", $val);
            $sheet->setCellValue("F{$currRow}", $pct);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 8) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AREA');
            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("D{$currRow}", $sumAreaQty);
            $sheet->setCellValue("E{$currRow}", $sumAreaVal);
            $sheet->setCellValue("F{$currRow}", $totalVal > 0 ? ($sumAreaVal / $totalVal) : 1);

            $this->applySummaryRowStyle($sheet, $currRow, 6);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, 7, $currRow, 1, 6);
        }

        // Section 2: Region / Wilayah
        $currRow += 3;
        $sheet->setCellValue('A' . $currRow, '2. PERFORMA PENJUALAN PER REGION / WILAYAH');
        $this->applySectionHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $currRow++;

        $headersRegion = ['No', 'Region / Wilayah', 'Kategori', 'Qty Terjual (Pcs)', 'Total Nilai Penjualan (Rp)', 'Kontribusi (%)'];
        $this->applyTableHeaders($sheet, $currRow, $headersRegion);
        $headerRegionRow = $currRow;
        $currRow++;

        $sumRegQty = 0;
        $sumRegVal = 0;
        foreach ($salesByRegion as $idx => $r) {
            $num = $idx + 1;
            $region = $r['region'] ?? '-';
            $qty = (float)($r['qty'] ?? 0);
            $val = (float)($r['value_rp'] ?? ($r['value'] ?? 0));
            $pct = $totalVal > 0 ? ($val / $totalVal) : 0;

            $sumRegQty += $qty;
            $sumRegVal += $val;

            $sheet->setCellValue("A{$currRow}", $num);
            $sheet->setCellValueExplicit("B{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", 'Penjualan Wilayah');
            $sheet->setCellValue("D{$currRow}", $qty);
            $sheet->setCellValue("E{$currRow}", $val);
            $sheet->setCellValue("F{$currRow}", $pct);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > $headerRegionRow + 1) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL REGION');
            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("D{$currRow}", $sumRegQty);
            $sheet->setCellValue("E{$currRow}", $sumRegVal);
            $sheet->setCellValue("F{$currRow}", $totalVal > 0 ? ($sumRegVal / $totalVal) : 1);

            $this->applySummaryRowStyle($sheet, $currRow, 6);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, $headerRegionRow, $currRow, 1, 6);
        }

        $this->autoFitColumns($sheet, 6);
    }

    protected function buildSalesRawSubmissionsSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'DATA MENTAH SUBMISI PENJUALAN MBR LENGKAP',
            $filters,
            15
        );

        $headers = [
            'No',
            'ID Submisi',
            'Tanggal & Jam',
            'Nama Petugas / SPG',
            'NIK Petugas',
            'Toko / Outlet',
            'Area / Cabang',
            'Region',
            'Total Qty (Pcs)',
            'Total Nilai (Rp)',
            'Bayar di Booth (Rp)',
            'Bayar di Kasir (Rp)',
            'Rincian Produk Terjual',
            'Bukti Foto Struk / Display',
            'Valid Radius GPS'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $submissions = $data['submissions'] ?? collect();
        $currRow = 7;
        $counter = 1;

        $sumQty = 0;
        $sumVal = 0;
        $sumBooth = 0;
        $sumKasir = 0;

        foreach ($submissions as $sub) {
            $subDate = $sub->submitted_at ? $sub->submitted_at->format('Y-m-d H:i:s') : ($sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : '-');
            $emp = $sub->employee;
            $empName = $emp ? ($emp->full_name ?: $emp->name) : 'Petugas / Mitra';
            $empNik = $emp ? ($emp->employee_no ?: ($emp->nik ?: '-')) : '-';
            $wl = $sub->workLocation;
            $storeName = $wl ? $wl->name : ($sub->store_name ?: 'Toko / Outlet');
            $branchName = $wl && $wl->branch ? $wl->branch->name : ($emp && $emp->branch ? $emp->branch->name : '-');
            $regionName = $wl && !empty($wl->region) ? $wl->region : ($emp && $emp->branch && !empty($emp->branch->region) ? $emp->branch->region : '-');

            $subQty = 0;
            $subVal = 0;
            $subBooth = 0;
            $subKasir = 0;
            $cartDetails = [];
            $photoUrl = null;

            foreach ($sub->values as $v) {
                $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                if ($fn === 'mbr_sales_items_json') {
                    $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                    if (is_array($raw)) {
                        foreach ($raw as $item) {
                            $pName = $item['name'] ?? ($item['product_name'] ?? 'Produk');
                            $pQty = $item['qty'] ?? 1;
                            $cartDetails[] = "{$pName} ({$pQty} pcs)";
                        }
                    }
                } elseif ($fn === 'total_qty_penjualan') {
                    $subQty = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif ($fn === 'total_value_penjualan_rp') {
                    $subVal = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif ($fn === 'total_bayar_di_booth_rp') {
                    $subBooth = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif ($fn === 'total_bayar_di_kasir_rp') {
                    $subKasir = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif (str_contains($fn, 'foto') || str_contains($fn, 'struk') || str_contains($fn, 'display')) {
                    $cand = $v->media_url ?: ($v->value_text ?: (is_array($v->value_json) ? ($v->value_json[0] ?? null) : null));
                    if ($cand && is_string($cand) && !str_starts_with($cand, '/data/user/')) {
                        $photoUrl = $this->resolvePublicMediaUrl($cand);
                    }
                }
            }

            // Disk fallback for photo
            if (!$photoUrl) {
                $matches = glob(storage_path("app/public/reports/*/report_{$sub->id}_*.jpg"));
                if (!empty($matches)) {
                    $rel = str_replace([storage_path('app/public/'), '\\'], ['', '/'], $matches[0]);
                    $photoUrl = asset('storage/' . ltrim($rel, '/'));
                }
            }

            $sumQty += $subQty;
            $sumVal += $subVal;
            $sumBooth += $subBooth;
            $sumKasir += $subKasir;

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValue("B{$currRow}", $sub->id);
            $sheet->setCellValue("C{$currRow}", $subDate);
            $sheet->setCellValueExplicit("D{$currRow}", $empName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $empNik, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currRow}", $storeName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currRow}", $branchName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currRow}", $regionName, DataType::TYPE_STRING);
            $sheet->setCellValue("I{$currRow}", $subQty);
            $sheet->setCellValue("J{$currRow}", $subVal);
            $sheet->setCellValue("K{$currRow}", $subBooth);
            $sheet->setCellValue("L{$currRow}", $subKasir);
            $sheet->setCellValueExplicit("M{$currRow}", !empty($cartDetails) ? implode(', ', $cartDetails) : '-', DataType::TYPE_STRING);

            if ($photoUrl) {
                $sheet->setCellValue("N{$currRow}", 'Buka Foto');
                $sheet->getCell("N{$currRow}")->getHyperlink()->setUrl($photoUrl);
                $sheet->getStyle("N{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2563EB'))->setUnderline(true);
            } else {
                $sheet->setCellValue("N{$currRow}", '-');
            }

            $sheet->setCellValue("O{$currRow}", $sub->is_within_radius ? 'Valid' : 'Luar Radius');

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("K{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("L{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("N{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:O{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL SELURUH SUBMISI');
            $sheet->mergeCells("A{$currRow}:H{$currRow}");
            $sheet->setCellValue("I{$currRow}", $sumQty);
            $sheet->setCellValue("J{$currRow}", $sumVal);
            $sheet->setCellValue("K{$currRow}", $sumBooth);
            $sheet->setCellValue("L{$currRow}", $sumKasir);

            $this->applySummaryRowStyle($sheet, $currRow, 15);
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("K{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("L{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 15);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data submisi transaksi');
            $sheet->mergeCells("A7:O7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 15);
    }

    // =========================================================================
    // FREE TASTE / SAMPLING BUILDERS
    // =========================================================================

    protected function buildFreeTasteSummarySheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $kpis = $data['kpis'] ?? [];

        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'EXECUTIVE SUMMARY - LAPORAN FREE TASTE / SAMPLING MBR',
            $filters,
            6
        );

        $sheet->setCellValue('A6', '1. RINGKASAN METRIK KINERJA FREE TASTE (KPI)');
        $this->applySectionHeaderStyle($sheet, 'A6:F6');

        $headersKpi = ['Metrik Kinerja Utama', 'Nilai / Kuantitas', 'Satuan', 'Keterangan Analitis'];
        $this->applyTableHeaders($sheet, 7, $headersKpi, ['A' => 1, 'B' => 2, 'C' => 1, 'D' => 2]);

        $totalDimasak = (float)($kpis['total_dimasak'] ?? 0);
        $totalCup = (float)($kpis['total_cup'] ?? 0);
        $cupPerPcs = (float)($kpis['cup_per_pcs'] ?? 0);
        $totalStokAwal = (float)($kpis['total_stok_awal'] ?? 0);
        $totalStokAkhir = (float)($kpis['total_stok_akhir'] ?? 0);
        $uniqueStores = (int)($kpis['unique_stores'] ?? 0);
        $uniqueMitras = (int)($kpis['unique_mitras'] ?? 0);
        $totalSubs = (int)($kpis['total_submissions'] ?? 0);
        $todayDimasak = (int)($kpis['today_dimasak'] ?? 0);
        $todayCup = (int)($kpis['today_cup'] ?? 0);

        $kpiRows = [
            ['Total Mie Dimasak', $totalDimasak, 'Pcs', 'Total porsi mie mentah yang dimasak untuk sampling'],
            ['Total Cup Dibagikan', $totalCup, 'Cup', 'Jumlah cup tester rasa yang dinikmati konsumen'],
            ['Rasio Konversi (Cup / Pcs)', $cupPerPcs, 'Cup / Pcs', 'Rata-rata cup yang dihasilkan dari 1 bungkus mie'],
            ['Total Stok Awal Sampling', $totalStokAwal, 'Pcs', 'Akumulasi stok persediaan mie sebelum demo masak'],
            ['Total Sisa Stok Akhir', $totalStokAkhir, 'Pcs', 'Sisa stok mie setelah kegiatan sampling selesai'],
            ['Dimasak Hari Ini', $todayDimasak, 'Pcs', 'Kuantitas mie dimasak pada tanggal hari ini'],
            ['Cup Dibagikan Hari Ini', $todayCup, 'Cup', 'Jumlah cup dibagikan pada tanggal hari ini'],
            ['Jumlah Toko / Outlet Terjangkau', $uniqueStores, 'Outlet', 'Outlet aktif yang menyelenggarakan demo free taste'],
            ['Jumlah Mitra / SPG Pelaksana', $uniqueMitras, 'Orang', 'Petugas SPG/Promotor yang bertugas memasak'],
            ['Total Submisi / Laporan Masuk', $totalSubs, 'Submisi', 'Jumlah formulir kegiatan sampling terverifikasi'],
        ];

        $currRow = 8;
        foreach ($kpiRows as $idx => $r) {
            $sheet->setCellValue('A' . $currRow, $r[0]);
            $sheet->setCellValue('B' . $currRow, $r[1]);
            $sheet->mergeCells("B{$currRow}:C{$currRow}");
            $sheet->setCellValue('D' . $currRow, $r[2]);
            $sheet->setCellValue('E' . $currRow, $r[3]);
            $sheet->mergeCells("E{$currRow}:F{$currRow}");

            if (str_contains($r[2], 'Cup / Pcs')) {
                $sheet->getStyle("B{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            } else {
                $sheet->getStyle("B{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            }

            $sheet->getStyle("A{$currRow}:F{$currRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("D{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }
        $this->applyDataBorders($sheet, 7, $currRow - 1, 1, 6);

        // Section 2: Tren Sampling Harian
        $currRow += 2;
        $sheet->setCellValue('A' . $currRow, '2. TREN KEGIATAN FREE TASTE HARIAN');
        $this->applySectionHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $currRow++;

        $sheet->setCellValue("A{$currRow}", 'No');
        $sheet->setCellValue("B{$currRow}", 'Tanggal');
        $sheet->mergeCells("B{$currRow}:C{$currRow}");
        $sheet->setCellValue("D{$currRow}", 'Dimasak (Pcs)');
        $sheet->setCellValue("E{$currRow}", 'Cup Dibagikan');
        $sheet->setCellValue("F{$currRow}", 'Rasio (Cup/Pcs)');
        $this->applyRowHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $trendHeaderRow = $currRow;
        $currRow++;

        $chartLabels = $data['chart']['daily']['labels'] ?? [];
        $chartDimasak = $data['chart']['daily']['dimasaks'] ?? [];
        $chartCup = $data['chart']['daily']['cups'] ?? [];

        $startTrendDataRow = $currRow;
        $counter = 1;
        foreach ($chartLabels as $i => $label) {
            $d = $chartDimasak[$i] ?? 0;
            $c = $chartCup[$i] ?? 0;
            if ($d == 0 && $c == 0) continue;

            $ratio = $d > 0 ? round($c / $d, 1) : 0;

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValue("B{$currRow}", $label);
            $sheet->mergeCells("B{$currRow}:C{$currRow}");
            $sheet->setCellValue("D{$currRow}", (float)$d);
            $sheet->setCellValue("E{$currRow}", (float)$c);
            $sheet->setCellValue("F{$currRow}", (float)$ratio);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > $startTrendDataRow) {
            $this->applyDataBorders($sheet, $trendHeaderRow, $currRow - 1, 1, 6);
        } else {
            $sheet->setCellValue("A{$currRow}", 'Tidak ada data sampling pada rentang waktu ini');
            $sheet->mergeCells("A{$currRow}:F{$currRow}");
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 6);
    }

    protected function buildFreeTasteTopMitraSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'LEADERBOARD MITRA SPG SAMPLING & DEMO MASAK MBR',
            $filters,
            9
        );

        $headers = [
            'Rank',
            'Nama Mitra SPG',
            'NIK Petugas',
            'Toko Penugasan',
            'Area / Cabang',
            'Region / Wilayah',
            'Mie Dimasak (Pcs)',
            'Cup Dibagikan',
            'Rasio (Cup / Pcs)'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $topMitra = $data['top_mitra'] ?? [];
        $currRow = 7;
        $sumDimasak = 0;
        $sumCup = 0;

        foreach ($topMitra as $idx => $m) {
            $rank = $idx + 1;
            $name = $m['name'] ?? '-';
            $nik = $m['nik'] ?? '-';
            $store = $m['store_name'] ?? ($m['store'] ?? '-');
            $area = $m['area'] ?? ($m['branch'] ?? '-');
            $region = $m['region'] ?? '-';
            $dimasak = (float)($m['dimasak'] ?? 0);
            $cup = (float)($m['cup'] ?? 0);
            $ratio = $dimasak > 0 ? round($cup / $dimasak, 1) : 0;

            $sumDimasak += $dimasak;
            $sumCup += $cup;

            $sheet->setCellValue("A{$currRow}", $rank);
            $sheet->setCellValueExplicit("B{$currRow}", $name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currRow}", $nik, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currRow}", $store, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $area, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValue("G{$currRow}", $dimasak);
            $sheet->setCellValue("H{$currRow}", $cup);
            $sheet->setCellValue("I{$currRow}", $ratio);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($rank === 1) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF9C3');
                $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
            } elseif ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI');
            $sheet->mergeCells("A{$currRow}:F{$currRow}");
            $sheet->setCellValue("G{$currRow}", $sumDimasak);
            $sheet->setCellValue("H{$currRow}", $sumCup);
            $sheet->setCellValue("I{$currRow}", $sumDimasak > 0 ? round($sumCup / $sumDimasak, 1) : 0);

            $this->applySummaryRowStyle($sheet, $currRow, 9);
            $sheet->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 9);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data mitra terdaftar');
            $sheet->mergeCells("A7:I7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 9);
    }

    protected function buildFreeTasteProductSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'REKAPITULASI SAMPLING PER VARIAN RASA / PRODUK MIE',
            $filters,
            7
        );

        $headers = [
            'Rank',
            'Varian Rasa / SKU Mie',
            'Stok Awal (Pcs)',
            'Dimasak (Pcs)',
            'Cup Dibagikan',
            'Sisa Stok (Pcs)',
            'Rasio (Cup / Pcs)'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $topProducts = $data['top_products'] ?? [];
        $currRow = 7;
        $sumAwal = 0;
        $sumDimasak = 0;
        $sumCup = 0;
        $sumAkhir = 0;

        foreach ($topProducts as $idx => $p) {
            $rank = $idx + 1;
            $name = $p['name'] ?? '-';
            $awal = (float)($p['stok_awal'] ?? 0);
            $dimasak = (float)($p['dimasak'] ?? 0);
            $cup = (float)($p['cup'] ?? 0);
            $akhir = (float)($p['stok_akhir'] ?? max(0, $awal - $dimasak));
            $ratio = $dimasak > 0 ? round($cup / $dimasak, 1) : 0;

            $sumAwal += $awal;
            $sumDimasak += $dimasak;
            $sumCup += $cup;
            $sumAkhir += $akhir;

            $sheet->setCellValue("A{$currRow}", $rank);
            $sheet->setCellValueExplicit("B{$currRow}", $name, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $awal);
            $sheet->setCellValue("D{$currRow}", $dimasak);
            $sheet->setCellValue("E{$currRow}", $cup);
            $sheet->setCellValue("F{$currRow}", $akhir);
            $sheet->setCellValue("G{$currRow}", $ratio);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("G{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:G{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI');
            $sheet->mergeCells("A{$currRow}:B{$currRow}");
            $sheet->setCellValue("C{$currRow}", $sumAwal);
            $sheet->setCellValue("D{$currRow}", $sumDimasak);
            $sheet->setCellValue("E{$currRow}", $sumCup);
            $sheet->setCellValue("F{$currRow}", $sumAkhir);
            $sheet->setCellValue("G{$currRow}", $sumDimasak > 0 ? round($sumCup / $sumDimasak, 1) : 0);

            $this->applySummaryRowStyle($sheet, $currRow, 7);
            $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("G{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 7);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data varian rasa');
            $sheet->mergeCells("A7:G7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 7);
    }

    protected function buildFreeTasteAreaRegionSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'DISTRIBUSI KEGIATAN SAMPLING PER AREA & REGION',
            $filters,
            6
        );

        $samplingByArea = $data['sampling_by_area'] ?? [];
        $samplingByRegion = $data['sampling_by_region'] ?? [];

        // Section 1: Area
        $sheet->setCellValue('A6', '1. DISTRIBUSI SAMPLING PER AREA / CABANG');
        $this->applySectionHeaderStyle($sheet, 'A6:F6');

        $headersArea = ['No', 'Nama Cabang / Area', 'Region / Wilayah', 'Mie Dimasak (Pcs)', 'Cup Dibagikan', 'Rasio (Cup / Pcs)'];
        $this->applyTableHeaders($sheet, 7, $headersArea);

        $currRow = 8;
        $sumDimasak = 0;
        $sumCup = 0;

        foreach ($samplingByArea as $idx => $a) {
            $num = $idx + 1;
            $area = $a['area'] ?? '-';
            $region = $a['region'] ?? '-';
            $dimasak = (float)($a['dimasak'] ?? 0);
            $cup = (float)($a['cup'] ?? 0);
            $ratio = $dimasak > 0 ? round($cup / $dimasak, 1) : 0;

            $sumDimasak += $dimasak;
            $sumCup += $cup;

            $sheet->setCellValue("A{$currRow}", $num);
            $sheet->setCellValueExplicit("B{$currRow}", $area, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValue("D{$currRow}", $dimasak);
            $sheet->setCellValue("E{$currRow}", $cup);
            $sheet->setCellValue("F{$currRow}", $ratio);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 8) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AREA');
            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("D{$currRow}", $sumDimasak);
            $sheet->setCellValue("E{$currRow}", $sumCup);
            $sheet->setCellValue("F{$currRow}", $sumDimasak > 0 ? round($sumCup / $sumDimasak, 1) : 0);

            $this->applySummaryRowStyle($sheet, $currRow, 6);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, 7, $currRow, 1, 6);
        }

        // Section 2: Region
        $currRow += 3;
        $sheet->setCellValue('A' . $currRow, '2. DISTRIBUSI SAMPLING PER REGION / WILAYAH');
        $this->applySectionHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $currRow++;

        $headersRegion = ['No', 'Region / Wilayah', 'Kategori', 'Mie Dimasak (Pcs)', 'Cup Dibagikan', 'Rasio (Cup / Pcs)'];
        $this->applyTableHeaders($sheet, $currRow, $headersRegion);
        $headerRegionRow = $currRow;
        $currRow++;

        $sumRegDimasak = 0;
        $sumRegCup = 0;

        foreach ($samplingByRegion as $idx => $r) {
            $num = $idx + 1;
            $region = $r['region'] ?? '-';
            $dimasak = (float)($r['dimasak'] ?? 0);
            $cup = (float)($r['cup'] ?? 0);
            $ratio = $dimasak > 0 ? round($cup / $dimasak, 1) : 0;

            $sumRegDimasak += $dimasak;
            $sumRegCup += $cup;

            $sheet->setCellValue("A{$currRow}", $num);
            $sheet->setCellValueExplicit("B{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", 'Sampling Wilayah');
            $sheet->setCellValue("D{$currRow}", $dimasak);
            $sheet->setCellValue("E{$currRow}", $cup);
            $sheet->setCellValue("F{$currRow}", $ratio);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > $headerRegionRow + 1) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL REGION');
            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("D{$currRow}", $sumRegDimasak);
            $sheet->setCellValue("E{$currRow}", $sumRegCup);
            $sheet->setCellValue("F{$currRow}", $sumRegDimasak > 0 ? round($sumRegCup / $sumRegDimasak, 1) : 0);

            $this->applySummaryRowStyle($sheet, $currRow, 6);
            $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, $headerRegionRow, $currRow, 1, 6);
        }

        $this->autoFitColumns($sheet, 6);
    }

    protected function buildFreeTasteRawSubmissionsSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'DATA MENTAH SUBMISI FREE TASTE / SAMPLING LENGKAP',
            $filters,
            16
        );

        $headers = [
            'No',
            'ID Submisi',
            'Tanggal & Jam',
            'Nama Petugas / SPG',
            'NIK Petugas',
            'Toko / Outlet Penugasan',
            'Area / Cabang',
            'Region',
            'Total Dimasak (Pcs)',
            'Total Cup Dibagikan',
            'Rasio (Cup / Pcs)',
            'Stok Awal (Pcs)',
            'Sisa Stok (Pcs)',
            'Rincian Varian Sampling',
            'Link Bukti Foto Kegiatan',
            'Valid Radius GPS'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $submissions = $data['submissions'] ?? collect();
        $currRow = 7;
        $counter = 1;

        $sumDimasak = 0;
        $sumCup = 0;
        $sumAwal = 0;
        $sumSisa = 0;

        foreach ($submissions as $sub) {
            $subDate = $sub->submitted_at ? $sub->submitted_at->format('Y-m-d H:i:s') : ($sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : '-');
            $emp = $sub->employee;
            $empName = $emp ? ($emp->full_name ?: $emp->name) : 'Petugas / Mitra';
            $empNik = $emp ? ($emp->employee_no ?: ($emp->nik ?: '-')) : '-';
            $wl = $sub->workLocation;
            $storeName = $wl ? $wl->name : ($sub->store_name ?: 'Toko / Outlet');
            $branchName = $wl && $wl->branch ? $wl->branch->name : ($emp && $emp->branch ? $emp->branch->name : '-');
            $regionName = $wl && !empty($wl->region) ? $wl->region : ($emp && $emp->branch && !empty($emp->branch->region) ? $emp->branch->region : '-');

            $subDimasak = 0;
            $subCup = 0;
            $subAwal = 0;
            $subSisa = 0;
            $variantDetails = [];
            $photoUrl = null;

            foreach ($sub->values as $v) {
                $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                if ($fn === 'mbr_freetaste_items_json') {
                    $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                    if (is_array($raw)) {
                        foreach ($raw as $item) {
                            $pName = $item['name'] ?? ($item['product_name'] ?? 'Varian');
                            $pD = $item['dimasak'] ?? 0;
                            $pC = $item['cup'] ?? 0;
                            $variantDetails[] = "{$pName} ({$pD} pcs -> {$pC} cup)";
                        }
                    }
                } elseif ($fn === 'total_dimasak' || str_contains($fn, 'dimasak')) {
                    $subDimasak = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif ($fn === 'total_cup' || str_contains($fn, 'cup')) {
                    $subCup = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif ($fn === 'total_stok_awal' || str_contains($fn, 'stok_awal')) {
                    $subAwal = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif ($fn === 'total_stok_akhir' || str_contains($fn, 'stok_akhir') || str_contains($fn, 'sisa')) {
                    $subSisa = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                } elseif (str_contains($fn, 'foto') || str_contains($fn, 'kegiatan') || str_contains($fn, 'sampling')) {
                    $cand = $v->media_url ?: ($v->value_text ?: (is_array($v->value_json) ? ($v->value_json[0] ?? null) : null));
                    if ($cand && is_string($cand) && !str_starts_with($cand, '/data/user/')) {
                        $photoUrl = $this->resolvePublicMediaUrl($cand);
                    }
                }
            }

            if (!$photoUrl) {
                $matches = glob(storage_path("app/public/reports/*/report_{$sub->id}_*.jpg"));
                if (!empty($matches)) {
                    $rel = str_replace([storage_path('app/public/'), '\\'], ['', '/'], $matches[0]);
                    $photoUrl = asset('storage/' . ltrim($rel, '/'));
                }
            }

            $ratio = $subDimasak > 0 ? round($subCup / $subDimasak, 1) : 0;
            $sumDimasak += $subDimasak;
            $sumCup += $subCup;
            $sumAwal += $subAwal;
            $sumSisa += $subSisa;

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValue("B{$currRow}", $sub->id);
            $sheet->setCellValue("C{$currRow}", $subDate);
            $sheet->setCellValueExplicit("D{$currRow}", $empName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $empNik, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currRow}", $storeName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currRow}", $branchName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currRow}", $regionName, DataType::TYPE_STRING);
            $sheet->setCellValue("I{$currRow}", $subDimasak);
            $sheet->setCellValue("J{$currRow}", $subCup);
            $sheet->setCellValue("K{$currRow}", $ratio);
            $sheet->setCellValue("L{$currRow}", $subAwal);
            $sheet->setCellValue("M{$currRow}", $subSisa);
            $sheet->setCellValueExplicit("N{$currRow}", !empty($variantDetails) ? implode('; ', $variantDetails) : '-', DataType::TYPE_STRING);

            if ($photoUrl) {
                $sheet->setCellValue("O{$currRow}", 'Buka Foto');
                $sheet->getCell("O{$currRow}")->getHyperlink()->setUrl($photoUrl);
                $sheet->getStyle("O{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2563EB'))->setUnderline(true);
            } else {
                $sheet->setCellValue("O{$currRow}", '-');
            }

            $sheet->setCellValue("P{$currRow}", $sub->is_within_radius ? 'Valid' : 'Luar Radius');

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("K{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("K{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("M{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("O{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("P{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:P{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI SELURUH SUBMISI');
            $sheet->mergeCells("A{$currRow}:H{$currRow}");
            $sheet->setCellValue("I{$currRow}", $sumDimasak);
            $sheet->setCellValue("J{$currRow}", $sumCup);
            $sheet->setCellValue("K{$currRow}", $sumDimasak > 0 ? round($sumCup / $sumDimasak, 1) : 0);
            $sheet->setCellValue("L{$currRow}", $sumAwal);
            $sheet->setCellValue("M{$currRow}", $sumSisa);

            $this->applySummaryRowStyle($sheet, $currRow, 16);
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("K{$currRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("K{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("M{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 16);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data submisi sampling');
            $sheet->mergeCells("A7:P7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 16);
    }

    // =========================================================================
    // TOOLS / PERALATAN BUILDERS
    // =========================================================================

    protected function buildToolsSummarySheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $kpis = $data['kpis'] ?? [];

        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'EXECUTIVE SUMMARY - LAPORAN INSPEKSI PROPERTI & TOOLS MBR',
            $filters,
            6
        );

        $sheet->setCellValue('A6', '1. RINGKASAN KETERSEDIAAN & KELAYAKAN PROPERTI / TOOLS (KPI)');
        $this->applySectionHeaderStyle($sheet, 'A6:F6');

        $headersKpi = ['Parameter Inspeksi', 'Jumlah / Persentase', 'Satuan', 'Keterangan Analitis'];
        $this->applyTableHeaders($sheet, 7, $headersKpi, ['A' => 1, 'B' => 2, 'C' => 1, 'D' => 2]);

        $totalSubs = (int)($kpis['total_submissions'] ?? 0);
        $totalAda = (int)($kpis['total_ada'] ?? 0);
        $pctAda = (float)($kpis['percent_ada'] ?? 0);
        $totalTidak = (int)($kpis['total_tidak'] ?? 0);
        $pctTidak = (float)($kpis['percent_tidak'] ?? 0);
        $totalBagus = (int)($kpis['total_bagus'] ?? 0);
        $pctBagus = (float)($kpis['percent_bagus'] ?? 0);
        $totalTidakBagus = (int)($kpis['total_tidak_bagus'] ?? 0);
        $pctTidakBagus = (float)($kpis['percent_tidak_bagus'] ?? 0);
        $uniqueStores = (int)($kpis['unique_stores'] ?? 0);
        $uniqueMitras = (int)($kpis['unique_mitras'] ?? 0);
        $defectCount = (int)($kpis['total_defect_photos'] ?? 0);

        $kpiRows = [
            ['Total Formulir Inspeksi Masuk', $totalSubs, 'Formulir', 'Total laporan pemeriksaan alat yang terverifikasi'],
            ['Status Ketersediaan: ADA', $totalAda, "Item ({$pctAda}%)", 'Alat fisik dilaporkan tersedia lengkap di outlet'],
            ['Status Ketersediaan: TIDAK ADA', $totalTidak, "Item ({$pctTidak}%)", 'Alat dilaporkan hilang / belum didistribusikan'],
            ['Kondisi Fisik: BAGUS (Layak Pakai)', $totalBagus, "Item ({$pctBagus}%)", 'Alat dalam kondisi prima dan bersih siap pakai'],
            ['Kondisi Fisik: RUSAK / CACAT', $totalTidakBagus, "Item ({$pctTidakBagus}%)", 'Alat mengalami kerusakan atau perlu penggantian'],
            ['Jumlah Toko / Outlet Terinspeksi', $uniqueStores, 'Outlet', 'Outlet yang telah diperiksa kelengkapan peralatannya'],
            ['Jumlah Mitra / Petugas Melapor', $uniqueMitras, 'Orang', 'Petugas SPG/MD yang melakukan checklist'],
            ['Total Temuan Fisik Alat Rusak', $defectCount, 'Kasus', 'Jumlah dokumentasi foto kerusakan yang tervalidasi'],
        ];

        $currRow = 8;
        foreach ($kpiRows as $idx => $r) {
            $sheet->setCellValue('A' . $currRow, $r[0]);
            $sheet->setCellValueExplicit('B' . $currRow, (string)$r[1], DataType::TYPE_STRING);
            $sheet->mergeCells("B{$currRow}:C{$currRow}");
            $sheet->setCellValue('D' . $currRow, $r[2]);
            $sheet->setCellValue('E' . $currRow, $r[3]);
            $sheet->mergeCells("E{$currRow}:F{$currRow}");

            $sheet->getStyle("A{$currRow}:F{$currRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight baris
            if (str_contains($r[0], 'RUSAK') && $totalTidakBagus > 0) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_DANGER_BG);
                $sheet->getStyle("A{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT));
            } elseif (str_contains($r[0], 'BAGUS')) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SUCCESS_BG);
            } elseif ($idx % 2 === 1) {
                $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }
        $this->applyDataBorders($sheet, 7, $currRow - 1, 1, 6);

        // Section 2: Ringkasan Status Evaluasi Kesiapan
        $currRow += 2;
        $sheet->setCellValue('A' . $currRow, '2. EVALUASI KESIAPAN PROPERTI SAMPLING DI LAPANGAN');
        $this->applySectionHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $currRow++;

        $sheet->setCellValue("A{$currRow}", 'Dimensi Evaluasi');
        $sheet->mergeCells("A{$currRow}:B{$currRow}");
        $sheet->setCellValue("C{$currRow}", 'Tingkat Kepatuhan (%)');
        $sheet->mergeCells("C{$currRow}:D{$currRow}");
        $sheet->setCellValue("E{$currRow}", 'Status Rekomendasi Operasional');
        $sheet->mergeCells("E{$currRow}:F{$currRow}");
        $this->applyRowHeaderStyle($sheet, "A{$currRow}:F{$currRow}");
        $evalHeaderRow = $currRow;
        $currRow++;

        $evalItems = [
            ['Ketersediaan Properti (ADA)', $pctAda / 100, $pctAda >= 90 ? 'SANGAT BAIK (Lengkap)' : ($pctAda >= 75 ? 'CUKUP (Perlu Distribusi Tambahan)' : 'KRITIS (Banyak Alat Hilang)')],
            ['Kelayakan Fisik (BAGUS)', $pctBagus / 100, $pctBagus >= 95 ? 'PRIMA (Alat Bersih & Layak)' : ($pctBagus >= 80 ? 'WASPADA (Ada Kerusakan Minor)' : 'BAHAYA (Segera Kirim Alat Pengganti)')],
        ];

        foreach ($evalItems as $idx => $e) {
            $sheet->setCellValue("A{$currRow}", $e[0]);
            $sheet->mergeCells("A{$currRow}:B{$currRow}");
            $sheet->setCellValue("C{$currRow}", $e[1]);
            $sheet->mergeCells("C{$currRow}:D{$currRow}");
            $sheet->setCellValue("E{$currRow}", $e[2]);
            $sheet->mergeCells("E{$currRow}:F{$currRow}");

            $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx === 0 && $pctAda >= 90) {
                $sheet->getStyle("E{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_SUCCESS_TEXT))->setBold(true);
            } elseif ($idx === 1 && $pctBagus < 80) {
                $sheet->getStyle("E{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT))->setBold(true);
            }
            $currRow++;
        }
        $this->applyDataBorders($sheet, $evalHeaderRow, $currRow - 1, 1, 6);

        $this->autoFitColumns($sheet, 6);
    }

    protected function buildTools13ItemsSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'REKAPITULASI STATUS & KONDISI 13 ALAT STANDAR FREE TASTE',
            $filters,
            10
        );

        $headers = [
            'No',
            'Nama Properti / Alat Standar',
            'Total Cek',
            'Ketersediaan (ADA)',
            'Tidak Ada (TIDAK)',
            '% Ketersediaan',
            'Kondisi BAGUS',
            'Kondisi RUSAK',
            '% Kelayakan (Bagus)',
            'Status Evaluasi Kesiapan'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $breakdown = $data['tools_breakdown'] ?? [];
        $currRow = 7;
        $counter = 1;

        $sumCek = 0;
        $sumAda = 0;
        $sumTidak = 0;
        $sumBagus = 0;
        $sumRusak = 0;

        foreach ($breakdown as $item) {
            $name = $item['name'] ?? '-';
            $cek = (int)($item['total_inspected'] ?? 0);
            $ada = (int)($item['ada'] ?? 0);
            $tidak = (int)($item['tidak'] ?? 0);
            $bagus = (int)($item['bagus'] ?? 0);
            $rusak = (int)($item['tidak_bagus'] ?? 0);

            $sumCek += $cek;
            $sumAda += $ada;
            $sumTidak += $tidak;
            $sumBagus += $bagus;
            $sumRusak += $rusak;

            $pctAda = $cek > 0 ? ($ada / $cek) : 0;
            $pctBagus = $ada > 0 ? ($bagus / $ada) : 0;

            $statusText = 'SIAP DIGUNAKAN';
            if ($tidak > 0 && $rusak > 0) {
                $statusText = 'PERLU PENGGANTIAN & DROP BARU';
            } elseif ($rusak > 0) {
                $statusText = 'TERDAPAT UNIT RUSAK';
            } elseif ($tidak > 0) {
                $statusText = 'DISTRIBUSI BELUM LENGKAP';
            }

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValueExplicit("B{$currRow}", $name, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $cek);
            $sheet->setCellValue("D{$currRow}", $ada);
            $sheet->setCellValue("E{$currRow}", $tidak);
            $sheet->setCellValue("F{$currRow}", $pctAda);
            $sheet->setCellValue("G{$currRow}", $bagus);
            $sheet->setCellValue("H{$currRow}", $rusak);
            $sheet->setCellValue("I{$currRow}", $pctBagus);
            $sheet->setCellValue("J{$currRow}", $statusText);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}:E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currRow}:H{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($rusak > 0) {
                $sheet->getStyle("H{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT))->setBold(true);
                $sheet->getStyle("J{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT))->setBold(true);
            }

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:J{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        // Summary row
        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI');
            $sheet->mergeCells("A{$currRow}:B{$currRow}");
            $sheet->setCellValue("C{$currRow}", $sumCek);
            $sheet->setCellValue("D{$currRow}", $sumAda);
            $sheet->setCellValue("E{$currRow}", $sumTidak);
            $sheet->setCellValue("F{$currRow}", $sumCek > 0 ? ($sumAda / $sumCek) : 0);
            $sheet->setCellValue("G{$currRow}", $sumBagus);
            $sheet->setCellValue("H{$currRow}", $sumRusak);
            $sheet->setCellValue("I{$currRow}", $sumAda > 0 ? ($sumBagus / $sumAda) : 0);
            $sheet->setCellValue("J{$currRow}", $sumRusak > 0 ? 'SEGERA REKONDISI UNIT RUSAK' : 'SEMUA PROPERTI PRIMA');

            $this->applySummaryRowStyle($sheet, $currRow, 10);
            $sheet->getStyle("C{$currRow}:E{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currRow}:H{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 10);
        }

        $this->autoFitColumns($sheet, 10);
    }

    protected function buildToolsDefectsSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'DAFTAR TEMUAN ALAT / PROPERTI RUSAK (DEFECT REPORT)',
            $filters,
            9
        );

        $headers = [
            'No',
            'Tanggal & Jam Cek',
            'Nama Toko / Outlet',
            'Cabang / Area',
            'Region / Wilayah',
            'Petugas / Mitra Pelapor',
            'Nama Alat Yang Rusak',
            'Kondisi & Catatan Kerusakan',
            'Link URL Foto Bukti Fisik Rusak'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $defectGallery = $data['defect_gallery'] ?? [];
        $currRow = 7;
        $counter = 1;

        foreach ($defectGallery as $item) {
            $time = $item['time'] ?? '-';
            $store = $item['store'] ?? ($item['store_name'] ?? '-');
            $area = $item['area'] ?? '-';
            $region = $item['region'] ?? '-';
            $mitra = $item['mitra'] ?? ($item['employee_name'] ?? '-');
            $tool = $item['tool'] ?? ($item['tool_name'] ?? 'Peralatan');
            $notes = $item['notes'] ?? 'Kondisi fisik rusak / tidak layak pakai';
            $photoUrl = $item['url'] ?? ($item['photo_url'] ?? null);

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValue("B{$currRow}", $time);
            $sheet->setCellValueExplicit("C{$currRow}", $store, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currRow}", $area, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $region, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currRow}", $mitra, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currRow}", $tool, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currRow}", $notes, DataType::TYPE_STRING);

            if ($photoUrl) {
                $sheet->setCellValue("I{$currRow}", 'Buka Foto Bukti Rusak');
                $sheet->getCell("I{$currRow}")->getHyperlink()->setUrl($photoUrl);
                $sheet->getStyle("I{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2563EB'))->setUnderline(true);
            } else {
                $sheet->setCellValue("I{$currRow}", '-');
            }

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT));
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $this->applyDataBorders($sheet, $startRow, $currRow - 1, 1, 9);
        } else {
            $sheet->setCellValue("A7", 'Alhamdulillah, tidak ada temuan alat rusak pada periode ini');
            $sheet->mergeCells("A7:I7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_SUCCESS_TEXT));
            $sheet->getStyle("A7:I7")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SUCCESS_BG);
        }

        $this->autoFitColumns($sheet, 9);
    }

    protected function buildToolsMitraActivitySheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'AKTIVITAS PETUGAS / MITRA PELAPOR PROPERTI TOOLS',
            $filters,
            9
        );

        $headers = [
            'Rank',
            'Nama Mitra / Petugas',
            'NIK Petugas',
            'Toko Penugasan',
            'Area / Cabang',
            'Total Checklist Dikirim',
            'Alat Lengkap (ADA)',
            'Temuan Alat Rusak',
            'Kepatuhan Checklist (%)'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $activeEmployees = $data['active_employees'] ?? [];
        $currRow = 7;
        $counter = 1;

        $sumTotal = 0;
        $sumAda = 0;
        $sumRusak = 0;

        foreach ($activeEmployees as $emp) {
            $name = $emp['name'] ?? '-';
            $nik = $emp['nik'] ?? '-';
            $store = $emp['store_name'] ?? '-';
            $area = $emp['branch_name'] ?? '-';
            $total = (int)($emp['total_reports'] ?? 0);
            $ada = (int)($emp['count_ada'] ?? 0);
            $rusak = (int)($emp['count_tidak_bagus'] ?? 0);
            $pctKepatuhan = $total > 0 ? ($ada / $total) : 0;

            $sumTotal += $total;
            $sumAda += $ada;
            $sumRusak += $rusak;

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValueExplicit("B{$currRow}", $name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currRow}", $nik, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currRow}", $store, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $area, DataType::TYPE_STRING);
            $sheet->setCellValue("F{$currRow}", $total);
            $sheet->setCellValue("G{$currRow}", $ada);
            $sheet->setCellValue("H{$currRow}", $rusak);
            $sheet->setCellValue("I{$currRow}", $pctKepatuhan);

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$currRow}:H{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($rusak > 0) {
                $sheet->getStyle("H{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT))->setBold(true);
            }

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL AKUMULASI');
            $sheet->mergeCells("A{$currRow}:E{$currRow}");
            $sheet->setCellValue("F{$currRow}", $sumTotal);
            $sheet->setCellValue("G{$currRow}", $sumAda);
            $sheet->setCellValue("H{$currRow}", $sumRusak);
            $sheet->setCellValue("I{$currRow}", $sumTotal > 0 ? ($sumAda / $sumTotal) : 0);

            $this->applySummaryRowStyle($sheet, $currRow, 9);
            $sheet->getStyle("F{$currRow}:H{$currRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("I{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyDataBorders($sheet, $startRow, $currRow, 1, 9);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data petugas pelapor');
            $sheet->mergeCells("A7:I7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 9);
    }

    protected function buildToolsRawSubmissionsSheet(Worksheet $sheet, ReportTemplate $template, array $data, array $filters): void
    {
        $sheet->setShowGridlines(true);
        $this->applyExecutiveBanner(
            $sheet,
            'PT WINGS SURYA',
            'DATA MENTAH SUBMISI INSPEKSI PROPERTI / TOOLS LENGKAP',
            $filters,
            14
        );

        $headers = [
            'No',
            'ID Submisi',
            'Tanggal & Jam Cek',
            'Nama Petugas / SPG',
            'NIK Petugas',
            'Toko / Outlet',
            'Area / Cabang',
            'Region',
            'Nama Alat Yang Diinspeksi',
            'Status Ketersediaan',
            'Kondisi Fisik',
            'Catatan Kerusakan',
            'Link URL Foto Bukti',
            'Valid Radius GPS'
        ];

        $startRow = 6;
        $this->applyTableHeaders($sheet, $startRow, $headers);
        $sheet->freezePane('A7');

        $submissions = $data['submissions'] ?? collect();
        $currRow = 7;
        $counter = 1;

        foreach ($submissions as $sub) {
            $subDate = $sub->submitted_at ? $sub->submitted_at->format('Y-m-d H:i:s') : ($sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : '-');
            $emp = $sub->employee;
            $empName = $emp ? ($emp->full_name ?: $emp->name) : 'Petugas Lapangan';
            $empNik = $emp ? ($emp->employee_no ?: ($emp->nik ?: '-')) : '-';
            $wl = $sub->workLocation;
            $storeName = $wl ? $wl->name : ($sub->store_name ?: 'Outlet');
            $branchName = $wl && $wl->branch ? $wl->branch->name : ($emp && $emp->branch ? $emp->branch->name : '-');
            $regionName = $wl && !empty($wl->region) ? $wl->region : ($emp && $emp->branch && !empty($emp->branch->region) ? $emp->branch->region : '-');

            $toolName = null;
            $availability = 'ADA';
            $condition = 'BAGUS';
            $notes = '-';
            $photoUrl = null;

            foreach ($sub->values as $val) {
                $fn = strtolower(trim($val->field_name ?? ''));
                $fl = strtolower(trim($val->formField?->field_label ?? ($val->formField?->label ?? '')));
                $txt = trim((string)($val->value_text ?? ''));

                if ($fn === 'foto_tools' || $val->field_type === 'camera_photo' || str_contains($fn, 'foto') || str_contains($fl, 'foto') || !empty($val->media_url)) {
                    $cand = $val->media_url ?: ($txt ?: (is_array($val->value_json) ? ($val->value_json[0] ?? null) : null));
                    if ($cand && is_string($cand) && !str_starts_with($cand, '/data/user/')) {
                        $photoUrl = $this->resolvePublicMediaUrl($cand);
                    }
                } elseif ($fn === 'kondisi_tools' || str_contains($fn, 'kondisi') || str_contains($fl, 'kondisi')) {
                    if (!empty($txt)) $condition = strtoupper($txt);
                } elseif ($fn === 'status_ketersediaan' || str_contains($fn, 'ketersediaan') || str_contains($fl, 'ketersediaan')) {
                    if (!empty($txt)) $availability = strtoupper($txt);
                } elseif ($fn === 'nama_tools' || $fn === 'pilih_tools' || str_contains($fn, 'tools') || str_contains($fl, 'alat') || str_contains($fl, 'tools')) {
                    if (!empty($txt)) $toolName = $txt;
                } elseif (str_contains($fn, 'catatan') || str_contains($fl, 'catatan') || str_contains($fn, 'keterangan')) {
                    if (!empty($txt)) $notes = $txt;
                }
            }

            if (!$photoUrl) {
                $matches = glob(storage_path("app/public/reports/*/report_{$sub->id}_*.jpg"));
                if (!empty($matches)) {
                    $rel = str_replace([storage_path('app/public/'), '\\'], ['', '/'], $matches[0]);
                    $photoUrl = asset('storage/' . ltrim($rel, '/'));
                }
            }

            // Fallback second pass for toolName
            if (!$toolName) {
                $standardTools = $data['standard_tools'] ?? [];
                foreach ($sub->values as $val) {
                    $txt = trim((string)($val->value_text ?? ''));
                    if (!empty($txt)) {
                        foreach ($standardTools as $st) {
                            if (strcasecmp($st, $txt) === 0 || str_contains(strtolower($txt), strtolower($st))) {
                                $toolName = $st;
                                break 2;
                            }
                        }
                    }
                }
            }

            $availability = ($availability === 'ADA') ? 'ADA' : (($availability === 'TIDAK' || $availability === 'TIDAK ADA') ? 'TIDAK' : 'ADA');
            if ($availability === 'ADA') {
                if ($condition === 'TIDAK BAGUS' || $condition === 'RUSAK') {
                    $condition = 'TIDAK BAGUS (RUSAK)';
                } else {
                    $condition = 'BAGUS';
                }
            } else {
                $condition = '-';
            }

            $sheet->setCellValue("A{$currRow}", $counter++);
            $sheet->setCellValue("B{$currRow}", $sub->id);
            $sheet->setCellValue("C{$currRow}", $subDate);
            $sheet->setCellValueExplicit("D{$currRow}", $empName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currRow}", $empNik, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currRow}", $storeName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currRow}", $branchName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currRow}", $regionName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$currRow}", $toolName ?: '1 Pcs panci susu', DataType::TYPE_STRING);
            $sheet->setCellValue("J{$currRow}", $availability);
            $sheet->setCellValue("K{$currRow}", $condition);
            $sheet->setCellValueExplicit("L{$currRow}", $notes, DataType::TYPE_STRING);

            if ($photoUrl) {
                $sheet->setCellValue("M{$currRow}", 'Buka Foto');
                $sheet->getCell("M{$currRow}")->getHyperlink()->setUrl($photoUrl);
                $sheet->getStyle("M{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2563EB'))->setUnderline(true);
            } else {
                $sheet->setCellValue("M{$currRow}", '-');
            }

            $sheet->setCellValue("N{$currRow}", $sub->is_within_radius ? 'Valid' : 'Luar Radius');

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Conditional formatting
            if (str_contains($condition, 'RUSAK')) {
                $sheet->getStyle("K{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT))->setBold(true);
            }
            if ($availability === 'TIDAK') {
                $sheet->getStyle("J{$currRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_DANGER_TEXT))->setBold(true);
            }

            if ($counter % 2 === 0) {
                $sheet->getStyle("A{$currRow}:N{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ZEBRA_ROW);
            }
            $currRow++;
        }

        if ($currRow > 7) {
            $this->applyDataBorders($sheet, $startRow, $currRow - 1, 1, 14);
        } else {
            $sheet->setCellValue("A7", 'Tidak ada data submisi inspeksi tools');
            $sheet->mergeCells("A7:N7");
            $sheet->getStyle("A7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->autoFitColumns($sheet, 14);
    }

    // =========================================================================
    // STYLING & FORMATTING HELPERS
    // =========================================================================

    /**
     * Apply Executive Company Header Banner
     */
    protected function applyExecutiveBanner(Worksheet $sheet, string $company, string $reportTitle, array $filters, int $lastColIndex): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        // Row 1: Company Name
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $company);
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PRIMARY_DARK);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Row 2: Report Title
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $reportTitle);
        $sheet->getStyle('A2')->getFont()->setSize(11)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PRIMARY_LIGHT);
        $sheet->getRowDimension(2)->setRowHeight(24);

        // Row 3: Metadata Filters Info
        $sheet->mergeCells("A3:{$lastCol}3");
        $metaText = 'Periode: ' . ($filters['period_label'] ?? '-') . 
                    ' | Wilayah: ' . ($filters['region'] ?? 'Semua Region') . 
                    ' | Cabang: ' . ($filters['area'] ?? 'Semua Cabang') . 
                    ' | Outlet: ' . ($filters['store'] ?? 'Semua Outlet') . 
                    ' | Diunduh: ' . Carbon::now()->translatedFormat('d F Y, H:i:s') . ' WIB';
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('334155'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A3:{$lastCol}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SUBHEADER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Row 4 & 5: Spacing
        $sheet->getRowDimension(4)->setRowHeight(8);
        $sheet->getRowDimension(5)->setRowHeight(8);
    }

    /**
     * Section Divider Header Style
     */
    protected function applySectionHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->getFont()->setSize(10)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PRIMARY_DARK);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);

        $firstCell = explode(':', $range)[0];
        $rowNum = (int)preg_replace('/[^0-9]/', '', $firstCell);
        $sheet->getRowDimension($rowNum)->setRowHeight(24);
    }

    /**
     * Row Header Style (for mini tables)
     */
    protected function applyRowHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setSize(9)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PRIMARY_LIGHT);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        $firstCell = explode(':', $range)[0];
        $rowNum = (int)preg_replace('/[^0-9]/', '', $firstCell);
        $sheet->getRowDimension($rowNum)->setRowHeight(24);
    }

    /**
     * Table Header Style with Column Merging Support
     */
    protected function applyTableHeaders(Worksheet $sheet, int $row, array $headers, array $spans = []): void
    {
        $colIndex = 1;
        foreach ($headers as $idx => $label) {
            $span = 1;
            $colKey = Coordinate::stringFromColumnIndex($colIndex);
            if (isset($spans[$colKey])) {
                $span = $spans[$colKey];
            }

            $sheet->setCellValueByColumnAndRow($colIndex, $row, $label);
            if ($span > 1) {
                $startCol = Coordinate::stringFromColumnIndex($colIndex);
                $endCol = Coordinate::stringFromColumnIndex($colIndex + $span - 1);
                $sheet->mergeCells("{$startCol}{$row}:{$endCol}{$row}");
                $colIndex += $span;
            } else {
                $colIndex++;
            }
        }

        $lastCol = Coordinate::stringFromColumnIndex($colIndex - 1);
        $range = "A{$row}:{$lastCol}{$row}";

        $sheet->getStyle($range)->getFont()->setSize(9)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PRIMARY_LIGHT);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(28);
    }

    /**
     * Apply Summary / Total Row Style
     */
    protected function applySummaryRowStyle(Worksheet $sheet, int $row, int $lastColIndex): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);
        $range = "A{$row}:{$lastCol}{$row}";

        $sheet->getStyle($range)->getFont()->setSize(9)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_TOTAL_ROW);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(24);

        $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::COLOR_BORDER_DARK);
        $sheet->getStyle($range)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::COLOR_PRIMARY_DARK);
    }

    /**
     * Apply Thin Data Borders across range
     */
    protected function applyDataBorders(Worksheet $sheet, int $startRow, int $endRow, int $startCol, int $endCol): void
    {
        $startColStr = Coordinate::stringFromColumnIndex($startCol);
        $endColStr = Coordinate::stringFromColumnIndex($endCol);
        $range = "{$startColStr}{$startRow}:{$endColStr}{$endRow}";

        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::COLOR_BORDER);
        $sheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::COLOR_BORDER_DARK);
    }

    /**
     * Auto-Fit Column Widths with Minimum Width padding
     */
    protected function autoFitColumns(Worksheet $sheet, int $lastColIndex): void
    {
        for ($i = 1; $i <= $lastColIndex; $i++) {
            $colStr = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colStr)->setAutoSize(true);
        }
    }

    /**
     * Stream Spreadsheet as HTTP Response
     */
    protected function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Resolve Public Media URL
     */
    protected function resolvePublicMediaUrl(?string $raw): ?string
    {
        if (empty($raw) || !is_string($raw)) return null;
        $clean = trim($raw);

        if (str_starts_with($clean, '/data/user/') || str_contains($clean, 'cache/wm_')) {
            return null;
        }

        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
            return str_replace(['/storage/storage/', 'esa-solution.id'], ['/storage/', 'esa-solutions.id'], $clean);
        }

        if (str_starts_with($clean, 'storage/')) {
            $clean = substr($clean, 8);
        } elseif (str_starts_with($clean, '/storage/')) {
            $clean = substr($clean, 9);
        } elseif (str_starts_with($clean, 'public/')) {
            $clean = substr($clean, 7);
        }

        return asset('storage/' . ltrim($clean, '/'));
    }
}
