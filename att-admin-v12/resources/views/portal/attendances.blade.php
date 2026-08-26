@extends('portal.layout')

@section('title', 'Presensi & Kehadiran - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Presensi & Kehadiran Promotor / SPG')
@section('breadcrumb_active', 'Presensi & Kehadiran')

@push('styles')
<style>
    .page-header-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
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
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: var(--brand-light);
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .page-title-text {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 0.25rem;
    }

    .page-meta-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.82rem;
        color: var(--text-muted);
    }

    .btn-action-primary {
        background: var(--brand-gradient);
        color: #ffffff;
        padding: 0.65rem 1.35rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        box-shadow: 0 3px 10px var(--brand-glow);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-action-primary:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
    }

    /* Stats Grid */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .stat-box {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-box-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .stat-box-val {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
    }

    .stat-box-lbl {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-muted);
    }

    /* Filter Bar */
    .filter-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.1rem 1.4rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .filter-grid {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-input {
        padding: 0.55rem 0.9rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.85rem;
        color: var(--text-heading);
        outline: none;
        background: #ffffff;
    }

    .filter-input:focus {
        border-color: var(--brand-primary);
    }

    /* Table */
    .table-container {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        text-align: left;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 0.9rem 1.1rem;
        font-weight: 700;
        color: var(--text-heading);
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .custom-table td {
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: var(--text-body);
    }

    .custom-table tr:hover {
        background-color: #f8fafc;
    }

    .emp-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        background: var(--brand-light);
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.85rem;
        border: 1px solid var(--brand-glow);
    }

    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-present { background: #dcfce7; color: #15803d; }
    .badge-late { background: #fef3c7; color: #b45309; }
    .badge-leave { background: #ede9fe; color: #6d28d9; }
    .badge-absent { background: #fee2e2; color: #b91c1c; }

    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 640px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
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
                <span>Periode: {{ $startDate->translatedFormat('d M Y') }} s/d {{ $endDate->translatedFormat('d M Y') }}</span>
            </div>
        </div>
    </div>

    <div>
        <a href="{{ route('portal.attendances.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'p' => $tenantPrincipal->id]) }}" class="btn-action-primary">
            <i class="fa-solid fa-file-excel"></i>
            Export Data Presensi (CSV)
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #e0f2fe; color: #0284c7;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="stat-box-val">{{ number_format($stats['total']) }}</div>
            <div class="stat-box-lbl">Total Log Presensi</div>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-icon" style="background: #dcfce7; color: #16a34a;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="stat-box-val">{{ number_format($stats['present']) }}</div>
            <div class="stat-box-lbl">Hadir On-Time</div>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-icon" style="background: #fef3c7; color: #d97706;">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div>
            <div class="stat-box-val">{{ number_format($stats['late']) }}</div>
            <div class="stat-box-lbl">Terlambat (Late)</div>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-icon" style="background: #ede9fe; color: #7c3aed;">
            <i class="fa-solid fa-envelope-open-text"></i>
        </div>
        <div>
            <div class="stat-box-val">{{ number_format($stats['leave']) }}</div>
            <div class="stat-box-lbl">Izin / Cuti / Sakit</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-card">
    <form action="{{ route('portal.attendances') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tgl Mulai</label>
            <input type="date" name="start_date" class="filter-input" value="{{ $startDate->format('Y-m-d') }}">
        </div>

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tgl Akhir</label>
            <input type="date" name="end_date" class="filter-input" value="{{ $endDate->format('Y-m-d') }}">
        </div>

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Toko / Outlet</label>
            <select name="location_id" class="filter-input" style="min-width: 180px;">
                <option value="">🏢 Semua Toko</option>
                @foreach($workLocations as $loc)
                    <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Status</label>
            <select name="status" class="filter-input">
                <option value="">Semua Status</option>
                <option value="present" {{ $status == 'present' ? 'selected' : '' }}>Hadir</option>
                <option value="late" {{ $status == 'late' ? 'selected' : '' }}>Terlambat</option>
                <option value="leave" {{ $status == 'leave' ? 'selected' : '' }}>Izin / Cuti</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama karyawan / NIK..." value="{{ $search }}">
        </div>

        <div style="margin-top: 18px;">
            <button type="submit" class="btn-action-primary" style="padding: 0.55rem 1.2rem;">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Karyawan / SPG</th>
                <th>Toko / Lokasi Kerja</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Durasi</th>
                <th>Status</th>
                <th>Lokasi GPS Checkin</th>
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
                    } elseif (str_contains($stat, 'leave') || str_contains($stat, 'cuti') || str_contains($stat, 'izin')) {
                        $badgeClass = 'badge-leave';
                        $badgeLabel = 'Izin / Cuti';
                    }
                @endphp
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ Carbon\Carbon::parse($att->attendance_date)->translatedFormat('d M Y') }}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            @if(!empty($att->employee?->photo))
                                <img src="{{ asset('storage/' . $att->employee->photo) }}" alt="{{ $att->employee->full_name }}" class="emp-avatar">
                            @else
                                <div class="emp-avatar">
                                    {{ strtoupper(substr($att->employee?->full_name ?? 'E', 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $att->employee?->full_name ?? 'Karyawan' }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $att->employee?->nik ?? '-' }} &bull; {{ $att->employee?->position?->name ?? 'SPG' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $att->employeeSchedule?->workLocation?->name ?? ($att->employee?->workLocation?->name ?? 'Toko / Outlet') }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $att->employee?->branch?->name ?? '-' }}</div>
                    </td>
                    <td>
                        @if($att->checkin_at)
                            <span style="color: #16a34a; font-weight: 700;">{{ Carbon\Carbon::parse($att->checkin_at)->format('H:i:s') }}</span>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($att->checkout_at)
                            <span style="color: #2563eb; font-weight: 700;">{{ Carbon\Carbon::parse($att->checkout_at)->format('H:i:s') }}</span>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        {{ $att->work_duration_minutes ? round($att->work_duration_minutes / 60, 1) . ' Jam' : '-' }}
                    </td>
                    <td>
                        <span class="badge-status {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </td>
                    <td>
                        @if($att->checkinLog)
                            <div style="font-size: 0.78rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $att->checkinLog->address_text ?? ($att->checkinLog->latitude . ', ' . $att->checkinLog->longitude) }}">
                                <i class="fa-solid fa-location-dot" style="color: var(--brand-primary);"></i>
                                {{ $att->checkinLog->address_text ?? ($att->checkinLog->latitude . ', ' . $att->checkinLog->longitude) }}
                            </div>
                        @else
                            <span style="color: #94a3b8; font-size: 0.78rem;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        <i class="fa-solid fa-clipboard-user" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3; display: block;"></i>
                        Belum ada catatan presensi promotor pada periode filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $attendances->appends(request()->query())->links() }}
    </div>
</div>
@endsection
