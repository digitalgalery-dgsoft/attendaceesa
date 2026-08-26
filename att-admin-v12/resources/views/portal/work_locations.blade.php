@extends('portal.layout')

@section('title', 'Daftar Lokasi Kerja & Toko - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Daftar Toko & Lokasi Penempatan')
@section('breadcrumb_active', 'Work Locations')

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
    .page-header-left { display: flex; align-items: center; gap: 1.1rem; }
    .page-icon-large {
        width: 52px; height: 52px; border-radius: 14px; background: var(--brand-light); color: var(--brand-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;
    }
    .page-title-text { font-size: 1.35rem; font-weight: 800; color: var(--text-heading); line-height: 1.25; margin-bottom: 0.25rem; }
    .page-meta-row { display: flex; align-items: center; gap: 0.6rem; font-size: 0.82rem; color: var(--text-muted); }
    .filter-card { background: #ffffff; border: 1px solid var(--border-color); border-radius: 14px; padding: 1.1rem 1.4rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); }
    .filter-grid { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .filter-input { padding: 0.55rem 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; color: var(--text-heading); outline: none; background: #ffffff; }
    .btn-action-primary {
        background: var(--brand-gradient); color: #ffffff; padding: 0.55rem 1.2rem; border-radius: 8px; font-weight: 700;
        font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;
    }
    .table-container { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .custom-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left; }
    .custom-table th { background: #f8fafc; padding: 0.9rem 1.1rem; font-weight: 700; color: var(--text-heading); border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .custom-table td { padding: 0.9rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-body); }
    .custom-table tr:hover { background-color: #f8fafc; }
</style>
@endpush

@section('content')
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-store"></i></div>
        <div>
            <h2 class="page-title-text">Outlet & Toko Penempatan</h2>
            <div class="page-meta-row">
                <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                <span>&bull;</span>
                <span>Total Data: {{ number_format($workLocations->total()) }} Outlet Terdaftar</span>
            </div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.work_locations') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Area / Cabang</label>
            <select name="branch_id" class="filter-input" style="min-width: 180px;">
                <option value="">🗺️ Semua Area</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama toko / kode / alamat..." value="{{ $search }}">
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
                <th>Kode Toko</th>
                <th>Nama Toko / Outlet</th>
                <th>Cabang / Region</th>
                <th>Kota / Alamat</th>
                <th>Radius GPS (Geofence)</th>
                <th>Status Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workLocations as $loc)
                <tr>
                    <td style="font-family: monospace; font-weight: 700;">{{ $loc->code ?? '-' }}</td>
                    <td style="font-weight: 700; color: var(--text-heading);">{{ $loc->name }}</td>
                    <td>{{ $loc->branch?->name ?? '-' }}</td>
                    <td>
                        <div>{{ $loc->city ?? '-' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $loc->address ?? '-' }}</div>
                    </td>
                    <td>
                        <span style="font-size: 0.8rem; font-weight: 700; color: #16a34a;">
                            <i class="fa-solid fa-circle-dot"></i> {{ $loc->radius_meter ?? 100 }} Meter
                        </span>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.6rem; border-radius: 9999px; background: #dcfce7; color: #15803d; font-weight: 700; font-size: 0.75rem;">
                            <i class="fa-solid fa-circle-check"></i> Aktif
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        <i class="fa-solid fa-store" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3; display: block;"></i>
                        Belum ada outlet/toko penempatan yang terdaftar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $workLocations->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>
@endsection
