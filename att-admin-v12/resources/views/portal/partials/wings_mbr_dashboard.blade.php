{{-- 
    WINGS SURYA - DASHBOARD LAPORAN PENJUALAN (EVENT MBR)
    Identitas Visual: 100% Selaras dengan Principal Portal (Native Brand Tokens, Clean White Cards, Soft Badges)
    100% Data Riil Submisi Masuk (Tanpa Dummy)
--}}

@push('styles')
<style>
    /* Wrapper Utama */
    .portal-mbr-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
        width: 100%;
        max-width: 100%;
    }

    /* 1. Filter Bar Portal Style */
    .portal-mbr-filter-bar {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.15rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .portal-mbr-filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }
    .portal-mbr-filter-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-mbr-filter-title i {
        color: var(--brand-primary);
    }
    .portal-mbr-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.85rem;
        align-items: flex-end;
    }
    .portal-mbr-field-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .portal-mbr-field-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .portal-mbr-input, .portal-mbr-select {
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
    .portal-mbr-input:focus, .portal-mbr-select:focus {
        background: #ffffff;
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 3px var(--brand-glow);
    }
    .portal-mbr-btn-submit {
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
    .portal-mbr-btn-submit:hover {
        opacity: 0.92;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--brand-glow);
    }
    .portal-mbr-btn-reset {
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
    .portal-mbr-btn-reset:hover {
        background: #e2e8f0;
        color: var(--text-heading);
    }
    .portal-mbr-btn-export {
        background: #107c41;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 0.6rem 1rem;
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
    .portal-mbr-btn-export:hover {
        background: #0b6333;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 124, 65, 0.35);
    }

    /* 2. Grid KPI Cards (Native Principal Portal Style) */
    .portal-mbr-kpi-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.15rem;
    }
    .portal-mbr-kpi-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.15rem;
    }
    @media (max-width: 1200px) {
        .portal-mbr-kpi-grid-4 { grid-template-columns: repeat(2, 1fr); }
        .portal-mbr-kpi-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .portal-mbr-kpi-grid-4 { grid-template-columns: 1fr; }
        .portal-mbr-kpi-grid-3 { grid-template-columns: 1fr; }
    }

    .portal-mbr-kpi-card {
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
    .portal-mbr-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--border-hover);
    }
    .portal-mbr-kpi-info {
        flex: 1;
        min-width: 0;
    }
    .portal-mbr-kpi-label {
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
    .portal-mbr-kpi-val {
        font-size: 1.65rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.15;
    }
    .portal-mbr-kpi-unit {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-left: 2px;
    }
    .portal-mbr-kpi-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    /* Icon Badges (Pastel Tints Native to Portal) */
    .portal-mbr-icon-badge {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .portal-mbr-icon-badge.brand   { background: var(--brand-light); color: var(--brand-primary); }
    .portal-mbr-icon-badge.emerald { background: #ecfdf5; color: #059669; }
    .portal-mbr-icon-badge.blue    { background: #eff6ff; color: #2563eb; }
    .portal-mbr-icon-badge.orange  { background: #fff7ed; color: #ea580c; }
    .portal-mbr-icon-badge.purple  { background: #f5f3ff; color: #7c3aed; }
    .portal-mbr-icon-badge.indigo  { background: #eef2ff; color: #4f46e5; }
    .portal-mbr-icon-badge.amber   { background: #fef3c7; color: #d97706; }

    /* 3. Section Cards (Standard Portal Styling) */
    .portal-mbr-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.35rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }
    .portal-mbr-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.15rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .portal-mbr-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-mbr-card-title i {
        color: var(--brand-primary);
    }
    .portal-mbr-card-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
    }

    /* Chart Pill Switcher */
    .portal-mbr-chart-switcher {
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        display: inline-flex;
        gap: 4px;
    }
    .portal-mbr-switcher-btn {
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
    .portal-mbr-switcher-btn:hover {
        color: var(--text-heading);
    }
    .portal-mbr-switcher-btn.active {
        background: var(--brand-primary);
        color: #ffffff;
        box-shadow: 0 2px 6px var(--brand-glow);
    }

    /* 4. Grid 2x2 Tables */
    .portal-mbr-table-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 992px) {
        .portal-mbr-table-grid { grid-template-columns: 1fr; }
    }

    /* Tables Native Portal Style */
    .portal-mbr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
    }
    .portal-mbr-table thead th {
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
    .portal-mbr-table thead th.num { text-align: right; }
    .portal-mbr-table tbody td {
        padding: 0.75rem 0.95rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-body);
        vertical-align: middle;
    }
    .portal-mbr-table tbody td.num { text-align: right; font-weight: 700; }
    .portal-mbr-table tbody tr:last-child td {
        border-bottom: none;
    }
    .portal-mbr-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Rank Badge */
    .portal-mbr-rank-badge {
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
    .portal-mbr-rank-badge.top-1 { background: #fef3c7; color: #b45309; }
    .portal-mbr-rank-badge.top-2 { background: #e2e8f0; color: #475569; }
    .portal-mbr-rank-badge.top-3 { background: #ffedd5; color: #c2410c; }

    /* Action Buttons in Table */
    .portal-mbr-btn-action {
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
    .portal-mbr-btn-action:hover {
        background: var(--brand-light);
        border-color: var(--brand-primary);
    }

    /* Status Pills */
    .portal-mbr-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
    }
    .portal-mbr-pill.booth { background: #eff6ff; color: #1d4ed8; }
    .portal-mbr-pill.kasir { background: #f5f3ff; color: #6d28d9; }
    .portal-mbr-pill.valid { background: #dcfce7; color: #15803d; }
    .portal-mbr-pill.warning { background: #fef3c7; color: #b45309; }

    /* Modal Styling Portal Standard */
    .portal-mbr-modal-overlay {
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
    .portal-mbr-modal-overlay.active {
        display: flex;
    }
    .portal-mbr-modal-card {
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
    @keyframes portalModalIn {
        from { opacity: 0; transform: scale(0.96) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .portal-mbr-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
    }
    .portal-mbr-modal-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .portal-mbr-modal-close {
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
    .portal-mbr-modal-close:hover {
        background: #fee2e2;
        color: #ef4444;
    }
    .portal-mbr-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
    }

    /* Photo Grid inside Modal */
    .portal-mbr-photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1rem;
    }
    .portal-mbr-photo-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s ease;
    }
    .portal-mbr-photo-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--brand-primary);
    }
    .portal-mbr-photo-img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        display: block;
        background: #f1f5f9;
    }
    .portal-mbr-photo-meta {
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    /* Lightbox Preview */
    .portal-mbr-lightbox {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.88);
        z-index: 10001;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }
    .portal-mbr-lightbox.active {
        display: flex;
    }
    .portal-mbr-lightbox img {
        max-width: 90vw;
        max-height: 85vh;
        border-radius: 10px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.6);
    }
</style>
@endpush

<div class="portal-mbr-wrapper">

    {{-- 2. KARTU METRIK KPI UTAMA (NATIVE PORTAL CARD STYLE) --}}
    <div class="portal-mbr-kpi-grid-4">
        
        {{-- KPI 1: Total Nilai Omzet --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Total Nilai Penjualan (Omzet)</div>
                <div class="portal-mbr-kpi-val" style="color: #059669; font-size: 1.55rem;">
                    Rp {{ number_format($mbrData['kpis']['total_value_penjualan_rp'], 0, ',', '.') }}
                </div>
                <div class="portal-mbr-kpi-sub">
                    <i class="fa-regular fa-calendar" style="color: var(--brand-primary);"></i>
                    <span>{{ Carbon\Carbon::create($startYear, $startMonth, 1)->translatedFormat('M Y') }} - {{ Carbon\Carbon::create($endYear, $endMonth, 1)->translatedFormat('M Y') }}</span>
                </div>
            </div>
            <div class="portal-mbr-icon-badge emerald">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- KPI 2: Total Qty Fisik Terjual --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Total Qty Penjualan</div>
                <div class="portal-mbr-kpi-val" style="color: #2563eb;">
                    {{ number_format($mbrData['kpis']['total_qty_penjualan']) }}<span class="portal-mbr-kpi-unit">Pcs</span>
                </div>
                <div class="portal-mbr-kpi-sub">
                    <i class="fa-solid fa-box-open" style="color: #2563eb;"></i>
                    <span>Akumulasi fisik seluruh item</span>
                </div>
            </div>
            <div class="portal-mbr-icon-badge blue">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>

        {{-- KPI 3: Total Varian SKU --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Total Varian Produk</div>
                <div class="portal-mbr-kpi-val" style="color: #ea580c;">
                    {{ $mbrData['kpis']['total_produk_penjualan'] }}<span class="portal-mbr-kpi-unit">SKU</span>
                </div>
                <div class="portal-mbr-kpi-sub">
                    <i class="fa-solid fa-tags" style="color: #ea580c;"></i>
                    <span>Varian produk aktif terjual</span>
                </div>
            </div>
            <div class="portal-mbr-icon-badge orange">
                <i class="fa-solid fa-tags"></i>
            </div>
        </div>

        {{-- KPI 4: Penjualan Hari Ini --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Penjualan Hari Ini</div>
                <div class="portal-mbr-kpi-val" style="color: var(--brand-primary);">
                    {{ number_format($mbrData['kpis']['total_penjualan_hari_ini']) }}<span class="portal-mbr-kpi-unit">Pcs</span>
                </div>
                <div class="portal-mbr-kpi-sub">
                    <i class="fa-regular fa-clock" style="color: var(--brand-primary);"></i>
                    <span>{{ Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
                </div>
            </div>
            <div class="portal-mbr-icon-badge brand">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
        </div>

    </div>

    {{-- KARTU METRIK KPI BARIS 2 (METODE BAYAR, MITRA AKTIF, TOKO TERCOVER) --}}
    <div class="portal-mbr-kpi-grid-3">
        
        {{-- KPI 5: Metode Pembayaran --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Metode Pembayaran</div>
                <div style="margin-top: 0.35rem; display: flex; flex-direction: column; gap: 0.25rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 700;">
                        <span style="color: #1d4ed8;"><i class="fa-solid fa-store" style="font-size: 0.75rem;"></i> Bayar Booth:</span>
                        <span style="color: var(--text-heading);">Rp {{ number_format($mbrData['kpis']['total_bayar_di_booth_rp'], 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 700;">
                        <span style="color: #6d28d9;"><i class="fa-solid fa-cash-register" style="font-size: 0.75rem;"></i> Bayar Kasir:</span>
                        <span style="color: var(--text-heading);">Rp {{ number_format($mbrData['kpis']['total_bayar_di_kasir_rp'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="portal-mbr-kpi-sub">
                    <a href="javascript:void(0)" onclick="openGalleryModal('struk')" style="color: var(--brand-primary); text-decoration: none; font-weight: 700;">
                        <i class="fa-solid fa-receipt"></i> Lihat Bukti Foto Struk
                    </a>
                </div>
            </div>
            <div class="portal-mbr-icon-badge purple">
                <i class="fa-solid fa-cash-register"></i>
            </div>
        </div>

        {{-- KPI 6: Mitra Aktif Penjualan --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Mitra Penjualan (SPG/MD)</div>
                <div class="portal-mbr-kpi-val" style="color: #4f46e5;">
                    {{ count($mbrData['top_mitra'] ?? []) }}<span class="portal-mbr-kpi-unit">Mitra</span>
                </div>
                <div class="portal-mbr-kpi-sub">
                    <i class="fa-solid fa-calculator" style="color: #4f46e5;"></i>
                    <span>Rata-rata: Rp {{ count($mbrData['top_mitra'] ?? []) > 0 ? number_format($mbrData['kpis']['total_value_penjualan_rp'] / count($mbrData['top_mitra']), 0, ',', '.') : '0' }} / mitra</span>
                </div>
            </div>
            <div class="portal-mbr-icon-badge indigo">
                <i class="fa-solid fa-user-tie"></i>
            </div>
        </div>

        {{-- KPI 7: Total Submisi & Toko Tercover --}}
        <div class="portal-mbr-kpi-card">
            <div class="portal-mbr-kpi-info">
                <div class="portal-mbr-kpi-label">Total Laporan Submisi</div>
                <div class="portal-mbr-kpi-val" style="color: var(--text-heading);">
                    {{ number_format($mbrData['kpis']['total_submissions']) }}<span class="portal-mbr-kpi-unit">Laporan</span>
                </div>
                <div class="portal-mbr-kpi-sub">
                    <i class="fa-solid fa-shop" style="color: #d97706;"></i>
                    <span>Tersebar di <strong>{{ $mbrData['kpis']['unique_stores'] }}</strong> Toko & Outlet</span>
                </div>
            </div>
            <div class="portal-mbr-icon-badge amber">
                <i class="fa-solid fa-store"></i>
            </div>
        </div>

    </div>

    {{-- 3. GRAFIK PENJUALAN DINAMIS (NATIVE PORTAL CARD) --}}
    <div class="portal-mbr-card">
        <div class="portal-mbr-card-header">
            <div>
                <div class="portal-mbr-card-title">
                    <i class="fa-solid fa-chart-column"></i>
                    <span>Tren Penjualan Produk Event MBR</span>
                </div>
                <div class="portal-mbr-card-sub">Grafik kuantiti penjualan aktual berdasarkan periode terpilih</div>
            </div>

            <div class="portal-mbr-chart-switcher">
                <button type="button" class="portal-mbr-switcher-btn active" id="btn_mode_daily" onclick="switchChartMode('daily')">Harian</button>
                <button type="button" class="portal-mbr-switcher-btn" id="btn_mode_weekly" onclick="switchChartMode('weekly')">Mingguan</button>
                <button type="button" class="portal-mbr-switcher-btn" id="btn_mode_monthly" onclick="switchChartMode('monthly')">Bulanan</button>
            </div>
        </div>

        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="mbrSalesChart"></canvas>
        </div>
    </div>

    {{-- 4. GRID 2X2 TABEL PERFORMA (NATIVE PORTAL WHITE CARDS) --}}
    <div class="portal-mbr-table-grid">
        
        {{-- Tabel 1: Top 5 Mitra Penjualan --}}
        <div class="portal-mbr-card" id="table_top_mitra">
            <div class="portal-mbr-card-header">
                <div>
                    <div class="portal-mbr-card-title">
                        <i class="fa-solid fa-trophy" style="color: #eab308;"></i>
                        <span>Top 5 Mitra Penjualan</span>
                    </div>
                    <div class="portal-mbr-card-sub">Peringkat kontribusi penjualan tertinggi oleh SPG/MD</div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="portal-mbr-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Nama Petugas (Mitra)</th>
                            <th>Daerah / Cabang</th>
                            <th class="num">Total Qty</th>
                            <th style="width: 70px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mbrData['top_mitra'] as $idx => $m)
                            <tr>
                                <td style="text-align: center;">
                                    <span class="portal-mbr-rank-badge {{ $idx == 0 ? 'top-1' : ($idx == 1 ? 'top-2' : ($idx == 2 ? 'top-3' : '')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">{{ $m['name'] }}</div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted);">Rp {{ number_format($m['value'], 0, ',', '.') }}</div>
                                </td>
                                <td style="color: var(--text-muted); font-weight: 600;">{{ $m['area'] }}</td>
                                <td class="num" style="color: #2563eb;">{{ number_format($m['qty']) }} Pcs</td>
                                <td style="text-align: center;">
                                    <button type="button" onclick='openMbrBreakdownModal("mitra", "{{ addslashes($m['name']) }}", "{{ addslashes($m['area']) }}", @json($m['products'] ?? []))' class="portal-mbr-btn-action">
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                    Belum ada data mitra pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 2: Top 5 Produk Terlaris --}}
        <div class="portal-mbr-card" id="table_top_products">
            <div class="portal-mbr-card-header">
                <div>
                    <div class="portal-mbr-card-title">
                        <i class="fa-solid fa-medal" style="color: #6366f1;"></i>
                        <span>Top 5 Produk Terlaris</span>
                    </div>
                    <div class="portal-mbr-card-sub">Varian SKU produk dengan volume penjualan tertinggi</div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="portal-mbr-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Nama Produk</th>
                            <th class="num">Total Qty</th>
                            <th class="num">Nilai (Rp)</th>
                            <th style="width: 70px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mbrData['top_products'] as $idx => $p)
                            <tr>
                                <td style="text-align: center;">
                                    <span class="portal-mbr-rank-badge {{ $idx == 0 ? 'top-1' : ($idx == 1 ? 'top-2' : ($idx == 2 ? 'top-3' : '')) }}">
                                        {{ $idx + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">{{ $p['name'] }}</div>
                                    @if(!empty($p['sku']) && $p['sku'] !== '-')
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">{{ $p['sku'] }}</div>
                                    @endif
                                </td>
                                <td class="num" style="color: #2563eb;">{{ number_format($p['qty']) }} Pcs</td>
                                <td class="num" style="color: #059669;">Rp {{ number_format($p['value'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" onclick='openMbrBreakdownModal("product", "{{ addslashes($p['name']) }}", "{{ addslashes($p['sku'] ?? '') }}", @json($p['breakdown'] ?? []))' class="portal-mbr-btn-action">
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                    Belum ada data produk pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 3: Penjualan Per Daerah / Cabang --}}
        <div class="portal-mbr-card" id="table_sales_area">
            <div class="portal-mbr-card-header">
                <div>
                    <div class="portal-mbr-card-title">
                        <i class="fa-solid fa-map-location-dot" style="color: #0d9488;"></i>
                        <span>Penjualan Per Daerah / Cabang</span>
                    </div>
                    <div class="portal-mbr-card-sub">Distribusi volume penjualan di tiap area cabang</div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="portal-mbr-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Daerah / Cabang</th>
                            <th style="text-align: center;">Toko Tercover</th>
                            <th class="num">Total Qty</th>
                            <th class="num">Nilai (Rp)</th>
                            <th style="width: 70px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mbrData['sales_by_area'] as $idx => $a)
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td style="font-weight: 700; color: var(--text-heading);">{{ $a['area'] }}</td>
                                <td style="text-align: center;">
                                    <span class="portal-mbr-pill" style="background: #f1f5f9; color: var(--text-body);">
                                        {{ $a['store_count'] }} Toko
                                    </span>
                                </td>
                                <td class="num" style="color: #2563eb;">{{ number_format($a['qty']) }} Pcs</td>
                                <td class="num" style="color: #059669;">Rp {{ number_format($a['value'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" onclick='openMbrBreakdownModal("area", "{{ addslashes($a['area']) }}", "", @json($a['breakdown'] ?? []))' class="portal-mbr-btn-action">
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                    Belum ada data daerah pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 4: Penjualan Per Wilayah (Region) --}}
        <div class="portal-mbr-card" id="table_sales_region">
            <div class="portal-mbr-card-header">
                <div>
                    <div class="portal-mbr-card-title">
                        <i class="fa-solid fa-earth-asia" style="color: var(--brand-primary);"></i>
                        <span>Penjualan Per Wilayah (Region)</span>
                    </div>
                    <div class="portal-mbr-card-sub">Agregasi performa penjualan tingkat regional</div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="portal-mbr-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Wilayah (Region)</th>
                            <th style="text-align: center;">Jumlah Daerah</th>
                            <th class="num">Total Qty</th>
                            <th class="num">Nilai (Rp)</th>
                            <th style="width: 70px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mbrData['sales_by_region'] as $idx => $r)
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td style="font-weight: 700; color: var(--text-heading);">{{ $r['region'] }}</td>
                                <td style="text-align: center;">
                                    <span class="portal-mbr-pill" style="background: #f1f5f9; color: var(--text-body);">
                                        {{ $r['area_count'] }} Daerah
                                    </span>
                                </td>
                                <td class="num" style="color: #2563eb;">{{ number_format($r['qty']) }} Pcs</td>
                                <td class="num" style="color: #059669;">Rp {{ number_format($r['value'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <button type="button" onclick='openMbrBreakdownModal("region", "{{ addslashes($r['region']) }}", "", @json($r['breakdown'] ?? []))' class="portal-mbr-btn-action">
                                        <i class="fa-solid fa-list-ul"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                    Belum ada data wilayah pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- 5. TABEL RINCIAN TRANSAKSI SUBMISI AKTUAL (LIVE LIST) --}}
    <div class="portal-mbr-card" id="table_submissions">
        <div class="portal-mbr-card-header">
            <div>
                <div class="portal-mbr-card-title">
                    <i class="fa-solid fa-table-list"></i>
                    <span>Rincian Transaksi Submission (Live Submissions)</span>
                </div>
                <div class="portal-mbr-card-sub">
                    Menampilkan <strong>{{ $submissions->count() }}</strong> dari <strong>{{ $submissions->total() }}</strong> total laporan masuk
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="button" onclick="openGalleryModal('all')" class="portal-mbr-btn-action" style="padding: 0.5rem 1rem; background: var(--brand-gradient); color: #ffffff; border: none; box-shadow: 0 2px 8px var(--brand-glow);">
                    <i class="fa-solid fa-images"></i> Galeri Dokumentasi ({{ count($mbrData['gallery_photos']) }})
                </button>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="portal-mbr-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th>Waktu Submit</th>
                        <th>Petugas (Mitra)</th>
                        <th>Toko / Lokasi</th>
                        <th>Ringkasan Keranjang (Cart)</th>
                        <th class="num">Total Qty</th>
                        <th class="num">Nilai (Rp)</th>
                        <th style="text-align: center;">Pembayaran</th>
                        <th style="width: 90px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $idx => $sub)
                        @php
                            $subDateDisplay = $sub->submitted_at ? $sub->submitted_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' : ($sub->created_at ? $sub->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' : '-');
                            $empName = $sub->employee ? ($sub->employee->full_name ?: $sub->employee->name) : 'Petugas';
                            $empNik = $sub->employee?->nik ?? ($sub->employee?->employee_no ?? '-');
                            $storeName = $sub->workLocation ? $sub->workLocation->name : ($sub->store_name ?: 'Toko / Outlet');
                            $storeArea = $sub->workLocation && $sub->workLocation->branch ? $sub->workLocation->branch->name : ($sub->employee && $sub->employee->branch ? $sub->employee->branch->name : '-');
                            
                            $cart = [];
                            $subQty = 0;
                            $subVal = 0;
                            $subBooth = 0;
                            $subKasir = 0;
                            $sellOutPhoto = null;
                            $subPhotos = [];
                            $statusPenjualan = null;
                            $alasanNoSellOut = null;
                            $keteranganNoSellOut = null;

                            foreach($sub->values as $v) {
                                $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                                $ft = strtolower(trim((string)($v->field_type ?: ($v->formField ? $v->formField->field_type : ''))));

                                if ($fn === 'mbr_sales_items_json') {
                                    $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                                    if (is_array($raw)) $cart = $raw;
                                } elseif ($fn === 'total_qty_penjualan') {
                                    $subQty = (int)($v->value_number ?? 0);
                                } elseif ($fn === 'total_value_penjualan_rp') {
                                    $subVal = (float)($v->value_number ?? 0);
                                } elseif ($fn === 'total_bayar_di_booth_rp') {
                                    $subBooth = (float)($v->value_number ?? 0);
                                } elseif ($fn === 'total_bayar_di_kasir_rp') {
                                    $subKasir = (float)($v->value_number ?? 0);
                                } elseif ($fn === 'status_penjualan') {
                                    $statusPenjualan = trim((string)($v->value_text ?: (is_array($v->value_json) ? ($v->value_json[0] ?? '') : '')));
                                } elseif ($fn === 'alasan_no_sell_out') {
                                    $alasanNoSellOut = trim((string)($v->value_text ?: (is_array($v->value_json) ? ($v->value_json[0] ?? '') : '')));
                                } elseif (in_array($fn, ['keterangan_no_sell_out', 'catatan', 'catatan_penjualan', 'keterangan'])) {
                                    $candKeterangan = trim((string)($v->value_text ?: ''));
                                    if (!empty($candKeterangan)) {
                                        $keteranganNoSellOut = $candKeterangan;
                                    }
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
                                            if (!$sellOutPhoto && (str_contains($fn, 'sell_out') || str_contains($fn, 'foto') || str_contains($fn, 'photo'))) {
                                                $sellOutPhoto = $pUrl;
                                            }
                                        }
                                    }
                                }
                            }

                            // Deteksi No Sell Out dan fallback alasan jika belum terisi
                            $isNoSellOut = (strcasecmp($statusPenjualan ?? '', 'No Sell Out') === 0) 
                                || !empty($alasanNoSellOut) 
                                || ($subQty == 0 && $subVal == 0 && empty($cart));

                            if ($isNoSellOut && empty($alasanNoSellOut)) {
                                foreach($sub->values as $v) {
                                    $vText = (string)($v->value_text ?? '');
                                    if (stripos($vText, 'Toko Tidak Mengijinkan') !== false) {
                                        $alasanNoSellOut = 'Toko Tidak Mengijinkan';
                                        break;
                                    } elseif (stripos($vText, 'Barang OOS') !== false || stripos($vText, 'OOS') !== false) {
                                        $alasanNoSellOut = 'Barang OOS';
                                        break;
                                    }
                                }
                                if (empty($alasanNoSellOut)) {
                                    $alasanNoSellOut = 'Toko Tidak Mengijinkan';
                                }
                            }

                            // Fallback jika foto tidak tercatat di submission values tapi tersimpan di disk
                            if (empty($subPhotos)) {
                                $matches = glob(storage_path("app/public/reports/*/report_{$sub->id}_*.jpg"));
                                if (!empty($matches)) {
                                    foreach ($matches as $match) {
                                        $rel = str_replace(storage_path('app/public/'), '', $match);
                                        $rel = str_replace('\\', '/', $rel);
                                        $pUrl = asset('storage/' . ltrim($rel, '/'));
                                        $subPhotos[] = [
                                            'label' => 'Foto Dokumentasi Laporan',
                                            'url' => $pUrl,
                                            'field_name' => 'foto_dokumentasi',
                                        ];
                                        if (!$sellOutPhoto) $sellOutPhoto = $pUrl;
                                    }
                                }
                            }
                            if (!$sellOutPhoto && !empty($subPhotos)) {
                                $sellOutPhoto = $subPhotos[0]['url'];
                            }

                            // Normalisasi foto struk & nama produk dalam item cart
                            if (!empty($cart)) {
                                $calcQ = 0; $calcV = 0;
                                foreach($cart as &$item) {
                                    $item['product_name'] = !empty($item['product_name']) ? $item['product_name'] : (!empty($item['name']) ? $item['name'] : (!empty($item['nama_produk']) ? $item['nama_produk'] : 'Produk Wings'));
                                    $q = (int)($item['qty'] ?? 1);
                                    $pr = (float)($item['store_price'] ?? ($item['price'] ?? 0));
                                    $v = (float)($item['value_rp'] ?? ($q * $pr));
                                    $calcQ += $q;
                                    $calcV += $v;

                                    $rawStruk = $item['struk_photo_url'] ?? ($item['struk_photo_path'] ?? ($item['foto_struk'] ?? ($item['photo_struk_url'] ?? null)));
                                    if ($rawStruk && is_string($rawStruk)) {
                                        $cleanS = trim($rawStruk);
                                        if (!str_starts_with($cleanS, '/data/user/') && !str_starts_with($cleanS, 'data/user/')) {
                                            $sUrl = (str_starts_with($cleanS, 'http://') || str_starts_with($cleanS, 'https://'))
                                                ? str_replace(['/storage/storage/', 'esa-solution.id'], ['/storage/', 'esa-solutions.id'], $cleanS)
                                                : asset('storage/' . ltrim(str_replace(['/storage/', 'storage/'], '', $cleanS), '/'));
                                            $item['struk_photo_url'] = $sUrl;
                                            $item['foto_struk'] = $sUrl;
                                        }
                                    }
                                }
                                unset($item);

                                if ($subQty <= 0) $subQty = $calcQ;
                                if ($subVal <= 0) $subVal = $calcV;
                            }

                            $noSellOutMeta = [
                                'is_no_sell_out' => $isNoSellOut,
                                'alasan' => $alasanNoSellOut ?: ($isNoSellOut ? 'Toko Tidak Mengijinkan' : ''),
                                'keterangan' => $keteranganNoSellOut ?: '',
                            ];
                        @endphp
                        <tr>
                            <td style="text-align: center; color: var(--text-muted); font-weight: 700;">
                                {{ $submissions->firstItem() + $idx }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $subDateDisplay }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">{{ $sub->submission_code }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $empName }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-muted);">NIK: {{ $empNik }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $storeName }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-muted);">{{ $storeArea }}</div>
                            </td>
                            <td>
                                @if(!empty($cart))
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        @foreach(array_slice($cart, 0, 2) as $cItem)
                                            @php
                                                $itemName = $cItem['product_name'] ?? ($cItem['name'] ?? ($cItem['nama_produk'] ?? 'Produk'));
                                                $itemQty = (int)($cItem['qty'] ?? 1);
                                                $itemPrice = (float)($cItem['store_price'] ?? ($cItem['price'] ?? 0));
                                            @endphp
                                            <div style="font-size: 0.74rem; color: var(--text-body); line-height: 1.35;">
                                                <div style="font-weight: 700; color: var(--text-heading);">&bull; {{ $itemName }}</div>
                                                <div style="display: flex; align-items: center; gap: 6px; padding-left: 8px; font-size: 0.72rem; margin-top: 1px;">
                                                    <span style="color: #2563eb; font-weight: 600;">{{ $itemQty }} pcs</span>
                                                    <span style="color: var(--text-muted);">&bull;</span>
                                                    <span style="color: #059669; font-weight: 700; background: #ecfdf5; padding: 1px 6px; border-radius: 4px; border: 1px solid #a7f3d0;" title="Harga Toko per pcs">
                                                        Rp {{ number_format($itemPrice, 0, ',', '.') }}/pcs
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if(count($cart) > 2)
                                            <span style="font-size: 0.72rem; color: var(--brand-primary); font-weight: 700; padding-left: 8px; cursor: pointer; text-decoration: underline;" onclick='openSubmissionDetailModal(@json($sub), @json($cart), @json($subPhotos), "{{ $sellOutPhoto }}", "{{ $subDateDisplay }}", @json($noSellOutMeta))'>
                                                +{{ count($cart) - 2 }} produk lainnya (Lihat Detail)
                                            </span>
                                        @endif
                                    </div>
                                @elseif($isNoSellOut)
                                    <div style="display: flex; flex-direction: column; gap: 3px;">
                                        <span class="portal-mbr-pill" style="background: #fee2e2; color: #dc2626; border-color: #fca5a5; font-weight: 800; font-size: 0.70rem; width: fit-content; padding: 2px 7px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-ban"></i> No Sell Out
                                        </span>
                                        <div style="font-size: 0.76rem; font-weight: 700; color: #991b1b; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid {{ stripos($alasanNoSellOut, 'OOS') !== false ? 'fa-box-open' : 'fa-store-slash' }}"></i>
                                            <span>{{ $alasanNoSellOut }}</span>
                                        </div>
                                        @if(!empty($keteranganNoSellOut))
                                            <div style="font-size: 0.70rem; color: var(--text-muted); font-style: italic; line-height: 1.25;" title="{{ $keteranganNoSellOut }}">
                                                "{{ Str::limit($keteranganNoSellOut, 35) }}"
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td class="num" style="color: #2563eb; font-weight: 700;">{{ number_format($subQty) }} Pcs</td>
                            <td class="num" style="color: #059669; font-weight: 700;">Rp {{ number_format($subVal, 0, ',', '.') }}</td>
                            <td style="text-align: center;">
                                @if($isNoSellOut)
                                    <span class="portal-mbr-pill" style="background: #f1f5f9; color: #64748b; border-color: #cbd5e1; font-size: 0.72rem; font-weight: 700;">
                                        <i class="fa-solid fa-ban"></i> No Sell Out
                                    </span>
                                @elseif($subKasir > 0 && $subBooth <= 0)
                                    <span class="portal-mbr-pill kasir"><i class="fa-solid fa-cash-register"></i> Kasir</span>
                                @elseif($subBooth > 0 && $subKasir <= 0)
                                    <span class="portal-mbr-pill booth"><i class="fa-solid fa-store"></i> Booth</span>
                                @elseif($subKasir > 0 && $subBooth > 0)
                                    <span class="portal-mbr-pill booth"><i class="fa-solid fa-coins"></i> Campuran</span>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                @if($sellOutPhoto)
                                    <button type="button" onclick="openLightbox('{{ $sellOutPhoto }}')" class="portal-mbr-btn-action" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe; margin-right: 4px;" title="Lihat Foto Dokumentasi">
                                        <i class="fa-solid fa-camera"></i> Foto
                                    </button>
                                @endif
                                <button type="button" onclick='openSubmissionDetailModal(@json($sub), @json($cart), @json($subPhotos), "{{ $sellOutPhoto }}", "{{ $subDateDisplay }}", @json($noSellOutMeta))' class="portal-mbr-btn-action">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                <div style="font-weight: 700; color: var(--text-heading); font-size: 1rem;">Belum Ada Data Laporan Masuk</div>
                                <div style="font-size: 0.82rem; margin-top: 0.35rem;">Data transaksi submission untuk periode ini akan otomatis muncul saat petugas SPG/MD mengirimkan laporan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div style="padding-top: 1.25rem; margin-top: 1rem; border-top: 1px solid var(--border-color);">
                {{ $submissions->links('portal.pagination') }}
            </div>
        @endif
    </div>

</div>

{{-- MODAL 1: GALERI DOKUMENTASI STRUK & SELL OUT --}}
<div class="portal-mbr-modal-overlay" id="galleryModal" onclick="closeGalleryModal()">
    <div class="portal-mbr-modal-card" onclick="event.stopPropagation()">
        <div class="portal-mbr-modal-header">
            <div class="portal-mbr-modal-title">
                <i class="fa-solid fa-images" style="color: var(--brand-primary);"></i>
                <span id="galleryModalTitle">Galeri Dokumentasi Foto Penjualan Event MBR</span>
            </div>
            <button type="button" class="portal-mbr-modal-close" onclick="closeGalleryModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-mbr-modal-body">
            <div class="portal-mbr-photo-grid" id="galleryGridContainer">
                @forelse($mbrData['gallery_photos'] as $gp)
                    <div class="portal-mbr-photo-card" data-type="{{ $gp['type'] }}">
                        <img src="{{ $gp['url'] }}" alt="{{ $gp['title'] }}" class="portal-mbr-photo-img" onclick="openLightbox('{{ $gp['url'] }}')">
                        <div class="portal-mbr-photo-meta">
                            <span class="portal-mbr-pill {{ $gp['type'] === 'struk' ? 'kasir' : 'booth' }}" style="align-self: flex-start;">
                                {{ $gp['type'] === 'struk' ? 'Struk Penjualan' : 'Display Sell Out' }}
                            </span>
                            <div style="font-weight: 700; font-size: 0.82rem; color: var(--text-heading); margin-top: 2px;">{{ $gp['mitra'] }}</div>
                            <div style="font-size: 0.74rem; color: var(--text-muted);">📍 {{ $gp['store'] }}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">🗓️ {{ $gp['date'] }}</div>
                            @if(!empty($gp['product']))
                                <div style="font-size: 0.74rem; color: var(--brand-primary); font-weight: 700;">{{ $gp['product'] }} ({{ $gp['qty'] }} pcs)</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        <i class="fa-solid fa-images" style="font-size: 2.5rem; display: block; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                        <div style="font-weight: 700; color: var(--text-heading); font-size: 1rem;">Belum Ada Foto Dokumentasi Masuk</div>
                        <p style="font-size: 0.82rem; margin-top: 0.35rem;">Foto bukti struk dan display sell out akan otomatis terkumpul di sini saat petugas mengirimkan laporan melalui aplikasi mobile.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2: RINCIAN LENGKAP TRANSAKSI (SUBMISSION CART) --}}
<div class="portal-mbr-modal-overlay" id="submissionDetailModal" onclick="closeSubmissionDetailModal()">
    <div class="portal-mbr-modal-card" onclick="event.stopPropagation()">
        <div class="portal-mbr-modal-header">
            <div class="portal-mbr-modal-title">
                <i class="fa-solid fa-basket-shopping" style="color: var(--brand-primary);"></i>
                <span id="subModalTitle">Rincian Transaksi Submission</span>
            </div>
            <button type="button" class="portal-mbr-modal-close" onclick="closeSubmissionDetailModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-mbr-modal-body" id="subModalBody">
            {{-- Injected dynamically --}}
        </div>
    </div>
</div>

{{-- MODAL 3: MODAL DETAIL UMUM --}}
<div class="portal-mbr-modal-overlay" id="genericDetailModal" onclick="closeGenericModal()">
    <div class="portal-mbr-modal-card" style="max-width: 480px;" onclick="event.stopPropagation()">
        <div class="portal-mbr-modal-header">
            <div class="portal-mbr-modal-title">
                <i class="fa-solid fa-circle-info" style="color: var(--brand-primary);"></i>
                <span id="genericModalTitle">Informasi Detail</span>
            </div>
            <button type="button" class="portal-mbr-modal-close" onclick="closeGenericModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-mbr-modal-body">
            <p id="genericModalContent" style="font-size: 0.95rem; color: var(--text-body); line-height: 1.6; margin: 0;"></p>
        </div>
    </div>
</div>

{{-- MODAL 4: MODAL RINCIAN DETAIL DRILLDOWN (TOP MITRA, TOP PRODUK, DAERAH, WILAYAH) --}}
<div class="portal-mbr-modal-overlay" id="mbrBreakdownModal" onclick="closeMbrBreakdownModal()">
    <div class="portal-mbr-modal-card" style="max-width: 860px;" onclick="event.stopPropagation()">
        <div class="portal-mbr-modal-header">
            <div class="portal-mbr-modal-title" id="mbrBreakdownModalTitle">
                <i class="fa-solid fa-list-ul" style="color: var(--brand-primary);"></i>
                <span>Rincian Detail</span>
            </div>
            <button type="button" class="portal-mbr-modal-close" onclick="closeMbrBreakdownModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="portal-mbr-modal-body" id="mbrBreakdownModalBody">
            {{-- Injected dynamically --}}
        </div>
    </div>
</div>

{{-- LIGHTBOX ZOOM PREVIEW --}}
<div class="portal-mbr-lightbox" id="lightboxOverlay" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="Perbesar Foto" onclick="event.stopPropagation()">
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let mbrChartInstance = null;
    const chartData = {
        daily: @json($mbrData['chart']['daily'] ?? ['labels' => [], 'qtys' => []]),
        weekly: @json($mbrData['chart']['weekly'] ?? ['labels' => [], 'qtys' => []]),
        monthly: @json($mbrData['chart']['monthly'] ?? ['labels' => [], 'qtys' => []])
    };

    // Brand Primary Color from Portal
    const portalBrandPrimary = '{{ $brandColor }}';

    function initMbrSalesChart() {
        const ctx = document.getElementById('mbrSalesChart');
        if (!ctx) return;

        if (mbrChartInstance) {
            mbrChartInstance.destroy();
        }

        const initialLabels = chartData.daily.labels || [];
        const initialQtys = chartData.daily.qtys || [];

        mbrChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: initialLabels,
                datasets: [{
                    label: 'Volume Penjualan (Pcs)',
                    data: initialQtys,
                    backgroundColor: portalBrandPrimary,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return ' Penjualan: ' + Number(context.raw).toLocaleString('id-ID') + ' Pcs';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 11 },
                            color: '#64748b',
                            callback: function(val) { return Number(val).toLocaleString('id-ID'); }
                        }
                    }
                }
            }
        });
    }

    function switchChartMode(mode) {
        document.getElementById('btn_mode_daily')?.classList.remove('active');
        document.getElementById('btn_mode_weekly')?.classList.remove('active');
        document.getElementById('btn_mode_monthly')?.classList.remove('active');
        document.getElementById('btn_mode_' + mode)?.classList.add('active');

        if (!mbrChartInstance || !chartData[mode]) return;

        mbrChartInstance.data.labels = chartData[mode].labels || [];
        mbrChartInstance.data.datasets[0].data = chartData[mode].qtys || [];
        mbrChartInstance.update();
    }

    // Modal Galeri
    function openGalleryModal(filterType = 'all') {
        const modal = document.getElementById('galleryModal');
        if (!modal) return;
        modal.classList.add('active');

        const titleEl = document.getElementById('galleryModalTitle');
        if (filterType === 'struk') {
            if (titleEl) titleEl.innerText = 'Dokumentasi Foto Struk Penjualan Kasir';
        } else {
            if (titleEl) titleEl.innerText = 'Galeri Dokumentasi Foto Penjualan Event MBR';
        }
    }

    function closeGalleryModal() {
        document.getElementById('galleryModal')?.classList.remove('active');
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

    // Modal Rincian Cart Submission
    function openSubmissionDetailModal(sub, cart, photos, sellOutPhoto, formattedTime, noSellOutMeta) {
        const modal = document.getElementById('submissionDetailModal');
        const body = document.getElementById('subModalBody');
        if (!modal || !body) return;

        photos = photos || [];
        cart = cart || [];
        if (!sellOutPhoto && photos.length > 0) {
            sellOutPhoto = photos[0].url;
        }

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

        // Deteksi status No Sell Out & alasan
        let isNoSellOut = Boolean(noSellOutMeta?.is_no_sell_out);
        let alasanNoSellOut = noSellOutMeta?.alasan || '';
        let keteranganNoSellOut = noSellOutMeta?.keterangan || '';

        if (!isNoSellOut && sub?.values && Array.isArray(sub.values)) {
            sub.values.forEach(v => {
                const fn = (v.field_name || (v.form_field ? v.form_field.field_name : '') || '').toLowerCase();
                const vt = String(v.value_text || '');
                if (fn === 'status_penjualan' && vt.toLowerCase().includes('no sell out')) {
                    isNoSellOut = true;
                }
                if (fn === 'alasan_no_sell_out' && vt) {
                    alasanNoSellOut = vt;
                    isNoSellOut = true;
                }
                if (['keterangan_no_sell_out', 'catatan', 'catatan_penjualan', 'keterangan'].includes(fn) && vt && !keteranganNoSellOut) {
                    keteranganNoSellOut = vt;
                }
            });
        }

        if (!isNoSellOut && cart.length === 0 && Number(sub?.total_qty_penjualan || 0) === 0) {
            isNoSellOut = true;
            if (!alasanNoSellOut) alasanNoSellOut = 'Toko Tidak Mengijinkan';
        }

        let noSellOutSectionHtml = '';
        if (isNoSellOut) {
            const isOos = (alasanNoSellOut || '').toLowerCase().includes('oos');
            noSellOutSectionHtml = `
                <div style="margin-top: 1.25rem; background: #fff5f5; border: 2px solid #fecaca; border-radius: 12px; padding: 1.15rem 1.25rem; box-shadow: 0 2px 6px rgba(220,38,38,0.06);">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; border: 1px solid #fca5a5;">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                                <span class="portal-mbr-pill" style="background: #dc2626; color: #ffffff; border-color: #b91c1c; font-weight: 800; font-size: 0.72rem; padding: 2px 8px;">
                                    <i class="fa-solid fa-ban"></i> STATUS: NO SELL OUT
                                </span>
                                <span style="font-size: 0.85rem; font-weight: 800; color: #991b1b;">
                                    (Tidak Ada Transaksi Penjualan)
                                </span>
                            </div>
                            <div style="font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span style="color: #64748b; font-weight: 600; font-size: 0.82rem;">Alasan Kendala di Lapangan:</span>
                                <span style="background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 6px; border: 1px solid #fca5a5; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid ${isOos ? 'fa-box-open' : 'fa-store-slash'}"></i>
                                    ${alasanNoSellOut || 'Toko Tidak Mengijinkan'}
                                </span>
                            </div>
                            ${keteranganNoSellOut ? `
                                <div style="margin-top: 8px; font-size: 0.82rem; color: #334155; background: #ffffff; padding: 8px 12px; border-radius: 8px; border: 1px dashed #fca5a5; line-height: 1.4;">
                                    <strong style="color: #64748b; display: block; font-size: 0.72rem; text-transform: uppercase;">Keterangan / Catatan Petugas:</strong>
                                    "${keteranganNoSellOut}"
                                </div>
                            ` : ''}
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 8px; line-height: 1.35;">
                                <i class="fa-solid fa-circle-info me-1" style="color: #0284c7;"></i>
                                Petugas SPG/MD melaporkan tidak ada transaksi penjualan fisik pada kunjungan toko ini dikarenakan kendala izin outlet atau ketiadaan stok barang (OOS).
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        let cartHtml = '';
        if (cart && cart.length > 0) {
            cartHtml = `
                <div style="overflow-x: auto;">
                    <table class="portal-mbr-table" style="margin-top: 0.75rem; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Produk</th>
                                <th class="num">Harga Toko / Pcs (Rp)</th>
                                <th class="num">Qty</th>
                                <th class="num">Subtotal (Rp)</th>
                                <th style="text-align: center;">Metode Bayar</th>
                                <th style="text-align: center; width: 90px;">Foto Struk</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cart.map((item, i) => {
                                const rawPhoto = item.struk_photo_url || item.foto_struk || item.struk_photo_path || item.photo_struk_url || null;
                                const strukUrl = rawPhoto ? resolveUrl(rawPhoto) : null;
                                const pName = item.product_name || item.name || item.nama_produk || 'Produk Wings';
                                const pSku = item.sku_code || item.sku || '-';
                                return `
                                    <tr>
                                        <td style="color: var(--text-muted); font-weight: 700; text-align: center;">${i + 1}</td>
                                        <td>
                                            <div style="font-weight: 800; color: var(--text-heading);">${pName}</div>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">${pSku}</div>
                                        </td>
                                        <td class="num" style="font-weight: 700; color: #0f172a;">Rp ${Number(item.store_price || item.price || 0).toLocaleString('id-ID')}</td>
                                        <td class="num" style="color: #2563eb; font-weight: 800;">${Number(item.qty || 1).toLocaleString('id-ID')} Pcs</td>
                                        <td class="num" style="color: #059669; font-weight: 800;">Rp ${Number(item.value_rp || (item.qty * (item.store_price || item.price || 0)) || 0).toLocaleString('id-ID')}</td>
                                        <td style="text-align: center;">
                                            <span class="portal-mbr-pill ${String(item.payment_type || '').toLowerCase().includes('kasir') ? 'kasir' : 'booth'}">
                                                <i class="fa-solid ${String(item.payment_type || '').toLowerCase().includes('kasir') ? 'fa-cash-register' : 'fa-store'}"></i>
                                                ${String(item.payment_type || '').toLowerCase().includes('kasir') ? 'Kasir' : 'Booth'}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            ${strukUrl ? `
                                                <button type="button" onclick="openLightbox('${strukUrl}')" class="portal-mbr-btn-action" style="background: #fef2f2; color: #dc2626; border-color: #fecaca; padding: 4px 8px; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat Struk ${pName}">
                                                    <i class="fa-solid fa-receipt"></i> Struk
                                                </button>
                                            ` : '<span style="color: var(--text-muted); font-size: 0.75rem;">-</span>'}
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (isNoSellOut) {
            cartHtml = `
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 1.25rem 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: 0.75rem;">
                    <i class="fa-solid fa-box-open" style="font-size: 1.75rem; color: #94a3b8; display: block; margin-bottom: 0.5rem;"></i>
                    <div style="font-weight: 800; color: var(--text-heading); font-size: 0.95rem;">Tidak Ada Item Keranjang Belanja</div>
                    <div style="font-size: 0.82rem; margin-top: 4px; color: #64748b;">
                        Laporan ini disubmit sebagai <strong>No Sell Out</strong> dengan kendala: 
                        <span style="color: #991b1b; font-weight: 700;">${alasanNoSellOut || 'Toko Tidak Mengijinkan'}</span>
                    </div>
                </div>
            `;
        } else {
            cartHtml = '<p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.75rem;">Tidak ada item keranjang terperinci.</p>';
        }

        // Section Dokumentasi Foto Laporan
        let photoSectionHtml = '';
        const mainPhoto = sellOutPhoto ? resolveUrl(sellOutPhoto) : (photos.length > 0 ? resolveUrl(photos[0].url) : null);
        
        if (mainPhoto || photos.length > 0) {
            photoSectionHtml = `
                <div style="margin-top: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.65rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-heading); margin: 0; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-camera" style="color: var(--brand-primary);"></i>
                            Foto Dokumentasi Laporan (Sell Out Toko / Display)
                        </h4>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><i class="fa-solid fa-magnifying-glass-plus"></i> Klik foto untuk memperbesar</span>
                    </div>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        ${mainPhoto ? `
                            <div style="position: relative; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; width: 100%; max-width: 300px; background: #0f172a; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                                <img src="${mainPhoto}" alt="Dokumentasi Sell Out Toko" onclick="openLightbox('${mainPhoto}')" style="width: 100%; height: 190px; object-fit: cover; display: block; cursor: pointer; transition: transform 0.2s ease;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                                <div style="position: absolute; bottom: 0; inset-x: 0; background: linear-gradient(to top, rgba(0,0,0,0.85), transparent); padding: 0.6rem 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                                    <span style="color: #fff; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-shop"></i> Display / Sell Out Toko
                                    </span>
                                    <button type="button" onclick="openLightbox('${mainPhoto}')" style="background: rgba(255,255,255,0.25); color: #fff; border: none; border-radius: 6px; padding: 2px 8px; font-size: 0.7rem; font-weight: 700; cursor: pointer;">
                                        <i class="fa-solid fa-expand"></i> Zoom
                                    </button>
                                </div>
                            </div>
                        ` : ''}

                        ${photos.filter(p => resolveUrl(p.url) !== mainPhoto).map((p, pI) => {
                            const pUrl = resolveUrl(p.url);
                            return `
                                <div style="position: relative; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; width: 100%; max-width: 220px; background: #0f172a; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                                    <img src="${pUrl}" alt="${p.label || 'Foto Laporan'}" onclick="openLightbox('${pUrl}')" style="width: 100%; height: 190px; object-fit: cover; display: block; cursor: pointer; transition: transform 0.2s ease;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                                    <div style="position: absolute; bottom: 0; inset-x: 0; background: linear-gradient(to top, rgba(0,0,0,0.85), transparent); padding: 0.6rem 0.75rem;">
                                        <span style="color: #fff; font-size: 0.74rem; font-weight: 700;">${p.label || 'Lampiran #' + (pI + 1)}</span>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        } else {
            photoSectionHtml = `
                <div style="margin-top: 1.25rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.82rem;">
                    <i class="fa-solid fa-image" style="font-size: 1.2rem; color: #94a3b8;"></i>
                    <span>Tidak ada lampiran foto dokumentasi sell out toko pada submisi laporan ini.</span>
                </div>
            `;
        }

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

            ${noSellOutSectionHtml}

            ${photoSectionHtml}

            <div style="margin-top: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-heading); margin: 0; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-basket-shopping" style="color: var(--brand-primary);"></i>
                        Rincian Item Keranjang Belanja
                    </h4>
                    <span style="font-size: 0.78rem; font-weight: 700; color: ${isNoSellOut ? '#dc2626' : '#2563eb'};">
                        ${cart.length} Varian Produk ${isNoSellOut ? '(No Sell Out)' : ''}
                    </span>
                </div>
                ${cartHtml}
            </div>
        `;

        modal.classList.add('active');
    }

    function closeSubmissionDetailModal() {
        document.getElementById('submissionDetailModal')?.classList.remove('active');
    }

    // Modal Breakdown Drilldown (Top Mitra, Top Produk, Daerah, Wilayah)
    function openMbrBreakdownModal(type, title, subtitle, list) {
        const modal = document.getElementById('mbrBreakdownModal');
        const titleEl = document.getElementById('mbrBreakdownModalTitle');
        const bodyEl = document.getElementById('mbrBreakdownModalBody');
        if (!modal || !bodyEl) return;

        list = list || [];
        let modalTitleHtml = '';
        let tableHtml = '';
        let totalQty = 0;
        let totalVal = 0;

        list.forEach(item => {
            totalQty += Number(item.qty || 0);
            totalVal += Number(item.value || 0);
        });

        if (type === 'mitra') {
            modalTitleHtml = `<i class="fa-solid fa-user-tie" style="color: #4f46e5;"></i> Rincian Penjualan Mitra: <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Daerah / Cabang</div>
                        <div style="font-weight: 700; color: var(--text-heading);">${subtitle || '-'}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Qty Terjual</div>
                            <div style="font-weight: 800; color: #2563eb; font-size: 1.15rem;">${totalQty.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Nilai Penjualan</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">Rp ${totalVal.toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-mbr-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Produk</th>
                                <th class="num">Jumlah Pcs</th>
                                <th class="num">Total Penjualan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading);">${it.name}</div>
                                        ${it.sku && it.sku !== '-' ? `<div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">${it.sku}</div>` : ''}
                                    </td>
                                    <td class="num" style="color: #2563eb; font-weight: 700;">${Number(it.qty || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">Rp ${Number(it.value || 0).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data produk untuk mitra ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="2" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: #2563eb;">${totalQty.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">Rp ${totalVal.toLocaleString('id-ID')}</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        } else if (type === 'product') {
            modalTitleHtml = `<i class="fa-solid fa-medal" style="color: #6366f1;"></i> Rincian Distribusi Penjualan Produk: <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode SKU</div>
                        <div style="font-weight: 700; font-family: monospace; color: var(--text-heading);">${subtitle || '-'}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Qty Terjual</div>
                            <div style="font-weight: 800; color: #2563eb; font-size: 1.15rem;">${totalQty.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Nilai</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">Rp ${totalVal.toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-mbr-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Mitra (SPG)</th>
                                <th>Area / Cabang</th>
                                <th>Toko / Lokasi</th>
                                <th class="num">Jumlah Pcs</th>
                                <th class="num">Total Penjualan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.mitra || '-'}</td>
                                    <td style="color: var(--text-muted); font-weight: 600;">${it.area || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td class="num" style="color: #2563eb; font-weight: 700;">${Number(it.qty || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">Rp ${Number(it.value || 0).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data distribusi untuk produk ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="4" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: #2563eb;">${totalQty.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">Rp ${totalVal.toLocaleString('id-ID')}</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        } else if (type === 'area') {
            modalTitleHtml = `<i class="fa-solid fa-map-location-dot" style="color: #0d9488;"></i> Rincian Penjualan Daerah / Cabang: <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Daerah / Cabang</div>
                        <div style="font-weight: 800; color: var(--text-heading); font-size: 1.05rem;">${title}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Qty</div>
                            <div style="font-weight: 800; color: #2563eb; font-size: 1.15rem;">${totalQty.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Nilai</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">Rp ${totalVal.toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-mbr-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Mitra (SPG)</th>
                                <th>Produk</th>
                                <th>Toko / Lokasi</th>
                                <th class="num">Jumlah Pcs</th>
                                <th class="num">Total Penjualan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.mitra || '-'}</td>
                                    <td style="color: var(--brand-primary); font-weight: 600;">${it.product || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td class="num" style="color: #2563eb; font-weight: 700;">${Number(it.qty || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">Rp ${Number(it.value || 0).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data penjualan di daerah ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="4" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: #2563eb;">${totalQty.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">Rp ${totalVal.toLocaleString('id-ID')}</td>
                                </tr>
                            </tfoot>
                        ` : ''}
                    </table>
                </div>
            `;
        } else if (type === 'region') {
            modalTitleHtml = `<i class="fa-solid fa-earth-asia" style="color: var(--brand-primary);"></i> Rincian Penjualan Wilayah (Region): <strong>${title}</strong>`;
            tableHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Wilayah (Region)</div>
                        <div style="font-weight: 800; color: var(--text-heading); font-size: 1.05rem;">${title}</div>
                    </div>
                    <div style="display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Qty</div>
                            <div style="font-weight: 800; color: #2563eb; font-size: 1.15rem;">${totalQty.toLocaleString('id-ID')} Pcs</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Nilai</div>
                            <div style="font-weight: 800; color: #059669; font-size: 1.15rem;">Rp ${totalVal.toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="portal-mbr-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nama Mitra (SPG)</th>
                                <th>Area / Cabang</th>
                                <th>Produk</th>
                                <th>Toko / Lokasi</th>
                                <th class="num">Jumlah Pcs</th>
                                <th class="num">Total Penjualan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${list.length > 0 ? list.map((it, idx) => `
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">${idx + 1}</td>
                                    <td style="font-weight: 700; color: var(--text-heading);">${it.mitra || '-'}</td>
                                    <td style="color: var(--text-muted); font-weight: 600;">${it.area || '-'}</td>
                                    <td style="color: var(--brand-primary); font-weight: 600;">${it.product || '-'}</td>
                                    <td style="color: var(--text-heading);">${it.store || '-'}</td>
                                    <td class="num" style="color: #2563eb; font-weight: 700;">${Number(it.qty || 0).toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669; font-weight: 700;">Rp ${Number(it.value || 0).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data penjualan di wilayah ini.</td>
                                </tr>
                            `}
                        </tbody>
                        ${list.length > 0 ? `
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: 800;">
                                    <td colspan="5" style="text-align: right; padding: 0.75rem 1rem;">TOTAL:</td>
                                    <td class="num" style="color: #2563eb;">${totalQty.toLocaleString('id-ID')} Pcs</td>
                                    <td class="num" style="color: #059669;">Rp ${totalVal.toLocaleString('id-ID')}</td>
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

    function closeMbrBreakdownModal() {
        document.getElementById('mbrBreakdownModal')?.classList.remove('active');
    }

    // Modal Detail Ringkas
    function showDetailModal(title, content) {
        document.getElementById('genericModalTitle').innerText = title;
        document.getElementById('genericModalContent').innerText = content;
        document.getElementById('genericDetailModal')?.classList.add('active');
    }

    function closeGenericModal() {
        document.getElementById('genericDetailModal')?.classList.remove('active');
    }

    // Lightbox Zoom
    function openLightbox(url) {
        const lb = document.getElementById('lightboxOverlay');
        const img = document.getElementById('lightboxImg');
        if (lb && img) {
            img.src = url;
            lb.classList.add('active');
        }
    }

    function closeLightbox() {
        document.getElementById('lightboxOverlay')?.classList.remove('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        initMbrSalesChart();
    });
</script>
@endpush
