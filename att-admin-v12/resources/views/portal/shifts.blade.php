@extends('portal.layout')

@section('title', 'Master Shift Kerja - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Master Shift Kerja')
@section('breadcrumb_active', 'Shifts')

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
        <div class="page-icon-large"><i class="fa-solid fa-business-time"></i></div>
        <div>
            <h2 class="page-title-text">Shift & Jam Kerja Operasional</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Ketentuan jam masuk dan jam pulang promotor lapangan</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Nama Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Toleransi Keterlambatan</th>
                <th>Estimasi Jam Kerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $s)
                <tr>
                    <td style="font-weight: 700; color: var(--text-heading);">{{ $s->name }}</td>
                    <td style="color: #16a34a; font-weight: 700;"><i class="fa-regular fa-clock"></i> {{ $s->start_time }}</td>
                    <td style="color: #2563eb; font-weight: 700;"><i class="fa-regular fa-clock"></i> {{ $s->end_time }}</td>
                    <td>{{ $s->late_tolerance_minutes ?? 0 }} Menit</td>
                    <td>{{ $s->work_hours ?? 8 }} Jam</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data shift yang terkonfigurasi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $shifts->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>
@endsection
