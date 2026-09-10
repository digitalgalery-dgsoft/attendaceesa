{{-- PORTAL OUT OF STOCK (OOS) EXECUTIVE DASHBOARD (SUMMARY, REASON BREAKDOWN, WEEKLY PIVOT & RAW SUBMISSIONS) --}}
<div class="custom-oos-wrapper" style="margin-bottom: 2rem; width: 100%; max-width: 100%; min-width: 0;">

    <!-- TOP TOOLBAR: TAB NAVIGATION, CHANNEL FILTER, NO OOS TOGGLE & EXPORT BUTTONS -->
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
            <button type="button" class="oos-nav-btn {{ ($activeTab ?? 'summary') === 'live' ? 'active' : '' }}" id="btn_oos_tab_live" onclick="switchOosTab('live')">
                <i class="fa-solid fa-inbox" style="font-size: 0.95rem; color: #2563eb;"></i>
                <span>Data Laporan Masuk</span>
                @if(isset($submissions) && $submissions->total() > 0)
                    <span class="badge-count" style="background: #2563eb; color: #ffffff;">{{ $submissions->total() }}</span>
                @elseif(isset($liveSubmissionsCount) && $liveSubmissionsCount > 0)
                    <span class="badge-count" style="background: #2563eb; color: #ffffff;">{{ $liveSubmissionsCount }}</span>
                @endif
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Channel Filter (LSO / SSO / All) -->
            <div style="display: inline-flex; align-items: center; background: #fff; padding: 3px 6px; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); gap: 4px;">
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); padding-left: 6px;">Channel:</span>
                @php $curChan = request()->query('channel', ''); @endphp
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'channel' => '', 'tab' => $activeTab ?? 'summary', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ empty($curChan) ? 'active' : '' }}">Semua</a>
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'channel' => 'LSO', 'tab' => $activeTab ?? 'summary', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ $curChan === 'LSO' ? 'active' : '' }}" title="Large Store Outlet (Modern Trade: Depo Bangunan, Mitra 10, ACE, dll)">LSO (Modern)</a>
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'channel' => 'SSO', 'tab' => $activeTab ?? 'summary', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ $curChan === 'SSO' ? 'active' : '' }}" title="Small Store Outlet (Traditional Trade / Retail)">SSO (Retail)</a>
            </div>

            <!-- Toggle Filter Produk "No OOS" -->
            <div style="display: inline-flex; align-items: center; background: #fff; padding: 3px 6px; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); gap: 4px;">
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); padding-left: 6px;">Status No OOS:</span>
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'show_no_oos' => 0, 'tab' => $activeTab ?? 'summary', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ empty($showNoOos) ? 'active' : '' }}" title="Sembunyikan produk dengan entri 'No OOS' (Hanya tampilkan barang kosong riil)">
                    <i class="fa-solid fa-eye-slash" style="font-size: 0.72rem;"></i> Sembunyikan (Default)
                </a>
                <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'show_no_oos' => 1, 'tab' => $activeTab ?? 'summary', 'p' => $tenantPrincipal->id])) }}" 
                   class="btn-chan-filter {{ !empty($showNoOos) ? 'active' : '' }}" title="Tampilkan seluruh data termasuk entri 'No OOS'">
                    <i class="fa-solid fa-eye" style="font-size: 0.72rem;"></i> Tampilkan Semua
                </a>
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
                    Total baris checklist OOS terdata
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
                                        <div style="font-weight: 700; color: var(--text-heading); display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                            <span>{{ $row['store_name'] }}</span>
                                            @if(!empty($row['is_live']))
                                                <span style="background: #2563eb; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 2px;">⚡ LIVE</span>
                                            @endif
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

            <!-- Beautiful Weekly Pivot Pagination Bar -->
            @if(($oosData['weekly']['total_pages'] ?? 1) > 1)
                @php
                    $curPage = (int)($oosData['weekly']['page'] ?? 1);
                    $totPages = (int)($oosData['weekly']['total_pages'] ?? 1);
                @endphp
                <div class="oos-pagination-bar">
                    <div class="oos-pagination-info">
                        Menampilkan <strong>{{ $oosData['weekly']['from'] }}</strong> s/d <strong>{{ $oosData['weekly']['to'] }}</strong> dari <strong>{{ number_format($oosData['weekly']['total_rows']) }}</strong> baris rekap (Hal <strong>{{ $curPage }}</strong> dari <strong>{{ $totPages }}</strong>)
                    </div>
                    <div class="oos-pagination-controls">
                        {{-- First Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => 1, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}" title="Halaman Pertama">
                            <i class="fa-solid fa-angles-left"></i>
                        </a>

                        {{-- Prev Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => max(1, $curPage - 1), 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </a>

                        {{-- Numeric Pages --}}
                        @php
                            $startP = max(1, $curPage - 2);
                            $endP = min($totPages, $curPage + 2);
                        @endphp

                        @if($startP > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => 1, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" class="oos-page-btn">1</a>
                            @if($startP > 2)
                                <span class="oos-page-dots">&hellip;</span>
                            @endif
                        @endif

                        @for($p = $startP; $p <= $endP; $p++)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => $p, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" 
                               class="oos-page-btn {{ $p === $curPage ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($endP < $totPages)
                            @if($endP < $totPages - 1)
                                <span class="oos-page-dots">&hellip;</span>
                            @endif
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => $totPages, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" class="oos-page-btn">{{ $totPages }}</a>
                        @endif

                        {{-- Next Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => min($totPages, $curPage + 1), 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>

                        {{-- Last Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'weekly_page' => $totPages, 'tab' => 'weekly', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}" title="Halaman Terakhir">
                            <i class="fa-solid fa-angles-right"></i>
                        </a>
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
                    @if(empty($showNoOos))
                        <div class="meta-pill" style="background: #eff6ff; color: #1e40af; border-color: #bfdbfe;">
                            <i class="fa-solid fa-filter" style="color: #2563eb;"></i>
                            <span class="meta-lbl" style="color: #1e40af;">Filter:</span>
                            <strong class="meta-val" style="color: #1e40af;">Kasus OOS Saja (Produk "No OOS" Disembunyikan)</strong>
                        </div>
                    @else
                        <div class="meta-pill" style="background: #f0fdf4; color: #15803d; border-color: #bbf7d0;">
                            <i class="fa-solid fa-circle-check" style="color: #16a34a;"></i>
                            <span class="meta-lbl" style="color: #15803d;">Filter:</span>
                            <strong class="meta-val" style="color: #15803d;">Menampilkan Seluruh Data Termasuk "No OOS"</strong>
                        </div>
                    @endif
                    <div class="meta-pill">
                        <span class="meta-lbl">Total Baris Terfilter:</span>
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
                                        <div style="font-weight: 700; color: var(--text-heading); display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                            <span>{{ !empty($r['submission_date']) ? (str_contains($r['submission_date'], '/') ? $r['submission_date'] : \Carbon\Carbon::parse($r['submission_date'])->format('Y-m-d H:i')) : '-' }}</span>
                                            @if(!empty($r['is_live']))
                                                <span style="background: #2563eb; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 2px;">⚡ LIVE</span>
                                            @endif
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

            <!-- Beautiful Submissions Pagination Bar -->
            @if(($oosData['submissions']['total_pages'] ?? 1) > 1)
                @php
                    $curPage = (int)($oosData['submissions']['page'] ?? 1);
                    $totPages = (int)($oosData['submissions']['total_pages'] ?? 1);
                @endphp
                <div class="oos-pagination-bar">
                    <div class="oos-pagination-info">
                        Menampilkan <strong>{{ $oosData['submissions']['from'] }}</strong> s/d <strong>{{ $oosData['submissions']['to'] }}</strong> dari <strong>{{ number_format($oosData['submissions']['total']) }}</strong> data submission (Hal <strong>{{ $curPage }}</strong> dari <strong>{{ $totPages }}</strong>)
                    </div>
                    <div class="oos-pagination-controls">
                        {{-- First Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}" title="Halaman Pertama">
                            <i class="fa-solid fa-angles-left"></i>
                        </a>

                        {{-- Prev Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => max(1, $curPage - 1), 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </a>

                        {{-- Numeric Pages --}}
                        @php
                            $startP = max(1, $curPage - 2);
                            $endP = min($totPages, $curPage + 2);
                        @endphp

                        @if($startP > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="oos-page-btn">1</a>
                            @if($startP > 2)
                                <span class="oos-page-dots">&hellip;</span>
                            @endif
                        @endif

                        @for($p = $startP; $p <= $endP; $p++)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $p, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                               class="oos-page-btn {{ $p === $curPage ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($endP < $totPages)
                            @if($endP < $totPages - 1)
                                <span class="oos-page-dots">&hellip;</span>
                            @endif
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $totPages, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="oos-page-btn">{{ $totPages }}</a>
                        @endif

                        {{-- Next Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => min($totPages, $curPage + 1), 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>

                        {{-- Last Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $totPages, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="oos-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}" title="Halaman Terakhir">
                            <i class="fa-solid fa-angles-right"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- PANE 4: DATA LAPORAN MASUK TERKINI (LIVE SUBMISSIONS & APPROVAL) -->
    <div id="pane_oos_live" class="oos-pane" style="{{ ($activeTab ?? 'summary') === 'live' ? 'display: block;' : 'display: none;' }}">
        <div class="oos-card">
            <div class="oos-card-header">
                <div>
                    <h3 class="oos-card-title">
                        <i class="fa-solid fa-clipboard-check" style="color: #0b3d88;"></i>
                        Data Laporan Out of Stock (OOS) Masuk Terkini (Live Submissions & Approval)
                    </h3>
                    <div class="oos-card-sub">
                        Daftar transaksi monitoring Out of Stock yang dikirimkan langsung oleh Promotor / SPG melalui aplikasi mobile untuk diverifikasi dan disetujui.
                    </div>
                </div>

                <div class="oos-header-meta">
                    <div style="font-size: 0.78rem; font-weight: 600; color: #475569; display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <i class="fa-solid fa-arrows-left-right" style="color: #0b3d88;"></i> Geser tabel untuk melihat seluruh parameter & kolom aksi
                    </div>
                    <div class="meta-pill" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                        <span class="meta-lbl" style="color: #1e40af;">Total Laporan:</span>
                        <strong class="meta-val" style="color: #0b3d88;">{{ isset($submissions) ? number_format($submissions->total()) : (isset($liveSubmissionsCount) ? number_format($liveSubmissionsCount) : 0) }} Laporan</strong>
                    </div>
                </div>
            </div>

            @if(isset($submissions) && $submissions->isNotEmpty())
                <div class="oos-table-viewport" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                    <table class="oos-table" style="min-width: 1750px; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 140px;">Kode Laporan</th>
                                <th style="min-width: 130px;">Waktu Submit</th>
                                <th style="min-width: 170px;">Promotor / SPG</th>
                                <th style="min-width: 180px;">Nama Toko / Outlet</th>
                                <th style="min-width: 130px;">Area & RSM</th>
                                <th style="min-width: 160px;">Produk OOS</th>
                                <th style="min-width: 120px;">Base / Kategori</th>
                                <th style="min-width: 120px;">Kemasan</th>
                                <th style="min-width: 90px; text-align: center;">Lama OOS</th>
                                <th style="min-width: 100px; text-align: center;">Saran Order</th>
                                <th style="min-width: 180px;">Alasan Out of Stock (OOS)</th>
                                <th style="text-align: center; width: 100px;">Radius GPS</th>
                                <th style="text-align: center; width: 120px;">Status</th>
                                <th style="text-align: center; width: 160px; min-width: 160px;" class="col-sticky-action">Aksi</th>
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

                                    $rawOosItems = !empty($valMap['oos_items_json']) ? (is_array($valMap['oos_items_json']) ? $valMap['oos_items_json'] : json_decode((string)$valMap['oos_items_json'], true)) : null;
                                    $hasMultiOos = is_array($rawOosItems) && count($rawOosItems) > 0;
                                    $multiProductNames = $hasMultiOos ? array_map(fn($it) => $it['product_name'] ?? ($it['nama_produk'] ?? ($it['produk_oos'] ?? 'Dulux SKU')), $rawOosItems) : [];
                                    $multiMaxLama = $hasMultiOos ? max(array_map(fn($it) => (int)($it['lama_oos_hari'] ?? 0), $rawOosItems) ?: [0]) : 0;
                                    $multiTotalSaran = $hasMultiOos ? array_sum(array_map(fn($it) => (int)($it['saran_qty_order'] ?? 0), $rawOosItems)) : 0;

                                    $produk = trim((string)($valMap['pilih_produk_dulux_yang_mengalami_out_of_stock_oos'] ?? ($valMap['nama_produk_yang_kosong_oos'] ?? ($valMap['nama_produk_yang_kosong'] ?? ($valMap['produk_oos'] ?? ($valMap['produk'] ?? 'Dulux Product'))))));
                                    $baseColor = trim((string)($valMap['base_kategori_warna_yang_kosong'] ?? ($valMap['base_tipe_warna'] ?? ($valMap['base_color'] ?? ($valMap['base_warna'] ?? ($valMap['base'] ?? '-'))))));
                                    $kemasanSize = trim((string)($valMap['kemasan_size_yang_kosong'] ?? ($valMap['ukuran_kemasan_size'] ?? ($valMap['kemasan_size'] ?? ($valMap['kemasan'] ?? '-')))));
                                    $lamaOos = $hasMultiOos ? $multiMaxLama : (int)($valMap['lama_kondisi_barang_kosong_jumlah_hari'] ?? ($valMap['lama_kondisi_oos_jumlah_hari'] ?? ($valMap['lama_oos_hari'] ?? 0)));
                                    $saranQty = $hasMultiOos ? $multiTotalSaran : (int)($valMap['saran_kuantiti_order_ke_toko_qty_kemasan'] ?? ($valMap['saran_kuantitas_order_qty_kaleng'] ?? ($valMap['saran_qty_order'] ?? 0)));
                                    $alasanOos = trim((string)($valMap['penyebab_alasan_out_of_stock_oos'] ?? ($valMap['alasan_oos'] ?? ($valMap['penyebab_alasan_oos'] ?? ($valMap['alasan'] ?? 'Lain-lain')))));

                                    $tipeOos = strtolower(trim((string)($valMap['tipe_laporan_oos'] ?? '')));
                                    $isNoOos = $tipeOos === 'no_oos' || str_contains(strtolower($alasanOos), 'no oos') || str_contains(strtolower($alasanOos), 'stok lengkap') || str_contains(strtolower($produk), 'no oos');
                                    $status = $sub->status ?? 'pending';
                                @endphp
                                <tr style="{{ $isNoOos ? 'background: #f0fdf4;' : '' }}">
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
                                            <span class="region-badge {{ strtolower($region) }}" style="font-size: 0.7rem; padding: 1px 5px;">{{ $region }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($isNoOos)
                                            <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.76rem; padding: 3px 8px; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-circle-check"></i> No OOS / Stok Lengkap
                                            </span>
                                        @elseif($hasMultiOos)
                                            <div>
                                                <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.72rem; padding: 2px 7px; border-radius: 6px; font-weight: 800; display: inline-block; margin-bottom: 3px;">
                                                    {{ count($rawOosItems) }} SKU OOS
                                                </span>
                                                <div style="font-weight: 700; color: #1e3a8a; font-size: 0.82rem; line-height: 1.3;">
                                                    {{ implode(', ', array_slice($multiProductNames, 0, 2)) }}{{ count($multiProductNames) > 2 ? ' +' . (count($multiProductNames) - 2) . ' lainnya' : '' }}
                                                </div>
                                                @if(count($rawOosItems) > 1)
                                                    <details style="margin-top: 4px; font-size: 0.74rem;">
                                                        <summary style="cursor: pointer; color: #2563eb; font-weight: 700; outline: none;">
                                                            Rincian {{ count($rawOosItems) }} SKU
                                                        </summary>
                                                        <div style="margin-top: 4px; padding: 6px 8px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.06); max-width: 260px;">
                                                            @foreach($rawOosItems as $oi)
                                                                <div style="padding: 2px 0; border-bottom: 1px dashed #f1f5f9; display: flex; justify-content: space-between; gap: 6px;">
                                                                    <span style="color: #1e293b; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $oi['product_name'] ?? ($oi['nama_produk'] ?? 'Produk') }}">
                                                                        {{ $oi['product_name'] ?? ($oi['nama_produk'] ?? 'Produk') }}
                                                                    </span>
                                                                    <span style="color: #dc2626; font-weight: 700; white-space: nowrap;">
                                                                        {{ $oi['lama_oos_hari'] ?? 1 }} hr
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>
                                        @else
                                            <div style="font-weight: 600; color: #1e3a8a;">
                                                {{ $produk }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="font-size: 0.82rem; color: #334155;">
                                        @if($isNoOos)
                                            <span style="color: #94a3b8;">-</span>
                                        @elseif($hasMultiOos && count($rawOosItems) > 1)
                                            <span style="font-size: 0.74rem; color: #64748b; font-style: italic;">Multi Base</span>
                                        @else
                                            <span style="display: inline-block; padding: 2px 8px; background: #f1f5f9; border-radius: 6px; font-weight: 600; font-size: 0.78rem;">
                                                {{ $baseColor }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="font-size: 0.82rem; color: #334155;">
                                        @if($isNoOos)
                                            <span style="color: #94a3b8;">-</span>
                                        @elseif($hasMultiOos && count($rawOosItems) > 1)
                                            <span style="font-size: 0.74rem; color: #64748b; font-style: italic;">Multi Size</span>
                                        @else
                                            {{ $kemasanSize }}
                                        @endif
                                    </td>
                                    <td style="text-align: center; font-weight: 700;">
                                        @if($isNoOos)
                                            <span style="color: #16a34a; font-weight: 700; font-size: 0.8rem;">0 Hari</span>
                                        @elseif($lamaOos > 0)
                                            <span style="color: #dc2626; font-size: 0.82rem; background: #fef2f2; padding: 2px 7px; border-radius: 6px; border: 1px solid #fecaca; display: inline-block;">
                                                {{ $hasMultiOos ? 'Maks ' : '' }}{{ $lamaOos }} Hari
                                            </span>
                                        @else
                                            <span style="color: #94a3b8;">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center; font-weight: 700;">
                                        @if($isNoOos)
                                            <span style="color: #94a3b8;">-</span>
                                        @elseif($saranQty > 0)
                                            <span style="color: #0b3d88; font-size: 0.82rem;">{{ $saranQty }}</span>
                                        @else
                                            <span style="color: #94a3b8;">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-size: 0.82rem; color: {{ $isNoOos ? '#15803d' : '#b91c1c' }}; font-weight: 600;">
                                            {{ $isNoOos ? 'Stok Lengkap / Tidak Ada OOS' : ($hasMultiOos && count($rawOosItems) > 1 ? ($alasanOos ?: 'Kendala PO/Distributor') : $alasanOos) }}
                                        </div>
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
                                            <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" class="btn-action-view" title="Lihat Detail & Bukti Foto">
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
                                                <button type="button" class="btn-action-quick-reject" onclick="openOosRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Tolak Laporan">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            @elseif(in_array($status, ['approved', 'verified']))
                                                <button type="button" class="btn-action-quick-reject" onclick="openOosRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Batalkan / Tolak Laporan">
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

                <!-- Live Submissions Pagination Bar -->
                @if($submissions->hasPages())
                    <div class="oos-pagination-bar">
                        <div class="oos-pagination-info">
                            Menampilkan <strong>{{ $submissions->firstItem() }}</strong> s/d <strong>{{ $submissions->lastItem() }}</strong> dari <strong>{{ number_format($submissions->total()) }}</strong> laporan masuk (Hal <strong>{{ $submissions->currentPage() }}</strong> dari <strong>{{ $submissions->lastPage() }}</strong>)
                        </div>
                        <div class="oos-pagination-controls">
                            @if(!$submissions->onFirstPage())
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url(1) }}" class="oos-page-btn" title="Halaman Pertama">
                                    <i class="fa-solid fa-angles-left"></i>
                                </a>
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->previousPageUrl() }}" class="oos-page-btn">
                                    <i class="fa-solid fa-chevron-left"></i> Prev
                                </a>
                            @else
                                <span class="oos-page-btn disabled"><i class="fa-solid fa-angles-left"></i></span>
                                <span class="oos-page-btn disabled"><i class="fa-solid fa-chevron-left"></i> Prev</span>
                            @endif

                            @for($i = max(1, $submissions->currentPage() - 2); $i <= min($submissions->lastPage(), $submissions->currentPage() + 2); $i++)
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url($i) }}" class="oos-page-btn {{ $i === $submissions->currentPage() ? 'active' : '' }}">
                                    {{ $i }}
                                </a>
                            @endfor

                            @if($submissions->hasMorePages())
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->nextPageUrl() }}" class="oos-page-btn">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </a>
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url($submissions->lastPage()) }}" class="oos-page-btn" title="Halaman Terakhir">
                                    <i class="fa-solid fa-angles-right"></i>
                                </a>
                            @else
                                <span class="oos-page-btn disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
                                <span class="oos-page-btn disabled"><i class="fa-solid fa-angles-right"></i></span>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 3rem 1.5rem; color: #64748b;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #94a3b8; margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <div style="font-weight: 700; color: #1e293b; font-size: 1rem;">Belum Ada Laporan Masuk</div>
                    <div style="font-size: 0.84rem; color: #64748b; margin-top: 0.25rem;">
                        Belum ada laporan Out of Stock (OOS) yang dikirim oleh Promotor / SPG untuk filter periode ini.
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Tolak Laporan OOS -->
    <div id="oos_reject_modal" class="portal-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="portal-modal-box">
            <div class="portal-modal-header">
                <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Tolak Laporan OOS
                </h4>
                <button type="button" onclick="closeOosRejectModal()" style="background: none; border: none; font-size: 1.3rem; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form id="oos_reject_form" method="POST" action="" style="padding: 1.5rem; margin: 0;">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem;">Kode Laporan:</label>
                    <div id="oos_reject_code" style="font-family: monospace; font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.88rem; border: 1px solid #e2e8f0;"></div>
                </div>
                <div style="margin-bottom: 1.25rem;">
                    <label for="oos_rejection_note" style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem;">Alasan Penolakan / Catatan: <span style="color: #ef4444;">*</span></label>
                    <textarea name="admin_notes" id="oos_rejection_note" rows="3" required placeholder="Tuliskan alasan penolakan secara jelas agar dapat diperbaiki oleh Promotor / SPG..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.6rem 0.75rem; font-size: 0.84rem; outline: none; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeOosRejectModal()" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 700; font-size: 0.82rem; cursor: pointer;">Batal</button>
                    <button type="submit" style="padding: 0.5rem 1.25rem; border-radius: 8px; border: none; background: #ef4444; color: #fff; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-xmark"></i> Tolak Laporan
                    </button>
                </div>
            </form>
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
    padding: 0.35rem 0.75rem;
    border-radius: 7px;
    color: #64748b;
    background: transparent;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-chan-filter:hover {
    background: #f1f5f9;
    color: #0F52BA;
}
.btn-chan-filter.active {
    background: #0F52BA;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(15, 82, 186, 0.25);
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
    align-items: center;
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
    font-weight: 600;
}
.meta-pill .meta-val {
    color: #0f172a;
    font-weight: 700;
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
.region-pill {
    background: #f1f5f9;
    color: #334155;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.76rem;
}

/* Modern Pagination Bar Styles */
.oos-pagination-bar {
    padding: 1rem 1.5rem;
    background: #ffffff;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.oos-pagination-info {
    font-size: 0.84rem;
    color: #64748b;
    font-weight: 500;
}
.oos-pagination-info strong {
    color: #0f172a;
    font-weight: 700;
}
.oos-pagination-controls {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f8fafc;
    padding: 4px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}
.oos-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 32px;
    height: 32px;
    padding: 0 10px;
    border-radius: 7px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    background: transparent;
    border: 1px solid transparent;
    text-decoration: none;
    transition: all 0.15s ease;
    cursor: pointer;
}
.oos-page-btn:hover:not(.disabled):not(.active) {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #0F52BA;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.oos-page-btn.active {
    background: #0F52BA !important;
    color: #ffffff !important;
    border-color: #0F52BA !important;
    font-weight: 800;
    box-shadow: 0 2px 6px rgba(15, 82, 186, 0.3);
}
.oos-page-btn.disabled {
    color: #cbd5e1 !important;
    cursor: not-allowed;
    pointer-events: none;
}
.oos-page-dots {
    padding: 0 6px;
    color: #94a3b8;
    font-weight: 700;
}

/* Action Buttons & Modal Styles */
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
    border: 1px solid #15803d;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-action-quick-approve:hover {
    background: #15803d;
}
.btn-action-quick-reject {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.35rem 0.65rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    background: #ef4444;
    color: #ffffff;
    border: 1px solid #dc2626;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-action-quick-reject:hover {
    background: #dc2626;
}
.col-sticky-action {
    position: sticky;
    right: 0;
    background: #ffffff;
    box-shadow: -3px 0 6px rgba(0, 0, 0, 0.05);
    z-index: 2;
}
.portal-modal-overlay {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
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
function switchOosTab(tabId) {
    document.getElementById('pane_oos_summary').style.display = 'none';
    document.getElementById('pane_oos_weekly').style.display = 'none';
    document.getElementById('pane_oos_raw').style.display = 'none';
    var livePane = document.getElementById('pane_oos_live');
    if (livePane) livePane.style.display = 'none';

    document.getElementById('btn_oos_tab_summary').classList.remove('active');
    document.getElementById('btn_oos_tab_weekly').classList.remove('active');
    document.getElementById('btn_oos_tab_raw').classList.remove('active');
    var liveBtn = document.getElementById('btn_oos_tab_live');
    if (liveBtn) liveBtn.classList.remove('active');

    if (tabId === 'weekly') {
        document.getElementById('pane_oos_weekly').style.display = 'block';
        document.getElementById('btn_oos_tab_weekly').classList.add('active');
    } else if (tabId === 'raw') {
        document.getElementById('pane_oos_raw').style.display = 'block';
        document.getElementById('btn_oos_tab_raw').classList.add('active');
    } else if (tabId === 'live') {
        if (livePane) livePane.style.display = 'block';
        if (liveBtn) liveBtn.classList.add('active');
    } else {
        document.getElementById('pane_oos_summary').style.display = 'block';
        document.getElementById('btn_oos_tab_summary').classList.add('active');
    }

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
}

function openOosRejectModal(subId, subCode) {
    var modal = document.getElementById('oos_reject_modal');
    var codeEl = document.getElementById('oos_reject_code');
    var form = document.getElementById('oos_reject_form');
    if (!modal || !form) return;
    if (codeEl) codeEl.innerText = subCode;
    var baseRoute = "{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => ':id', 'p' => $tenantPrincipal->id]) }}";
    form.action = baseRoute.replace(':id', subId);
    modal.style.display = 'flex';
}

function closeOosRejectModal() {
    var modal = document.getElementById('oos_reject_modal');
    if (modal) modal.style.display = 'none';
}
</script>
