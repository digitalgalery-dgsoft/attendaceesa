{{-- 
    WINGS SURYA - DASHBOARD LAPORAN FREE TASTE / SAMPLING (EVENT MBR)
    Identitas Visual: 100% Selaras dengan Principal Portal (Native Brand Tokens, Clean White Cards, Soft Badges)
    100% Data Riil Submisi Masuk (Tanpa Dummy)
--}}

@push('styles')
<style>
    /* Wrapper Utama */
    .portal-freetaste-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
        width: 100%;
        max-width: 100%;
    }

    /* 1. Filter Bar Portal Style */
    .portal-freetaste-filter-bar {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.15rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .portal-freetaste-filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }
    .portal-freetaste-filter-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-freetaste-filter-title i {
        color: var(--brand-primary);
    }
    .portal-freetaste-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.85rem;
        align-items: flex-end;
    }
    .portal-freetaste-field-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .portal-freetaste-field-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .portal-freetaste-input, .portal-freetaste-select {
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
    .portal-freetaste-input:focus, .portal-freetaste-select:focus {
        background: #ffffff;
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 3px var(--brand-glow);
    }
    .portal-freetaste-btn-submit {
        background: var(--brand-gradient);
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
        box-shadow: 0 2px 8px var(--brand-glow);
        transition: all 0.2s ease;
    }
    .portal-freetaste-btn-submit:hover {
        opacity: 0.92;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--brand-glow);
    }
    .portal-freetaste-btn-reset {
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
    .portal-freetaste-btn-reset:hover {
        background: #e2e8f0;
        color: var(--text-heading);
    }
    .portal-freetaste-btn-export {
        background: #107c41;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 0.6rem 1.15rem;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        box-shadow: 0 2px 8px rgba(16, 124, 65, 0.25);
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .portal-freetaste-btn-export:hover {
        background: #0b6333;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 124, 65, 0.35);
    }

    /* 2. Grid KPI Cards */
    .portal-freetaste-kpi-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.15rem;
    }
    .portal-freetaste-kpi-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.15rem;
    }
    @media (max-width: 1200px) {
        .portal-freetaste-kpi-grid-4 { grid-template-columns: repeat(2, 1fr); }
        .portal-freetaste-kpi-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .portal-freetaste-kpi-grid-4 { grid-template-columns: 1fr; }
        .portal-freetaste-kpi-grid-3 { grid-template-columns: 1fr; }
    }

    .portal-freetaste-kpi-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .portal-freetaste-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--border-hover);
    }
    .portal-freetaste-kpi-info {
        flex: 1;
        min-width: 0;
    }
    .portal-freetaste-kpi-label {
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 0.35rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .portal-freetaste-kpi-val {
        font-size: 1.65rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.15;
    }
    .portal-freetaste-kpi-unit {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-left: 2px;
    }
    .portal-freetaste-kpi-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    /* Icon Badges */
    .portal-freetaste-icon-badge {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .portal-freetaste-icon-badge.brand   { background: var(--brand-light); color: var(--brand-primary); }
    .portal-freetaste-icon-badge.emerald { background: #ecfdf5; color: #059669; }
    .portal-freetaste-icon-badge.amber   { background: #fef3c7; color: #d97706; }
    .portal-freetaste-icon-badge.blue    { background: #eff6ff; color: #2563eb; }
    .portal-freetaste-icon-badge.orange  { background: #fff7ed; color: #ea580c; }
    .portal-freetaste-icon-badge.purple  { background: #f5f3ff; color: #7c3aed; }
    .portal-freetaste-icon-badge.indigo  { background: #eef2ff; color: #4f46e5; }

    /* 3. Section Cards */
    .portal-freetaste-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.35rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }
    .portal-freetaste-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.15rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .portal-freetaste-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-freetaste-card-title i {
        color: var(--brand-primary);
    }
    .portal-freetaste-card-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
    }

    /* Chart Pill Switcher */
    .portal-freetaste-chart-switcher {
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        display: inline-flex;
        gap: 4px;
    }
    .portal-freetaste-switcher-btn {
        border: none;
        background: transparent;
        padding: 0.35rem 0.85rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .portal-freetaste-switcher-btn:hover {
        color: var(--text-heading);
    }
    .portal-freetaste-switcher-btn.active {
        background: var(--brand-primary);
        color: #ffffff;
        box-shadow: 0 2px 6px var(--brand-glow);
    }

    /* 4. Grid 2x2 Tables */
    .portal-freetaste-table-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 992px) {
        .portal-freetaste-table-grid { grid-template-columns: 1fr; }
    }

    /* Tables Native Portal Style */
    .portal-freetaste-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
    }
    .portal-freetaste-table thead th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 0.75rem 0.95rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .portal-freetaste-table thead th.num { text-align: right; }
    .portal-freetaste-table tbody td {
        padding: 0.75rem 0.95rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-body);
        vertical-align: middle;
    }
    .portal-freetaste-table tbody td.num { text-align: right; font-weight: 700; }
    .portal-freetaste-table tbody tr:last-child td {
        border-bottom: none;
    }
    .portal-freetaste-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Rank Badge */
    .portal-freetaste-rank-badge {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 800;
        background: #f1f5f9;
        color: var(--text-heading);
    }
    .portal-freetaste-rank-badge.top-1 { background: #fef3c7; color: #b45309; }
    .portal-freetaste-rank-badge.top-2 { background: #e2e8f0; color: #475569; }
    .portal-freetaste-rank-badge.top-3 { background: #ffedd5; color: #c2410c; }

    /* Action Buttons in Table */
    .portal-freetaste-btn-action {
        background: #f1f5f9;
        color: var(--brand-primary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 0.35rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease;
    }
    .portal-freetaste-btn-action:hover {
        background: var(--brand-light);
        border-color: var(--brand-primary);
    }

    /* Status Pills */
    .portal-freetaste-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
    }
    .portal-freetaste-pill.cup { background: #ecfdf5; color: #059669; }
    .portal-freetaste-pill.pcs { background: #eff6ff; color: #1d4ed8; }

    /* Modal Styling */
    .portal-freetaste-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }
    .portal-freetaste-modal-overlay.active {
        display: flex;
    }
    .portal-freetaste-modal-card {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        width: 100%;
        max-width: 820px;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: portalModalIn 0.2s ease-out;
    }
    .portal-freetaste-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
    }
    .portal-freetaste-modal-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-freetaste-modal-close {
        background: #f1f5f9;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .portal-freetaste-modal-close:hover {
        background: #fee2e2;
        color: #ef4444;
    }
    .portal-freetaste-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
    }

    /* Photo Grid inside Modal */
    .portal-freetaste-photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1rem;
    }
    .portal-freetaste-photo-card {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: var(--shadow-xs);
        transition: transform 0.2s ease;
    }
    .portal-freetaste-photo-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    .portal-freetaste-photo-thumb {
        width: 100%;
        height: 170px;
        object-fit: cover;
        cursor: pointer;
        background: #f8fafc;
        display: block;
    }
    .portal-freetaste-photo-info {
        padding: 0.75rem 0.85rem;
        font-size: 0.78rem;
    }
    .portal-freetaste-photo-meta {
        color: var(--text-muted);
        font-size: 0.72rem;
        margin-top: 0.2rem;
    }

    /* Lightbox */
    .portal-freetaste-lightbox {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.88);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        cursor: zoom-out;
    }
    .portal-freetaste-lightbox.active {
        display: flex;
    }
    .portal-freetaste-lightbox img {
        max-width: 92vw;
        max-height: 92vh;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }

    /* Empty state */
    .portal-freetaste-empty-state {
        text-align: center;
        padding: 2.5rem 1.5rem;
        color: var(--text-muted);
    }
    .portal-freetaste-empty-icon {
        font-size: 2.25rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
    }
</style>
@endpush

@php
    $freetasteKpis = $freetasteData['kpis'] ?? [];
    $totalDimasak = $freetasteKpis['total_dimasak'] ?? 0;
    $totalCup = $freetasteKpis['total_cup'] ?? 0;
    $cupPerPcs = $freetasteKpis['cup_per_pcs'] ?? 0;
    $totalStokAwal = $freetasteKpis['total_stok_awal'] ?? 0;
    $totalStokAkhir = $freetasteKpis['total_stok_akhir'] ?? 0;
    $uniqueSkus = $freetasteKpis['unique_skus'] ?? 0;
    $todayDimasak = $freetasteKpis['today_dimasak'] ?? 0;
    $todayCup = $freetasteKpis['today_cup'] ?? 0;
    $uniqueStores = $freetasteKpis['unique_stores'] ?? 0;
    $uniqueMitras = $freetasteKpis['unique_mitras'] ?? 0;

    $galleryPhotos = $freetasteData['gallery_photos'] ?? [];
@endphp

<div class="portal-freetaste-wrapper">

    @if(!empty($galleryPhotos))
    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 0.5rem;">
        <button type="button" class="portal-freetaste-btn-action" onclick="openFreetasteGalleryModal('all')">
            <i class="fa-solid fa-images"></i>
            <span>Galeri Foto Sampling ({{ count($galleryPhotos) }})</span>
        </button>
    </div>
    @endif

    {{-- 2. GRID 7 KPI UTAMA PORTAL STYLE --}}
    <div class="portal-freetaste-kpi-grid-4">
        {{-- Card 1: Total Mie Dimasak --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Total Mie Dimasak</div>
                <div class="portal-freetaste-kpi-val">{{ number_format($totalDimasak, 0, ',', '.') }}<span class="portal-freetaste-kpi-unit">Pcs</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-fire" style="color: var(--brand-primary);"></i>
                    <span>{{ number_format($todayDimasak, 0, ',', '.') }} Pcs dimasak hari ini</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge brand">
                <i class="fa-solid fa-fire-burner"></i>
            </div>
        </div>

        {{-- Card 2: Total Cup Dibagikan --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Total Cup Dibagikan</div>
                <div class="portal-freetaste-kpi-val" style="color: #059669;">{{ number_format($totalCup, 0, ',', '.') }}<span class="portal-freetaste-kpi-unit">Cup</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-mug-hot" style="color: #059669;"></i>
                    <span>{{ number_format($todayCup, 0, ',', '.') }} Cup tester hari ini</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge emerald">
                <i class="fa-solid fa-mug-hot"></i>
            </div>
        </div>

        {{-- Card 3: Rata-rata Cup per Pcs --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Rasio Cup / Pcs Mie</div>
                <div class="portal-freetaste-kpi-val" style="color: #d97706;">{{ $cupPerPcs }}<span class="portal-freetaste-kpi-unit">Cup/Bungkus</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-chart-pie" style="color: #d97706;"></i>
                    <span>Efisiensi penyajian sampling</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge amber">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>

        {{-- Card 4: Sisa Stok Akhir Sampling --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Sisa Stok Akhir</div>
                <div class="portal-freetaste-kpi-val" style="color: #ea580c;">{{ number_format($totalStokAkhir, 0, ',', '.') }}<span class="portal-freetaste-kpi-unit">Pcs</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-boxes-stacked" style="color: #ea580c;"></i>
                    <span>Dari {{ number_format($totalStokAwal, 0, ',', '.') }} Pcs stok awal</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge orange">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>
    </div>

    {{-- Grid 3 Card Pendukung --}}
    <div class="portal-freetaste-kpi-grid-3">
        {{-- Card 5: Varian SKU Disampling --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Varian SKU Disampling</div>
                <div class="portal-freetaste-kpi-val">{{ number_format($uniqueSkus, 0, ',', '.') }}<span class="portal-freetaste-kpi-unit">Varian</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-layer-group" style="color: #7c3aed;"></i>
                    <span>SKU Mie Sedaap aktif disampling</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge purple">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>

        {{-- Card 6: Toko Tercover --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Toko / Outlet Tercover</div>
                <div class="portal-freetaste-kpi-val">{{ number_format($uniqueStores, 0, ',', '.') }}<span class="portal-freetaste-kpi-unit">Outlet</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-store" style="color: #2563eb;"></i>
                    <span>Titik pelaksanaan event MBR</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge blue">
                <i class="fa-solid fa-store"></i>
            </div>
        </div>

        {{-- Card 7: SPG / Mitra Sampling --}}
        <div class="portal-freetaste-kpi-card">
            <div class="portal-freetaste-kpi-info">
                <div class="portal-freetaste-kpi-label">Mitra / SPG Pelaksana</div>
                <div class="portal-freetaste-kpi-val">{{ number_format($uniqueMitras, 0, ',', '.') }}<span class="portal-freetaste-kpi-unit">Orang</span></div>
                <div class="portal-freetaste-kpi-sub">
                    <i class="fa-solid fa-users" style="color: #4f46e5;"></i>
                    <span>{{ $freetasteKpis['total_submissions'] ?? 0 }} sesi pelaporan sampling</span>
                </div>
            </div>
            <div class="portal-freetaste-icon-badge indigo">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    {{-- 3. GRAFIK TREN SAMPLING (MIE DIMASAK VS CUP DIBAGIKAN) --}}
    <div class="portal-freetaste-card">
        <div class="portal-freetaste-card-header">
            <div>
                <div class="portal-freetaste-card-title">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Tren Aktivitas Sampling Mie Sedaap</span>
                </div>
                <div class="portal-freetaste-card-sub">Perbandingan kuantiti mie yang dimasak (Pcs) dan jumlah cup tester yang dibagikan ke pengunjung</div>
            </div>
            <div class="portal-freetaste-chart-switcher">
                <button type="button" class="portal-freetaste-switcher-btn active" id="freetasteBtnHarian" onclick="switchFreetasteChartMode('daily')">Harian</button>
                <button type="button" class="portal-freetaste-switcher-btn" id="freetasteBtnMingguan" onclick="switchFreetasteChartMode('weekly')">Mingguan</button>
                <button type="button" class="portal-freetaste-switcher-btn" id="freetasteBtnBulanan" onclick="switchFreetasteChartMode('monthly')">Bulanan</button>
            </div>
        </div>
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="freetasteChart"></canvas>
        </div>
    </div>

    {{-- 4. GRID 2X2 TABEL PERFORMA --}}
    <div class="portal-freetaste-table-grid">
        {{-- Tabel 1: Top 5 Varian Mie Paling Banyak Disampling --}}
        <div class="portal-freetaste-card">
            <div class="portal-freetaste-card-header">
                <div>
                    <div class="portal-freetaste-card-title">
                        <i class="fa-solid fa-trophy"></i>
                        <span>Top 5 Varian Paling Banyak Disampling</span>
                    </div>
                    <div class="portal-freetaste-card-sub">Peringkat produk berdasarkan kuantiti bungkus yang dimasak</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="portal-freetaste-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Varian Produk</th>
                            <th class="num">Stok Awal</th>
                            <th class="num">Dimasak</th>
                            <th class="num">Cup</th>
                            <th class="num">Sisa</th>
                            <th style="width: 65px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($freetasteData['top_products'] ?? [] as $idx => $p)
                            <tr>
                                <td>
                                    <span class="portal-freetaste-rank-badge {{ $idx === 0 ? 'top-1' : ($idx === 1 ? 'top-2' : ($idx === 2 ? 'top-3' : '')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">{{ $p['name'] }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $p['sku'] }}</div>
                                </td>
                                <td class="num">{{ number_format($p['stok_awal'] ?? 0, 0, ',', '.') }}</td>
                                <td class="num" style="color: var(--brand-primary);">{{ number_format($p['dimasak'] ?? 0, 0, ',', '.') }}</td>
                                <td class="num" style="color: #059669;">{{ number_format($p['cup'] ?? 0, 0, ',', '.') }}</td>
                                <td class="num" style="color: #ea580c;">{{ number_format($p['stok_akhir'] ?? 0, 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" class="portal-freetaste-btn-action" onclick='openFreetasteBreakdownModal("variant", "{{ addslashes($p['name']) }}", "{{ addslashes($p['sku'] ?? '') }}", @json($p['breakdown'] ?? []))'>
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="portal-freetaste-empty-state">
                                    <div class="portal-freetaste-empty-icon"><i class="fa-solid fa-bowl-food"></i></div>
                                    <div style="font-weight: 700;">Belum Ada Data Sampling Masuk</div>
                                    <div style="font-size: 0.78rem;">Data varian produk akan muncul otomatis saat laporan disubmit.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 2: Top 5 Petugas / Mitra SPG Teraktif --}}
        <div class="portal-freetaste-card">
            <div class="portal-freetaste-card-header">
                <div>
                    <div class="portal-freetaste-card-title">
                        <i class="fa-solid fa-user-check"></i>
                        <span>Top 5 Mitra / SPG Teraktif Sampling</span>
                    </div>
                    <div class="portal-freetaste-card-sub">Peringkat petugas pelaksana event MBR berdasarkan kuantiti penyajian</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="portal-freetaste-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Nama Petugas / SPG</th>
                            <th>Daerah (Cabang)</th>
                            <th class="num">Dimasak (Pcs)</th>
                            <th class="num">Cup Dibagikan</th>
                            <th style="width: 65px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($freetasteData['top_mitra'] ?? [] as $idx => $m)
                            <tr>
                                <td>
                                    <span class="portal-freetaste-rank-badge {{ $idx === 0 ? 'top-1' : ($idx === 1 ? 'top-2' : ($idx === 2 ? 'top-3' : '')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">{{ $m['name'] }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ count($m['stores'] ?? []) }} Toko Tercover</div>
                                </td>
                                <td><span style="font-weight: 600;">{{ $m['area'] }}</span></td>
                                <td class="num" style="color: var(--brand-primary);">{{ number_format($m['dimasak'], 0, ',', '.') }}</td>
                                <td class="num" style="color: #059669;">{{ number_format($m['cup'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" class="portal-freetaste-btn-action" onclick='openFreetasteBreakdownModal("mitra", "{{ addslashes($m['name']) }}", "{{ addslashes($m['area']) }}", @json($m['breakdown'] ?? []))'>
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="portal-freetaste-empty-state">
                                    <div class="portal-freetaste-empty-icon"><i class="fa-solid fa-user-xmark"></i></div>
                                    <div style="font-weight: 700;">Belum Ada Data Mitra</div>
                                    <div style="font-size: 0.78rem;">Data keaktifan mitra akan dihitung dari laporan yang masuk.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 3: Performa Sampling per Daerah (Area) --}}
        <div class="portal-freetaste-card">
            <div class="portal-freetaste-card-header">
                <div>
                    <div class="portal-freetaste-card-title">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>Distribusi Sampling per Daerah (Cabang)</span>
                    </div>
                    <div class="portal-freetaste-card-sub">Sebaran aktivitas sampling di masing-masing cabang/depo</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="portal-freetaste-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Daerah / Cabang</th>
                            <th class="num">Toko Tercover</th>
                            <th class="num">Dimasak (Pcs)</th>
                            <th class="num">Cup Dibagikan</th>
                            <th style="width: 65px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($freetasteData['sampling_by_area'] ?? [] as $idx => $ar)
                            <tr>
                                <td>
                                    <span class="portal-freetaste-rank-badge {{ $idx === 0 ? 'top-1' : ($idx === 1 ? 'top-2' : ($idx === 2 ? 'top-3' : '')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: var(--text-heading);">{{ $ar['area'] }}</td>
                                <td class="num">{{ count($ar['stores'] ?? []) }} Toko</td>
                                <td class="num" style="color: var(--brand-primary);">{{ number_format($ar['dimasak'], 0, ',', '.') }}</td>
                                <td class="num" style="color: #059669;">{{ number_format($ar['cup'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" class="portal-freetaste-btn-action" onclick='openFreetasteBreakdownModal("area", "{{ addslashes($ar['area']) }}", "", @json($ar['breakdown'] ?? []))'>
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="portal-freetaste-empty-state">
                                    <div class="portal-freetaste-empty-icon"><i class="fa-solid fa-map-pin"></i></div>
                                    <div style="font-weight: 700;">Belum Ada Data Daerah</div>
                                    <div style="font-size: 0.78rem;">Data per cabang akan terakumulasi dari laporan yang terkirim.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 4: Performa Sampling per Wilayah (Region) --}}
        <div class="portal-freetaste-card">
            <div class="portal-freetaste-card-header">
                <div>
                    <div class="portal-freetaste-card-title">
                        <i class="fa-solid fa-globe"></i>
                        <span>Distribusi Sampling per Wilayah (Region)</span>
                    </div>
                    <div class="portal-freetaste-card-sub">Akumulasi kuantiti penyajian sampling di level regional</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="portal-freetaste-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Wilayah (Region)</th>
                            <th class="num">Dimasak (Pcs)</th>
                            <th class="num">Cup Dibagikan</th>
                            <th style="width: 65px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($freetasteData['sampling_by_region'] ?? [] as $idx => $rg)
                            <tr>
                                <td>
                                    <span class="portal-freetaste-rank-badge {{ $idx === 0 ? 'top-1' : ($idx === 1 ? 'top-2' : ($idx === 2 ? 'top-3' : '')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: var(--text-heading);">{{ $rg['region'] }}</td>
                                <td class="num" style="color: var(--brand-primary);">{{ number_format($rg['dimasak'], 0, ',', '.') }}</td>
                                <td class="num" style="color: #059669;">{{ number_format($rg['cup'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" class="portal-freetaste-btn-action" onclick='openFreetasteBreakdownModal("region", "{{ addslashes($rg['region']) }}", "", @json($rg['breakdown'] ?? []))'>
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="portal-freetaste-empty-state">
                                    <div class="portal-freetaste-empty-icon"><i class="fa-solid fa-earth-asia"></i></div>
                                    <div style="font-weight: 700;">Belum Ada Data Wilayah</div>
                                    <div style="font-size: 0.78rem;">Data regional akan muncul otomatis dari laporan submisi.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 5. TABEL RINCIAN SUBMISSION LIVE --}}
    <div class="portal-freetaste-card">
        <div class="portal-freetaste-card-header">
            <div>
                <div class="portal-freetaste-card-title">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Rincian Laporan Masuk (Submissions Live)</span>
                </div>
                <div class="portal-freetaste-card-sub">Daftar transaksi sampling dan dokumentasi booth yang dilaporkan oleh petugas</div>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="portal-freetaste-table">
                <thead>
                    <tr>
                        <th>Kode Laporan</th>
                        <th>Waktu Pelaporan</th>
                        <th>Petugas (Mitra)</th>
                        <th>Toko / Outlet</th>
                        <th>Daerah</th>
                        <th class="num">Mie Dimasak</th>
                        <th class="num">Cup Dibagikan</th>
                        <th class="num">Sisa Stok</th>
                        <th style="text-align: center;">Dokumentasi</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $toLocalDiskPath = function($u) {
                            if (!$u || !is_string($u)) return null;
                            $path = parse_url($u, PHP_URL_PATH) ?: $u;
                            $rel = ltrim(str_replace(['/storage/storage/', '/storage/', 'storage/'], '', $path), '/');
                            $full = storage_path('app/public/' . $rel);
                            if (file_exists($full)) return $full;
                            $pub = public_path('storage/' . $rel);
                            if (file_exists($pub)) return $pub;
                            $base = basename($rel);
                            $found = glob(storage_path("app/public/reports/*/{$base}"));
                            if (!empty($found)) return $found[0];
                            return null;
                        };

                        $isDuplicateImage = function($url1, $url2) use ($toLocalDiskPath) {
                            if (!$url1 || !$url2) return false;
                            if ($url1 === $url2) return true;
                            $c1 = preg_replace('/\?.*$/', '', $url1);
                            $c2 = preg_replace('/\?.*$/', '', $url2);
                            if ($c1 === $c2) return true;
                            $f1 = $toLocalDiskPath($url1);
                            $f2 = $toLocalDiskPath($url2);
                            if ($f1 && $f2) {
                                if ($f1 === $f2) return true;
                                $sz1 = @filesize($f1);
                                $sz2 = @filesize($f2);
                                if ($sz1 > 0 && $sz1 === $sz2) {
                                    return md5_file($f1) === md5_file($f2);
                                }
                            }
                            return false;
                        };
                    @endphp
                    @forelse($submissions as $sub)
                        @php
                            $subCart = [];
                            $subDimasak = 0;
                            $subCup = 0;
                            $subAkhir = 0;
                            $subBoothPhoto = null;
                            $subKegiatanPhoto = null;
                            $subStockAkhirPhoto = null;
                            $subPhotos = [];

                            foreach ($sub->values as $v) {
                                $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                                $ft = strtolower(trim((string)($v->field_type ?: ($v->formField ? $v->formField->field_type : ''))));

                                if ($fn === 'mbr_freetaste_items_json' || $fn === 'mbr_sampling_items_json') {
                                    $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                                    if (is_array($raw)) $subCart = $raw;
                                } elseif ($fn === 'total_mie_dimasak') {
                                    $subDimasak = (int)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                                } elseif ($fn === 'total_cup_dibagikan') {
                                    $subCup = (int)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                                } elseif ($fn === 'total_stok_akhir_sampling') {
                                    $subAkhir = (int)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
                                }

                                $isMedia = in_array($ft, ['photo', 'camera_photo', 'multi_photo', 'image']) 
                                    || str_contains($fn, 'foto') || str_contains($fn, 'photo') || str_contains($fn, 'image')
                                    || !empty($v->media_url);

                                if ($isMedia) {
                                    $rawP = $v->media_url ?: ($v->value_text ?: (is_array($v->value_json) ? ($v->value_json[0] ?? null) : null));
                                    if (!empty($rawP) && is_string($rawP)) {
                                        $cleanP = trim($rawP);
                                        if (!str_starts_with($cleanP, '/data/user/') && !str_starts_with($cleanP, 'data/user/') && !str_contains($cleanP, 'cache/wm_')) {
                                            $pUrl = (str_starts_with($cleanP, 'http://') || str_starts_with($cleanP, 'https://'))
                                                ? str_replace(['/storage/storage/', 'esa-solution.id'], ['/storage/', 'esa-solutions.id'], $cleanP)
                                                : asset('storage/' . ltrim(str_replace(['/storage/', 'storage/'], '', $cleanP), '/'));
                                            
                                            $label = $v->formField ? $v->formField->field_label : ucwords(str_replace('_', ' ', $fn));
                                            $subPhotos[] = [
                                                'label' => $label,
                                                'url' => $pUrl,
                                                'field_name' => $fn,
                                            ];
                                            if (str_contains($fn, 'kegiatan') || str_contains($fn, 'sampling_kegiatan')) {
                                                $subKegiatanPhoto = $pUrl;
                                            } elseif (str_contains($fn, 'booth') || str_contains($fn, 'stand')) {
                                                $subBoothPhoto = $pUrl;
                                            } elseif (str_contains($fn, 'stock') || str_contains($fn, 'stok')) {
                                                $subStockAkhirPhoto = $pUrl;
                                            }
                                        }
                                    }
                                }
                            }

                            // If not yet tagged, assign from available subPhotos
                            if (!$subBoothPhoto && !empty($subPhotos)) {
                                $subBoothPhoto = $subPhotos[0]['url'];
                            }

                            // Ekstrak foto sampling varian dari subCart jika ada
                            $cartSamplingPhoto = null;
                            if (!empty($subCart)) {
                                foreach ($subCart as $cItm) {
                                    $rawSam = $cItm['photo_sampling_url'] ?? ($cItm['foto_sampling'] ?? ($cItm['sampling_photo_url'] ?? null));
                                    if ($rawSam && is_string($rawSam)) {
                                        $cleanS = trim($rawSam);
                                        if (!str_starts_with($cleanS, '/data/user/') && !str_starts_with($cleanS, 'data/user/')) {
                                            $cartSamplingPhoto = (str_starts_with($cleanS, 'http://') || str_starts_with($cleanS, 'https://'))
                                                ? str_replace(['/storage/storage/', 'esa-solution.id'], ['/storage/', 'esa-solutions.id'], $cleanS)
                                                : asset('storage/' . ltrim(str_replace(['/storage/', 'storage/'], '', $cleanS), '/'));
                                            break;
                                        }
                                    }
                                }
                            }

                            // Fallback jika foto tersimpan di disk
                            if (empty($subPhotos)) {
                                $matches = glob(storage_path("app/public/reports/*/report_{$sub->id}_*.jpg"));
                                if (empty($matches)) {
                                    $matches = glob(storage_path("app/public/reports/*/*_{$sub->id}_*.jpg"));
                                }
                                if (!empty($matches)) {
                                    foreach ($matches as $match) {
                                        $rel = str_replace(storage_path('app/public/'), '', $match);
                                        $rel = str_replace('\\', '/', $rel);
                                        $pUrl = asset('storage/' . ltrim($rel, '/'));
                                        $subPhotos[] = [
                                            'label' => 'Foto Stand / Booth Sampling',
                                            'url' => $pUrl,
                                            'field_name' => 'foto_booth_sampling',
                                        ];
                                        if (!$subBoothPhoto) {
                                            $subBoothPhoto = $pUrl;
                                        } elseif (!$subKegiatanPhoto && !$isDuplicateImage($pUrl, $subBoothPhoto)) {
                                            $subKegiatanPhoto = $pUrl;
                                        }
                                    }
                                }
                            }
                            if (!$subBoothPhoto && !empty($subPhotos)) {
                                $subBoothPhoto = $subPhotos[0]['url'];
                            }

                            // Jika $subKegiatanPhoto kosong ATAU sama persis dengan $subBoothPhoto, utamakan foto dari cart item jika bukan duplikat
                            if ($cartSamplingPhoto && (!$subKegiatanPhoto || $isDuplicateImage($subKegiatanPhoto, $subBoothPhoto))) {
                                if (!$isDuplicateImage($cartSamplingPhoto, $subBoothPhoto)) {
                                    $subKegiatanPhoto = $cartSamplingPhoto;
                                }
                            }

                            if ((!$subKegiatanPhoto || $isDuplicateImage($subKegiatanPhoto, $subBoothPhoto)) && count($subPhotos) > 1) {
                                foreach ($subPhotos as $sp) {
                                    if (!$isDuplicateImage($sp['url'], $subBoothPhoto) && !$isDuplicateImage($sp['url'], $subStockAkhirPhoto)) {
                                        $subKegiatanPhoto = $sp['url'];
                                        break;
                                    }
                                }
                            }
                            if (!$subStockAkhirPhoto && count($subPhotos) > 2) {
                                foreach ($subPhotos as $sp) {
                                    if (!$isDuplicateImage($sp['url'], $subBoothPhoto) && !$isDuplicateImage($sp['url'], $subKegiatanPhoto)) {
                                        $subStockAkhirPhoto = $sp['url'];
                                        break;
                                    }
                                }
                            }

                            // Hindari duplikasi jika foto kegiatan identik/duplikat dengan booth
                            if ($subKegiatanPhoto && $isDuplicateImage($subKegiatanPhoto, $subBoothPhoto)) {
                                $subKegiatanPhoto = null;
                            }

                            // Hindari duplikasi jika foto stock akhir identik dengan booth/kegiatan
                            if ($subStockAkhirPhoto && ($isDuplicateImage($subStockAkhirPhoto, $subBoothPhoto) || $isDuplicateImage($subStockAkhirPhoto, $subKegiatanPhoto))) {
                                $subStockAkhirPhoto = null;
                            }

                            // Filter duplikasi pada $subPhotos
                            $uniqueSubPhotos = [];
                            foreach ($subPhotos as $sp) {
                                $isDup = false;
                                foreach ($uniqueSubPhotos as $usp) {
                                    if ($isDuplicateImage($sp['url'], $usp['url'])) {
                                        $isDup = true;
                                        break;
                                    }
                                }
                                if (!$isDup) {
                                    $uniqueSubPhotos[] = $sp;
                                }
                            }
                            $subPhotos = $uniqueSubPhotos;

                            // Normalisasi foto produk sampling
                            if (!empty($subCart)) {
                                foreach ($subCart as &$it) {
                                    $rawSam = $it['photo_sampling_url'] ?? ($it['foto_sampling'] ?? ($it['sampling_photo_url'] ?? null));
                                    if ($rawSam && is_string($rawSam)) {
                                        $cleanS = trim($rawSam);
                                        if (!str_starts_with($cleanS, '/data/user/') && !str_starts_with($cleanS, 'data/user/')) {
                                            $sUrl = (str_starts_with($cleanS, 'http://') || str_starts_with($cleanS, 'https://'))
                                                ? str_replace(['/storage/storage/', 'esa-solution.id'], ['/storage/', 'esa-solutions.id'], $cleanS)
                                                : asset('storage/' . ltrim(str_replace(['/storage/', 'storage/'], '', $cleanS), '/'));
                                            $it['photo_sampling_url'] = $sUrl;
                                            $it['foto_sampling'] = $sUrl;
                                        }
                                    }
                                }
                                unset($it);

                                if ($subDimasak <= 0) {
                                    foreach ($subCart as $it) {
                                        $subDimasak += (int)($it['jumlah_dimasak'] ?? ($it['dimasak'] ?? 0));
                                        $subCup += (int)($it['jumlah_cup'] ?? ($it['cup'] ?? 0));
                                        $subAkhir += (int)($it['stok_akhir'] ?? 0);
                                    }
                                }
                            }
                            $freetasteTimeFormatted = ($sub->submitted_at ? $sub->submitted_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') : ($sub->created_at ? $sub->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') : '-')) . ' WIB';
                        @endphp
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-weight: 700; color: var(--brand-primary);">
                                    {{ $sub->submission_code ?: ('SUB-' . $sub->id) }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-heading);">
                                    {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y') : $sub->created_at->translatedFormat('d M Y') }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $sub->submitted_at ? $sub->submitted_at->format('H:i') : $sub->created_at->format('H:i') }} WIB
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">
                                    {{ $sub->employee ? ($sub->employee->full_name ?: $sub->employee->name) : 'Mitra SPG' }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $sub->employee?->employee_no ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    {{ $sub->workLocation ? $sub->workLocation->name : ($sub->store_name ?: '-') }}
                                </div>
                            </td>
                            <td>
                                <span>{{ $sub->workLocation && $sub->workLocation->branch ? $sub->workLocation->branch->name : ($sub->employee && $sub->employee->branch ? $sub->employee->branch->name : '-') }}</span>
                            </td>
                            <td class="num" style="color: var(--brand-primary);">
                                {{ number_format($subDimasak, 0, ',', '.') }} Pcs
                            </td>
                            <td class="num" style="color: #059669;">
                                {{ number_format($subCup, 0, ',', '.') }} Cup
                            </td>
                            <td class="num" style="color: #ea580c;">
                                {{ number_format($subAkhir, 0, ',', '.') }} Pcs
                            </td>
                            <td style="text-align: center;">
                                @if($subBoothPhoto)
                                    <button type="button" class="portal-freetaste-btn-action" onclick="openLightbox('{{ $subBoothPhoto }}')" title="Lihat Foto Stand / Booth" style="padding: 4px 8px; font-size: 0.72rem;">
                                        <i class="fa-solid fa-store"></i>
                                        <span>Booth</span>
                                    </button>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="portal-freetaste-btn-action" onclick='openFreetasteSubmissionDetailModal(@json($sub), @json($subCart), @json($subPhotos), "{{ $subBoothPhoto }}", "{{ $subKegiatanPhoto }}", "{{ $subStockAkhirPhoto }}", "{{ $freetasteTimeFormatted }}")'>
                                    <i class="fa-solid fa-eye"></i>
                                    <span>Detail</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="portal-freetaste-empty-state">
                                <div class="portal-freetaste-empty-icon"><i class="fa-solid fa-inbox"></i></div>
                                <div style="font-weight: 700;">Belum Ada Laporan Free Taste Masuk</div>
                                <div style="font-size: 0.78rem;">Data laporan submisi sampling yang dikirim mitra akan otomatis tampil di sini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
                {{ $submissions->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>

{{-- MODAL DETAIL SUBMISSION & ITEM SAMPLING --}}
<div class="portal-freetaste-modal-overlay" id="freetasteSubDetailModal" onclick="closeFreetasteSubmissionDetailModal()">
    <div class="portal-freetaste-modal-card" onclick="event.stopPropagation()">
        <div class="portal-freetaste-modal-header">
            <div class="portal-freetaste-modal-title">
                <i class="fa-solid fa-bowl-food" style="color: var(--brand-primary);"></i>
                <span>Rincian Produk Sampling Laporan MBR</span>
            </div>
            <button type="button" class="portal-freetaste-modal-close" onclick="closeFreetasteSubmissionDetailModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-freetaste-modal-body" id="freetasteSubModalBody">
            <!-- Dynamic Content -->
        </div>
    </div>
</div>

{{-- MODAL GALERI FOTO SAMPLING --}}
<div class="portal-freetaste-modal-overlay" id="freetasteGalleryModal" onclick="closeFreetasteGalleryModal()">
    <div class="portal-freetaste-modal-card" style="max-width: 950px;" onclick="event.stopPropagation()">
        <div class="portal-freetaste-modal-header">
            <div class="portal-freetaste-modal-title">
                <i class="fa-solid fa-images" style="color: var(--brand-primary);"></i>
                <span id="freetasteGalleryModalTitle">Dokumentasi Foto Sampling & Booth Event MBR</span>
            </div>
            <button type="button" class="portal-freetaste-modal-close" onclick="closeFreetasteGalleryModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-freetaste-modal-body">
            @if(empty($galleryPhotos))
                <div class="portal-freetaste-empty-state">
                    <div class="portal-freetaste-empty-icon"><i class="fa-solid fa-camera-retro"></i></div>
                    <div style="font-weight: 700;">Belum Ada Foto Dokumentasi Sampling</div>
                    <div style="font-size: 0.8rem;">Foto booth dan foto sampling yang dikirim mitra akan otomatis terkumpul di galeri ini.</div>
                </div>
            @else
                <div class="portal-freetaste-photo-grid">
                    @foreach($galleryPhotos as $photo)
                        <div class="portal-freetaste-photo-card">
                            <div style="position: relative;">
                                <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}" class="portal-freetaste-photo-thumb" onclick="openLightbox('{{ $photo['url'] }}')">
                                <span style="position: absolute; top: 6px; left: 6px; padding: 2px 7px; border-radius: 4px; font-size: 0.68rem; font-weight: 800; color: #fff; background: {{ ($photo['type'] ?? '') === 'kegiatan' ? '#f59e0b' : (($photo['type'] ?? '') === 'booth' ? 'var(--brand-primary)' : '#10b981') }}; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                                    {{ ($photo['type'] ?? '') === 'kegiatan' ? 'Kegiatan Sampling' : (($photo['type'] ?? '') === 'booth' ? 'Stand / Booth' : 'Varian Produk') }}
                                </span>
                            </div>
                            <div class="portal-freetaste-photo-info">
                                <div style="font-weight: 700; color: var(--text-heading); font-size: 0.82rem;">{{ $photo['product'] }}</div>
                                <div class="portal-freetaste-photo-meta">
                                    <div><i class="fa-solid fa-store"></i> {{ $photo['store'] }}</div>
                                    <div><i class="fa-solid fa-user"></i> {{ $photo['mitra'] }} &bull; {{ $photo['date'] }}</div>
                                    @if(isset($photo['dimasak']) && $photo['dimasak'] > 0)
                                        <div style="margin-top: 0.25rem; font-weight: 700; color: var(--brand-primary);">
                                            Masak: {{ $photo['dimasak'] }} Pcs &bull; Cup: {{ $photo['cup'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL RINCIAN DETAIL DRILLDOWN (TOP VARIAN, TOP MITRA, DAERAH, WILAYAH) --}}
<div class="portal-freetaste-modal-overlay" id="freetasteBreakdownModal" onclick="closeFreetasteBreakdownModal()">
    <div class="portal-freetaste-modal-card" style="max-width: 860px;" onclick="event.stopPropagation()">
        <div class="portal-freetaste-modal-header">
            <div class="portal-freetaste-modal-title" id="freetasteBreakdownModalTitle">
                <i class="fa-solid fa-list-ul" style="color: var(--brand-primary);"></i>
                <span>Rincian Detail</span>
            </div>
            <button type="button" class="portal-freetaste-modal-close" onclick="closeFreetasteBreakdownModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-freetaste-modal-body" id="freetasteBreakdownModalBody">
            {{-- Injected dynamically --}}
        </div>
    </div>
</div>

{{-- LIGHTBOX ZOOM PREVIEW --}}
<div class="portal-freetaste-lightbox" id="freetasteLightboxOverlay" onclick="closeFreetasteLightbox()">
    <img id="freetasteLightboxImg" src="" alt="Perbesar Foto" onclick="event.stopPropagation()">
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const freetasteChartData = {
        daily: @json($freetasteData['chart']['daily'] ?? ['labels' => [], 'dimasaks' => [], 'cups' => []]),
        weekly: @json($freetasteData['chart']['weekly'] ?? ['labels' => [], 'dimasaks' => [], 'cups' => []]),
        monthly: @json($freetasteData['chart']['monthly'] ?? ['labels' => [], 'dimasaks' => [], 'cups' => []])
    };

    const freetasteBrandPrimary = '{{ $brandColor }}';
    let freetasteChartInstance = null;

    function initFreetasteChart() {
        const ctx = document.getElementById('freetasteChart');
        if (!ctx) return;

        if (typeof Chart === 'undefined') {
            console.warn('Chart.js belum siap, mencoba lagi...');
            setTimeout(initFreetasteChart, 150);
            return;
        }

        if (freetasteChartInstance) {
            freetasteChartInstance.destroy();
        }

        const initialLabels = freetasteChartData.daily.labels || [];
        const initialDimasaks = freetasteChartData.daily.dimasaks || [];
        const initialCups = freetasteChartData.daily.cups || [];

        freetasteChartInstance = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: initialLabels,
                datasets: [
                    {
                        label: 'Mie Dimasak (Pcs)',
                        data: initialDimasaks,
                        backgroundColor: freetasteBrandPrimary,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 38
                    },
                    {
                        label: 'Cup Tester (Cup)',
                        data: initialCups,
                        backgroundColor: '#059669',
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 38
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { family: 'Outfit', size: 12, weight: '700' },
                            boxWidth: 14,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Outfit', size: 11, weight: '600' }, color: '#64748b' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Outfit', size: 11 },
                            color: '#64748b',
                            callback: function(value) {
                                return Number(value).toLocaleString('id-ID');
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function switchFreetasteChartMode(mode) {
        document.getElementById('freetasteBtnHarian')?.classList.remove('active');
        document.getElementById('freetasteBtnMingguan')?.classList.remove('active');
        document.getElementById('freetasteBtnBulanan')?.classList.remove('active');

        if (mode === 'daily') document.getElementById('freetasteBtnHarian')?.classList.add('active');
        if (mode === 'weekly') document.getElementById('freetasteBtnMingguan')?.classList.add('active');
        if (mode === 'monthly') document.getElementById('freetasteBtnBulanan')?.classList.add('active');

        if (!freetasteChartInstance) return;

        const d = freetasteChartData[mode] || { labels: [], dimasaks: [], cups: [] };
        freetasteChartInstance.data.labels = d.labels;
        freetasteChartInstance.data.datasets[0].data = d.dimasaks;
        freetasteChartInstance.data.datasets[1].data = d.cups;
        freetasteChartInstance.update();
    }

    // Modal Galeri
    function openFreetasteGalleryModal(filterType = 'all') {
        const modal = document.getElementById('freetasteGalleryModal');
        if (!modal) return;
        modal.classList.add('active');
    }

    function closeFreetasteGalleryModal() {
        document.getElementById('freetasteGalleryModal')?.classList.remove('active');
    }

    // Helper Format Waktu Lapor ke Jam Indonesia (WIB)
    function formatWaktuLapor(dateVal, fallbackFormatted) {
        if (fallbackFormatted && fallbackFormatted !== '-' && !fallbackFormatted.includes('T')) {
            return fallbackFormatted;
        }
        if (!dateVal) return '-';
        try {
            const d = new Date(dateVal);
            if (isNaN(d.getTime())) return dateVal;
            const options = {
                timeZone: 'Asia/Jakarta',
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            };
            return d.toLocaleDateString('id-ID', options).replace(/\./g, ':') + ' WIB';
        } catch (e) {
            return dateVal;
        }
    }

    // Modal Rincian Submissions
    function openFreetasteSubmissionDetailModal(sub, cart, photos, boothPhoto, kegiatanPhoto, stockAkhirPhoto, formattedTime) {
        const modal = document.getElementById('freetasteSubDetailModal');
        const body = document.getElementById('freetasteSubModalBody');
        if (!modal || !body) return;

        // Backward compatibility jika formattedTime dikirim di argumen ke-5 atau ke-6
        if (typeof kegiatanPhoto === 'string' && (kegiatanPhoto.includes('WIB') || kegiatanPhoto.includes(':') || !formattedTime)) {
            if (!stockAkhirPhoto && !formattedTime) {
                formattedTime = kegiatanPhoto;
                kegiatanPhoto = null;
            }
        }
        if (typeof stockAkhirPhoto === 'string' && (stockAkhirPhoto.includes('WIB') || stockAkhirPhoto.includes(':'))) {
            if (!formattedTime) {
                formattedTime = stockAkhirPhoto;
                stockAkhirPhoto = null;
            }
        }

        photos = photos || [];

        const resolveUrl = (u) => {
            if (!u || typeof u !== 'string') return '';
            let s = u.trim();
            if (!s || s === '-' || s.startsWith('/data/user/') || s.startsWith('data/user/')) return '';
            if (s.startsWith('http://') || s.startsWith('https://')) {
                return s.replace('esa-solution.id', 'esa-solutions.id').replace('/storage/storage/', '/storage/');
            }
            if (s.startsWith('/storage/')) return s;
            if (s.startsWith('storage/')) return '/' + s;
            return '/storage/' + s.replace(/^\/+/, '');
        };

        // Identifikasi foto kegiatan & foto booth & foto stock akhir dari photos array jika belum spesifik
        if (!kegiatanPhoto && photos.length > 0) {
            const kegObj = photos.find(p => p.field_name && (p.field_name.includes('kegiatan') || p.field_name.includes('sampling_kegiatan')));
            if (kegObj) kegiatanPhoto = kegObj.url;
        }
        if (!boothPhoto && photos.length > 0) {
            const boothObj = photos.find(p => p.field_name && (p.field_name.includes('booth') || p.field_name.includes('stand')));
            if (boothObj) boothPhoto = boothObj.url;
        }
        if (!stockAkhirPhoto && photos.length > 0) {
            const stockObj = photos.find(p => p.field_name && (p.field_name.includes('stock_akhir') || p.field_name.includes('stok_akhir') || (p.field_name.includes('stock') && !p.field_name.includes('awal')) || (p.field_name.includes('stok') && !p.field_name.includes('awal'))));
            if (stockObj) stockAkhirPhoto = stockObj.url;
        }

        // Ekstrak foto kegiatan sampling dari cart items jika submisi lama menyimpan foto di item produk
        let cartSamplingPhoto = null;
        if (cart && cart.length > 0) {
            for (const it of cart) {
                const rawPhoto = it.photo_sampling_url || it.foto_sampling || it.sampling_photo_url || null;
                if (rawPhoto && typeof rawPhoto === 'string') {
                    const resolved = resolveUrl(rawPhoto);
                    if (resolved && resolved !== resolveUrl(boothPhoto)) {
                        cartSamplingPhoto = rawPhoto;
                        break;
                    } else if (resolved && !cartSamplingPhoto) {
                        cartSamplingPhoto = rawPhoto;
                    }
                }
            }
        }

        // Jika foto kegiatan kosong ATAU sama persis dengan foto booth, utamakan foto dari cart item
        if (cartSamplingPhoto && (!kegiatanPhoto || resolveUrl(kegiatanPhoto) === resolveUrl(boothPhoto))) {
            kegiatanPhoto = cartSamplingPhoto;
        }

        // Fallback foto yang belum terpetakan
        if (!boothPhoto && photos.length > 0) {
            boothPhoto = photos[0].url;
        }
        if ((!kegiatanPhoto || resolveUrl(kegiatanPhoto) === resolveUrl(boothPhoto)) && photos.length > 1) {
            const other = photos.find(p => resolveUrl(p.url) !== resolveUrl(boothPhoto) && resolveUrl(p.url) !== resolveUrl(stockAkhirPhoto));
            if (other) kegiatanPhoto = other.url;
        }
        if ((!stockAkhirPhoto || resolveUrl(stockAkhirPhoto) === resolveUrl(boothPhoto) || resolveUrl(stockAkhirPhoto) === resolveUrl(kegiatanPhoto)) && photos.length > 2) {
            const third = photos.find(p => resolveUrl(p.url) !== resolveUrl(boothPhoto) && resolveUrl(p.url) !== resolveUrl(kegiatanPhoto));
            if (third) stockAkhirPhoto = third.url;
        }

        // Jika foto kegiatan tetap sama persis dengan foto booth, jangan duplikasi foto
        if (kegiatanPhoto && resolveUrl(kegiatanPhoto) === resolveUrl(boothPhoto)) {
            kegiatanPhoto = null;
        }

        const resolvedBooth = boothPhoto ? resolveUrl(boothPhoto) : null;
        const resolvedKegiatan = kegiatanPhoto ? resolveUrl(kegiatanPhoto) : null;
        const resolvedStockAkhir = stockAkhirPhoto ? resolveUrl(stockAkhirPhoto) : null;

        let cartHtml = '';
        if (cart && cart.length > 0) {
            cartHtml = `
                <div style="overflow-x: auto;">
                    <table class="portal-freetaste-table" style="margin-top: 0.75rem; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Varian Produk</th>
                                <th class="num">Stok Awal</th>
                                <th class="num">Dimasak</th>
                                <th class="num">Stok Akhir</th>
                                <th class="num">Cup Tester</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cart.map((item, i) => `
                                <tr>
                                    <td style="color: var(--text-muted); font-weight: 700; text-align: center;">${i + 1}</td>
                                    <td>
                                        <div style="font-weight: 800; color: var(--text-heading);">${item.name || item.product_name || 'Produk Mie Sedaap'}</div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">${item.sku_code || item.sku || '-'}</div>
                                    </td>
                                    <td class="num">${Number(item.stok_awal || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: var(--brand-primary); font-weight: 800;">${Number(item.jumlah_dimasak || item.dimasak || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #ea580c; font-weight: 800;">${Number(item.stok_akhir || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 800;">${Number(item.jumlah_cup || item.cup || 0).toLocaleString('id-ID')} Cup</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            cartHtml = '<p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.75rem;">Tidak ada item keranjang sampling terperinci.</p>';
        }

        // Section Dokumentasi Foto Sampling Sejajar (Stand / Booth, Kegiatan Sampling, Foto Stock Akhir)
        const photoCards = [
            {
                id: 'modalCardBooth',
                title: 'Foto Stand / Booth Sampling',
                badge: 'Setup Stand / Booth',
                icon: 'fa-store',
                color: 'var(--brand-primary)',
                url: resolvedBooth,
                emptyText: 'Foto stand / booth belum diunggah.'
            },
            {
                id: 'modalCardKegiatan',
                title: 'Foto Kegiatan Sampling',
                badge: 'Kegiatan Sampling',
                icon: 'fa-fire-burner',
                color: '#d97706',
                url: resolvedKegiatan,
                emptyText: 'Foto kegiatan sampling belum diunggah.'
            },
            {
                id: 'modalCardStockAkhir',
                title: 'Foto Stock Akhir',
                badge: 'Foto Stock Akhir',
                icon: 'fa-boxes-stacked',
                color: '#059669',
                url: resolvedStockAkhir,
                emptyText: 'Foto stock akhir belum diunggah.'
            }
        ];

        const hasAnyPhoto = photoCards.some(c => c.url !== null);

        let photoSectionHtml = `
            <div style="margin-top: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-heading); margin: 0; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-camera" style="color: var(--brand-primary);"></i>
                        Foto Dokumentasi Sampling
                    </h4>
                    ${hasAnyPhoto ? '<span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><i class="fa-solid fa-magnifying-glass-plus"></i> Klik foto untuk memperbesar</span>' : ''}
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.9rem;">
                    ${photoCards.map(c => `
                        <div id="${c.id}" style="display: flex; flex-direction: column; gap: 6px;">
                            <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid ${c.icon}" style="color: ${c.color};"></i>
                                <span>${c.title}</span>
                            </div>
                            ${c.url ? `
                                <div style="position: relative; border-radius: 14px; overflow: hidden; border: 1.5px solid ${c.color}; background: #0f172a; box-shadow: 0 4px 14px rgba(0,0,0,0.08);">
                                    <img src="${c.url}" alt="${c.title}" onclick="openLightbox('${c.url}')" style="width: 100%; height: 185px; object-fit: cover; display: block; cursor: pointer; transition: transform 0.25s ease;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                                    <div style="position: absolute; bottom: 0; inset-x: 0; background: linear-gradient(to top, rgba(0,0,0,0.88), transparent); padding: 0.5rem 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                                        <span style="color: #fff; font-size: 0.72rem; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid ${c.icon}"></i> ${c.badge}
                                        </span>
                                        <button type="button" onclick="openLightbox('${c.url}')" style="background: rgba(255,255,255,0.25); color: #fff; border: none; border-radius: 6px; padding: 2px 7px; font-size: 0.68rem; font-weight: 700; cursor: pointer;">
                                            <i class="fa-solid fa-expand"></i> Zoom
                                        </button>
                                    </div>
                                </div>
                            ` : `
                                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; height: 185px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                                    <i class="fa-solid ${c.icon}" style="font-size: 1.6rem; color: #cbd5e1;"></i>
                                    <span>${c.emptyText}</span>
                                </div>
                            `}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        const waktuLaporStr = formatWaktuLapor(sub.submitted_at || sub.created_at, formattedTime);

        body.innerHTML = `
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
                <div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Kode Submisi</span>
                    <div style="font-weight: 800; font-family: monospace; color: var(--brand-primary); font-size: 0.95rem;">${sub.submission_code || '-'}</div>
                </div>
                <div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Waktu Lapor</span>
                    <div style="font-weight: 700; color: var(--text-heading); font-size: 0.9rem;">${waktuLaporStr}</div>
                </div>
                <div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Petugas / Mitra</span>
                    <div style="font-weight: 700; color: var(--text-heading); font-size: 0.9rem;">${sub.employee?.full_name || sub.employee?.name || 'Petugas'}</div>
                </div>
                <div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Toko / Lokasi</span>
                    <div style="font-weight: 700; color: var(--text-heading); font-size: 0.9rem;">${sub.work_location?.name || sub.store_name || '-'}</div>
                </div>
            </div>

            ${photoSectionHtml}

            <div style="margin-top: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-heading); margin: 0; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-bowl-food" style="color: var(--brand-primary);"></i>
                        Rincian Varian Sampling & Kuantiti
                    </h4>
                    <span style="font-size: 0.78rem; font-weight: 700; color: #059669;">${cart.length} Varian Produk</span>
                </div>
                ${cartHtml}
            </div>
        `;

        // Client-side verification: deteksi jika foto kegiatan identik secara visual / data dengan foto booth
        if (resolvedBooth && resolvedKegiatan && resolvedBooth !== resolvedKegiatan) {
            const imgA = new Image();
            const imgB = new Image();
            imgA.crossOrigin = 'anonymous';
            imgB.crossOrigin = 'anonymous';
            let loaded = 0;
            const checkDuplicate = () => {
                loaded++;
                if (loaded === 2) {
                    if (imgA.naturalWidth > 0 && imgA.naturalWidth === imgB.naturalWidth && imgA.naturalHeight === imgB.naturalHeight) {
                        let isIdentical = false;
                        try {
                            const canvas = document.createElement('canvas');
                            canvas.width = 16;
                            canvas.height = 16;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(imgA, 0, 0, 16, 16);
                            const pA = ctx.getImageData(0, 0, 16, 16).data;
                            ctx.clearRect(0, 0, 16, 16);
                            ctx.drawImage(imgB, 0, 0, 16, 16);
                            const pB = ctx.getImageData(0, 0, 16, 16).data;
                            let diff = 0;
                            for (let i = 0; i < pA.length; i += 4) {
                                diff += Math.abs(pA[i] - pB[i]) + Math.abs(pA[i+1] - pB[i+1]) + Math.abs(pA[i+2] - pB[i+2]);
                            }
                            if (diff < 150) isIdentical = true;
                        } catch (e) {
                            try {
                                Promise.all([
                                    fetch(resolvedBooth, { method: 'HEAD' }).then(r => r.headers.get('content-length')),
                                    fetch(resolvedKegiatan, { method: 'HEAD' }).then(r => r.headers.get('content-length'))
                                ]).then(([lenA, lenB]) => {
                                    if (lenA && lenB && lenA === lenB && lenA !== '0') {
                                        setKegiatanEmpty();
                                    }
                                }).catch(() => {});
                            } catch(err) {}
                        }
                        if (isIdentical) {
                            setKegiatanEmpty();
                        }
                    }
                }
            };
            const setKegiatanEmpty = () => {
                const cardEl = document.getElementById('modalCardKegiatan');
                if (cardEl) {
                    cardEl.innerHTML = `
                        <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-fire-burner" style="color: #d97706;"></i>
                            <span>Foto Kegiatan Sampling</span>
                        </div>
                        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; height: 185px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                            <i class="fa-solid fa-fire-burner" style="font-size: 1.6rem; color: #cbd5e1;"></i>
                            <span>Foto kegiatan sampling belum diunggah.</span>
                        </div>
                    `;
                }
            };
            imgA.onload = checkDuplicate;
            imgB.onload = checkDuplicate;
            imgA.src = resolvedBooth;
            imgB.src = resolvedKegiatan;
        }

        modal.classList.add('active');
    }

    function closeFreetasteSubmissionDetailModal() {
        document.getElementById('freetasteSubDetailModal')?.classList.remove('active');
    }

    // Modal Breakdown Drilldown Sampling (Top Varian, Top Mitra, Daerah, Wilayah)
    function openFreetasteBreakdownModal(type, title, subtitle, list) {
        const modal = document.getElementById('freetasteBreakdownModal');
        const titleEl = document.getElementById('freetasteBreakdownModalTitle');
        const bodyEl = document.getElementById('freetasteBreakdownModalBody');
        if (!modal || !bodyEl) return;

        list = list || [];
        let modalTitleHtml = '';
        let tableHtml = '';
        let totalDimasak = 0;
        let totalCup = 0;

        list.forEach(item => {
            totalDimasak += Number(item.dimasak || 0);
            totalCup += Number(item.cup || 0);
        });

        if (type === 'variant') {
            modalTitleHtml = `<i class="fa-solid fa-trophy" style="color: var(--brand-primary);"></i> Rincian Sampling Varian: <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode SKU</div>
                        <div style="font-weight: 700; font-family: monospace; color: var(--text-heading);">${subtitle || '-'}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Dimasak</div>
                            <div style="font-weight: 800; color: var(--brand-primary); font-size: 1.15rem;">${totalDimasak.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Cup Tester</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">${totalCup.toLocaleString('id-ID')} Cup</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-freetaste-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Mitra (SPG)</th>
                                <th>Toko / Lokasi</th>
                                <th>Area / Cabang</th>
                                <th class="num">Dimasak (Pcs)</th>
                                <th class="num">Cup Tester</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.mitra || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td style="color: var(--text-muted); font-weight: 600;">${it.area || '-'}</td>
                                    <td class="num" style="color: var(--brand-primary); font-weight: 700;">${Number(it.dimasak || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">${Number(it.cup || 0).toLocaleString('id-ID')} Cup</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data rincian sampling untuk varian ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="4" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: var(--brand-primary);">${totalDimasak.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">${totalCup.toLocaleString('id-ID')} Cup</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        } else if (type === 'mitra') {
            modalTitleHtml = `<i class="fa-solid fa-user-check" style="color: #059669;"></i> Rincian Sampling Mitra: <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Daerah / Cabang</div>
                        <div style="font-weight: 700; color: var(--text-heading);">${subtitle || '-'}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Dimasak</div>
                            <div style="font-weight: 800; color: var(--brand-primary); font-size: 1.15rem;">${totalDimasak.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Cup Tester</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">${totalCup.toLocaleString('id-ID')} Cup</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-freetaste-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Varian Produk</th>
                                <th>Toko / Lokasi</th>
                                <th class="num">Dimasak (Pcs)</th>
                                <th class="num">Cup Tester</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.product || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td class="num" style="color: var(--brand-primary); font-weight: 700;">${Number(it.dimasak || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">${Number(it.cup || 0).toLocaleString('id-ID')} Cup</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data produk sampling untuk mitra ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="3" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: var(--brand-primary);">${totalDimasak.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">${totalCup.toLocaleString('id-ID')} Cup</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        } else if (type === 'area') {
            modalTitleHtml = `<i class="fa-solid fa-map-location-dot" style="color: #0d9488;"></i> Rincian Distribusi Sampling Daerah / Cabang: <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Daerah / Cabang</div>
                        <div style="font-weight: 800; color: var(--text-heading); font-size: 1.05rem;">${title}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Dimasak</div>
                            <div style="font-weight: 800; color: var(--brand-primary); font-size: 1.15rem;">${totalDimasak.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Cup Tester</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">${totalCup.toLocaleString('id-ID')} Cup</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-freetaste-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Mitra (SPG)</th>
                                <th>Toko / Lokasi</th>
                                <th>Varian Produk</th>
                                <th class="num">Dimasak (Pcs)</th>
                                <th class="num">Cup Tester</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.mitra || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td style="color: var(--brand-primary); font-weight: 600;">${it.product || '-'}</td>
                                    <td class="num" style="color: var(--brand-primary); font-weight: 700;">${Number(it.dimasak || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">${Number(it.cup || 0).toLocaleString('id-ID')} Cup</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data sampling di daerah ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="4" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: var(--brand-primary);">${totalDimasak.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">${totalCup.toLocaleString('id-ID')} Cup</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        } else if (type === 'region') {
            modalTitleHtml = `<i class="fa-solid fa-globe" style="color: var(--brand-primary);"></i> Rincian Distribusi Sampling Wilayah (Region): <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Wilayah (Region)</div>
                        <div style="font-weight: 800; color: var(--text-heading); font-size: 1.05rem;">${title}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Dimasak</div>
                            <div style="font-weight: 800; color: var(--brand-primary); font-size: 1.15rem;">${totalDimasak.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Cup Tester</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">${totalCup.toLocaleString('id-ID')} Cup</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-freetaste-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Mitra (SPG)</th>
                                <th>Area / Cabang</th>
                                <th>Toko / Lokasi</th>
                                <th>Varian Produk</th>
                                <th class="num">Dimasak (Pcs)</th>
                                <th class="num">Cup Tester</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.mitra || '-'}</td>
                                    <td style="color: var(--text-muted); font-weight: 600;">${it.area || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td style="color: var(--brand-primary); font-weight: 600;">${it.product || '-'}</td>
                                    <td class="num" style="color: var(--brand-primary); font-weight: 700;">${Number(it.dimasak || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">${Number(it.cup || 0).toLocaleString('id-ID')} Cup</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data sampling di wilayah ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="5" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: var(--brand-primary);">${totalDimasak.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">${totalCup.toLocaleString('id-ID')} Cup</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        }

        titleEl.innerHTML = modalTitleHtml;
        bodyEl.innerHTML = tableHtml;
        modal.classList.add('active');
    }

    function closeFreetasteBreakdownModal() {
        document.getElementById('freetasteBreakdownModal')?.classList.remove('active');
    }

    // Lightbox Zoom
    function openLightbox(url) {
        const lb = document.getElementById('freetasteLightboxOverlay');
        const img = document.getElementById('freetasteLightboxImg');
        if (lb && img) {
            img.src = url;
            lb.classList.add('active');
        }
    }

    function closeFreetasteLightbox() {
        document.getElementById('freetasteLightboxOverlay')?.classList.remove('active');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFreetasteChart);
    } else {
        initFreetasteChart();
    }
</script>
@endpush
