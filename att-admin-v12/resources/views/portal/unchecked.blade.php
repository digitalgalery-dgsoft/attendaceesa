@extends('portal.layout')

@section('title', 'Monitoring Belum Check-in - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Monitoring Promotor Belum Check-in')
@section('breadcrumb_active', 'Monitoring Kehadiran')

@push('styles')
<style>
    /* Admin-Style Container & Cards */
    .page-header-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .page-icon-large {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .page-title-text {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        margin-bottom: 0.25rem;
        letter-spacing: -0.3px;
    }

    .page-meta-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 500;
    }

    .btn-action-primary {
        background: #dc2626;
        color: #ffffff;
        padding: 0.6rem 1.25rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-action-primary:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
        color: #ffffff;
    }

    /* KPI Grid matching Admin Dashboard */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 1.25rem;
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
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }

    .kpi-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-val {
        font-size: 26px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 4px;
    }

    .kpi-sub {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 3px;
        font-weight: 500;
    }

    /* Admin-Style Filter Card */
    .pro-filter-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-input {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 12.5px;
        color: #0f172a;
        background: #ffffff;
        width: 100%;
        outline: none;
        transition: border-color 0.15s ease;
    }

    .filter-input:focus {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    .btn-filter-submit {
        background: #0f172a;
        color: #ffffff;
        border: 1px solid #0f172a;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .btn-filter-submit:hover {
        background: #1e293b;
    }

    .btn-filter-reset {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .btn-filter-reset:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* Filter Pills matching Admin Dashboard */
    .filter-pills-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #e2e8f0;
    }

    .filter-btn-pill {
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 9999px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-btn-pill:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }
    .filter-btn-pill.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.15);
    }
    .filter-btn-pill.active .pill-count {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }

    .pill-count {
        background: #f1f5f9;
        color: #475569;
        font-size: 10.5px;
        padding: 1px 6px;
        border-radius: 9999px;
        font-weight: 800;
    }

    /* Admin-Style Pro Bordered Table */
    .pro-table-container {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .pro-bordered-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        color: #1e293b;
        min-width: 1000px;
    }

    .pro-bordered-table th {
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        border-right: 1px solid #cbd5e1;
        border-bottom: 2px solid #94a3b8;
        white-space: nowrap;
    }

    .pro-bordered-table th:last-child {
        border-right: none;
    }

    .pro-bordered-table td {
        padding: 11px 14px;
        border-right: 1px solid #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .pro-bordered-table td:last-child {
        border-right: none;
    }

    .pro-bordered-table tbody tr:nth-child(even) {
        background: #fafbfc;
    }

    .pro-bordered-table tbody tr:hover {
        background: #f1f5f9 !important;
    }

    /* Employee Cell */
    .emp-cell-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .emp-avatar-box {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 13px;
        flex-shrink: 0;
        border: 1px solid #fecaca;
    }

    .emp-name-text {
        font-weight: 800;
        font-size: 13px;
        color: #0f172a;
        text-transform: uppercase !important;
        letter-spacing: 0.3px;
        line-height: 1.25;
    }

    .emp-sub-text {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .nik-badge {
        font-family: monospace;
        font-weight: 700;
        color: #334155;
        background: #f1f5f9;
        padding: 1px 5px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }

    /* Badges */
    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .badge-absent { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .badge-late { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-gray { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
</style>
@endpush

@section('content')
<!-- Header Card -->
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large">
            <i class="fa-solid fa-user-slash"></i>
        </div>
        <div>
            <h2 class="page-title-text">Monitoring Tim Belum Check-in</h2>
            <div class="page-meta-row">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Tanggal: <strong>{{ $summary['today_formatted'] }}</strong></span>
                <span>&bull;</span>
                <span>Belum Hadir Hari Ini: <strong style="color: #dc2626;">{{ number_format($summary['today_unchecked_count']) }} Orang</strong></span>
            </div>
        </div>
    </div>

    <div>
        <a href="{{ route('portal.unchecked.export', array_merge(request()->query(), ['p' => $tenantPrincipal->id])) }}" class="btn-action-primary">
            <i class="fa-solid fa-file-excel"></i>
            Export Data Belum Check-in (CSV)
        </a>
    </div>
</div>

<!-- Top KPI Cards Grid (Admin-Style) -->
<div class="kpi-grid">
    {{-- Card 1: Total Belum Check-in 7 Hari --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #64748b;">Belum Check-In (7 Hari)</div>
            <div class="kpi-val" style="color: #0f172a;">{{ number_format($summary['total_unchecked_7days']) }}</div>
            <div class="kpi-sub">Dari total {{ number_format($summary['total_active']) }} promotor aktif</div>
        </div>
        <div class="kpi-icon-box" style="background: #fee2e2; color: #dc2626;">
            <i class="fa-solid fa-user-minus"></i>
        </div>
    </div>

    {{-- Card 2: Belum Check-in Hari Ini --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #d97706;">Belum Check-In Hari Ini</div>
            <div class="kpi-val" style="color: #d97706;">{{ number_format($summary['today_unchecked_count']) }}</div>
            <div class="kpi-sub">{{ $summary['today_formatted'] }}</div>
        </div>
        <div class="kpi-icon-box" style="background: #fef3c7; color: #d97706;">
            <i class="fa-solid fa-clock"></i>
        </div>
    </div>

    {{-- Card 3: >= 3 Hari Tidak Hadir --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #e11d48;">&ge; 3 Hari Tidak Hadir</div>
            <div class="kpi-val" style="color: #e11d48;">{{ number_format($summary['ge3_days_count']) }}</div>
            <div class="kpi-sub">Perlu perhatian / follow-up segera</div>
        </div>
        <div class="kpi-icon-box" style="background: #ffe4e6; color: #e11d48;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    {{-- Card 4: Belum Pernah Hadir --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #475569;">Belum Pernah Hadir</div>
            <div class="kpi-val" style="color: #334155;">{{ number_format($summary['never_attended_count']) }}</div>
            <div class="kpi-sub">Tidak ada riwayat presensi</div>
        </div>
        <div class="kpi-icon-box" style="background: #f1f5f9; color: #475569;">
            <i class="fa-solid fa-ban"></i>
        </div>
    </div>
</div>

<!-- Admin-Style Filter Card -->
<div class="pro-filter-card">
    <form action="{{ route('portal.unchecked') }}" method="GET">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <input type="hidden" name="filter" value="{{ $quickFilter }}">

        <div class="filter-grid">
            {{-- Filter Area / Cabang --}}
            <div class="filter-group">
                <label>Filter Area / Cabang</label>
                <select name="branch_id" class="filter-input">
                    <option value="">-- Semua Area / Cabang --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ ($branchId ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Toko Penempatan --}}
            <div class="filter-group">
                <label>Filter Toko Penempatan</label>
                <select name="location_id" class="filter-input">
                    <option value="">-- Semua Toko Penempatan --</option>
                    @foreach($workLocations as $loc)
                        <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Pencarian Cepat --}}
            <div class="filter-group" style="grid-column: span 1 / -1;">
                <label>Cari Karyawan / NIK / Toko / Jabatan</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="q" class="filter-input" style="flex: 1;" placeholder="Ketik nama promotor, NIK, toko penempatan..." value="{{ $search }}">
                    <button type="submit" class="btn-filter-submit">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="{{ route('portal.unchecked', ['p' => $tenantPrincipal->id]) }}" class="btn-filter-reset">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </a>
                </div>
            </div>
        </div>

        {{-- Filter Status Cepat Pills (Identik Admin Dashboard) --}}
        <div class="filter-pills-bar">
            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 4px;">
                Status Cepat:
            </span>

            @php
                $queryParams = request()->except(['filter', 'page']);
                $queryParams['p'] = $tenantPrincipal->id;
            @endphp

            <a href="{{ route('portal.unchecked', array_merge($queryParams, ['filter' => 'today'])) }}"
               class="filter-btn-pill {{ $quickFilter === 'today' ? 'active' : '' }}">
                <i class="fa-solid fa-clock"></i>
                Belum Check-In Hari Ini
                <span class="pill-count">{{ number_format($summary['today_unchecked_count']) }}</span>
            </a>

            <a href="{{ route('portal.unchecked', array_merge($queryParams, ['filter' => 'all'])) }}"
               class="filter-btn-pill {{ $quickFilter === 'all' ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-week"></i>
                Semua (7 Hari Terakhir)
                <span class="pill-count">{{ number_format($summary['total_unchecked_7days']) }}</span>
            </a>

            <a href="{{ route('portal.unchecked', array_merge($queryParams, ['filter' => 'ge3'])) }}"
               class="filter-btn-pill {{ $quickFilter === 'ge3' ? 'active' : '' }}">
                <i class="fa-solid fa-triangle-exclamation"></i>
                &ge; 3 Hari Tidak Hadir
                <span class="pill-count">{{ number_format($summary['ge3_days_count']) }}</span>
            </a>

            <a href="{{ route('portal.unchecked', array_merge($queryParams, ['filter' => 'never'])) }}"
               class="filter-btn-pill {{ $quickFilter === 'never' ? 'active' : '' }}">
                <i class="fa-solid fa-ban"></i>
                Belum Pernah Hadir
                <span class="pill-count">{{ number_format($summary['never_attended_count']) }}</span>
            </a>
        </div>
    </form>
</div>

<!-- Admin-Style Table Container -->
<div class="pro-table-container">
    <table class="pro-bordered-table">
        <thead>
            <tr>
                <th>Karyawan / SPG</th>
                <th>Cabang / Area</th>
                <th>Toko Penempatan</th>
                <th style="width: 130px;">Shift Jadwal</th>
                <th style="text-align: center; width: 150px;">Jam Masuk Seharusnya</th>
                <th style="text-align: center; width: 170px;">Riwayat / Keterangan</th>
                <th style="text-align: center; width: 150px;">Status Real-Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unchecked as $item)
                @php
                    $empNameUpper = strtoupper($item['full_name']);
                    $nikText = $item['nik'];
                    $posText = $item['position_name'];
                    $branchText = $item['branch_name'];
                    $storeName = strtoupper($item['store_name']);
                    $shiftName = $item['shift_name'];
                    $shiftTime = $item['shift_start_time'];
                @endphp
                <tr>
                    <td>
                        <div class="emp-cell-wrapper">
                            @if(!empty($item['employee']?->photo))
                                <img src="{{ asset('storage/' . $item['employee']->photo) }}" alt="{{ $empNameUpper }}" class="emp-avatar-box" style="object-fit: cover;">
                            @else
                                <div class="emp-avatar-box">
                                    {{ strtoupper(substr($empNameUpper, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="emp-name-text">{{ $empNameUpper }}</div>
                                <div class="emp-sub-text">
                                    <span class="nik-badge">NIK: {{ $nikText }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $posText }}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-weight: 700; color: #0f172a; font-size: 12.5px;">{{ $branchText }}</span>
                    </td>
                    <td>
                        <div style="font-weight: 800; color: #0f172a; text-transform: uppercase; font-size: 12.5px;">{{ $storeName }}</div>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #475569; font-size: 12px;">{{ $shiftName }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span style="font-weight: 800; color: #dc2626; font-family: monospace; font-size: 13px;">
                            <i class="fa-regular fa-clock" style="font-size: 11px;"></i> {{ $shiftTime }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        @if($item['days_since_last'] == -1)
                            <span class="badge-status badge-gray">
                                <i class="fa-solid fa-ban" style="font-size: 10px;"></i> Belum Pernah Hadir
                            </span>
                        @elseif($item['is_today_unchecked'])
                            @if($item['missed_count_7days'] >= 3)
                                <span class="badge-status badge-absent" title="Terlewat {{ $item['missed_count_7days'] }} hari dalam 7 hari terakhir">
                                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 10px;"></i> {{ $item['missed_count_7days'] }} Hari Terlewat
                                </span>
                            @else
                                <span class="badge-status badge-late">
                                    <i class="fa-solid fa-clock" style="font-size: 10px;"></i> Hari Ini
                                </span>
                            @endif
                        @else
                            <span class="badge-status badge-gray">
                                Terakhir: {{ $item['last_attendance_date'] }}
                            </span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge-status badge-absent">
                            <i class="fa-solid fa-circle-exclamation"></i> Belum Check-in
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3.5rem 1rem; color: #16a34a; font-weight: 700;">
                        <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: #16a34a;"></i>
                        <span style="font-size: 14px;">Luar biasa! Tidak ada promotor yang belum check-in pada kriteria filter ini.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1rem 1.25rem; border-top: 1px solid #cbd5e1; background: #fafbfc; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 12px; color: #64748b; font-weight: 500;">
            Menampilkan data <strong>{{ $unchecked->firstItem() ?? 0 }}</strong> - <strong>{{ $unchecked->lastItem() ?? 0 }}</strong> dari total <strong>{{ $unchecked->total() }}</strong> promotor
        </div>
        <div>
            {{ $unchecked->appends(request()->query())->links('portal.pagination') }}
        </div>
    </div>
</div>
@endsection
