@extends('portal.layout')

@section('title', 'Presensi & Kehadiran - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Presensi & Kehadiran Promotor / SPG')
@section('breadcrumb_active', 'Presensi & Kehadiran')

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
        background: var(--brand-primary, #dc2626);
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
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        border-color: var(--brand-primary, #dc2626);
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    .filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
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

    .badge-present { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-late { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-leave { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
    .badge-absent { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .time-badge-in {
        color: #16a34a;
        font-weight: 800;
        font-family: monospace;
        font-size: 13px;
    }

    .time-badge-out {
        color: #2563eb;
        font-weight: 800;
        font-family: monospace;
        font-size: 13px;
    }

    .gps-link-box {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        color: #0284c7;
        text-decoration: none;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .gps-link-box:hover {
        text-decoration: underline;
        color: #0369a1;
    }
</style>
@endpush

@section('content')
<!-- Header Card -->
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large">
            <i class="fa-solid fa-clipboard-user"></i>
        </div>
        <div>
            <h2 class="page-title-text">Presensi & Kehadiran Promotor</h2>
            <div class="page-meta-row">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Periode: <strong>{{ $startDate->translatedFormat('d M Y') }}</strong> s/d <strong>{{ $endDate->translatedFormat('d M Y') }}</strong></span>
            </div>
        </div>
    </div>

    <div>
        <a href="{{ route('portal.attendances.export', array_merge(request()->query(), ['p' => $tenantPrincipal->id])) }}" class="btn-action-primary">
            <i class="fa-solid fa-file-excel"></i>
            Export Data Presensi (CSV)
        </a>
    </div>
</div>

<!-- KPI Cards Grid (Admin-Style) -->
<div class="kpi-grid">
    {{-- Card 1: Total Log Presensi --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #64748b;">Total Log Presensi</div>
            <div class="kpi-val" style="color: #0f172a;">{{ number_format($stats['total']) }}</div>
            <div class="kpi-sub">Periode Terpilih</div>
        </div>
        <div class="kpi-icon-box" style="background: #e0f2fe; color: #0284c7;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    {{-- Card 2: Hadir On-Time --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #16a34a;">Hadir On-Time</div>
            <div class="kpi-val" style="color: #16a34a;">{{ number_format($stats['present']) }}</div>
            <div class="kpi-sub">Datang Tepat Waktu</div>
        </div>
        <div class="kpi-icon-box" style="background: #dcfce7; color: #16a34a;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    {{-- Card 3: Terlambat (Late) --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #d97706;">Terlambat (Late)</div>
            <div class="kpi-val" style="color: #d97706;">{{ number_format($stats['late']) }}</div>
            <div class="kpi-sub">Perlu Perhatian / Follow-up</div>
        </div>
        <div class="kpi-icon-box" style="background: #fef3c7; color: #d97706;">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
    </div>

    {{-- Card 4: Izin / Cuti / Sakit --}}
    <div class="kpi-card">
        <div>
            <div class="kpi-label" style="color: #7c3aed;">Izin / Cuti / Sakit</div>
            <div class="kpi-val" style="color: #7c3aed;">{{ number_format($stats['leave']) }}</div>
            <div class="kpi-sub">Pengajuan Disetujui</div>
        </div>
        <div class="kpi-icon-box" style="background: #ede9fe; color: #7c3aed;">
            <i class="fa-solid fa-envelope-open-text"></i>
        </div>
    </div>
</div>

<!-- Admin-Style Filter Card -->
<div class="pro-filter-card">
    <form action="{{ route('portal.attendances') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">

        {{-- Tanggal Mulai --}}
        <div class="filter-group">
            <label>Tgl Mulai</label>
            <input type="date" name="start_date" class="filter-input" value="{{ $startDate->format('Y-m-d') }}">
        </div>

        {{-- Tanggal Akhir --}}
        <div class="filter-group">
            <label>Tgl Akhir</label>
            <input type="date" name="end_date" class="filter-input" value="{{ $endDate->format('Y-m-d') }}">
        </div>

        {{-- Filter Area / Cabang (Baru, Selaras Dashboard Admin) --}}
        <div class="filter-group">
            <label>Area / Cabang</label>
            <select name="branch_id" class="filter-input">
                <option value="">-- Semua Area / Cabang --</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ ($branchId ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filter Toko / Outlet --}}
        <div class="filter-group">
            <label>Toko / Outlet</label>
            <select name="location_id" class="filter-input">
                <option value="">-- Semua Toko / Outlet --</option>
                @foreach($workLocations as $loc)
                    <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filter Status --}}
        <div class="filter-group">
            <label>Status Kehadiran</label>
            <select name="status" class="filter-input">
                <option value="">Semua Status</option>
                <option value="present" {{ ($status ?? '') == 'present' ? 'selected' : '' }}>Hadir On-Time</option>
                <option value="late" {{ ($status ?? '') == 'late' ? 'selected' : '' }}>Terlambat (Late)</option>
                <option value="leave" {{ ($status ?? '') == 'leave' ? 'selected' : '' }}>Izin / Cuti / Sakit</option>
            </select>
        </div>

        {{-- Pencarian Nama / NIK --}}
        <div class="filter-group" style="grid-column: span 1 / -1;">
            <label>Cari Karyawan / NIK</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" name="q" class="filter-input" style="flex: 1;" placeholder="Ketik nama karyawan atau NIK promotor..." value="{{ $search }}">
                <button type="submit" class="btn-filter-submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('portal.attendances', ['p' => $tenantPrincipal->id]) }}" class="btn-filter-reset">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Admin-Style Table Container -->
<div class="pro-table-container">
    <table class="pro-bordered-table">
        <thead>
            <tr>
                <th style="width: 120px;">Tanggal</th>
                <th>Karyawan / SPG</th>
                <th>Toko / Lokasi Kerja</th>
                <th style="text-align: center; width: 110px;">Jam Masuk</th>
                <th style="text-align: center; width: 110px;">Jam Keluar</th>
                <th style="text-align: center; width: 100px;">Durasi</th>
                <th style="text-align: center; width: 150px;">Status</th>
                <th>Lokasi GPS Check-in</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $att)
                @php
                    $stat = strtolower($att->status ?? 'present');
                    $badgeClass = 'badge-present';
                    $badgeLabel = 'Hadir On-Time';
                    if (str_contains($stat, 'late') || str_contains($stat, 'lambat')) {
                        $badgeClass = 'badge-late';
                        $badgeLabel = 'Terlambat (' . ($att->late_minutes ?? 0) . ' mnt)';
                    } elseif (str_contains($stat, 'leave') || str_contains($stat, 'cuti') || str_contains($stat, 'izin') || str_contains($stat, 'sick') || str_contains($stat, 'sakit')) {
                        $badgeClass = 'badge-leave';
                        $badgeLabel = 'Izin / Cuti';
                    } elseif (str_contains($stat, 'absent') || str_contains($stat, 'alpha')) {
                        $badgeClass = 'badge-absent';
                        $badgeLabel = 'Alpha / Tidak Hadir';
                    }

                    // Format nama seragam KAPITAL
                    $empNameUpper = strtoupper($att->employee?->full_name ?? 'KARYAWAN');
                    $nikText = $att->employee?->nik ?? ($att->employee?->employee_no ?? '-');
                    $posText = $att->employee?->position?->name ?? 'SPG';
                    $branchText = $att->employee?->branch?->name ?? '-';
                    $storeName = $att->employeeSchedule?->workLocation?->name ?? ($att->employee?->workLocation?->name ?? 'Toko / Outlet');

                    // GPS Link
                    $hasCoords = !empty($att->checkinLog?->latitude) && !empty($att->checkinLog?->longitude);
                    $mapsUrl = $hasCoords ? "https://www.google.com/maps?q={$att->checkinLog->latitude},{$att->checkinLog->longitude}" : null;
                @endphp
                <tr>
                    <td style="font-weight: 700; white-space: nowrap; color: #0f172a;">
                        {{ Carbon\Carbon::parse($att->attendance_date)->translatedFormat('d M Y') }}
                        <div style="font-size: 11px; font-weight: 500; color: #64748b;">
                            {{ Carbon\Carbon::parse($att->attendance_date)->translatedFormat('l') }}
                        </div>
                    </td>
                    <td>
                        <div class="emp-cell-wrapper">
                            @if(!empty($att->employee?->photo))
                                <img src="{{ asset('storage/' . $att->employee->photo) }}" alt="{{ $empNameUpper }}" class="emp-avatar-box" style="object-fit: cover;">
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
                        <div style="font-weight: 800; color: #0f172a; text-transform: uppercase; font-size: 12.5px;">{{ strtoupper($storeName) }}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                            <i class="fa-solid fa-map-location-dot" style="font-size: 10px; color: #94a3b8;"></i> Area: {{ $branchText }}
                        </div>
                    </td>
                    <td style="text-align: center;">
                        @if($att->checkin_at)
                            <span class="time-badge-in">
                                <i class="fa-regular fa-clock" style="font-size: 11px;"></i> {{ Carbon\Carbon::parse($att->checkin_at)->format('H:i:s') }}
                            </span>
                        @else
                            <span style="color: #94a3b8; font-weight: 700;">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($att->checkout_at)
                            <span class="time-badge-out">
                                <i class="fa-regular fa-clock" style="font-size: 11px;"></i> {{ Carbon\Carbon::parse($att->checkout_at)->format('H:i:s') }}
                            </span>
                        @else
                            <span style="color: #94a3b8; font-weight: 700;">-</span>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: 700; color: #334155; font-size: 12.5px;">
                        {{ $att->work_duration_minutes ? round($att->work_duration_minutes / 60, 1) . ' Jam' : '-' }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge-status {{ $badgeClass }}">
                            @if(str_contains($badgeClass, 'present'))
                                <i class="fa-solid fa-circle-check"></i>
                            @elseif(str_contains($badgeClass, 'late'))
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            @elseif(str_contains($badgeClass, 'leave'))
                                <i class="fa-solid fa-envelope-open-text"></i>
                            @else
                                <i class="fa-solid fa-circle-exclamation"></i>
                            @endif
                            {{ $badgeLabel }}
                        </span>
                    </td>
                    <td>
                        @if($att->checkinLog)
                            @if($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" class="gps-link-box" title="{{ $att->checkinLog->address_text ?? ($att->checkinLog->latitude . ', ' . $att->checkinLog->longitude) }}">
                                    <i class="fa-solid fa-location-dot" style="color: #dc2626;"></i>
                                    <span>{{ $att->checkinLog->address_text ?? ($att->checkinLog->latitude . ', ' . $att->checkinLog->longitude) }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; opacity: 0.7;"></i>
                                </a>
                            @else
                                <div style="font-size: 11.5px; color: #475569;">
                                    <i class="fa-solid fa-location-dot" style="color: #dc2626;"></i>
                                    {{ $att->checkinLog->address_text ?? '-' }}
                                </div>
                            @endif
                        @else
                            <span style="color: #94a3b8; font-size: 12px;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3.5rem 1rem; color: #64748b;">
                        <i class="fa-solid fa-clipboard-user" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3; display: block; color: #94a3b8;"></i>
                        <span style="font-size: 13.5px; font-weight: 600;">Belum ada catatan log presensi promotor pada filter yang dipilih.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1rem 1.25rem; border-top: 1px solid #cbd5e1; background: #fafbfc; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 12px; color: #64748b; font-weight: 500;">
            Menampilkan data <strong>{{ $attendances->firstItem() ?? 0 }}</strong> - <strong>{{ $attendances->lastItem() ?? 0 }}</strong> dari total <strong>{{ $attendances->total() }}</strong> catatan
        </div>
        <div>
            {{ $attendances->appends(request()->query())->links('portal.pagination') }}
        </div>
    </div>
</div>
@endsection
