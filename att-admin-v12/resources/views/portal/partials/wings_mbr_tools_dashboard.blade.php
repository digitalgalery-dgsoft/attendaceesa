{{-- 
    WINGS SURYA - EXECUTIVE DASHBOARD LAPORAN TOOLS (PROPERTI FREE TASTE)
    Identitas Visual: 100% Selaras dengan Principal Portal (Native Brand Tokens, Clean White Cards, Soft Badges, ApexCharts)
    Template: RPT-WINGS-MBR-TOOLS-01
--}}

@push('styles')
<style>
    .portal-tools-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
        width: 100%;
        max-width: 100%;
    }

    /* 1. Filter Bar */
    .portal-tools-filter-bar {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.15rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .portal-tools-filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }
    .portal-tools-filter-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-tools-filter-title i {
        color: #d32f2f;
    }
    .portal-tools-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
        align-items: flex-end;
    }
    .portal-tools-field-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .portal-tools-field-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .portal-tools-input, .portal-tools-select {
        width: 100%;
        padding: 0.55rem 0.85rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-heading);
        outline: none;
        transition: all 0.2s ease;
    }
    .portal-tools-input:focus, .portal-tools-select:focus {
        background: #ffffff;
        border-color: #d32f2f;
        box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.15);
    }
    .portal-tools-btn-submit {
        background: linear-gradient(135deg, #d32f2f, #b71c1c);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 0.6rem 1.25rem;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        box-shadow: 0 2px 8px rgba(211, 47, 47, 0.3);
        transition: all 0.2s ease;
    }
    .portal-tools-btn-submit:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(211, 47, 47, 0.4);
    }
    .portal-tools-btn-reset {
        background: #f1f5f9;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        transition: all 0.2s ease;
    }
    .portal-tools-btn-reset:hover {
        background: #e2e8f0;
        color: var(--text-heading);
    }
    .portal-tools-btn-action {
        background: #fff;
        border: 1px solid var(--border-color);
        color: var(--text-heading);
        border-radius: 8px;
        padding: 0.4rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s ease;
    }
    .portal-tools-btn-action:hover {
        background: #f8fafc;
        border-color: #d32f2f;
        color: #d32f2f;
    }

    /* 2. KPI Cards Grid */
    .portal-tools-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.15rem;
    }
    @media (max-width: 1200px) {
        .portal-tools-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .portal-tools-kpi-grid { grid-template-columns: 1fr; }
    }

    .portal-tools-kpi-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .portal-tools-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .portal-tools-kpi-info {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        z-index: 2;
    }
    .portal-tools-kpi-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .portal-tools-kpi-val {
        font-size: 1.75rem;
        font-weight: 900;
        color: var(--text-heading);
        line-height: 1.1;
        display: flex;
        align-items: baseline;
        gap: 0.35rem;
    }
    .portal-tools-kpi-unit {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-muted);
    }
    .portal-tools-kpi-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.2rem;
    }
    .portal-tools-kpi-progress {
        height: 6px;
        background: #f1f5f9;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.4rem;
        width: 140px;
    }
    .portal-tools-kpi-progress-bar {
        height: 100%;
        border-radius: 4px;
    }
    .portal-tools-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .portal-tools-icon-badge.red { background: #fee2e2; color: #dc2626; }
    .portal-tools-icon-badge.emerald { background: #d1fae5; color: #059669; }
    .portal-tools-icon-badge.rose { background: #ffe4e6; color: #e11d48; }
    .portal-tools-icon-badge.indigo { background: #e0e7ff; color: #4f46e5; }
    .portal-tools-icon-badge.amber { background: #fef3c7; color: #d97706; }
    .portal-tools-icon-badge.blue { background: #dbeafe; color: #2563eb; }

    /* 3. Charts & Breakdown Grid */
    .portal-tools-grid-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
    .portal-tools-grid-3col {
        display: grid;
        grid-template-columns: 320px 320px 1fr;
        gap: 1.25rem;
    }
    @media (max-width: 1200px) {
        .portal-tools-grid-3col { grid-template-columns: 1fr 1fr; }
        .portal-tools-grid-2col { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .portal-tools-grid-3col { grid-template-columns: 1fr; }
    }

    .portal-tools-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .portal-tools-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
    }
    .portal-tools-card-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-tools-card-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* 4. Table Styles */
    .portal-tools-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.82rem;
    }
    .portal-tools-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 800;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 0.75rem 0.85rem;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
        white-space: nowrap;
    }
    .portal-tools-table th.num, .portal-tools-table td.num {
        text-align: right;
    }
    .portal-tools-table td {
        padding: 0.75rem 0.85rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-heading);
        vertical-align: middle;
    }
    .portal-tools-table tbody tr:hover td {
        background: #f8fafc;
    }
    .portal-tools-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .portal-tools-badge.ada {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .portal-tools-badge.tidak {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .portal-tools-badge.bagus {
        background: #e0e7ff;
        color: #3730a3;
        border: 1px solid #c7d2fe;
    }
    .portal-tools-badge.tidak-bagus {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .portal-tools-badge.neutral {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    /* Rank Badges */
    .portal-tools-rank {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 800;
    }
    .portal-tools-rank.top-1 { background: #fef08a; color: #854d0e; border: 1.5px solid #eab308; }
    .portal-tools-rank.top-2 { background: #e2e8f0; color: #334155; border: 1.5px solid #94a3b8; }
    .portal-tools-rank.top-3 { background: #fed7aa; color: #9a3412; border: 1.5px solid #f97316; }
    .portal-tools-rank.other { background: #f1f5f9; color: #64748b; }

    /* Lightbox Modal */
    .portal-tools-modal {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .portal-tools-modal.active {
        display: flex;
    }
    .portal-tools-modal-content {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: 650px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 1.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .portal-tools-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
    }
    .portal-tools-modal-title {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-heading);
    }
    .portal-tools-modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--text-muted);
        cursor: pointer;
    }
    .portal-tools-modal-close:hover {
        color: #d32f2f;
    }
</style>
@endpush

@php
    $toolsKpis = $toolsData['kpis'] ?? [];
    $totalSubs = $toolsKpis['total_submissions'] ?? 0;
    $totalAda = $toolsKpis['total_ada'] ?? 0;
    $percentAda = $toolsKpis['percent_ada'] ?? 0;
    $totalTidak = $toolsKpis['total_tidak'] ?? 0;
    $percentTidak = $toolsKpis['percent_tidak'] ?? 0;
    $totalBagus = $toolsKpis['total_bagus'] ?? 0;
    $percentBagus = $toolsKpis['percent_bagus'] ?? 0;
    $totalTidakBagus = $toolsKpis['total_tidak_bagus'] ?? 0;
    $percentTidakBagus = $toolsKpis['percent_tidak_bagus'] ?? 0;
    $uniqueStores = $toolsKpis['unique_stores'] ?? 0;
    $uniqueMitras = $toolsKpis['unique_mitras'] ?? 0;
    $totalDefectPhotos = $toolsKpis['total_defect_photos'] ?? 0;

    $chartDaily = $toolsData['chart']['daily'] ?? [];
    $donutKetersediaan = $toolsData['chart']['donut_ketersediaan'] ?? [];
    $donutKondisi = $toolsData['chart']['donut_kondisi'] ?? [];
    $toolsBreakdown = $toolsData['tools_breakdown'] ?? [];
    $activeEmployees = $toolsData['active_employees'] ?? [];
    $defectGallery = $toolsData['defect_gallery'] ?? [];
    $standardTools = $toolsData['standard_tools'] ?? [
        '1 Pcs panci susu',
        '1 Pcs mangkuk pengaduk',
        '1 Pcs Gunting',
        '2 set sendok garpu',
        '1 pcs centong sayur',
        '1 pcs capitan',
        '1 Pcs Pompa dispenser air (optional)',
        '1 Pcs Galon air',
        '1 Pcs Kompor portable + Gas',
        '1 pcs saringan / tirisan mie',
        '1 Pcs tray',
        '1 Gelas Takar',
        'Papercup & Garpu kecil (untuk pengunjung)',
    ];
@endphp

<div class="portal-tools-wrapper">

    {{-- 1. FILTER BAR --}}
    <div class="portal-tools-filter-bar">
        <div class="portal-tools-filter-header">
            <div class="portal-tools-filter-title">
                <i class="fa-solid fa-wrench"></i>
                <span>Filter & Monitoring Laporan Tools (Properti Free Taste)</span>
            </div>
            @if(!empty($defectGallery))
                <button type="button" class="portal-tools-btn-action" onclick="openDefectGalleryModal()">
                    <i class="fa-solid fa-camera-rotate" style="color: #ea580c;"></i>
                    <span>Foto Bukti Kerusakan ({{ count($defectGallery) }})</span>
                </button>
            @endif
        </div>

        <form method="GET" action="{{ route('portal.report.detail', ['code' => $template->code]) }}" class="portal-tools-filter-grid">
            <input type="hidden" name="tab" value="{{ request('tab', 'dashboard') }}">

            {{-- Periode Bulan & Tahun --}}
            <div class="portal-tools-field-group">
                <label class="portal-tools-field-label">Periode Laporan</label>
                <div style="display: flex; gap: 0.4rem;">
                    <select name="start_month" class="portal-tools-select" style="flex: 1;">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (request('start_month', $startMonth ?? date('n')) == $m) ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('M') }}
                            </option>
                        @endfor
                    </select>
                    <select name="start_year" class="portal-tools-select" style="width: 85px;">
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ (request('start_year', $startYear ?? date('Y')) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Filter Wilayah / Region --}}
            <div class="portal-tools-field-group">
                <label class="portal-tools-field-label">Wilayah (Region)</label>
                <select name="region" class="portal-tools-select">
                    <option value="">Semua Wilayah</option>
                    @foreach($regions as $reg)
                        <option value="{{ $reg }}" {{ request('region', $selectedRegion) == $reg ? 'selected' : '' }}>{{ $reg }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Daerah / Cabang --}}
            <div class="portal-tools-field-group">
                <label class="portal-tools-field-label">Daerah (Area)</label>
                <select name="area_id" class="portal-tools-select">
                    <option value="">Semua Daerah</option>
                    @foreach($areas as $ar)
                        <option value="{{ $ar->name ?? $ar->id }}" {{ (request('area_id', $selectedAreaId) == ($ar->name ?? $ar->id)) ? 'selected' : '' }}>
                            {{ $ar->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Toko / Outlet --}}
            <div class="portal-tools-field-group">
                <label class="portal-tools-field-label">Toko / Outlet</label>
                <select name="location_id" class="portal-tools-select">
                    <option value="">Semua Toko</option>
                    @foreach($workLocations as $loc)
                        <option value="{{ $loc->name ?? $loc->id }}" {{ (request('location_id', $selectedLocationId) == ($loc->name ?? $loc->id)) ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search & Action Buttons --}}
            <div class="portal-tools-field-group">
                <label class="portal-tools-field-label">Pencarian Petugas / Toko</label>
                <input type="text" name="q" class="portal-tools-input" placeholder="Cari nama mitra, toko, alat..." value="{{ request('q', $search) }}">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="portal-tools-btn-submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Terapkan</span>
                </button>
                <a href="{{ route('portal.report.detail', ['code' => $template->code]) }}" class="portal-tools-btn-reset">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 2. GRID 4 KPI UTAMA (PERSENTASE KETERSEDIAAN & KONDISI) --}}
    <div class="portal-tools-kpi-grid">
        {{-- Card 1: Total Laporan Masuk --}}
        <div class="portal-tools-kpi-card">
            <div class="portal-tools-kpi-info">
                <div class="portal-tools-kpi-label">Total Laporan Masuk</div>
                <div class="portal-tools-kpi-val">
                    {{ number_format($totalSubs, 0, ',', '.') }}
                    <span class="portal-tools-kpi-unit">Inspeksi</span>
                </div>
                <div class="portal-tools-kpi-sub">
                    <i class="fa-solid fa-store" style="color: #2563eb;"></i>
                    <span>{{ $uniqueStores }} Toko &bull; {{ $uniqueMitras }} Petugas SPG</span>
                </div>
            </div>
            <div class="portal-tools-icon-badge red">
                <i class="fa-solid fa-toolbox"></i>
            </div>
        </div>

        {{-- Card 2: Ketersediaan Tools (ADA) --}}
        <div class="portal-tools-kpi-card">
            <div class="portal-tools-kpi-info">
                <div class="portal-tools-kpi-label">Status Ketersediaan (ADA)</div>
                <div class="portal-tools-kpi-val" style="color: #059669;">
                    {{ $percentAda }}%
                    <span class="portal-tools-kpi-unit">({{ number_format($totalAda, 0, ',', '.') }} Alat)</span>
                </div>
                <div class="portal-tools-kpi-progress">
                    <div class="portal-tools-kpi-progress-bar" style="width: {{ min(100, $percentAda) }}%; background: #10b981;"></div>
                </div>
                <div class="portal-tools-kpi-sub">
                    <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
                    <span>Fisik alat tersedia lengkap di lokasi</span>
                </div>
            </div>
            <div class="portal-tools-icon-badge emerald">
                <i class="fa-solid fa-check-double"></i>
            </div>
        </div>

        {{-- Card 3: Alat Tidak Tersedia (TIDAK) --}}
        <div class="portal-tools-kpi-card">
            <div class="portal-tools-kpi-info">
                <div class="portal-tools-kpi-label">Belum Ada / Hilang (TIDAK)</div>
                <div class="portal-tools-kpi-val" style="color: #dc2626;">
                    {{ $percentTidak }}%
                    <span class="portal-tools-kpi-unit">({{ number_format($totalTidak, 0, ',', '.') }} Alat)</span>
                </div>
                <div class="portal-tools-kpi-progress">
                    <div class="portal-tools-kpi-progress-bar" style="width: {{ min(100, $percentTidak) }}%; background: #ef4444;"></div>
                </div>
                <div class="portal-tools-kpi-sub">
                    <i class="fa-solid fa-circle-xmark" style="color: #ef4444;"></i>
                    <span>Perlu pengadaan / distribusi ulang</span>
                </div>
            </div>
            <div class="portal-tools-icon-badge rose">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 4: Kondisi Fisik Tools (BAGUS vs TIDAK BAGUS) --}}
        <div class="portal-tools-kpi-card">
            <div class="portal-tools-kpi-info">
                <div class="portal-tools-kpi-label">Kondisi Alat (BAGUS / LAYAK)</div>
                <div class="portal-tools-kpi-val" style="color: #4f46e5;">
                    {{ $percentBagus }}%
                    <span class="portal-tools-kpi-unit">({{ number_format($totalBagus, 0, ',', '.') }} Bagus)</span>
                </div>
                <div class="portal-tools-kpi-sub" style="color: {{ $totalTidakBagus > 0 ? '#d97706' : '#10b981' }}; font-weight: 700;">
                    @if($totalTidakBagus > 0)
                        <i class="fa-solid fa-triangle-exclamation" style="color: #d97706;"></i>
                        <span>{{ number_format($totalTidakBagus, 0, ',', '.') }} alat rusak ({{ $percentTidakBagus }}%)</span>
                        @if($totalDefectPhotos > 0)
                            &bull; <a href="javascript:void(0)" onclick="openDefectGalleryModal()" style="color: #ea580c; text-decoration: underline; font-weight: 800;">Lihat {{ $totalDefectPhotos }} Foto Rusak</a>
                        @endif
                    @else
                        <i class="fa-solid fa-shield-check" style="color: #10b981;"></i>
                        <span>100% Alat dalam kondisi prima</span>
                    @endif
                </div>
            </div>
            <div class="portal-tools-icon-badge indigo" style="cursor: pointer;" onclick="openDefectGalleryModal()" title="Klik untuk lihat galeri alat rusak">
                <i class="fa-solid fa-thumbs-up"></i>
            </div>
        </div>
    </div>

    {{-- 3. VISUAL CHARTS ANALYTICS (2 DONUT + 1 AREA TREN) --}}
    <div class="portal-tools-grid-3col">
        {{-- Donut Chart 1: Ketersediaan Tools --}}
        <div class="portal-tools-card">
            <div class="portal-tools-card-header">
                <div>
                    <div class="portal-tools-card-title">
                        <i class="fa-solid fa-chart-pie" style="color: #10b981;"></i>
                        <span>Ketersediaan Tools</span>
                    </div>
                    <div class="portal-tools-card-sub">Rasio Fisik Tersedia (ADA) vs Tidak</div>
                </div>
            </div>
            <div style="position: relative; height: 260px;">
                <div id="chartDonutKetersediaan"></div>
            </div>
            <div style="display: flex; justify-content: space-around; padding-top: 0.5rem; border-top: 1px dashed var(--border-color); font-size: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
                    <span style="font-weight: 700;">ADA: {{ $percentAda }}% ({{ $totalAda }})</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span>
                    <span style="font-weight: 700;">TIDAK: {{ $percentTidak }}% ({{ $totalTidak }})</span>
                </div>
            </div>
        </div>

        {{-- Donut Chart 2: Kondisi Fisik Tools --}}
        <div class="portal-tools-card">
            <div class="portal-tools-card-header">
                <div>
                    <div class="portal-tools-card-title">
                        <i class="fa-solid fa-shield-heart" style="color: #4f46e5;"></i>
                        <span>Kondisi Fisik Tools</span>
                    </div>
                    <div class="portal-tools-card-sub">Rasio Kondisi BAGUS vs Rusak/Cacat</div>
                </div>
            </div>
            <div style="position: relative; height: 260px;">
                <div id="chartDonutKondisi"></div>
            </div>
            <div style="display: flex; justify-content: space-around; padding-top: 0.5rem; border-top: 1px dashed var(--border-color); font-size: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #4f46e5;"></span>
                    <span style="font-weight: 700;">BAGUS: {{ $percentBagus }}% ({{ $totalBagus }})</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
                    <span style="font-weight: 700;">RUSAK: {{ $percentTidakBagus }}% ({{ $totalTidakBagus }})</span>
                </div>
            </div>
        </div>

        {{-- Area Chart: Tren Laporan Harian --}}
        <div class="portal-tools-card">
            <div class="portal-tools-card-header">
                <div>
                    <div class="portal-tools-card-title">
                        <i class="fa-solid fa-chart-line" style="color: #d32f2f;"></i>
                        <span>Tren Aktivitas Pelaporan Tools</span>
                    </div>
                    <div class="portal-tools-card-sub">Grafik volume inspeksi harian sepanjang periode</div>
                </div>
            </div>
            <div style="position: relative; height: 290px;">
                <div id="chartDailyTrend"></div>
            </div>
        </div>
    </div>

    {{-- 4. GRID 2 KOLOM: BREAKDOWN PER ITEM TOOLS & LEADERBOARD KARYAWAN TERAKTIF --}}
    <div class="portal-tools-grid-2col">
        {{-- Tabel 1: Status Ketersediaan & Kondisi per 13 Item Tools Standar --}}
        <div class="portal-tools-card">
            <div class="portal-tools-card-header">
                <div>
                    <div class="portal-tools-card-title">
                        <i class="fa-solid fa-list-check" style="color: #d32f2f;"></i>
                        <span>Status per Item Tools (13 Properti Standar)</span>
                    </div>
                    <div class="portal-tools-card-sub">Rekap ketersediaan dan tingkat kelayakan kondisi masing-masing alat</div>
                </div>
            </div>
            <div style="overflow-x: auto; max-height: 480px;">
                <table class="portal-tools-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>Nama Tools / Properti</th>
                            <th class="num">Total Cek</th>
                            <th class="num" style="color: #059669;">ADA (%)</th>
                            <th class="num" style="color: #dc2626;">TIDAK (%)</th>
                            <th class="num" style="color: #4f46e5;">BAGUS</th>
                            <th class="num" style="color: #d97706;">RUSAK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($toolsBreakdown as $idx => $tItem)
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 700;">{{ $idx + 1 }}</td>
                                <td>
                                    <div style="font-weight: 800; color: var(--text-heading);">{{ $tItem['name'] }}</div>
                                    @if(!empty($tItem['photos_defect']))
                                        <div style="margin-top: 4px;">
                                            <button type="button" class="portal-tools-btn-action" style="padding: 2px 6px; font-size: 0.68rem; background: #fffbeb; color: #b45309; border-color: #fde68a;" onclick="openToolDefectModal('{{ addslashes($tItem['name']) }}', {{ json_encode($tItem['photos_defect']) }})">
                                                <i class="fa-solid fa-camera"></i> {{ count($tItem['photos_defect']) }} Bukti Rusak
                                            </button>
                                        </div>
                                    @endif
                                </td>
                                <td class="num" style="font-weight: 700;">{{ $tItem['total_inspected'] }}</td>
                                <td class="num">
                                    <span style="font-weight: 800; color: #059669;">{{ $tItem['ada'] }}</span>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">({{ $tItem['percent_ada'] }}%)</span>
                                </td>
                                <td class="num">
                                    <span style="font-weight: 800; color: {{ $tItem['tidak'] > 0 ? '#dc2626' : 'var(--text-muted)' }};">{{ $tItem['tidak'] }}</span>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">({{ $tItem['percent_tidak'] }}%)</span>
                                </td>
                                <td class="num" style="font-weight: 700; color: #4f46e5;">{{ $tItem['bagus'] }}</td>
                                <td class="num" style="font-weight: 800; color: {{ $tItem['tidak_bagus'] > 0 ? '#d97706' : 'var(--text-muted)' }};">
                                    {{ $tItem['tidak_bagus'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    Belum ada data rekapan tools pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 2: Leaderboard Karyawan dengan Laporan Teraktif --}}
        <div class="portal-tools-card">
            <div class="portal-tools-card-header">
                <div>
                    <div class="portal-tools-card-title">
                        <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i>
                        <span>Karyawan / SPG Pelapor Teraktif</span>
                    </div>
                    <div class="portal-tools-card-sub">Peringkat petugas lapangan dengan frekuensi pelaporan tools tertinggi</div>
                </div>
            </div>
            <div style="overflow-x: auto; max-height: 480px;">
                <table class="portal-tools-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">Rank</th>
                            <th>Petugas (SPG/MD)</th>
                            <th>Area / Cabang</th>
                            <th class="num">Laporan</th>
                            <th class="num" style="color: #059669;">Alat ADA</th>
                            <th class="num" style="color: #d97706;">Rusak</th>
                            <th style="width: 90px; text-align: right;">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeEmployees as $idx => $emp)
                            <tr>
                                <td style="text-align: center;">
                                    <span class="portal-tools-rank {{ $idx === 0 ? 'top-1' : ($idx === 1 ? 'top-2' : ($idx === 2 ? 'top-3' : 'other')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: var(--text-heading);">{{ $emp['name'] }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">NIK: {{ $emp['nik'] }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-heading);">{{ $emp['branch_name'] }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ Str::limit($emp['store_name'], 18) }}</div>
                                </td>
                                <td class="num" style="font-weight: 900; color: #d32f2f; font-size: 0.95rem;">
                                    {{ $emp['total_reports'] }}
                                </td>
                                <td class="num" style="font-weight: 700; color: #059669;">
                                    {{ $emp['count_ada'] }}
                                </td>
                                <td class="num" style="font-weight: 700; color: {{ $emp['count_tidak_bagus'] > 0 ? '#d97706' : 'var(--text-muted)' }};">
                                    {{ $emp['count_tidak_bagus'] }}
                                </td>
                                <td style="text-align: right; font-size: 0.72rem; color: var(--text-muted); white-space: nowrap;">
                                    {{ $emp['last_submitted_at'] ? \Carbon\Carbon::parse($emp['last_submitted_at'])->format('d M H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    Belum ada data aktivitas petugas lapangan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 5. TABEL RINCIAN DATA SUBMISSION LAPORAN --}}
    <div class="portal-tools-card">
        <div class="portal-tools-card-header">
            <div>
                <div class="portal-tools-card-title">
                    <i class="fa-solid fa-table-list" style="color: #2563eb;"></i>
                    <span>Rincian Data Submission Laporan Tools</span>
                </div>
                <div class="portal-tools-card-sub">Menampilkan {{ $submissions->total() }} baris rekaman data submisi lapangan</div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="portal-tools-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th>No. Laporan</th>
                        <th>Tanggal & Waktu</th>
                        <th>Petugas (SPG/MD)</th>
                        <th>Toko / Outlet</th>
                        <th>Nama Tools</th>
                        <th style="text-align: center;">Ketersediaan</th>
                        <th style="text-align: center;">Kondisi</th>
                        <th style="text-align: center;">Bukti Foto</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">GPS</th>
                        <th style="text-align: center; width: 70px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $sIdx => $sub)
                        @php
                            $subToolName = null;
                            $subAvailability = 'ADA';
                            $subCondition = 'BAGUS';
                            $subPhoto = null;

                            foreach ($sub->values as $v) {
                                $fName = strtolower(trim($v->field_name ?? ''));
                                $fLabel = strtolower(trim($v->formField?->field_label ?? ($v->formField?->label ?? '')));
                                $valTxt = trim((string)($v->value_text ?? ''));

                                if ($fName === 'foto_tools' || $v->field_type === 'camera_photo' || str_contains($fName, 'foto') || str_contains($fLabel, 'foto') || !empty($v->media_url)) {
                                    $subPhoto = $v->media_url ?: ($valTxt ?: (is_array($v->value_json) ? ($v->value_json[0] ?? null) : $subPhoto));
                                } elseif ($fName === 'kondisi_tools' || str_contains($fName, 'kondisi') || str_contains($fLabel, 'kondisi')) {
                                    $subCondition = strtoupper($valTxt) === 'TIDAK BAGUS' ? 'TIDAK BAGUS' : 'BAGUS';
                                } elseif ($fName === 'status_ketersediaan' || str_contains($fName, 'ketersediaan') || str_contains($fLabel, 'ketersediaan')) {
                                    $subAvailability = strtoupper($valTxt) === 'TIDAK' ? 'TIDAK' : 'ADA';
                                } elseif ($fName === 'nama_tools' || $fName === 'pilih_tools' || $fName === 'tools' || $fName === 'alat' || str_contains($fLabel, 'pilih tools') || str_contains($fLabel, 'properti')) {
                                    $subToolName = $valTxt ?: $subToolName;
                                }
                            }

                            if (!$subToolName) {
                                foreach ($sub->values as $v) {
                                    $valTxt = trim((string)($v->value_text ?? ''));
                                    if ($valTxt) {
                                        foreach ($standardTools as $st) {
                                            if (strcasecmp($st, $valTxt) === 0 || str_contains(strtolower($valTxt), strtolower($st)) || str_contains(strtolower($st), strtolower($valTxt))) {
                                                $subToolName = $st;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }

                            if ($subToolName) {
                                foreach ($standardTools as $st) {
                                    if (strcasecmp($st, $subToolName) === 0 || str_contains(strtolower($st), strtolower($subToolName)) || str_contains(strtolower($subToolName), strtolower($st))) {
                                        $subToolName = $st;
                                        break;
                                    }
                                }
                            }

                            if (!$subToolName) {
                                $subToolName = '1 Pcs panci susu';
                            }

                            if ($subAvailability === 'TIDAK') {
                                $subCondition = '-';
                            }

                            $resolvedPhoto = null;
                            if ($subPhoto) {
                                $cleanP = trim((string)$subPhoto);
                                if (str_starts_with($cleanP, '[') && str_ends_with($cleanP, ']')) {
                                    $dec = json_decode($cleanP, true);
                                    if (is_array($dec) && !empty($dec)) {
                                        $cleanP = trim((string)($dec[0] ?? ''));
                                    }
                                }
                                if (!empty($cleanP) && !str_starts_with($cleanP, '/data/user/')) {
                                    if (str_starts_with($cleanP, 'http://') || str_starts_with($cleanP, 'https://')) {
                                        $resolvedPhoto = str_replace(['/storage/storage/', 'esa-solution.id'], ['/storage/', 'esa-solutions.id'], $cleanP);
                                    } else {
                                        if (str_starts_with($cleanP, 'storage/')) {
                                            $cleanP = substr($cleanP, 8);
                                        } elseif (str_starts_with($cleanP, '/storage/')) {
                                            $cleanP = substr($cleanP, 9);
                                        } elseif (str_starts_with($cleanP, 'public/')) {
                                            $cleanP = substr($cleanP, 7);
                                        }
                                        $resolvedPhoto = asset('storage/' . ltrim($cleanP, '/'));
                                    }
                                }
                            }
                            if (!$resolvedPhoto) {
                                $mSub = glob(storage_path("app/public/reports/*/report_{$sub->report_template_id}_{$sub->id}_*.*"));
                                if (empty($mSub)) {
                                    $mSub = glob(storage_path("app/public/reports/*/report_{$sub->id}_*.*"));
                                }
                                if (!empty($mSub)) {
                                    $relSub = str_replace(storage_path('app/public/'), '', $mSub[0]);
                                    $relSub = str_replace('\\', '/', $relSub);
                                    $resolvedPhoto = asset('storage/' . ltrim($relSub, '/'));
                                }
                            }
                        @endphp
                        <tr>
                            <td style="text-align: center; color: var(--text-muted); font-weight: 700;">
                                {{ $submissions->firstItem() + $sIdx }}
                            </td>
                            <td>
                                <div style="font-weight: 800; font-family: monospace; color: #2563eb;">
                                    {{ $sub->submission_code ?: $sub->code }}
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 700; color: var(--text-heading);">
                                    {{ ($sub->submitted_at ?? $sub->created_at)->format('d M Y') }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ ($sub->submitted_at ?? $sub->created_at)->format('H:i:s') }} WIB
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--text-heading);">
                                    {{ $sub->employee?->full_name ?? ($sub->employee?->name ?? 'Petugas') }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                    NIK: {{ $sub->employee?->employee_no ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">
                                    {{ $sub->workLocation?->name ?? ($sub->store_name ?? '-') }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $sub->workLocation?->branch?->name ?? ($sub->employee?->branch?->name ?? '-') }}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">
                                    {{ $subToolName }}
                                </div>
                            </td>
                            <td style="text-align: center;">
                                @if($subAvailability === 'ADA')
                                    <span class="portal-tools-badge ada">
                                        <i class="fa-solid fa-check"></i> ADA
                                    </span>
                                @else
                                    <span class="portal-tools-badge tidak">
                                        <i class="fa-solid fa-xmark"></i> TIDAK
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($subCondition === 'BAGUS')
                                    <span class="portal-tools-badge bagus">
                                        <i class="fa-solid fa-thumbs-up"></i> BAGUS
                                    </span>
                                @elseif($subCondition === 'TIDAK BAGUS')
                                    <span class="portal-tools-badge tidak-bagus">
                                        <i class="fa-solid fa-triangle-exclamation"></i> RUSAK
                                    </span>
                                @else
                                    <span class="portal-tools-badge neutral">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($resolvedPhoto)
                                    <button type="button" class="portal-tools-btn-action" onclick="openLightbox('{{ $resolvedPhoto }}', '{{ addslashes($subToolName) }}', '{{ addslashes($sub->workLocation?->name ?? $sub->store_name ?? '') }}')" style="padding: 3px 8px; font-size: 0.72rem; background: #fff7ed; color: #c2410c; border-color: #ffedd5;">
                                        <i class="fa-solid fa-camera"></i> Foto
                                    </button>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <span class="portal-tools-badge bagus" style="background: #dcfce7; color: #15803d; border-color: #86efac;">
                                    <i class="fa-solid fa-circle-check"></i> Diterima
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if($sub->is_within_radius ?? true)
                                    <span style="color: #059669; font-weight: 700; font-size: 0.75rem;">
                                        <i class="fa-solid fa-location-dot"></i> Valid
                                    </span>
                                @else
                                    <span style="color: #ea580c; font-weight: 700; font-size: 0.75rem;">
                                        <i class="fa-solid fa-location-arrow"></i> Di luar
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id]) }}" class="portal-tools-btn-action" style="padding: 3px 8px; font-size: 0.72rem;">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                Belum ada data submission laporan tools untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($submissions->hasPages())
            <div style="padding-top: 1rem; border-top: 1px solid var(--border-color);">
                {{ $submissions->links('portal.pagination') }}
            </div>
        @endif
    </div>

</div>

{{-- 6. MODAL LIGHTBOX FOTO WATERMARK --}}
<div id="toolsLightboxModal" class="portal-tools-modal" onclick="closeLightbox(event)">
    <div class="portal-tools-modal-content" style="max-width: 550px; background: #0f172a; color: #fff; padding: 1.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #334155; padding-bottom: 0.75rem;">
            <div>
                <div id="lightboxToolTitle" style="font-weight: 800; font-size: 1rem; color: #f8fafc;">Foto Bukti Fisik Tools</div>
                <div id="lightboxStoreSubtitle" style="font-size: 0.75rem; color: #94a3b8;">-</div>
            </div>
            <button type="button" class="portal-tools-modal-close" style="color: #94a3b8;" onclick="closeLightbox(null, true)">&times;</button>
        </div>
        <div style="text-align: center; margin: 0.5rem 0;">
            <img id="lightboxImg" src="" alt="Bukti Fisik Tools" style="width: 100%; max-height: 480px; object-fit: contain; border-radius: 12px; border: 1px solid #334155;">
        </div>
    </div>
</div>

{{-- 7. MODAL GALERI FOTO RUSAK --}}
<div id="defectGalleryModal" class="portal-tools-modal" onclick="closeDefectGalleryModal(event)">
    <div class="portal-tools-modal-content" style="max-width: 850px;">
        <div class="portal-tools-modal-header">
            <div class="portal-tools-modal-title">
                <i class="fa-solid fa-camera-rotate" style="color: #ea580c;"></i>
                <span id="defectGalleryTitle">Galeri Foto Bukti Kerusakan Tools</span>
            </div>
            <button type="button" class="portal-tools-modal-close" onclick="closeDefectGalleryModal(null, true)">&times;</button>
        </div>
        <div id="defectGalleryBody" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; max-height: 60vh; overflow-y: auto; padding: 0.5rem;">
            @php
                $placeholderSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'><rect fill='%23f8fafc' width='400' height='300'/><path fill='%23cbd5e1' d='M160 110a20 20 0 1 1-40 0 20 20 0 0 1 40 0zm-80 90l60-80 40 50 50-65 70 95H80z'/><text fill='%2394a3b8' font-family='sans-serif' font-size='14' font-weight='700' x='50%' y='82%' text-anchor='middle'>Foto Tidak Tersedia</text></svg>";
            @endphp
            @forelse($defectGallery as $dg)
                @php
                    $dUrl = $dg['url'] ?? '';
                    if ($dUrl && !str_starts_with($dUrl, 'http://') && !str_starts_with($dUrl, 'https://')) {
                        if (str_starts_with($dUrl, 'storage/')) {
                            $dUrl = substr($dUrl, 8);
                        } elseif (str_starts_with($dUrl, '/storage/')) {
                            $dUrl = substr($dUrl, 9);
                        } elseif (str_starts_with($dUrl, 'public/')) {
                            $dUrl = substr($dUrl, 7);
                        }
                        $dUrl = asset('storage/' . ltrim($dUrl, '/'));
                    }
                @endphp
                <div style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: #f8fafc;">
                    <img src="{{ $dUrl }}" alt="{{ $dg['tool'] }}" onerror="this.onerror=null; this.src='{{ $placeholderSvg }}';" onclick="openLightbox('{{ $dUrl }}', '{{ addslashes($dg['tool']) }}', '{{ addslashes($dg['store']) }}')" style="width: 100%; height: 160px; object-fit: cover; cursor: pointer;">
                    <div style="padding: 0.75rem;">
                        <div style="font-weight: 800; color: var(--text-heading); font-size: 0.85rem;">{{ $dg['tool'] }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-store"></i> {{ $dg['store'] }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">{{ $dg['mitra'] }} &bull; {{ $dg['time'] }}</div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 2.5rem; color: var(--text-muted);">
                    <i class="fa-solid fa-circle-check" style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;"></i>
                    <div>Tidak ada bukti kerusakan tools yang tercatat pada periode ini.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initToolsCharts();
    });

    function initToolsCharts() {
        if (typeof ApexCharts === 'undefined') return;

        // 1. Donut Chart Ketersediaan Tools (ADA vs TIDAK)
        var optionsKetersediaan = {
            series: [{{ $totalAda }}, {{ $totalTidak }}],
            labels: ['Alat Tersedia (ADA)', 'Tidak Ada (TIDAK)'],
            colors: ['#10b981', '#ef4444'],
            chart: {
                type: 'donut',
                height: 240,
                fontFamily: 'Outfit, sans-serif'
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + "%";
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: { show: true, fontSize: '12px', fontWeight: 700 },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 900,
                                formatter: function (val) {
                                    return Number(val).toLocaleString('id-ID');
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Cek',
                                fontSize: '11px',
                                fontWeight: 700,
                                color: '#64748b',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('id-ID') + " Alat";
                    }
                }
            }
        };
        var chartKetersediaan = new ApexCharts(document.querySelector("#chartDonutKetersediaan"), optionsKetersediaan);
        chartKetersediaan.render();

        // 2. Donut Chart Kondisi Tools (BAGUS vs TIDAK BAGUS)
        var optionsKondisi = {
            series: [{{ $totalBagus }}, {{ $totalTidakBagus }}],
            labels: ['Kondisi BAGUS', 'TIDAK BAGUS (Rusak)'],
            colors: ['#4f46e5', '#f59e0b'],
            chart: {
                type: 'donut',
                height: 240,
                fontFamily: 'Outfit, sans-serif'
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + "%";
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: { show: true, fontSize: '12px', fontWeight: 700 },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 900,
                                formatter: function (val) {
                                    return Number(val).toLocaleString('id-ID');
                                }
                            },
                            total: {
                                show: true,
                                label: 'Fisik Ada',
                                fontSize: '11px',
                                fontWeight: 700,
                                color: '#64748b',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('id-ID') + " Alat";
                    }
                }
            }
        };
        var chartKondisi = new ApexCharts(document.querySelector("#chartDonutKondisi"), optionsKondisi);
        chartKondisi.render();

        // 3. Area Chart Tren Pelaporan Harian
        var dailyLabels = {!! json_encode($chartDaily['labels'] ?? []) !!};
        var dailyTotals = {!! json_encode($chartDaily['totals'] ?? []) !!};
        var dailyAdas = {!! json_encode($chartDaily['adas'] ?? []) !!};

        var optionsDaily = {
            series: [{
                name: 'Total Laporan',
                data: dailyTotals
            }, {
                name: 'Alat Tersedia (ADA)',
                data: dailyAdas
            }],
            chart: {
                type: 'area',
                height: 270,
                toolbar: { show: false },
                fontFamily: 'Outfit, sans-serif'
            },
            colors: ['#d32f2f', '#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: [3, 2] },
            xaxis: {
                categories: dailyLabels,
                labels: { rotate: -45, style: { fontSize: '11px', fontWeight: 600 } }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return Math.round(val);
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontWeight: 700,
                fontSize: '12px'
            },
            tooltip: {
                shared: true,
                intersect: false
            }
        };
        var chartDaily = new ApexCharts(document.querySelector("#chartDailyTrend"), optionsDaily);
        chartDaily.render();
    }

    var placeholderSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'><rect fill='%23f8fafc' width='400' height='300'/><path fill='%23cbd5e1' d='M160 110a20 20 0 1 1-40 0 20 20 0 0 1 40 0zm-80 90l60-80 40 50 50-65 70 95H80z'/><text fill='%2394a3b8' font-family='sans-serif' font-size='14' font-weight='700' x='50%' y='82%' text-anchor='middle'>Foto Tidak Tersedia</text></svg>";

    function resolveClientPhotoUrl(raw) {
        if (!raw) return '';
        var clean = String(raw).trim();
        if (clean.startsWith('http://') || clean.startsWith('https://')) {
            return clean.replace('/storage/storage/', '/storage/').replace('esa-solution.id', 'esa-solutions.id');
        }
        if (clean.startsWith('/storage/')) {
            return clean;
        }
        if (clean.startsWith('storage/')) {
            return '/' + clean;
        }
        return '/storage/' + clean.replace(/^\/+/, '');
    }

    // Lightbox handlers
    function openLightbox(url, title, store) {
        var modal = document.getElementById('toolsLightboxModal');
        var img = document.getElementById('lightboxImg');
        var tTitle = document.getElementById('lightboxToolTitle');
        var sSub = document.getElementById('lightboxStoreSubtitle');
        if (modal && img) {
            var safeUrl = resolveClientPhotoUrl(url);
            img.src = safeUrl;
            img.onerror = function() {
                this.onerror = null;
                this.src = placeholderSvg;
            };
            if (tTitle) tTitle.innerText = title || 'Foto Bukti Fisik Tools';
            if (sSub) sSub.innerText = store ? ('Lokasi Toko: ' + store) : '-';
            modal.classList.add('active');
        }
    }

    function closeLightbox(event, force) {
        if (force || (event && event.target && event.target.id === 'toolsLightboxModal')) {
            var modal = document.getElementById('toolsLightboxModal');
            if (modal) modal.classList.remove('active');
        }
    }

    function openDefectGalleryModal() {
        var modal = document.getElementById('defectGalleryModal');
        var titleEl = document.getElementById('defectGalleryTitle');
        if (titleEl) {
            titleEl.innerText = 'Galeri Foto Bukti Kerusakan Tools (' + defectGalleryData.length + ')';
        }
        if (modal) modal.classList.add('active');
    }

    function closeDefectGalleryModal(event, force) {
        if (force || (event && event.target && event.target.id === 'defectGalleryModal')) {
            var modal = document.getElementById('defectGalleryModal');
            if (modal) modal.classList.remove('active');
        }
    }

    // Filter defect modal per tool
    var defectGalleryData = {!! json_encode($defectGallery ?? []) !!};
    function openToolDefectModal(toolName, directPhotos) {
        var modal = document.getElementById('defectGalleryModal');
        var titleEl = document.getElementById('defectGalleryTitle');
        var bodyEl = document.getElementById('defectGalleryBody');

        if (!modal || !bodyEl) return;

        var items = (directPhotos && Array.isArray(directPhotos) && directPhotos.length > 0)
            ? directPhotos
            : defectGalleryData.filter(function(d) {
                var dTool = (d.tool || '').toLowerCase();
                var targetTool = (toolName || '').toLowerCase();
                return dTool.indexOf(targetTool) !== -1 || targetTool.indexOf(dTool) !== -1;
            });

        if (titleEl) {
            titleEl.innerText = 'Bukti Kerusakan: ' + toolName + ' (' + items.length + ')';
        }

        if (items.length === 0) {
            bodyEl.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-muted);"><i class="fa-solid fa-circle-info" style="margin-right: 6px;"></i>Tidak ada foto kerusakan yang tercatat untuk alat ini.</div>';
        } else {
            bodyEl.innerHTML = items.map(function(dg) {
                var safeUrl = resolveClientPhotoUrl(dg.url);
                var safeTool = (dg.tool || toolName || 'Tools').replace(/'/g, "\\'");
                var safeStore = (dg.store || '').replace(/'/g, "\\'");
                var safeMitra = dg.mitra || '-';
                var safeTime = dg.time || '-';

                return `
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: #f8fafc;">
                        <img src="${safeUrl}" alt="${safeTool}" onerror="this.onerror=null; this.src=placeholderSvg;" onclick="openLightbox('${safeUrl}', '${safeTool}', '${safeStore}')" style="width: 100%; height: 160px; object-fit: cover; cursor: pointer;">
                        <div style="padding: 0.75rem;">
                            <div style="font-weight: 800; color: var(--text-heading); font-size: 0.85rem;">${dg.tool || toolName}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-store"></i> ${dg.store}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">${safeMitra} &bull; ${safeTime}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        modal.classList.add('active');
    }
</script>
@endpush
