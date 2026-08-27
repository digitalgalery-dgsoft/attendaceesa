@extends('portal.layout')

@php
    $brandColor = $brandColor ?? ($tenantPrincipal->theme_color ?? '#0F52BA');
    $selectedEmployee = $employees->firstWhere('id', $employeeId);
    $verifiedPercent = $totalSubmissions > 0 ? round(($approvedSubmissions / $totalSubmissions) * 100) : 0;
@endphp

@section('title', 'Sales Summary Dashboard - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Sales Summary Dashboard')
@section('breadcrumb_active', 'Executive Overview')

@push('styles')
<style>
    /* Filter Bar */
    .filter-bar {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 0.85rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.75rem;
        box-shadow: var(--shadow-sm);
        flex-wrap: wrap;
    }

    .filter-group-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-select-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.95rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-heading);
        outline: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .filter-select-btn:hover, .filter-select-btn:focus {
        background: #ffffff;
        border-color: var(--brand-primary);
    }

    /* SEARCHABLE ALPINE DROPDOWN */
    .searchable-dropdown-wrap {
        position: relative;
        min-width: 260px;
    }

    .searchable-trigger-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.5rem 0.95rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-heading);
        cursor: pointer;
        text-align: left;
        transition: all 0.2s ease;
    }
    .searchable-trigger-btn:hover {
        background: #ffffff;
        border-color: var(--brand-primary);
    }

    .searchable-dropdown-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        width: 340px;
        max-height: 380px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        z-index: 100;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: fadeIn 0.15s ease;
    }

    .search-input-box {
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid var(--border-color);
        background: #f8fafc;
    }
    .search-inner-input {
        width: 100%;
        padding: 0.45rem 0.75rem;
        font-size: 0.82rem;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        outline: none;
        font-family: inherit;
    }
    .search-inner-input:focus {
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 2px var(--brand-glow);
    }

    .searchable-options-list {
        overflow-y: auto;
        max-height: 300px;
        padding: 0.35rem 0;
    }

    .area-group-header {
        padding: 0.45rem 0.85rem 0.25rem;
        font-size: 0.72rem;
        font-weight: 800;
        color: #0F52BA;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: rgba(15, 82, 186, 0.04);
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .area-group-header:first-child {
        border-top: none;
    }

    .searchable-option-item {
        padding: 0.5rem 0.85rem;
        font-size: 0.83rem;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 2px;
        transition: background 0.12s ease;
    }
    .searchable-option-item:hover, .searchable-option-item.selected {
        background: #f1f5f9;
    }
    .option-emp-name {
        font-weight: 700;
        color: var(--text-heading);
    }
    .option-emp-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-update-refresh {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1.25rem;
        background: var(--brand-primary);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 2px 8px var(--brand-glow);
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-update-refresh:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }

    /* KPI Grid */
    .kpi-main-grid {
        display: grid;
        grid-template-columns: 1.8fr 1fr 1fr 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .kpi-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.35rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        transition: all 0.25s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        border-color: var(--border-hover);
        box-shadow: var(--shadow-md);
    }

    .kpi-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .kpi-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-big-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
        letter-spacing: -0.5px;
        margin-bottom: 0.65rem;
    }

    .kpi-sub-text {
        font-size: 0.78rem;
        color: var(--text-muted);
        line-height: 1.4;
    }

    /* Sales Main Card Specific */
    .sales-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .gauge-wrapper {
        position: relative;
        width: 76px;
        height: 76px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .gauge-svg {
        transform: rotate(-90deg);
        width: 76px;
        height: 76px;
    }

    .gauge-circle-bg {
        fill: none;
        stroke: #f1f5f9;
        stroke-width: 7;
    }

    .gauge-circle-progress {
        fill: none;
        stroke: #16a34a;
        stroke-width: 7;
        stroke-linecap: round;
        transition: stroke-dashoffset 0.8s ease;
    }

    .gauge-text {
        position: absolute;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--text-heading);
    }

    .sales-metrics-sub {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 0.65rem;
    }

    .metric-sub-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .metric-sub-val {
        font-size: 0.92rem;
        font-weight: 800;
        color: var(--text-heading);
    }

    .progress-bar-container {
        margin-top: 0.65rem;
    }

    .progress-bar-label {
        display: flex;
        justify-content: space-between;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .progress-track {
        height: 6px;
        background: #f1f5f9;
        border-radius: 9999px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: #16a34a;
        border-radius: 9999px;
    }

    /* Growth Badge */
    .growth-positive {
        color: #16a34a;
        font-weight: 800;
    }
    .growth-negative {
        color: #dc2626;
        font-weight: 800;
    }

    /* Charts Row */
    .charts-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .chart-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }

    .chart-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-heading);
    }

    /* Table Card */
    .table-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .table-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .custom-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        text-align: left;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: var(--text-body);
        vertical-align: middle;
    }

    .custom-table tr:hover td {
        background: #f8fafc;
    }

    @media (max-width: 1300px) {
        .kpi-main-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .charts-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .kpi-main-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

    <!-- Filter Bar -->
    <form id="dashboardFilterForm" action="{{ route('portal.dashboard') }}" method="GET" class="filter-bar">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        
        <div class="filter-group-left">
            <!-- Month Selector -->
            <select name="month" class="filter-select-btn" onchange="this.form.submit()">
                @for ($m = 1; $m <= 12; $m++)
                    @php
                        $dateObj = Carbon\Carbon::create(null, $m, 1);
                    @endphp
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ $dateObj->translatedFormat('F') }} {{ $year }}
                    </option>
                @endfor
            </select>

            <!-- SEARCHABLE GROUPED EMPLOYEE DROPDOWN -->
            <div class="searchable-dropdown-wrap" x-data="{
                open: false,
                search: '',
                selectedId: '{{ $employeeId }}',
                selectedLabel: '{{ $selectedEmployee ? $selectedEmployee->full_name . ' - ' . ($selectedEmployee->position?->name ?? 'SPG/MD') : '👥 Semua Karyawan / Promotor' }}',
                selectEmployee(id, label) {
                    this.selectedId = id;
                    this.selectedLabel = label;
                    this.open = false;
                    document.getElementById('hiddenEmpInput').value = id;
                    document.getElementById('dashboardFilterForm').submit();
                }
            }" @click.away="open = false">
                <input type="hidden" id="hiddenEmpInput" name="employee_id" :value="selectedId">
                
                <button type="button" class="searchable-trigger-btn" @click="open = !open">
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="selectedLabel"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem; color: var(--text-muted);"></i>
                </button>

                <div class="searchable-dropdown-menu" x-show="open" x-cloak style="display: none;">
                    <div class="search-input-box">
                        <input type="text" class="search-inner-input" placeholder="🔍 Cari nama, NIK, atau cabang..." x-model="search" @click.stop>
                    </div>

                    <div class="searchable-options-list">
                        <!-- Option All -->
                        <div class="searchable-option-item" @click="selectEmployee('', '👥 Semua Karyawan / Promotor')" x-show="search === '' || 'semua'.includes(search.toLowerCase())">
                            <span class="option-emp-name" style="color: #0F52BA;">👥 Semua Karyawan / Promotor</span>
                            <span class="option-emp-sub">Tampilkan seluruh data promotor</span>
                        </div>

                        <!-- Grouped by Area / Branch -->
                        @foreach($groupedEmployees as $branchName => $branchEmps)
                            <div class="area-group-header">
                                <i class="fa-solid fa-map-pin"></i>
                                <span>{{ $branchName }} ({{ $branchEmps->count() }})</span>
                            </div>

                            @foreach($branchEmps as $emp)
                                @php
                                    $posName = $emp->position?->name ?? 'Promotor / SPG';
                                    $empFullLabel = $emp->full_name . ' - ' . $posName;
                                    $searchableText = strtolower($emp->full_name . ' ' . $emp->nik . ' ' . $posName . ' ' . $branchName);
                                @endphp
                                <div class="searchable-option-item" 
                                     x-show="search === '' || '{{ addslashes($searchableText) }}'.includes(search.toLowerCase())"
                                     @click="selectEmployee('{{ $emp->id }}', '{{ addslashes($empFullLabel) }}')"
                                     :class="{ 'selected': selectedId == '{{ $emp->id }}' }">
                                    <span class="option-emp-name">{{ $emp->full_name }} - <span style="font-weight: 500; color: #64748b;">{{ $posName }}</span></span>
                                    <span class="option-emp-sub">NIK: {{ $emp->nik ?? '-' }} &bull; Area: {{ $branchName }}</span>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Location Dropdown -->
            <select name="location_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">🏢 Semua Toko / Outlet</option>
                @foreach($workLocations as $loc)
                    <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="btn-update-refresh">
                <i class="fa-solid fa-arrows-rotate"></i>
                Update Data
            </button>
        </div>
    </form>

    <!-- KPI Main Cards Grid (100% REAL DATA, NO DUMMY) -->
    <div class="kpi-main-grid">
        <!-- 1. Total Aktivitas Laporan Masuk -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-title">Aktivitas Laporan Masuk</span>
                <span class="kpi-title" style="color: #16a34a;"><i class="fa-solid fa-circle-check"></i> Verifikasi</span>
            </div>

            <div class="sales-top-row">
                <div class="kpi-big-value">
                    {{ number_format($totalSubmissions) }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Laporan</span>
                </div>

                <!-- Circular SVG Donut Gauge -->
                <div class="gauge-wrapper" title="{{ $verifiedPercent }}% Laporan Terverifikasi Valid">
                    @php
                        $radius = 31;
                        $circumference = 2 * pi() * $radius;
                        $offset = $circumference - ($verifiedPercent / 100) * $circumference;
                    @endphp
                    <svg class="gauge-svg" viewBox="0 0 76 76">
                        <circle class="gauge-circle-bg" cx="38" cy="38" r="{{ $radius }}"></circle>
                        <circle class="gauge-circle-progress" cx="38" cy="38" r="{{ $radius }}" 
                            stroke-dasharray="{{ $circumference }}" 
                            stroke-dashoffset="{{ $offset }}"></circle>
                    </svg>
                    <div class="gauge-text">{{ $verifiedPercent }}%</div>
                </div>
            </div>

            <div class="sales-metrics-sub">
                <div>
                    <div class="metric-sub-label" style="color: #16a34a;">Valid</div>
                    <div class="metric-sub-val" style="color: #16a34a;">{{ number_format($approvedSubmissions) }}</div>
                </div>
                <div>
                    <div class="metric-sub-label" style="color: #b45309;">Menunggu</div>
                    <div class="metric-sub-val" style="color: #b45309;">{{ number_format($pendingSubmissions) }}</div>
                </div>
                <div>
                    <div class="metric-sub-label" style="color: #dc2626;">Ditolak</div>
                    <div class="metric-sub-val" style="color: #dc2626;">{{ number_format($rejectedSubmissions) }}</div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar-label">
                    <span>Tingkat Verifikasi</span>
                    <span style="font-weight: 700; color: #16a34a;">{{ $verifiedPercent }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: {{ $verifiedPercent }}%;"></div>
                </div>
            </div>
        </div>

        <!-- 2. Pertumbuhan vs Bulan Lalu -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-title">Pertumbuhan Laporan</span>
            </div>
            <div class="kpi-big-value {{ $growthPercent >= 0 ? 'growth-positive' : 'growth-negative' }}">
                {{ $growthPercent >= 0 ? '+' : '' }}{{ $growthPercent }}%
            </div>
            <div class="kpi-sub-text">
                Bandingkan dengan <strong>{{ number_format($prevSubmissions) }}</strong> laporan pada bulan {{ Carbon\Carbon::create(null, $month, 1)->subMonth()->translatedFormat('F') }}
            </div>
        </div>

        <!-- 3. Total Promotor / Karyawan -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-title">Tenaga Lapangan</span>
            </div>
            <div class="kpi-big-value">
                {{ number_format($totalEmployees) }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Total</span>
            </div>
            <div class="kpi-sub-text">
                <strong style="color: #16a34a;">{{ number_format($activeEmployees) }}</strong> Aktif &bull; <span style="color: var(--text-muted);">{{ number_format($resignedEmployees) }} Nonaktif</span>
            </div>
        </div>

        <!-- 4. Modul Pelaporan SOP -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-title">Modul SOP</span>
            </div>
            <div class="kpi-big-value">
                {{ $activeTemplates->count() }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Form</span>
            </div>
            <div class="kpi-sub-text">
                Formulir pelaporan aktif yang dapat diakses promotor
            </div>
        </div>

        <!-- 5. Toko / Outlet Terkunjungi -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-title">Cakupan Outlet</span>
            </div>
            <div class="kpi-big-value">
                {{ number_format($totalStores) }} <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Toko</span>
            </div>
            <div class="kpi-sub-text">
                Toko unik yang dikunjungi promotor periode ini
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <!-- 1. Submission Trend Area Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Tren Laporan Masuk & Aktivitas Harian</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Total laporan harian SPG / MD selama bulan {{ Carbon\Carbon::create(null, $month, 1)->translatedFormat('F Y') }}</p>
                </div>
            </div>
            <div id="chartSubmissionsTrend" style="min-height: 280px;"></div>
        </div>

        <!-- 2. Category Distribution Donut Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Distribusi Kategori Laporan</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Proporsi jenis formulir yang dikirim</p>
                </div>
            </div>
            <div id="chartCategoryDonut" style="min-height: 280px;"></div>
        </div>
    </div>

    <!-- Recent Submissions Table -->
    <div class="table-card">
        <div class="table-header-row">
            <div>
                <h3 class="chart-title">Laporan Terbaru Masuk (Live Submissions)</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Aktivitas pengiriman laporan terkini dari lapangan</p>
            </div>
        </div>

        @if($recentSubmissions->isNotEmpty())
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;">No. Laporan</th>
                            <th>Formulir / Template</th>
                            <th>Petugas (SPG/MD)</th>
                            <th>Toko / Outlet</th>
                            <th>Waktu Submit</th>
                            <th style="text-align: center;">Validasi GPS</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSubmissions as $sub)
                            @php
                                $status = $sub->status ?? 'pending';
                                $storeName = $sub->workLocation?->name ?? $sub->itineraryItem?->destination ?? $sub->store_name ?? 'Kunjungan Toko';
                            @endphp
                            <tr>
                                <td>
                                    <span style="font-family: monospace; font-weight: 700; font-size: 0.78rem; background: rgba(15, 82, 186, 0.08); color: #0F52BA; padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(15, 82, 186, 0.2);">
                                        {{ $sub->submission_code }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->template?->title ?? 'Form SOP' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ ucfirst($sub->template?->category ?? 'General') }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->employee?->full_name ?? $sub->employee?->name ?? 'Petugas' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->employee?->position?->name ?? 'SPG/MD' }} ({{ $sub->employee?->nik ?? '-' }})
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $storeName }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->employee?->branch?->name ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y') : '-' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->submitted_at ? $sub->submitted_at->format('H:i:s') : '-' }} WIB
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    @if($sub->is_within_radius)
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                                            <i class="fa-solid fa-circle-check"></i> Valid
                                        </span>
                                    @else
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Luar Radius
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if(in_array($status, ['approved', 'verified']))
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                            Terverifikasi
                                        </span>
                                    @elseif($status === 'rejected')
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                            Ditolak
                                        </span>
                                    @else
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                            Menunggu
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    @if($sub->template)
                                        <a href="{{ route('portal.report.submission', ['code' => $sub->template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; background: #f1f5f9; color: #0F52BA; border: 1px solid #cbd5e1; transition: all 0.15s ease;" onmouseover="this.style.background='#0F52BA'; this.style.color='#fff';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#0F52BA';">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.25rem;">
                {{ $recentSubmissions->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-heading);">Belum Ada Laporan Terkirim</div>
                <p style="font-size: 0.85rem; max-width: 400px; margin: 0.25rem auto 0;">
                    Laporan dari promotor lapangan pada periode ini akan langsung muncul secara realtime di tabel ini.
                </p>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Line / Area Chart: Daily Activity
        var chartSubmissionsOptions = {
            series: [{
                name: 'Laporan Masuk',
                data: {!! json_encode($chartSubmissions) !!}
            }],
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Outfit, sans-serif'
            },
            colors: ['#0F52BA'],
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 2.5
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: {!! json_encode($chartLabels) !!},
                labels: {
                    style: { colors: '#64748b', fontSize: '11px' },
                    rotate: 0,
                    hideOverlappingLabels: true
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748b', fontSize: '11px' },
                    formatter: function (val) { return parseInt(val); }
                },
                min: 0
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: function (val) { return val + ' Laporan'; }
                }
            }
        };

        var chartSubmissions = new ApexCharts(document.querySelector("#chartSubmissionsTrend"), chartSubmissionsOptions);
        chartSubmissions.render();

        // 2. Donut Chart: Category Distribution
        @php
            $catLabels = array_keys($categoryBreakdown);
            $catSeries = array_values($categoryBreakdown);
            if (empty($catSeries)) {
                $catLabels = ['Belum Ada Laporan'];
                $catSeries = [1];
            }
        @endphp

        var chartCategoryOptions = {
            series: {!! json_encode($catSeries) !!},
            labels: {!! json_encode(array_map('ucfirst', $catLabels)) !!},
            chart: {
                type: 'donut',
                height: 280,
                fontFamily: 'Outfit, sans-serif'
            },
            colors: ['#0F52BA', '#16a34a', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'],
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                fontSize: '12px',
                labels: { colors: '#334155' }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '13px',
                                color: '#64748b',
                                formatter: function (w) {
                                    return {{ $totalSubmissions }};
                                }
                            }
                        }
                    }
                }
            },
            stroke: { width: 2, colors: ['#ffffff'] }
        };

        var chartCategory = new ApexCharts(document.querySelector("#chartCategoryDonut"), chartCategoryOptions);
        chartCategory.render();
    });
</script>
@endpush
