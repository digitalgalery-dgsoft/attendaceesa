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
</style>
