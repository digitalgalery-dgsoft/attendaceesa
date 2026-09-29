@extends('portal.layout')

@section('title', 'Monitoring Tim Belum Check-in - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Monitoring Tim Belum Check-in')
@section('breadcrumb_active', 'Monitoring Belum Check-in')

@push('styles')
<style>
    /* Custom Styling for Full Width & Professional Crisp Borders matching Admin */
    .monitoring-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* KPI Grid matching Screenshot 2 */
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

    .kpi-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    /* Professional Card / Section Box */
    .pro-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* Professional Bordered Table Styles */
    .pro-table-container {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #94a3b8;
        border-radius: 8px;
        background: #ffffff;
    }

    .pro-bordered-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        color: #1e293b;
        min-width: 100%;
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

    .pro-bordered-table td {
        padding: 10px 14px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .pro-bordered-table th:last-child,
    .pro-bordered-table td:last-child {
        border-right: none;
    }

    .pro-bordered-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    .pro-bordered-table tbody tr:hover {
        background: #e2e8f0 !important;
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
    .pro-bordered-table tbody tr:nth-child(even) .sticky-col {
        background: #f8fafc;
    }
    .pro-bordered-table thead .sticky-col {
        background: #f1f5f9;
        z-index: 20;
    }
    .pro-bordered-table tfoot .sticky-col {
        background: #e2e8f0;
        z-index: 20;
    }

    /* Matrix Total Column & Footer */
    .matrix-total-col {
        background: #f1f5f9;
        border-left: 2px solid #94a3b8 !important;
        font-weight: 700;
        text-align: center;
    }

    .pro-bordered-table tfoot td {
        background: #e2e8f0;
        font-weight: 800;
        border-top: 2px solid #64748b;
        border-bottom: none;
        color: #0f172a;
    }

    /* Interactive Matrix Badge (Sesuai Screenshot 2) */
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

    /* Date Chips in Detail Table (Sesuai Screenshot 3) */
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

    /* Filter Status Pills */
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
        text-decoration: none;
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
</style>
@endpush

@section('content')
<div class="monitoring-wrapper">
    {{-- Header & Top Action Buttons (Sesuai Screenshot 2) --}}
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
        <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.5px;">
            Monitoring Tim Belum Check-in
        </h1>

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
            <a href="{{ route('portal.unchecked.export', array_merge(request()->query(), ['p' => $tenantPrincipal->id])) }}" class="btn-action-primary" style="background: #10b981; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>
            <a href="{{ route('portal.unchecked', ['p' => $tenantPrincipal->id]) }}" style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <i class="fa-solid fa-arrows-rotate"></i>
                Segarkan Data
            </a>
        </div>
    </div>

    {{-- TOP 4 KPI METRIC CARDS (Sesuai Screenshot 2) --}}
    <div class="kpi-grid">
        {{-- Card 1: Total Belum Check-in 7 Hari --}}
        <div class="kpi-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Belum Check-In (7 Hari)</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ number_format($summary['total_unchecked_7days']) }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Dari total {{ number_format($summary['total_active']) }} karyawan aktif</div>
            </div>
            <div class="kpi-icon-box" style="background: #fee2e2; color: #dc2626;">
                <i class="fa-solid fa-user-minus"></i>
            </div>
        </div>

        {{-- Card 2: Belum Check-in Hari Ini --}}
        <div class="kpi-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px;">Belum Check-In Hari Ini</div>
                <div style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ number_format($summary['today_unchecked_count']) }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $summary['today_formatted'] }}</div>
            </div>
            <div class="kpi-icon-box" style="background: #fef3c7; color: #d97706;">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>

        {{-- Card 3: >= 3 Hari Tidak Hadir --}}
        <div class="kpi-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px;">&ge; 3 Hari Tidak Hadir</div>
                <div style="font-size: 26px; font-weight: 800; color: #e11d48; margin-top: 4px;">{{ number_format($summary['ge3_days_count']) }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Perlu perhatian / follow up</div>
            </div>
            <div class="kpi-icon-box" style="background: #ffe4e6; color: #e11d48;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 4: Belum Pernah Hadir --}}
        <div class="kpi-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Belum Pernah Hadir</div>
                <div style="font-size: 26px; font-weight: 800; color: #334155; margin-top: 4px;">{{ number_format($summary['never_attended_count']) }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Tidak ada riwayat presensi</div>
            </div>
            <div class="kpi-icon-box" style="background: #f1f5f9; color: #475569;">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>
    </div>

    {{-- FILTER PANEL (Sesuai Screenshot 2) --}}
    <div class="pro-card">
        <form action="{{ route('portal.unchecked') }}" method="GET">
            <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
            <input type="hidden" name="filter" value="{{ $quickFilter }}">
            @if($selectedCellPrincipalId) <input type="hidden" name="cell_p" value="{{ $selectedCellPrincipalId }}"> @endif
            @if($selectedCellBranchId) <input type="hidden" name="cell_b" value="{{ $selectedCellBranchId }}"> @endif

            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: space-between;">
                {{-- Dropdowns & Search --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; flex: 1;">
                    {{-- Filter Prinsiple --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Filter Prinsiple</label>
                        <select name="principal_id" class="searchable-filter-select" onchange="this.form.submit()" style="width: 100%;">
                            <option value="">-- Semua Prinsiple --</option>
                            @foreach ($allPrincipals as $p)
                                <option value="{{ $p->id }}" {{ (string)$filterPrincipalId === (string)$p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Area / Cabang --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Filter Area / Cabang</label>
                        <select name="branch_id" class="searchable-filter-select" onchange="this.form.submit()" style="width: 100%;">
                            <option value="">-- Semua Area / Cabang --</option>
                            @foreach ($allBranches as $b)
                                <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pencarian Cepat --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Cari Karyawan / NIK / Jabatan</label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Ketik nama karyawan atau NIK..."
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a; outline: none; min-height: 38px; box-sizing: border-box;"
                        />
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="submit" class="btn-action-primary" style="padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; background: #4f46e5; color: #ffffff; border: none; cursor: pointer;">
                        <i class="fa-solid fa-magnifying-glass"></i> Cari
                    </button>
                    <a
                        href="{{ route('portal.unchecked', ['p' => $tenantPrincipal->id]) }}"
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; text-decoration: none;"
                    >
                        <i class="fa-solid fa-arrows-rotate"></i>
                        Reset Filter
                    </a>
                </div>
            </div>

            {{-- Filter Status Pills (Sesuai Screenshot 2) --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #e2e8f0;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; margin-right: 4px;">Status Cepat:</span>

                <a
                    href="{{ route('portal.unchecked', array_merge(request()->query(), ['filter' => 'all'])) }}"
                    class="filter-btn-pill {{ $quickFilter === 'all' ? 'active' : '' }}"
                >
                    Semua (7 Hari)
                </a>

                <a
                    href="{{ route('portal.unchecked', array_merge(request()->query(), ['filter' => 'today'])) }}"
                    class="filter-btn-pill {{ $quickFilter === 'today' ? 'active' : '' }}"
                >
                    Belum Check-In Hari Ini
                </a>

                <a
                    href="{{ route('portal.unchecked', array_merge(request()->query(), ['filter' => 'ge3'])) }}"
                    class="filter-btn-pill {{ $quickFilter === 'ge3' ? 'active' : '' }}"
                >
                    &ge; 3 Hari Tidak Hadir
                </a>

                <a
                    href="{{ route('portal.unchecked', array_merge(request()->query(), ['filter' => 'never'])) }}"
                    class="filter-btn-pill {{ $quickFilter === 'never' ? 'active' : '' }}"
                >
                    Belum Pernah Hadir
                </a>
            </div>
        </form>
    </div>

    {{-- SECTION 1: MATRIKS TIM BELUM CHECK-IN (PRINSIPLE VS AREA) (Persis Screenshot 2) --}}
    <div class="pro-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-table-cells" style="color: #4f46e5; font-size: 18px;"></i>
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
                    @forelse ($matrix['rows'] as $row)
                        @php
                            $rowPId = (string)($row['principal_id'] ?? '0');
                        @endphp
                        <tr>
                            {{-- Principal Name Column (Sticky) --}}
                            <td class="sticky-col">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-weight: 700; color: #0f172a;">{{ $row['principal_name'] }}</span>
                                    <a
                                        href="{{ route('portal.unchecked', array_merge(request()->query(), ['cell_p' => $rowPId, 'cell_b' => null])) }}#detail-section"
                                        style="font-size: 11px; font-weight: 600; color: #4f46e5; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 2px 6px; cursor: pointer; margin-left: 8px; text-decoration: none;"
                                        title="Filter seluruh area untuk {{ $row['principal_name'] }}"
                                    >
                                        Filter
                                    </a>
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
                                        <a
                                            href="{{ route('portal.unchecked', array_merge(request()->query(), ['cell_p' => $rowPId, 'cell_b' => $colBId])) }}#detail-section"
                                            class="matrix-badge-btn {{ $isCellActive ? 'matrix-badge-active' : ($val >= 3 ? 'matrix-badge-high' : 'matrix-badge-low') }}"
                                            title="Klik untuk melihat {{ $val }} karyawan di {{ $row['principal_name'] }} - {{ $colName }}"
                                        >
                                            {{ $val }}
                                        </a>
                                    @else
                                        <span style="color: #94a3b8; font-size: 12px;">-</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Row Total --}}
                            <td class="matrix-total-col">
                                @if ($row['total_row'] > 0)
                                    <a
                                        href="{{ route('portal.unchecked', array_merge(request()->query(), ['cell_p' => $rowPId, 'cell_b' => null])) }}#detail-section"
                                        style="font-size: 13px; font-weight: 800; color: #4f46e5; text-decoration: underline;"
                                    >
                                        {{ $row['total_row'] }}
                                    </a>
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
                                    $isColActive = ($selectedCellBranchId === $colBId && empty($selectedCellPrincipalId));
                                @endphp
                                <td style="text-align: center; @if($isColActive) background: #c7d2fe; @endif">
                                    @if ($colTotal > 0)
                                        <a
                                            href="{{ route('portal.unchecked', array_merge(request()->query(), ['cell_p' => null, 'cell_b' => $colBId])) }}#detail-section"
                                            style="font-weight: 800; color: #0f172a; text-decoration: underline;"
                                            title="Filter seluruh prinsiple di area {{ $colName }}"
                                        >
                                            {{ $colTotal }}
                                        </a>
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
                <i class="fa-solid fa-filter" style="color: #4f46e5;"></i>
                <span>
                    Filter Matriks Aktif:
                    <strong style="color: #1e1b4b;">{{ $selectedCellPrincipalName ?: 'Semua Prinsiple' }}</strong>
                    &bull;
                    <strong style="color: #1e1b4b;">{{ $selectedCellBranchName ?: 'Semua Area' }}</strong>
                    <span style="opacity: 0.8; margin-left: 4px;">({{ number_format($detailPagination['total_count']) }} Karyawan Ditemukan)</span>
                </span>
            </div>
            <a
                href="{{ route('portal.unchecked', array_merge(request()->query(), ['cell_p' => null, 'cell_b' => null])) }}"
                style="font-size: 12px; font-weight: 700; color: #dc2626; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 6px; padding: 4px 10px; text-decoration: none;"
            >
                ✕ Hapus Filter Matriks
            </a>
        </div>
    @endif

    {{-- SECTION 2: TABEL DETAIL RINCIAN KARYAWAN (Persis Screenshot 3) --}}
    <div class="pro-card" id="detail-section">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-list-ul" style="color: #4f46e5; font-size: 18px;"></i>
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
                {{-- Table Header Sesuai Screenshot 3 --}}
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
                    @forelse ($detailPagination['items'] as $index => $emp)
                        @php
                            $rowNo = $detailPagination['from'] + $index;
                            $empName = strtoupper($emp['full_name']);
                            $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($empName) . '&background=4f46e5&color=fff&size=64';
                            if (!empty($emp['photo'])) {
                                try {
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($emp['photo'])) {
                                        $photoUrl = asset('storage/' . $emp['photo']);
                                    } elseif (\Illuminate\Support\Facades\Storage::exists($emp['photo'])) {
                                        $photoUrl = \Illuminate\Support\Facades\Storage::url($emp['photo']);
                                    }
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr>
                            {{-- Nomor Urut --}}
                            <td style="text-align: center; font-weight: 600; color: #64748b;">
                                {{ $rowNo }}
                            </td>

                            {{-- Nama Karyawan & NIK (UPPERCASE) --}}
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img
                                        src="{{ $photoUrl }}"
                                        alt="{{ $empName }}"
                                        style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;"
                                        loading="lazy"
                                    />
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 13px; text-transform: uppercase;">
                                            {{ $empName }}
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                                            NIK: {{ $emp['employee_no'] }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Jabatan --}}
                            <td style="color: #334155; font-weight: 500;">
                                {{ $emp['position_name'] }}
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

                            {{-- Tgl Tidak Check-In (7 Hari Terakhir) (Persis Screenshot 3) --}}
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
                                    <i class="fa-solid fa-circle-check" style="font-size: 36px; color: #16a34a;"></i>
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
                <div>
                    Menampilkan <strong style="color: #0f172a;">{{ $detailPagination['from'] }}</strong> - <strong style="color: #0f172a;">{{ $detailPagination['to'] }}</strong> dari <strong style="color: #0f172a;">{{ number_format($detailPagination['total_count']) }}</strong> data karyawan
                </div>

                <div style="display: flex; align-items: center; gap: 16px;">
                    <form action="{{ route('portal.unchecked') }}" method="GET" style="display: flex; align-items: center; gap: 6px;">
                        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
                        <input type="hidden" name="filter" value="{{ $quickFilter }}">
                        @if($filterPrincipalId) <input type="hidden" name="principal_id" value="{{ $filterPrincipalId }}"> @endif
                        @if($branchId) <input type="hidden" name="branch_id" value="{{ $branchId }}"> @endif
                        @if($search) <input type="hidden" name="q" value="{{ $search }}"> @endif
                        @if($selectedCellPrincipalId) <input type="hidden" name="cell_p" value="{{ $selectedCellPrincipalId }}"> @endif
                        @if($selectedCellBranchId) <input type="hidden" name="cell_b" value="{{ $selectedCellBranchId }}"> @endif

                        <span>Per halaman:</span>
                        <select name="per_page" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        @if ($detailPagination['page'] > 1)
                            <a
                                href="{{ route('portal.unchecked', array_merge(request()->query(), ['page' => $detailPagination['page'] - 1])) }}#detail-section"
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; text-decoration: none;"
                            >
                                &laquo; Sebelumnya
                            </a>
                        @else
                            <span style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; color: #94a3b8; opacity: 0.5;">
                                &laquo; Sebelumnya
                            </span>
                        @endif

                        <span style="font-weight: 700; padding: 0 4px;">
                            Halaman {{ $detailPagination['page'] }} dari {{ $detailPagination['total_pages'] }}
                        </span>

                        @if ($detailPagination['page'] < $detailPagination['total_pages'])
                            <a
                                href="{{ route('portal.unchecked', array_merge(request()->query(), ['page' => $detailPagination['page'] + 1])) }}#detail-section"
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; text-decoration: none;"
                            >
                                Selanjutnya &raquo;
                            </a>
                        @else
                            <span style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; color: #94a3b8; opacity: 0.5;">
                                Selanjutnya &raquo;
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
