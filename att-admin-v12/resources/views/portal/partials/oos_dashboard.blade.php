{{-- PORTAL OUT OF STOCK (OOS) EXECUTIVE DASHBOARD (SUMMARY, REASON BREAKDOWN, WEEKLY PIVOT & RAW SUBMISSIONS) --}}
<div class="custom-oos-wrapper" style="margin-bottom: 2rem; width: 100%; max-width: 100%; min-width: 0;">

    <!-- TOP TOOLBAR: TAB NAVIGATION, CHANNEL FILTER & EXPORT BUTTONS -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="oos-main-nav" style="background: #e2e8f0; padding: 4px; border-radius: 12px; display: inline-flex; gap: 4px; flex-wrap: wrap;">
            <button type="button" class="oos-nav-btn {{ ($activeTab ?? 'summary') === 'summary' ? 'active' : '' }}" id="btn_oos_tab_summary" onclick="switchOosTab('summary')">
                <i class="fa-solid fa-chart-pie" style="font-size: 0.95rem;"></i>
                <span>Ringkasan Eksekutif & Alasan OOS</span>
                <span class="badge-count">{{ number_format($oosData['kpis']['total_stores'] ?? 0) }} Toko</span>
            </button>
            <button type="button" class="oos-nav-btn {{ ($activeTab ?? 'summary') === 'weekly' ? 'active' : '' }}" id="btn_oos_tab_weekly" onclick="switchOosTab('weekly')">
                <i class="fa-solid fa-calendar-week" style="font-size: 0.95rem;"></i>
                <span>Rekapitulasi OOS Mingguan Toko</span>
                <span class="badge-count">{{ number_format($oosData['weekly']['total_rows'] ?? 0) }} Baris</span>
            </button>
            <button type="button" class="oos-nav-btn {{ ($activeTab ?? 'summary') === 'raw' ? 'active' : '' }}" id="btn_oos_tab_raw" onclick="switchOosTab('raw')">
                <i class="fa-solid fa-list-check" style="font-size: 0.95rem;"></i>
                <span>Raw Data Submissions</span>
                <span class="badge-count">{{ number_format($oosData['submissions']['total'] ?? 0) }} Baris</span>
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Channel Filter (LSO / SSO / All) -->
            <div style="display: inline-flex; align-items: center; background: #fff; padding: 3px 6px; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); gap: 4px;">
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); padding-left: 6px;">Channel:</span>
                @php $curChan = request()->query('channel', ''); @endphp
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'channel' => '', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ empty($curChan) ? 'active' : '' }}">Semua</a>
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'channel' => 'LSO', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ $curChan === 'LSO' ? 'active' : '' }}" title="Large Store Outlet (Modern Trade: Depo Bangunan, Mitra 10, ACE, dll)">LSO (Modern Trade)</a>
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'channel' => 'SSO', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ $curChan === 'SSO' ? 'active' : '' }}" title="Small Store Outlet (Traditional Trade / Retail)">SSO (Retail)</a>
            </div>

            <!-- Periode Indicator -->
            <div style="font-size: 0.84rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; background: #fff; padding: 0.5rem 0.9rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-calendar-days" style="color: var(--brand-primary);"></i>
                <span>Periode: <strong>{{ reset($oosData['months']) }} – {{ end($oosData['months']) }}</strong></span>
            </div>

            <!-- Export Buttons -->
            <div style="display: inline-flex; gap: 6px;">
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'oos_summary', 'p' => $tenantPrincipal->id])) }}" class="btn-oos-export" title="Download Ringkasan & Alasan OOS">
                    <i class="fa-solid fa-file-excel" style="color: #107c41;"></i>
                    <span>Export Ringkasan</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'oos_weekly', 'p' => $tenantPrincipal->id])) }}" class="btn-oos-export" style="background: #f0fdf4;" title="Download Rekapitulasi OOS Mingguan">
                    <i class="fa-solid fa-file-waveform" style="color: #16a34a;"></i>
                    <span>Export Rekap Mingguan</span>
                </a>
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'oos_raw', 'p' => $tenantPrincipal->id])) }}" class="btn-oos-export" style="background: #f8fafc;" title="Download Raw Data Submissions">
                    <i class="fa-solid fa-file-csv" style="color: #0284c7;"></i>
                    <span>Export Raw Data</span>
                </a>
            </div>
        </div>
    </div>

    <!-- PANE 1: RINGKASAN EKSEKUTIF & ALASAN OOS -->
    <div id="pane_oos_summary" class="oos-pane" style="{{ ($activeTab ?? 'summary') === 'summary' ? 'display: block;' : 'display: none;' }}">
        
        <!-- KPI METRICS ROW -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <!-- KPI 1: Total Toko Aktif / Input -->
            <div class="oos-kpi-card" style="border-left: 4px solid #3b82f6;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Toko Terpantau</div>
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-heading); margin-top: 0.35rem;">
                    {{ number_format($oosData['kpis']['total_stores'] ?? 0) }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Toko</span>
                </div>
                <div style="font-size: 0.78rem; color: #16a34a; margin-top: 0.25rem; display: flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-check-circle"></i> Seluruh gerai LSO & SSO aktif
                </div>
            </div>

            <!-- KPI 2: Total Kasus OOS Riil -->
            <div class="oos-kpi-card" style="border-left: 4px solid #ef4444;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Kasus OOS Riil</div>
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #ef4444; margin-top: 0.35rem;">
                    {{ number_format($oosData['kpis']['total_oos_cases'] ?? 0) }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Kasus</span>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Insiden barang kosong terlapor
                </div>
            </div>

            <!-- KPI 3: Toko Bebas OOS (No OOS Rate) -->
            <div class="oos-kpi-card" style="border-left: 4px solid #10b981;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Toko Bebas OOS (Stok Lengkap)</div>
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; margin-top: 0.35rem;">
                    {{ number_format($oosData['kpis']['no_oos_percentage'] ?? 0, 1) }}%
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.25rem;">
                    {{ number_format($oosData['kpis']['no_oos_stores'] ?? 0) }} Toko berstatus No OOS
                </div>
            </div>

            <!-- KPI 4: Total Submission Laporan -->
            <div class="oos-kpi-card" style="border-left: 4px solid #8b5cf6;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Submission Laporan</div>
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-heading); margin-top: 0.35rem;">
                    {{ number_format($oosData['kpis']['total_submissions'] ?? 0) }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Laporan</span>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Total baris data checklist OOS
                </div>
            </div>
        </div>

        <!-- REASON BREAKDOWN CARD -->
        <div class="oos-card">
            <div class="oos-card-header">
                <div>
                    <h3 class="oos-card-title">
                        <i class="fa-solid fa-chart-column" style="color: var(--brand-primary);"></i>
                        Distribusi & Analisis Alasan Out of Stock (OOS)
                    </h3>
                    <div class="oos-card-sub">
                        Proporsi dan rincian penyebab barang kosong berdasarkan laporan petugas lapangan di gerai mitra.
                    </div>
                </div>

                <div class="oos-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Kategori Alasan:</span>
                        <strong class="meta-val">{{ count($oosData['reasons'] ?? []) }} Kategori</strong>
                    </div>
                </div>
            </div>

            <div class="oos-table-viewport">
                <table class="oos-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">NO</th>
                            <th style="min-width: 340px;">PENYEBAB / ALASAN OUT OF STOCK (OOS)</th>
                            <th style="width: 140px; text-align: right;">JUMLAH TOKO</th>
                            <th style="width: 130px; text-align: right;">% SHARE TOKO</th>
                            <th style="width: 150px; text-align: right;">TOTAL INSIDEN OOS</th>
                            <th style="min-width: 220px;">VISUALISASI PROPORSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($oosData['reasons']))
                            @foreach($oosData['reasons'] as $idx => $r)
                                @php
                                    $isNoOos = str_contains(strtolower($r['reason']), 'no oos') || str_contains(strtolower($r['reason']), 'no. oos');
                                    $barColor = $isNoOos ? '#10b981' : ($idx === 0 ? '#ef4444' : ($idx === 1 ? '#f59e0b' : '#3b82f6'));
                                @endphp
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">{{ $idx + 1 }}</td>
                                    <td>
                                        <div style="font-weight: 700; color: {{ $isNoOos ? '#059669' : 'var(--text-heading)' }}; display: flex; align-items: center; gap: 8px;">
                                            @if($isNoOos)
                                                <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
                                            @else
                                                <i class="fa-solid fa-circle-exclamation" style="color: {{ $barColor }};"></i>
                                            @endif
                                            <span>{{ $r['reason'] }}</span>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700;">
                                        {{ number_format($r['store_count']) }} Toko
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="pct-pill {{ $isNoOos ? 'pct-pill-green' : ($r['percentage'] > 15 ? 'pct-pill-red' : 'pct-pill-neutral') }}">
                                            {{ number_format($r['percentage'], 1) }}%
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: {{ $isNoOos ? '#059669' : '#dc2626' }};">
                                        {{ number_format($r['incident_count']) }} Kasus
                                    </td>
                                    <td>
                                        <div style="background: #e2e8f0; height: 10px; border-radius: 9999px; overflow: hidden; width: 100%;">
                                            <div style="background: {{ $barColor }}; width: {{ min(100, max(2, $r['percentage'])) }}%; height: 100%; border-radius: 9999px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                    Tidak ada data alasan OOS untuk filter periode ini.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PANE 2: REKAPITULASI OOS MINGGUAN TOKO (WEEKLY PIVOT TABLE) -->
    <div id="pane_oos_weekly" class="oos-pane" style="{{ ($activeTab ?? 'summary') === 'weekly' ? 'display: block;' : 'display: none;' }}">
        <div class="oos-card">
            <div class="oos-card-header">
                <div>
                    <h3 class="oos-card-title">
                        <i class="fa-solid fa-calendar-week" style="color: var(--brand-primary);"></i>
                        Tabel Rekapitulasi OOS Mingguan Per Toko & Produk
                    </h3>
                    <div class="oos-card-sub">
                        Rekapitulasi frekuensi barang kosong berdasarkan toko, produk, base/warna, kemasan, dan minggu pelaporan (Week).
                    </div>
                </div>

                <div class="oos-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Baris:</span>
                        <strong class="meta-val">{{ number_format($oosData['weekly']['total_rows'] ?? 0) }} Baris</strong>
                    </div>
                    <div class="meta-pill meta-pill-highlight">
                        <span class="meta-lbl">Total Kasus OOS:</span>
                        <strong class="meta-val">{{ number_format($oosData['weekly']['grand_total_cases'] ?? 0) }} Kasus</strong>
                    </div>
                </div>
            </div>

            <!-- Table Viewport with Horizontal Scroll -->
            <div class="oos-table-viewport">
                <table class="oos-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th style="width: 80px; text-align: center;">CHANNEL</th>
                            <th style="width: 90px;">SAP</th>
                            <th style="min-width: 220px;">NAMA TOKO / STORE</th>
                            <th style="width: 80px; text-align: center;">REGION</th>
                            <th style="width: 120px;">AREA</th>
                            <th style="min-width: 180px;">NAMA PRODUK</th>
                            <th style="width: 120px;">BASE / COLOR</th>
                            <th style="width: 100px; text-align: center;">KEMASAN</th>
                            <th style="min-width: 240px;">ALASAN OOS</th>
                            @foreach($oosData['weeks'] as $wk)
                                <th style="width: 80px; text-align: center; background: #0F52BA; color: #fff;">W{{ $wk }}</th>
                            @endforeach
                            <th style="width: 110px; text-align: center; background: #0b3d88 !important; color: #fff !important;">GRAND TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($oosData['weekly']['rows']))
                            @foreach($oosData['weekly']['rows'] as $idx => $row)
                                @php
                                    $isNoOosRow = str_contains(strtolower($row['alasan_oos']), 'no oos') || str_contains(strtolower($row['alasan_oos']), 'no. oos') || str_contains(strtolower($row['produk']), 'no oos');
                                @endphp
                                <tr style="{{ $isNoOosRow ? 'background: #f0fdf4;' : '' }}">
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">
                                        {{ $oosData['weekly']['from'] + $idx }}
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="chan-pill chan-pill-{{ strtolower($row['channel']) }}">
                                            {{ $row['channel'] }}
                                        </span>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">
                                        {{ $row['sap'] ?: '-' }}
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading);">
                                            {{ $row['store_name'] }}
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="region-pill">{{ $row['region'] ?: '-' }}</span>
                                    </td>
                                    <td>{{ $row['area'] ?: '-' }}</td>
                                    <td>
                                        <div style="font-weight: 600; color: {{ $isNoOosRow ? '#15803d' : '#1e3a8a' }};">
                                            {{ $row['produk'] }}
                                        </div>
                                    </td>
                                    <td style="color: var(--text-muted);">{{ $row['base_color'] ?: '-' }}</td>
                                    <td style="text-align: center; font-size: 0.82rem;">{{ $row['kemasan_size'] ?: '-' }}</td>
                                    <td>
                                        <div style="font-size: 0.82rem; color: {{ $isNoOosRow ? '#15803d' : '#b91c1c' }}; font-weight: 600;">
                                            {{ $row['alasan_oos'] }}
                                        </div>
                                    </td>
                                    @foreach($oosData['weeks'] as $wk)
                                        @php $cnt = (int)($row['weeks'][$wk] ?? 0); @endphp
                                        <td style="text-align: center; font-weight: {{ $cnt > 0 ? '700' : 'normal' }}; color: {{ $cnt > 0 ? ($isNoOosRow ? '#15803d' : '#dc2626') : '#94a3b8' }};">
                                            {{ $cnt > 0 ? $cnt : '-' }}
                                        </td>
                                    @endforeach
                                    <td style="text-align: center; font-weight: 800; background: rgba(15, 82, 186, 0.05); color: {{ $isNoOosRow ? '#15803d' : '#b91c1c' }};">
                                        {{ number_format($row['total_cases']) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ 11 + count($oosData['weeks']) }}" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                    Tidak ada data rekapitulasi mingguan untuk filter periode ini.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Weekly Pivot Pagination -->
            @if($oosData['weekly']['total_pages'] > 1)
                <div class="stock-pagination-wrapper">
                    <div style="font-size: 0.84rem; color: var(--text-muted);">
                        Menampilkan <strong>{{ $oosData['weekly']['from'] }}</strong> s/d <strong>{{ $oosData['weekly']['to'] }}</strong> dari <strong>{{ number_format($oosData['weekly']['total_rows']) }}</strong> baris rekap
                    </div>
                    <div class="custom-pager">
                        @if($oosData['weekly']['page'] > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => $oosData['weekly']['page'] - 1, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" class="page-link-btn">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        @endif

                        <span class="page-indicator">Hal {{ $oosData['weekly']['page'] }} dari {{ $oosData['weekly']['total_pages'] }}</span>

                        @if($oosData['weekly']['page'] < $oosData['weekly']['total_pages'])
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => $oosData['weekly']['page'] + 1, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" class="page-link-btn">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- PANE 3: RAW DATA SUBMISSIONS (16 KOLOM) -->
    <div id="pane_oos_raw" class="oos-pane" style="{{ ($activeTab ?? 'summary') === 'raw' ? 'display: block;' : 'display: none;' }}">
        <div class="oos-card">
            <div class="oos-card-header">
                <div>
                    <h3 class="oos-card-title">
                        <i class="fa-solid fa-list-check" style="color: var(--brand-primary);"></i>
                        Data Mentah Pelaporan Out of Stock (Submissions)
                    </h3>
                    <div class="oos-card-sub">
                        16 kolom data mentah seluruh submission checklist OOS yang diinput oleh petugas SPG/MD di lapangan.
                    </div>
                </div>

                <div class="oos-header-meta">
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Baris:</span>
                        <strong class="meta-val">{{ number_format($oosData['submissions']['total'] ?? 0) }} Baris</strong>
                    </div>
                </div>
            </div>

            <div class="oos-table-viewport">
                <table class="oos-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th style="width: 140px;">SUBMISSION DATE</th>
                            <th style="width: 110px;">TGL OOS</th>
                            <th style="width: 70px; text-align: center;">WEEK</th>
                            <th style="width: 80px; text-align: center;">CHANNEL</th>
                            <th style="width: 70px; text-align: center;">REGION</th>
                            <th style="width: 120px;">AREA</th>
                            <th style="width: 130px;">RSM AREA</th>
                            <th style="width: 110px;">ACCOUNT</th>
                            <th style="width: 90px;">SAP</th>
                            <th style="min-width: 220px;">NAMA TOKO</th>
                            <th style="min-width: 180px;">NAMA PRODUK</th>
                            <th style="width: 110px;">BASE / COLOR</th>
                            <th style="width: 100px; text-align: center;">KEMASAN / SIZE</th>
                            <th style="width: 90px; text-align: center;">LAMA OOS (HARI)</th>
                            <th style="width: 90px; text-align: center;">SARAN QTY</th>
                            <th style="min-width: 240px;">ALASAN OOS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($oosData['submissions']['rows']))
                            @foreach($oosData['submissions']['rows'] as $idx => $r)
                                @php
                                    $isNoOos = str_contains(strtolower($r['alasan_oos']), 'no oos') || str_contains(strtolower($r['alasan_oos']), 'no. oos') || str_contains(strtolower($r['produk']), 'no oos');
                                @endphp
                                <tr style="{{ $isNoOos ? 'background: #f0fdf4;' : '' }}">
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 700;">
                                        {{ $oosData['submissions']['from'] + $idx }}
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading);">
                                            {{ !empty($r['submission_date']) ? (str_contains($r['submission_date'], '/') ? $r['submission_date'] : \Carbon\Carbon::parse($r['submission_date'])->format('Y-m-d H:i')) : '-' }}
                                        </div>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.82rem;">{{ $r['tanggal_oos'] ?: '-' }}</td>
                                    <td style="text-align: center; font-weight: 700; color: var(--brand-primary);">{{ $r['week'] }}</td>
                                    <td style="text-align: center;">
                                        <span class="chan-pill chan-pill-{{ strtolower($r['channel']) }}">
                                            {{ $r['channel'] }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;"><span class="region-pill">{{ $r['region'] ?: '-' }}</span></td>
                                    <td>{{ $r['area'] ?: '-' }}</td>
                                    <td style="color: var(--text-muted); font-size: 0.82rem;">{{ $r['rsm_area'] ?: '-' }}</td>
                                    <td style="font-size: 0.82rem; font-weight: 600;">{{ $r['account'] ?: '-' }}</td>
                                    <td style="font-family: monospace; font-size: 0.85rem;">{{ $r['sap'] ?: '-' }}</td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading);">{{ $r['store_name'] }}</div>
                                    </td>
                                    <td style="font-weight: 600; color: {{ $isNoOos ? '#15803d' : '#1e3a8a' }};">{{ $r['produk'] }}</td>
                                    <td style="color: var(--text-muted); font-size: 0.82rem;">{{ $r['base_color'] ?: '-' }}</td>
                                    <td style="text-align: center; font-size: 0.82rem;">{{ $r['kemasan_size'] ?: '-' }}</td>
                                    <td style="text-align: center; font-weight: 700; color: {{ $r['lama_oos_hari'] > 0 ? '#dc2626' : '#94a3b8' }};">
                                        {{ $r['lama_oos_hari'] > 0 ? $r['lama_oos_hari'] : '-' }}
                                    </td>
                                    <td style="text-align: center; font-weight: 700;">{{ $r['saran_qty_order'] > 0 ? $r['saran_qty_order'] : '-' }}</td>
                                    <td>
                                        <div style="font-size: 0.82rem; color: {{ $isNoOos ? '#15803d' : '#b91c1c' }}; font-weight: 600;">
                                            {{ $r['alasan_oos'] }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="17" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                    Tidak ada data submission untuk filter periode ini.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Submissions Pagination -->
            @if($oosData['submissions']['total_pages'] > 1)
                <div class="stock-pagination-wrapper">
                    <div style="font-size: 0.84rem; color: var(--text-muted);">
                        Menampilkan <strong>{{ $oosData['submissions']['from'] }}</strong> s/d <strong>{{ $oosData['submissions']['to'] }}</strong> dari <strong>{{ number_format($oosData['submissions']['total']) }}</strong> data submission
                    </div>
                    <div class="custom-pager">
                        @if($oosData['submissions']['page'] > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $oosData['submissions']['page'] - 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="page-link-btn">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        @endif

                        <span class="page-indicator">Hal {{ $oosData['submissions']['page'] }} dari {{ $oosData['submissions']['total_pages'] }}</span>

                        @if($oosData['submissions']['page'] < $oosData['submissions']['total_pages'])
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $oosData['submissions']['page'] + 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="page-link-btn">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

<!-- STYLES FOR OOS EXECUTIVE DASHBOARD -->
<style>
.oos-nav-btn {
    border: none;
    background: transparent;
    padding: 0.55rem 1.15rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.86rem;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.oos-nav-btn:hover {
    color: #0F52BA;
    background: rgba(255, 255, 255, 0.6);
}
.oos-nav-btn.active {
    background: #ffffff;
    color: #0F52BA;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.btn-chan-filter {
    text-decoration: none;
    font-size: 0.76rem;
    font-weight: 700;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    color: #64748b;
    background: transparent;
    transition: all 0.15s ease;
}
.btn-chan-filter:hover {
    background: #f1f5f9;
    color: #0F52BA;
}
.btn-chan-filter.active {
    background: #0F52BA;
    color: #ffffff !important;
}
.btn-oos-export {
    background: #ffffff;
    border: 1px solid var(--border-color);
    padding: 0.5rem 0.85rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-heading);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}
.btn-oos-export:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.oos-kpi-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}
.oos-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.oos-card-header {
    background: #f8fafc;
    padding: 1.15rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.oos-card-title {
    font-size: 1.08rem;
    font-weight: 800;
    color: var(--text-heading);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.oos-card-sub {
    font-size: 0.82rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}
.oos-header-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.oos-table-viewport {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.oos-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    text-align: left;
    white-space: nowrap;
}
.oos-table th {
    background: #f1f5f9;
    color: var(--text-heading);
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.75rem 0.9rem;
    border-bottom: 2px solid var(--border-color);
    border-right: 1px solid var(--border-color);
}
.oos-table td {
    padding: 0.7rem 0.9rem;
    border-bottom: 1px solid var(--border-color);
    border-right: 1px solid rgba(0,0,0,0.04);
    color: var(--text-body);
}
.oos-table tbody tr:hover {
    background: #f8fafc;
}
.pct-pill {
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    font-size: 0.76rem;
    font-weight: 700;
}
.pct-pill-green {
    background: #dcfce7;
    color: #15803d;
}
.pct-pill-red {
    background: #fee2e2;
    color: #b91c1c;
}
.pct-pill-neutral {
    background: #f1f5f9;
    color: #475569;
}
.chan-pill {
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
}
.chan-pill-lso {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.chan-pill-sso {
    background: #fdf4ff;
    color: #86198f;
    border: 1px solid #f5d0fe;
}
</style>

<script>
function switchOosTab(tabId) {
    document.getElementById('pane_oos_summary').style.display = 'none';
    document.getElementById('pane_oos_weekly').style.display = 'none';
    document.getElementById('pane_oos_raw').style.display = 'none';

    document.getElementById('btn_oos_tab_summary').classList.remove('active');
    document.getElementById('btn_oos_tab_weekly').classList.remove('active');
    document.getElementById('btn_oos_tab_raw').classList.remove('active');

    if (tabId === 'weekly') {
        document.getElementById('pane_oos_weekly').style.display = 'block';
        document.getElementById('btn_oos_tab_weekly').classList.add('active');
    } else if (tabId === 'raw') {
        document.getElementById('pane_oos_raw').style.display = 'block';
        document.getElementById('btn_oos_tab_raw').classList.add('active');
    } else {
        document.getElementById('pane_oos_summary').style.display = 'block';
        document.getElementById('btn_oos_tab_summary').classList.add('active');
    }
}
</script>
