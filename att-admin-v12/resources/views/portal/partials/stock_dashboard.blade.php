{{-- PORTAL STOCK END EXECUTIVE DASHBOARD (MONTHLY DAILY TREND COMPARE, PIVOTABLE, SUMM & SCM, RAW DATA SUBMISSIONS) --}}
<div class="custom-stock-wrapper" style="margin-bottom: 2rem; width: 100%; max-width: 100%; min-width: 0;">

    <!-- TOP TOOLBAR: TAB NAVIGATION & EXPORT BUTTONS -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="stock-main-nav" style="background: #e2e8f0; padding: 4px; border-radius: 12px; display: inline-flex; gap: 4px; flex-wrap: wrap;">
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'monthly') === 'monthly' ? 'active' : '' }}" id="btn_stock_tab_monthly" onclick="switchStockTab('monthly')">
                <i class="fa-solid fa-chart-line" style="font-size: 0.95rem;"></i>
                <span>Tren Harian & Komparasi Bulanan</span>
                <span class="badge-count" style="background: rgba(11, 61, 136, 0.12); color: #0b3d88;">{{ $monthlyCompareData['month_name'] ?? 'Bulan' }} {{ $monthlyCompareData['current_year'] ?? 2026 }} vs {{ $monthlyCompareData['previous_year'] ?? 2025 }}</span>
            </button>
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'monthly') === 'pivotable' ? 'active' : '' }}" id="btn_stock_tab_pivotable" onclick="switchStockTab('pivotable')">
                <i class="fa-solid fa-boxes-stacked" style="font-size: 0.95rem;"></i>
                <span>Rekap Volume Stock Toko</span>
                <span class="badge-count">{{ number_format($stockData['pivotable']['total_stores'] ?? 0) }} Toko</span>
            </button>
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'monthly') === 'summ' ? 'active' : '' }}" id="btn_stock_tab_summ" onclick="switchStockTab('summ')">
                <i class="fa-solid fa-file-waveform" style="font-size: 0.95rem;"></i>
                <span>Ringkasan SCM & Stock</span>
                <span class="badge-count">{{ number_format($stockData['summ']['total_stores'] ?? 0) }} Toko</span>
            </button>
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'monthly') === 'raw' ? 'active' : '' }}" id="btn_stock_tab_raw" onclick="switchStockTab('raw')">
                <i class="fa-solid fa-list-check" style="font-size: 0.95rem;"></i>
                <span>Raw Data Submissions</span>
                <span class="badge-count">{{ number_format($stockData['submissions']['total'] ?? 0) }} Baris</span>
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Brand Badge Indicator -->
            @if(($selectedBrand ?? 'ALL') === 'DULUX')
                <div style="font-size: 0.82rem; font-weight: 700; color: #1e3a8a; background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.45rem 0.85rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-brush"></i> Brand: <strong>Dulux</strong>
                </div>
            @elseif(($selectedBrand ?? 'ALL') === 'CATYLAC')
                <div style="font-size: 0.82rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.45rem 0.85rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-brush"></i> Brand: <strong>Catylac</strong>
                </div>
            @else
                <div style="font-size: 0.82rem; font-weight: 700; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.45rem 0.85rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-palette"></i> Brand: <strong>Semua (Dulux & Catylac)</strong>
                </div>
            @endif

            <!-- Periode Indicator -->
            <div style="font-size: 0.84rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; background: #fff; padding: 0.45rem 0.85rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-calendar-days" style="color: var(--brand-primary);"></i>
                <span>Periode: <strong>{{ reset($stockData['months']) }} – {{ end($stockData['months']) }}</strong></span>
            </div>

            <!-- Export Buttons -->
            <div style="display: inline-flex; gap: 6px; flex-wrap: wrap;">
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_monthly_compare', 'brand' => $selectedBrand ?? 'ALL', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" style="background: linear-gradient(135deg, #0b3d88 0%, #1e40af 100%); color: #ffffff;" title="Download Data Perbandingan Tren Harian Bulanan (CSV)">
                    <i class="fa-solid fa-file-csv" style="color: #60a5fa;"></i>
                    <span>Export Tren Harian</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_pivot', 'brand' => $selectedBrand ?? 'ALL', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" title="Download Rekap Volume Stock Per Toko (Pivotable)">
                    <i class="fa-solid fa-file-excel" style="color: #107c41;"></i>
                    <span>Export Rekap Stock</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_summ', 'brand' => $selectedBrand ?? 'ALL', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" style="background: #f0fdf4;" title="Download Ringkasan SCM & Stock (Summ)">
                    <i class="fa-solid fa-file-waveform" style="color: #16a34a;"></i>
                    <span>Export SCM & Stock</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_raw', 'brand' => $selectedBrand ?? 'ALL', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" style="background: #f8fafc;" title="Download Raw Data Submissions">
                    <i class="fa-solid fa-list-check" style="color: #0284c7;"></i>
                    <span>Export Raw Data</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PANE 1: TREN HARIAN & PERBANDINGAN BULANAN (CURRENT YEAR VS PREVIOUS YEAR) -->
    <!-- ========================================================================= -->
    <div id="pane_stock_monthly" class="stock-pane" style="{{ ($activeTab ?? 'monthly') === 'monthly' ? 'display: block;' : 'display: none;' }}">
        <div class="stock-card" style="margin-bottom: 1.5rem;">
            <div class="stock-card-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
                <div>
                    <h3 class="stock-card-title">
                        <i class="fa-solid fa-chart-line" style="color: #0b3d88;"></i>
                        Grafik Garis Tren Harian & Perbandingan Volume Stock
                    </h3>
                    <div class="stock-card-sub">
                        Komparasi volume stok fisik per tanggal (1 s/d {{ count($monthlyCompareData['daily_trend']['days'] ?? []) }}) untuk bulan <strong>{{ $monthlyCompareData['month_name'] ?? 'Bulan Terpilih' }}</strong> antara <strong>Tahun {{ $monthlyCompareData['current_year'] ?? 2026 }}</strong> vs <strong>Tahun {{ $monthlyCompareData['previous_year'] ?? 2025 }}</strong>.
                    </div>
                </div>

                <div class="stock-header-meta">
                    <div class="meta-pill" style="background: #f0f7ff; border: 1px solid #bfdbfe;">
                        <span class="meta-lbl" style="color: #1e40af;">Bulan Komparasi:</span>
                        <strong class="meta-val" style="color: #0b3d88;">{{ $monthlyCompareData['month_name'] ?? '-' }} {{ $monthlyCompareData['current_year'] ?? '' }} vs {{ $monthlyCompareData['previous_year'] ?? '' }}</strong>
                    </div>
                </div>
            </div>

            <!-- 5 KPI SUMMARY CARDS -->
            <div class="monthly-kpi-grid">
                <!-- KPI 1: Volume Tahun Berjalan -->
                <div class="monthly-kpi-card" style="border-left: 4px solid #0b3d88;">
                    <div class="kpi-icon-box" style="background: rgba(11, 61, 136, 0.1); color: #0b3d88;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Volume {{ $monthlyCompareData['month_name'] }} {{ $monthlyCompareData['current_year'] }}</div>
                        <div class="kpi-value" style="color: #0b3d88;">{{ number_format($monthlyCompareData['kpi']['cy_volume'] ?? 0, 2) }} <span class="kpi-unit">Liter</span></div>
                        <div class="kpi-subtext"><i class="fa-solid fa-store" style="color: #64748b;"></i> {{ number_format($monthlyCompareData['kpi']['cy_stores'] ?? 0) }} Toko Melapor</div>
                    </div>
                </div>

                <!-- KPI 2: Volume Tahun Sebelumnya -->
                <div class="monthly-kpi-card" style="border-left: 4px solid #f59e0b;">
                    <div class="kpi-icon-box" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Volume {{ $monthlyCompareData['month_name'] }} {{ $monthlyCompareData['previous_year'] }}</div>
                        <div class="kpi-value" style="color: #334155;">{{ number_format($monthlyCompareData['kpi']['py_volume'] ?? 0, 2) }} <span class="kpi-unit">Liter</span></div>
                        <div class="kpi-subtext"><i class="fa-solid fa-store" style="color: #64748b;"></i> {{ number_format($monthlyCompareData['kpi']['py_stores'] ?? 0) }} Toko Melapor</div>
                    </div>
                </div>

                <!-- KPI 3: Pertumbuhan YoY Bulan Terpilih -->
                @php
                    $growth = (float)($monthlyCompareData['kpi']['growth'] ?? 0);
                    $growthDiff = (float)($monthlyCompareData['kpi']['growth_diff'] ?? 0);
                    $isPos = $growth >= 0;
                @endphp
                <div class="monthly-kpi-card" style="border-left: 4px solid {{ $isPos ? '#10b981' : '#ef4444' }};">
                    <div class="kpi-icon-box" style="background: {{ $isPos ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $isPos ? '#059669' : '#dc2626' }};">
                        <i class="fa-solid {{ $isPos ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Pertumbuhan YoY (Bulan Ini)</div>
                        <div class="kpi-value" style="color: {{ $isPos ? '#059669' : '#dc2626' }};">
                            {{ $isPos ? '+' : '' }}{{ number_format($growth, 1) }}%
                        </div>
                        <div class="kpi-subtext" style="color: {{ $isPos ? '#059669' : '#dc2626' }}; font-weight: 600;">
                            {{ $growthDiff >= 0 ? '+' : '' }}{{ number_format($growthDiff, 2) }} Liter
                        </div>
                    </div>
                </div>

                <!-- KPI 4: Rata-Rata Stok per Toko -->
                <div class="monthly-kpi-card" style="border-left: 4px solid #6366f1;">
                    <div class="kpi-icon-box" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5;">
                        <i class="fa-solid fa-shop"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Rata-Rata per Toko</div>
                        <div class="kpi-value" style="color: #1e293b;">{{ number_format($monthlyCompareData['kpi']['cy_avg_per_store'] ?? 0, 2) }} <span class="kpi-unit">L/Toko</span></div>
                        <div class="kpi-subtext">vs {{ number_format($monthlyCompareData['kpi']['py_avg_per_store'] ?? 0, 2) }} L ({{ $monthlyCompareData['previous_year'] }})</div>
                    </div>
                </div>

                <!-- KPI 5: Komposisi Dulux vs Catylac -->
                <div class="monthly-kpi-card" style="border-left: 4px solid #0284c7;">
                    <div class="kpi-icon-box" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Komposisi Brand ({{ $monthlyCompareData['current_year'] }})</div>
                        <div style="font-size: 0.82rem; font-weight: 700; color: #1e3a8a; margin-top: 4px;">
                            🔵 Dulux: {{ number_format($monthlyCompareData['kpi']['dulux_vol'] ?? 0, 1) }} L ({{ number_format($monthlyCompareData['kpi']['dulux_pct'] ?? 0, 1) }}%)
                        </div>
                        <div style="font-size: 0.82rem; font-weight: 700; color: #059669; margin-top: 2px;">
                            🟢 Catylac: {{ number_format($monthlyCompareData['kpi']['catylac_vol'] ?? 0, 1) }} L ({{ number_format($monthlyCompareData['kpi']['catylac_pct'] ?? 0, 1) }}%)
                        </div>
                    </div>
                </div>
            </div>

            <!-- APEXCHARTS LINE CHART: DAILY TREND COMPARISON -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-top: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <div>
                        <h4 style="margin: 0; font-size: 0.98rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-chart-area" style="color: #0b3d88;"></i>
                            Tren Pergerakan Volume Stock Harian (Tanggal 1 s/d {{ count($monthlyCompareData['daily_trend']['days'] ?? []) }})
                        </h4>
                        <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                            Garis biru merepresentasikan {{ $monthlyCompareData['month_name'] }} {{ $monthlyCompareData['current_year'] }} (Tahun Berjalan) dan garis oranye/amber merepresentasikan {{ $monthlyCompareData['month_name'] }} {{ $monthlyCompareData['previous_year'] }} (Tahun Sebelumnya).
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px; font-size: 0.82rem; font-weight: 700;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; color: #0b3d88;">
                            <span style="width: 12px; height: 12px; border-radius: 3px; background: #0b3d88; display: inline-block;"></span>
                            {{ $monthlyCompareData['month_name'] }} {{ $monthlyCompareData['current_year'] }}
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 6px; color: #d97706;">
                            <span style="width: 12px; height: 12px; border-radius: 3px; background: #f59e0b; display: inline-block;"></span>
                            {{ $monthlyCompareData['month_name'] }} {{ $monthlyCompareData['previous_year'] }}
                        </span>
                    </div>
                </div>

                <div id="chart_stock_monthly_daily_trend" style="min-height: 360px; width: 100%;"></div>
            </div>

            <!-- 2-COLUMN DETAIL GRID: DAY-BY-DAY TABLE & TOP STORES -->
            <div class="monthly-detail-grid" style="margin-top: 1.5rem;">
                <!-- LEFT COLUMN: DAY-BY-DAY BREAKDOWN TABLE -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h4 class="detail-card-title">
                            <i class="fa-solid fa-calendar-day" style="color: #0b3d88;"></i>
                            Rincian Harian Tanggal 1 s/d {{ count($monthlyCompareData['daily_trend']['days'] ?? []) }} ({{ $monthlyCompareData['month_name'] }})
                        </h4>
                        <span style="font-size: 0.76rem; color: #64748b; font-weight: 600;">Satuan: Liter</span>
                    </div>

                    <div class="table-scroll-container" style="max-height: 420px; overflow-y: auto;">
                        <table class="stock-table" style="font-size: 0.82rem;">
                            <thead style="position: sticky; top: 0; z-index: 5;">
                                <tr>
                                    <th style="width: 70px; text-align: center;">TANGGAL</th>
                                    <th style="min-width: 120px; text-align: right;">{{ substr($monthlyCompareData['month_name'] ?? 'Bulan', 0, 3) }} {{ $monthlyCompareData['current_year'] }} (L)</th>
                                    <th style="min-width: 120px; text-align: right;">{{ substr($monthlyCompareData['month_name'] ?? 'Bulan', 0, 3) }} {{ $monthlyCompareData['previous_year'] }} (L)</th>
                                    <th style="min-width: 110px; text-align: right;">SELISIH (L)</th>
                                    <th style="min-width: 90px; text-align: center;">GROWTH</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($monthlyCompareData['daily_trend']['table']))
                                    @foreach($monthlyCompareData['daily_trend']['table'] as $row)
                                        @php
                                            $cVal = (float)$row['cy_volume'];
                                            $pVal = (float)$row['py_volume'];
                                            $delta = (float)$row['delta'];
                                            $dGrowth = (float)$row['growth'];
                                            $hasData = ($cVal > 0 || $pVal > 0);
                                        @endphp
                                        <tr style="{{ $hasData ? 'background: #fff;' : 'background: #fbfcfe; color: #94a3b8;' }}">
                                            <td style="text-align: center; font-weight: 700; color: #0f172a;">
                                                <span class="sap-pill" style="font-size: 0.78rem;">Tgl {{ sprintf('%02d', $row['day']) }}</span>
                                            </td>
                                            <td style="text-align: right; font-weight: 700; color: #0b3d88; font-variant-numeric: tabular-nums;">
                                                {{ $cVal > 0 ? number_format($cVal, 2) : '-' }}
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: #475569; font-variant-numeric: tabular-nums;">
                                                {{ $pVal > 0 ? number_format($pVal, 2) : '-' }}
                                            </td>
                                            <td style="text-align: right; font-weight: 700; color: {{ $delta > 0 ? '#059669' : ($delta < 0 ? '#dc2626' : '#64748b') }}; font-variant-numeric: tabular-nums;">
                                                {{ $delta != 0 ? ($delta > 0 ? '+' : '') . number_format($delta, 2) : '-' }}
                                            </td>
                                            <td style="text-align: center; font-weight: 700; font-size: 0.78rem; color: {{ $dGrowth > 0 ? '#059669' : ($dGrowth < 0 ? '#dc2626' : '#64748b') }};">
                                                @if($cVal > 0 || $pVal > 0)
                                                    {{ ($dGrowth > 0 ? '+' : '') . number_format($dGrowth, 1) }}%
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: #94a3b8;">Tidak ada data harian.</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot style="position: sticky; bottom: 0; z-index: 5;">
                                <tr class="stock-tfoot-row">
                                    <td style="text-align: center; font-weight: 800; text-transform: uppercase;">TOTAL:</td>
                                    <td style="text-align: right; font-weight: 800; color: #0b3d88; font-size: 0.9rem; font-variant-numeric: tabular-nums;">
                                        {{ number_format($monthlyCompareData['kpi']['cy_volume'] ?? 0, 2) }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #475569; font-size: 0.9rem; font-variant-numeric: tabular-nums;">
                                        {{ number_format($monthlyCompareData['kpi']['py_volume'] ?? 0, 2) }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: {{ ($monthlyCompareData['kpi']['growth_diff'] ?? 0) >= 0 ? '#059669' : '#dc2626' }}; font-size: 0.9rem; font-variant-numeric: tabular-nums;">
                                        {{ ($monthlyCompareData['kpi']['growth_diff'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($monthlyCompareData['kpi']['growth_diff'] ?? 0, 2) }}
                                    </td>
                                    <td style="text-align: center; font-weight: 800; font-size: 0.85rem; color: {{ ($monthlyCompareData['kpi']['growth'] ?? 0) >= 0 ? '#059669' : '#dc2626' }};">
                                        {{ ($monthlyCompareData['kpi']['growth'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($monthlyCompareData['kpi']['growth'] ?? 0, 1) }}%
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- RIGHT COLUMN: TOP 10 STORES COMPARISON -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h4 class="detail-card-title">
                            <i class="fa-solid fa-ranking-star" style="color: #f59e0b;"></i>
                            Top 10 Store / Toko Terbesar di Bulan {{ $monthlyCompareData['month_name'] }}
                        </h4>
                        <span style="font-size: 0.76rem; color: #64748b; font-weight: 600;">Ranking Volume</span>
                    </div>

                    <div class="table-scroll-container" style="max-height: 420px; overflow-y: auto;">
                        <table class="stock-table" style="font-size: 0.82rem;">
                            <thead style="position: sticky; top: 0; z-index: 5;">
                                <tr>
                                    <th style="width: 45px; text-align: center;">NO</th>
                                    <th style="min-width: 170px;">NAMA TOKO</th>
                                    <th style="min-width: 100px; text-align: right;">{{ $monthlyCompareData['current_year'] }} (L)</th>
                                    <th style="min-width: 100px; text-align: right;">{{ $monthlyCompareData['previous_year'] }} (L)</th>
                                    <th style="width: 80px; text-align: center;">GROWTH</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($monthlyCompareData['top_stores']))
                                    @foreach($monthlyCompareData['top_stores'] as $idx => $ts)
                                        @php
                                            $tGrowth = (float)($ts['growth'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; color: #64748b;">
                                                {{ $idx + 1 }}
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">
                                                    {{ $ts['store_name'] }}
                                                </div>
                                                <div style="font-size: 0.74rem; color: #64748b;">
                                                    {{ $ts['area'] }} · {{ $ts['channel'] }}
                                                </div>
                                            </td>
                                            <td style="text-align: right; font-weight: 700; color: #0b3d88; font-variant-numeric: tabular-nums;">
                                                {{ number_format($ts['cy_volume'], 2) }}
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: #64748b; font-variant-numeric: tabular-nums;">
                                                {{ $ts['py_volume'] > 0 ? number_format($ts['py_volume'], 2) : '-' }}
                                            </td>
                                            <td style="text-align: center; font-weight: 700; color: {{ $tGrowth >= 0 ? '#059669' : '#dc2626' }};">
                                                {{ $tGrowth >= 0 ? '+' : '' }}{{ number_format($tGrowth, 1) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: #94a3b8;">Belum ada data toko.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PANE 2: REKAP VOLUME STOCK TOKO (PIVOTABLE)                               -->
    <!-- ========================================================================= -->
    <div id="pane_stock_pivotable" class="stock-pane" style="{{ ($activeTab ?? 'monthly') === 'pivotable' ? 'display: block;' : 'display: none;' }}">
        <div class="stock-card">
            <div class="stock-card-header">
                <div>
                    <h3 class="stock-card-title">
                        <i class="fa-solid fa-boxes-stacked" style="color: var(--brand-primary);"></i>
                        Tabel Rekapitulasi Volume Stock Fisik Toko
                    </h3>
                    <div class="stock-card-sub">
                        Volume total stock (Liter) per toko breakdown Dulux, Catylac Smart Choice, dan Catylac beserta Grand Total.
                    </div>
                </div>

                <div class="stock-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Toko:</span>
                        <strong class="meta-val">{{ number_format($stockData['pivotable']['total_stores'] ?? 0) }} Toko</strong>
                    </div>
                    <div class="meta-pill">
                        <span class="meta-lbl">Dulux Stock:</span>
                        <strong class="meta-val" style="color: #1e3a8a;">{{ number_format($stockData['pivotable']['grand_total_dulux'] ?? 0, 2) }} L</strong>
                    </div>
                    <div class="meta-pill">
                        <span class="meta-lbl">Catylac Stock:</span>
                        <strong class="meta-val" style="color: #0284c7;">{{ number_format($stockData['pivotable']['grand_total_catylac'] ?? 0, 2) }} L</strong>
                    </div>
                    <div class="meta-pill meta-pill-highlight">
                        <span class="meta-lbl">Grand Total Stock:</span>
                        <strong class="meta-val">{{ number_format($stockData['pivotable']['grand_total_all'] ?? 0, 2) }} L</strong>
                    </div>
                </div>
            </div>

            <!-- Table Viewport with Horizontal Scroll -->
            <div class="stock-table-viewport">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">NO</th>
                            <th style="width: 100px;">SAP</th>
                            <th style="min-width: 260px;">NAMA TOKO / STORE</th>
                            <th style="width: 90px; text-align: center;">REGION</th>
                            <th style="width: 140px;">AREA</th>
                            <th style="min-width: 140px; text-align: right;">DULUX (L)</th>
                            <th style="min-width: 150px; text-align: right;">CATYLAC SMART CHOICE (L)</th>
                            <th style="min-width: 140px; text-align: right;">CATYLAC (L)</th>
                            <th style="min-width: 160px; text-align: right; background: #0b3d88 !important; color: #fff !important;">GRAND TOTAL STOCK (L)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($stockData['pivotable']['rows']))
                            @foreach($stockData['pivotable']['rows'] as $index => $store)
                                @php
                                    $rowNo = ($stockData['pivotable']['from'] ?? 1) + $index;
                                    $dVol = (float)($store['dulux_vol'] ?? 0);
                                    $scVol = (float)($store['catylac_sc_vol'] ?? 0);
                                    $cVol = (float)($store['catylac_vol'] ?? 0);
                                    $totVol = (float)($store['total_vol'] ?? 0);
                                @endphp
                                <tr>
                                    <td style="text-align: center; font-weight: 600; color: #64748b; font-size: 0.82rem;">
                                        {{ $rowNo }}
                                    </td>
                                    <td>
                                        <span class="sap-pill">{{ $store['sap'] ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem;">
                                            {{ $store['store_name'] }}
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="region-badge {{ strtolower($store['region'] ?? '') }}">{{ $store['region'] ?? '-' }}</span>
                                    </td>
                                    <td style="color: #475569; font-size: 0.84rem;">
                                        {{ $store['area'] ?? '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #1e3a8a; font-variant-numeric: tabular-nums;">
                                        {{ $dVol > 0 ? number_format($dVol, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #059669; font-variant-numeric: tabular-nums;">
                                        {{ $scVol > 0 ? number_format($scVol, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #0284c7; font-variant-numeric: tabular-nums;">
                                        {{ $cVol > 0 ? number_format($cVol, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #0b3d88; font-size: 0.92rem; background: #f0f7ff; font-variant-numeric: tabular-nums;">
                                        {{ $totVol > 0 ? number_format($totVol, 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                    Tidak ada data stock yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    @if(!empty($stockData['pivotable']['rows']))
                        <tfoot>
                            <tr class="stock-tfoot-row">
                                <td colspan="5" style="text-align: right; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.85rem;">
                                    TOTAL TERFILTER:
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #1e3a8a; font-size: 0.92rem; font-variant-numeric: tabular-nums;">
                                    {{ number_format($stockData['pivotable']['grand_total_dulux'] ?? 0, 2) }}
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #059669; font-size: 0.92rem; font-variant-numeric: tabular-nums;">
                                    {{ number_format($stockData['pivotable']['grand_total_catylac_sc'] ?? 0, 2) }}
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #0284c7; font-size: 0.92rem; font-variant-numeric: tabular-nums;">
                                    {{ number_format($stockData['pivotable']['grand_total_catylac'] ?? 0, 2) }}
                                </td>
                                <td style="text-align: right; font-weight: 900; color: #fff; font-size: 0.98rem; background: #0b3d88 !important; font-variant-numeric: tabular-nums;">
                                    {{ number_format($stockData['pivotable']['grand_total_all'] ?? 0, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- PAGINATION PIVOTABLE -->
            @if(($stockData['pivotable']['total_pages'] ?? 1) > 1)
                <div class="stock-pagination-bar">
                    <div class="pagination-info">
                        Menampilkan <strong>{{ $stockData['pivotable']['from'] }}</strong> - <strong>{{ $stockData['pivotable']['to'] }}</strong> dari <strong>{{ number_format($stockData['pivotable']['total']) }}</strong> toko
                    </div>
                    <div class="pagination-controls">
                        @php
                            $currP = $stockData['pivotable']['page'];
                            $totP = $stockData['pivotable']['total_pages'];
                        @endphp
                        @if($currP > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $currP - 1, 'tab' => 'pivotable']) }}" class="page-btn">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        @endif
                        <span class="page-current">Halaman {{ $currP }} / {{ $totP }}</span>
                        @if($currP < $totP)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $currP + 1, 'tab' => 'pivotable']) }}" class="page-btn">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PANE 3: RINGKASAN SCM & STOCK BULANAN (SUMM)                              -->
    <!-- ========================================================================= -->
    <div id="pane_stock_summ" class="stock-pane" style="{{ ($activeTab ?? 'monthly') === 'summ' ? 'display: block;' : 'display: none;' }}">
        <div class="stock-card">
            <div class="stock-card-header">
                <div>
                    <h3 class="stock-card-title">
                        <i class="fa-solid fa-chart-line" style="color: #16a34a;"></i>
                        Tabel Ringkasan SCM (Stock Cover Month) & Volume Offtake
                    </h3>
                    <div class="stock-card-sub">
                        Perbandingan stok fisik terhadap rata-rata penjualan bulanan (*Stock Cover Month* = Stock / Offtake) per toko.
                    </div>
                </div>

                <div class="stock-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Stock:</span>
                        <strong class="meta-val" style="color: #0b3d88;">{{ number_format($stockData['summ']['total_stock'] ?? 0, 2) }} L</strong>
                    </div>
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Offtake:</span>
                        <strong class="meta-val" style="color: #0284c7;">{{ number_format($stockData['summ']['total_offtake'] ?? 0, 2) }} L</strong>
                    </div>
                    <div class="meta-pill meta-pill-highlight" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                        <span class="meta-lbl">Rata-Rata SCM:</span>
                        <strong class="meta-val">{{ number_format($stockData['summ']['avg_scm'] ?? 0, 2) }} Bulan</strong>
                    </div>
                </div>
            </div>

            <!-- Table Viewport with Horizontal Scroll -->
            <div class="stock-table-viewport">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">NO</th>
                            <th style="width: 100px;">SAP</th>
                            <th style="min-width: 250px;">NAMA TOKO / STORE</th>
                            <th style="width: 90px; text-align: center;">REGION</th>
                            <th style="width: 130px;">AREA</th>
                            <th style="min-width: 125px; text-align: right;">DULUX STOCK (L)</th>
                            <th style="min-width: 125px; text-align: right;">CATYLAC STOCK (L)</th>
                            <th style="min-width: 135px; text-align: right; background: #1e3a8a !important; color: #fff !important;">TOTAL STOCK (L)</th>
                            <th style="min-width: 125px; text-align: right;">DULUX OFFTAKE (L)</th>
                            <th style="min-width: 125px; text-align: right;">CATYLAC OFFTAKE (L)</th>
                            <th style="min-width: 135px; text-align: right; background: #0369a1 !important; color: #fff !important;">TOTAL OFFTAKE (L)</th>
                            <th style="min-width: 120px; text-align: right;">DULUX SCM</th>
                            <th style="min-width: 120px; text-align: right;">CATYLAC SCM</th>
                            <th style="min-width: 130px; text-align: right; background: #065f46 !important; color: #fff !important;">TOTAL SCM (BLN)</th>
                            <th style="width: 120px; text-align: center;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($stockData['summ']['rows']))
                            @foreach($stockData['summ']['rows'] as $index => $store)
                                @php
                                    $rowNo = ($stockData['summ']['from'] ?? 1) + $index;
                                    $dStk = (float)($store['dulux_stock'] ?? 0);
                                    $cStk = (float)($store['catylac_stock'] ?? 0);
                                    $tStk = (float)($store['total_stock'] ?? 0);
                                    $dOff = (float)($store['dulux_offtake'] ?? 0);
                                    $cOff = (float)($store['catylac_offtake'] ?? 0);
                                    $tOff = (float)($store['total_offtake'] ?? 0);
                                    
                                    $scmD = $dOff > 0 ? ($dStk / $dOff) : 0;
                                    $scmC = $cOff > 0 ? ($cStk / $cOff) : 0;
                                    $scmTot = $tOff > 0 ? ($tStk / $tOff) : 0;
                                @endphp
                                <tr>
                                    <td style="text-align: center; font-weight: 600; color: #64748b; font-size: 0.82rem;">
                                        {{ $rowNo }}
                                    </td>
                                    <td>
                                        <span class="sap-pill">{{ $store['sap'] ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem;">
                                            {{ $store['store_name'] }}
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="region-badge {{ strtolower($store['region'] ?? '') }}">{{ $store['region'] ?? '-' }}</span>
                                    </td>
                                    <td style="color: #475569; font-size: 0.84rem;">
                                        {{ $store['area'] ?? '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #1e3a8a; font-variant-numeric: tabular-nums;">
                                        {{ $dStk > 0 ? number_format($dStk, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #0284c7; font-variant-numeric: tabular-nums;">
                                        {{ $cStk > 0 ? number_format($cStk, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #1e3a8a; background: #f0f7ff; font-variant-numeric: tabular-nums;">
                                        {{ $tStk > 0 ? number_format($tStk, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #475569; font-variant-numeric: tabular-nums;">
                                        {{ $dOff > 0 ? number_format($dOff, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #475569; font-variant-numeric: tabular-nums;">
                                        {{ $cOff > 0 ? number_format($cOff, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #0369a1; background: #f0f9ff; font-variant-numeric: tabular-nums;">
                                        {{ $tOff > 0 ? number_format($tOff, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                        {{ $scmD > 0 ? number_format($scmD, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                        {{ $scmC > 0 ? number_format($scmC, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #065f46; background: #ecfdf5; font-size: 0.92rem; font-variant-numeric: tabular-nums;">
                                        {{ $scmTot > 0 ? number_format($scmTot, 2) : '-' }}
                                    </td>
                                    <td style="text-align: center;">
                                        @if($scmTot <= 0)
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #64748b;">No Offtake</span>
                                        @elseif($scmTot < 1.0)
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; background: #fee2e2; color: #dc2626;">Low Stock</span>
                                        @elseif($scmTot <= 2.5)
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; background: #dcfce7; color: #16a34a;">Ideal (1-2.5)</span>
                                        @else
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #d97706;">Overstock</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="15" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                    Tidak ada data SCM yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION SUMM -->
            @if(($stockData['summ']['total_pages'] ?? 1) > 1)
                <div class="stock-pagination-bar">
                    <div class="pagination-info">
                        Menampilkan <strong>{{ $stockData['summ']['from'] }}</strong> - <strong>{{ $stockData['summ']['to'] }}</strong> dari <strong>{{ number_format($stockData['summ']['total']) }}</strong> toko
                    </div>
                    <div class="pagination-controls">
                        @php
                            $currP = $stockData['summ']['page'];
                            $totP = $stockData['summ']['total_pages'];
                        @endphp
                        @if($currP > 1)
                            <a href="{{ request()->fullUrlWithQuery(['summ_page' => $currP - 1, 'tab' => 'summ']) }}" class="page-btn">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        @endif
                        <span class="page-current">Halaman {{ $currP }} / {{ $totP }}</span>
                        @if($currP < $totP)
                            <a href="{{ request()->fullUrlWithQuery(['summ_page' => $currP + 1, 'tab' => 'summ']) }}" class="page-btn">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PANE 4: RAW DATA SUBMISSIONS (16 KOLOM)                                   -->
    <!-- ========================================================================= -->
    <div id="pane_stock_raw" class="stock-pane" style="{{ ($activeTab ?? 'monthly') === 'raw' ? 'display: block;' : 'display: none;' }}">
        <div class="stock-card">
            <div class="stock-card-header">
                <div>
                    <h3 class="stock-card-title">
                        <i class="fa-solid fa-list-check" style="color: #0284c7;"></i>
                        Raw Data Transaksi Submissions Stock End
                    </h3>
                    <div class="stock-card-sub">
                        Seluruh entri transaksi detail stock opname 16 field input yang dilaporkan per SKU produk.
                    </div>
                </div>

                <div class="stock-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Baris:</span>
                        <strong class="meta-val">{{ number_format($stockData['submissions']['total'] ?? 0) }} Baris</strong>
                    </div>
                </div>
            </div>

            <!-- Table Viewport with Horizontal Scroll -->
            <div class="stock-table-viewport">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th style="min-width: 140px;">SUBMISSION DATE</th>
                            <th style="min-width: 110px;">TGL CATAT</th>
                            <th style="width: 75px; text-align: center;">REGION</th>
                            <th style="min-width: 120px;">AREA</th>
                            <th style="min-width: 90px;">SAP</th>
                            <th style="min-width: 220px;">NAMA TOKO</th>
                            <th style="min-width: 110px;">KETERANGAN</th>
                            <th style="min-width: 100px;">BRAND</th>
                            <th style="min-width: 180px;">PRODUK</th>
                            <th style="min-width: 120px;">WARNA</th>
                            <th style="min-width: 110px; text-align: right;">KEMASAN (G)</th>
                            <th style="min-width: 90px; text-align: right;">QTY (G)</th>
                            <th style="min-width: 110px; text-align: right;">KEMASAN (P)</th>
                            <th style="min-width: 90px; text-align: right;">QTY (P)</th>
                            <th style="min-width: 120px; text-align: right; background: #0b3d88 !important; color: #fff !important;">VOL (L)</th>
                            <th style="min-width: 80px; text-align: right;">CONF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($stockData['submissions']['rows']))
                            @foreach($stockData['submissions']['rows'] as $index => $row)
                                @php
                                    $rowNo = ($stockData['submissions']['from'] ?? 1) + $index;
                                    $vL = (float)($row['volume_liter'] ?? 0);
                                @endphp
                                <tr>
                                    <td style="text-align: center; font-weight: 600; color: #64748b; font-size: 0.82rem;">
                                        {{ $rowNo }}
                                    </td>
                                    <td style="font-size: 0.82rem; color: #334155; white-space: nowrap;">
                                        {{ $row['submission_date'] ?? '-' }}
                                    </td>
                                    <td style="font-size: 0.82rem; color: #475569; white-space: nowrap;">
                                        {{ $row['tgl_catat'] ? date('Y-m-d', strtotime($row['tgl_catat'])) : '-' }}
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="region-badge {{ strtolower($row['region'] ?? '') }}">{{ $row['region'] ?? '-' }}</span>
                                    </td>
                                    <td style="font-size: 0.84rem; color: #475569;">
                                        {{ $row['area'] ?? '-' }}
                                    </td>
                                    <td>
                                        <span class="sap-pill">{{ $row['sap'] ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #0f172a; font-size: 0.84rem;">
                                            {{ $row['store_name'] ?? '-' }}
                                        </div>
                                    </td>
                                    <td style="font-size: 0.82rem; color: #64748b;">
                                        {{ $row['keterangan'] ?? '-' }}
                                    </td>
                                    <td>
                                        @if(str_contains(strtolower($row['brand'] ?? ''), 'dulux'))
                                            <span style="font-weight: 700; color: #1e3a8a;">{{ $row['brand'] }}</span>
                                        @elseif(str_contains(strtolower($row['brand'] ?? ''), 'smart choice'))
                                            <span style="font-weight: 700; color: #059669;">{{ $row['brand'] }}</span>
                                        @else
                                            <span style="font-weight: 700; color: #0284c7;">{{ $row['brand'] ?? '-' }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 0.84rem; color: #1e293b; font-weight: 500;">
                                        {{ $row['produk'] ?? '-' }}
                                    </td>
                                    <td style="font-size: 0.82rem; color: #475569;">
                                        {{ $row['warna'] ?? '-' }}
                                    </td>
                                    <td style="text-align: right; font-variant-numeric: tabular-nums;">
                                        {{ $row['kemasan_galon'] ? number_format((float)$row['kemasan_galon'], 1) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                        {{ $row['qty_galon'] ? number_format((int)$row['qty_galon']) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-variant-numeric: tabular-nums;">
                                        {{ $row['kemasan_pail'] ? number_format((float)$row['kemasan_pail'], 1) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">
                                        {{ $row['qty_pail'] ? number_format((int)$row['qty_pail']) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #0b3d88; font-variant-numeric: tabular-nums; background: #f0f7ff;">
                                        {{ $vL > 0 ? number_format($vL, 2) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.82rem; color: #64748b; font-variant-numeric: tabular-nums;">
                                        {{ $row['conf'] ? number_format((float)$row['conf'], 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="17" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                    Tidak ada data submission yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION SUBMISSIONS -->
            @if(($stockData['submissions']['total_pages'] ?? 1) > 1)
                <div class="stock-pagination-bar">
                    <div class="pagination-info">
                        Menampilkan <strong>{{ $stockData['submissions']['from'] }}</strong> - <strong>{{ $stockData['submissions']['to'] }}</strong> dari <strong>{{ number_format($stockData['submissions']['total']) }}</strong> baris
                    </div>
                    <div class="pagination-controls">
                        @php
                            $currP = $stockData['submissions']['page'];
                            $totP = $stockData['submissions']['total_pages'];
                        @endphp
                        @if($currP > 1)
                            <a href="{{ request()->fullUrlWithQuery(['raw_page' => $currP - 1, 'tab' => 'raw']) }}" class="page-btn">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        @endif
                        <span class="page-current">Halaman {{ $currP }} / {{ $totP }}</span>
                        @if($currP < $totP)
                            <a href="{{ request()->fullUrlWithQuery(['raw_page' => $currP + 1, 'tab' => 'raw']) }}" class="page-btn">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

<!-- STYLES -->
<style>
    .custom-stock-wrapper {
        font-family: inherit;
    }
    .stock-main-nav {
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);
    }
    .stock-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border: none;
        background: transparent;
        color: #475569;
        font-weight: 700;
        font-size: 0.85rem;
        border-radius: 9px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .stock-nav-btn:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.6);
    }
    .stock-nav-btn.active {
        background: #ffffff;
        color: var(--brand-primary, #0b3d88);
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .stock-nav-btn .badge-count {
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 6px;
    }
    .stock-nav-btn.active .badge-count {
        background: var(--brand-glow, #e0e7ff);
        color: var(--brand-primary, #0b3d88);
    }

    .btn-stock-export {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 0.85rem;
        border-radius: 9px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        color: #334155;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        transition: all 0.15s ease;
    }
    .btn-stock-export:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    .stock-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .stock-card-header {
        padding: 1.25rem 1.5rem;
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .stock-card-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .stock-card-sub {
        font-size: 0.82rem;
        color: #64748b;
        margin-top: 4px;
    }

    .stock-header-meta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
    }
    .meta-lbl {
        color: #64748b;
        font-weight: 600;
    }
    .meta-val {
        color: #0f172a;
        font-weight: 800;
    }
    .meta-pill-highlight {
        background: linear-gradient(135deg, #0b3d88 0%, #1e40af 100%);
        border: none;
    }
    .meta-pill-highlight .meta-lbl {
        color: rgba(255,255,255,0.85);
    }
    .meta-pill-highlight .meta-val {
        color: #ffffff;
    }

    /* Monthly KPI Grid */
    .monthly-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        padding: 1rem 1.5rem 0.5rem 1.5rem;
    }
    .monthly-kpi-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .monthly-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    }
    .kpi-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .kpi-label {
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .kpi-value {
        font-size: 1.25rem;
        font-weight: 800;
        margin-top: 2px;
        line-height: 1.2;
    }
    .kpi-unit {
        font-size: 0.76rem;
        font-weight: 600;
        color: #64748b;
    }
    .kpi-subtext {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 3px;
        font-weight: 500;
    }

    /* 2-Column Grid */
    .monthly-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        padding: 0 1.5rem 1.5rem 1.5rem;
    }
    @media (max-width: 1024px) {
        .monthly-detail-grid {
            grid-template-columns: 1fr;
        }
    }
    .detail-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .detail-card-header {
        padding: 0.9rem 1.25rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .detail-card-title {
        margin: 0;
        font-size: 0.88rem;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .stock-table-viewport {
        width: 100%;
        overflow-x: auto;
        position: relative;
    }
    .stock-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.84rem;
        text-align: left;
    }
    .stock-table th {
        background: #0d2857;
        color: #ffffff;
        font-weight: 700;
        font-size: 0.78rem;
        letter-spacing: 0.4px;
        padding: 12px 14px;
        border-bottom: 2px solid #081d3f;
        white-space: nowrap;
    }
    .stock-table td {
        padding: 11px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    .stock-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .stock-tfoot-row td {
        background: #f1f5f9;
        border-top: 2px solid #cbd5e1;
        padding: 14px;
    }

    .sap-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        font-family: monospace;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .region-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        background: #e2e8f0;
        color: #334155;
    }
    .region-badge.r1 { background: #fee2e2; color: #991b1b; }
    .region-badge.r2 { background: #e0e7ff; color: #3730a3; }
    .region-badge.r3 { background: #dcfce7; color: #166534; }
    .region-badge.r4 { background: #fef3c7; color: #92400e; }

    .stock-pagination-bar {
        padding: 1rem 1.5rem;
        background: #fafcff;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.84rem;
    }
    .stock-pagination-bar .pagination-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .stock-pagination-bar .page-btn {
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .stock-pagination-bar .page-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .stock-pagination-bar .page-current {
        font-weight: 700;
        color: #0f172a;
        padding: 0 6px;
    }
</style>

<!-- JAVASCRIPT TAB SWITCHER & APEXCHARTS INITIALIZATION -->
<script>
    function switchStockTab(tabKey) {
        // 1. Hide all panes
        const panes = document.querySelectorAll('.stock-pane');
        panes.forEach(p => p.style.display = 'none');

        // 2. Remove active from all nav buttons
        const navBtns = document.querySelectorAll('.stock-nav-btn');
        navBtns.forEach(b => b.classList.remove('active'));

        // 3. Show target pane & activate button
        const targetPane = document.getElementById('pane_stock_' + tabKey);
        const targetBtn = document.getElementById('btn_stock_tab_' + tabKey);
        if (targetPane) targetPane.style.display = 'block';
        if (targetBtn) targetBtn.classList.add('active');

        // 4. Update URL parameter silently
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url);

        // 5. Trigger resize for ApexCharts if monthly tab opened
        if (tabKey === 'monthly' && window.stockDailyTrendChart) {
            setTimeout(function() {
                window.stockDailyTrendChart.windowResize();
            }, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(!empty($monthlyCompareData['daily_trend']['categories']))
            var dailyCategories = {!! json_encode($monthlyCompareData['daily_trend']['categories']) !!};
            var cyDailyData = {!! json_encode($monthlyCompareData['daily_trend']['cy_series']) !!};
            var pyDailyData = {!! json_encode($monthlyCompareData['daily_trend']['py_series']) !!};
            var cyYearName = "{{ $monthlyCompareData['month_name'] ?? 'Bulan' }} {{ $monthlyCompareData['current_year'] ?? 2026 }}";
            var pyYearName = "{{ $monthlyCompareData['month_name'] ?? 'Bulan' }} {{ $monthlyCompareData['previous_year'] ?? 2025 }}";

            var dailyTrendOptions = {
                series: [{
                    name: cyYearName + ' (Tahun Berjalan)',
                    data: cyDailyData
                }, {
                    name: pyYearName + ' (Tahun Sebelumnya)',
                    data: pyDailyData
                }],
                chart: {
                    type: 'area',
                    height: 360,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    },
                    fontFamily: 'Outfit, Plus Jakarta Sans, sans-serif'
                },
                colors: ['#0b3d88', '#f59e0b'],
                stroke: {
                    curve: 'smooth',
                    width: [3, 2],
                    dashArray: [0, 4]
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: [0.35, 0.12],
                        opacityTo: [0.03, 0.0],
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: [4, 3],
                    hover: { size: 6 }
                },
                xaxis: {
                    categories: dailyCategories,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '11px',
                            fontWeight: 600
                        },
                        rotate: -45,
                        rotateAlways: false
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    title: {
                        text: 'Volume Stock (Liter)',
                        style: { color: '#475569', fontWeight: 600, fontSize: '12px' }
                    },
                    labels: {
                        formatter: function (val) {
                            if (val >= 1000000) return (val/1000000).toFixed(1) + "M L";
                            if (val >= 1000) return (val/1000).toFixed(1) + "k L";
                            return val ? val.toLocaleString('id-ID') + " L" : "0 L";
                        },
                        style: { colors: '#64748b', fontSize: '11px' }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return (val !== null && val !== undefined) ? val.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + " Liter" : "0.00 Liter";
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '13px',
                    fontWeight: 600,
                    markers: { radius: 12 }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4
                }
            };

            var chartEl = document.querySelector("#chart_stock_monthly_daily_trend");
            if (chartEl && typeof ApexCharts !== 'undefined') {
                var dailyTrendChart = new ApexCharts(chartEl, dailyTrendOptions);
                dailyTrendChart.render();
                window.stockDailyTrendChart = dailyTrendChart;
            }
        @endif
    });
</script>
