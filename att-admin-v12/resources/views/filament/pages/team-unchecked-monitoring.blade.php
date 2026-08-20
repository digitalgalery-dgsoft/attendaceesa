<x-filament-panels::page>
    <style>
        /* Custom Styling for Full Width & Professional Crisp Borders */
        .monitoring-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* KPI Card Styles */
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
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dark .kpi-card {
            background: #1e293b;
            border-color: #334155;
        }

        .kpi-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Card / Section Box */
        .pro-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .pro-card {
            background: #1e293b;
            border-color: #334155;
        }

        /* Professional Bordered Table Styles */
        .pro-table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #94a3b8;
            border-radius: 8px;
            background: #ffffff;
        }
        .dark .pro-table-container {
            border-color: #475569;
            background: #0f172a;
        }

        .pro-bordered-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            color: #1e293b;
            min-width: 100%;
        }
        .dark .pro-bordered-table {
            color: #f1f5f9;
        }

        .pro-bordered-table th {
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
        .dark .pro-bordered-table th {
            background: #1e293b;
            color: #f8fafc;
            border-right-color: #334155;
            border-bottom-color: #475569;
        }

        .pro-bordered-table td {
            padding: 10px 14px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .dark .pro-bordered-table td {
            border-right-color: #334155;
            border-bottom-color: #334155;
        }

        .pro-bordered-table th:last-child,
        .pro-bordered-table td:last-child {
            border-right: none;
        }

        .pro-bordered-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .dark .pro-bordered-table tbody tr:nth-child(even) {
            background: #182234;
        }

        .pro-bordered-table tbody tr:hover {
            background: #e2e8f0 !important;
        }
        .dark .pro-bordered-table tbody tr:hover {
            background: #334155 !important;
        }

        /* Sticky First Column for Matrix */
        .pro-bordered-table .sticky-col {
            position: sticky;
            left: 0;
            z-index: 10;
            background: #ffffff;
            border-right: 2px solid #94a3b8 !important;
            min-width: 240px;
        }
        .dark .pro-bordered-table .sticky-col {
            background: #0f172a;
            border-right-color: #475569 !important;
        }
        .pro-bordered-table tbody tr:nth-child(even) .sticky-col {
            background: #f8fafc;
        }
        .dark .pro-bordered-table tbody tr:nth-child(even) .sticky-col {
            background: #182234;
        }
        .pro-bordered-table thead .sticky-col {
            background: #f1f5f9;
            z-index: 20;
        }
        .dark .pro-bordered-table thead .sticky-col {
            background: #1e293b;
        }
        .pro-bordered-table tfoot .sticky-col {
            background: #e2e8f0;
            z-index: 20;
        }
        .dark .pro-bordered-table tfoot .sticky-col {
            background: #334155;
        }

        /* Matrix Total Column & Footer */
        .matrix-total-col {
            background: #f1f5f9;
            border-left: 2px solid #94a3b8 !important;
            font-weight: 700;
            text-align: center;
        }
        .dark .matrix-total-col {
            background: #1e293b;
            border-left-color: #475569 !important;
        }

        .pro-bordered-table tfoot td {
            background: #e2e8f0;
            font-weight: 800;
            border-top: 2px solid #64748b;
            border-bottom: none;
            color: #0f172a;
        }
        .dark .pro-bordered-table tfoot td {
            background: #334155;
            border-top-color: #64748b;
            color: #f8fafc;
        }

        /* Interactive Matrix Badge */
        .matrix-badge-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .matrix-badge-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        .matrix-badge-low {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        .matrix-badge-high {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .matrix-badge-active {
            background: #4f46e5 !important;
            color: #ffffff !important;
            border-color: #3730a3 !important;
            box-shadow: 0 0 0 2px #818cf8;
        }

        /* Date Chips in Detail Table */
        .chip-date {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid #fca5a5;
            background: #fff1f2;
            color: #be123c;
            margin: 2px;
        }
        .chip-date-today {
            background: #ef4444;
            color: #ffffff;
            border-color: #b91c1c;
            font-weight: 700;
        }
        .dark .chip-date {
            background: #4c0519;
            color: #fecdd3;
            border-color: #9f1239;
        }
        .dark .chip-date-today {
            background: #dc2626;
            color: #ffffff;
            border-color: #ef4444;
        }

        /* Filter Pills */
        .filter-btn-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-btn-pill:hover {
            background: #e2e8f0;
        }
        .filter-btn-pill.active {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4338ca;
            box-shadow: 0 1px 2px rgba(79, 70, 229, 0.3);
        }
        .dark .filter-btn-pill {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }
        .dark .filter-btn-pill.active {
            background: #6366f1;
            color: #ffffff;
            border-color: #4f46e5;
        }
    </style>

    @php
        $matrix = $this->getMatrixData();
        $detailPagination = $this->getFilteredDetailData();
        $details = $detailPagination['items'];
        $summary = $matrix['summary_info'];
        $allPrincipals = \App\Models\Principal::query()
            ->when(auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction(), function($q) {
                $q->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $allBranches = \App\Models\Branch::query()
            ->when(auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction(), function($q) {
                $q->whereIn('id', auth()->user()->getAccessibleBranchIds());
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Hitung metrik ringkasan
        $todayUncheckedCount = collect($summary['employees'])->where('is_today_unchecked', true)->count();
        $ge3DaysCount = collect($summary['employees'])->where('missed_count_7days', '>=', 3)->count();
        $neverAttendedCount = collect($summary['employees'])->where('days_since_last', -1)->count();
    @endphp

    <div class="monitoring-wrapper">
        {{-- TOP KPI METRIC CARDS --}}
        <div class="kpi-grid">
            {{-- Card 1: Total Belum Check-in 7 Hari --}}
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Belum Check-In (7 Hari)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ number_format($summary['total_unchecked_employees']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Dari total {{ number_format($summary['total_active_employees']) }} karyawan aktif</div>
                </div>
                <div class="kpi-icon-box" style="background: #fee2e2; color: #dc2626;">
                    <x-filament::icon icon="heroicon-o-user-minus" style="width: 26px; height: 26px;" />
                </div>
            </div>

            {{-- Card 2: Belum Check-in Hari Ini --}}
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px;">Belum Check-In Hari Ini</div>
                    <div style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ number_format($todayUncheckedCount) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $summary['today_formatted'] }}</div>
                </div>
                <div class="kpi-icon-box" style="background: #fef3c7; color: #d97706;">
                    <x-filament::icon icon="heroicon-o-clock" style="width: 26px; height: 26px;" />
                </div>
            </div>

            {{-- Card 3: >= 3 Hari Tidak Hadir --}}
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px;">&ge; 3 Hari Tidak Hadir</div>
                    <div style="font-size: 26px; font-weight: 800; color: #e11d48; margin-top: 4px;">{{ number_format($ge3DaysCount) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Perlu perhatian / follow-up</div>
                </div>
                <div class="kpi-icon-box" style="background: #ffe4e6; color: #e11d48;">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width: 26px; height: 26px;" />
                </div>
            </div>

            {{-- Card 4: Belum Pernah Hadir --}}
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Belum Pernah Hadir</div>
                    <div style="font-size: 26px; font-weight: 800; color: #334155; margin-top: 4px;">{{ number_format($neverAttendedCount) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Tidak ada riwayat presensi</div>
                </div>
                <div class="kpi-icon-box" style="background: #f1f5f9; color: #475569;">
                    <x-filament::icon icon="heroicon-o-no-symbol" style="width: 26px; height: 26px;" />
                </div>
            </div>
        </div>

        {{-- FILTER PANEL --}}
        <div class="pro-card">
            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: space-between;">
                {{-- Dropdowns & Search --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; flex: 1;">
                    {{-- Filter Prinsiple --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Filter Prinsiple</label>
                        <select wire:model.live="selectedPrincipalId" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a;">
                            <option value="">-- Semua Prinsiple --</option>
                            @foreach ($allPrincipals as $p)
                                <option value="{{ (string)$p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Area / Cabang --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Filter Area / Cabang</label>
                        <select wire:model.live="selectedBranchId" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a;">
                            <option value="">-- Semua Area / Cabang --</option>
                            @foreach ($allBranches as $b)
                                <option value="{{ (string)$b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pencarian Cepat --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Cari Karyawan / NIK / Jabatan</label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="Ketik nama karyawan atau NIK..."
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a;"
                        />
                    </div>
                </div>

                {{-- Reset Button --}}
                <div>
                    <button
                        type="button"
                        wire:click="resetAllFilters"
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer;"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-path" style="width: 16px; height: 16px;" />
                        Reset Filter
                    </button>
                </div>
            </div>

            {{-- Filter Status Pills --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #e2e8f0;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; margin-right: 4px;">Status Cepat:</span>

                <button
                    type="button"
                    wire:click="$set('quickFilter', 'all')"
                    class="filter-btn-pill {{ $quickFilter === 'all' ? 'active' : '' }}"
                >
                    Semua (7 Hari)
                </button>

                <button
                    type="button"
                    wire:click="$set('quickFilter', 'today')"
                    class="filter-btn-pill {{ $quickFilter === 'today' ? 'active' : '' }}"
                >
                    Belum Check-In Hari Ini
                </button>

                <button
                    type="button"
                    wire:click="$set('quickFilter', 'ge3')"
                    class="filter-btn-pill {{ $quickFilter === 'ge3' ? 'active' : '' }}"
                >
                    &ge; 3 Hari Tidak Hadir
                </button>

                <button
                    type="button"
                    wire:click="$set('quickFilter', 'never')"
                    class="filter-btn-pill {{ $quickFilter === 'never' ? 'active' : '' }}"
                >
                    Belum Pernah Hadir
                </button>
            </div>
        </div>

        {{-- SECTION 1: MATRIKS PRINSIPLE VS AREA (SESUAI GAMBAR 1) --}}
        <div class="pro-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-table-cells" style="width: 22px; height: 22px; color: #4f46e5;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Matriks Tim Belum Check-In (Prinsiple vs Area)</span>
                </div>
                <span style="font-size: 12px; color: #64748b;">
                    💡 <em>Klik pada angka cell untuk memfilter langsung detail karyawan di bawah</em>
                </span>
            </div>

            <div class="pro-table-container">
                <table class="pro-bordered-table">
                    {{-- Header Columns (Area) --}}
                    <thead>
                        <tr>
                            <th class="sticky-col" style="text-align: left;">
                                Prinsiple
                            </th>
                            @foreach ($matrix['columns'] as $colId => $colName)
                                <th style="text-align: center; min-width: 110px;">
                                    {{ $colName }}
                                </th>
                            @endforeach
                            <th class="matrix-total-col" style="min-width: 100px; color: #4f46e5;">
                                Total
                            </th>
                        </tr>
                    </thead>

                    {{-- Rows (Prinsiple) --}}
                    <tbody>
                        @forelse ($matrix['rows'] as $index => $row)
                            @php
                                $rowPId = (string)($row['principal_id'] ?? '0');
                                $isRowSelected = ($selectedCellPrincipalId === $rowPId && $selectedCellBranchId === null);
                            @endphp
                            <tr>
                                {{-- Principal Name Column (Sticky) --}}
                                <td class="sticky-col">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <span style="font-weight: 700; color: #0f172a;">{{ $row['principal_name'] }}</span>
                                        <button
                                            type="button"
                                            wire:click="selectMatrixCell('{{ $rowPId }}', '', '{{ addslashes($row['principal_name']) }}', '')"
                                            style="font-size: 11px; font-weight: 600; color: #4f46e5; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 2px 6px; cursor: pointer; margin-left: 8px;"
                                            title="Filter seluruh area untuk {{ $row['principal_name'] }}"
                                        >
                                            Filter
                                        </button>
                                    </div>
                                </td>

                                {{-- Branch Values --}}
                                @foreach ($matrix['columns'] as $colId => $colName)
                                    @php
                                        $colBId = (string)$colId;
                                        $val = $row['branches'][$colId] ?? 0;
                                        $isCellActive = ($selectedCellPrincipalId === $rowPId && $selectedCellBranchId === $colBId);
                                    @endphp
                                    <td style="text-align: center; @if($isCellActive) background: #e0e7ff; @endif">
                                        @if ($val > 0)
                                            <button
                                                type="button"
                                                wire:click="selectMatrixCell('{{ $rowPId }}', '{{ $colBId }}', '{{ addslashes($row['principal_name']) }}', '{{ addslashes($colName) }}')"
                                                class="matrix-badge-btn {{ $isCellActive ? 'matrix-badge-active' : ($val >= 3 ? 'matrix-badge-high' : 'matrix-badge-low') }}"
                                                title="Klik untuk melihat {{ $val }} karyawan di {{ $row['principal_name'] }} - {{ $colName }}"
                                            >
                                                {{ $val }}
                                            </button>
                                        @else
                                            <span style="color: #94a3b8; font-size: 12px;">-</span>
                                        @endif
                                    </td>
                                @endforeach

                                {{-- Row Total --}}
                                <td class="matrix-total-col">
                                    @if ($row['total_row'] > 0)
                                        <button
                                            type="button"
                                            wire:click="selectMatrixCell('{{ $rowPId }}', '', '{{ addslashes($row['principal_name']) }}', '')"
                                            style="font-size: 13px; font-weight: 800; color: #4f46e5; text-decoration: underline; background: none; border: none; cursor: pointer;"
                                        >
                                            {{ $row['total_row'] }}
                                        </button>
                                    @else
                                        <span style="color: #94a3b8;">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($matrix['columns']) + 2 }}" style="text-align: center; padding: 24px; color: #64748b;">
                                    Tidak ada data matriks yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Column Totals (Footer) --}}
                    @if (count($matrix['rows']) > 0)
                        <tfoot>
                            <tr>
                                <td class="sticky-col" style="text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                                    Total Per Area
                                </td>
                                @foreach ($matrix['columns'] as $colId => $colName)
                                    @php
                                        $colBId = (string)$colId;
                                        $colTotal = $matrix['column_totals'][$colId] ?? 0;
                                        $isColActive = ($selectedCellBranchId === $colBId && $selectedCellPrincipalId === null);
                                    @endphp
                                    <td style="text-align: center; @if($isColActive) background: #c7d2fe; @endif">
                                        @if ($colTotal > 0)
                                            <button
                                                type="button"
                                                wire:click="selectMatrixCell('', '{{ $colBId }}', '', '{{ addslashes($colName) }}')"
                                                style="font-weight: 800; color: #0f172a; text-decoration: underline; background: none; border: none; cursor: pointer;"
                                                title="Filter seluruh prinsiple di area {{ $colName }}"
                                            >
                                                {{ $colTotal }}
                                            </button>
                                        @else
                                            <span style="color: #94a3b8;">0</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="matrix-total-col" style="background: #4f46e5; color: #ffffff; font-size: 15px; font-weight: 900;">
                                    {{ $matrix['grand_total'] }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ACTIVE FILTER BANNER (Jika Cell Matriks Diklik) --}}
        @if (!empty($selectedCellPrincipalId) || !empty($selectedCellBranchId))
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 10px; background: #eef2ff; border: 1px solid #818cf8; color: #312e81;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600;">
                    <x-filament::icon icon="heroicon-o-funnel" style="width: 18px; height: 18px; color: #4f46e5;" />
                    <span>
                        Filter Matriks Aktif:
                        <strong style="color: #1e1b4b;">{{ $selectedCellPrincipalName ?: 'Semua Prinsiple' }}</strong>
                        &bull;
                        <strong style="color: #1e1b4b;">{{ $selectedCellBranchName ?: 'Semua Area' }}</strong>
                        <span style="opacity: 0.8; margin-left: 4px;">({{ number_format($detailPagination['total_count']) }} Karyawan Ditemukan)</span>
                    </span>
                </div>
                <button
                    type="button"
                    wire:click="resetCellFilter"
                    style="font-size: 12px; font-weight: 700; color: #dc2626; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 6px; padding: 4px 10px; cursor: pointer;"
                >
                    ✕ Hapus Filter Matriks
                </button>
            </div>
        @endif

        {{-- SECTION 2: TABEL DETAIL RINCIAN KARYAWAN (SESUAI GAMBAR 2) --}}
        <div class="pro-card" id="detail-section">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-list-bullet" style="width: 22px; height: 22px; color: #4f46e5;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Rincian Data Karyawan Belum Check-In</span>
                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 700; background: #e0e7ff; color: #3730a3;">
                        {{ number_format($detailPagination['total_count']) }} Karyawan
                    </span>
                </div>
                <span style="font-size: 12px; color: #64748b;">
                    Rentang: <strong>{{ $summary['seven_days_range'] }}</strong>
                </span>
            </div>

            <div class="pro-table-container">
                <table class="pro-bordered-table">
                    {{-- Table Header Sesuai Gambar 2 --}}
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="min-width: 240px; text-align: left;">Nama Karyawan</th>
                            <th style="min-width: 140px; text-align: left;">Jabatan</th>
                            <th style="min-width: 160px; text-align: left;">Prinsiple</th>
                            <th style="min-width: 130px; text-align: left;">Area</th>
                            <th style="min-width: 280px; text-align: left;">Tgl Tidak Check-in (7 Hari Terakhir)</th>
                        </tr>
                    </thead>

                    {{-- Table Body --}}
                    <tbody>
                        @forelse ($details as $index => $emp)
                            @php
                                $rowNo = $detailPagination['from'] + $index;
                                $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($emp['full_name']) . '&background=4f46e5&color=fff&size=64';
                                if (!empty($emp['photo'])) {
                                    try {
                                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($emp['photo'])) {
                                            $photoUrl = asset('storage/' . $emp['photo']);
                                        }
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            <tr>
                                {{-- Nomor Urut --}}
                                <td style="text-align: center; font-weight: 600; color: #64748b;">
                                    {{ $rowNo }}
                                </td>

                                {{-- Nama Karyawan & NIK --}}
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img
                                            src="{{ $photoUrl }}"
                                            alt="{{ $emp['full_name'] }}"
                                            style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;"
                                            loading="lazy"
                                        />
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a; font-size: 13px;">{{ $emp['full_name'] }}</div>
                                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">NIK: {{ $emp['employee_no'] }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Jabatan --}}
                                <td style="color: #334155; font-weight: 500;">
                                    {{ $emp['position'] }}
                                </td>

                                {{-- Prinsiple --}}
                                <td style="color: #0f172a; font-weight: 600;">
                                    {{ $emp['principal_name'] }}
                                </td>

                                {{-- Area --}}
                                <td>
                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #f1f5f9; border: 1px solid #cbd5e1; color: #1e293b;">
                                        {{ $emp['branch_name'] }}
                                    </span>
                                </td>

                                {{-- Tgl Tidak Check-In (7 Hari Terakhir) --}}
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 4px;">
                                        @foreach ($emp['missed_dates'] as $mDate)
                                            @if ($mDate['is_today'])
                                                <span class="chip-date chip-date-today" title="{{ $mDate['full_date'] }} (Hari Ini)">
                                                    ● {{ $mDate['formatted_date'] }} (Hari Ini)
                                                </span>
                                            @else
                                                <span class="chip-date" title="{{ $mDate['full_date'] }} ({{ $mDate['day_name'] }})">
                                                    {{ $mDate['formatted_date'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                        Total: <strong style="color: #e11d48;">{{ $emp['missed_count_7days'] }} Hari</strong> &bull; Terakhir Hadir: <strong>{{ $emp['last_attendance_date'] }}</strong>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 36px; color: #64748b;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                        <x-filament::icon icon="heroicon-o-check-circle" style="width: 36px; height: 36px; color: #16a34a;" />
                                        <span style="font-weight: 700; color: #0f172a; font-size: 14px;">Tidak ada karyawan yang belum check-in pada kriteria ini.</span>
                                        <span style="font-size: 12px; color: #64748b;">Semua karyawan hadir tepat waktu atau filter tidak menghasilkan data.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION TOOLBAR --}}
            @if ($detailPagination['total_count'] > 0)
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
                    {{-- Info Showing --}}
                    <div>
                        Menampilkan <strong style="color: #0f172a;">{{ $detailPagination['from'] }}</strong> - <strong style="color: #0f172a;">{{ $detailPagination['to'] }}</strong> dari <strong style="color: #0f172a;">{{ number_format($detailPagination['total_count']) }}</strong> data karyawan
                    </div>

                    {{-- Controls --}}
                    <div style="display: flex; align-items: center; gap: 16px;">
                        {{-- Per Page Select --}}
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span>Per halaman:</span>
                            <select wire:model.live="perPage" style="padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        {{-- Previous / Next Buttons --}}
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button
                                type="button"
                                wire:click="previousPage"
                                @if ($detailPagination['page'] <= 1) disabled @endif
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $detailPagination['page'] <= 1 ? '0.4' : '1' }};"
                            >
                                &laquo; Sebelumnya
                            </button>

                            <span style="font-weight: 700; padding: 0 4px;">
                                Halaman {{ $detailPagination['page'] }} dari {{ $detailPagination['total_pages'] }}
                            </span>

                            <button
                                type="button"
                                wire:click="nextPage({{ $detailPagination['total_pages'] }})"
                                @if ($detailPagination['page'] >= $detailPagination['total_pages']) disabled @endif
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $detailPagination['page'] >= $detailPagination['total_pages'] ? '0.4' : '1' }};"
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
