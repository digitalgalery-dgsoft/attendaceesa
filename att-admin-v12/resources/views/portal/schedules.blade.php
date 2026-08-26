@extends('portal.layout')

@section('title', 'Roster & Jadwal Kerja - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Roster & Jadwal Kerja Promotor')
@section('breadcrumb_active', 'Roster Jadwal')

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
        <div class="page-icon-large"><i class="fa-solid fa-calendar-week"></i></div>
        <div>
            <h2 class="page-title-text">Jadwal Roster Promotor</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Jadwal penugasan shift dan lokasi toko kerja promotor</div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.schedules') }}" method="GET" class="filter-grid">
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
                @for($y = Carbon\Carbon::now()->year - 1; $y <= Carbon\Carbon::now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
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
                <th>Tanggal Jadwal</th>
                <th>Karyawan / SPG</th>
                <th>Cabang / Area</th>
                <th>Penempatan Toko</th>
                <th>Shift Kerja</th>
                <th>Jam Masuk - Pulang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $sch)
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ Carbon\Carbon::parse($sch->schedule_date)->translatedFormat('d M Y') }}
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $sch->employee?->full_name ?? 'Karyawan' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $sch->employee?->nik ?? '-' }}</div>
                    </td>
                    <td>{{ $sch->employee?->branch?->name ?? '-' }}</td>
                    <td>{{ $sch->workLocation?->name ?? ($sch->employee?->workLocation?->name ?? 'Toko Default') }}</td>
                    <td>
                        <span style="font-weight: 700; color: var(--brand-primary);">{{ $sch->shift?->name ?? 'Normal Shift' }}</span>
                    </td>
                    <td>{{ $sch->shift?->start_time ?? '08:00' }} - {{ $sch->shift?->end_time ?? '17:00' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada jadwal roster pada periode bulan yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $schedules->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>
@endsection
