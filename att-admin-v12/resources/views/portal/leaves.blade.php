@extends('portal.layout')

@section('title', 'Izin & Cuti Karyawan - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Pengajuan Izin & Cuti Promotor')
@section('breadcrumb_active', 'Izin / Cuti')

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
    .badge-approved { background: #dcfce7; color: #15803d; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; }
    .badge-pending { background: #fef3c7; color: #b45309; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; }
    .badge-rejected { background: #fee2e2; color: #b91c1c; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; }
</style>
@endpush

@section('content')
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-envelope-open-text"></i></div>
        <div>
            <h2 class="page-title-text">Pengajuan Izin & Cuti Promotor</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Riwayat permohonan cuti, izin, dan surat sakit</div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.leaves') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Status Approval</label>
            <select name="status" class="filter-input">
                <option value="">Semua Status</option>
                <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama karyawan / NIK / alasan..." value="{{ $search }}">
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
                <th>Karyawan / SPG</th>
                <th>Jenis Izin / Cuti</th>
                <th>Tgl Mulai</th>
                <th>Tgl Selesai</th>
                <th>Keterangan / Alasan</th>
                <th>Lampiran</th>
                <th>Status Approval</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaves as $lv)
                @php
                    $st = strtolower($lv->status ?? 'pending');
                    $badgeClass = 'badge-pending';
                    $badgeText = 'Menunggu';
                    if ($st == 'approved' || $st == 'disetujui') {
                        $badgeClass = 'badge-approved';
                        $badgeText = 'Disetujui';
                    } elseif ($st == 'rejected' || $st == 'ditolak') {
                        $badgeClass = 'badge-rejected';
                        $badgeText = 'Ditolak';
                    }
                @endphp
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $lv->employee?->full_name ?? 'Karyawan' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $lv->employee?->branch?->name ?? '-' }}</div>
                    </td>
                    <td style="font-weight: 700; color: var(--brand-primary);">{{ strtoupper($lv->type ?? 'CUTI') }}</td>
                    <td>{{ $lv->start_date ? Carbon\Carbon::parse($lv->start_date)->translatedFormat('d M Y') : '-' }}</td>
                    <td>{{ $lv->end_date ? Carbon\Carbon::parse($lv->end_date)->translatedFormat('d M Y') : '-' }}</td>
                    <td>{{ $lv->notes ?? '-' }}</td>
                    <td>
                        @if(!empty($lv->attachment_path))
                            <a href="{{ asset('storage/' . $lv->attachment_path) }}" target="_blank" style="color: var(--brand-primary); font-weight: 700; text-decoration: none;">
                                <i class="fa-solid fa-paperclip"></i> Lihat File
                            </a>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada data pengajuan izin atau cuti.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $leaves->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>
@endsection
