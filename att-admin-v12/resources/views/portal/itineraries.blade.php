@extends('portal.layout')

@section('title', 'Jadwal Kunjungan (Itinerari) - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Jadwal Kunjungan (Itinerari Toko)')
@section('breadcrumb_active', 'Itinerari Kunjungan')

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
        <div class="page-icon-large"><i class="fa-solid fa-route"></i></div>
        <div>
            <h2 class="page-title-text">Jadwal Visitasi & Itinerari Promotor</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Daftar rencana rute kunjungan promotor dan MD per tanggal</div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.itineraries') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tanggal</label>
            <input type="date" name="date" class="filter-input" value="{{ $date }}">
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama promotor / NIK..." value="{{ $search }}">
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
                <th>Tanggal</th>
                <th>Promotor / SPG</th>
                <th>Cabang / Area</th>
                <th>Daftar Toko Kunjungan (Rute Itinerari)</th>
                <th>Catatan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itineraries as $itn)
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ Carbon\Carbon::parse($itn->date)->translatedFormat('d M Y') }}
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $itn->employee?->full_name ?? 'Petugas' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $itn->employee?->nik ?? '-' }}</div>
                    </td>
                    <td>{{ $itn->employee?->branch?->name ?? '-' }}</td>
                    <td>
                        @if($itn->items && $itn->items->count() > 0)
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                @foreach($itn->items as $item)
                                    <div style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8rem;">
                                        <span style="width: 20px; height: 20px; border-radius: 50%; background: var(--brand-light); color: var(--brand-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800;">
                                            {{ $item->sequence ?? ($loop->index + 1) }}
                                        </span>
                                        <strong>{{ $item->workLocation?->name ?? 'Toko / Outlet' }}</strong>
                                        @if($item->visit_type)
                                            <span style="font-size: 0.7rem; color: var(--text-muted);">({{ $item->visit_type }})</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span style="color: #94a3b8; font-size: 0.8rem;">Belum ada rute toko</span>
                        @endif
                    </td>
                    <td>{{ $itn->notes ?? '-' }}</td>
                    <td>
                        <span style="padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #dcfce7; color: #15803d;">
                            {{ strtoupper($itn->status ?? 'ACTIVE') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada jadwal itinerari pada tanggal yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $itineraries->appends(request()->query())->links() }}
    </div>
</div>
@endsection
