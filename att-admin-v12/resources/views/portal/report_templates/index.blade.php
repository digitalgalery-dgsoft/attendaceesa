@extends('portal.layout')

@section('title', 'Form Builder (Template Laporan) - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Form Builder (Template Laporan SOP)')
@section('breadcrumb_active', 'Form Builder')

@push('styles')
<style>
    /* Premium Header Card */
    .fb-header-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .fb-header-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 260px;
        height: 100%;
        background: linear-gradient(135deg, transparent 40%, rgba(15, 82, 186, 0.04) 100%);
        pointer-events: none;
    }

    .fb-header-left {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .fb-header-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(15, 82, 186, 0.12), rgba(15, 82, 186, 0.04));
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        border: 1px solid rgba(15, 82, 186, 0.15);
        box-shadow: 0 4px 12px rgba(15, 82, 186, 0.08);
        flex-shrink: 0;
    }

    .fb-header-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 0.35rem;
    }

    .fb-header-subtitle {
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.4;
    }

    .btn-create-template {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.75rem 1.45rem;
        background: var(--brand-gradient);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 4px 14px var(--brand-glow);
        transition: all 0.25s ease;
    }

    .btn-create-template:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--brand-glow);
        color: #ffffff;
    }

    /* KPI Summary Cards */
    .fb-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .fb-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .fb-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .fb-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .fb-stat-info {
        flex: 1;
    }

    .fb-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
        margin-bottom: 0.2rem;
    }

    .fb-stat-label {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    /* Filter & Search Bar */
    .fb-filter-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.1rem 1.35rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .fb-search-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
        min-width: 280px;
    }

    .fb-search-input-wrapper {
        position: relative;
        flex: 1;
    }

    .fb-search-input-wrapper i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .fb-search-input {
        width: 100%;
        padding: 0.65rem 1rem 0.65rem 2.6rem;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.88rem;
        background: #f8fafc;
        color: var(--text-heading);
        transition: all 0.2s ease;
    }

    .fb-search-input:focus {
        background: #ffffff;
        border-color: var(--brand-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(15, 82, 186, 0.12);
    }

    .fb-select-filter {
        padding: 0.65rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.88rem;
        background: #f8fafc;
        color: var(--text-heading);
        cursor: pointer;
        min-width: 180px;
    }

    .fb-select-filter:focus {
        border-color: var(--brand-primary);
        outline: none;
    }

    /* Modern Table Container */
    .fb-table-container {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .fb-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .fb-table thead th {
        background: #f8fafc;
        color: var(--text-muted);
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .fb-table tbody td {
        padding: 1.15rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
        color: var(--text-body);
        vertical-align: middle;
    }

    .fb-table tbody tr:last-child td {
        border-bottom: none;
    }

    .fb-table tbody tr:hover td {
        background-color: #fafcff;
    }

    /* Template Title & Code */
    .tpl-title-block {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .tpl-title-text {
        font-weight: 700;
        color: var(--text-heading);
        font-size: 0.95rem;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .tpl-title-text:hover {
        color: var(--brand-primary);
    }

    .tpl-code-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.74rem;
        font-weight: 700;
        font-family: monospace;
        color: #475569;
        background: #f1f5f9;
        padding: 0.15rem 0.5rem;
        border-radius: 6px;
        width: fit-content;
        border: 1px solid #e2e8f0;
    }

    /* Category Badges */
    .cat-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cat-offtake, .cat-sellout { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .cat-stock { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .cat-pricing, .cat-price { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .cat-promo { background: #fdf4ff; color: #86198f; border: 1px solid #f5d0fe; }
    .cat-display, .cat-posm { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
    .cat-competitor { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; }
    .cat-expiry { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .cat-survey, .cat-general { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }

    /* Schedule Badge */
    .sched-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.76rem;
        font-weight: 700;
        background: #f8fafc;
        color: var(--text-heading);
        padding: 0.25rem 0.6rem;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    /* Toggle Switch */
    .switch-toggle {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .switch-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider-toggle {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 24px;
    }

    .slider-toggle:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    input:checked + .slider-toggle {
        background-color: #10b981;
    }

    input:checked + .slider-toggle:before {
        transform: translateX(20px);
    }

    /* Action Buttons */
    .fb-action-group {
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }

    .btn-action-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: var(--text-muted);
        background: #ffffff;
        border: 1px solid var(--border-color);
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .btn-action-icon:hover {
        background: var(--brand-light);
        color: var(--brand-primary);
        border-color: var(--brand-primary);
        transform: translateY(-1px);
    }

    .btn-action-icon.btn-danger-hover:hover {
        background: #fef2f2;
        color: #ef4444;
        border-color: #fca5a5;
    }

    /* Empty State */
    .fb-empty-state {
        padding: 4rem 2rem;
        text-align: center;
        color: var(--text-muted);
    }

    .fb-empty-icon {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<!-- Header Card -->
<div class="fb-header-card">
    <div class="fb-header-left">
        <div class="fb-header-icon">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>
        <div>
            <div class="fb-header-title">Form Builder & Template Laporan</div>
            <div class="fb-header-subtitle">
                Kelola struktur formulir pelaporan SOP, jadwal kuota, pertanyaan dinamis, dan penugasan khusus prinsiple <strong>{{ $tenantPrincipal->name }}</strong>.
            </div>
        </div>
    </div>
    <div class="fb-header-right">
        <a href="{{ route('portal.report_templates.create', ['p' => $tenantPrincipal->id]) }}" class="btn-create-template">
            <i class="fa-solid fa-plus"></i>
            <span>Buat Template Baru</span>
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="fb-stats-grid">
    <div class="fb-stat-card">
        <div class="fb-stat-icon" style="background: #eff6ff; color: #2563eb;">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div class="fb-stat-info">
            <div class="fb-stat-value">{{ number_format($totalTemplates) }}</div>
            <div class="fb-stat-label">Total Template Form</div>
        </div>
    </div>
    <div class="fb-stat-card">
        <div class="fb-stat-icon" style="background: #ecfdf5; color: #059669;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="fb-stat-info">
            <div class="fb-stat-value">{{ number_format($totalActive) }}</div>
            <div class="fb-stat-label">Form Aktif (Mobile)</div>
        </div>
    </div>
    <div class="fb-stat-card">
        <div class="fb-stat-icon" style="background: #faf5ff; color: #9333ea;">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="fb-stat-info">
            <div class="fb-stat-value">{{ number_format($totalFields) }}</div>
            <div class="fb-stat-label">Total Field / Pertanyaan</div>
        </div>
    </div>
    <div class="fb-stat-card">
        <div class="fb-stat-icon" style="background: #fff7ed; color: #ea580c;">
            <i class="fa-solid fa-cloud-arrow-up"></i>
        </div>
        <div class="fb-stat-info">
            <div class="fb-stat-value">{{ number_format($totalSubmissions) }}</div>
            <div class="fb-stat-label">Total Laporan Terkirim</div>
        </div>
    </div>
</div>

<!-- Filter & Search Bar -->
<form method="GET" action="{{ route('portal.report_templates') }}" class="fb-filter-card">
    <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
    
    <div class="fb-search-group">
        <div class="fb-search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="{{ $search }}" class="fb-search-input" placeholder="Cari nama form template atau kode...">
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <select name="category" class="fb-select-filter" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            @foreach($categories as $catKey => $catLabel)
                <option value="{{ $catKey }}" {{ $category === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
            @endforeach
        </select>

        <select name="status" class="fb-select-filter" style="min-width: 140px;" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>🟢 Aktif</option>
            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>⚪ Non-Aktif</option>
        </select>

        @if(!empty($search) || !empty($category) || !empty($status))
            <a href="{{ route('portal.report_templates', ['p' => $tenantPrincipal->id]) }}" class="btn-action-icon" title="Reset Filter" style="width: auto; padding: 0 0.85rem; gap: 0.35rem; color: #ef4444; border-color: #fca5a5;">
                <i class="fa-solid fa-rotate-left"></i>
                <span style="font-size: 0.8rem; font-weight: 700;">Reset</span>
            </a>
        @endif
    </div>
</form>

<!-- Table Container -->
<div class="fb-table-container">
    @if($templates->count() > 0)
        <table class="fb-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Judul Form & Kode</th>
                    <th>Kategori</th>
                    <th>Jadwal / Kuota</th>
                    <th>Jumlah Field</th>
                    <th>Penugasan</th>
                    <th>Submissions</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $item)
                    @php
                        $catClass = 'cat-' . ($item->category ?? 'general');
                    @endphp
                    <tr>
                        <td>
                            <div class="tpl-title-block">
                                <a href="{{ route('portal.report_templates.edit', ['id' => $item->id, 'p' => $tenantPrincipal->id]) }}" class="tpl-title-text">
                                    {{ $item->title }}
                                </a>
                                <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.15rem;">
                                    <span class="tpl-code-badge">{{ $item->code }}</span>
                                    @if($item->require_gps)
                                        <span title="Wajib Titik GPS" style="font-size: 0.72rem; color: #0284c7; background: #e0f2fe; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700;">
                                            <i class="fa-solid fa-location-dot"></i> GPS
                                        </span>
                                    @endif
                                    @if($item->require_signature)
                                        <span title="Wajib Tanda Tangan" style="font-size: 0.72rem; color: #7c3aed; background: #ede9fe; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700;">
                                            <i class="fa-solid fa-signature"></i> TTD
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cat-badge {{ $catClass }}">
                                {{ $categories[$item->category] ?? ucfirst($item->category) }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                <span class="sched-badge">
                                    @if($item->schedule_type === 'weekly')
                                        🗓️ Weekly ({{ $item->target_count ?? 1 }}x / mgg)
                                    @elseif($item->schedule_type === 'monthly')
                                        📆 Monthly ({{ $item->target_count ?? 1 }}x / bln)
                                    @else
                                        📅 Daily (Harian)
                                    @endif
                                </span>
                                @if(!empty($item->report_days) && is_array($item->report_days))
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        Hari: {{ implode(', ', array_map('ucfirst', $item->report_days)) }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <span style="font-weight: 800; color: var(--text-heading); font-size: 0.95rem;">
                                    {{ $item->fields->count() }}
                                </span>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">pertanyaan</span>
                            </div>
                            @if($item->products->count() > 0)
                                <div style="font-size: 0.72rem; color: var(--brand-primary); margin-top: 0.15rem; font-weight: 600;">
                                    <i class="fa-solid fa-boxes-stacked"></i> {{ $item->products->count() }} SKU Terpilih
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $posCount = $item->positions->count();
                                $empCount = $item->employees->count();
                            @endphp
                            @if($posCount == 0 && $empCount == 0)
                                <span style="font-size: 0.78rem; color: #64748b; font-weight: 600;">
                                    <i class="fa-solid fa-globe"></i> Semua Tim
                                </span>
                            @else
                                <div style="display: flex; flex-direction: column; gap: 0.15rem; font-size: 0.76rem;">
                                    @if($posCount > 0)
                                        <span style="color: #2563eb; font-weight: 700;">
                                            <i class="fa-solid fa-id-badge"></i> {{ $posCount }} Jabatan
                                        </span>
                                    @endif
                                    @if($empCount > 0)
                                        <span style="color: #059669; font-weight: 700;">
                                            <i class="fa-solid fa-user-check"></i> {{ $empCount }} Karyawan
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('portal.report.detail', ['code' => $item->code, 'p' => $tenantPrincipal->id]) }}" style="display: inline-flex; align-items: center; gap: 0.35rem; text-decoration: none; font-weight: 800; color: var(--brand-primary);">
                                <span>{{ number_format($item->submissions_count ?? 0) }}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                            </a>
                        </td>
                        <td style="text-align: center;">
                            <label class="switch-toggle" title="Klik untuk mengaktifkan / menonaktifkan">
                                <input type="checkbox" {{ $item->is_active ? 'checked' : '' }} onchange="toggleActive({{ $item->id }}, this)">
                                <span class="slider-toggle"></span>
                            </label>
                        </td>
                        <td style="text-align: right;">
                            <div class="fb-action-group" style="justify-content: flex-end;">
                                <a href="{{ route('portal.report.detail', ['code' => $item->code, 'p' => $tenantPrincipal->id]) }}" class="btn-action-icon" title="Lihat Dashboard & Data Submissions">
                                    <i class="fa-solid fa-chart-pie"></i>
                                </a>
                                <a href="{{ route('portal.report_templates.edit', ['id' => $item->id, 'p' => $tenantPrincipal->id]) }}" class="btn-action-icon" title="Edit Visual Form Builder">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('portal.report_templates.duplicate', ['id' => $item->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Duplikasi form template \'{{ $item->title }}\'?')">
                                    @csrf
                                    <button type="submit" class="btn-action-icon" title="Duplikasi Template Form">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </form>
                                <form action="{{ route('portal.report_templates.destroy', ['id' => $item->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template form \'{{ $item->title }}\'? Seluruh data pertanyaan di dalamnya akan dihapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action-icon btn-danger-hover" title="Hapus Template Form">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        @if($templates->hasPages())
            <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
                {{ $templates->appends(request()->query())->links('portal.pagination') }}
            </div>
        @endif
    @else
        <div class="fb-empty-state">
            <div class="fb-empty-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.35rem;">
                Belum Ada Form Template yang Sesuai
            </h3>
            <p style="font-size: 0.85rem; max-width: 480px; margin: 0 auto 1.5rem auto;">
                Tidak ditemukan template laporan yang sesuai dengan filter pencarian. Anda dapat membuat form baru untuk kebutuhan tim lapangan Anda.
            </p>
            <a href="{{ route('portal.report_templates.create', ['p' => $tenantPrincipal->id]) }}" class="btn-create-template">
                <i class="fa-solid fa-plus"></i>
                <span>Buat Form Template Sekarang</span>
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleActive(templateId, checkbox) {
        const isChecked = checkbox.checked;
        const originalState = !isChecked;

        fetch(`{{ url('/portal/report-templates') }}/${templateId}/toggle-active?p={{ $tenantPrincipal->id }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                checkbox.checked = originalState;
                alert('Gagal mengubah status template.');
            }
        })
        .catch(err => {
            console.error('Error toggling template status:', err);
            checkbox.checked = originalState;
            alert('Terjadi kesalahan jaringan saat mengubah status template.');
        });
    }
</script>
@endpush
