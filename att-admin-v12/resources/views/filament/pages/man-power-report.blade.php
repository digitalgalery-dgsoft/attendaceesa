<x-filament-panels::page>
    <style>
        .report-page-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .report-card {
            background: #1e293b;
            border-color: #334155;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
        }
        @media (min-width: 640px) {
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .kpi-card {
            background: #1e293b;
            border-color: #334155;
        }

        .report-table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #94a3b8;
            border-radius: 8px;
            background: #ffffff;
        }
        .dark .report-table-container {
            border-color: #475569;
            background: #0f172a;
        }

        .report-bordered-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            color: #1e293b;
            min-width: 100%;
        }
        .dark .report-bordered-table {
            color: #f1f5f9;
        }

        .report-bordered-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            border-right: 1px solid #cbd5e1;
            border-bottom: 2px solid #94a3b8;
            white-space: nowrap;
        }
        .dark .report-bordered-table th {
            background: #1e293b;
            color: #f8fafc;
            border-right-color: #334155;
            border-bottom-color: #475569;
        }

        .report-bordered-table td {
            padding: 10px 14px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .dark .report-bordered-table td {
            border-right-color: #334155;
            border-bottom-color: #334155;
        }

        .report-bordered-table th:last-child,
        .report-bordered-table td:last-child {
            border-right: none;
        }

        .report-bordered-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .dark .report-bordered-table tbody tr:nth-child(even) {
            background: #182234;
        }

        .report-bordered-table tbody tr:hover {
            background: #e2e8f0 !important;
        }
        .dark .report-bordered-table tbody tr:hover {
            background: #334155 !important;
        }

        .report-bordered-table .sticky-col {
            position: sticky;
            left: 0;
            z-index: 10;
            background: #ffffff;
            border-right: 2px solid #94a3b8 !important;
            min-width: 220px;
            font-weight: 600;
        }
        .dark .report-bordered-table .sticky-col {
            background: #0f172a;
            border-right-color: #475569 !important;
        }
        .report-bordered-table thead .sticky-col {
            background: #f1f5f9;
            z-index: 20;
        }
        .dark .report-bordered-table thead .sticky-col {
            background: #1e293b;
        }
        .report-bordered-table tfoot .sticky-col {
            background: #e2e8f0;
            z-index: 20;
        }
        .dark .report-bordered-table tfoot .sticky-col {
            background: #334155;
        }

        .avg-col {
            background: #eef2ff !important;
            color: #4338ca !important;
            font-weight: 800 !important;
            text-align: center;
            border-left: 2px solid #94a3b8 !important;
        }
        .dark .avg-col {
            background: #312e81 !important;
            color: #e0e7ff !important;
            border-left-color: #475569 !important;
        }

        .report-bordered-table tfoot td {
            background: #e2e8f0;
            font-weight: 800;
            border-top: 2px solid #64748b;
            color: #0f172a;
        }
        .dark .report-bordered-table tfoot td {
            background: #334155;
            border-top-color: #64748b;
            color: #f8fafc;
        }
    </style>

    @php
        $tableData = $this->getManPowerData();
        $totalCompanies = count($tableData);
        $totalSumAvg = collect($tableData)->sum(fn($r) => $r['months'][12] ?? 0);
        $avgPerCompany = $totalCompanies > 0 ? round($totalSumAvg / $totalCompanies) : 0;
        
        // Hitung total manpower per bulan untuk baris footer
        $monthlyTotals = array_fill(0, 13, 0);
        foreach ($tableData as $row) {
            foreach ($row['months'] as $mIdx => $mVal) {
                $monthlyTotals[$mIdx] += $mVal;
            }
        }
    @endphp

    <div class="report-page-wrapper">
        {{-- KPI TOP SUMMARY --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Perusahaan</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ number_format($totalCompanies) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Tahun {{ $year ?: date('Y') }}</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-building-office-2" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">Total Manpower (Rata-rata)</div>
                    <div style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 4px;">{{ number_format($totalSumAvg) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Akumulasi seluruh unit</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-users" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase;">Rata-rata per Entitas</div>
                    <div style="font-size: 24px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ number_format($avgPerCompany) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Karyawan per perusahaan</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-chart-bar" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase;">Periode Laporan</div>
                    <div style="font-size: 24px; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ $year ?: date('Y') }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Januari - Desember</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-calendar" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>

        {{-- FILTER FORM CARD --}}
        <div class="report-card">
            <form wire:submit.prevent="submit">
                {{ $this->form }}
            </form>
        </div>

        {{-- CHART SECTION WITH DYNAMIC LIVEWIRE KEY --}}
        <div class="report-card">
            <div style="margin-bottom: 12px; font-size: 15px; font-weight: 800; color: #0f172a;">
                Grafik Tren Manpower (Tahun {{ $year ?: date('Y') }})
            </div>
            @livewire(\App\Filament\Widgets\ManPowerChartWidget::class, [
                'year' => $year,
                'company_id' => $company_id,
                'branch_id' => $branch_id
            ], key('manpower-chart-'.$year.'-'.$company_id.'-'.$branch_id))
        </div>

        {{-- DATA TABLE SECTION --}}
        <div class="report-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-table-cells" style="width: 20px; height: 20px; color: #4f46e5;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Tabel Rekapitulasi Manpower per Bulan</span>
                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 700; background: #e0e7ff; color: #3730a3;">
                        {{ $totalCompanies }} Perusahaan
                    </span>
                </div>
                <span style="font-size: 12px; color: #64748b;">
                    Satuan: Jumlah Karyawan Aktif
                </span>
            </div>

            <div class="report-table-container">
                <table class="report-bordered-table">
                    <thead>
                        <tr>
                            <th class="sticky-col" style="text-align: left;">Perusahaan</th>
                            <th style="text-align: center;">Jan</th>
                            <th style="text-align: center;">Feb</th>
                            <th style="text-align: center;">Mar</th>
                            <th style="text-align: center;">Apr</th>
                            <th style="text-align: center;">Mei</th>
                            <th style="text-align: center;">Jun</th>
                            <th style="text-align: center;">Jul</th>
                            <th style="text-align: center;">Agu</th>
                            <th style="text-align: center;">Sep</th>
                            <th style="text-align: center;">Okt</th>
                            <th style="text-align: center;">Nov</th>
                            <th style="text-align: center;">Des</th>
                            <th class="avg-col" style="min-width: 90px;">Total (Avg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableData as $row)
                            <tr>
                                <td class="sticky-col">{{ $row['company'] }}</td>
                                @foreach($row['months'] as $index => $val)
                                    @if($index < 12)
                                        <td style="text-align: center; color: #334155; font-weight: {{ $val > 0 ? '600' : 'normal' }};">
                                            {{ $val > 0 ? number_format($val) : '-' }}
                                        </td>
                                    @else
                                        <td class="avg-col">
                                            {{ number_format($val) }}
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" style="padding: 30px; text-align: center; color: #64748b;">
                                    Tidak ada data manpower yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($tableData) > 0)
                        <tfoot>
                            <tr>
                                <td class="sticky-col" style="text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                                    Total Manpower
                                </td>
                                @foreach($monthlyTotals as $index => $totalVal)
                                    @if($index < 12)
                                        <td style="text-align: center; font-weight: 800; color: #0f172a;">
                                            {{ number_format($totalVal) }}
                                        </td>
                                    @else
                                        <td class="avg-col" style="font-size: 14px; font-weight: 900; background: #4f46e5 !important; color: #ffffff !important;">
                                            {{ number_format($totalVal) }}
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
