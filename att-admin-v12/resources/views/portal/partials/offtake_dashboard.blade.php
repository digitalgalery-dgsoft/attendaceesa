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
            <button type="button" class="offtake-nav-btn {{ ($activeTab ?? 'sheet2') === 'live' ? 'active' : '' }}" id="btn_offtake_tab_live" onclick="switchOfftakeTab('live')">
                <i class="fa-solid fa-list-check" style="font-size: 0.95rem; color: #2563eb;"></i>
                <span>Data Laporan Masuk</span>
                @if(isset($submissions) && $submissions->total() > 0)
                    <span class="badge-count" style="background: #2563eb; color: #ffffff;">{{ $submissions->total() }}</span>
                @endif
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Periode Indicator -->
            <div style="font-size: 0.84rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; background: #fff; padding: 0.5rem 0.9rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-calendar-days" style="color: var(--brand-primary);"></i>
                <span>Periode: <strong>{{ !empty($offtakeData['months']) ? (collect($offtakeData['months'])->first() . (count($offtakeData['months']) > 1 ? ' – ' . collect($offtakeData['months'])->last() : '')) : '-' }}</strong></span>
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
                        <strong class="meta-val">{{ number_format($offtakeData['sheet2']['total_stores'] ?? 0) }} Toko</strong>
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
                                    <td style="font-size: 0.84rem; color: #475569;">
                                        {{ $store['area'] ?: '-' }}
                                    </td>
                                    @foreach($offtakeData['months'] as $mKey => $mLabel)
                                        @php
                                            $mVol = (float)($store["m_{$mKey}"] ?? 0);
                                        @endphp
                                        <td style="text-align: right; font-size: 0.85rem; font-weight: {{ $mVol > 0 ? '600' : '400' }}; color: {{ $mVol > 0 ? '#0f172a' : '#94a3b8' }};">
                                            {{ $mVol > 0 ? number_format($mVol, 2) : '-' }}
                                        </td>
                                    @endforeach
                                    <td style="text-align: right; font-weight: 800; color: var(--brand-primary); font-size: 0.9rem; background: #f8fafc;">
                                        {{ number_format($totalVol, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ 6 + count($offtakeData['months']) }}" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem;"></i>
                                    <div>Tidak ada data penjualan toko untuk filter yang dipilih.</div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    @if(!empty($offtakeData['sheet2']['stores']))
                        <tfoot>
                            <tr class="offtake-grand-row">
                                <td colspan="5" style="text-align: right; font-weight: 800; font-size: 0.86rem; color: #0f172a; padding-right: 1.25rem;">
                                    GRAND TOTAL
                                </td>
                                @foreach($offtakeData['months'] as $mKey => $mLabel)
                                    @php
                                        $gMonthVol = (float)($offtakeData['sheet2']['grand_total']["m_{$mKey}"] ?? 0);
                                    @endphp
                                    <td style="text-align: right; font-weight: 800; font-size: 0.88rem; color: #0b3d88;">
                                        {{ number_format($gMonthVol, 2) }}
                                    </td>
                                @endforeach
                                <td style="text-align: right; font-weight: 900; font-size: 0.95rem; color: #0b3d88; background: #e0e7ff;">
                                    {{ number_format($offtakeData['sheet2']['grand_total']['total_vol'] ?? 0, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- Table Pagination & Navigation Bar -->
            <div class="offtake-table-footer">
                <div style="font-size: 0.85rem; color: #64748b;">
                    Menampilkan <strong>{{ $offtakeData['sheet2']['from'] }}</strong> – <strong>{{ $offtakeData['sheet2']['to'] }}</strong> dari <strong>{{ number_format($offtakeData['sheet2']['total_stores'] ?? 0) }}</strong> toko
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
                        <strong class="meta-val">{{ number_format($offtakeData['sheet1']['total_records'] ?? 0) }} Baris</strong>
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
                                        <span class="brand-tag {{ strtolower($r['brand'] ?? '') === 'dulux' ? 'brand-tag-dulux' : 'brand-tag-catylac' }}">
                                            {{ $r['brand'] ?: '-' }}
                                        </span>
                                    </td>
                                    <td style="font-size: 0.84rem; font-weight: 600; color: #1e293b;">
                                        {{ $r['sub_brand'] ?: '-' }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.82rem;">
                                        {{ !empty($r['kemasan_galon']) && $r['kemasan_galon'] !== '0' && $r['kemasan_galon'] !== '-' ? (str_contains($r['kemasan_galon'], 'L') ? $r['kemasan_galon'] : $r['kemasan_galon'] . ' L') : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ !empty($r['qty_galon']) ? number_format((float)$r['qty_galon']) : '-' }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.82rem;">
                                        {{ !empty($r['kemasan_pail']) && $r['kemasan_pail'] !== '0' && $r['kemasan_pail'] !== '-' ? (str_contains($r['kemasan_pail'], 'L') ? $r['kemasan_pail'] : $r['kemasan_pail'] . ' L') : '-' }}
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
                    Menampilkan <strong>{{ $offtakeData['sheet1']['from'] }}</strong> – <strong>{{ $offtakeData['sheet1']['to'] }}</strong> dari <strong>{{ number_format($offtakeData['sheet1']['total_records'] ?? 0) }}</strong> transaksi
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

    <!-- PANE 3: DATA LAPORAN MASUK TERKINI (LIVE SUBMISSIONS & APPROVAL) -->
    <div id="pane_offtake_live" class="offtake-pane" style="{{ ($activeTab ?? 'sheet2') === 'live' ? 'display: block;' : 'display: none;' }}">
        <div class="offtake-card">
            <div class="offtake-card-header">
                <div>
                    <h3 class="offtake-card-title">
                        <i class="fa-solid fa-clipboard-check" style="color: var(--brand-primary);"></i>
                        Data Laporan Masuk Terkini (Live Submissions & Approval)
                    </h3>
                    <div class="offtake-card-sub">
                        Daftar transaksi penjualan harian (Offtake) yang dikirim langsung oleh Promotor / SPG melalui aplikasi mobile untuk diverifikasi dan disetujui.
                    </div>
                </div>

                <div class="offtake-header-meta">
                    <div style="font-size: 0.78rem; font-weight: 600; color: #475569; display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <i class="fa-solid fa-arrows-left-right" style="color: var(--brand-primary);"></i> Geser untuk melihat kolom aksi
                    </div>
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Laporan:</span>
                        <strong class="meta-val">{{ isset($submissions) ? number_format($submissions->total()) : 0 }} Laporan</strong>
                    </div>
                </div>
            </div>

            @if(isset($submissions) && $submissions->isNotEmpty())
                <div class="offtake-table-viewport" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                    <table class="offtake-table" style="min-width: 1480px; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 140px;">Kode Laporan</th>
                                <th style="min-width: 130px;">Waktu Submit</th>
                                <th style="min-width: 170px;">Promotor / SPG</th>
                                <th style="min-width: 180px;">Nama Toko / Outlet</th>
                                <th style="min-width: 130px;">Area & RSM</th>
                                <th style="min-width: 150px;">Brand & Sub Brand</th>
                                <th style="min-width: 140px; text-align: right;">Kemasan & Qty</th>
                                <th style="min-width: 110px; text-align: right;">Total Volume</th>
                                <th style="text-align: center; width: 100px;">Radius GPS</th>
                                <th style="text-align: center; width: 120px;">Status</th>
                                <th style="text-align: center; width: 170px; min-width: 170px;" class="col-sticky-action">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $idx => $sub)
                                @php
                                    $valMap = [];
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
                                    }

                                    $store = $sub->workLocation?->name ?? 'Toko Tidak Terdaftar';
                                    $sap = $sub->workLocation?->code ?? ($sub->workLocation?->store_code ?? '-');
                                    $area = $sub->workLocation?->branch?->name ?? ($sub->workLocation?->area?->name ?? ($sub->workLocation?->area ?? '-'));
                                    $region = $sub->workLocation?->region ?? '-';
                                    $empName = $sub->employee?->full_name ?? ($sub->employee?->name ?? 'Petugas');
                                    $empNik = $sub->employee?->nik ?? ($sub->employee?->employee_no ?? '-');

                                    $rawBrand = trim((string)($valMap['brand'] ?? ($valMap['brand_cat'] ?? ($valMap['brand_rm_base'] ?? ''))));
                                    $rawSubBrand = trim((string)($valMap['sub_brand'] ?? ($valMap['subbrand'] ?? ($valMap['sub_brand_produk'] ?? ($valMap['sub_brand1'] ?? ($valMap['nama_produk'] ?? ''))))));
                                    if (empty($rawBrand)) {
                                        if (stripos($rawSubBrand, 'Catylac') !== false) {
                                            $rawBrand = 'Catylac';
                                        } elseif (stripos($rawSubBrand, 'Maxilite') !== false) {
                                            $rawBrand = 'Maxilite';
                                        } else {
                                            $rawBrand = 'Dulux';
                                        }
                                    }
                                    if (empty($rawSubBrand)) {
                                        $rawSubBrand = $rawBrand . ' Product';
                                    }

                                    $kemasanGalon = trim((string)($valMap['kemasan_galon'] ?? ''));
                                    $qtyGalon = (float)($valMap['qty_galon'] ?? ($valMap['kuantiti_galon_terjual_unit'] ?? ($valMap['kuantiti_galon_terjual'] ?? 0)));
                                    $kemasanPail = trim((string)($valMap['kemasan_pail'] ?? ''));
                                    $qtyPail = (float)($valMap['qty_pail'] ?? ($valMap['kuantiti_pail_terjual_unit'] ?? ($valMap['kuantiti_pail_terjual'] ?? 0)));

                                    $volLiter = (float)($valMap['total_volume_liter'] ?? ($valMap['volume_liter'] ?? 0));
                                    if ($volLiter <= 0) {
                                        $volG = (float)($valMap['volume_galon_l'] ?? 0);
                                        $volP = (float)($valMap['volume_pail_l'] ?? 0);
                                        if ($volG > 0 || $volP > 0) {
                                            $volLiter = $volG + $volP;
                                        } else {
                                            $gSize = 0;
                                            if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $kemasanGalon, $gm)) $gSize = (float)$gm[1];
                                            $pSize = 0;
                                            if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $kemasanPail, $pm)) $pSize = (float)$pm[1];
                                            $volLiter = ($gSize * $qtyGalon) + ($pSize * $qtyPail);
                                        }
                                    }

                                    $status = $sub->status ?? 'pending';
                                @endphp
                                <tr>
                                    <td style="text-align: center; color: #64748b; font-size: 0.8rem; font-weight: 700;">
                                        {{ $submissions->firstItem() + $idx }}
                                    </td>
                                    <td>
                                        <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" style="font-family: monospace; font-weight: 700; font-size: 0.82rem; color: #0F52BA; text-decoration: none; background: rgba(15, 82, 186, 0.08); padding: 3px 8px; border-radius: 6px; border: 1px solid rgba(15, 82, 186, 0.2); display: inline-block;">
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
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">
                                            {{ $store }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: #64748b;">
                                            SAP: <span class="sap-pill" style="font-size: 0.72rem; padding: 1px 6px;">{{ $sap }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b; font-size: 0.82rem;">
                                            {{ $area }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: #64748b;">
                                            <span class="region-pill" style="font-size: 0.7rem; padding: 1px 5px;">{{ $region }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="brand-tag {{ strtolower($rawBrand) === 'dulux' ? 'brand-tag-dulux' : 'brand-tag-catylac' }}" style="font-size: 0.7rem; padding: 1px 5px;">
                                                {{ $rawBrand }}
                                            </span>
                                        </div>
                                        <div style="font-size: 0.82rem; font-weight: 600; color: #1e293b; margin-top: 2px;">
                                            {{ $rawSubBrand }}
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-size: 0.8rem;">
                                        @if($qtyGalon > 0)
                                            <div><strong>{{ number_format($qtyGalon) }}</strong> Galon <span style="color: #64748b;">({{ $kemasanGalon ?: '2.5L' }})</span></div>
                                        @endif
                                        @if($qtyPail > 0)
                                            <div><strong>{{ number_format($qtyPail) }}</strong> Pail <span style="color: #64748b;">({{ $kemasanPail ?: '20L' }})</span></div>
                                        @endif
                                        @if($qtyGalon <= 0 && $qtyPail <= 0)
                                            <span style="color: #94a3b8;">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #0b3d88; font-size: 0.88rem;">
                                        {{ number_format($volLiter, 2) }} L
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
                                    <td style="text-align: center;" class="col-sticky-action">
                                        <div style="display: inline-flex; align-items: center; gap: 4px; justify-content: center; white-space: nowrap;">
                                            <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" class="btn-action-view" title="Lihat Detail & Bukti Nota">
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
                                                <button type="button" class="btn-action-quick-reject" onclick="openOfftakeRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Tolak Laporan">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            @elseif(in_array($status, ['approved', 'verified']))
                                                <button type="button" class="btn-action-quick-reject" onclick="openOfftakeRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Batalkan / Tolak Laporan">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            @elseif($status === 'rejected')
                                                <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Ubah status menjadi Disetujui?')">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn-action-quick-approve" title="Setujui Laporan">
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
                    <div style="padding: 1rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--border-color); border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                        {{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->links('portal.pagination') }}
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <i class="fa-solid fa-clipboard-question" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1; display: block;"></i>
                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-heading);">Tidak Ada Data Laporan Masuk</div>
                    <p style="font-size: 0.85rem; max-width: 420px; margin: 0.35rem auto 0;">
                        Belum ada laporan Offtake yang dikirimkan pada filter periode / area yang dipilih.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- REJECT MODAL -->
    <div id="offtake_reject_modal" class="portal-modal-backdrop" style="display: none;">
        <div class="portal-modal-box">
            <div class="portal-modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-xmark" style="color: #dc2626; font-size: 1.25rem;"></i>
                    <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a;">Tolak Laporan Masuk</h4>
                </div>
                <button type="button" onclick="closeOfftakeRejectModal()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form id="offtake_reject_form" method="POST">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div style="padding: 1.25rem 1.5rem;">
                    <p style="font-size: 0.88rem; color: #475569; margin-bottom: 0.75rem;">
                        Anda akan menolak laporan <strong id="offtake_reject_code" style="font-family: monospace; color: #0f172a;">-</strong>. Berikan alasan penolakan di bawah ini:
                    </p>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">Alasan Penolakan / Catatan:</label>
                    <textarea name="verification_notes" rows="3" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.6rem 0.75rem; font-size: 0.88rem; font-family: inherit; resize: vertical; box-sizing: border-box;" placeholder="Contoh: Bukti nota tidak jelas, produk salah, dll." required></textarea>
                </div>
                <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" onclick="closeOfftakeRejectModal()" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-size: 0.84rem; font-weight: 600; cursor: pointer; color: #475569;">Batal</button>
                    <button type="submit" style="padding: 0.5rem 1.15rem; border-radius: 8px; border: none; background: #dc2626; color: #fff; font-size: 0.84rem; font-weight: 700; cursor: pointer;">Konfirmasi Tolak</button>
                </div>
            </form>
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

/* Sticky Action Column */
.col-sticky-action {
    position: sticky;
    right: 0;
    z-index: 4;
    background: #ffffff;
    box-shadow: -3px 0 8px rgba(0, 0, 0, 0.08);
}
th.col-sticky-action {
    z-index: 12;
    background: var(--brand-primary) !important;
}
.offtake-table tr:hover td.col-sticky-action {
    background: #f8fafc !important;
}

/* Quick Action & Modal Styles */
.btn-action-view {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.35rem 0.65rem;
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
    padding: 0.35rem 0.65rem;
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
    padding: 0.35rem 0.65rem;
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

.portal-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.portal-modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 90%;
    max-width: 480px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    animation: modalScaleIn 0.2s ease-out;
}
@keyframes modalScaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.portal-modal-header {
    padding: 1.15rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>

<script>
function switchOfftakeTab(tabId) {
    var paneSheet2 = document.getElementById('pane_offtake_sheet2');
    var paneSheet1 = document.getElementById('pane_offtake_sheet1');
    var paneLive = document.getElementById('pane_offtake_live');
    var btnSheet2 = document.getElementById('btn_offtake_tab_sheet2');
    var btnSheet1 = document.getElementById('btn_offtake_tab_sheet1');
    var btnLive = document.getElementById('btn_offtake_tab_live');

    if (paneSheet2) paneSheet2.style.display = 'none';
    if (paneSheet1) paneSheet1.style.display = 'none';
    if (paneLive) paneLive.style.display = 'none';

    if (btnSheet2) btnSheet2.classList.remove('active');
    if (btnSheet1) btnSheet1.classList.remove('active');
    if (btnLive) btnLive.classList.remove('active');

    if (tabId === 'sheet1') {
        if (paneSheet1) paneSheet1.style.display = 'block';
        if (btnSheet1) btnSheet1.classList.add('active');
    } else if (tabId === 'live') {
        if (paneLive) paneLive.style.display = 'block';
        if (btnLive) btnLive.classList.add('active');
    } else {
        if (paneSheet2) paneSheet2.style.display = 'block';
        if (btnSheet2) btnSheet2.classList.add('active');
    }

    // Persist tab parameter in current URL without reloading page
    var url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
}

function openOfftakeRejectModal(subId, subCode) {
    var modal = document.getElementById('offtake_reject_modal');
    var codeEl = document.getElementById('offtake_reject_code');
    var form = document.getElementById('offtake_reject_form');
    if (!modal || !form) return;
    if (codeEl) codeEl.innerText = subCode;
    var baseRoute = "{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => ':id', 'p' => $tenantPrincipal->id]) }}";
    form.action = baseRoute.replace(':id', subId);
    modal.style.display = 'flex';
}

function closeOfftakeRejectModal() {
    var modal = document.getElementById('offtake_reject_modal');
    if (modal) modal.style.display = 'none';
}
</script>
