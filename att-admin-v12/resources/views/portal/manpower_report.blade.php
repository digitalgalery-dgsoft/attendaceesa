@extends('portal.layout')

@section('title', 'Manpower Report - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Laporan Rekapitulasi Manpower (Tenaga Kerja)')
@section('breadcrumb_active', 'Manpower Report')

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
    .table-container { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); overflow-x: auto; }
    .custom-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; text-align: center; }
    .custom-table th { background: #f8fafc; padding: 0.75rem 0.6rem; font-weight: 700; color: var(--text-heading); border: 1px solid var(--border-color); }
    .custom-table td { padding: 0.75rem 0.6rem; border: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-body); }
    .custom-table tfoot td { background: #f8fafc; font-weight: 800; color: var(--text-heading); border: 1px solid var(--border-color); }
</style>
@endpush

@section('content')
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-chart-column"></i></div>
        <div>
            <h2 class="page-title-text">Laporan Manpower Bulanan (Tahun {{ $year }})</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Rata-rata Manpower: <strong>{{ $totalAverage }} Orang / Bulan</strong></span>
            </div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.manpower_report') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
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
        <div style="margin-top: 18px;">
            <button type="submit" class="btn-action-primary"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th style="text-align: left; min-width: 180px;">Cabang / Region</th>
                <th>Jan</th>
                <th>Feb</th>
                <th>Mar</th>
                <th>Apr</th>
                <th>Mei</th>
                <th>Jun</th>
                <th>Jul</th>
                <th>Ags</th>
                <th>Sep</th>
                <th>Okt</th>
                <th>Nov</th>
                <th>Des</th>
                <th style="background: var(--brand-light); color: var(--brand-primary);">Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branchData as $row)
                <tr>
                    <td style="text-align: left; font-weight: 700; color: var(--text-heading);">
                        <i class="fa-solid fa-building" style="color: var(--brand-primary); margin-right: 0.3rem;"></i>
                        {{ $row['branch']->name }}
                    </td>
                    @for($m = 1; $m <= 12; $m++)
                        <td>{{ $row['months'][$m] > 0 ? $row['months'][$m] : '-' }}</td>
                    @endfor
                    <td style="font-weight: 800; background: #f8fafc; color: var(--brand-primary);">
                        {{ $row['average'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data manpower untuk prinsiple pada tahun {{ $year }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align: left;">TOTAL MANPOWER</td>
                @for($m = 1; $m <= 12; $m++)
                    <td>{{ $monthlyTotals[$m] > 0 ? $monthlyTotals[$m] : '-' }}</td>
                @endfor
                <td style="background: var(--brand-light); color: var(--brand-primary); font-size: 0.95rem;">
                    {{ $totalAverage }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
