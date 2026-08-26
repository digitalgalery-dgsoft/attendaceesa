@extends('portal.layout')

@section('title', 'Mandays Report - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Laporan Mandays & Efektivitas Kehadiran')
@section('breadcrumb_active', 'Mandays Report')

@push('styles')
<style>
    .page-header-card {
        background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between;
    }
    .page-header-left { display: flex; align-items: center; gap: 1.1rem; }
    .page-icon-large {
        width: 52px; height: 52px; border-radius: 14px; background: var(--brand-light); color: var(--brand-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;
    }
    .page-title-text { font-size: 1.35rem; font-weight: 800; color: var(--text-heading); margin-bottom: 0.25rem; }
    .filter-card { background: #ffffff; border: 1px solid var(--border-color); border-radius: 14px; padding: 1.1rem 1.4rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); }
    .filter-grid { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .filter-input { padding: 0.55rem 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; color: var(--text-heading); outline: none; background: #ffffff; }
    .btn-action-primary {
        background: var(--brand-gradient); color: #ffffff; padding: 0.55rem 1.2rem; border-radius: 8px; font-weight: 700;
        font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;
    }
    .table-container { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .custom-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left; }
    .custom-table th { background: #f8fafc; padding: 0.9rem 1.1rem; font-weight: 700; color: var(--text-heading); border-bottom: 1px solid var(--border-color); }
    .custom-table td { padding: 0.9rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-body); }
</style>
@endpush

@section('content')
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-chart-line"></i></div>
        <div>
            <h2 class="page-title-text">Laporan Mandays Kehadiran</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Periode: {{ Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</span>
            </div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.mandays_report') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Bulan</label>
            <select name="month" class="filter-input" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
        </div>
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tahun</label>
            <select name="year" class="filter-input" onchange="this.form.submit()">
                @for($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Area / Cabang</label>
            <select name="branch_id" class="filter-input" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="">🗺️ Semua Area</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama karyawan / NIK..." value="{{ $search }}">
        </div>
        <div style="margin-top: 18px;">
            <button type="submit" class="btn-action-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Promotor / SPG</th>
                <th>NIK</th>
                <th>Cabang / Area</th>
                <th style="text-align: center;">Target Hari Kerja</th>
                <th style="text-align: center;">Realisasi Hadir</th>
                <th style="text-align: center;">Izin / Cuti</th>
                <th style="text-align: center;">% Efektivitas Mandays</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mandaysData as $row)
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $row['employee']->full_name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $row['employee']->position?->name ?? 'SPG' }}</div>
                    </td>
                    <td style="font-family: monospace; font-weight: 700;">{{ $row['employee']->nik ?? '-' }}</td>
                    <td>{{ $row['employee']->branch?->name ?? '-' }}</td>
                    <td style="text-align: center; font-weight: 700;">{{ $row['target_days'] }} Hari</td>
                    <td style="text-align: center; font-weight: 700; color: #16a34a;">{{ $row['present_days'] }} Hari</td>
                    <td style="text-align: center; font-weight: 700; color: #7c3aed;">{{ $row['leave_days'] }} Hari</td>
                    <td style="text-align: center;">
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 800; font-size: 0.78rem; {{ $row['percentage'] >= 85 ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' }}">
                            {{ $row['percentage'] }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data mandays promotor pada bulan yang dipilih.
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
