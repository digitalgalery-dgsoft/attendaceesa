{{-- 
    WINGS SURYA - DASHBOARD LAPORAN PENJUALAN EVENT MBR
    Referensi: Web Report Wings AdhiTech (Slide 1 - 9)
    100% Khusus Data Submission Form Penjualan
--}}

@push('styles')
<style>
    /* Styling Dasar Dashboard Wings MBR */
    .mbr-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    /* 1. Filter Box (Gaya AdhiTech Navy Header) */
    .mbr-filter-box {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
    }
    .mbr-filter-header {
        background: #2c3e50;
        color: #ffffff;
        padding: 0.85rem 1.25rem;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mbr-filter-body {
        padding: 1.25rem;
    }
    .mbr-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        align-items: flex-end;
    }
    .mbr-filter-field {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .mbr-filter-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        text-transform: capitalize;
    }
    .mbr-filter-select, .mbr-filter-input {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        font-size: 0.85rem;
        color: #1e293b;
        outline: none;
        transition: border-color 0.2s;
    }
    .mbr-filter-select:focus, .mbr-filter-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }
    .mbr-btn-reload {
        background-color: #ea580c;
        color: #ffffff;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: background-color 0.2s;
    }
    .mbr-btn-reload:hover {
        background-color: #c2410c;
        color: #ffffff;
    }
    .mbr-btn-reset {
        background-color: #334155;
        color: #ffffff;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: background-color 0.2s;
    }
    .mbr-btn-reset:hover {
        background-color: #1e293b;
        color: #ffffff;
    }

    /* 2. Kartu KPI Stat (Slide 1 & Slide 8) */
    .mbr-kpi-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }
    .mbr-kpi-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 1024px) {
        .mbr-kpi-grid-4 { grid-template-columns: repeat(2, 1fr); }
        .mbr-kpi-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .mbr-kpi-grid-4 { grid-template-columns: 1fr; }
        .mbr-kpi-grid-3 { grid-template-columns: 1fr; }
    }

    .mbr-kpi-card {
        border-radius: 12px;
        padding: 1.25rem 1.25rem 0.85rem 1.25rem;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 155px;
    }
    .mbr-kpi-card.green { background: linear-gradient(135deg, #10b981, #059669); }
    .mbr-kpi-card.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .mbr-kpi-card.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .mbr-kpi-card.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .mbr-kpi-card.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
    .mbr-kpi-card.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .mbr-kpi-card.slate { background: linear-gradient(135deg, #64748b, #475569); }

    .mbr-kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    .mbr-kpi-title {
        font-size: 0.88rem;
        font-weight: 600;
        opacity: 0.95;
    }
    .mbr-kpi-icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }
    .mbr-kpi-val {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1.15;
        margin: 0.35rem 0 0.2rem 0;
        letter-spacing: -0.5px;
    }
    .mbr-kpi-sub {
        font-size: 0.74rem;
        opacity: 0.88;
        margin-bottom: 0.75rem;
    }
    .mbr-kpi-footer-link {
        border-top: 1px solid rgba(255, 255, 255, 0.25);
        padding-top: 0.55rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.78rem;
        font-weight: 700;
        color: #ffffff;
        text-decoration: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .mbr-kpi-footer-link:hover {
        opacity: 0.85;
        color: #ffffff;
    }

    /* 3. Section Grafik Penjualan (Slide 2 & 8) */
    .mbr-chart-card {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .mbr-chart-header {
        background: #14b8a6;
        color: #ffffff;
        padding: 0.85rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .mbr-chart-title {
        font-weight: 700;
        font-size: 0.98rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mbr-chart-switcher {
        display: inline-flex;
        background: #ffffff;
        border-radius: 6px;
        overflow: hidden;
        padding: 2px;
    }
    .mbr-chart-btn {
        padding: 0.3rem 0.85rem;
        border: none;
        background: transparent;
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.2s;
    }
    .mbr-chart-btn.active {
        background: #ea580c;
        color: #ffffff;
    }
    .mbr-chart-body {
        padding: 1.5rem;
        position: relative;
        height: 330px;
    }

    /* 4. Grid 2x2 Tabel Performa (Slide 2 & 3) */
    .mbr-table-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    @media (max-width: 900px) {
        .mbr-table-grid { grid-template-columns: 1fr; }
    }

    .mbr-panel-table {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .mbr-panel-header {
        padding: 0.85rem 1.25rem;
        color: #ffffff;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mbr-panel-header.green { background-color: #10b981; }
    .mbr-panel-header.purple { background-color: #8b5cf6; }
    .mbr-panel-header.teal { background-color: #0d9488; }
    .mbr-panel-header.red { background-color: #dc2626; }

    .mbr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .mbr-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 0.65rem 0.85rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.78rem;
        text-transform: uppercase;
    }
    .mbr-table td {
        padding: 0.75rem 0.85rem;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        vertical-align: middle;
    }
    .mbr-table tr:hover td {
        background-color: #f8fafc;
    }
    .mbr-table td.num {
        font-weight: 700;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .mbr-btn-detail {
        background-color: #f97316;
        color: #ffffff;
        border: none;
        padding: 0.3rem 0.75rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.75rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.2s;
    }
    .mbr-btn-detail:hover {
        background-color: #ea580c;
        color: #ffffff;
    }

    /* 5. Tabel Live Submissions Transaksi */
    .mbr-submissions-card {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .mbr-submissions-header {
        background: #1e293b;
        color: #ffffff;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .mbr-submissions-title {
        font-size: 1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mbr-badge-pill {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .mbr-badge-booth { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .mbr-badge-kasir { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

    /* 6. Modal Dialog Galeri Foto (Slide 9) */
    .mbr-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .mbr-modal-overlay.active {
        display: flex;
    }
    .mbr-modal-box {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 960px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: mbrModalIn 0.25s ease-out;
    }
    @keyframes mbrModalIn {
        from { opacity: 0; transform: scale(0.96) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .mbr-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .mbr-modal-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mbr-modal-close {
        background: #f1f5f9;
        border: none;
        border-radius: 8px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .mbr-modal-close:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .mbr-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
    }
    .mbr-photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 1.25rem;
    }
    .mbr-photo-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
    }
    .mbr-photo-thumb {
        width: 100%;
        height: 170px;
        object-fit: cover;
        background: #f8fafc;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .mbr-photo-thumb:hover {
        transform: scale(1.02);
    }
    .mbr-photo-info {
        padding: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        flex: 1;
    }
    .mbr-photo-tag {
        font-size: 0.72rem;
        font-weight: 800;
        color: #ea580c;
        text-transform: uppercase;
    }
    .mbr-photo-date {
        font-size: 0.75rem;
        color: #64748b;
    }
    .mbr-photo-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0.2rem;
    }
    .mbr-photo-store {
        font-size: 0.78rem;
        color: #475569;
    }
    .mbr-btn-view-photo {
        margin-top: 0.5rem;
        padding: 0.4rem;
        border-radius: 6px;
        border: 1px solid #f97316;
        color: #ea580c;
        background: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        text-decoration: none;
        display: block;
        transition: all 0.2s;
    }
    .mbr-btn-view-photo:hover {
        background: #ea580c;
        color: #ffffff;
    }

    /* Lightbox Preview */
    .mbr-lightbox-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.88);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }
    .mbr-lightbox-overlay.active {
        display: flex;
    }
    .mbr-lightbox-img {
        max-width: 90vw;
        max-height: 85vh;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
</style>
@endpush

<div class="mbr-container">

    {{-- 1. FILTER DATA (GAYA ADHITECH - DARK NAVY HEADER) --}}
    <div class="mbr-filter-box">
        <div class="mbr-filter-header">
            <i class="fa-solid fa-filter"></i>
            <span>Filter Data Penjualan (Event MBR)</span>
        </div>
        <div class="mbr-filter-body">
            <form action="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" method="GET">
                <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
                <div class="mbr-filter-grid">
                    
                    {{-- Periode Tanggal --}}
                    <div class="mbr-filter-field" style="grid-column: span 2;">
                        <label class="mbr-filter-label">Periode Bulan & Tahun</label>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <select name="start_month" class="mbr-filter-select">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $startMonth == $m ? 'selected' : '' }}>
                                        {{ Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select name="start_year" class="mbr-filter-select" style="max-width: 90px;">
                                @for($y = Carbon\Carbon::now()->year + 1; $y >= 2023; $y--)
                                    <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                            <span style="font-weight: 700; color: #94a3b8; font-size: 0.8rem;">s/d</span>
                            <select name="end_month" class="mbr-filter-select">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $endMonth == $m ? 'selected' : '' }}>
                                        {{ Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select name="end_year" class="mbr-filter-select" style="max-width: 90px;">
                                @for($y = Carbon\Carbon::now()->year + 1; $y >= 2023; $y--)
                                    <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    {{-- Wilayah (Region) --}}
                    <div class="mbr-filter-field">
                        <label class="mbr-filter-label">Wilayah</label>
                        <select name="region" class="mbr-filter-select">
                            <option value="">Semua Wilayah</option>
                            @foreach($regions as $r)
                                <option value="{{ $r }}" {{ $selectedRegion == $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Daerah Mitra (Area / Cabang) --}}
                    <div class="mbr-filter-field">
                        <label class="mbr-filter-label">Daerah Mitra</label>
                        <select name="area_id" class="mbr-filter-select">
                            <option value="">Semua Daerah</option>
                            @foreach($areas as $a)
                                @php $aId = is_object($a) ? $a->id : $a; $aName = is_object($a) ? $a->name : $a; @endphp
                                <option value="{{ $aId }}" {{ (string)$selectedAreaId === (string)$aId ? 'selected' : '' }}>{{ $aName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Toko (Work Location) --}}
                    <div class="mbr-filter-field">
                        <label class="mbr-filter-label">Toko / Outlet</label>
                        <select name="location_id" class="mbr-filter-select">
                            <option value="">Semua Toko</option>
                            @foreach($workLocations as $loc)
                                @php $lId = is_object($loc) ? $loc->id : $loc; $lName = is_object($loc) ? $loc->name : $loc; @endphp
                                <option value="{{ $lId }}" {{ (string)$selectedLocationId === (string)$lId ? 'selected' : '' }}>{{ $lName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pencarian --}}
                    <div class="mbr-filter-field">
                        <label class="mbr-filter-label">Cari Mitra / Toko</label>
                        <input type="text" name="q" value="{{ $search }}" class="mbr-filter-input" placeholder="Ketik kata kunci...">
                    </div>

                    {{-- Tombol Filter --}}
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="mbr-btn-reload">
                            <i class="fa-solid fa-rotate"></i> Reload
                        </button>
                        <a href="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" class="mbr-btn-reset">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. KARTU KPI STAT UTAMA (BARIS 1: 4 KARTU SESUAI SLIDE 1 & 8) --}}
    <div class="mbr-kpi-grid-4">
        
        {{-- Card 1: Total Nilai Penjualan / Omzet (Green) --}}
        <div class="mbr-kpi-card green">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Total Nilai Penjualan (Omzet)</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-money-bill-wave"></i></div>
                </div>
                <div class="mbr-kpi-val" style="font-size: 1.45rem;">Rp {{ number_format($mbrData['kpis']['total_value_penjualan_rp'], 0, ',', '.') }}</div>
                <div class="mbr-kpi-sub">Periode: {{ Carbon\Carbon::create($startYear, $startMonth, 1)->translatedFormat('F Y') }} - {{ Carbon\Carbon::create($endYear, $endMonth, 1)->translatedFormat('F Y') }}</div>
            </div>
            <a href="#table_submissions" class="mbr-kpi-footer-link">
                <span>👁️ Lihat Rincian Transaksi</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        {{-- Card 2: Total Qty Penjualan (Blue) --}}
        <div class="mbr-kpi-card blue">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Total Qty Penjualan</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-box"></i></div>
                </div>
                <div class="mbr-kpi-val">{{ number_format($mbrData['kpis']['total_qty_penjualan']) }} <span style="font-size: 1rem; font-weight: 600;">Pcs</span></div>
                <div class="mbr-kpi-sub">Periode: {{ Carbon\Carbon::create($startYear, $startMonth, 1)->translatedFormat('F Y') }} - {{ Carbon\Carbon::create($endYear, $endMonth, 1)->translatedFormat('F Y') }}</div>
            </div>
            <a href="#table_submissions" class="mbr-kpi-footer-link">
                <span>👁️ Lihat Detail</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        {{-- Card 3: Total Produk Penjualan (Orange) --}}
        <div class="mbr-kpi-card orange">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Total Varian Produk</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-tags"></i></div>
                </div>
                <div class="mbr-kpi-val">{{ $mbrData['kpis']['total_produk_penjualan'] }} <span style="font-size: 1rem; font-weight: 600;">SKU</span></div>
                <div class="mbr-kpi-sub">Varian Produk Terjual di Laporan</div>
            </div>
            <a href="#table_top_products" class="mbr-kpi-footer-link">
                <span>👁️ Lihat Detail</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        {{-- Card 4: Total Penjualan Hari Ini (Red) --}}
        <div class="mbr-kpi-card red">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Total Penjualan Hari Ini</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-calendar-day"></i></div>
                </div>
                <div class="mbr-kpi-val">{{ number_format($mbrData['kpis']['total_penjualan_hari_ini']) }} <span style="font-size: 1rem; font-weight: 600;">Pcs</span></div>
                <div class="mbr-kpi-sub">{{ Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
            </div>
            <a href="#table_submissions" class="mbr-kpi-footer-link">
                <span>👁️ Lihat Detail</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    {{-- KARTU KPI STAT PENDUKUNG (BARIS 2: METODE BAYAR, MITRA AKTIF & OUTLET) --}}
    <div class="mbr-kpi-grid-3">
        {{-- Card 5: Metode Pembayaran Booth vs Kasir (Purple) --}}
        <div class="mbr-kpi-card purple">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Metode Pembayaran</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-cash-register"></i></div>
                </div>
                <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.35rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700;">
                        <span>🛒 Bayar di Booth:</span>
                        <span>Rp {{ number_format($mbrData['kpis']['total_bayar_di_booth_rp'], 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700;">
                        <span>🏬 Bayar di Kasir:</span>
                        <span>Rp {{ number_format($mbrData['kpis']['total_bayar_di_kasir_rp'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="mbr-kpi-sub" style="margin-top: 0.5rem;">Tunai SPG vs Mesin Kasir Outlet</div>
            </div>
            <a href="javascript:void(0)" onclick="openGalleryModal('struk')" class="mbr-kpi-footer-link">
                <span>🧾 Galeri Foto Struk Penjualan</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        {{-- Card 6: Total Mitra Penjualan (Teal) --}}
        <div class="mbr-kpi-card teal">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Total Mitra Aktif (SPG/MD)</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-user-tie"></i></div>
                </div>
                <div class="mbr-kpi-val">{{ count($mbrData['top_mitra'] ?? []) }} <span style="font-size: 1rem; font-weight: 600;">Mitra</span></div>
                <div class="mbr-kpi-sub">Rata-rata: Rp {{ count($mbrData['top_mitra'] ?? []) > 0 ? number_format($mbrData['kpis']['total_value_penjualan_rp'] / count($mbrData['top_mitra']), 0, ',', '.') : '0' }} / mitra</div>
            </div>
            <a href="#table_top_mitra" class="mbr-kpi-footer-link">
                <span>👥 Lihat Top Mitra</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        {{-- Card 7: Total Submissions & Outlet (Slate) --}}
        <div class="mbr-kpi-card slate">
            <div>
                <div class="mbr-kpi-top">
                    <span class="mbr-kpi-title">Total Laporan & Toko Dikunjungi</span>
                    <div class="mbr-kpi-icon-badge"><i class="fa-solid fa-store"></i></div>
                </div>
                <div class="mbr-kpi-val">{{ number_format($mbrData['kpis']['total_submissions']) }} <span style="font-size: 1rem; font-weight: 600;">Laporan</span></div>
                <div class="mbr-kpi-sub">Tersebar di {{ $mbrData['kpis']['unique_stores'] }} Toko & Lokasi Mitra</div>
            </div>
            <a href="javascript:void(0)" onclick="openGalleryModal('all')" class="mbr-kpi-footer-link">
                <span>📸 Lihat Dokumentasi Foto Sell Out</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    {{-- 3. GRAFIK PENJUALAN PRODUK (SLIDE 2 & 8) --}}
    <div class="mbr-chart-card">
        <div class="mbr-chart-header">
            <div class="mbr-chart-title">
                <i class="fa-solid fa-chart-column"></i>
                <span>Grafik Penjualan Produk</span>
            </div>
            <div class="mbr-chart-switcher">
                <button type="button" class="mbr-chart-btn active" id="btn_mode_daily" onclick="switchChartMode('daily')">Harian</button>
                <button type="button" class="mbr-chart-btn" id="btn_mode_weekly" onclick="switchChartMode('weekly')">Mingguan</button>
                <button type="button" class="mbr-chart-btn" id="btn_mode_monthly" onclick="switchChartMode('monthly')">Bulanan</button>
            </div>
        </div>
        <div class="mbr-chart-body">
            <canvas id="mbrSalesChart"></canvas>
        </div>
    </div>

    {{-- 4. GRID 2X2 TABEL PERFORMA (SLIDE 2 & 3) --}}
    <div class="mbr-table-grid">
        
        {{-- Tabel 1: Top 5 Mitra Penjualan (Green Header) --}}
        <div class="mbr-panel-table" id="table_top_mitra">
            <div class="mbr-panel-header green">
                <i class="fa-solid fa-trophy"></i>
                <span>Top 5 Mitra Penjualan</span>
            </div>
            <table class="mbr-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>Nama Mitra</th>
                        <th>Daerah</th>
                        <th class="num">Total Qty</th>
                        <th style="width: 70px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mbrData['top_mitra'] as $idx => $m)
                        <tr>
                            <td style="font-weight: 800; color: #10b981;">{{ $idx + 1 }}</td>
                            <td style="font-weight: 700;">{{ $m['name'] }}</td>
                            <td style="color: #64748b;">{{ $m['area'] }}</td>
                            <td class="num">{{ number_format($m['qty']) }}</td>
                            <td style="text-align: center;">
                                <button type="button" onclick="showDetailModal('Mitra: {{ $m['name'] }}', 'Total Penjualan: {{ number_format($m['qty']) }} Pcs (Rp {{ number_format($m['value'], 0, ',', '.') }})')" class="mbr-btn-detail">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: #94a3b8;">Belum ada data mitra</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tabel 2: Top 5 Produk Penjualan (Purple Header) --}}
        <div class="mbr-panel-table" id="table_top_products">
            <div class="mbr-panel-header purple">
                <i class="fa-solid fa-star"></i>
                <span>Top 5 Produk Penjualan</span>
            </div>
            <table class="mbr-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>Produk</th>
                        <th class="num">Total Qty</th>
                        <th style="width: 70px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mbrData['top_products'] as $idx => $p)
                        <tr>
                            <td style="font-weight: 800; color: #8b5cf6;">{{ $idx + 1 }}</td>
                            <td style="font-weight: 700;">
                                {{ $p['name'] }}
                                @if(!empty($p['sku']) && $p['sku'] !== '-')
                                    <span style="display: block; font-size: 0.72rem; color: #64748b; font-weight: 500;">{{ $p['sku'] }}</span>
                                @endif
                            </td>
                            <td class="num">{{ number_format($p['qty']) }}</td>
                            <td style="text-align: center;">
                                <button type="button" onclick="showDetailModal('Produk: {{ $p['name'] }}', 'Total Terjual: {{ number_format($p['qty']) }} Pcs | Nilai: Rp {{ number_format($p['value'], 0, ',', '.') }}')" class="mbr-btn-detail">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8;">Belum ada data produk</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tabel 3: Total Penjualan Per Daerah (Teal Header) --}}
        <div class="mbr-panel-table" id="table_sales_area">
            <div class="mbr-panel-header teal">
                <i class="fa-solid fa-location-dot"></i>
                <span>Total Penjualan Per Daerah</span>
            </div>
            <table class="mbr-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>Daerah</th>
                        <th class="num">Total Qty</th>
                        <th style="width: 70px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mbrData['sales_by_area'] as $idx => $a)
                        <tr>
                            <td style="font-weight: 800; color: #0d9488;">{{ $idx + 1 }}</td>
                            <td style="font-weight: 700;">
                                {{ $a['area'] }}
                                <span style="display: block; font-size: 0.72rem; color: #64748b; font-weight: 500;">{{ $a['store_count'] }} Toko Tercover</span>
                            </td>
                            <td class="num">{{ number_format($a['qty']) }}</td>
                            <td style="text-align: center;">
                                <button type="button" onclick="showDetailModal('Daerah: {{ $a['area'] }}', 'Total Penjualan: {{ number_format($a['qty']) }} Pcs (Rp {{ number_format($a['value'], 0, ',', '.') }}) di {{ $a['store_count'] }} Toko')" class="mbr-btn-detail">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8;">Belum ada data daerah</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tabel 4: Total Penjualan Per Wilayah (Red Header) --}}
        <div class="mbr-panel-table" id="table_sales_region">
            <div class="mbr-panel-header red">
                <i class="fa-solid fa-map"></i>
                <span>Total Penjualan Per Wilayah</span>
            </div>
            <table class="mbr-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>Wilayah</th>
                        <th class="num">Total Qty</th>
                        <th style="width: 70px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mbrData['sales_by_region'] as $idx => $r)
                        <tr>
                            <td style="font-weight: 800; color: #dc2626;">{{ $idx + 1 }}</td>
                            <td style="font-weight: 700;">
                                {{ $r['region'] }}
                                <span style="display: block; font-size: 0.72rem; color: #64748b; font-weight: 500;">{{ $r['area_count'] }} Daerah / Cabang</span>
                            </td>
                            <td class="num">{{ number_format($r['qty']) }}</td>
                            <td style="text-align: center;">
                                <button type="button" onclick="showDetailModal('Wilayah: {{ $r['region'] }}', 'Total Wilayah: {{ number_format($r['qty']) }} Pcs (Rp {{ number_format($r['value'], 0, ',', '.') }})')" class="mbr-btn-detail">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8;">Belum ada data wilayah</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. TABEL DATA RINCIAN TRANSAKSI SUBMISSION (LIVE LIST) --}}
    <div class="mbr-submissions-card" id="table_submissions">
        <div class="mbr-submissions-header">
            <div class="mbr-submissions-title">
                <i class="fa-solid fa-list-check"></i>
                <span>Rincian Transaksi Laporan Penjualan (Live Data)</span>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="button" onclick="openGalleryModal('all')" class="mbr-btn-detail" style="background: #2563eb;">
                    <i class="fa-solid fa-images"></i> Galeri Foto Lengkap ({{ count($mbrData['gallery_photos']) }})
                </button>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="mbr-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Tanggal Submit</th>
                        <th>Nama Mitra (Karyawan)</th>
                        <th>Toko / Outlet</th>
                        <th>Rincian Produk Terjual</th>
                        <th class="num">Total Qty</th>
                        <th class="num">Total Omzet (Rp)</th>
                        <th>Metode Bayar</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $idx => $sub)
                        @php
                            $cartJson = null;
                            $qty = 0; $val = 0; $booth = 0; $kasir = 0;
                            foreach($sub->values as $v) {
                                $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                                if ($fn === 'mbr_sales_items_json') {
                                    $cartJson = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                                } elseif ($fn === 'total_qty_penjualan') {
                                    $qty = (int)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text));
                                } elseif ($fn === 'total_value_penjualan_rp') {
                                    $val = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text));
                                } elseif ($fn === 'total_bayar_di_booth_rp') {
                                    $booth = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text));
                                } elseif ($fn === 'total_bayar_di_kasir_rp') {
                                    $kasir = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text));
                                }
                            }
                            if ($qty <= 0 && is_array($cartJson)) {
                                foreach($cartJson as $cIt) $qty += (int)($cIt['qty'] ?? 1);
                            }
                            if ($val <= 0 && is_array($cartJson)) {
                                foreach($cartJson as $cIt) $val += (float)($cIt['value_rp'] ?? (((int)($cIt['qty'] ?? 1)) * (float)($cIt['store_price'] ?? 3100)));
                            }
                        @endphp
                        <tr>
                            <td style="color: #64748b; font-weight: 700;">{{ $submissions->firstItem() + $idx }}</td>
                            <td>
                                <strong style="color: #0f172a;">{{ $sub->submitted_at ? $sub->submitted_at->format('d/m/Y') : $sub->created_at->format('d/m/Y') }}</strong>
                                <span style="display: block; font-size: 0.72rem; color: #64748b;">{{ $sub->submitted_at ? $sub->submitted_at->format('H:i') : $sub->created_at->format('H:i') }} WIB</span>
                            </td>
                            <td>
                                <strong style="color: #0f172a;">{{ $sub->employee ? $sub->employee->full_name : 'Mitra Wings' }}</strong>
                                <span style="display: block; font-size: 0.72rem; color: #64748b;">NIK: {{ $sub->employee ? $sub->employee->employee_no : '-' }}</span>
                            </td>
                            <td>
                                <strong style="color: #0f172a;">{{ $sub->workLocation ? $sub->workLocation->name : 'Outlet Toko' }}</strong>
                                <span style="display: block; font-size: 0.72rem; color: #64748b;">{{ $sub->workLocation && $sub->workLocation->branch ? $sub->workLocation->branch->name : '-' }}</span>
                            </td>
                            <td>
                                @if(is_array($cartJson) && !empty($cartJson))
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 280px;">
                                        @foreach(array_slice($cartJson, 0, 3) as $cIt)
                                            <span style="background: #f1f5f9; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.72rem; color: #334155;">
                                                {{ $cIt['name'] ?? ($cIt['product_name'] ?? 'Mie Sedaap') }}: <strong>{{ $cIt['qty'] ?? 1 }}</strong>
                                            </span>
                                        @endforeach
                                        @if(count($cartJson) > 3)
                                            <span style="font-size: 0.72rem; color: #ea580c; font-weight: 700;">+{{ count($cartJson) - 3 }} produk lagi</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: #94a3b8; font-size: 0.78rem;">-</span>
                                @endif
                            </td>
                            <td class="num"><strong style="color: #2563eb;">{{ number_format($qty) }}</strong></td>
                            <td class="num"><strong style="color: #059669;">Rp {{ number_format($val, 0, ',', '.') }}</strong></td>
                            <td>
                                @if($kasir > 0 && $booth > 0)
                                    <span class="mbr-badge-pill mbr-badge-booth">Booth (Rp {{ number_format($booth, 0, ',', '.') }})</span>
                                    <span class="mbr-badge-pill mbr-badge-kasir">Kasir (Rp {{ number_format($kasir, 0, ',', '.') }})</span>
                                @elseif($kasir > 0)
                                    <span class="mbr-badge-pill mbr-badge-kasir">Kasir Toko</span>
                                @else
                                    <span class="mbr-badge-pill mbr-badge-booth">Bayar di Booth</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('portal.report.submission.detail', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" class="mbr-btn-detail" style="background: #0284c7;">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                <div style="font-weight: 700; color: #475569; font-size: 1rem;">Belum Ada Data Laporan Masuk</div>
                                <div style="font-size: 0.82rem; margin-top: 0.35rem;">Data transaksi submission untuk periode ini akan otomatis muncul saat petugas SPG/MD mengirimkan laporan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div style="padding: 1rem; border-top: 1px solid #e2e8f0;">
                {{ $submissions->links('portal.pagination') }}
            </div>
        @endif
    </div>
</div>

{{-- 6. MODAL DETAIL FOTO (SLIDE 9 PPTX) --}}
<div class="mbr-modal-overlay" id="galleryModal">
    <div class="mbr-modal-box">
        <div class="mbr-modal-header">
            <div class="mbr-modal-title">
                <i class="fa-solid fa-images" style="color: #ea580c;"></i>
                <span id="galleryModalTitle">Detail Foto Penjualan & Struk Event MBR</span>
            </div>
            <button type="button" class="mbr-modal-close" onclick="closeGalleryModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="mbr-modal-body">
            <div class="mbr-photo-grid" id="galleryGridContainer">
                @forelse($mbrData['gallery_photos'] as $gp)
                    <div class="mbr-photo-card" data-type="{{ $gp['type'] }}">
                        <img src="{{ $gp['url'] }}" alt="{{ $gp['title'] }}" class="mbr-photo-thumb" onclick="openLightbox('{{ $gp['url'] }}')">
                        <div class="mbr-photo-info">
                            <span class="mbr-photo-tag">{{ $gp['title'] }}</span>
                            <span class="mbr-photo-date">{{ $gp['date'] }}</span>
                            <span class="mbr-photo-name">{{ $gp['mitra'] }}</span>
                            <span class="mbr-photo-store">📍 {{ $gp['store'] }}</span>
                            @if(!empty($gp['product']))
                                <span style="font-size: 0.72rem; color: #0284c7; font-weight: 700;">{{ $gp['product'] }} ({{ $gp['qty'] }} pcs)</span>
                            @endif
                            <button type="button" onclick="openLightbox('{{ $gp['url'] }}')" class="mbr-btn-view-photo">
                                <i class="fa-solid fa-magnifying-glass-plus"></i> Perbesar Foto
                            </button>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                        <i class="fa-solid fa-images" style="font-size: 2.5rem; display: block; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                        <div style="font-weight: 700; color: #475569; font-size: 1rem;">Belum Ada Foto Struk / Sell Out</div>
                        <p style="font-size: 0.82rem; margin-top: 0.35rem;">Dokumentasi foto struk dan foto sell out toko akan otomatis muncul di sini saat petugas mengirimkan laporan melalui aplikasi.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- MODAL DETAIL TEKS UMUM --}}
<div class="mbr-modal-overlay" id="genericDetailModal">
    <div class="mbr-modal-box" style="max-width: 520px;">
        <div class="mbr-modal-header">
            <div class="mbr-modal-title" id="genericModalTitle">Detail Informasi</div>
            <button type="button" class="mbr-modal-close" onclick="closeGenericModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="mbr-modal-body">
            <p id="genericModalContent" style="font-size: 0.95rem; color: #334155; line-height: 1.6; margin: 0;"></p>
        </div>
    </div>
</div>

{{-- LIGHTBOX OVERLAY --}}
<div class="mbr-lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="Zoom Foto" class="mbr-lightbox-img" onclick="event.stopPropagation()">
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
                    label: 'Qty Penjualan (Pcs)',
                    data: initialQtys,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
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

    // Modal Galeri Foto
    function openGalleryModal(filterType = 'all') {
        const modal = document.getElementById('galleryModal');
        if (!modal) return;
        modal.classList.add('active');

        const titleEl = document.getElementById('galleryModalTitle');
        if (filterType === 'freetaste') {
            if (titleEl) titleEl.innerText = 'Detail Foto Sampling Freetaste';
        } else if (filterType === 'struk') {
            if (titleEl) titleEl.innerText = 'Detail Foto Struk Penjualan';
        } else {
            if (titleEl) titleEl.innerText = 'Detail Dokumentasi Foto Penjualan Event MBR';
        }
    }

    function closeGalleryModal() {
        document.getElementById('galleryModal')?.classList.remove('active');
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

    // Detail Modal Umum
    function showDetailModal(title, text) {
        document.getElementById('genericModalTitle').innerText = title;
        document.getElementById('genericModalContent').innerText = text;
        document.getElementById('genericDetailModal')?.classList.add('active');
    }

    function closeGenericModal() {
        document.getElementById('genericDetailModal')?.classList.remove('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        initMbrSalesChart();
    });
</script>
@endpush
