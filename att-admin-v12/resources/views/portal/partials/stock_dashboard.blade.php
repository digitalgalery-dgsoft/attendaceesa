{{-- PORTAL STOCK END EXECUTIVE DASHBOARD (PIVOTABLE, SUMM & SCM, RAW DATA SUBMISSIONS) --}}
<div class="custom-stock-wrapper" style="margin-bottom: 2rem; width: 100%; max-width: 100%; min-width: 0;">

    <!-- TOP TOOLBAR: TAB NAVIGATION & EXPORT BUTTONS -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="stock-main-nav" style="background: #e2e8f0; padding: 4px; border-radius: 12px; display: inline-flex; gap: 4px; flex-wrap: wrap;">
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'pivotable') === 'pivotable' ? 'active' : '' }}" id="btn_stock_tab_pivotable" onclick="switchStockTab('pivotable')">
                <i class="fa-solid fa-boxes-stacked" style="font-size: 0.95rem;"></i>
                <span>Rekap Volume Stock Toko</span>
                <span class="badge-count">{{ number_format($stockData['pivotable']['total_stores'] ?? 0) }} Toko</span>
            </button>
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'pivotable') === 'summ' ? 'active' : '' }}" id="btn_stock_tab_summ" onclick="switchStockTab('summ')">
                <i class="fa-solid fa-chart-line" style="font-size: 0.95rem;"></i>
                <span>Ringkasan SCM & Stock Bulanan</span>
                <span class="badge-count">{{ number_format($stockData['summ']['total_stores'] ?? 0) }} Toko</span>
            </button>
            <button type="button" class="stock-nav-btn {{ ($activeTab ?? 'pivotable') === 'raw' ? 'active' : '' }}" id="btn_stock_tab_raw" onclick="switchStockTab('raw')">
                <i class="fa-solid fa-list-check" style="font-size: 0.95rem;"></i>
                <span>Raw Data Submissions</span>
                <span class="badge-count">{{ number_format($stockData['submissions']['total'] ?? 0) }} Baris</span>
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Periode Indicator -->
            <div style="font-size: 0.84rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; background: #fff; padding: 0.5rem 0.9rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-calendar-days" style="color: var(--brand-primary);"></i>
                <span>Periode: <strong>{{ reset($stockData['months']) }} – {{ end($stockData['months']) }}</strong></span>
            </div>

            <!-- Export Buttons -->
            <div style="display: inline-flex; gap: 6px;">
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_pivot', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" title="Download Rekap Volume Stock Per Toko (Pivotable)">
                    <i class="fa-solid fa-file-excel" style="color: #107c41;"></i>
                    <span>Export Rekap Stock</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_summ', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" style="background: #f0fdf4;" title="Download Ringkasan SCM & Stock (Summ)">
                    <i class="fa-solid fa-file-waveform" style="color: #16a34a;"></i>
                    <span>Export SCM & Stock</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'stock_raw', 'p' => $tenantPrincipal->id])) }}" class="btn-stock-export" style="background: #f8fafc;" title="Download Raw Data Submissions">
                    <i class="fa-solid fa-file-csv" style="color: #0284c7;"></i>
                    <span>Export Raw Data</span>
                </a>
            </div>
        </div>
    </div>

    <!-- PANE 1: REKAP VOLUME STOCK TOKO (PIVOTABLE) -->
    <div id="pane_stock_pivotable" class="stock-pane" style="{{ ($activeTab ?? 'pivotable') === 'pivotable' ? 'display: block;' : 'display: none;' }}">
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
                            <a href="{{ request()->fullUrlWithQuery(['pivot_page' => $currP - 1, 'tab' => 'pivotable']) }}" class="page-btn">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        @endif
                        <span class="page-current">Halaman {{ $currP }} / {{ $totP }}</span>
                        @if($currP < $totP)
                            <a href="{{ request()->fullUrlWithQuery(['pivot_page' => $currP + 1, 'tab' => 'pivotable']) }}" class="page-btn">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- PANE 2: RINGKASAN SCM & STOCK BULANAN (SUMM) -->
    <div id="pane_stock_summ" class="stock-pane" style="{{ ($activeTab ?? 'pivotable') === 'summ' ? 'display: block;' : 'display: none;' }}">
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

    <!-- PANE 3: RAW DATA SUBMISSIONS (16 KOLOM) -->
    <div id="pane_stock_raw" class="stock-pane" style="{{ ($activeTab ?? 'pivotable') === 'raw' ? 'display: block;' : 'display: none;' }}">
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

<!-- STYLES FOR STOCK DASHBOARD -->
<style>
    .stock-main-nav .stock-nav-btn {
        border: none;
        background: transparent;
        padding: 8px 16px;
        border-radius: 9px;
        font-size: 0.88rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stock-main-nav .stock-nav-btn:hover {
        color: #0f172a;
        background: rgba(255,255,255,0.6);
    }
    .stock-main-nav .stock-nav-btn.active {
        background: #ffffff;
        color: var(--brand-primary);
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .stock-main-nav .badge-count {
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.72rem;
        padding: 2px 7px;
        border-radius: 6px;
        font-weight: 700;
    }
    .stock-main-nav .stock-nav-btn.active .badge-count {
        background: #eff6ff;
        color: var(--brand-primary);
    }

    .btn-stock-export {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.5rem 0.85rem;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1e293b;
        background: #ffffff;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-stock-export:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.06);
    }

    .stock-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .stock-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        background: #fafcff;
    }
    .stock-card-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .stock-card-sub {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 3px;
    }

    .stock-header-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .meta-pill {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .meta-pill .meta-lbl {
        color: #64748b;
    }
    .meta-pill .meta-val {
        color: #0f172a;
    }
    .meta-pill-highlight {
        background: linear-gradient(135deg, #0b3d88 0%, #0284c7 100%);
        border: none;
        color: #ffffff;
    }
    .meta-pill-highlight .meta-lbl {
        color: rgba(255,255,255,0.85);
    }
    .meta-pill-highlight .meta-val {
        color: #ffffff;
        font-size: 0.9rem;
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

<!-- JAVASCRIPT TAB SWITCHER -->
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
    }
</script>
