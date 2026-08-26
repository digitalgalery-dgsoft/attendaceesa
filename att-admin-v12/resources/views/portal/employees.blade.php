@extends('portal.layout')

@section('title', 'Daftar Karyawan & SPG - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Daftar Karyawan / Promotor / SPG')
@section('breadcrumb_active', 'Employees')

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
        grid-template-columns: repeat(3, 1fr);
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

    .badge-active { background: #dcfce7; color: #15803d; }
    .badge-inactive { background: #fee2e2; color: #b91c1c; }

    svg.w-5.h-5,
    svg.w-6.h-6,
    .table-container svg,
    nav[role="navigation"] svg {
        width: 1.25rem !important;
        height: 1.25rem !important;
        max-width: 1.25rem !important;
        max-height: 1.25rem !important;
        display: inline-block !important;
    }

    @media (max-width: 992px) {
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
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <h2 class="page-title-text">Tenaga Lapangan & Promotor (SPG)</h2>
            <div class="page-meta-row">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Total Data: {{ number_format($stats['total']) }} Orang Terdaftar</span>
            </div>
        </div>
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
            <div class="stat-box-lbl">Total Seluruh Promotor</div>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-icon" style="background: #dcfce7; color: #16a34a;">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div>
            <div class="stat-box-val">{{ number_format($stats['active']) }}</div>
            <div class="stat-box-lbl">Status Aktif</div>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-icon" style="background: #fee2e2; color: #ef4444;">
            <i class="fa-solid fa-user-xmark"></i>
        </div>
        <div>
            <div class="stat-box-val">{{ number_format($stats['inactive']) }}</div>
            <div class="stat-box-lbl">Resigned / Non-Aktif</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-card">
    <form action="{{ route('portal.employees') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Area / Cabang</label>
            <select name="branch_id" class="filter-input" style="min-width: 180px;">
                <option value="">🗺️ Semua Area / Cabang</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Posisi / Jabatan</label>
            <select name="position_id" class="filter-input" style="min-width: 180px;">
                <option value="">🪪 Semua Jabatan</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos->id }}" {{ $positionId == $pos->id ? 'selected' : '' }}>{{ $pos->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Status</label>
            <select name="status" class="filter-input">
                <option value="">Semua Status</option>
                <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ $status === '0' ? 'selected' : '' }}>Resign / Non-Aktif</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama karyawan / NIK / No. HP..." value="{{ $search }}">
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
                <th>Foto & Karyawan</th>
                <th>NIK</th>
                <th>Posisi / Jabatan</th>
                <th>Cabang / Area</th>
                <th>Penempatan Toko</th>
                <th>No. Telepon / WA</th>
                <th>Status</th>
                <th>Tgl Bergabung</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            @if(!empty($emp->photo))
                                <img src="{{ asset('storage/' . $emp->photo) }}" alt="{{ $emp->full_name }}" class="emp-avatar">
                            @else
                                <div class="emp-avatar">
                                    {{ strtoupper(substr($emp->full_name ?? 'E', 0, 1)) }}
                                </div>
                            @endif
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $emp->full_name }}</div>
                        </div>
                    </td>
                    <td style="font-family: monospace; font-weight: 700;">{{ $emp->nik ?? '-' }}</td>
                    <td>{{ $emp->position?->name ?? 'SPG / Promotor' }}</td>
                    <td>{{ $emp->branch?->name ?? '-' }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $emp->workLocation?->name ?? 'Semua Toko' }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $emp->workLocation?->city ?? '' }}</div>
                    </td>
                    <td>{{ $emp->phone ?? '-' }}</td>
                    <td>
                        @if($emp->is_active)
                            <span class="badge-status badge-active"><i class="fa-solid fa-circle-check"></i> Aktif</span>
                        @else
                            <span class="badge-status badge-inactive"><i class="fa-solid fa-circle-xmark"></i> Resign</span>
                        @endif
                    </td>
                    <td>{{ $emp->join_date ? Carbon\Carbon::parse($emp->join_date)->translatedFormat('d M Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        <i class="fa-solid fa-users" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3; display: block;"></i>
                        Tidak ditemukan data karyawan promotor sesuai filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $employees->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>
@endsection
