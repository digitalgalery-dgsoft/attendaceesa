@extends('portal.layout')

@section('title', 'Daftar Area & Cabang - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Daftar Area & Cabang Operasional')
@section('breadcrumb_active', 'Areas')

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
    .table-container { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .custom-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left; }
    .custom-table th { background: #f8fafc; padding: 0.9rem 1.1rem; font-weight: 700; color: var(--text-heading); border-bottom: 1px solid var(--border-color); }
    .custom-table td { padding: 0.9rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-body); }
</style>
@endpush

@section('content')
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-map-location-dot"></i></div>
        <div>
            <h2 class="page-title-text">Area & Cabang Operasional</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Wilayah distribusi dan operasional tim promotor</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Nama Cabang / Region</th>
                <th>Alamat Kantor / Depo</th>
                <th>Jumlah SPG Terdaftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $b)
                <tr>
                    <td style="font-weight: 700; color: var(--text-heading);">
                        <i class="fa-solid fa-building" style="color: var(--brand-primary); margin-right: 0.4rem;"></i>
                        {{ $b->name }}
                    </td>
                    <td>{{ $b->address ?? '-' }}</td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.6rem; border-radius: 9999px; background: var(--brand-light); color: var(--brand-primary); font-weight: 800; font-size: 0.78rem;">
                            <i class="fa-solid fa-users"></i> {{ $b->employees_count ?? 0 }} Promotor
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data cabang/area.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
