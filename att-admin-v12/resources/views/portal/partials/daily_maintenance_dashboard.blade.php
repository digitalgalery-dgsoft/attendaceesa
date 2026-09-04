{{-- DAILY MAINTENANCE EXECUTIVE DASHBOARD --}}
<div class="dm-executive-wrapper">
    {{-- TOP NAVIGATION & TABS BAR --}}
    <div class="dm-header-card">
        <div class="dm-header-top">
            <div class="dm-title-box">
                <div class="dm-badge-icon">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div>
                    <h2 class="dm-main-title">Laporan Daily Maintenance POST & Mesin Tinting</h2>
                    <p class="dm-sub-title">Monitoring Perawatan Harian, Nozzle Cleaning, Kalibrasi & Program Mix2Win Mesin Tinting Dulux</p>
                </div>
            </div>

            <div class="dm-header-actions">
                {{-- Export Dropdown --}}
                <div class="dropdown d-inline-block">
                    <button class="btn btn-dm-export dropdown-toggle" type="button" id="dmExportMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-file-arrow-down"></i> Export Excel / CSV
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dm-dropdown-menu shadow-sm" aria-labelledby="dmExportMenu">
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'dm_raw', 'p' => $tenantPrincipal->id])) }}">
                                <i class="fa-solid fa-table-list text-primary me-2"></i> Export Data Mentah Submission (CSV)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'dm_stores', 'p' => $tenantPrincipal->id])) }}">
                                <i class="fa-solid fa-store text-success me-2"></i> Export Rekapitulasi Toko & Mesin (CSV)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- TABS NAVIGATION --}}
        <div class="dm-tabs-container">
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'summary', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? 'summary') === 'summary' ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i> Ringkasan & Kepatuhan
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'stores', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? '') === 'stores' ? 'active' : '' }}">
                <i class="fa-solid fa-store"></i> Matriks Toko & Mesin
                <span class="badge-count">{{ number_format($dmData['store_matrix']['total_rows'] ?? 0) }}</span>
            </a>
            <a href="{{ route('portal.report.detail', array_merge(request()->query(), ['code' => $template->code, 'tab' => 'raw', 'p' => $tenantPrincipal->id])) }}" 
               class="dm-tab-btn {{ ($activeTab ?? '') === 'raw' ? 'active' : '' }}">
                <i class="fa-solid fa-list-check"></i> Data Mentah Submission
                <span class="badge-count">{{ number_format($dmData['submissions']['total'] ?? 0) }}</span>
            </a>
        </div>
    </div>

    {{-- ADVANCED FILTER TOOLBAR --}}
    <div class="dm-filter-card">
        <form method="GET" action="{{ route('portal.report.detail', ['code' => $template->code]) }}" id="dmFilterForm" class="dm-filter-grid">
            <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
            <input type="hidden" name="tab" value="{{ $activeTab ?? 'summary' }}">

            {{-- Year Selector --}}
            <div class="dm-filter-group">
                <label class="dm-filter-label"><i class="fa-solid fa-calendar"></i> Tahun</label>
                <select name="start_year" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                    <option value="2026" {{ ($startYear ?? 2026) == 2026 ? 'selected' : '' }}>2026 (Jan - Jul)</option>
                    <option value="2025" {{ ($startYear ?? 2026) == 2025 ? 'selected' : '' }}>2025 (Jan - Des)</option>
                </select>
                <input type="hidden" name="end_year" value="{{ $startYear ?? 2026 }}">
            </div>

            {{-- Month Range Selector --}}
            <div class="dm-filter-group">
                <label class="dm-filter-label"><i class="fa-solid fa-calendar-week"></i> Rentang Bulan</label>
                <div class="d-flex align-items-center gap-1">
                    <select name="start_month" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ ($startMonth ?? 1) == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('M') }}
                            </option>
                        @endfor
                    </select>
                    <span class="text-muted fw-bold">&ndash;</span>
                    <select name="end_month" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ ($endMonth ?? 7) == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('M') }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Region / RSM Area --}}
            <div class="dm-filter-group">
                <label class="dm-filter-label"><i class="fa-solid fa-map"></i> Region / RSM Area</label>
                <select name="region" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                    <option value="">Semua Region</option>
                    @foreach($regions as $reg)
                        <option value="{{ $reg }}" {{ ($selectedRegion ?? '') === $reg ? 'selected' : '' }}>{{ $reg }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Area --}}
            <div class="dm-filter-group">
                <label class="dm-filter-label"><i class="fa-solid fa-location-dot"></i> Area</label>
                <select name="area_id" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                    <option value="">Semua Area</option>
                    @foreach($areas as $ar)
                        @php
                            $arId = is_array($ar) ? ($ar['id'] ?? $ar['name'] ?? '') : (is_object($ar) && !($ar instanceof \__PHP_Incomplete_Class) ? ($ar->id ?? $ar->name ?? '') : (is_string($ar) ? $ar : ''));
                            $arName = is_array($ar) ? ($ar['name'] ?? '') : (is_object($ar) && !($ar instanceof \__PHP_Incomplete_Class) ? ($ar->name ?? '') : (is_string($ar) ? $ar : ''));
                        @endphp
                        <option value="{{ $arId }}" {{ ($selectedAreaId ?? '') == $arId ? 'selected' : '' }}>{{ $arName }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tipe Mesin POST --}}
            <div class="dm-filter-group">
                <label class="dm-filter-label"><i class="fa-solid fa-gears"></i> Tipe Mesin</label>
                <select name="machine_type" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                    <option value="">Semua Mesin</option>
                    @foreach($machineTypes as $mt)
                        <option value="{{ $mt }}" {{ ($selectedMachineType ?? '') === $mt ? 'selected' : '' }}>{{ $mt }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Kategori Toko --}}
            <div class="dm-filter-group">
                <label class="dm-filter-label"><i class="fa-solid fa-tag"></i> Kategori Toko</label>
                <select name="category" class="form-select dm-filter-select" onchange="document.getElementById('dmFilterForm').submit()">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ ($selectedCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Search Box & Submit --}}
            <div class="dm-filter-group dm-filter-search">
                <label class="dm-filter-label"><i class="fa-solid fa-magnifying-glass"></i> Cari Toko / Serial Mesin / Petugas</label>
                <div class="input-group">
                    <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control dm-filter-input" placeholder="Nama toko, SAP, No mesin...">
                    <button class="btn btn-dm-search" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    @if(!empty($search) || !empty($selectedRegion) || !empty($selectedAreaId) || !empty($selectedMachineType) || !empty($selectedCategory))
                        <a href="{{ route('portal.report.detail', ['code' => $template->code, 'tab' => $activeTab ?? 'summary', 'p' => $tenantPrincipal->id]) }}" class="btn btn-dm-reset" title="Reset Filter">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
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
            <h4 class="dm-section-title"><i class="fa-solid fa-list-check text-primary"></i> Ringkasan Kepatuhan Prosedur Perawatan</h4>
            <div class="dm-checklist-grid">
                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-fill-drip text-primary"></i> Cek & Isi Tinta</span>
                        <span class="dm-chk-badge green">{{ number_format($dmData['kpis']['tinta_rate'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="progress dm-progress">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $dmData['kpis']['tinta_rate'] ?? 0) }}%"></div>
                    </div>
                    <p class="dm-chk-desc">Pengecekan level tinta tabung & pengisian jika dibutuhkan.</p>
                </div>

                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-broom text-info"></i> Nozzle & Cup Cleaning</span>
                        <span class="dm-chk-badge cyan">{{ number_format($dmData['kpis']['nozzle_rate'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="progress dm-progress">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ min(100, $dmData['kpis']['nozzle_rate'] ?? 0) }}%"></div>
                    </div>
                    <p class="dm-chk-desc">Pembersihan ujung nozzle, cuci cup & spons (D200 / Brush).</p>
                </div>

                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-laptop-code text-indigo"></i> Prosedur Mix2Win</span>
                        <span class="dm-chk-badge purple">{{ number_format($dmData['kpis']['mix2win_rate'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="progress dm-progress">
                        <div class="progress-bar bg-purple" role="progressbar" style="width: {{ min(100, $dmData['kpis']['mix2win_rate'] ?? 0) }}%"></div>
                    </div>
                    <p class="dm-chk-desc">Kepatuhan 12 langkah sirkulasi tinter pada software Mix2Win.</p>
                </div>

                <div class="dm-checklist-card">
                    <div class="dm-chk-head">
                        <span class="dm-chk-title"><i class="fa-solid fa-soap text-success"></i> Pembersihan Unit & PC</span>
                        <span class="dm-chk-badge teal">{{ number_format($dmData['kpis']['pembersihan_rate'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="progress dm-progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, $dmData['kpis']['pembersihan_rate'] ?? 0) }}%"></div>
                    </div>
                    <p class="dm-chk-desc">Pembersihan bodi mesin tinting, shaker & komputer toko.</p>
                </div>
            </div>
        </div>

        {{-- BREAKDOWNS (MACHINE TYPE, CATEGORY & REGION) --}}
        <div class="row g-4 mt-1">
            {{-- By Machine Type --}}
            <div class="col-lg-6">
                <div class="dm-card h-100">
                    <div class="dm-card-header">
                        <h5 class="dm-card-title"><i class="fa-solid fa-gears text-primary"></i> Sebaran per Tipe Mesin POST</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table dm-table table-hover align-middle mb-0">
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
            </div>

            {{-- By Store Category --}}
            <div class="col-lg-6">
                <div class="dm-card h-100">
                    <div class="dm-card-header">
                        <h5 class="dm-card-title"><i class="fa-solid fa-tags text-success"></i> Sebaran per Kategori Toko</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table dm-table table-hover align-middle mb-0">
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
            <div class="col-12">
                <div class="dm-card">
                    <div class="dm-card-header">
                        <h5 class="dm-card-title"><i class="fa-solid fa-map-location-dot text-indigo"></i> Rekapitulasi per Regional (RSM Area)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table dm-table table-hover align-middle mb-0">
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
                    <p class="text-muted small mb-0">Rekapitulasi frekuensi maintenance, serial mesin, dan skor kepatuhan per toko.</p>
                </div>
                <div class="meta-pill">
                    <span class="meta-lbl">Total Unit Terdata:</span>
                    <strong class="meta-val">{{ number_format($dmData['store_matrix']['total_rows'] ?? 0) }} Mesin</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table dm-table table-hover align-middle mb-0">
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

            <div class="table-responsive">
                <table class="table dm-table table-hover align-middle mb-0">
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
                                <td class="small fw-semibold text-dark" style="white-space: nowrap;">{{ $r['submission_date'] }}</td>
                                <td class="small text-muted" style="white-space: nowrap;">{{ $r['tanggal_report'] }}</td>
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
                                <td class="small text-muted" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $r['kesimpulan'] }}">
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
    gap: 1.25rem;
    font-family: inherit;
}
.dm-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.dm-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.dm-title-box {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.dm-badge-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0F52BA 0%, #0284c7 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(15, 82, 186, 0.25);
}
.dm-main-title {
    font-size: 1.3rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}
.dm-sub-title {
    font-size: 0.84rem;
    color: #64748b;
    margin: 0;
}
.btn-dm-export {
    background: #0F52BA;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.84rem;
    padding: 0.55rem 1.15rem;
    border-radius: 9px;
    border: none;
    transition: all 0.15s ease;
}
.btn-dm-export:hover {
    background: #0b3d88;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15, 82, 186, 0.3);
}
.dm-tabs-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-top: 1px solid #f1f5f9;
    padding-top: 1rem;
    flex-wrap: wrap;
}
.dm-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.1rem;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
    text-decoration: none;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.15s ease;
}
.dm-tab-btn:hover {
    background: #f1f5f9;
    color: #0F52BA;
}
.dm-tab-btn.active {
    background: #0F52BA;
    color: #ffffff;
    border-color: #0F52BA;
    box-shadow: 0 2px 8px rgba(15, 82, 186, 0.25);
}
.badge-count {
    background: rgba(255,255,255,0.25);
    color: inherit;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.72rem;
}
.dm-tab-btn:not(.active) .badge-count {
    background: #e2e8f0;
    color: #475569;
}

/* Filter Card */
.dm-filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.dm-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}
.dm-filter-search {
    grid-column: span 2;
}
@media (max-width: 992px) {
    .dm-filter-search { grid-column: span 1; }
}
.dm-filter-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 4px;
}
.dm-filter-select, .dm-filter-input {
    font-size: 0.82rem;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 0.45rem 0.75rem;
    font-weight: 600;
}
.btn-dm-search {
    background: #0F52BA;
    color: #ffffff;
    border: none;
    padding: 0 0.85rem;
    border-radius: 0 8px 8px 0;
}
.btn-dm-reset {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #cbd5e1;
    border-left: none;
    display: flex;
    align-items: center;
    padding: 0 0.75rem;
    border-radius: 0 8px 8px 0;
}

/* KPI Cards */
.dm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}
.dm-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.4rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.dm-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.dm-kpi-icon.blue { background: #eff6ff; color: #1d4ed8; }
.dm-kpi-icon.indigo { background: #eef2ff; color: #4338ca; }
.dm-kpi-icon.teal { background: #f0fdfa; color: #0f766e; }
.dm-kpi-icon.green { background: #f0fdf4; color: #15803d; }
.dm-kpi-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.dm-kpi-val {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin: 2px 0;
}
.dm-kpi-unit {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
}
.dm-kpi-sub {
    font-size: 0.74rem;
    color: #94a3b8;
}

/* Compliance Section */
.dm-compliance-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.35rem 1.5rem;
}
.dm-section-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.dm-checklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}
.dm-checklist-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.15rem;
}
.dm-chk-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.dm-chk-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 5px;
}
.dm-chk-badge {
    font-size: 0.76rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 6px;
}
.dm-chk-badge.green { background: #dcfce7; color: #166534; }
.dm-chk-badge.cyan { background: #cffafe; color: #155e75; }
.dm-chk-badge.purple { background: #f3e8ff; color: #6b21a8; }
.dm-chk-badge.teal { background: #ccfbf1; color: #115e59; }
.dm-progress {
    height: 7px;
    border-radius: 4px;
    margin-bottom: 0.45rem;
    background: #e2e8f0;
}
.bg-purple { background-color: #9333ea !important; }
.dm-chk-desc {
    font-size: 0.72rem;
    color: #64748b;
    margin: 0;
    line-height: 1.3;
}

/* Card & Table */
.dm-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.dm-card-header {
    padding: 1.15rem 1.5rem;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}
.dm-card-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.dm-table {
    margin: 0;
    font-size: 0.82rem;
}
.dm-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
}
.dm-table tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
.region-badge {
    background: #f1f5f9;
    color: #1e293b;
    font-weight: 700;
    font-size: 0.76rem;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
}
.serial-badge {
    font-family: monospace;
    font-size: 0.78rem;
    background: #f8fafc;
    color: #334155;
    padding: 0.2rem 0.5rem;
    border-radius: 5px;
    border: 1px solid #e2e8f0;
}
.freq-badge {
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
}
.bg-light-primary { background: #eff6ff !important; color: #1d4ed8 !important; }
.bg-light-success { background: #f0fdf4 !important; color: #166534 !important; }
.bg-light-warning { background: #fffbeb !important; color: #92400e !important; }
.bg-light-danger { background: #fef2f2 !important; color: #991b1b !important; }
.bg-light-dark { background: #f1f5f9 !important; color: #334155 !important; }

/* Pagination Bar */
.dm-pagination-bar {
    padding: 1rem 1.5rem;
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
