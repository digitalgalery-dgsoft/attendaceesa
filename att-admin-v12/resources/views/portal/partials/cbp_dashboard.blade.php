{{-- PORTAL CBP EXECUTIVE DASHBOARD (DASHBOARD 1 & DASHBOARD 2) --}}
<div class="custom-cbp-wrapper" style="margin-bottom: 2rem; width: 100%; max-width: 100%; min-width: 0;">

    <!-- TOP MAIN TAB NAVIGATION -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="cbp-main-nav">
            <button type="button" class="cbp-nav-btn active" id="btn_cbp_tab_d1" onclick="switchCbpMainTab('d1')">
                <i class="fa-solid fa-palette" style="font-size: 1rem;"></i>
                <span>Wallpaint (Cat Tembok)</span>
            </button>
            <button type="button" class="cbp-nav-btn" id="btn_cbp_tab_d2" onclick="switchCbpMainTab('d2')">
                <i class="fa-solid fa-shield-halved" style="font-size: 1rem;"></i>
                <span>WTP MCC (Enamel & Waterproofing)</span>
            </button>
            <button type="button" class="cbp-nav-btn" id="btn_cbp_tab_raw" onclick="switchCbpMainTab('raw')">
                <i class="fa-solid fa-file-excel" style="font-size: 1rem; color: #107c41;"></i>
                <span>Raw Data</span>
            </button>
            <button type="button" class="cbp-nav-btn" id="btn_cbp_tab_live" onclick="switchCbpMainTab('live')">
                <i class="fa-solid fa-list-check" style="font-size: 1rem; color: #2563eb;"></i>
                <span>Data Laporan Masuk</span>
                @if(isset($submissions) && $submissions->total() > 0)
                    <span style="background: #2563eb; color: #ffffff; font-size: 0.72rem; font-weight: 800; padding: 2px 7px; border-radius: 9999px; margin-left: 6px;">{{ $submissions->total() }}</span>
                @endif
            </button>
        </div>

        <div style="font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-clock-rotate-left"></i>
            @php
                $firstMonthLabel = !empty($cbpData['months']) ? ($cbpData['months'][min(array_keys($cbpData['months']))]['label'] ?? "Jan $endYear") : "Jan $endYear";
                $lastMonthLabel = !empty($cbpData['months']) ? ($cbpData['months'][max(array_keys($cbpData['months']))]['label'] ?? "Des $endYear") : "Des $endYear";
            @endphp
            <span>Periode Data: <strong>{{ $firstMonthLabel }} – {{ $lastMonthLabel }}</strong></span>
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

                    <!-- View Toggle: Price Table vs Index Table vs MoM Growth -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="cbp-toggle-group">
                            <button type="button" class="cbp-toggle-btn active" id="btn_price_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'price')">
                                <i class="fa-solid fa-rupiah-sign"></i> Rata-Rata MOP (Rp)
                            </button>
                            <button type="button" class="cbp-toggle-btn" id="btn_index_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'index')">
                                <i class="fa-solid fa-percent"></i> Price Index to AN (100%)
                            </button>
                            <button type="button" class="cbp-toggle-btn" id="btn_mom_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'mom')">
                                <i class="fa-solid fa-arrow-trend-up"></i> % MoM Growth
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

                    <!-- 3. Table MOP Increase Month on Month (% Growth MoM) -->
                    <div id="table_mom_wrap_{{ $sKey }}" style="overflow-x: auto; display: none;">
                        <table class="custom-table cbp-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 90px;">Brand</th>
                                    <th style="min-width: 220px;">Sub Brand / Produk</th>
                                    @foreach($cbpData['months'] as $m => $mMeta)
                                        <th style="text-align: center; min-width: 105px;">{{ $mMeta['short'] }} {{ $endYear }}</th>
                                    @endforeach
                                    <th style="text-align: center; min-width: 120px; background: #e2e8f0 !important; color: #1e293b !important; font-weight: 800;">
                                        Rata-Rata MoM
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
                                            @php $momVal = $pData['mom_growth'][$m] ?? null; @endphp
                                            <td style="text-align: center;">
                                                @if($momVal !== null)
                                                    @if($momVal > 0.0001)
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;" title="Naik {{ number_format($momVal, 2) }}%">
                                                            +{{ number_format($momVal, 2) }}%
                                                        </span>
                                                    @elseif($momVal < -0.0001)
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;" title="Turun {{ number_format(abs($momVal), 2) }}%">
                                                            {{ number_format($momVal, 2) }}%
                                                        </span>
                                                    @else
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.78rem; font-weight: 600; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                                                            0.00%
                                                        </span>
                                                    @endif
                                                @else
                                                    <span style="color: var(--text-muted);">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="text-align: center; background: rgba(226, 232, 240, 0.4);">
                                            @php $avgMom = $pData['avg_mom'] ?? null; @endphp
                                            @if($avgMom !== null)
                                                @if($avgMom > 0.0001)
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 800; background: #dcfce7; color: #15803d; border: 1px solid #86efac;">
                                                        +{{ number_format($avgMom, 2) }}%
                                                    </span>
                                                @elseif($avgMom < -0.0001)
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 800; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">
                                                        {{ number_format($avgMom, 2) }}%
                                                    </span>
                                                @else
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                                        0.00%
                                                    </span>
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

                    <!-- View Toggle: Price Table vs Index Table vs MoM Growth -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="cbp-toggle-group">
                            <button type="button" class="cbp-toggle-btn active" id="btn_price_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'price')">
                                <i class="fa-solid fa-rupiah-sign"></i> Rata-Rata MOP (Rp)
                            </button>
                            <button type="button" class="cbp-toggle-btn" id="btn_index_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'index')">
                                <i class="fa-solid fa-percent"></i> Price Index to AN (100%)
                            </button>
                            <button type="button" class="cbp-toggle-btn" id="btn_mom_{{ $sKey }}" onclick="toggleCbpTableType('{{ $sKey }}', 'mom')">
                                <i class="fa-solid fa-arrow-trend-up"></i> % MoM Growth
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

                    <!-- 3. Table MOP Increase Month on Month (% Growth MoM) -->
                    <div id="table_mom_wrap_{{ $sKey }}" style="overflow-x: auto; display: none;">
                        <table class="custom-table cbp-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 90px;">Brand</th>
                                    <th style="min-width: 220px;">Sub Brand / Produk</th>
                                    @foreach($cbpData['months'] as $m => $mMeta)
                                        <th style="text-align: center; min-width: 105px;">{{ $mMeta['short'] }} {{ $endYear }}</th>
                                    @endforeach
                                    <th style="text-align: center; min-width: 120px; background: #e2e8f0 !important; color: #1e293b !important; font-weight: 800;">
                                        Rata-Rata MoM
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
                                            @php $momVal = $pData['mom_growth'][$m] ?? null; @endphp
                                            <td style="text-align: center;">
                                                @if($momVal !== null)
                                                    @if($momVal > 0.0001)
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;" title="Naik {{ number_format($momVal, 2) }}%">
                                                            +{{ number_format($momVal, 2) }}%
                                                        </span>
                                                    @elseif($momVal < -0.0001)
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;" title="Turun {{ number_format(abs($momVal), 2) }}%">
                                                            {{ number_format($momVal, 2) }}%
                                                        </span>
                                                    @else
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.78rem; font-weight: 600; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                                                            0.00%
                                                        </span>
                                                    @endif
                                                @else
                                                    <span style="color: var(--text-muted);">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="text-align: center; background: rgba(226, 232, 240, 0.4);">
                                            @php $avgMom = $pData['avg_mom'] ?? null; @endphp
                                            @if($avgMom !== null)
                                                @if($avgMom > 0.0001)
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 800; background: #dcfce7; color: #15803d; border: 1px solid #86efac;">
                                                        +{{ number_format($avgMom, 2) }}%
                                                    </span>
                                                @elseif($avgMom < -0.0001)
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 800; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">
                                                        {{ number_format($avgMom, 2) }}%
                                                    </span>
                                                @else
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                                        0.00%
                                                    </span>
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

    <!-- PANE 3: RAW DATA (DESAIN SESUAI TEMPLATE BAWAAN PORTAL) -->
    <div id="cbp_pane_raw" class="cbp-pane-container" style="display: none;">
        @php
            $rawData = $cbpData['raw_data'] ?? ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0, 'from' => 0, 'to' => 0];
            $rawRows = $rawData['rows'] ?? [];
            $activeMonths = $rawData['months'] ?? ($cbpData['months'] ?? []);
            $totalMonthCols = count($activeMonths) * 9;
            $totalTableCols = 12 + $totalMonthCols;
        @endphp

        <div class="cbp-sec-card" style="margin-bottom: 1.5rem;">
            <!-- Header Card Bawaan Template -->
            <div class="cbp-sec-header">
                <div>
                    <div class="cbp-sec-title">
                        <i class="fa-solid fa-table-cells" style="color: var(--brand-primary); font-size: 1.15rem;"></i>
                        <span>Rincian Data Mentah CBP 2026 (Raw Data)</span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                        Menampilkan data ke-<strong>{{ number_format($rawData['from']) }}</strong> – <strong>{{ number_format($rawData['to']) }}</strong> dari total <strong>{{ number_format($rawData['total']) }}</strong> item toko & produk (<strong>{{ count($activeMonths) }}</strong> bulan terpantau)
                    </div>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'p' => $tenantPrincipal->id])) }}" class="btn-export-excel" style="padding: 0.5rem 1rem; font-size: 0.82rem; font-weight: 700; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Export Excel (Raw Data)</span>
                    </a>
                </div>
            </div>

            <!-- Viewport Table Sesuai Template Bawaan -->
            <div class="cbp-raw-viewport">
                <table class="cbp-raw-table">
                    <thead>
                        <!-- Baris 1: Header Kolom Utama & Merged Date Kemasan Sesuai Filter Bulan -->
                        <tr>
                            <th rowspan="2" style="text-align: center; width: 60px;">Regional</th>
                            <th rowspan="2" style="text-align: center; width: 85px;">SAP Member</th>
                            <th rowspan="2" style="text-align: center; width: 80px;">SAP Gab</th>
                            <th rowspan="2" style="text-align: left; min-width: 200px;">Nama Toko</th>
                            <th rowspan="2" style="text-align: left; min-width: 170px;">Nama TL</th>
                            <th rowspan="2" style="text-align: left; min-width: 110px;">Area Sales</th>
                            <th rowspan="2" style="text-align: left; min-width: 110px;">RSM Area</th>
                            <th rowspan="2" style="text-align: center; width: 60px;">Class</th>
                            <th rowspan="2" style="text-align: center; width: 75px;">Type</th>
                            <th rowspan="2" style="text-align: left; min-width: 150px;">Product</th>
                            <th rowspan="2" style="text-align: left; min-width: 160px;">Category</th>
                            <th rowspan="2" style="text-align: left; min-width: 140px;">Product Group</th>
                            @foreach($activeMonths as $mKey => $mInfo)
                                <th colspan="9" style="text-align: center; font-weight: 800; background: #eff6ff; color: var(--brand-primary); border-left: 2px solid #cbd5e1; font-size: 0.82rem; padding: 0.65rem 1rem;">
                                    {{ $mInfo['date_header'] ?? ($mInfo['label'] ?? 'Bulan ' . $mKey) }}
                                </th>
                            @endforeach
                        </tr>
                        <!-- Baris 2: Sub Header Kemasan Sesuai Setiap Bulan Terfilter -->
                        <tr>
                            @foreach($activeMonths as $mKey => $mInfo)
                                <th style="text-align: right; min-width: 90px; border-left: 2px solid #cbd5e1;">Tin</th>
                                <th style="text-align: right; min-width: 95px;">Harga Terendah</th>
                                <th style="text-align: center; min-width: 80px;">REASON</th>
                                <th style="text-align: right; min-width: 90px; border-left: 1px dashed #cbd5e1;">Galon</th>
                                <th style="text-align: right; min-width: 95px;">Harga Terendah</th>
                                <th style="text-align: center; min-width: 80px;">REASON</th>
                                <th style="text-align: right; min-width: 95px; border-left: 1px dashed #cbd5e1;">Pail</th>
                                <th style="text-align: right; min-width: 95px;">Harga Terendah</th>
                                <th style="text-align: center; min-width: 80px;">REASON</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rawRows as $r)
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--text-muted);">{{ $r['regional'] }}</td>
                                <td style="text-align: center; color: var(--text-muted);">{{ $r['sap_member'] }}</td>
                                <td style="text-align: center; color: var(--text-muted);">{{ $r['sap_gab'] }}</td>
                                <td style="font-weight: 700; color: var(--text-heading); text-align: left;">{{ $r['name_store'] }}</td>
                                <td style="text-align: left; color: var(--text-muted);">{{ $r['tl_name'] }}</td>
                                <td style="text-align: left; color: var(--text-muted);">{{ $r['area'] }}</td>
                                <td style="text-align: left; color: var(--text-muted);">{{ $r['rsm_area'] }}</td>
                                <td style="text-align: center;">
                                    @if(!empty($r['class']))
                                        <span class="cbp-brand-badge cbp-brand-comp">{{ $r['class'] }}</span>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if(!empty($r['store_type']))
                                        <span class="cbp-brand-badge cbp-brand-comp">{{ $r['store_type'] }}</span>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: left; font-weight: 600; color: var(--text-heading);">
                                    {{ $r['product'] }}
                                    @if(!empty($r['is_live']))
                                        <span style="font-size: 0.68rem; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; border: 1px solid #bfdbfe; margin-left: 4px;" title="Data Terkini dari Aplikasi Mobile">
                                            <i class="fa-solid fa-mobile-screen"></i> Live
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: left; color: var(--text-muted);">{{ $r['category'] }}</td>
                                <td style="text-align: left; color: var(--text-muted);">{{ $r['product_group'] }}</td>

                                @foreach($activeMonths as $mKey => $mInfo)
                                    @php
                                        $mp = $r['monthly_prices'][$mKey] ?? null;
                                        $pTin = (!empty($mp['price_tin']) && $mp['price_tin'] > 0) ? (float)$mp['price_tin'] : (!empty($mp['lowest_tin']) ? (float)$mp['lowest_tin'] : 0);
                                        $lTin = (!empty($mp['lowest_tin']) && $mp['lowest_tin'] > 0) ? (float)$mp['lowest_tin'] : $pTin;
                                        $rTin = $mp['reason_tin'] ?? '';
                                        $hasTin = ($pTin > 0 || $lTin > 0);

                                        $pGalon = (!empty($mp['price_galon']) && $mp['price_galon'] > 0) ? (float)$mp['price_galon'] : (!empty($mp['lowest_galon']) ? (float)$mp['lowest_galon'] : 0);
                                        $lGalon = (!empty($mp['lowest_galon']) && $mp['lowest_galon'] > 0) ? (float)$mp['lowest_galon'] : $pGalon;
                                        $rGalon = $mp['reason_galon'] ?? '';
                                        $hasGalon = ($pGalon > 0 || $lGalon > 0);

                                        $pPail = (!empty($mp['price_pail']) && $mp['price_pail'] > 0) ? (float)$mp['price_pail'] : (!empty($mp['lowest_pail']) ? (float)$mp['lowest_pail'] : 0);
                                        $lPail = (!empty($mp['lowest_pail']) && $mp['lowest_pail'] > 0) ? (float)$mp['lowest_pail'] : $pPail;
                                        $rPail = $mp['reason_pail'] ?? '';
                                        $hasPail = ($pPail > 0 || $lPail > 0);

                                        $allEmpty = !$hasTin && !$hasGalon && !$hasPail;
                                    @endphp

                                    <!-- Kemasan Tin -->
                                    @if($hasTin)
                                        <td style="text-align: right; font-weight: 700; color: var(--text-heading); border-left: 2px solid #cbd5e1;">{{ number_format($pTin, 0, ',', '.') }}</td>
                                        <td style="text-align: right; color: var(--text-muted);">{{ number_format($lTin, 0, ',', '.') }}</td>
                                        <td style="text-align: center;">{{ $rTin ?: '-' }}</td>
                                    @else
                                        <td class="cell-peach-portal" style="border-left: 2px solid #cbd5e1;"></td>
                                        <td class="cell-peach-portal"></td>
                                        <td class="cell-peach-portal" style="text-align: center; font-style: italic; font-size: 0.76rem; color: #c2410c;">{{ $rTin }}</td>
                                    @endif

                                    <!-- Kemasan Galon -->
                                    @if($hasGalon)
                                        <td style="text-align: right; font-weight: 700; color: var(--text-heading); border-left: 1px dashed #e2e8f0;">{{ number_format($pGalon, 0, ',', '.') }}</td>
                                        <td style="text-align: right; color: var(--text-muted);">{{ number_format($lGalon, 0, ',', '.') }}</td>
                                        <td style="text-align: center;">{{ $rGalon ?: '-' }}</td>
                                    @else
                                        <td class="cell-peach-portal" style="border-left: 1px dashed #fed7aa;"></td>
                                        <td class="cell-peach-portal"></td>
                                        <td class="cell-peach-portal" style="text-align: center; font-style: italic; font-size: 0.76rem; color: #c2410c;">{{ $rGalon }}</td>
                                    @endif

                                    <!-- Kemasan Pail -->
                                    @if($hasPail)
                                        <td style="text-align: right; font-weight: 700; color: var(--text-heading); border-left: 1px dashed #e2e8f0;">{{ number_format($pPail, 0, ',', '.') }}</td>
                                        <td style="text-align: right; color: var(--text-muted);">{{ number_format($lPail, 0, ',', '.') }}</td>
                                        <td style="text-align: center;">{{ $rPail ?: '-' }}</td>
                                    @else
                                        <td class="cell-peach-portal" style="border-left: 1px dashed #fed7aa;"></td>
                                        <td class="cell-peach-portal"></td>
                                        <td class="cell-peach-portal" style="text-align: center; font-style: italic; font-size: 0.76rem; font-weight: 600; color: #c2410c;">
                                            {{ (!empty($rPail) ? $rPail : ($allEmpty ? 'Not Exist' : '')) }}
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalTableCols }}" style="text-align: center; padding: 3rem; color: var(--text-muted); font-size: 0.95rem;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 0.5rem; color: #cbd5e1; display: block;"></i>
                                    Tidak ada data Raw Data yang cocok dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar Sesuai Template Bawaan -->
            @if($rawData['total_pages'] > 1)
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.25rem; background: #f8fafc; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Halaman <strong>{{ $rawData['page'] }}</strong> dari <strong>{{ number_format($rawData['total_pages']) }}</strong> (Total {{ number_format($rawData['total']) }} baris)
                    </div>

                    <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                        @php
                            $currP = $rawData['page'];
                            $totP = $rawData['total_pages'];
                            $startP = max(1, $currP - 2);
                            $endP = min($totP, $currP + 2);
                        @endphp

                        @if($currP > 1)
                            <a href="{{ request()->fullUrlWithQuery(['tab' => 'raw', 'raw_page' => 1]) }}" class="excel-page-btn" title="Halaman Pertama">« Pertama</a>
                            <a href="{{ request()->fullUrlWithQuery(['tab' => 'raw', 'raw_page' => $currP - 1]) }}" class="excel-page-btn" title="Sebelumnya">‹ Sebelumnya</a>
                        @endif

                        @if($startP > 1)
                            <span style="padding: 0 4px; color: #94a3b8;">...</span>
                        @endif

                        @for($p = $startP; $p <= $endP; $p++)
                            <a href="{{ request()->fullUrlWithQuery(['tab' => 'raw', 'raw_page' => $p]) }}" class="excel-page-btn {{ $p === $currP ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($endP < $totP)
                            <span style="padding: 0 4px; color: #94a3b8;">...</span>
                        @endif

                        @if($currP < $totP)
                            <a href="{{ request()->fullUrlWithQuery(['tab' => 'raw', 'raw_page' => $currP + 1]) }}" class="excel-page-btn" title="Berikutnya">Berikutnya ›</a>
                            <a href="{{ request()->fullUrlWithQuery(['tab' => 'raw', 'raw_page' => $totP]) }}" class="excel-page-btn" title="Halaman Terakhir">Terakhir »</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- PANE 4: DATA LAPORAN MASUK TERKINI (LIVE SUBMISSIONS DARI MOBILE / ADMIN) -->
    <div id="cbp_pane_live" class="cbp-pane-container" style="display: none;">
        <div class="cbp-sec-card" style="margin-bottom: 1.5rem;">
            <div class="cbp-sec-header">
                <div>
                    <div class="cbp-sec-title">
                        <i class="fa-solid fa-clipboard-check" style="color: var(--brand-primary); font-size: 1.15rem;"></i>
                        <span>Data Laporan Masuk Terkini (Live Submissions)</span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                        Daftar isian laporan CBP yang dikirim langsung oleh SPG / Promotor melalui aplikasi mobile & form pelaporan.
                    </div>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size: 0.82rem; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 0.4rem 0.85rem; border-radius: 8px; border: 1px solid #bfdbfe;">
                        Total {{ $submissions->total() }} Laporan Masuk
                    </span>
                </div>
            </div>

            @if($submissions->isNotEmpty())
                <div style="overflow-x: auto;">
                    <table class="custom-table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 150px;">Kode Laporan</th>
                                <th style="min-width: 140px;">Waktu Submit</th>
                                <th style="min-width: 180px;">Promotor / SPG</th>
                                <th style="min-width: 190px;">Nama Toko / Outlet</th>
                                <th style="min-width: 140px;">Area Sales & RSM</th>
                                <th style="min-width: 160px;">Produk / Brand</th>
                                <th style="min-width: 140px;">Kategori</th>
                                <th style="min-width: 150px; text-align: right;">Harga (Galon / Tin / Pail)</th>
                                <th style="text-align: center; width: 100px;">Radius GPS</th>
                                <th style="text-align: center; width: 120px;">Status</th>
                                <th style="text-align: center; width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $idx => $sub)
                                @php
                                    $valMap = [];
                                    foreach ($sub->values as $v) {
                                        $valMap[$v->field_name] = $v->value_number ?? $v->value_text;
                                    }
                                    $pName = $valMap['subbrand_produk'] ?? $valMap['product'] ?? '-';
                                    $bName = $valMap['brand_cat'] ?? $valMap['brand'] ?? '-';
                                    $catName = $valMap['kategori_produk'] ?? $valMap['category'] ?? '-';
                                    $pGalon = (float)($valMap['harga_galon_rp'] ?? 0);
                                    $pTin = (float)($valMap['harga_tin_rp'] ?? 0);
                                    $pPail = (float)($valMap['harga_pail_rp'] ?? 0);
                                    $status = $sub->status ?? 'pending';
                                    $store = $sub->workLocation?->name ?? 'Toko Tidak Terdaftar';
                                    $branch = $sub->workLocation?->branch?->name ?? '-';
                                @endphp
                                <tr>
                                    <td style="color: var(--text-muted); font-weight: 700; text-align: center;">
                                        {{ $submissions->firstItem() + $idx }}
                                    </td>
                                    <td>
                                        <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" style="font-family: monospace; font-weight: 700; font-size: 0.82rem; color: #0F52BA; text-decoration: none; background: rgba(15, 82, 186, 0.08); padding: 3px 8px; border-radius: 6px; border: 1px solid rgba(15, 82, 186, 0.2); display: inline-block;">
                                            {{ $sub->submission_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading); font-size: 0.85rem;">
                                            {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y') : '-' }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            {{ $sub->submitted_at ? $sub->submitted_at->format('H:i') : '-' }} WIB
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading); font-size: 0.85rem;">
                                            {{ $sub->employee?->full_name ?? $sub->employee?->name ?? 'Petugas' }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                            {{ $sub->employee?->nik ?? ($sub->employee?->employee_no ?? '-') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading); font-size: 0.85rem;">
                                            {{ $store }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            Kode: {{ $sub->workLocation?->code ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-heading); font-size: 0.82rem;">
                                            {{ $branch }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted);">
                                            {{ $sub->workLocation?->region ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--brand-primary); font-size: 0.85rem;">
                                            {{ $pName }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            {{ $bName }}
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px;">
                                            {{ $catName }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        @if($pGalon > 0)
                                            <div style="font-weight: 700; color: var(--text-heading); font-size: 0.82rem;">
                                                G: Rp {{ number_format($pGalon, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        @if($pTin > 0)
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                T: Rp {{ number_format($pTin, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        @if($pPail > 0)
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                P: Rp {{ number_format($pPail, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        @if($pGalon <= 0 && $pTin <= 0 && $pPail <= 0)
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($sub->is_within_radius)
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.25rem 0.6rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-circle-check"></i> Valid
                                            </span>
                                        @else
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.6rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Luar Radius
                                            </span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if(in_array($status, ['approved', 'verified']))
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.25rem 0.6rem; border-radius: 8px;">
                                                Terverifikasi
                                            </span>
                                        @elseif($status === 'rejected')
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 0.25rem 0.6rem; border-radius: 8px;">
                                                Ditolak
                                            </span>
                                        @else
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.6rem; border-radius: 8px;">
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" style="display: inline-flex; align-items: center; gap: 4px; padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none; background: #f1f5f9; color: #0F52BA; border: 1px solid #cbd5e1; transition: all 0.15s ease;" onmouseover="this.style.background='#0F52BA'; this.style.color='#fff';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#0F52BA';">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($submissions->hasPages())
                    <div style="padding: 1rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--border-color); border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        {{ $submissions->appends(request()->query())->links('portal.pagination') }}
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <i class="fa-solid fa-clipboard-question" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1; display: block;"></i>
                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-heading);">Tidak Ada Data Laporan Masuk</div>
                    <p style="font-size: 0.85rem; max-width: 420px; margin: 0.35rem auto 0;">
                        Belum ada laporan CBP yang dikirimkan pada filter periode / area yang dipilih.
                    </p>
                </div>
            @endif
        </div>
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
        var paneRaw = document.getElementById('cbp_pane_raw');
        var paneLive = document.getElementById('cbp_pane_live');
        var canvas = document.getElementById('dashboard_canvas');

        if (paneD1) paneD1.style.display = (tabName === 'd1') ? 'block' : 'none';
        if (paneD2) paneD2.style.display = (tabName === 'd2') ? 'block' : 'none';
        if (paneRaw) paneRaw.style.display = (tabName === 'raw') ? 'block' : 'none';
        if (paneLive) paneLive.style.display = (tabName === 'live') ? 'block' : 'none';
        if (canvas) canvas.style.display = 'none';

        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', function() {
        var urlParams = new URLSearchParams(window.location.search);
        var activeTab = urlParams.get('tab');
        if (activeTab === 'raw' || urlParams.get('raw_page')) {
            switchCbpMainTab('raw');
        } else if (activeTab === 'live' || urlParams.get('page')) {
            switchCbpMainTab('live');
        }
    });

    function toggleCbpTableType(sectionKey, type) {
        var btnPrice = document.getElementById('btn_price_' + sectionKey);
        var btnIndex = document.getElementById('btn_index_' + sectionKey);
        var btnMom = document.getElementById('btn_mom_' + sectionKey);
        var wrapPrice = document.getElementById('table_price_wrap_' + sectionKey);
        var wrapIndex = document.getElementById('table_index_wrap_' + sectionKey);
        var wrapMom = document.getElementById('table_mom_wrap_' + sectionKey);

        if (btnPrice) btnPrice.classList.remove('active');
        if (btnIndex) btnIndex.classList.remove('active');
        if (btnMom) btnMom.classList.remove('active');
        if (wrapPrice) wrapPrice.style.display = 'none';
        if (wrapIndex) wrapIndex.style.display = 'none';
        if (wrapMom) wrapMom.style.display = 'none';

        if (type === 'price') {
            if (btnPrice) btnPrice.classList.add('active');
            if (wrapPrice) wrapPrice.style.display = 'block';
        } else if (type === 'index') {
            if (btnIndex) btnIndex.classList.add('active');
            if (wrapIndex) wrapIndex.style.display = 'block';
        } else if (type === 'mom') {
            if (btnMom) btnMom.classList.add('active');
            if (wrapMom) wrapMom.style.display = 'block';
        }
    }
</script>
