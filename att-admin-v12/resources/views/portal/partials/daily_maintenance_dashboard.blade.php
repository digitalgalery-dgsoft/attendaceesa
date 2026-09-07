{{-- DAILY MAINTENANCE EXECUTIVE DASHBOARD --}}
<div class="dm-executive-wrapper">

    {{-- TOP TOOLBAR: TAB NAVIGATION & EXPORT BUTTONS --}}
    <div class="dm-top-toolbar">
        {{-- TABS NAVIGATION --}}
        <div class="dm-tabs-container">
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'summary', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? 'summary') === 'summary' ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Ringkasan & Kepatuhan</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? '') === 'stores' ? 'active' : '' }}">
                <i class="fa-solid fa-store"></i>
                <span>Matriks Toko & Mesin</span>
                <span class="badge-count">{{ number_format($dmData['store_matrix']['total_rows'] ?? 0) }}</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? '') === 'raw' ? 'active' : '' }}">
                <i class="fa-solid fa-list-check"></i>
                <span>Data Mentah Submission</span>
                <span class="badge-count">{{ number_format($dmData['submissions']['total'] ?? 0) }}</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'live', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? '') === 'live' ? 'active' : '' }}">
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
        <div class="dm-export-actions">
            <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'dm_stores', 'p' => $tenantPrincipal->id])) }}" 
               class="btn-dm-export-action success" title="Download Rekapitulasi Toko & Mesin (CSV)">
                <i class="fa-solid fa-file-excel"></i>
                <span>Export Rekap Toko & Mesin</span>
            </a>
            <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'dm_raw', 'p' => $tenantPrincipal->id])) }}" 
               class="btn-dm-export-action primary" title="Download Data Mentah Submission Lengkap (CSV)">
                <i class="fa-solid fa-file-csv"></i>
                <span>Export Data Mentah</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB 1: RINGKASAN & KEPATUHAN (SUMMARY & COMPLIANCE) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? 'summary') === 'summary')
        {{-- KPI HIGHLIGHT CARDS --}}
        <div class="dm-kpi-grid">
            <div class="dm-kpi-card">
                <div class="dm-kpi-icon blue">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="dm-kpi-content">
                    <span class="dm-kpi-label">Total Toko Terawat</span>
                    <div class="dm-kpi-val">{{ number_format($dmData['kpis']['total_stores'] ?? 0) }} <span class="dm-kpi-unit">Toko</span></div>
                    <span class="dm-kpi-sub">Toko dengan aktivitas maintenance</span>
                </div>
            </div>

            <div class="dm-kpi-card">
                <div class="dm-kpi-icon indigo">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <div class="dm-kpi-content">
                    <span class="dm-kpi-label">Mesin Tinting Aktif</span>
                    <div class="dm-kpi-val">{{ number_format($dmData['kpis']['total_machines'] ?? 0) }} <span class="dm-kpi-unit">Mesin</span></div>
                    <span class="dm-kpi-sub">Total unit serial mesin terdaftar</span>
                </div>
            </div>

            <div class="dm-kpi-card">
                <div class="dm-kpi-icon teal">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div class="dm-kpi-content">
                    <span class="dm-kpi-label">Total Submission Maintenance</span>
                    <div class="dm-kpi-val">{{ number_format($dmData['kpis']['total_submissions'] ?? 0) }} <span class="dm-kpi-unit">Laporan</span></div>
                    <span class="dm-kpi-sub">Periode {{ reset($dmData['months']) }} – {{ end($dmData['months']) }} {{ $startYear ?? 2026 }}</span>
                </div>
            </div>

            <div class="dm-kpi-card">
                <div class="dm-kpi-icon green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="dm-kpi-content">
                    <span class="dm-kpi-label">Kepatuhan Cek Tinta</span>
                    <div class="dm-kpi-val">{{ number_format($dmData['kpis']['tinta_rate'] ?? 0, 1) }}%</div>
                    <span class="dm-kpi-sub">Pemeriksaan & pengisian tabung tinta</span>
                </div>
            </div>
        </div>

        {{-- CHECKLIST COMPLIANCE HEALTH CARDS --}}
        <div class="dm-compliance-section">
            <div class="dm-compliance-header">
                <div>
                    <h4 class="dm-section-title"><i class="fa-solid fa-list-check text-primary"></i> Ringkasan Kepatuhan Prosedur Perawatan</h4>
                    <p class="dm-section-subtitle">Persentase keberhasilan pengerjaan 4 checklist wajib teknisi & DC lapangan</p>
                </div>
            </div>

            <div class="dm-checklist-grid">
                @php $tintaPct = (float)($dmData['kpis']['tinta_rate'] ?? 0); @endphp
                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-fill-drip text-primary"></i> Cek & Isi Tinta</span>
                        <span class="dm-chk-badge {{ $tintaPct >= 80 ? 'green' : 'amber' }}">{{ number_format($tintaPct, 1) }}%</span>
                    </div>
                    <div class="dm-progress-track">
                        <div class="dm-progress-fill color-blue" style="width: {{ min(100, $tintaPct) }}%;"></div>
                    </div>
                    <p class="dm-chk-desc">Pengecekan level tinta tabung & pengisian jika dibutuhkan.</p>
                </div>

                @php $nozzlePct = (float)($dmData['kpis']['nozzle_rate'] ?? 0); @endphp
                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-broom text-info"></i> Nozzle & Cup Cleaning</span>
                        <span class="dm-chk-badge {{ $nozzlePct >= 80 ? 'green' : 'amber' }}">{{ number_format($nozzlePct, 1) }}%</span>
                    </div>
                    <div class="dm-progress-track">
                        <div class="dm-progress-fill color-cyan" style="width: {{ min(100, $nozzlePct) }}%;"></div>
                    </div>
                    <p class="dm-chk-desc">Pembersihan ujung nozzle, cuci cup & spons (D200 / Brush).</p>
                </div>

                @php $mix2winPct = (float)($dmData['kpis']['mix2win_rate'] ?? 0); @endphp
                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-laptop-code text-indigo"></i> Prosedur Mix2Win</span>
                        <span class="dm-chk-badge {{ $mix2winPct >= 80 ? 'green' : 'amber' }}">{{ number_format($mix2winPct, 1) }}%</span>
                    </div>
                    <div class="dm-progress-track">
                        <div class="dm-progress-fill color-purple" style="width: {{ min(100, $mix2winPct) }}%;"></div>
                    </div>
                    <p class="dm-chk-desc">Kepatuhan 12 langkah sirkulasi tinter pada software Mix2Win.</p>
                </div>

                @php $cleanPct = (float)($dmData['kpis']['pembersihan_rate'] ?? 0); @endphp
                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-soap text-success"></i> Pembersihan Unit & PC</span>
                        <span class="dm-chk-badge {{ $cleanPct >= 80 ? 'green' : 'amber' }}">{{ number_format($cleanPct, 1) }}%</span>
                    </div>
                    <div class="dm-progress-track">
                        <div class="dm-progress-fill color-teal" style="width: {{ min(100, $cleanPct) }}%;"></div>
                    </div>
                    <p class="dm-chk-desc">Pembersihan bodi mesin tinting, shaker & komputer toko.</p>
                </div>
            </div>
        </div>

        {{-- BREAKDOWNS: MACHINE TYPE & STORE CATEGORY --}}
        <div class="dm-breakdowns-grid">
            {{-- By Machine Type --}}
            <div class="dm-card">
                <div class="dm-card-header">
                    <h5 class="dm-card-title"><i class="fa-solid fa-gears text-primary"></i> Sebaran per Tipe Mesin POST</h5>
                    <span class="dm-card-badge">{{ count($dmData['by_machine_type']) }} Tipe</span>
                </div>
                <div class="dm-table-scroll-container" style="max-height: 380px;">
                    <table class="dm-table table-hover">
                        <thead>
                            <tr>
                                <th>Tipe Mesin</th>
                                <th class="text-center">Submission</th>
                                <th class="text-center">Toko</th>
                                <th class="text-center">Mesin</th>
                                <th class="text-center">Tinta OK</th>
                                <th class="text-center">Pembersihan OK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dmData['by_machine_type'] as $bm)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $bm['machine_type'] }}</span>
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($bm['submissions']) }}</td>
                                    <td class="text-center">{{ number_format($bm['stores']) }}</td>
                                    <td class="text-center">{{ number_format($bm['machines']) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light-primary text-primary fw-bold">{{ $bm['avg_tinta'] }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light-success text-success fw-bold">{{ $bm['avg_clean'] }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- By Store Category --}}
            <div class="dm-card">
                <div class="dm-card-header">
                    <h5 class="dm-card-title"><i class="fa-solid fa-tags text-success"></i> Sebaran per Kategori Toko</h5>
                    <span class="dm-card-badge">{{ count($dmData['by_category']) }} Kategori</span>
                </div>
                <div class="dm-table-scroll-container" style="max-height: 380px;">
                    <table class="dm-table table-hover">
                        <thead>
                            <tr>
                                <th>Kategori Toko</th>
                                <th class="text-center">Submission</th>
                                <th class="text-center">Toko Terawat</th>
                                <th class="text-center">Mesin</th>
                                <th class="text-center">% Kepatuhan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dmData['by_category'] as $bc)
                                <tr>
                                    <td>
                                        <span class="badge bg-light-dark text-dark fw-bold px-2 py-1">{{ $bc['category'] ?: 'Uncategorized' }}</span>
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($bc['submissions']) }}</td>
                                    <td class="text-center">{{ number_format($bc['stores']) }}</td>
                                    <td class="text-center">{{ number_format($bc['machines']) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light-success text-success fw-bold">{{ $bc['avg_tinta'] }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Tidak ada data untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- By Regional RSM Area --}}
        <div class="dm-card">
            <div class="dm-card-header">
                <h5 class="dm-card-title"><i class="fa-solid fa-map-location-dot text-indigo"></i> Rekapitulasi per Regional (RSM Area)</h5>
                <span class="dm-card-badge">{{ count($dmData['by_region']) }} Region</span>
            </div>
            <div class="dm-table-scroll-container" style="max-height: 420px;">
                <table class="dm-table table-hover">
                    <thead>
                        <tr>
                            <th>Region / RSM Area</th>
                            <th class="text-center">Total Submission</th>
                            <th class="text-center">Jumlah Toko</th>
                            <th class="text-center">Jumlah Mesin</th>
                            <th class="text-center">Cek Tinta OK</th>
                            <th class="text-center">Pembersihan OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dmData['by_region'] as $br)
                            <tr>
                                <td>
                                    <span class="region-badge">{{ $br['rsm_area'] ?: 'Other' }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ number_format($br['submissions']) }}</td>
                                <td class="text-center">{{ number_format($br['stores']) }} Toko</td>
                                <td class="text-center">{{ number_format($br['machines']) }} Mesin</td>
                                <td class="text-center">
                                    <span class="badge bg-light-primary text-primary fw-bold">{{ $br['avg_tinta'] }}%</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-success text-success fw-bold">{{ $br['avg_clean'] }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada data untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 2: MATRIKS TOKO & MESIN (STORE & MACHINE MATRIX) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? '') === 'stores')
        <div class="dm-card">
            <div class="dm-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="dm-card-title mb-1"><i class="fa-solid fa-store text-primary"></i> Matriks Perawatan per Toko & Mesin Tinting</h5>
                    <p class="text-muted small mb-0">Rekapitulasi frekuensi maintenance, nomor serial mesin, dan skor kepatuhan per unit toko.</p>
                </div>
                <div class="meta-pill">
                    <span class="meta-lbl">Total Unit Terdata:</span>
                    <strong class="meta-val">{{ number_format($dmData['store_matrix']['total_rows'] ?? 0) }} Mesin</strong>
                </div>
            </div>

            {{-- SCROLLABLE TABLE CONTAINER (HORIZONTAL & VERTICAL) --}}
            <div class="dm-table-scroll-container" style="max-height: 560px;">
                <table class="dm-table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Toko</th>
                            <th style="width: 100px;">SAP</th>
                            <th style="width: 100px;">Kategori</th>
                            <th>Region / Area</th>
                            <th>Tipe Mesin</th>
                            <th>No Mesin (Serial)</th>
                            <th class="text-center">Frekuensi Rawat</th>
                            <th class="text-center">Tgl Terakhir</th>
                            <th class="text-center">Tinta OK</th>
                            <th class="text-center">Pembersihan</th>
                            <th class="text-center">Skor Kepatuhan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dmData['store_matrix']['rows'] as $idx => $sr)
                            <tr>
                                <td class="text-muted fw-bold">{{ $dmData['store_matrix']['from'] + $idx }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $sr['store_name'] }}</div>
                                    @if(!empty($sr['is_live']))
                                        <span class="badge bg-primary text-white" style="font-size: 0.68rem; padding: 2px 6px; margin-top: 2px; display: inline-flex; align-items: center; gap: 3px;"><i class="fa-solid fa-bolt"></i> LIVE</span>
                                    @endif
                                </td>
                                <td><code>{{ $sr['sap_code'] ?: '-' }}</code></td>
                                <td>
                                    <span class="badge bg-light-dark text-dark">{{ $sr['category'] ?: 'SSO' }}</span>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark">{{ $sr['rsm_area'] ?: '-' }}</div>
                                    <div class="small text-muted">{{ $sr['area'] ?: '-' }}</div>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">{{ $sr['machine_type'] }}</span>
                                </td>
                                <td>
                                    <span class="serial-badge">{{ $sr['machine_no'] ?: '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="freq-badge">{{ number_format($sr['total_checks']) }}x</span>
                                </td>
                                <td class="text-center small text-muted">{{ $sr['last_date'] ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="small fw-bold">{{ number_format($sr['tinta_ok_cnt']) }} / {{ number_format($sr['total_checks']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="small fw-bold">{{ number_format($sr['clean_ok_cnt']) }} / {{ number_format($sr['total_checks']) }}</span>
                                </td>
                                <td class="text-center">
                                    @php $score = (float)($sr['compliance_pct'] ?? 0); @endphp
                                    <span class="badge {{ $score >= 90 ? 'bg-light-success text-success' : ($score >= 70 ? 'bg-light-warning text-warning' : 'bg-light-danger text-danger') }} fw-bold">
                                        {{ $score }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                    Tidak ada data toko & mesin yang cocok dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION BAR FOR STORE MATRIX --}}
            @if(($dmData['store_matrix']['total_pages'] ?? 1) > 1)
                @php
                    $curPage = (int)($dmData['store_matrix']['page'] ?? 1);
                    $totPages = (int)($dmData['store_matrix']['total_pages'] ?? 1);
                @endphp
                <div class="dm-pagination-bar">
                    <div class="dm-pagination-info">
                        Menampilkan <strong>{{ $dmData['store_matrix']['from'] }}</strong> s/d <strong>{{ $dmData['store_matrix']['to'] }}</strong> dari <strong>{{ number_format($dmData['store_matrix']['total_rows']) }}</strong> unit mesin (Hal <strong>{{ $curPage }}</strong> dari <strong>{{ $totPages }}</strong>)
                    </div>

                    <div class="dm-pagination-controls">
                        {{-- First Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => 1, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}" title="Halaman Pertama">
                            <i class="fa-solid fa-angles-left"></i>
                        </a>

                        {{-- Prev Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => max(1, $curPage - 1), 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </a>

                        {{-- Numeric Pages --}}
                        @php
                            $startP = max(1, $curPage - 2);
                            $endP = min($totPages, $curPage + 2);
                        @endphp

                        @if($startP > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => 1, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" class="dm-page-btn">1</a>
                            @if($startP > 2)
                                <span class="dm-page-dots">&hellip;</span>
                            @endif
                        @endif

                        @for($p = $startP; $p <= $endP; $p++)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => $p, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
                               class="dm-page-btn {{ $p === $curPage ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($endP < $totPages)
                            @if($endP < $totPages - 1)
                                <span class="dm-page-dots">&hellip;</span>
                            @endif
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => $totPages, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" class="dm-page-btn">{{ $totPages }}</a>
                        @endif

                        {{-- Next Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => min($totPages, $curPage + 1), 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>

                        {{-- Last Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'store_page' => $totPages, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}" title="Halaman Terakhir">
                            <i class="fa-solid fa-angles-right"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 3: DATA MENTAH SUBMISSION (RAW SUBMISSIONS) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? '') === 'raw')
        <div class="dm-card">
            <div class="dm-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="dm-card-title mb-1"><i class="fa-solid fa-list-check text-primary"></i> Data Mentah Submission Daily Maintenance</h5>
                    <p class="text-muted small mb-0">Seluruh data aktivitas laporan maintenance lapangan lengkap 15+ kolom.</p>
                </div>
                <div class="meta-pill">
                    <span class="meta-lbl">Total Laporan:</span>
                    <strong class="meta-val">{{ number_format($dmData['submissions']['total'] ?? 0) }} Baris</strong>
                </div>
            </div>

            {{-- SCROLLABLE TABLE CONTAINER (HORIZONTAL & VERTICAL) --}}
            <div class="dm-table-scroll-container" style="max-height: 560px;">
                <table class="dm-table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Submission Date</th>
                            <th>Tgl Report</th>
                            <th>Nama Toko</th>
                            <th>SAP</th>
                            <th>Kategori</th>
                            <th>Region / Area</th>
                            <th>Nama TL</th>
                            <th>Tipe Mesin</th>
                            <th>No Mesin (Serial)</th>
                            <th>Nama DC</th>
                            <th class="text-center">Tinta</th>
                            <th class="text-center">Nozzle/Brush</th>
                            <th class="text-center">Mix2Win</th>
                            <th class="text-center">Pembersihan</th>
                            <th>Kesimpulan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dmData['submissions']['rows'] as $idx => $r)
                            <tr>
                                <td class="text-muted fw-bold">{{ $dmData['submissions']['from'] + $idx }}</td>
                                <td class="small fw-semibold text-dark">{{ $r['submission_date'] }}</td>
                                <td class="small text-muted">{{ $r['tanggal_report'] }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $r['store_name'] }}</div>
                                    @if(!empty($r['is_live']))
                                        <span class="badge bg-primary text-white" style="font-size: 0.68rem; padding: 2px 6px; margin-top: 2px; display: inline-flex; align-items: center; gap: 3px;"><i class="fa-solid fa-bolt"></i> LIVE</span>
                                    @endif
                                </td>
                                <td><code>{{ $r['sap_code'] ?: '-' }}</code></td>
                                <td><span class="badge bg-light-dark text-dark">{{ $r['category'] ?: 'SSO' }}</span></td>
                                <td>
                                    <div class="small fw-bold text-dark">{{ $r['rsm_area'] ?: '-' }}</div>
                                    <div class="small text-muted">{{ $r['area'] ?: '-' }}</div>
                                </td>
                                <td class="small text-dark">{{ $r['tl_name'] ?: '-' }}</td>
                                <td><span class="fw-bold text-primary">{{ $r['machine_type'] }}</span></td>
                                <td><span class="serial-badge">{{ $r['machine_no'] ?: '-' }}</span></td>
                                <td class="small text-dark">{{ $r['dc_name'] ?: '-' }}</td>
                                <td class="text-center">
                                    @if($r['tinta_ok'] == 1)
                                        <span class="badge bg-light-success text-success"><i class="fa-solid fa-check"></i> OK</span>
                                    @else
                                        <span class="badge bg-light-danger text-danger"><i class="fa-solid fa-xmark"></i> NO</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($r['d200_nozzle_ok'] == 1 || $r['discovery_brush_ok'] == 1 || $r['manual_nozzle_ok'] == 1)
                                        <span class="badge bg-light-success text-success"><i class="fa-solid fa-check"></i> OK</span>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-primary text-primary">{{ $r['mix2win_steps_ok'] }}/12</span>
                                </td>
                                <td class="text-center">
                                    @if($r['pembersihan_all_ok'] == 1)
                                        <span class="badge bg-light-success text-success"><i class="fa-solid fa-check"></i> Bersih</span>
                                    @else
                                        <span class="badge bg-light-warning text-warning">Sebagian</span>
                                    @endif
                                </td>
                                <td class="small text-muted" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $r['kesimpulan'] }}">
                                    {{ $r['kesimpulan'] ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                    Tidak ada data submission yang cocok dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION BAR FOR RAW SUBMISSIONS --}}
            @if(($dmData['submissions']['total_pages'] ?? 1) > 1)
                @php
                    $curPage = (int)($dmData['submissions']['page'] ?? 1);
                    $totPages = (int)($dmData['submissions']['total_pages'] ?? 1);
                @endphp
                <div class="dm-pagination-bar">
                    <div class="dm-pagination-info">
                        Menampilkan <strong>{{ $dmData['submissions']['from'] }}</strong> s/d <strong>{{ $dmData['submissions']['to'] }}</strong> dari <strong>{{ number_format($dmData['submissions']['total']) }}</strong> data submission (Hal <strong>{{ $curPage }}</strong> dari <strong>{{ $totPages }}</strong>)
                    </div>

                    <div class="dm-pagination-controls">
                        {{-- First Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}" title="Halaman Pertama">
                            <i class="fa-solid fa-angles-left"></i>
                        </a>

                        {{-- Prev Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => max(1, $curPage - 1), 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage <= 1 ? 'disabled' : '' }}">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </a>

                        {{-- Numeric Pages --}}
                        @php
                            $startP = max(1, $curPage - 2);
                            $endP = min($totPages, $curPage + 2);
                        @endphp

                        @if($startP > 1)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => 1, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="dm-page-btn">1</a>
                            @if($startP > 2)
                                <span class="dm-page-dots">&hellip;</span>
                            @endif
                        @endif

                        @for($p = $startP; $p <= $endP; $p++)
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $p, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                               class="dm-page-btn {{ $p === $curPage ? 'active' : '' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($endP < $totPages)
                            @if($endP < $totPages - 1)
                                <span class="dm-page-dots">&hellip;</span>
                            @endif
                            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $totPages, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" class="dm-page-btn">{{ $totPages }}</a>
                        @endif

                        {{-- Next Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => min($totPages, $curPage + 1), 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>

                        {{-- Last Page --}}
                        <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'raw_page' => $totPages, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
                           class="dm-page-btn {{ $curPage >= $totPages ? 'disabled' : '' }}" title="Halaman Terakhir">
                            <i class="fa-solid fa-angles-right"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 4: DATA LAPORAN MASUK (LIVE SUBMISSIONS & VERIFIKASI) --}}
    {{-- ========================================================================= --}}
    @if(($activeTab ?? '') === 'live')
        <div class="dm-card">
            <div class="dm-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="dm-card-title mb-1"><i class="fa-solid fa-inbox text-primary"></i> Data Laporan Masuk & Verifikasi Daily Maintenance</h5>
                    <p class="text-muted small mb-0">Verifikasi berkala kondisi mesin tinting POST, kebersihan nozzle, sirkulasi pasta, dan program Mix2Win langsung dari DC/Promotor.</p>
                </div>
                <div class="meta-pill">
                    <span class="meta-lbl">Total Laporan Masuk:</span>
                    <strong class="meta-val" style="color: #2563eb;">{{ number_format(isset($submissions) ? $submissions->total() : 0) }} Laporan</strong>
                </div>
            </div>

            @if(isset($submissions) && $submissions->isNotEmpty())
                <div class="dm-table-scroll-container" style="max-height: 600px;">
                    <table class="dm-table table-hover" style="min-width: 1750px;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 150px;">Kode Laporan</th>
                                <th style="min-width: 140px;">Waktu Submit</th>
                                <th style="min-width: 170px;">Promotor / DC</th>
                                <th style="min-width: 180px;">Nama Toko / Outlet</th>
                                <th style="min-width: 140px;">Area & RSM</th>
                                <th style="min-width: 160px;">Tipe & No Mesin</th>
                                <th style="min-width: 180px;">Kebersihan Nozzle & Brush</th>
                                <th style="min-width: 170px;">Sirkulasi Pasta Tinter</th>
                                <th style="min-width: 170px;">Program Mix2Win</th>
                                <th style="min-width: 170px;">Software Komputer</th>
                                <th style="min-width: 120px; text-align: center;">Bukti Foto</th>
                                <th style="min-width: 180px;">Kesimpulan SPG</th>
                                <th style="text-align: center; width: 110px;">Radius GPS</th>
                                <th style="text-align: center; width: 130px;">Status</th>
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

                                        // Collect photos
                                        $isPhoto = in_array($v->field_type, ['photo', 'camera_photo', 'multi_photo'])
                                            || str_contains((string)$v->field_name, 'foto')
                                            || !empty($v->media_url)
                                            || !empty($v->file_path);

                                        if ($isPhoto) {
                                            $rawP = $v->value_text ?: ($v->file_path ?: $v->media_url);
                                            if ($rawP && is_string($rawP) && !str_starts_with($rawP, '/data/user/')) {
                                                $cleanP = trim($rawP);
                                                $photoUrl = (str_starts_with($cleanP, 'http://') || str_starts_with($cleanP, 'https://'))
                                                    ? $cleanP
                                                    : asset('storage/' . ltrim(str_replace('storage/', '', $cleanP), '/'));
                                                $photos[] = [
                                                    'url' => $photoUrl,
                                                    'label' => $v->formField?->field_label ?? ($v->field_name ?: 'Foto Dokumentasi')
                                                ];
                                            }
                                        }
                                    }

                                    $store = $sub->workLocation?->name ?? ($sub->store_name ?? 'Toko Tidak Terdaftar');
                                    $sap = $sub->workLocation?->code ?? ($sub->workLocation?->store_code ?? '-');
                                    $area = $sub->workLocation?->branch?->name ?? ($sub->workLocation?->area?->name ?? ($sub->workLocation?->area ?? '-'));
                                    $region = $sub->workLocation?->region ?? '-';
                                    $empName = $sub->employee?->full_name ?? ($sub->employee?->name ?? 'Petugas');
                                    $empNik = $sub->employee?->nik ?? ($sub->employee?->employee_no ?? '-');

                                    $tipeMesin = trim((string)($valMap['tipe_mesin_tinting_post_di_toko'] ?? ($valMap['tipe_mesin_post'] ?? ($valMap['tipe_mesin'] ?? '-'))));
                                    $noMesin = trim((string)($valMap['nomor_seri_no_mesin_post_dulux'] ?? ($valMap['no_mesin_post'] ?? ($valMap['no_mesin'] ?? '-'))));
                                    $statusNozzle = trim((string)($valMap['status_pemeriksaan_kebersihan_nozzle_brush_cleaning'] ?? ($valMap['status_nozzle_cleaning'] ?? ($valMap['kebersihan_nozzle'] ?? '-'))));
                                    $statusTinter = trim((string)($valMap['status_sirkulasi_agitasi_pasta_tinter'] ?? ($valMap['status_sirkulasi_tinter'] ?? ($valMap['sirkulasi_agitasi'] ?? '-'))));
                                    $statusMix2win = trim((string)($valMap['status_partisipasi_program_mix2win_toko'] ?? ($valMap['status_program_mix2win'] ?? ($valMap['partisipasi_mix2win'] ?? '-'))));
                                    $statusSoftware = trim((string)($valMap['status_software_tinting_komputer_database_formula_warna'] ?? ($valMap['status_software_komputer'] ?? '-')));
                                    $kesimpulan = trim((string)($valMap['kesimpulan_kondisi_mesin_rekomendasi_maintenance'] ?? ($valMap['kesimpulan_maintenance'] ?? ($valMap['kesimpulan'] ?? '-'))));

                                    $isNozzleClean = stripos($statusNozzle, 'bersih') !== false;
                                    $isTinterNormal = stripos($statusTinter, 'normal') !== false || stripos($statusTinter, 'aman') !== false;
                                    $isMix2winOk = stripos($statusMix2win, 'aktif') !== false;
                                    $isSoftwareNormal = stripos($statusSoftware, 'normal') !== false;
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
                                        <div class="fw-bold text-dark">{{ $store }}</div>
                                        <div style="font-size: 0.74rem; color: #64748b;">
                                            SAP: <span class="badge bg-light-secondary text-secondary" style="font-family: monospace;">{{ $sap }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small">{{ $area }}</div>
                                        <span class="region-badge" style="font-size: 0.7rem; padding: 1px 6px;">{{ $region }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary small">{{ $tipeMesin }}</div>
                                        <span class="serial-badge">{{ $noMesin }}</span>
                                    </td>
                                    <td>
                                        @if($isNozzleClean)
                                            <span class="badge bg-light-success text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-check"></i> {{ $statusNozzle }}</span>
                                        @else
                                            <span class="badge bg-light-danger text-danger" style="font-size: 0.75rem;"><i class="fa-solid fa-triangle-exclamation"></i> {{ $statusNozzle }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isTinterNormal)
                                            <span class="badge bg-light-success text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-check"></i> {{ $statusTinter }}</span>
                                        @else
                                            <span class="badge bg-light-warning text-warning" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-exclamation"></i> {{ $statusTinter }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isMix2winOk)
                                            <span class="badge bg-light-success text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-check"></i> {{ $statusMix2win }}</span>
                                        @else
                                            <span class="badge bg-light-warning text-warning" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-xmark"></i> {{ $statusMix2win }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isSoftwareNormal)
                                            <span class="badge bg-light-success text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-check"></i> {{ $statusSoftware }}</span>
                                        @else
                                            <span class="badge bg-light-danger text-danger" style="font-size: 0.75rem;"><i class="fa-solid fa-triangle-exclamation"></i> {{ $statusSoftware }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if(!empty($photos))
                                            <div style="display: inline-flex; align-items: center; gap: 4px;">
                                                @foreach($photos as $pIdx => $p)
                                                    <img src="{{ $p['url'] }}" 
                                                         alt="{{ $p['label'] }}" 
                                                         title="{{ $p['label'] }}"
                                                         onclick="openDmPhotoModal('{{ $p['url'] }}', '{{ addslashes($p['label']) }}', '{{ $sub->submission_code }}')"
                                                         style="width: 34px; height: 34px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; transition: transform 0.15s ease;"
                                                         onmouseover="this.style.transform='scale(1.15)'"
                                                         onmouseout="this.style.transform='scale(1)'"
                                                         onerror="this.style.display='none'">
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $kesimpulan }}">
                                        {{ $kesimpulan ?: '-' }}
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
                                                <button type="button" class="btn-action-quick-reject" onclick="openDmRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Tolak Laporan">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            @elseif(in_array($status, ['approved', 'verified']))
                                                <button type="button" class="btn-action-quick-reject" onclick="openDmRejectModal('{{ $sub->id }}', '{{ $sub->submission_code }}')" title="Batalkan / Tolak Laporan">
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
                    <div class="dm-pagination-bar">
                        <div class="dm-pagination-info">
                            Menampilkan <strong>{{ $submissions->firstItem() }}</strong> s/d <strong>{{ $submissions->lastItem() }}</strong> dari <strong>{{ number_format($submissions->total()) }}</strong> laporan masuk (Hal <strong>{{ $submissions->currentPage() }}</strong> dari <strong>{{ $submissions->lastPage() }}</strong>)
                        </div>
                        <div class="dm-pagination-controls">
                            @if(!$submissions->onFirstPage())
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url(1) }}" class="dm-page-btn" title="Halaman Pertama">
                                    <i class="fa-solid fa-angles-left"></i>
                                </a>
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->previousPageUrl() }}" class="dm-page-btn">
                                    <i class="fa-solid fa-chevron-left"></i> Prev
                                </a>
                            @else
                                <span class="dm-page-btn disabled"><i class="fa-solid fa-angles-left"></i></span>
                                <span class="dm-page-btn disabled"><i class="fa-solid fa-chevron-left"></i> Prev</span>
                            @endif

                            @for($i = max(1, $submissions->currentPage() - 2); $i <= min($submissions->lastPage(), $submissions->currentPage() + 2); $i++)
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url($i) }}" class="dm-page-btn {{ $i === $submissions->currentPage() ? 'active' : '' }}">
                                    {{ $i }}
                                </a>
                            @endfor

                            @if($submissions->hasMorePages())
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->nextPageUrl() }}" class="dm-page-btn">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </a>
                                <a href="{{ $submissions->appends(array_merge(request()->query(), ['tab' => 'live']))->url($submissions->lastPage()) }}" class="dm-page-btn" title="Halaman Terakhir">
                                    <i class="fa-solid fa-angles-right"></i>
                                </a>
                            @else
                                <span class="dm-page-btn disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
                                <span class="dm-page-btn disabled"><i class="fa-solid fa-angles-right"></i></span>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 3.5rem 1.5rem; color: #64748b;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #eff6ff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; color: #2563eb; margin-bottom: 0.85rem;">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 1.05rem;">Belum Ada Laporan Masuk</div>
                    <div style="font-size: 0.84rem; color: #64748b; margin-top: 0.35rem; max-width: 480px; margin-left: auto; margin-right: auto;">
                        Belum ada laporan Daily Maintenance yang dikirimkan oleh DC/Promotor untuk filter periode ini. Laporan yang di-submit dari aplikasi akan otomatis muncul di sini.
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

{{-- MODERN STYLES --}}
<style>
.dm-executive-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    font-family: inherit;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    margin-bottom: 2rem;
}

/* TOP TOOLBAR */
.dm-top-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
}

.dm-tabs-container {
    background: #e2e8f0;
    padding: 5px;
    border-radius: 12px;
    display: inline-flex;
    gap: 5px;
    flex-wrap: wrap;
}

.dm-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.6rem 1.25rem;
    border-radius: 9px;
    font-size: 0.86rem;
    font-weight: 700;
    color: #475569;
    text-decoration: none;
    background: transparent;
    border: none;
    transition: all 0.2s ease-in-out;
}

.dm-tab-btn:hover {
    background: #f1f5f9;
    color: #0F52BA;
}

.dm-tab-btn.active {
    background: #0F52BA;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15, 82, 186, 0.3);
}

.badge-count {
    background: rgba(0, 0, 0, 0.08);
    color: inherit;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.74rem;
    font-weight: 700;
}

.dm-tab-btn.active .badge-count {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

.dm-export-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.btn-dm-export-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.15rem;
    border-radius: 10px;
    font-size: 0.84rem;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}

.btn-dm-export-action.success {
    color: #166534;
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.btn-dm-export-action.success:hover {
    background: #dcfce7;
    color: #14532d;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(22, 163, 74, 0.15);
}

.btn-dm-export-action.primary {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #eff6ff;
}
.btn-dm-export-action.primary:hover {
    background: #dbeafe;
    color: #1e40af;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.15);
}

/* KPI CARDS GRID */
.dm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    width: 100%;
}

@media (max-width: 1200px) {
    .dm-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .dm-kpi-grid { grid-template-columns: 1fr; }
}

.dm-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.35rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.15rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dm-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}

.dm-kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.dm-kpi-icon.blue   { background: #eff6ff; color: #1d4ed8; }
.dm-kpi-icon.indigo { background: #eef2ff; color: #4338ca; }
.dm-kpi-icon.teal   { background: #f0fdfa; color: #0f766e; }
.dm-kpi-icon.green  { background: #f0fdf4; color: #15803d; }

.dm-kpi-content {
    flex: 1;
    min-width: 0;
}

.dm-kpi-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: block;
    margin-bottom: 2px;
}

.dm-kpi-val {
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin: 2px 0;
}

.dm-kpi-unit {
    font-size: 0.88rem;
    font-weight: 600;
    color: #64748b;
}

.dm-kpi-sub {
    font-size: 0.74rem;
    color: #94a3b8;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* COMPLIANCE SECTION */
.dm-compliance-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    width: 100%;
}

.dm-compliance-header {
    margin-bottom: 1.25rem;
}

.dm-section-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.dm-section-subtitle {
    font-size: 0.82rem;
    color: #64748b;
    margin: 0;
}

.dm-checklist-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    width: 100%;
}

@media (max-width: 1200px) {
    .dm-checklist-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .dm-checklist-grid { grid-template-columns: 1fr; }
}

.dm-checklist-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.15rem 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s ease;
}

.dm-checklist-card:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
}

.dm-chk-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.dm-chk-title {
    font-size: 0.84rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.dm-chk-badge {
    font-size: 0.78rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 6px;
}
.dm-chk-badge.green { background: #dcfce7; color: #166534; }
.dm-chk-badge.cyan  { background: #cffafe; color: #155e75; }
.dm-chk-badge.purple{ background: #f3e8ff; color: #6b21a8; }
.dm-chk-badge.teal  { background: #ccfbf1; color: #115e59; }
.dm-chk-badge.amber { background: #fef3c7; color: #92400e; }

/* PROGRESS BAR STYLES */
.dm-progress-track {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin: 8px 0 8px 0;
    position: relative;
}

.dm-progress-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.dm-progress-fill.color-blue   { background: linear-gradient(90deg, #2563eb, #3b82f6); }
.dm-progress-fill.color-cyan   { background: linear-gradient(90deg, #0891b2, #06b6d4); }
.dm-progress-fill.color-purple { background: linear-gradient(90deg, #7c3aed, #a855f7); }
.dm-progress-fill.color-teal   { background: linear-gradient(90deg, #059669, #10b981); }

.dm-chk-desc {
    font-size: 0.74rem;
    color: #64748b;
    margin: 0;
    line-height: 1.35;
}

/* BREAKDOWN GRIDS */
.dm-breakdowns-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    width: 100%;
}

@media (max-width: 992px) {
    .dm-breakdowns-grid { grid-template-columns: 1fr; }
}

/* CARDS & TABLES */
.dm-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    width: 100%;
}

.dm-card-header {
    padding: 1.15rem 1.5rem;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dm-card-title {
    font-size: 0.98rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.dm-card-badge {
    font-size: 0.74rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

/* SCROLLABLE TABLE CONTAINER (HORIZONTAL & VERTICAL) */
.dm-table-scroll-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    position: relative;
    -webkit-overflow-scrolling: touch;
}

.dm-table-scroll-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.dm-table-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.dm-table-scroll-container::-webkit-scrollbar-track {
    background: #f8fafc;
}

.dm-table {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.83rem;
}

.dm-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafc !important;
    color: #475569;
    font-weight: 700;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.85rem 1rem;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.dm-table tbody td {
    padding: 0.8rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    white-space: nowrap;
    vertical-align: middle;
}

.dm-table tbody tr:hover td {
    background: #f8fafc;
}

.region-badge {
    background: #f1f5f9;
    color: #1e293b;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 0.25rem 0.65rem;
    border-radius: 6px;
}

.serial-badge {
    font-family: monospace;
    font-size: 0.8rem;
    background: #f8fafc;
    color: #334155;
    padding: 0.2rem 0.55rem;
    border-radius: 5px;
    border: 1px solid #e2e8f0;
}

.freq-badge {
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 6px;
}

.meta-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.35rem 0.85rem;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.meta-lbl {
    font-size: 0.76rem;
    font-weight: 600;
    color: #64748b;
}
.meta-val {
    font-size: 0.82rem;
    font-weight: 800;
    color: #0f172a;
}

.bg-light-primary { background: #eff6ff !important; color: #1d4ed8 !important; }
.bg-light-success { background: #f0fdf4 !important; color: #166534 !important; }
.bg-light-warning { background: #fffbeb !important; color: #92400e !important; }
.bg-light-danger  { background: #fef2f2 !important; color: #991b1b !important; }
.bg-light-dark    { background: #f1f5f9 !important; color: #334155 !important; }
.bg-light-secondary { background: #f8fafc !important; color: #64748b !important; }

/* PAGINATION BAR */
.dm-pagination-bar {
    padding: 1.1rem 1.5rem;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.dm-pagination-info {
    font-size: 0.84rem;
    color: #64748b;
    font-weight: 500;
}
.dm-pagination-info strong {
    color: #0f172a;
    font-weight: 700;
}

.dm-pagination-controls {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f8fafc;
    padding: 4px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.dm-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    background: transparent;
    border: 1px solid transparent;
    text-decoration: none;
    transition: all 0.15s ease;
}

.dm-page-btn:hover:not(.disabled):not(.active) {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #0F52BA;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.dm-page-btn.active {
    background: #0F52BA !important;
    color: #ffffff !important;
    border-color: #0F52BA !important;
    font-weight: 800;
    box-shadow: 0 2px 6px rgba(15, 82, 186, 0.3);
}

.dm-page-btn.disabled {
    color: #cbd5e1 !important;
    cursor: not-allowed;
    pointer-events: none;
}

.dm-page-dots {
    padding: 0 6px;
    color: #94a3b8;
    font-weight: 700;
}

/* ACTION BUTTONS & MODAL STYLES */
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

.dm-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.dm-modal-box {
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
.dm-modal-header {
    padding: 1.15rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>

{{-- MODAL TOLAK LAPORAN DAILY MAINTENANCE --}}
<div id="dm_reject_modal" class="dm-modal-backdrop" style="display: none;">
    <div class="dm-modal-box">
        <div class="dm-modal-header">
            <h5 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Tolak Laporan Maintenance
            </h5>
            <button type="button" onclick="closeDmRejectModal()" style="background: none; border: none; font-size: 1.3rem; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form id="dm_reject_form" method="POST" action="" style="padding: 1.5rem; margin: 0;">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem;">Kode Laporan:</label>
                <div id="dm_reject_code" style="font-family: monospace; font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.88rem; border: 1px solid #e2e8f0;"></div>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label for="dm_rejection_note" style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem;">Alasan Penolakan / Catatan: <span style="color: #ef4444;">*</span></label>
                <textarea name="admin_notes" id="dm_rejection_note" rows="3" required placeholder="Tuliskan catatan alasan penolakan agar dapat diperbaiki oleh Promotor / DC..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.6rem 0.75rem; font-size: 0.84rem; outline: none; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="closeDmRejectModal()" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 700; font-size: 0.82rem; cursor: pointer;">Batal</button>
                <button type="submit" style="padding: 0.5rem 1.25rem; border-radius: 8px; border: none; background: #ef4444; color: #fff; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-xmark"></i> Tolak Laporan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL LIGHTBOX FOTO PREVIEW --}}
<div id="dm_photo_modal" class="dm-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeDmPhotoModal();">
    <div class="dm-modal-box" style="max-width: 600px; text-align: center; background: #0f172a; color: #ffffff;">
        <div style="padding: 0.9rem 1.25rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="text-align: left;">
                <div id="dm_photo_title" style="font-weight: 700; font-size: 0.9rem;">Foto Bukti</div>
                <div id="dm_photo_sub" style="font-size: 0.75rem; color: #94a3b8; font-family: monospace;"></div>
            </div>
            <button type="button" onclick="closeDmPhotoModal()" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 1rem; display: flex; justify-content: center; align-items: center; min-height: 250px; max-height: 75vh; overflow: auto;">
            <img id="dm_photo_img" src="" alt="Preview" style="max-width: 100%; max-height: 70vh; border-radius: 8px; object-fit: contain; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
        </div>
        <div style="padding: 0.75rem 1.25rem; background: rgba(0,0,0,0.3); display: flex; justify-content: flex-end;">
            <a id="dm_photo_link" href="" target="_blank" style="color: #60a5fa; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Ukuran Penuh
            </a>
        </div>
    </div>
</div>

<script>
function openDmRejectModal(subId, subCode) {
    var modal = document.getElementById('dm_reject_modal');
    var codeEl = document.getElementById('dm_reject_code');
    var form = document.getElementById('dm_reject_form');
    if (!modal || !form) return;
    if (codeEl) codeEl.innerText = subCode;
    var baseRoute = "{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => ':id', 'p' => $tenantPrincipal->id]) }}";
    form.action = baseRoute.replace(':id', subId);
    modal.style.display = 'flex';
}

function closeDmRejectModal() {
    var modal = document.getElementById('dm_reject_modal');
    if (modal) modal.style.display = 'none';
}

function openDmPhotoModal(url, label, subCode) {
    var modal = document.getElementById('dm_photo_modal');
    var img = document.getElementById('dm_photo_img');
    var title = document.getElementById('dm_photo_title');
    var sub = document.getElementById('dm_photo_sub');
    var link = document.getElementById('dm_photo_link');
    if (!modal || !img) return;
    img.src = url;
    if (title) title.innerText = label || 'Foto Dokumentasi';
    if (sub) sub.innerText = subCode || '';
    if (link) link.href = url;
    modal.style.display = 'flex';
}

function closeDmPhotoModal() {
    var modal = document.getElementById('dm_photo_modal');
    if (modal) modal.style.display = 'none';
}
</script>
