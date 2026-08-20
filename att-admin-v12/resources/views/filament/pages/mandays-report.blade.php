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

        .percent-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }
        .percent-high {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .percent-med {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .percent-low {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>

    @php
        $allData = $this->getAllMandaysData();
        $pagination = $this->getMandaysData();
        $pagedItems = $pagination['items'];

        $totalEmps = count($allData);
        $totalTargetHK = collect($allData)->sum('target');
        $totalAktualHK = collect($allData)->sum('aktual');
        $overallPercentage = $totalTargetHK > 0 ? round(($totalAktualHK / $totalTargetHK) * 100, 1) : 0;

        $monthNames = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $selectedMonthName = $monthNames[str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT)] ?? 'Bulan Ini';
    @endphp

    <div class="report-page-wrapper">
        {{-- KPI TOP SUMMARY --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Karyawan</div>
                    <div style="font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ number_format($totalEmps) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $selectedMonthName }} {{ $year ?: date('Y') }}</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-users" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #6366f1; text-transform: uppercase;">Total Target HK</div>
                    <div style="font-size: 26px; font-weight: 800; color: #4f46e5; margin-top: 4px;">{{ number_format($totalTargetHK) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Hari Kerja Direncanakan</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #6366f1; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-flag" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">Total Aktual HK</div>
                    <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">{{ number_format($totalAktualHK) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Hari Kerja Terealisasi</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-check-badge" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: {{ $overallPercentage >= 90 ? '#059669' : ($overallPercentage >= 75 ? '#d97706' : '#dc2626') }}; text-transform: uppercase;">Pencapaian Rata-rata</div>
                    <div style="font-size: 26px; font-weight: 800; color: {{ $overallPercentage >= 90 ? '#059669' : ($overallPercentage >= 75 ? '#d97706' : '#dc2626') }}; margin-top: 4px;">
                        {{ $overallPercentage }}%
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Aktual / Target Mandays</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: {{ $overallPercentage >= 90 ? '#d1fae5' : ($overallPercentage >= 75 ? '#fef3c7' : '#fee2e2') }}; color: {{ $overallPercentage >= 90 ? '#059669' : ($overallPercentage >= 75 ? '#d97706' : '#dc2626') }}; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-chart-pie" style="width: 24px; height: 24px;" />
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
                Grafik Target vs Realisasi Mandays (Top 10 Karyawan - {{ $selectedMonthName }} {{ $year ?: date('Y') }})
            </div>
            @livewire(\App\Filament\Widgets\MandaysChartWidget::class, [
                'month' => $month,
                'year' => $year,
                'branch_id' => $branch_id,
                'principal_id' => $principal_id
            ], key('mandays-chart-'.$month.'-'.$year.'-'.$branch_id.'-'.$principal_id))
        </div>

        {{-- DATA TABLE SECTION --}}
        <div class="report-card">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-table-cells" style="width: 20px; height: 20px; color: #4f46e5;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Rincian Mandays Karyawan</span>
                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 700; background: #e0e7ff; color: #3730a3;">
                        {{ number_format($pagination['total_count']) }} Karyawan
                    </span>
                </div>

                {{-- Live Search Input --}}
                <div style="min-width: 260px;">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama, NIK, cabang, prinsiple..."
                        style="width: 100%; padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;"
                    />
                </div>
            </div>

            <div class="report-table-container">
                <table class="report-bordered-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="text-align: left; min-width: 220px;">Nama Karyawan</th>
                            <th style="text-align: left; min-width: 140px;">Region / Area</th>
                            <th style="text-align: left; min-width: 160px;">Prinsiple</th>
                            <th style="text-align: center; min-width: 110px;">Target HK</th>
                            <th style="text-align: center; min-width: 110px;">Aktual HK</th>
                            <th style="text-align: center; min-width: 130px;">Pencapaian (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagedItems as $index => $row)
                            @php
                                $rowNo = $pagination['from'] + $index;
                                $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($row['employee']) . '&background=4f46e5&color=fff&size=64';
                                if (!empty($row['photo'])) {
                                    try {
                                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($row['photo'])) {
                                            $photoUrl = asset('storage/' . $row['photo']);
                                        }
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            <tr>
                                <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $rowNo }}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img
                                            src="{{ $photoUrl }}"
                                            alt="{{ $row['employee'] }}"
                                            style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;"
                                            loading="lazy"
                                        />
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a;">{{ $row['employee'] }}</div>
                                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">NIK: {{ $row['employee_no'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #f1f5f9; border: 1px solid #cbd5e1; color: #1e293b;">
                                        {{ $row['branch'] }}
                                    </span>
                                </td>
                                <td style="color: #0f172a; font-weight: 600;">{{ $row['principal'] }}</td>
                                <td style="text-align: center; font-weight: 700; color: #4f46e5;">{{ $row['target'] }} HK</td>
                                <td style="text-align: center; font-weight: 700; color: #059669;">{{ $row['aktual'] }} HK</td>
                                <td style="text-align: center;">
                                    @php
                                        $pct = $row['percentage'];
                                    @endphp
                                    <span class="percent-badge {{ $pct >= 100 ? 'percent-high' : ($pct >= 75 ? 'percent-med' : 'percent-low') }}">
                                        {{ $pct }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 30px; text-align: center; color: #64748b;">
                                    Tidak ada data mandays yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION TOOLBAR --}}
            @if ($pagination['total_count'] > 0)
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
                    <div>
                        Menampilkan <strong style="color: #0f172a;">{{ $pagination['from'] }}</strong> - <strong style="color: #0f172a;">{{ $pagination['to'] }}</strong> dari <strong style="color: #0f172a;">{{ number_format($pagination['total_count']) }}</strong> data karyawan
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span>Per halaman:</span>
                            <select wire:model.live="perPage" style="padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button
                                type="button"
                                wire:click="previousPage"
                                @if ($pagination['page'] <= 1) disabled @endif
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $pagination['page'] <= 1 ? '0.4' : '1' }};"
                            >
                                &laquo; Sebelumnya
                            </button>

                            <span style="font-weight: 700; padding: 0 4px;">
                                Halaman {{ $pagination['page'] }} dari {{ $pagination['total_pages'] }}
                            </span>

                            <button
                                type="button"
                                wire:click="nextPage({{ $pagination['total_pages'] }})"
                                @if ($pagination['page'] >= $pagination['total_pages']) disabled @endif
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $pagination['page'] >= $pagination['total_pages'] ? '0.4' : '1' }};"
                            >
                                Selanjutnya &raquo;
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
