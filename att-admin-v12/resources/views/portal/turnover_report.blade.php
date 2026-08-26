@extends('portal.layout')

@section('title', 'Turnover Report - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Laporan Turnover Promotor & SPG')
@section('breadcrumb_active', 'Turnover Report')

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
    .custom-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: center; }
    .custom-table th { background: #f8fafc; padding: 0.9rem 1.1rem; font-weight: 700; color: var(--text-heading); border-bottom: 1px solid var(--border-color); }
    .custom-table td { padding: 0.9rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-body); }
</style>
@endpush

@section('content')
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
        <div>
            <h2 class="page-title-text">Laporan Turnover Karyawan (Tahun {{ $year }})</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Statistik Masuk & Keluar Promotor per Bulan</span>
            </div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.turnover_report') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tahun</label>
            <select name="year" class="filter-input" onchange="this.form.submit()">
                @for($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div style="margin-top: 18px;">
            <button type="submit" class="btn-action-primary"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th style="text-align: left;">Bulan</th>
                <th>Karyawan Awal Bulan</th>
                <th>Karyawan Masuk (Join)</th>
                <th>Karyawan Keluar (Resign)</th>
                <th>Karyawan Akhir Bulan</th>
                <th>Turnover Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($turnoverRows as $row)
                <tr>
                    <td style="text-align: left; font-weight: 700; color: var(--text-heading);">
                        <i class="fa-regular fa-calendar" style="color: var(--brand-primary); margin-right: 0.3rem;"></i>
                        {{ $row['month_name'] }}
                    </td>
                    <td style="font-weight: 600;">{{ $row['start_count'] }} Orang</td>
                    <td style="color: #16a34a; font-weight: 700;">+{{ $row['joined'] }}</td>
                    <td style="color: #dc2626; font-weight: 700;">-{{ $row['resigned'] }}</td>
                    <td style="font-weight: 700; color: var(--text-heading);">{{ $row['end_count'] }} Orang</td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 800; font-size: 0.78rem; {{ $row['rate'] <= 5 ? 'background: #dcfce7; color: #15803d;' : 'background: #fef3c7; color: #b45309;' }}">
                            {{ $row['rate'] }}%
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
