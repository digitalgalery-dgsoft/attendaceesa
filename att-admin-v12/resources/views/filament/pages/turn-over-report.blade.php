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

        .badge-stat {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-join {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-resign {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .badge-net-pos {
            background: #e0e7ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
        }
        .badge-net-neg {
            background: #ffe4e6;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }
        .badge-net-zero {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
    </style>

    @php
        $tableData = $this->getTurnOverData();
        $totalJoined = collect($tableData)->sum('joined');
        $totalResigned = collect($tableData)->sum('resigned');
        $totalNet = $totalJoined - $totalResigned;
    @endphp

    <div class="report-page-wrapper">
        {{-- KPI TOP SUMMARY --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">Karyawan Masuk (Join)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">+{{ number_format($totalJoined) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Tahun {{ $year ?: date('Y') }}</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-user-plus" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #dc2626; text-transform: uppercase;">Karyawan Keluar (Resign)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #dc2626; margin-top: 4px;">-{{ number_format($totalResigned) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Tahun {{ $year ?: date('Y') }}</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-user-minus" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: {{ $totalNet >= 0 ? '#4338ca' : '#be123c' }}; text-transform: uppercase;">Net Pertumbuhan</div>
                    <div style="font-size: 26px; font-weight: 800; color: {{ $totalNet >= 0 ? '#4338ca' : '#be123c' }}; margin-top: 4px;">
                        {{ $totalNet > 0 ? '+' : '' }}{{ number_format($totalNet) }}
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Selisih Masuk - Keluar</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: {{ $totalNet >= 0 ? '#e0e7ff' : '#ffe4e6' }}; color: {{ $totalNet >= 0 ? '#4338ca' : '#be123c' }}; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-scale" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase;">Periode Analisis</div>
                    <div style="font-size: 26px; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ $year ?: date('Y') }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">12 Bulan Kalender</div>
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
                Grafik Perbandingan Karyawan Masuk vs Keluar (Tahun {{ $year ?: date('Y') }})
            </div>
            @livewire(\App\Filament\Widgets\TurnOverChartWidget::class, [
                'year' => $year,
                'principal_id' => $principal_id
            ], key('turnover-chart-'.$year.'-'.$principal_id))
        </div>

        {{-- DATA TABLE SECTION --}}
        <div class="report-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-table-cells" style="width: 20px; height: 20px; color: #4f46e5;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Tabel Rekapitulasi Turnover Bulanan</span>
                </div>
                <span style="font-size: 12px; color: #64748b;">
                    Total Data: 12 Bulan
                </span>
            </div>

            <div class="report-table-container">
                <table class="report-bordered-table">
                    <thead>
                        <tr>
                            <th style="text-align: left; min-width: 160px;">Bulan</th>
                            <th style="text-align: center; min-width: 140px;">Masuk (Join)</th>
                            <th style="text-align: center; min-width: 140px;">Keluar (Resign)</th>
                            <th style="text-align: center; min-width: 140px;">Net Pertumbuhan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableData as $row)
                            <tr>
                                <td style="font-weight: 700; color: #0f172a;">{{ $row['month'] }}</td>
                                <td style="text-align: center;">
                                    <span class="badge-stat {{ $row['joined'] > 0 ? 'badge-join' : 'badge-net-zero' }}">
                                        +{{ number_format($row['joined']) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-stat {{ $row['resigned'] > 0 ? 'badge-resign' : 'badge-net-zero' }}">
                                        -{{ number_format($row['resigned']) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if ($row['net'] > 0)
                                        <span class="badge-stat badge-net-pos">+{{ number_format($row['net']) }}</span>
                                    @elseif ($row['net'] < 0)
                                        <span class="badge-stat badge-net-neg">{{ number_format($row['net']) }}</span>
                                    @else
                                        <span class="badge-stat badge-net-zero">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding: 30px; text-align: center; color: #64748b;">
                                    Tidak ada data turnover yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($tableData) > 0)
                        <tfoot>
                            <tr>
                                <td style="text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                                    Total 1 Tahun
                                </td>
                                <td style="text-align: center; color: #059669; font-size: 14px;">
                                    +{{ number_format($totalJoined) }}
                                </td>
                                <td style="text-align: center; color: #dc2626; font-size: 14px;">
                                    -{{ number_format($totalResigned) }}
                                </td>
                                <td style="text-align: center; font-size: 14px; color: {{ $totalNet >= 0 ? '#4338ca' : '#be123c' }};">
                                    {{ $totalNet > 0 ? '+' : '' }}{{ number_format($totalNet) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
