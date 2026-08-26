@extends('portal.layout')

@section('title', 'Monitoring Belum Check-in - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Monitoring Promotor Belum Check-in')
@section('breadcrumb_active', 'Monitoring Kehadiran')

@push('styles')
<style>
    .page-header-card {
        background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between;
    }
    .page-header-left { display: flex; align-items: center; gap: 1.1rem; }
    .page-icon-large {
        width: 52px; height: 52px; border-radius: 14px; background: #fee2e2; color: #ef4444;
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
        <div class="page-icon-large"><i class="fa-solid fa-user-slash"></i></div>
        <div>
            <h2 class="page-title-text">Monitoring Tim Belum Check-in Hari Ini</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                Tanggal: {{ Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }} &bull; Total Belum Hadir: <strong>{{ $unchecked->total() }} Orang</strong>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Karyawan / SPG</th>
                <th>NIK</th>
                <th>Cabang / Area</th>
                <th>Toko Penempatan</th>
                <th>Shift Jadwal</th>
                <th>Jam Masuk Seharusnya</th>
                <th>Status Real-Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unchecked as $item)
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $item->employee?->full_name ?? 'Karyawan' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $item->employee?->position?->name ?? 'SPG' }}</div>
                    </td>
                    <td style="font-family: monospace; font-weight: 700;">{{ $item->employee?->nik ?? '-' }}</td>
                    <td>{{ $item->employee?->branch?->name ?? '-' }}</td>
                    <td>{{ $item->workLocation?->name ?? ($item->employee?->workLocation?->name ?? 'Toko Default') }}</td>
                    <td>{{ $item->shift?->name ?? 'Default Shift' }}</td>
                    <td style="font-weight: 700; color: #ef4444;">
                        <i class="fa-regular fa-clock"></i> {{ $item->shift?->start_time ?? '08:00' }}
                    </td>
                    <td>
                        <span style="padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #fee2e2; color: #b91c1c;">
                            <i class="fa-solid fa-circle-exclamation"></i> Belum Check-in
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: #16a34a; font-weight: 700;">
                        <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block;"></i>
                        Luar biasa! Seluruh promotor yang memiliki jadwal hari ini telah melakukan check-in.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $unchecked->appends(request()->query())->links() }}
    </div>
</div>
@endsection
