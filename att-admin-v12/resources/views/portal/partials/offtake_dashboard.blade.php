{{-- PORTAL OFFTAKE EXECUTIVE DASHBOARD (SHEET 2 PIVOT & SHEET 1 RAW DATA) --}}
<div class="custom-offtake-wrapper" style="margin-bottom: 2rem; width: 100%; max-width: 100%; min-width: 0;">

    <!-- TOP TOOLBAR: TAB NAVIGATION & EXPORT BUTTONS -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="offtake-main-nav" style="background: #e2e8f0; padding: 4px; border-radius: 12px; display: inline-flex; gap: 4px;">
            <button type="button" class="offtake-nav-btn {{ ($activeTab ?? 'sheet2') === 'sheet2' ? 'active' : '' }}" id="btn_offtake_tab_sheet2" onclick="switchOfftakeTab('sheet2')">
                <i class="fa-solid fa-table-cells" style="font-size: 0.95rem;"></i>
                <span>Rekap Volume Toko</span>
                <span class="badge-count">{{ number_format($offtakeData['sheet2']['total_stores'] ?? 0) }} Toko</span>
            </button>
            <button type="button" class="offtake-nav-btn {{ ($activeTab ?? 'sheet2') === 'sheet1' ? 'active' : '' }}" id="btn_offtake_tab_sheet1" onclick="switchOfftakeTab('sheet1')">
                <i class="fa-solid fa-receipt" style="font-size: 0.95rem;"></i>
                <span>Raw Data Transaksi</span>
                <span class="badge-count">{{ number_format($offtakeData['sheet1']['total_records'] ?? 0) }} Baris</span>
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Periode Indicator -->
            <div style="font-size: 0.84rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; background: #fff; padding: 0.5rem 0.9rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-calendar-days" style="color: var(--brand-primary);"></i>
                <span>Periode: <strong>{{ reset($offtakeData['months']) }} – {{ end($offtakeData['months']) }}</strong></span>
            </div>

            <!-- Export Buttons -->
            <div style="display: inline-flex; gap: 6px;">
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'sheet2', 'p' => $tenantPrincipal->id])) }}" class="btn-offtake-export" title="Download Rekap Volume Per Toko">
                    <i class="fa-solid fa-file-excel" style="color: #107c41;"></i>
                    <span>Export Rekap Toko</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="btn-offtake-export" style="background: #f8fafc;" title="Download Raw Data Transaksi">
                    <i class="fa-solid fa-file-csv" style="color: #0284c7;"></i>
                    <span>Export Raw Data</span>
                </a>
            </div>
        </div>
    </div>

    <!-- PANE 1: REKAPITULASI VOLUME TOKO -->
    <div id="pane_offtake_sheet2" class="offtake-pane" style="{{ ($activeTab ?? 'sheet2') === 'sheet2' ? 'display: block;' : 'display: none;' }}">
        <div class="offtake-card">
            <div class="offtake-card-header">
                <div>
                    <h3 class="offtake-card-title">
                        <i class="fa-solid fa-chart-pie" style="color: var(--brand-primary);"></i>
                        Tabel Rekapitulasi Volume Penjualan Toko
                    </h3>
                    <div class="offtake-card-sub">
                        Volume total penjualan (Liter) per toko pada bulan terpilih beserta Grand Total seluruh toko.
                    </div>
                </div>

                <div class="offtake-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Toko Terfilter:</span>
                        <strong class="meta-val">{{ number_format($offtakeData['sheet2']['total_stores']) }} Toko</strong>
                    </div>
                    <div class="meta-pill meta-pill-highlight">
                        <span class="meta-lbl">Grand Total Volume:</span>
                        <strong class="meta-val">{{ number_format($offtakeData['sheet2']['grand_total']['total_vol'] ?? 0, 2) }} L</strong>
                    </div>
                </div>
            </div>

            <!-- Table Viewport with Horizontal Scroll -->
            <div class="offtake-table-viewport">
                <table class="offtake-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">NO</th>
                            <th style="width: 100px;">SAP</th>
                            <th style="min-width: 260px;">NAMA TOKO / STORE</th>
                            <th style="width: 90px; text-align: center;">REGION</th>
                            <th style="width: 140px;">AREA</th>
                            @foreach($offtakeData['months'] as $mKey => $mLabel)
                                <th style="min-width: 130px; text-align: right;">{{ strtoupper($mLabel) }} (L)</th>
                            @endforeach
                            <th style="min-width: 150px; text-align: right; background: #0b3d88 !important; color: #fff !important;">GRAND TOTAL (L)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($offtakeData['sheet2']['stores']))
                            @foreach($offtakeData['sheet2']['stores'] as $index => $store)
                                @php
                                    $rowNo = ($offtakeData['sheet2']['from'] ?? 1) + $index;
                                    $totalVol = (float)($store['total_vol'] ?? 0);
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
                                            {{ $store['name_store'] }}
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="region-pill">{{ $store['region'] ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.82rem; color: #475569; font-weight: 500;">
                                            {{ $store['area'] ?: '-' }}
                                        </span>
                                    </td>
                                    @foreach($offtakeData['months'] as $mKey => $mLabel)
                                        @php
                                            $mVol = (float)($store["m_{$mKey}"] ?? 0);
                                        @endphp
                                        <td style="text-align: right; font-weight: {{ $mVol > 0 ? '600' : '400' }}; color: {{ $mVol > 0 ? '#1e293b' : '#94a3b8' }};">
                                            {{ $mVol > 0 ? number_format($mVol, 2) : '-' }}
                                        </td>
                                    @endforeach
                                    <td style="text-align: right; font-weight: 800; color: var(--brand-primary); font-size: 0.92rem; background: #f8fafc;">
                                        {{ number_format($totalVol, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ 6 + count($offtakeData['months']) }}" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-box-open" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem;"></i>
                                    <div>Tidak ada data penjualan toko untuk filter yang dipilih.</div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="tfoot-grand-total">
                            <td colspan="5" style="text-align: right; font-weight: 800; font-size: 0.92rem; letter-spacing: 0.5px; padding-right: 1.5rem;">
                                GRAND TOTAL (SELURUH TOKO TERFILTER):
                            </td>
                            @foreach($offtakeData['months'] as $mKey => $mLabel)
                                @php
                                    $gMVol = (float)($offtakeData['sheet2']['grand_total']["m_{$mKey}"] ?? 0);
                                @endphp
                                <td style="text-align: right; font-weight: 800; font-size: 0.92rem;">
                                    {{ number_format($gMVol, 2) }}
                                </td>
                            @endforeach
                            <td style="text-align: right; font-weight: 900; font-size: 1.05rem; background: #0b3d88 !important; color: #fff !important;">
                                {{ number_format((float)($offtakeData['sheet2']['grand_total']['total_vol'] ?? 0), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Table Pagination & Navigation Bar -->
            <div class="offtake-table-footer">
                <div style="font-size: 0.85rem; color: #64748b;">
                    Menampilkan <strong>{{ $offtakeData['sheet2']['from'] }}</strong> – <strong>{{ $offtakeData['sheet2']['to'] }}</strong> dari <strong>{{ number_format($offtakeData['sheet2']['total_stores']) }}</strong> toko
                </div>

                @if(($offtakeData['sheet2']['total_pages'] ?? 1) > 1)
                    <div class="offtake-pagination">
                        @php
                            $curPage = $offtakeData['sheet2']['page'];
                            $totPages = $offtakeData['sheet2']['total_pages'];
                            $queryAll = request()->query();
                            unset($queryAll['page']);
                            $queryAll['tab'] = 'sheet2';
                        @endphp

                        @if($curPage > 1)
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryAll, ['page' => $curPage - 1])) }}" class="page-link-btn">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        @endif

                        @for($p = max(1, $curPage - 2); $p <= min($totPages, $curPage + 2); $p++)
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryAll, ['page' => $p])) }}" class="page-link-btn {{ $p == $curPage ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($curPage < $totPages)
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryAll, ['page' => $curPage + 1])) }}" class="page-link-btn">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- PANE 2: SHEET 1 RAW DATA TRANSAKSI -->
    <div id="pane_offtake_sheet1" class="offtake-pane" style="{{ ($activeTab ?? 'sheet2') === 'sheet1' ? 'display: block;' : 'display: none;' }}">
        <div class="offtake-card">
            <div class="offtake-card-header">
                <div>
                    <h3 class="offtake-card-title">
                        <i class="fa-solid fa-receipt" style="color: #0284c7;"></i>
                        Raw Data Transaksi Penjualan Dulux & Catylac
                    </h3>
                    <div class="offtake-card-sub">
                        Detail log transaksi penjualan, ukuran kemasan galon & pail, kuantiti unit, dan volume liter.
                    </div>
                </div>

                <div class="offtake-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Transaksi Terfilter:</span>
                        <strong class="meta-val">{{ number_format($offtakeData['sheet1']['total_records']) }} Baris</strong>
                    </div>
                </div>
            </div>

            <!-- Table Viewport with Horizontal Scroll -->
            <div class="offtake-table-viewport">
                <table class="offtake-table offtake-raw-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th style="width: 105px;">TANGGAL</th>
                            <th style="min-width: 200px;">NAMA TOKO / STORE</th>
                            <th style="width: 90px;">SAP</th>
                            <th style="width: 75px; text-align: center;">REGION</th>
                            <th style="width: 120px;">AREA</th>
                            <th style="width: 95px;">BRAND</th>
                            <th style="min-width: 180px;">SUB BRAND</th>
                            <th style="width: 100px; text-align: right;">KEMASAN GALON</th>
                            <th style="width: 85px; text-align: right;">QTY GALON</th>
                            <th style="width: 100px; text-align: right;">KEMASAN PAIL</th>
                            <th style="width: 85px; text-align: right;">QTY PAIL</th>
                            <th style="min-width: 115px; text-align: right; background: #0b3d88 !important; color: #fff !important;">VOLUME (L)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($offtakeData['sheet1']['rows']))
                            @foreach($offtakeData['sheet1']['rows'] as $index => $r)
                                @php
                                    $rawNo = ($offtakeData['sheet1']['from'] ?? 1) + $index;
                                    $volL = (float)($r['volume_liter'] ?? 0);
                                @endphp
                                <tr>
                                    <td style="text-align: center; color: #64748b; font-size: 0.8rem;">
                                        {{ $rawNo }}
                                    </td>
                                    <td style="font-size: 0.82rem; color: #334155;">
                                        {{ !empty($r['trans_date']) ? date('d/m/Y', strtotime($r['trans_date'])) : '-' }}
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">
                                            {{ $r['name_store'] }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="sap-pill">{{ $r['sap'] ?: '-' }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="region-pill">{{ $r['region'] ?: '-' }}</span>
                                    </td>
                                    <td style="font-size: 0.82rem; color: #475569;">
                                        {{ $r['area'] ?: '-' }}
                                    </td>
                                    <td>
                                        <span class="brand-tag {{ strtolower($r['brand']) === 'dulux' ? 'brand-tag-dulux' : 'brand-tag-catylac' }}">
                                            {{ $r['brand'] ?: '-' }}
                                        </span>
                                    </td>
                                    <td style="font-size: 0.84rem; font-weight: 600; color: #1e293b;">
                                        {{ $r['sub_brand'] ?: '-' }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.82rem;">
                                        {{ !empty($r['kemasan_galon']) && $r['kemasan_galon'] !== '0' ? $r['kemasan_galon'] . ' L' : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ !empty($r['qty_galon']) ? number_format((float)$r['qty_galon']) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.82rem;">
                                        {{ !empty($r['kemasan_pail']) && $r['kemasan_pail'] !== '0' ? $r['kemasan_pail'] . ' L' : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ !empty($r['qty_pail']) ? number_format((float)$r['qty_pail']) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: var(--brand-primary); font-size: 0.88rem; background: #f8fafc;">
                                        {{ number_format($volL, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="13" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-box-open" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem;"></i>
                                    <div>Tidak ada data transaksi untuk filter yang dipilih.</div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination & Navigation Bar -->
            <div class="offtake-table-footer">
                <div style="font-size: 0.85rem; color: #64748b;">
                    Menampilkan <strong>{{ $offtakeData['sheet1']['from'] }}</strong> – <strong>{{ $offtakeData['sheet1']['to'] }}</strong> dari <strong>{{ number_format($offtakeData['sheet1']['total_records']) }}</strong> transaksi
                </div>

                @if(($offtakeData['sheet1']['total_pages'] ?? 1) > 1)
                    <div class="offtake-pagination">
                        @php
                            $curRawPage = $offtakeData['sheet1']['page'];
                            $totRawPages = $offtakeData['sheet1']['total_pages'];
                            $queryRaw = request()->query();
                            unset($queryRaw['raw_page']);
                            $queryRaw['tab'] = 'sheet1';
                        @endphp

                        @if($curRawPage > 1)
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryRaw, ['raw_page' => $curRawPage - 1])) }}" class="page-link-btn">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        @endif

                        @for($p = max(1, $curRawPage - 2); $p <= min($totRawPages, $curRawPage + 2); $p++)
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryRaw, ['raw_page' => $p])) }}" class="page-link-btn {{ $p == $curRawPage ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($curRawPage < $totRawPages)
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryRaw, ['raw_page' => $curRawPage + 1])) }}" class="page-link-btn">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* Offtake Custom Styles */
.custom-offtake-wrapper {
    box-sizing: border-box;
}

.offtake-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.1rem;
    background: transparent;
    color: #475569;
    font-size: 0.88rem;
    font-weight: 700;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.offtake-nav-btn.active {
    background: #ffffff;
    color: var(--brand-primary);
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.offtake-nav-btn:not(.active):hover {
    background: rgba(255,255,255,0.6);
    color: #1e293b;
}

.offtake-nav-btn .badge-count {
    font-size: 0.72rem;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
    font-weight: 700;
}

.offtake-nav-btn.active .badge-count {
    background: rgba(15, 82, 186, 0.1);
    color: var(--brand-primary);
}

.btn-offtake-export {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.52rem 0.95rem;
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 9px;
    color: var(--text-heading);
    font-size: 0.84rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.15s ease;
}

.btn-offtake-export:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.offtake-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.offtake-card-header {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.offtake-card-title {
    font-size: 1.12rem;
    font-weight: 800;
    color: var(--text-heading);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.offtake-card-sub {
    font-size: 0.83rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.offtake-header-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.meta-pill {
    background: #ffffff;
    border: 1px solid var(--border-color);
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-size: 0.82rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.meta-pill-highlight {
    background: rgba(15, 82, 186, 0.06);
    border-color: rgba(15, 82, 186, 0.25);
    color: var(--brand-primary);
}

.meta-lbl {
    color: var(--text-muted);
}

.meta-val {
    color: var(--text-heading);
    font-weight: 800;
}

.meta-pill-highlight .meta-val {
    color: var(--brand-primary);
}

/* Table Styling */
.offtake-table-viewport {
    width: 100%;
    max-height: 620px;
    overflow-x: auto;
    overflow-y: auto;
    position: relative;
}

.offtake-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.85rem;
}

.offtake-table th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--brand-primary);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.78rem;
    letter-spacing: 0.4px;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.12);
    white-space: nowrap;
}

.offtake-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    white-space: nowrap;
}

.offtake-table tr:hover td {
    background: #f8fafc;
}

.tfoot-grand-total td {
    position: sticky;
    bottom: 0;
    z-index: 9;
    background: #f1f5f9 !important;
    border-top: 2px solid #cbd5e1 !important;
    border-bottom: none !important;
    color: #0f172a !important;
    padding: 0.95rem 1rem;
}

.sap-pill {
    font-family: monospace;
    font-weight: 700;
    font-size: 0.82rem;
    background: #f1f5f9;
    color: #475569;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.region-pill {
    font-weight: 700;
    font-size: 0.76rem;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    background: #e0f2fe;
    color: #0369a1;
}

.brand-tag {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    display: inline-block;
}

.brand-tag-dulux {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.brand-tag-catylac {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.offtake-table-footer {
    padding: 1rem 1.5rem;
    background: #ffffff;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.offtake-pagination {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.page-link-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    color: #475569;
    background: #ffffff;
    border: 1px solid var(--border-color);
    transition: all 0.15s ease;
}

.page-link-btn:hover {
    background: #f8fafc;
    color: var(--brand-primary);
    border-color: #cbd5e1;
}

.page-link-btn.active {
    background: var(--brand-primary);
    color: #ffffff;
    border-color: var(--brand-primary);
}
</style>

<script>
function switchOfftakeTab(tabId) {
    var paneSheet2 = document.getElementById('pane_offtake_sheet2');
    var paneSheet1 = document.getElementById('pane_offtake_sheet1');
    var btnSheet2 = document.getElementById('btn_offtake_tab_sheet2');
    var btnSheet1 = document.getElementById('btn_offtake_tab_sheet1');

    if (!paneSheet2 || !paneSheet1) return;

    if (tabId === 'sheet1') {
        paneSheet2.style.display = 'none';
        paneSheet1.style.display = 'block';
        btnSheet2.classList.remove('active');
        btnSheet1.classList.add('active');
    } else {
        paneSheet1.style.display = 'none';
        paneSheet2.style.display = 'block';
        btnSheet1.classList.remove('active');
        btnSheet2.classList.add('active');
    }

    // Persist tab parameter in current URL without reloading page
    var url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
}
</script>
