{{-- CUSTOMER DATABASE & CONSUMER INSIGHTS EXECUTIVE DASHBOARD --}}
<div class="cust-executive-wrapper">

    {{-- TOP TOOLBAR: TAB NAVIGATION & EXPORT BUTTONS --}}
    <div class="cust-top-toolbar">
        {{-- TABS NAVIGATION --}}
        <div class="cust-tabs-container">
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'insights', 'p' => $tenantPrincipal->id])) }}" 
               class="cust-tab-btn {{ ($activeTab ?? 'insights') === 'insights' ? 'active' : '' }}">
                <i class="fa-solid fa-users-viewfinder"></i>
                <span>Profil & Perilaku Konsumen</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'regional_store', 'p' => $tenantPrincipal->id])) }}" 
               class="cust-tab-btn {{ ($activeTab ?? '') === 'regional_store' ? 'active' : '' }}">
                <i class="fa-solid fa-store"></i>
                <span>Analisis Toko & Wilayah</span>
                <span class="badge-count">{{ number_format($custData['top_stores']['total'] ?? 0) }}</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
               class="cust-tab-btn {{ ($activeTab ?? '') === 'raw' ? 'active' : '' }}">
                <i class="fa-solid fa-list-check"></i>
                <span>Data Mentah Pelanggan</span>
                <span class="badge-count">{{ number_format($custData['submissions']['total'] ?? 0) }}</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'live', 'p' => $tenantPrincipal->id])) }}" 
               class="cust-tab-btn {{ ($activeTab ?? '') === 'live' ? 'active' : '' }}">
                <i class="fa-solid fa-inbox text-primary"></i>
                <span>Data Laporan Masuk</span>
                @if(isset($submissions) && $submissions->total() > 0)
                    <span class="badge-count" style="background: #2563eb; color: #ffffff;">{{ $submissions->total() }}</span>
                @elseif(isset($liveSubmissionsCount) && $liveSubmissionsCount > 0)
                    <span class="badge-count" style="background: #2563eb; color: #ffffff;">{{ $liveSubmissionsCount }}</span>
                @endif
            </a>
        </div>

        {{-- EXPORT BUTTONS --}}
        <div class="cust-export-actions">
            <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'cust_stores', 'p' => $tenantPrincipal->id])) }}" 
               class="btn-cust-export-action success" title="Download Rekapitulasi Wilayah & Toko (CSV)">
                <i class="fa-solid fa-file-excel"></i>
                <span>Export Rekap Toko & Wilayah</span>
            </a>
            <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'cust_raw', 'p' => $tenantPrincipal->id])) }}" 
               class="btn-cust-export-action primary" title="Download Data Mentah Konsumen Lengkap (CSV)">
                <i class="fa-solid fa-file-csv"></i>
                <span>Export Data Mentah</span>
            </a>
        </div>
    </div>

    {{-- EXECUTIVE KPI HIGHLIGHT CARDS (6 CARDS) --}}
    @php
        $totVal = (float)($custData['kpis']['total_value'] ?? 0);
        if ($totVal >= 1000000000) {
            $totValFormatted = 'Rp ' . number_format($totVal / 1000000000, 2);
            $totValUnit = 'Miliar';
        } elseif ($totVal >= 1000000) {
            $totValFormatted = 'Rp ' . number_format($totVal / 1000000, 2);
            $totValUnit = 'Juta';
        } else {
            $totValFormatted = 'Rp ' . number_format($totVal, 0, ',', '.');
            $totValUnit = '';
        }

        $avgVal = (float)($custData['kpis']['avg_basket_size'] ?? 0);
        if ($avgVal >= 1000000000) {
            $avgValFormatted = 'Rp ' . number_format($avgVal / 1000000000, 2);
            $avgValUnit = 'Miliar';
        } elseif ($avgVal >= 1000000) {
            $avgValFormatted = 'Rp ' . number_format($avgVal / 1000000, 2);
            $avgValUnit = 'Juta';
        } else {
            $avgValFormatted = 'Rp ' . number_format($avgVal, 0, ',', '.');
            $avgValUnit = '';
        }
    @endphp
    <div class="cust-kpi-grid">
        <div class="cust-kpi-card">
            <div class="cust-kpi-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="cust-kpi-content">
                <span class="cust-kpi-label">Total Konsumen Terdata</span>
                <div class="cust-kpi-val">{{ number_format($custData['kpis']['total_records'] ?? 0) }} <span class="cust-kpi-unit">Orang</span></div>
                <span class="cust-kpi-sub">Profil pembeli di toko cat</span>
            </div>
        </div>

        <div class="cust-kpi-card">
            <div class="cust-kpi-icon emerald">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
            <div class="cust-kpi-content">
                <span class="cust-kpi-label">Total Nilai Transaksi</span>
                <div class="cust-kpi-val" title="Nilai riil: Rp {{ number_format($totVal, 0, ',', '.') }}">
                    {{ $totValFormatted }}
                    @if($totValUnit)
                        <span class="cust-kpi-unit">{{ $totValUnit }}</span>
                    @endif
                </div>
                <span class="cust-kpi-sub">Estimasi belanja cat & material</span>
            </div>
        </div>

        <div class="cust-kpi-card">
            <div class="cust-kpi-icon purple">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="cust-kpi-content">
                <span class="cust-kpi-label">Rata-Rata Belanja (Basket Size)</span>
                <div class="cust-kpi-val" title="Nilai riil: Rp {{ number_format($avgVal, 0, ',', '.') }}">
                    {{ $avgValFormatted }}
                    @if($avgValUnit)
                        <span class="cust-kpi-unit">{{ $avgValUnit }}</span>
                    @endif
                </div>
                <span class="cust-kpi-sub">Rata-rata nilai per konsumen</span>
            </div>
        </div>

        <div class="cust-kpi-card">
            <div class="cust-kpi-icon amber">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="cust-kpi-content">
                <span class="cust-kpi-label">Toko Aktif Mendata</span>
                <div class="cust-kpi-val">{{ number_format($custData['kpis']['unique_stores'] ?? 0) }} <span class="cust-kpi-unit">Toko</span></div>
                <span class="cust-kpi-sub">Outlet dengan database konsumen</span>
            </div>
        </div>

        <div class="cust-kpi-card">
            <div class="cust-kpi-icon indigo">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="cust-kpi-content">
                <span class="cust-kpi-label">DC / Promotor Terlibat</span>
                <div class="cust-kpi-val">{{ number_format($custData['kpis']['unique_dcs'] ?? 0) }} <span class="cust-kpi-unit">DC</span></div>
                <span class="cust-kpi-sub">Petugas promosi lapangan</span>
            </div>
        </div>

        <div class="cust-kpi-card">
            <div class="cust-kpi-icon teal">
                <i class="fa-solid fa-shuffle"></i>
            </div>
            <div class="cust-kpi-content">
                <span class="cust-kpi-label">Konversi Beralih ke Dulux</span>
                <div class="cust-kpi-val">{{ number_format($custData['kpis']['switched_cnt'] ?? 0) }} <span class="cust-kpi-unit">({{ $custData['kpis']['switched_pct'] ?? 0 }}%)</span></div>
                <span class="cust-kpi-sub">Konsumen kompetitor beralih ke Dulux</span>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB 1: PROFIL & PERILAKU KONSUMEN (CONSUMER INSIGHTS) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? 'insights') === 'insights')
        <div class="cust-insights-grid">
            
            {{-- CARD 1: SEGMENTASI TIPE PELANGGAN --}}
            <div class="cust-card">
                <div class="cust-card-header">
                    <div>
                        <h3 class="cust-card-title"><i class="fa-solid fa-id-badge text-blue"></i> Segmentasi Tipe Pelanggan</h3>
                        <p class="cust-card-sub">Distribusi profil pembeli cat dan kontribusi nilai belanja per segmen</p>
                    </div>
                </div>
                <div class="cust-card-body">
                    <div class="cust-segment-list">
                        @foreach($custData['insights']['customer_types'] ?? [] as $t)
                            <div class="cust-segment-item">
                                <div class="cust-segment-info">
                                    <div class="cust-segment-title-wrap">
                                        <span class="cust-segment-name">{{ $t['tipe_pelanggan'] ?: 'Lainnya' }}</span>
                                        <span class="badge-seg-count">{{ number_format($t['total_count']) }} Orang ({{ $t['pct'] }}%)</span>
                                    </div>
                                    <div class="cust-segment-val-wrap">
                                        <span class="cust-segment-val">Rp {{ number_format($t['total_val'], 0, ',', '.') }}</span>
                                        <span class="cust-segment-avg">Rata-rata: Rp {{ number_format($t['avg_val'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="cust-progress-bar-bg">
                                    <div class="cust-progress-bar-fill blue" style="width: {{ min(100, max(5, $t['pct'])) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- CARD 2: ALASAN MEMILIH BRAND CAT --}}
            <div class="cust-card">
                <div class="cust-card-header">
                    <div>
                        <h3 class="cust-card-title"><i class="fa-solid fa-comment-dots text-emerald"></i> Alasan Memilih Brand Cat</h3>
                        <p class="cust-card-sub">Faktor utama pendorong keputusan pembelian konsumen di toko</p>
                    </div>
                </div>
                <div class="cust-card-body">
                    <div class="cust-segment-list">
                        @foreach($custData['insights']['reasons'] ?? [] as $r)
                            <div class="cust-segment-item">
                                <div class="cust-segment-info">
                                    <span class="cust-segment-name">{{ $r['alasan'] ?: 'Lainnya' }}</span>
                                    <span class="badge-seg-count">{{ number_format($r['total_count']) }} ({{ $r['pct'] }}%)</span>
                                </div>
                                <div class="cust-progress-bar-bg">
                                    <div class="cust-progress-bar-fill emerald" style="width: {{ min(100, max(5, $r['pct'])) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- CARD 3: BRAND PREFERENCE (BRAND DICARI VS DIBELI) --}}
            <div class="cust-card col-span-2">
                <div class="cust-card-header">
                    <div>
                        <h3 class="cust-card-title"><i class="fa-solid fa-tags text-purple"></i> Preferensi Brand: Brand Dicari vs Brand Dibeli</h3>
                        <p class="cust-card-sub">Perbandingan brand yang awal mula ditanyakan konsumen vs produk yang akhirnya dibeli</p>
                    </div>
                </div>
                <div class="cust-card-body">
                    <div class="brand-comparison-grid">
                        <div class="brand-sub-col">
                            <h4 class="brand-col-title sought"><i class="fa-solid fa-magnifying-glass"></i> Top 8 Brand Awal Dicari</h4>
                            <div class="brand-list">
                                @foreach($custData['insights']['brands_sought'] ?? [] as $bs)
                                    <div class="brand-pill-item sought">
                                        <span class="brand-name">{{ $bs['brand_dicari'] ?: 'Lainnya' }}</span>
                                        <span class="brand-count">{{ number_format($bs['cnt']) }} <small>({{ $bs['pct'] }}%)</small></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="brand-sub-col">
                            <h4 class="brand-col-title bought"><i class="fa-solid fa-bag-shopping"></i> Top 8 Brand Akhirnya Dibeli</h4>
                            <div class="brand-list">
                                @foreach($custData['insights']['brands_bought'] ?? [] as $bb)
                                    <div class="brand-pill-item bought">
                                        <span class="brand-name">{{ $bb['brand_dibeli'] ?: 'Lainnya' }}</span>
                                        <span class="brand-count">{{ number_format($bb['cnt']) }} <small>({{ $bb['pct'] }}%)</small></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 4: TUJUAN KE TOKO --}}
            <div class="cust-card">
                <div class="cust-card-header">
                    <div>
                        <h3 class="cust-card-title"><i class="fa-solid fa-door-open text-amber"></i> Tujuan Datang ke Toko</h3>
                        <p class="cust-card-sub">Maksud kedatangan konsumen ke toko cat</p>
                    </div>
                </div>
                <div class="cust-card-body">
                    <div class="cust-segment-list">
                        @foreach($custData['insights']['purposes'] ?? [] as $p)
                            <div class="cust-segment-item">
                                <div class="cust-segment-info">
                                    <span class="cust-segment-name">{{ $p['tujuan_ke_toko'] ?: 'Lainnya' }}</span>
                                    <span class="badge-seg-count">{{ number_format($p['total_count']) }} ({{ $p['pct'] }}%)</span>
                                </div>
                                <div class="cust-progress-bar-bg">
                                    <div class="cust-progress-bar-fill amber" style="width: {{ min(100, max(5, $p['pct'])) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- CARD 5: TIPE PENORONG / KEBUTUHAN LAINNYA --}}
            <div class="cust-card">
                <div class="cust-card-header">
                    <div>
                        <h3 class="cust-card-title"><i class="fa-solid fa-paint-roller text-indigo"></i> Tipe Pekerjaan & Layanan</h3>
                        <p class="cust-card-sub">Jenis proyek dan permintaan demo visualizer warna</p>
                    </div>
                </div>
                <div class="cust-card-body">
                    <div class="sub-insight-section">
                        <span class="sub-insight-heading">Tipe Pengecatan:</span>
                        <div class="chip-row">
                            @foreach($custData['insights']['paint_types'] ?? [] as $pt)
                                <div class="chip-item">
                                    <span class="chip-label">{{ $pt['tipe_pengecatan'] ?: 'Lainnya' }}</span>
                                    <span class="chip-val">{{ number_format($pt['total_count']) }} ({{ $pt['pct'] }}%)</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="sub-insight-section mt-4">
                        <span class="sub-insight-heading">Memerlukan Preview / Visualizer:</span>
                        <div class="chip-row">
                            @foreach($custData['insights']['preview_needs'] ?? [] as $pn)
                                <div class="chip-item {{ $pn['memerlukan_preview'] === 'Ya' ? 'highlight-emerald' : '' }}">
                                    <span class="chip-label">{{ $pn['memerlukan_preview'] ?: 'Tidak' }}</span>
                                    <span class="chip-val">{{ number_format($pn['total_count']) }} ({{ $pn['pct'] }}%)</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="sub-insight-section mt-4">
                        <span class="sub-insight-heading">Program Loyalitas Mitra Dulux:</span>
                        <div class="chip-row">
                            @foreach($custData['insights']['painter_loyalty'] ?? [] as $pl)
                                <div class="chip-item {{ str_contains($pl['status_painter'], 'Bersedia') ? 'highlight-purple' : '' }}">
                                    <span class="chip-label">{{ $pl['status_painter'] }}</span>
                                    <span class="chip-val">{{ number_format($pl['total_count']) }} ({{ $pl['pct'] }}%)</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 2: ANALISIS TOKO & WILAYAH (STORE & REGIONAL VALUE) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? '') === 'regional_store')
        <div class="cust-regional-wrapper">
            
            {{-- REKAPITULASI PER REGION (RSM AREA) --}}
            <div class="cust-card mb-6">
                <div class="cust-card-header">
                    <div>
                        <h3 class="cust-card-title"><i class="fa-solid fa-map-location-dot text-blue"></i> Kontribusi Database Pelanggan per Region (RSM Area)</h3>
                        <p class="cust-card-sub">Persebaran konsumen, toko aktif, promotor, dan total nilai estimasi belanja per wilayah</p>
                    </div>
                </div>
                <div class="cust-table-responsive">
                    <table class="cust-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Region (RSM Area)</th>
                                <th class="text-center">Total Konsumen</th>
                                <th class="text-center">Toko Aktif</th>
                                <th class="text-center">DC / Promotor</th>
                                <th class="text-right">Total Nilai Belanja (Rp)</th>
                                <th class="text-right">Rata-Rata per Orang</th>
                                <th class="text-center">Beralih ke Dulux</th>
                                <th class="text-center">Kontribusi %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($custData['by_region'] ?? [] as $index => $r)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $r['rsm_area'] }}</strong></td>
                                    <td class="text-center"><strong>{{ number_format($r['total_count']) }}</strong></td>
                                    <td class="text-center">{{ number_format($r['stores']) }} Toko</td>
                                    <td class="text-center">{{ number_format($r['dcs']) }} DC</td>
                                    <td class="text-right font-mono font-semibold text-emerald-700">Rp {{ number_format($r['total_val'], 0, ',', '.') }}</td>
                                    <td class="text-right font-mono text-gray-600">Rp {{ number_format($r['avg_val'], 0, ',', '.') }}</td>
                                    <td class="text-center"><span class="badge-switch">{{ number_format($r['switched_cnt']) }}</span></td>
                                    <td class="text-center">
                                        <div class="table-bar-wrap">
                                            <span class="table-bar-val">{{ $r['pct'] }}%</span>
                                            <div class="table-bar-bg">
                                                <div class="table-bar-fill" style="width: {{ min(100, $r['pct'] * 2.5) }}%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-6 text-gray-500">Tidak ada data wilayah sesuai filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DUA KOLOM: TOP TOKO & TOP DC --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- TOP TOKO PAGINATED --}}
                <div class="cust-card">
                    <div class="cust-card-header">
                        <div>
                            <h3 class="cust-card-title"><i class="fa-solid fa-store text-emerald"></i> Peringkat Toko Terbaik</h3>
                            <p class="cust-card-sub">Urutan toko berdasarkan total nilai belanja & jumlah konsumen terdata</p>
                        </div>
                    </div>
                    <div class="cust-table-responsive">
                        <table class="cust-table compact">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Toko & SAP</th>
                                    <th>Wilayah</th>
                                    <th class="text-center">Konsumen</th>
                                    <th class="text-right">Total Belanja (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $fromIndex = $custData['top_stores']['from'] ?? 1;
                                @endphp
                                @forelse($custData['top_stores']['rows'] ?? [] as $i => $st)
                                    <tr>
                                        <td>{{ $fromIndex + $i }}</td>
                                        <td>
                                            <div class="font-medium text-gray-900">
                                                {{ $st['store_name'] }}
                                                @if(!empty($st['is_live']))
                                                    <span class="badge-live-tag">⚡ LIVE</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500">SAP: {{ $st['sap_code'] ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="text-xs text-gray-700">{{ $st['rsm_area'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $st['area'] }}</div>
                                        </td>
                                        <td class="text-center"><strong>{{ number_format($st['total_customers']) }}</strong></td>
                                        <td class="text-right font-mono text-emerald-700 font-medium">Rp {{ number_format($st['total_val'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-6 text-gray-500">Tidak ada data toko sesuai filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION TOP STORES --}}
                    @if(($custData['top_stores']['total_pages'] ?? 1) > 1)
                        <div class="cust-pagination-bar">
                            <span class="cust-pagination-info">Menampilkan {{ $custData['top_stores']['from'] }} - {{ $custData['top_stores']['to'] }} dari {{ number_format($custData['top_stores']['total']) }} Toko</span>
                            <div class="cust-pagination-links">
                                @if($custData['top_stores']['page'] > 1)
                                    <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => $custData['top_stores']['page'] - 1, 'tab' => 'regional_store', 'p' => $tenantPrincipal->id])) }}" class="cust-page-btn"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                                @endif
                                <span class="cust-page-current">Hal {{ $custData['top_stores']['page'] }} / {{ $custData['top_stores']['total_pages'] }}</span>
                                @if($custData['top_stores']['page'] < $custData['top_stores']['total_pages'])
                                    <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => $custData['top_stores']['page'] + 1, 'tab' => 'regional_store', 'p' => $tenantPrincipal->id])) }}" class="cust-page-btn">Next <i class="fa-solid fa-chevron-right"></i></a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- TOP DC / PROMOTOR --}}
                <div class="cust-card">
                    <div class="cust-card-header">
                        <div>
                            <h3 class="cust-card-title"><i class="fa-solid fa-trophy text-amber"></i> Top 20 Promotor / DC Teraktif</h3>
                            <p class="cust-card-sub">Promotor paling produktif dalam melayani dan mendata konsumen</p>
                        </div>
                    </div>
                    <div class="cust-table-responsive">
                        <table class="cust-table compact">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama DC / Promotor</th>
                                    <th>Outlet / Wilayah</th>
                                    <th class="text-center">Konsumen</th>
                                    <th class="text-right">Total Transaksi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($custData['top_dcs'] ?? [] as $i => $dc)
                                    <tr>
                                        <td>
                                            @if($i === 0) 🥇 
                                            @elseif($i === 1) 🥈 
                                            @elseif($i === 2) 🥉 
                                            @else {{ $i + 1 }} 
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $dc['nama_dc'] }}</div>
                                            @if($dc['switched_cnt'] > 0)
                                                <div class="text-xs text-teal-600 font-medium"><i class="fa-solid fa-shuffle"></i> {{ $dc['switched_cnt'] }} beralih ke Dulux</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-xs text-gray-700 truncate max-w-xs">{{ $dc['store_name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $dc['rsm_area'] }}</div>
                                        </td>
                                        <td class="text-center"><strong>{{ number_format($dc['total_customers']) }}</strong></td>
                                        <td class="text-right font-mono text-emerald-700 font-medium">Rp {{ number_format($dc['total_val'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-6 text-gray-500">Tidak ada data promotor sesuai filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 3: DATA MENTAH PELANGGAN (SEARCHABLE RAW SUBMISSIONS) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? '') === 'raw')
        <div class="cust-card">
            <div class="cust-card-header">
                <div>
                    <h3 class="cust-card-title"><i class="fa-solid fa-table-list text-blue"></i> Data Mentah Submission Pelanggan & Konsumen</h3>
                    <p class="cust-card-sub">Seluruh transaksi interaksi konsumen lengkap dengan profil, preferensi brand, dan kontak</p>
                </div>
                <div>
                    <span class="badge-total-records">Total: {{ number_format($custData['submissions']['total'] ?? 0) }} Baris Data</span>
                </div>
            </div>

            <div class="cust-table-responsive full-height">
                <table class="cust-table raw-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>TGL LAPORAN</th>
                            <th>NAMA TOKO & SAP</th>
                            <th>REGION / AREA</th>
                            <th>NAMA KONSUMEN</th>
                            <th>KONTAK (WA / HP)</th>
                            <th>TIPE PELANGGAN</th>
                            <th>BRAND DICARI</th>
                            <th>BRAND DIBELI</th>
                            <th>ALASAN</th>
                            <th>TIPE PENGECATAN</th>
                            <th class="text-right">NILAI BELANJA (RP)</th>
                            <th>STATUS SWITCH</th>
                            <th>NAMA DC</th>
                            <th>KETERANGAN</th>
                            <th class="text-center">FOTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $fromIdx = $custData['submissions']['from'] ?? 1;
                        @endphp
                        @forelse($custData['submissions']['rows'] ?? [] as $idx => $row)
                            <tr>
                                <td>{{ $fromIdx + $idx }}</td>
                                <td>
                                    <div class="font-medium whitespace-nowrap">{{ $row['tanggal'] ?: '-' }}</div>
                                    <div class="text-xs text-gray-400 whitespace-nowrap">{{ $row['submission_date'] ? explode(' ', $row['submission_date'])[1] ?? '' : '' }}</div>
                                </td>
                                <td>
                                    <div class="font-medium text-gray-900">
                                        {{ $row['store_name'] }}
                                        @if(!empty($row['is_live']))
                                            <span class="badge-live-tag">⚡ LIVE</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">SAP: {{ $row['sap_code'] ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="text-xs font-semibold text-gray-700">{{ $row['rsm_area'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['area'] }}</div>
                                </td>
                                <td>
                                    <div class="font-medium text-gray-900">{{ $row['nama_pelanggan'] ?: '-' }}</div>
                                    @if($row['alamat'])
                                        <div class="text-xs text-gray-500 truncate max-w-xs" title="{{ $row['alamat'] }}"><i class="fa-solid fa-location-dot"></i> {{ $row['alamat'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $row['no_hp'] ?? '');
                                        if (str_starts_with($cleanPhone, '0')) {
                                            $waPhone = '62' . substr($cleanPhone, 1);
                                        } elseif (str_starts_with($cleanPhone, '62')) {
                                            $waPhone = $cleanPhone;
                                        } else {
                                            $waPhone = '62' . $cleanPhone;
                                        }
                                    @endphp
                                    @if($cleanPhone && strlen($cleanPhone) >= 8)
                                        <a href="https://wa.me/{{ $waPhone }}" target="_blank" class="btn-wa-link" title="Kirim Pesan WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i> {{ $row['no_hp'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ $row['no_hp'] ?: '-' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $typeClass = match($row['tipe_pelanggan']) {
                                            'Pemilik Rumah' => 'badge-type-home',
                                            'Tukang Cat & Bangunan' => 'badge-type-painter',
                                            'Kontraktor' => 'badge-type-contractor',
                                            'Mitra Dulux' => 'badge-type-partner',
                                            default => 'badge-type-other'
                                        };
                                    @endphp
                                    <span class="badge-cust-type {{ $typeClass }}">{{ $row['tipe_pelanggan'] ?: '-' }}</span>
                                </td>
                                <td><span class="font-medium text-gray-800">{{ $row['brand_dicari'] ?: '-' }}</span></td>
                                <td><span class="font-semibold text-blue-700">{{ $row['brand_dibeli'] ?: '-' }}</span></td>
                                <td><span class="text-xs text-gray-700">{{ $row['alasan'] ?: '-' }}</span></td>
                                <td><span class="text-xs text-gray-600">{{ $row['tipe_pengecatan'] ?: '-' }}</span></td>
                                <td class="text-right font-mono font-semibold text-emerald-700 whitespace-nowrap">
                                    Rp {{ number_format($row['value_pembelian'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($row['is_switched'] == 1)
                                        <span class="badge-switch-success"><i class="fa-solid fa-check"></i> Switch Dulux</span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td><div class="text-xs font-medium text-gray-800">{{ $row['nama_dc'] ?: '-' }}</div></td>
                                <td><div class="text-xs text-gray-600 max-w-xs truncate" title="{{ $row['keterangan'] }}">{{ $row['keterangan'] ?: '-' }}</div></td>
                                <td class="text-center">
                                    @if($row['foto_1'])
                                        <a href="{{ $row['foto_1'] }}" target="_blank" class="btn-view-photo" title="Lihat Foto 1"><i class="fa-solid fa-image"></i></a>
                                    @endif
                                    @if($row['foto_2'])
                                        <a href="{{ $row['foto_2'] }}" target="_blank" class="btn-view-photo" title="Lihat Foto 2"><i class="fa-solid fa-image"></i></a>
                                    @endif
                                    @if($row['foto_3'])
                                        <a href="{{ $row['foto_3'] }}" target="_blank" class="btn-view-photo" title="Lihat Foto 3"><i class="fa-solid fa-image"></i></a>
                                    @endif
                                    @if(!$row['foto_1'] && !$row['foto_2'] && !$row['foto_3'])
                                        <span class="text-gray-300 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="16" class="text-center py-8 text-gray-500">Tidak ada data konsumen sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION RAW SUBMISSIONS --}}
            @if(($custData['submissions']['total_pages'] ?? 1) > 1)
                <div class="cust-pagination-bar">
                    <span class="cust-pagination-info">Menampilkan {{ $custData['submissions']['from'] }} s/d {{ $custData['submissions']['to'] }} dari {{ number_format($custData['submissions']['total']) }} data (Hal {{ $custData['submissions']['page'] }} dari {{ $custData['submissions']['total_pages'] }})</span>
                    
                    <div class="cust-pagination-links">
                        @php
                            $curPage = $custData['submissions']['page'];
                            $totPages = $custData['submissions']['total_pages'];
                        @endphp
                        
                        @if($curPage > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="cust-page-btn" title="Halaman Pertama"><i class="fa-solid fa-angles-left"></i></a>
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $curPage - 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="cust-page-btn"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                        @endif

                        @for($p = max(1, $curPage - 2); $p <= min($totPages, $curPage + 2); $p++)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $p, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                               class="cust-page-btn {{ $p == $curPage ? 'active' : '' }}">{{ $p }}</a>
                        @endfor

                        @if($curPage < $totPages)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $curPage + 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="cust-page-btn">Next <i class="fa-solid fa-chevron-right"></i></a>
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $totPages, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="cust-page-btn" title="Halaman Terakhir"><i class="fa-solid fa-angles-right"></i></a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 4: DATA LAPORAN MASUK (LIVE SUBMISSIONS & VERIFIKASI) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? '') === 'live')
        <div class="cust-card">
            <div class="cust-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="cust-card-title mb-1"><i class="fa-solid fa-inbox text-primary"></i> Data Laporan Masuk & Verifikasi Data Pelanggan Dulux</h3>
                    <p class="cust-card-sub mb-0">Verifikasi berkala pendataan profil konsumen, preferensi brand (dicari vs dibeli), dan transaksi cat langsung dari SPG/DC di toko.</p>
                </div>
                <div style="background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); padding: 6px 14px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 0.78rem; color: #64748b; font-weight: 600;">Total Laporan Masuk:</span>
                    <strong style="color: #2563eb; font-size: 0.95rem;">{{ number_format(isset($submissions) ? $submissions->total() : 0) }} Laporan</strong>
                </div>
            </div>

            @if(isset($submissions) && $submissions->isNotEmpty())
                <div class="cust-table-responsive" style="max-height: 650px;">
                    <table class="cust-table raw-table" style="min-width: 1700px;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 150px;">Kode Laporan</th>
                                <th style="min-width: 140px;">Waktu Submit</th>
                                <th style="min-width: 170px;">Promotor / DC</th>
                                <th style="min-width: 180px;">Nama Toko & SAP</th>
                                <th style="min-width: 130px;">Area & RSM</th>
                                <th style="min-width: 180px;">Profil Pelanggan</th>
                                <th style="min-width: 130px;">Tipe Pelanggan</th>
                                <th style="min-width: 150px;">Brand Dicari</th>
                                <th style="min-width: 170px;">Brand Dibeli</th>
                                <th style="min-width: 140px; text-align: right;">Nilai Belanja</th>
                                <th style="min-width: 160px;">Alasan Memilih</th>
                                <th style="min-width: 120px; text-align: center;">Foto Bukti</th>
                                <th style="text-align: center; width: 100px;">Radius GPS</th>
                                <th style="text-align: center; width: 120px;">Status</th>
                                <th style="text-align: center; width: 170px; min-width: 170px; position: sticky; right: 0; background: #f8fafc; z-index: 6;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $idx => $sub)
                                @php
                                    $valMap = [];
                                    $photos = [];
                                    foreach ($sub->values as $v) {
                                        $val = $v->value_number ?? $v->value_text ?? $v->value_date ?? $v->value_json;
                                        if ($v->field_name) {
                                            $valMap[$v->field_name] = $val;
                                            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->field_name), '_'));
                                            $valMap[$slug] = $val;
                                        }
                                        if ($v->formField) {
                                            if ($v->formField->field_name) {
                                                $valMap[$v->formField->field_name] = $val;
                                                $slugF = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->formField->field_name), '_'));
                                                $valMap[$slugF] = $val;
                                            }
                                            if ($v->formField->field_label) {
                                                $slugL = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->formField->field_label), '_'));
                                                $valMap[$slugL] = $val;
                                            }
                                        }

                                        $isPhoto = in_array($v->field_type, ['photo', 'camera_photo', 'multi_photo'])
                                            || str_contains((string)$v->field_name, 'foto')
                                            || !empty($v->media_url)
                                            || !empty($v->file_path);

                                        if ($isPhoto) {
                                            $rawP = $v->value_json ?: ($v->media_url ?: ($v->file_path ?: $v->value_text));
                                            $photoLabel = $v->formField?->field_label ?? ($v->field_name ?: 'Foto Bukti');
                                            if (is_array($rawP)) {
                                                foreach ($rawP as $pSubIdx => $rp) {
                                                    if (is_string($rp) && !empty($rp) && !str_starts_with($rp, '/data/user/')) {
                                                        $cleanP = trim($rp);
                                                        $photoUrl = (str_starts_with($cleanP, 'http://') || str_starts_with($cleanP, 'https://'))
                                                            ? $cleanP
                                                            : asset('storage/' . ltrim(str_replace('storage/', '', $cleanP), '/'));
                                                        $photos[] = [
                                                            'url' => $photoUrl,
                                                            'label' => count($rawP) > 1 ? "{$photoLabel} (" . ($pSubIdx + 1) . "/" . count($rawP) . ")" : $photoLabel
                                                        ];
                                                    }
                                                }
                                            } elseif (is_string($rawP) && !empty($rawP) && !str_starts_with($rawP, '/data/user/')) {
                                                $splitP = explode(',', $rawP);
                                                foreach ($splitP as $pSubIdx => $rp) {
                                                    $cleanP = trim($rp);
                                                    if (!empty($cleanP) && !str_starts_with($cleanP, '/data/user/')) {
                                                        $photoUrl = (str_starts_with($cleanP, 'http://') || str_starts_with($cleanP, 'https://'))
                                                            ? $cleanP
                                                            : asset('storage/' . ltrim(str_replace('storage/', '', $cleanP), '/'));
                                                        $photos[] = [
                                                            'url' => $photoUrl,
                                                            'label' => count($splitP) > 1 ? "{$photoLabel} (" . ($pSubIdx + 1) . "/" . count($splitP) . ")" : $photoLabel
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $store = $sub->workLocation?->name ?? ($sub->store_name ?? 'Toko Tidak Terdaftar');
                                    $sap = $sub->workLocation?->code ?? ($sub->workLocation?->store_code ?? '-');
                                    $area = $sub->workLocation?->branch?->name ?? ($sub->workLocation?->area?->name ?? ($sub->workLocation?->area ?? '-'));
                                    $region = $sub->workLocation?->region ?? '-';
                                    $empName = $sub->employee?->full_name ?? ($sub->employee?->name ?? 'Petugas');
                                    $empNik = $sub->employee?->nik ?? ($sub->employee?->employee_no ?? '-');

                                    $namaPelanggan = trim((string)($valMap['nama_lengkap_pelanggan'] ?? ($valMap['nama_pelanggan'] ?? ($valMap['nama_konsumen'] ?? '-'))));
                                    $noHp = trim((string)($valMap['nomor_hp_whatsapp_pelanggan'] ?? ($valMap['no_hp_pelanggan'] ?? ($valMap['no_hp'] ?? '-'))));
                                    $alamat = trim((string)($valMap['alamat_domisili_pelanggan'] ?? ($valMap['alamat_pelanggan'] ?? ($valMap['alamat'] ?? '-'))));
                                    $tipePelanggan = trim((string)($valMap['tipe_kategori_pelanggan'] ?? ($valMap['tipe_pelanggan'] ?? 'Pemilik Rumah')));
                                    $brandDicari = trim((string)($valMap['brand_cat_yang_awalnya_dicari_ditanyakan'] ?? ($valMap['brand_dicari'] ?? '-')));
                                    $brandDibeli = trim((string)($valMap['brand_cat_yang_akhirnya_dibeli'] ?? ($valMap['brand_dibeli'] ?? '-')));
                                    $alasan = trim((string)($valMap['alasan_konsumen_memilih_brand_tersebut'] ?? ($valMap['alasan_pilih_brand'] ?? ($valMap['alasan'] ?? 'Rekomendasi DC'))));
                                    
                                    $rawValNum = $valMap['estimasi_total_nilai_pembelian_rupiah'] ?? ($valMap['total_estimasi_nilai_pembelian_rupiah'] ?? ($valMap['value_pembelian_rp'] ?? ($valMap['value_pembelian'] ?? 0)));
                                    $valPembelian = is_numeric($rawValNum) ? (float)$rawValNum : (float)preg_replace('/[^0-9.]/', '', (string)$rawValNum);

                                    $isDuluxBought = (stripos($brandDibeli, 'dulux') !== false || stripos($brandDibeli, 'catylac') !== false || stripos($brandDibeli, 'aquashield') !== false);
                                    $isDuluxSought = (stripos($brandDicari, 'dulux') !== false || stripos($brandDicari, 'catylac') !== false || stripos($brandDicari, 'aquashield') !== false);
                                    $isSwitched = ($isDuluxBought && !$isDuluxSought && !empty($brandDicari) && $brandDicari !== '-');

                                    $status = $sub->status ?? 'pending';
                                @endphp
                                <tr>
                                    <td style="text-align: center; color: #64748b; font-weight: 700;">
                                        {{ $submissions->firstItem() + $idx }}
                                    </td>
                                    <td>
                                        <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" 
                                           style="font-family: monospace; font-weight: 700; font-size: 0.82rem; color: #0F52BA; text-decoration: none; background: rgba(15, 82, 186, 0.08); padding: 3px 8px; border-radius: 6px; border: 1px solid rgba(15, 82, 186, 0.2); display: inline-block;">
                                            {{ $sub->submission_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">
                                            {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y') : ($sub->created_at ? $sub->created_at->translatedFormat('d M Y') : '-') }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: #64748b;">
                                            {{ $sub->submitted_at ? $sub->submitted_at->format('H:i') : ($sub->created_at ? $sub->created_at->format('H:i') : '-') }} WIB
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">
                                            {{ $empName }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: #64748b; font-family: monospace;">
                                            {{ $empNik }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">{{ $store }}</div>
                                        <div style="font-size: 0.74rem; color: #64748b;">
                                            SAP: <span class="badge bg-light-secondary text-secondary" style="font-family: monospace;">{{ $sap }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 0.82rem; color: #1e293b;">{{ $area }}</div>
                                        <span class="region-badge" style="font-size: 0.7rem; padding: 1px 6px;">{{ $region }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">{{ $namaPelanggan ?: '-' }}</div>
                                        @if($noHp && $noHp !== '-')
                                            @php
                                                $cleanPh = preg_replace('/[^0-9]/', '', $noHp);
                                                if (str_starts_with($cleanPh, '0')) {
                                                    $waPh = '62' . substr($cleanPh, 1);
                                                } else {
                                                    $waPh = $cleanPh;
                                                }
                                            @endphp
                                            <a href="https://wa.me/{{ $waPh }}" target="_blank" class="btn-wa-link" style="font-size: 0.72rem; padding: 1px 6px; margin-top: 2px;">
                                                <i class="fa-brands fa-whatsapp"></i> {{ $noHp }}
                                            </a>
                                        @endif
                                        @if($alamat && $alamat !== '-')
                                            <div style="font-size: 0.72rem; color: #64748b; margin-top: 2px; max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $alamat }}">
                                                <i class="fa-solid fa-location-dot"></i> {{ $alamat }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $typeCls = match($tipePelanggan) {
                                                'Pemilik Rumah' => 'badge-type-home',
                                                'Tukang Cat & Bangunan' => 'badge-type-painter',
                                                'Kontraktor' => 'badge-type-contractor',
                                                'Mitra Dulux' => 'badge-type-partner',
                                                default => 'badge-type-other'
                                            };
                                        @endphp
                                        <span class="badge-cust-type {{ $typeCls }}">{{ $tipePelanggan ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #475569; font-size: 0.82rem;">{{ $brandDicari ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0F52BA; font-size: 0.82rem;">{{ $brandDibeli ?: '-' }}</div>
                                        @if($isSwitched)
                                            <span class="badge-switch-success" style="font-size: 0.68rem; margin-top: 2px;">
                                                <i class="fa-solid fa-shuffle"></i> Switch Dulux
                                            </span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #059669; font-size: 0.85rem; white-space: nowrap;">
                                        Rp {{ number_format($valPembelian, 0, ',', '.') }}
                                    </td>
                                    <td style="font-size: 0.78rem; color: #475569;">
                                        {{ $alasan ?: '-' }}
                                    </td>
                                    <td style="text-align: center;">
                                        @if(!empty($photos))
                                            <div style="display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap; justify-content: center; max-width: 110px;">
                                                @foreach($photos as $pIdx => $p)
                                                    <img src="{{ $p['url'] }}" 
                                                         alt="{{ $p['label'] }}" 
                                                         title="{{ $p['label'] }}"
                                                         onclick="openCustPhotoModal('{{ $p['url'] }}', '{{ addslashes($p['label']) }}', '{{ $sub->submission_code }}')"
                                                         style="width: 32px; height: 32px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; transition: transform 0.15s ease;"
                                                         onmouseover="this.style.transform='scale(1.15)'"
                                                         onmouseout="this.style.transform='scale(1)'"
                                                         onerror="this.style.display='none'">
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($sub->is_within_radius)
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.25rem 0.55rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-circle-check"></i> Valid
                                            </span>
                                        @else
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.55rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Luar
                                            </span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if(in_array($status, ['approved', 'verified']))
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.25rem 0.6rem; border-radius: 8px; display: inline-block;">
                                                <i class="fa-solid fa-circle-check"></i> Terverifikasi
                                            </span>
                                        @elseif($status === 'rejected')
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 0.25rem 0.6rem; border-radius: 8px; display: inline-block;">
                                                <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                            </span>
                                        @else
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.6rem; border-radius: 8px; display: inline-block;">
                                                <i class="fa-solid fa-clock"></i> Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td style="text-align: center; position: sticky; right: 0; background: #ffffff; z-index: 5; box-shadow: -2px 0 6px rgba(0,0,0,0.03);">
                                        <div style="display: inline-flex; align-items: center; gap: 4px; justify-content: center; white-space: nowrap;">
                                            <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" class="btn-action-view" title="Lihat Detail & Bukti Lengkap">
                                                <i class="fa-solid fa-eye"></i> Detail
                                            </a>
                                            @if(in_array($status, ['pending', 'submitted']))
                                                <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Setujui laporan {{ $sub->submission_code }}?')">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn-action-quick-approve" title="Setujui Laporan">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn-action-quick-reject" onclick="openCustRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Tolak Laporan">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            @elseif(in_array($status, ['approved', 'verified']))
                                                <button type="button" class="btn-action-quick-reject" onclick="openCustRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Batalkan / Tolak Laporan">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            @elseif($status === 'rejected')
                                                <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Setujui kembali laporan {{ $sub->submission_code }}?')">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn-action-quick-approve" title="Setujui Kembali">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($submissions->hasPages())
                    <div class="cust-pagination-bar">
                        <div class="cust-pagination-info">
                            Menampilkan <strong>{{ $submissions->firstItem() }}</strong> s/d <strong>{{ $submissions->lastItem() }}</strong> dari <strong>{{ number_format($submissions->total()) }}</strong> laporan masuk (Hal <strong>{{ $submissions->currentPage() }}</strong> dari <strong>{{ $submissions->lastPage() }}</strong>)
                        </div>
                        <div class="cust-pagination-links">
                            @if(!$submissions->onFirstPage())
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url(1) }}" class="cust-page-btn" title="Halaman Pertama">
                                    <i class="fa-solid fa-angles-left"></i>
                                </a>
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->previousPageUrl() }}" class="cust-page-btn">
                                    <i class="fa-solid fa-chevron-left"></i> Prev
                                </a>
                            @endif

                            @for($i = max(1, $submissions->currentPage() - 2); $i <= min($submissions->lastPage(), $submissions->currentPage() + 2); $i++)
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url($i) }}" 
                                   class="cust-page-btn {{ $i == $submissions->currentPage() ? 'active' : '' }}">
                                    {{ $i }}
                                </a>
                            @endfor

                            @if($submissions->hasMorePages())
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->nextPageUrl() }}" class="cust-page-btn">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </a>
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url($submissions->lastPage()) }}" class="cust-page-btn" title="Halaman Terakhir">
                                    <i class="fa-solid fa-angles-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                    <i class="fa-solid fa-inbox" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 0.75rem; display: block;"></i>
                    <div style="font-weight: 700; font-size: 1rem; color: #1e293b;">Tidak Ada Data Laporan Masuk</div>
                    <p style="font-size: 0.85rem; margin-top: 4px;">Belum ada submisi laporan konsumen yang dikirimkan oleh promotor/DC untuk filter periode dan toko ini.</p>
                </div>
            @endif
        </div>
    @endif

{{-- CSS STYLES FOR CUSTOMER DATABASE DASHBOARD --}}
<style>
.cust-executive-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 30px;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* TOP TOOLBAR */
.cust-top-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    background: #ffffff;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
}

.cust-tabs-container {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.cust-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    transition: all 0.2s ease;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.cust-tab-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.cust-tab-btn.active {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
    box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);
}

.cust-tab-btn .badge-count {
    background: rgba(0,0,0,0.08);
    color: inherit;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 9999px;
    font-weight: 700;
}

.cust-tab-btn.active .badge-count {
    background: rgba(255,255,255,0.25);
    color: #ffffff;
}

.cust-export-actions {
    display: flex;
    gap: 8px;
}

.btn-cust-export-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-cust-export-action.success {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.btn-cust-export-action.success:hover {
    background: #059669;
    color: #ffffff;
}

.btn-cust-export-action.primary {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.btn-cust-export-action.primary:hover {
    background: #2563eb;
    color: #ffffff;
}

/* KPI CARDS */
.cust-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}

.cust-kpi-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #ffffff;
    padding: 18px 20px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.cust-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.cust-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.cust-kpi-icon.blue { background: #e0f2fe; color: #0284c7; }
.cust-kpi-icon.emerald { background: #d1fae5; color: #059669; }
.cust-kpi-icon.purple { background: #f3e8ff; color: #9333ea; }
.cust-kpi-icon.amber { background: #fef3c7; color: #d97706; }
.cust-kpi-icon.indigo { background: #e0e7ff; color: #4f46e5; }
.cust-kpi-icon.teal { background: #ccfbf1; color: #0d9488; }

.cust-kpi-content {
    display: flex;
    flex-direction: column;
}

.cust-kpi-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
}

.cust-kpi-val {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin: 3px 0 2px 0;
}

.cust-kpi-unit {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.cust-kpi-sub {
    font-size: 11.5px;
    color: #94a3b8;
}

/* CARDS & GRIDS */
.cust-insights-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 1024px) {
    .cust-insights-grid {
        grid-template-columns: 1fr;
    }
    .cust-insights-grid .col-span-2 {
        grid-column: span 1 / span 1 !important;
    }
}

.cust-insights-grid .col-span-2 {
    grid-column: span 2 / span 2;
}

.cust-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.cust-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}

.cust-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cust-card-sub {
    font-size: 12px;
    color: #64748b;
    margin: 2px 0 0 0;
}

.cust-card-body {
    padding: 18px 20px;
}

/* SEGMENT LIST */
.cust-segment-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.cust-segment-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.cust-segment-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.cust-segment-title-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cust-segment-name {
    font-weight: 600;
    color: #1e293b;
}

.badge-seg-count {
    font-size: 11.5px;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 600;
}

.cust-segment-val-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.cust-segment-val {
    font-weight: 700;
    color: #059669;
    font-family: monospace;
}

.cust-segment-avg {
    font-size: 11px;
    color: #94a3b8;
}

.cust-progress-bar-bg {
    width: 100%;
    height: 8px;
    background: #f1f5f9;
    border-radius: 9999px;
    overflow: hidden;
}

.cust-progress-bar-fill {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.4s ease;
}

.cust-progress-bar-fill.blue { background: #0284c7; }
.cust-progress-bar-fill.emerald { background: #059669; }
.cust-progress-bar-fill.purple { background: #9333ea; }
.cust-progress-bar-fill.amber { background: #d97706; }

/* BRAND COMPARISON */
.brand-comparison-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 640px) {
    .brand-comparison-grid { grid-template-columns: 1fr; }
}

.brand-col-title {
    font-size: 13px;
    font-weight: 700;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.brand-col-title.sought { color: #d97706; }
.brand-col-title.bought { color: #059669; }

.brand-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.brand-pill-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
}

.brand-pill-item.sought {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    color: #92400e;
}

.brand-pill-item.bought {
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    color: #166534;
}

.brand-name {
    font-weight: 600;
}

.brand-count {
    font-weight: 700;
    font-size: 12.5px;
}

/* CHIPS & SUB INSIGHTS */
.sub-insight-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sub-insight-heading {
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.chip-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12.5px;
}

.chip-item.highlight-emerald {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #065f46;
}

.chip-item.highlight-purple {
    background: #f3e8ff;
    border-color: #e9d5ff;
    color: #6b21a8;
}

.chip-label {
    color: inherit;
    font-weight: 500;
}

.chip-val {
    font-weight: 700;
    background: rgba(0,0,0,0.06);
    padding: 1px 6px;
    border-radius: 4px;
}

/* TABLES */
.cust-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.cust-table-responsive.full-height {
    max-height: 600px;
    overflow-y: auto;
}

.cust-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}

.cust-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    padding: 10px 14px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

.cust-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}

.cust-table tr:hover td {
    background: #f8fafc;
}

.cust-table.compact th, .cust-table.compact td {
    padding: 8px 12px;
}

/* TABLE BARS */
.table-bar-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.table-bar-val {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}

.table-bar-bg {
    width: 60px;
    height: 5px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
}

.table-bar-fill {
    height: 100%;
    background: #0284c7;
    border-radius: 9999px;
}

/* BADGES & BUTTONS IN TABLE */
.badge-total-records {
    background: #e0f2fe;
    color: #0369a1;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
}

.badge-switch {
    background: #ccfbf1;
    color: #0f766e;
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
}

.badge-switch-success {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #dcfce7;
    color: #15803d;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    white-space: nowrap;
}

.btn-wa-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #dcfce7;
    color: #166534;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-wa-link:hover {
    background: #22c55e;
    color: #ffffff;
}

.badge-cust-type {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-type-home { background: #e0f2fe; color: #0369a1; }
.badge-type-painter { background: #fef3c7; color: #92400e; }
.badge-type-contractor { background: #ede9fe; color: #6d28d9; }
.badge-type-partner { background: #fce7f3; color: #be185d; }
.badge-type-other { background: #f1f5f9; color: #475569; }

.btn-view-photo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 6px;
    text-decoration: none;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.btn-view-photo:hover {
    background: #2563eb;
    color: #ffffff;
}

/* PAGINATION */
.cust-pagination-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 18px;
    background: #fafafa;
    border-top: 1px solid #e2e8f0;
}

.cust-pagination-info {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 500;
}

.cust-pagination-links {
    display: flex;
    align-items: center;
    gap: 4px;
}

.cust-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    transition: all 0.2s ease;
}

.cust-page-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.cust-page-btn.active {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}

.cust-page-current {
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    padding: 0 8px;
}

/* BADGE LIVE */
.badge-live-tag {
    background: linear-gradient(135deg, #0284c7, #2563eb);
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-left: 4px;
    letter-spacing: 0.3px;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
}

/* ACTION BUTTONS FOR DATA LAPORAN MASUK */
.btn-action-view {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.32rem 0.65rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
    background: #f1f5f9;
    color: #0F52BA;
    border: 1px solid #cbd5e1;
    transition: all 0.15s ease;
}
.btn-action-view:hover {
    background: #0F52BA;
    color: #ffffff;
    border-color: #0F52BA;
}

.btn-action-quick-approve {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.32rem 0.65rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    background: #16a34a;
    color: #ffffff;
    border: none;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(22, 163, 74, 0.2);
    transition: all 0.15s ease;
}
.btn-action-quick-approve:hover {
    background: #15803d;
    transform: translateY(-1px);
}

.btn-action-quick-reject {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.32rem 0.65rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    background: #dc2626;
    color: #ffffff;
    border: none;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(220, 38, 38, 0.2);
    transition: all 0.15s ease;
}
.btn-action-quick-reject:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}
</style>

{{-- MODAL REJECT WITH REASON UNTUK CUSTOMER DB --}}
<div id="cust_reject_modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 460px; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); margin: 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div style="font-weight: 700; font-size: 1.1rem; color: #b91c1c; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Tolak Laporan Konsumen
            </div>
            <button type="button" onclick="closeCustRejectModal()" style="background: none; border: none; font-size: 1.4rem; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
            Anda akan menolak laporan <strong id="cust_reject_code" style="color: #0f172a; font-family: monospace;"></strong>. Masukkan alasan penolakan untuk promotor/DC.
        </p>
        <form id="cust_reject_form" method="POST" action="">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Alasan Penolakan <span style="color: #ef4444;">*</span></label>
                <textarea name="rejection_note" rows="3" required placeholder="Contoh: Bukti foto tidak jelas, kontak pelanggan tidak valid, dll..." style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="closeCustRejectModal()" style="padding: 0.5rem 1rem; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Batal</button>
                <button type="submit" style="padding: 0.5rem 1.25rem; background: #ef4444; color: #ffffff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Tolak Laporan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL LIGHTBOX FOTO BUKTI UNTUK CUSTOMER DB --}}
<div id="cust_photo_modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);" onclick="if(event.target === this) closeCustPhotoModal()">
    <div style="background: #1e293b; border-radius: 12px; width: 90%; max-width: 750px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border: 1px solid #334155;">
        <div style="padding: 0.75rem 1.25rem; background: #0f172a; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #334155; color: #f8fafc;">
            <div>
                <div id="cust_photo_title" style="font-weight: 700; font-size: 0.9rem;">Foto Bukti</div>
                <div id="cust_photo_sub" style="font-size: 0.75rem; color: #94a3b8; font-family: monospace;"></div>
            </div>
            <button type="button" onclick="closeCustPhotoModal()" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 1rem; display: flex; justify-content: center; align-items: center; min-height: 250px; max-height: 75vh; overflow: auto;">
            <img id="cust_photo_img" src="" alt="Preview" style="max-width: 100%; max-height: 70vh; border-radius: 8px; object-fit: contain; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
        </div>
        <div style="padding: 0.75rem 1.25rem; background: rgba(0,0,0,0.3); display: flex; justify-content: flex-end;">
            <a id="cust_photo_link" href="" target="_blank" style="color: #60a5fa; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Ukuran Penuh
            </a>
        </div>
    </div>
</div>

<script>
function openCustRejectModal(subId, subCode) {
    var modal = document.getElementById('cust_reject_modal');
    var codeEl = document.getElementById('cust_reject_code');
    var form = document.getElementById('cust_reject_form');
    if (!modal || !form) return;
    if (codeEl) codeEl.innerText = subCode;
    var baseRoute = "{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => ':id', 'p' => $tenantPrincipal->id]) }}";
    form.action = baseRoute.replace(':id', subId);
    modal.style.display = 'flex';
}

function closeCustRejectModal() {
    var modal = document.getElementById('cust_reject_modal');
    if (modal) modal.style.display = 'none';
}

function openCustPhotoModal(url, label, subCode) {
    var modal = document.getElementById('cust_photo_modal');
    var img = document.getElementById('cust_photo_img');
    var title = document.getElementById('cust_photo_title');
    var sub = document.getElementById('cust_photo_sub');
    var link = document.getElementById('cust_photo_link');
    if (!modal || !img) return;
    img.src = url;
    if (title) title.innerText = label || 'Foto Bukti';
    if (sub) sub.innerText = subCode || '';
    if (link) link.href = url;
    modal.style.display = 'flex';
}

function closeCustPhotoModal() {
    var modal = document.getElementById('cust_photo_modal');
    if (modal) modal.style.display = 'none';
}
</script>
