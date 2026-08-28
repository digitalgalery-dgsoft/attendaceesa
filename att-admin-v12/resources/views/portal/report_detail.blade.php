@extends('portal.layout')

@section('title', $template->title . ' - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', $template->title)
@section('breadcrumb_active', $template->code)

@push('styles')
<style>
    .template-detail-header {
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

    .template-header-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .template-icon-large {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: var(--brand-light);
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .template-title-text {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 0.25rem;
    }

    .template-meta-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.82rem;
        color: var(--text-muted);
    }

    .template-code-pill {
        font-family: monospace;
        font-weight: 700;
        background: #f1f5f9;
        color: var(--text-heading);
        padding: 0.2rem 0.6rem;
        border-radius: 6px;
        border: 1px solid var(--border-color);
    }

    .btn-export-excel {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.35rem;
        background: #16a34a;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }

    .btn-export-excel:hover {
        background: #15803d;
        transform: translateY(-2px);
    }

    /* Mini Stats */
    .mini-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .mini-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
    }

    .mini-stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .mini-stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
    }

    /* Filter Bar */
    .filter-bar {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 0.85rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        flex-wrap: wrap;
    }

    .filter-group-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-select-btn {
        padding: 0.5rem 0.95rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-heading);
        outline: none;
        cursor: pointer;
    }

    .filter-search-input {
        padding: 0.5rem 0.85rem;
        font-size: 0.85rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: #f8fafc;
        outline: none;
        width: 220px;
    }

    .table-container-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .custom-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        text-align: left;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .custom-table td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: var(--text-body);
        vertical-align: middle;
    }

    .custom-table tr:hover td {
        background: #f8fafc;
    }

    .img-thumb-preview {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid var(--border-color);
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .img-thumb-preview:hover {
        transform: scale(1.15);
    }

    @media (max-width: 768px) {
        .mini-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

    <!-- Header Card -->
    <div class="template-detail-header">
        <div class="template-header-left">
            <div class="template-icon-large">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div>
                <h2 class="template-title-text">{{ $template->title }}</h2>
                <div class="template-meta-row">
                    <span class="template-code-pill">{{ $template->code }}</span>
                    <span>&bull;</span>
                    <span>Kategori: <strong>{{ ucfirst($template->category ?? 'General') }}</strong></span>
                    <span>&bull;</span>
                    <span>{{ $template->fields->count() }} Field Input</span>
                </div>
            </div>
        </div>

        <div>
            <a href="{{ route('portal.report.export', ['code' => $template->code, 'month' => $month, 'year' => $year, 'p' => $tenantPrincipal->id]) }}" class="btn-export-excel">
                <i class="fa-solid fa-file-excel"></i>
                Export Rekap CSV / Excel
            </a>
        </div>
    </div>

    <!-- Mini Stats -->
    <div class="mini-stats-grid">
        <div class="mini-stat-card">
            <div class="mini-stat-label">Total Laporan Periode {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</div>
            <div class="mini-stat-value">{{ number_format($totalTemplateSubmissions ?? 0) }}</div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-label">Outlet / Toko Terjangkau</div>
            <div class="mini-stat-value">{{ number_format($uniqueStores ?? 0) }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" method="GET" class="filter-bar">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div class="filter-group-left">
            <select name="month" class="filter-select-btn" onchange="this.form.submit()">
                @for ($m = 1; $m <= 12; $m++)
                    @php
                        $dateObj = Carbon\Carbon::create(null, $m, 1);
                    @endphp
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ $dateObj->translatedFormat('F') }} {{ $year }}
                    </option>
                @endfor
            </select>

            <select name="employee_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">👥 Semua Petugas / SPG</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>

            <select name="location_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">🏢 Semua Toko</option>
                @foreach($workLocations as $loc)
                    <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="q" class="filter-search-input" placeholder="Cari petugas / toko..." value="{{ $search }}">
        </div>

        <div>
            <button type="submit" class="filter-select-btn" style="background: var(--brand-gradient); color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px var(--brand-glow);">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
        </div>
    </form>

    <!-- Submissions Table Card -->
    <div class="table-container-card">
        @if($submissions->isNotEmpty())
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 140px;">No. Laporan</th>
                            <th>Tanggal & Waktu</th>
                            <th>Petugas (SPG/MD)</th>
                            <th>Toko / Outlet</th>
                            <th style="text-align: center;">Validasi GPS</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $idx => $sub)
                            @php
                                $status = $sub->status ?? 'pending';
                                $storeName = $sub->workLocation?->name ?? $sub->itineraryItem?->destination ?? $sub->store_name ?? 'Kunjungan Toko';
                            @endphp
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 700; text-align: center;">
                                    {{ $submissions->firstItem() + $idx }}
                                </td>
                                <td>
                                    <span class="template-code-pill" style="font-size: 0.78rem; color: #0F52BA; background: rgba(15, 82, 186, 0.08); border-color: rgba(15, 82, 186, 0.2);">
                                        {{ $sub->submission_code }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y') : '-' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->submitted_at ? $sub->submitted_at->format('H:i:s') : '-' }} WIB
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->employee?->full_name ?? $sub->employee?->name ?? 'Petugas' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        NIK: {{ $sub->employee?->nik ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $storeName }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->employee?->branch?->name ?? ($sub->workLocation?->address ? \Illuminate\Support\Str::limit($sub->workLocation->address, 35) : '-') }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    @if($sub->is_within_radius)
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-circle-check"></i> Valid
                                        </span>
                                    @else
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.65rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Luar Radius
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if(in_array($status, ['approved', 'verified']))
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                            Terverifikasi
                                        </span>
                                    @elseif($status === 'rejected')
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                            Ditolak
                                        </span>
                                    @else
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                            Menunggu
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.45rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; background: #f1f5f9; color: #0F52BA; border: 1px solid #cbd5e1; transition: all 0.15s ease;" onmouseover="this.style.background='#0F52BA'; this.style.color='#fff';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#0F52BA';">
                                        <i class="fa-solid fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.25rem;">
                {{ $submissions->appends(request()->query())->links('portal.pagination') }}
            </div>
        @else
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-heading);">Belum Ada Data Laporan Masuk</div>
                <p style="font-size: 0.85rem; max-width: 420px; margin: 0.35rem auto 0;">
                    Data submission untuk formulir <strong>{{ $template->title }}</strong> akan otomatis terisi saat petugas SPG/MD mengirimkan laporan melalui aplikasi mobile.
                </p>
            </div>
        @endif
    </div>

@endsection
