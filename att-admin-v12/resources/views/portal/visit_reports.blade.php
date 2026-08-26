@extends('portal.layout')

@section('title', 'Laporan Kunjungan Toko - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Laporan Kunjungan & Visit Toko')
@section('breadcrumb_active', 'Visit Reports')

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
        <div class="page-icon-large"><i class="fa-solid fa-file-waveform"></i></div>
        <div>
            <h2 class="page-title-text">Laporan Kunjungan (Visit Reports)</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Aktivitas kunjungan promotor dan MD ke outlet / toko</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Waktu Visit</th>
                <th>Karyawan / MD / SPG</th>
                <th>Toko / Outlet</th>
                <th>Cabang / Region</th>
                <th>Catatan Kunjungan</th>
                <th>Foto / Bukti</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visitReports as $vr)
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ $vr->visited_at ? Carbon\Carbon::parse($vr->visited_at)->translatedFormat('d M Y H:i') : '-' }}
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $vr->employee?->full_name ?? 'Petugas' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $vr->employee?->nik ?? '-' }}</div>
                    </td>
                    <td style="font-weight: 600;">{{ $vr->itineraryItem?->workLocation?->name ?? 'Toko / Outlet' }}</td>
                    <td>{{ $vr->employee?->branch?->name ?? '-' }}</td>
                    <td>{{ $vr->notes ?? '-' }}</td>
                    <td>
                        @if(!empty($vr->photo_path))
                            <a href="{{ asset('storage/' . $vr->photo_path) }}" target="_blank" style="color: var(--brand-primary); font-weight: 700; text-decoration: none;">
                                <i class="fa-solid fa-image"></i> Lihat Foto
                            </a>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data laporan kunjungan toko.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $visitReports->appends(request()->query())->links() }}
    </div>
</div>
@endsection
