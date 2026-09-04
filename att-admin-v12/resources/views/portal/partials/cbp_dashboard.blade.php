{{-- PORTAL CBP EXECUTIVE DASHBOARD (DASHBOARD 1 & DASHBOARD 2) --}}
<div class="custom-cbp-wrapper" style="margin-bottom: 2rem;">

    <!-- TOP MAIN TAB NAVIGATION -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="cbp-main-nav">
            <button type="button" class="cbp-nav-btn active" id="btn_cbp_tab_d1" onclick="switchCbpMainTab('d1')">
                <i class="fa-solid fa-palette" style="font-size: 1rem;"></i>
                <span>Cat Tembok (Dashboard 1)</span>
            </button>
            <button type="button" class="cbp-nav-btn" id="btn_cbp_tab_d2" onclick="switchCbpMainTab('d2')">
                <i class="fa-solid fa-shield-halved" style="font-size: 1rem;"></i>
                <span>Enamel & Waterproofing (Dashboard 2)</span>
            </button>
            <button type="button" class="cbp-nav-btn" id="btn_cbp_tab_raw" onclick="switchCbpMainTab('raw')">
                <i class="fa-solid fa-table-list" style="font-size: 1rem;"></i>
                <span>Data Rincian Submisi (Raw Data)</span>
            </button>
        </div>

        <div style="font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Periode Data: <strong>Januari – Juli {{ $endYear }}</strong></span>
        </div>
    </div>

    <!-- EXECUTIVE KPI OVERVIEW CARDS -->
    <div class="cbp-kpi-grid">
        <!-- 1. Total Monitoring Record -->
        <div class="cbp-kpi-card">
            <div class="cbp-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div>
                <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 2px;">
                    Total Monitoring CBP
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-heading); line-height: 1.2;">
                    {{ number_format($cbpData['kpis']['total_records'] ?? 0) }}
                </div>
                <div style="font-size: 0.72rem; color: #16a34a; font-weight: 600; margin-top: 2px;">
                    <i class="fa-solid fa-check-double"></i> Data Riil Terverifikasi
                </div>
            </div>
        </div>

        <!-- 2. Rata-Rata Harga Dulux (MOP) -->
        <div class="cbp-kpi-card">
            <div class="cbp-kpi-icon" style="background: #f0fdf4; color: #16a34a;">
                <i class="fa-solid fa-tags"></i>
            </div>
            <div>
                <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 2px;">
                    Rata-Rata MOP Dulux
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-heading); line-height: 1.2;">
                    Rp {{ number_format($cbpData['kpis']['avg_an_galon'] ?? 0, 0, ',', '.') }}
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 500; margin-top: 2px;">
                    Rata-rata Galon Dulux
                </div>
            </div>
        </div>

        <!-- 3. Rata-Rata Harga Kompetitor -->
        <div class="cbp-kpi-card">
            <div class="cbp-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                <i class="fa-solid fa-store-slash"></i>
            </div>
            <div>
                <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 2px;">
                    Rata-Rata MOP Kompetitor
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-heading); line-height: 1.2;">
                    Rp {{ number_format($cbpData['kpis']['avg_comp_galon'] ?? 0, 0, ',', '.') }}
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 500; margin-top: 2px;">
                    Rata-rata Galon Pasar
                </div>
            </div>
        </div>

        <!-- 4. Indeks Daya Saing Dulux -->
        <div class="cbp-kpi-card">
            <div class="cbp-kpi-icon" style="background: #faf5ff; color: #9333ea;">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 2px;">
                    Indeks Rasio Harga
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-heading); line-height: 1.2;">
                    {{ number_format($cbpData['kpis']['ratio_index'] ?? 100, 1) }}%
                </div>
                <div style="font-size: 0.72rem; font-weight: 700; color: {{ ($cbpData['kpis']['ratio_index'] ?? 100) <= 100 ? '#16a34a' : '#ea580c' }}; margin-top: 2px;">
                    {{ ($cbpData['kpis']['ratio_index'] ?? 100) <= 100 ? 'Dulux Lebih Kompetitif' : 'Dulux Premium Positioning' }}
                </div>
            </div>
        </div>

        <!-- 5. Toko Terpantau -->
        <div class="cbp-kpi-card">
            <div class="cbp-kpi-icon" style="background: #fdf2f8; color: #db2777;">
                <i class="fa-solid fa-shop"></i>
            </div>
            <div>
                <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 2px;">
                    Toko Terpantau
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-heading); line-height: 1.2;">
                    {{ number_format($cbpData['kpis']['unique_stores'] ?? 0) }}
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 500; margin-top: 2px;">
                    Titik Outlet Aktif
                </div>
            </div>
        </div>
    </div>

    <!-- PANE 1: DASHBOARD (1) - CAT TEMBOK (INTERIOR & EKSTERIOR) -->
    <div id="cbp_pane_d1" class="cbp-pane-container">
        
        <!-- MOP YoY & Monthly Trend Chart Card -->
        <div class="cbp-sec-card" style="margin-bottom: 1.75rem;">
            <div class="cbp-sec-header">
                <div>
                    <div class="cbp-sec-title">
                        <i class="fa-solid fa-chart-line" style="color: var(--brand-primary);"></i>
                        <span>Tren MOP Bulanan & YoY: Dulux vs Kompetitor Utama</span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Perbandingan tren pergerakan harga rata-rata kemasan galon (MOP) per bulan sepanjang {{ $endYear }}
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span class="cbp-benchmark-tag">
                        <i class="fa-solid fa-circle-info"></i> Rata-Rata Galon (Rp)
                    </span>
                </div>
            </div>
            <div style="padding: 1.25rem 1.5rem;">
                <div id="cbp_mop_trend_chart" style="min-height: 320px;"></div>
            </div>
        </div>

        <!-- CATEGORY SECTIONS OF DASHBOARD 1 -->
        @foreach($cbpData['dashboard1'] as $sKey => $sec)
            <div class="cbp-sec-card" id="card_{{ $sKey }}">
                <div class="cbp-sec-header">
                    <div>
                        <div class="cbp-sec-title">
                            <i class="fa-solid fa-layer-group" style="color: var(--brand-primary); font-size: 1.15rem;"></i>
                            <span>{{ $sec['title'] }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                            <span class="cbp-benchmark-tag">
                                <i class="fa-solid fa-bullseye"></i> {{ $sec['benchmark_label'] }}
                            </span>
                            <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-muted); background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                <i class="fa-solid fa-box-open"></i> {{ $sec['unit'] }}
                            </span>
                            <span style="font-size: 0.76rem; color: #64748b;">
                                ({{ count($sec['products']) }} Sub Brand)
                            </span>
                        </div>
                    </div>

                    <!-- View Toggle: Price Table vs Index Table -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="cbp-toggle-group">
                            <button type="button" class="cbp-toggle-btn active" id="btn_price_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'price')">
                                <i class="fa-solid fa-rupiah-sign"></i> Rata-Rata MOP (Rp)
                            </button>
                            <button type="button" class="cbp-toggle-btn" id="btn_index_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'index')">
                                <i class="fa-solid fa-percent"></i> Price Index to AN (100%)
                            </button>
                        </div>
                    </div>
                </div>

                <div style="padding: 0;">
                    <!-- 1. Table Rata-Rata Harga MOP (Rp) -->
                    <div id="table_price_wrap_{{ $sKey }}" style="overflow-x: auto;">
                        <table class="custom-table cbp-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 90px;">Brand</th>
                                    <th style="min-width: 220px;">Sub Brand / Produk</th>
                                    @foreach($cbpData['months'] as $m => $mMeta)
                                        <th style="text-align: right; min-width: 110px;">{{ $mMeta['short'] }} {{ $endYear }}</th>
                                    @endforeach
                                    <th style="text-align: right; min-width: 125px; background: #e2e8f0 !important; color: #1e293b !important; font-weight: 800;">
                                        Rata-Rata
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowIdx = 1; @endphp
                                @forelse($sec['products'] as $pName => $pData)
                                    <tr class="{{ $pData['brand'] === 'AN' ? 'cbp-row-an' : '' }}">
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted);">{{ $rowIdx++ }}</td>
                                        <td>
                                            <span class="cbp-brand-badge {{ $pData['brand'] === 'AN' ? 'cbp-brand-an' : 'cbp-brand-comp' }}">
                                                {{ $pData['brand'] === 'AN' ? 'Dulux' : $pData['brand'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: {{ $pData['brand'] === 'AN' ? 'var(--brand-primary)' : 'var(--text-heading)' }};">
                                                {{ $pData['product'] }}
                                                @if($pData['is_benchmark'])
                                                    <span style="font-size: 0.7rem; font-weight: 800; background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">
                                                        BENCHMARK
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach($cbpData['months'] as $m => $mMeta)
                                            @php $pr = $pData['prices'][$m] ?? null; @endphp
                                            <td style="text-align: right; font-weight: {{ $pData['brand'] === 'AN' ? '700' : '500' }}; color: {{ $pr ? 'var(--text-heading)' : 'var(--text-muted)' }};">
                                                {{ $pr ? number_format($pr, 0, ',', '.') : '-' }}
                                            </td>
                                        @endforeach
                                        <td style="text-align: right; font-weight: 800; color: {{ $pData['brand'] === 'AN' ? 'var(--brand-primary)' : '#1e293b' }}; background: rgba(226, 232, 240, 0.4);">
                                            {{ $pData['avg_price'] > 0 ? number_format($pData['avg_price'], 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($cbpData['months']) + 4 }}" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            Tidak ada data untuk kategori ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 2. Table Price Index to AN Brands (%) -->
                    <div id="table_index_wrap_{{ $sKey }}" style="overflow-x: auto; display: none;">
                        <table class="custom-table cbp-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 90px;">Brand</th>
                                    <th style="min-width: 220px;">Sub Brand / Produk</th>
                                    @foreach($cbpData['months'] as $m => $mMeta)
                                        <th style="text-align: center; min-width: 100px;">{{ $mMeta['short'] }} {{ $endYear }}</th>
                                    @endforeach
                                    <th style="text-align: center; min-width: 120px; background: #e2e8f0 !important; color: #1e293b !important; font-weight: 800;">
                                        Rata-Rata Indeks
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowIdx = 1; @endphp
                                @forelse($sec['products'] as $pName => $pData)
                                    <tr class="{{ $pData['brand'] === 'AN' ? 'cbp-row-an' : '' }}">
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted);">{{ $rowIdx++ }}</td>
                                        <td>
                                            <span class="cbp-brand-badge {{ $pData['brand'] === 'AN' ? 'cbp-brand-an' : 'cbp-brand-comp' }}">
                                                {{ $pData['brand'] === 'AN' ? 'Dulux' : $pData['brand'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: {{ $pData['brand'] === 'AN' ? 'var(--brand-primary)' : 'var(--text-heading)' }};">
                                                {{ $pData['product'] }}
                                                @if($pData['is_benchmark'])
                                                    <span style="font-size: 0.7rem; font-weight: 800; background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">
                                                        100% ACUAN
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach($cbpData['months'] as $m => $mMeta)
                                            @php $idx = $pData['indices'][$m] ?? null; @endphp
                                            <td style="text-align: center;">
                                                @if($idx !== null)
                                                    @if($pData['is_benchmark'])
                                                        <span class="cbp-pill-bm">100.0%</span>
                                                    @elseif($idx < 100)
                                                        <span class="cbp-pill-cheaper" title="Kompetitor Lebih Murah">{{ number_format($idx, 1) }}%</span>
                                                    @else
                                                        <span class="cbp-pill-expensive" title="Kompetitor Lebih Mahal">{{ number_format($idx, 1) }}%</span>
                                                    @endif
                                                @else
                                                    <span style="color: var(--text-muted);">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="text-align: center; background: rgba(226, 232, 240, 0.4);">
                                            @if($pData['avg_index'] > 0)
                                                @if($pData['is_benchmark'])
                                                    <span class="cbp-pill-bm" style="font-size: 0.85rem;">100.0%</span>
                                                @elseif($pData['avg_index'] < 100)
                                                    <span class="cbp-pill-cheaper" style="font-size: 0.85rem;">{{ number_format($pData['avg_index'], 1) }}%</span>
                                                @else
                                                    <span class="cbp-pill-expensive" style="font-size: 0.85rem;">{{ number_format($pData['avg_index'], 1) }}%</span>
                                                @endif
                                            @else
                                                <span style="color: var(--text-muted);">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($cbpData['months']) + 4 }}" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            Tidak ada data untuk kategori ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- PANE 2: DASHBOARD (2) - ENAMEL & WATERPROOFING -->
    <div id="cbp_pane_d2" class="cbp-pane-container" style="display: none;">
        @foreach($cbpData['dashboard2'] as $sKey => $sec)
            <div class="cbp-sec-card" id="card_{{ $sKey }}">
                <div class="cbp-sec-header">
                    <div>
                        <div class="cbp-sec-title">
                            <i class="fa-solid {{ $sKey === 'enamel' ? 'fa-brush' : 'fa-droplet-slash' }}" style="color: var(--brand-primary); font-size: 1.15rem;"></i>
                            <span>{{ $sec['title'] }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                            <span class="cbp-benchmark-tag">
                                <i class="fa-solid fa-bullseye"></i> {{ $sec['benchmark_label'] }}
                            </span>
                            <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-muted); background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                <i class="fa-solid fa-box-open"></i> {{ $sec['unit'] }}
                            </span>
                            <span style="font-size: 0.76rem; color: #64748b;">
                                ({{ count($sec['products']) }} Sub Brand)
                            </span>
                        </div>
                    </div>

                    <!-- View Toggle: Price Table vs Index Table -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="cbp-toggle-group">
                            <button type="button" class="cbp-toggle-btn active" id="btn_price_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'price')">
                                <i class="fa-solid fa-rupiah-sign"></i> Rata-Rata MOP (Rp)
                            </button>
                            <button type="button" class="cbp-toggle-btn" id="btn_index_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'index')">
                                <i class="fa-solid fa-percent"></i> Price Index to AN (100%)
                            </button>
                        </div>
                    </div>
                </div>

                <div style="padding: 0;">
                    <!-- 1. Table Rata-Rata Harga MOP (Rp) -->
                    <div id="table_price_wrap_{{ $sKey }}" style="overflow-x: auto;">
                        <table class="custom-table cbp-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 90px;">Brand</th>
                                    <th style="min-width: 220px;">Sub Brand / Produk</th>
                                    @foreach($cbpData['months'] as $m => $mMeta)
                                        <th style="text-align: right; min-width: 110px;">{{ $mMeta['short'] }} {{ $endYear }}</th>
                                    @endforeach
                                    <th style="text-align: right; min-width: 125px; background: #e2e8f0 !important; color: #1e293b !important; font-weight: 800;">
                                        Rata-Rata
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowIdx = 1; @endphp
                                @forelse($sec['products'] as $pName => $pData)
                                    <tr class="{{ $pData['brand'] === 'AN' ? 'cbp-row-an' : '' }}">
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted);">{{ $rowIdx++ }}</td>
                                        <td>
                                            <span class="cbp-brand-badge {{ $pData['brand'] === 'AN' ? 'cbp-brand-an' : 'cbp-brand-comp' }}">
                                                {{ $pData['brand'] === 'AN' ? 'Dulux' : $pData['brand'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: {{ $pData['brand'] === 'AN' ? 'var(--brand-primary)' : 'var(--text-heading)' }};">
                                                {{ $pData['product'] }}
                                                @if($pData['is_benchmark'])
                                                    <span style="font-size: 0.7rem; font-weight: 800; background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">
                                                        BENCHMARK
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach($cbpData['months'] as $m => $mMeta)
                                            @php $pr = $pData['prices'][$m] ?? null; @endphp
                                            <td style="text-align: right; font-weight: {{ $pData['brand'] === 'AN' ? '700' : '500' }}; color: {{ $pr ? 'var(--text-heading)' : 'var(--text-muted)' }};">
                                                {{ $pr ? number_format($pr, 0, ',', '.') : '-' }}
                                            </td>
                                        @endforeach
                                        <td style="text-align: right; font-weight: 800; color: {{ $pData['brand'] === 'AN' ? 'var(--brand-primary)' : '#1e293b' }}; background: rgba(226, 232, 240, 0.4);">
                                            {{ $pData['avg_price'] > 0 ? number_format($pData['avg_price'], 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($cbpData['months']) + 4 }}" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            Tidak ada data untuk kategori ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 2. Table Price Index to AN Brands (%) -->
                    <div id="table_index_wrap_{{ $sKey }}" style="overflow-x: auto; display: none;">
                        <table class="custom-table cbp-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 90px;">Brand</th>
                                    <th style="min-width: 220px;">Sub Brand / Produk</th>
                                    @foreach($cbpData['months'] as $m => $mMeta)
                                        <th style="text-align: center; min-width: 100px;">{{ $mMeta['short'] }} {{ $endYear }}</th>
                                    @endforeach
                                    <th style="text-align: center; min-width: 120px; background: #e2e8f0 !important; color: #1e293b !important; font-weight: 800;">
                                        Rata-Rata Indeks
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowIdx = 1; @endphp
                                @forelse($sec['products'] as $pName => $pData)
                                    <tr class="{{ $pData['brand'] === 'AN' ? 'cbp-row-an' : '' }}">
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted);">{{ $rowIdx++ }}</td>
                                        <td>
                                            <span class="cbp-brand-badge {{ $pData['brand'] === 'AN' ? 'cbp-brand-an' : 'cbp-brand-comp' }}">
                                                {{ $pData['brand'] === 'AN' ? 'Dulux' : $pData['brand'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: {{ $pData['brand'] === 'AN' ? 'var(--brand-primary)' : 'var(--text-heading)' }};">
                                                {{ $pData['product'] }}
                                                @if($pData['is_benchmark'])
                                                    <span style="font-size: 0.7rem; font-weight: 800; background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">
                                                        100% ACUAN
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach($cbpData['months'] as $m => $mMeta)
                                            @php $idx = $pData['indices'][$m] ?? null; @endphp
                                            <td style="text-align: center;">
                                                @if($idx !== null)
                                                    @if($pData['is_benchmark'])
                                                        <span class="cbp-pill-bm">100.0%</span>
                                                    @elseif($idx < 100)
                                                        <span class="cbp-pill-cheaper" title="Kompetitor Lebih Murah">{{ number_format($idx, 1) }}%</span>
                                                    @else
                                                        <span class="cbp-pill-expensive" title="Kompetitor Lebih Mahal">{{ number_format($idx, 1) }}%</span>
                                                    @endif
                                                @else
                                                    <span style="color: var(--text-muted);">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="text-align: center; background: rgba(226, 232, 240, 0.4);">
                                            @if($pData['avg_index'] > 0)
                                                @if($pData['is_benchmark'])
                                                    <span class="cbp-pill-bm" style="font-size: 0.85rem;">100.0%</span>
                                                @elseif($pData['avg_index'] < 100)
                                                    <span class="cbp-pill-cheaper" style="font-size: 0.85rem;">{{ number_format($pData['avg_index'], 1) }}%</span>
                                                @else
                                                    <span class="cbp-pill-expensive" style="font-size: 0.85rem;">{{ number_format($pData['avg_index'], 1) }}%</span>
                                                @endif
                                            @else
                                                <span style="color: var(--text-muted);">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($cbpData['months']) + 4 }}" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            Tidak ada data untuk kategori ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

<script>
    function switchCbpMainTab(tabName) {
        // Nav Buttons
        document.querySelectorAll('.cbp-nav-btn').forEach(btn => btn.classList.remove('active'));
        var activeBtn = document.getElementById('btn_cbp_tab_' + tabName);
        if (activeBtn) activeBtn.classList.add('active');

        var paneD1 = document.getElementById('cbp_pane_d1');
        var paneD2 = document.getElementById('cbp_pane_d2');
        var canvas = document.getElementById('dashboard_canvas');

        if (tabName === 'd1') {
            if (paneD1) paneD1.style.display = 'block';
            if (paneD2) paneD2.style.display = 'none';
            if (canvas) canvas.style.display = 'none';
        } else if (tabName === 'd2') {
            if (paneD1) paneD1.style.display = 'none';
            if (paneD2) paneD2.style.display = 'block';
            if (canvas) canvas.style.display = 'none';
        } else if (tabName === 'raw') {
            if (paneD1) paneD1.style.display = 'none';
            if (paneD2) paneD2.style.display = 'none';
            if (canvas) canvas.style.display = 'grid';
            if (canvas) {
                canvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function toggleCbpTableType(sectionKey, type) {
        var btnPrice = document.getElementById('btn_price_' + sectionKey);
        var btnIndex = document.getElementById('btn_index_' + sectionKey);
        var wrapPrice = document.getElementById('table_price_wrap_' + sectionKey);
        var wrapIndex = document.getElementById('table_index_wrap_' + sectionKey);

        if (type === 'price') {
            if (btnPrice) btnPrice.classList.add('active');
            if (btnIndex) btnIndex.classList.remove('active');
            if (wrapPrice) wrapPrice.style.display = 'block';
            if (wrapIndex) wrapIndex.style.display = 'none';
        } else {
            if (btnPrice) btnPrice.classList.remove('active');
            if (btnIndex) btnIndex.classList.add('active');
            if (wrapPrice) wrapPrice.style.display = 'none';
            if (wrapIndex) wrapIndex.style.display = 'block';
        }
    }
</script>
