@extends('portal.layout')

@section('title', 'Lembur Karyawan - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Pengajuan Lembur (Extra Hours)')
@section('breadcrumb_active', 'Lembur')

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
        <div class="page-icon-large"><i class="fa-solid fa-user-clock"></i></div>
        <div>
            <h2 class="page-title-text">Pengajuan Lembur (Extra Hours)</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Riwayat tugas lembur dan tambahan jam kerja lapangan</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Karyawan / SPG</th>
                <th>Jam Mulai - Selesai</th>
                <th>Durasi</th>
                <th>Tugas / Keterangan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($extraHours as $eh)
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ $eh->date ? Carbon\Carbon::parse($eh->date)->translatedFormat('d M Y') : '-' }}
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $eh->employee?->full_name ?? 'Karyawan' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $eh->employee?->branch?->name ?? '-' }}</div>
                    </td>
                    <td>{{ $eh->start_time ?? '-' }} - {{ $eh->end_time ?? '-' }}</td>
                    <td><span style="font-weight: 700; color: var(--brand-primary);">{{ $eh->duration ?? 0 }} Jam</span></td>
                    <td>{{ $eh->notes ?? '-' }}</td>
                    <td>
                        <span style="padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #dcfce7; color: #15803d;">
                            {{ strtoupper($eh->status ?? 'APPROVED') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data pengajuan lembur.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $extraHours->appends(request()->query())->links() }}
    </div>
</div>
@endsection
