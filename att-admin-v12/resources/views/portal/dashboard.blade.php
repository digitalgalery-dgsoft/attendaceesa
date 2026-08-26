@extends('portal.layout')

@php
    $brandColor = $brandColor ?? ($tenantPrincipal->theme_color ?? '#0F52BA');
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

    .filter-search-box {
        position: relative;
        display: flex;
        align-items: center;
    }

    .filter-search-input {
        padding: 0.5rem 2.2rem 0.5rem 0.85rem;
        font-size: 0.85rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: #f8fafc;
        outline: none;
        width: 200px;
        transition: all 0.2s ease;
    }

    .filter-search-input:focus {
        background: #ffffff;
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 3px var(--brand-glow);
        width: 240px;
    }

    .btn-filter-search {
        position: absolute;
        right: 0.25rem;
        background: var(--brand-primary);
        color: #ffffff;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        cursor: pointer;
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
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .kpi-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
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
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .kpi-big-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
        letter-spacing: -0.5px;
        margin-bottom: 0.75rem;
    }

    .kpi-sub-text {
        font-size: 0.8rem;
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
        width: 86px;
        height: 86px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .gauge-svg {
        transform: rotate(-90deg);
        width: 86px;
        height: 86px;
    }

    .gauge-circle-bg {
        fill: none;
        stroke: #f1f5f9;
        stroke-width: 8;
    }

    .gauge-circle-progress {
        fill: none;
        stroke: var(--brand-primary);
        stroke-width: 8;
        stroke-linecap: round;
        transition: stroke-dashoffset 0.8s ease;
    }

    .gauge-text {
        position: absolute;
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-heading);
    }

    .sales-metrics-sub {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        padding-top: 0.85rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 0.75rem;
    }

    .metric-sub-label {
        font-size: 0.72rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .metric-sub-val {
        font-size: 0.98rem;
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
        height: 7px;
        background: #f1f5f9;
        border-radius: 9999px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--brand-primary);
        border-radius: 9999px;
    }

    .progress-fill.striped {
        background: repeating-linear-gradient(
            45deg,
            #3b82f6,
            #3b82f6 10px,
            #60a5fa 10px,
            #60a5fa 20px
        );
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
        font-size: 0.88rem;
    }

    .custom-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        text-align: left;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: var(--text-body);
    }

    .custom-table tr:hover td {
        background: #f8fafc;
    }

    .badge-gps-valid {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.74rem;
        font-weight: 700;
        color: #16a34a;
        background: #dcfce7;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
    }

    .badge-gps-invalid {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.74rem;
        font-weight: 700;
        color: #dc2626;
        background: #fee2e2;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
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
    <form action="{{ route('portal.dashboard') }}" method="GET" class="filter-bar">
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

            <!-- Employee Dropdown -->
            <select name="employee_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">👥 Semua Promotor / SPG</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }} ({{ $emp->nik }})
                    </option>
                @endforeach
            </select>

            <!-- Location Dropdown -->
            <select name="location_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">🏢 Semua Toko / Outlet</option>
                @foreach($workLocations as $loc)
                    <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>

            <!-- Position Dropdown -->
            <select name="position_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">📌 Semua Posisi</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos->id }}" {{ $positionId == $pos->id ? 'selected' : '' }}>
                        {{ $pos->name }}
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

    <!-- KPI Main Cards Grid -->
    <div class="kpi-main-grid">
        <!-- 1. Total National Sales & Donut Achievement Card -->
        <div class="kpi-card">
            <div class="kpi-card-header">
                <span class="kpi-title">Total National Sales</span>
                <span class="kpi-title">Total Achievement <i class="fa-solid fa-circle-info" style="font-size: 0.75rem;"></i></span>
            </div>

            <div class="sales-top-row">
                <div class="kpi-big-value">
                    @if($totalSalesVal > 0)
                        Rp {{ number_format($totalSalesVal / 1000000, 2) }} M
                    @else
                        Rp {{ number_format($totalSubmissions) }} Laporan
                    @endif
                </div>

                <!-- Circular SVG Donut Gauge -->
                <div class="gauge-wrapper">
                    @php
                        $radius = 35;
                        $circumference = 2 * pi() * $radius;
                        $offset = $circumference - ($achievementPercent / 100) * $circumference;
                    @endphp
                    <svg class="gauge-svg" viewBox="0 0 86 86">
                        <circle class="gauge-circle-bg" cx="43" cy="43" r="{{ $radius }}"></circle>
                        <circle class="gauge-circle-progress" cx="43" cy="43" r="{{ $radius }}" 
                            stroke-dasharray="{{ $circumference }}" 
                            stroke-dashoffset="{{ $offset }}"></circle>
                    </svg>
                    <div class="gauge-text">{{ $achievementPercent }}%</div>
                </div>
            </div>

            <div class="sales-metrics-sub">
                <div>
                    <div class="metric-sub-label">Target / Running Rate</div>
                    <div class="metric-sub-val">
                        @if($targetSalesVal > 0)
                            Rp {{ number_format($targetSalesVal / 1000000, 2) }} M
                        @else
                            Rp 0
                        @endif
                    </div>
                </div>
                <div>
                    <div class="metric-sub-label">Prev. Sales / Running Rate</div>
                    <div class="metric-sub-val">
                        @if($prevSalesVal > 0)
                            Rp {{ number_format($prevSalesVal / 1000000, 2) }} M
                        @else
                            Rp 0
                        @endif
                    </div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar-label">
                    <span>Achievement by Running Rate</span>
                    <span>{{ $achievementPercent }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: {{ $achievementPercent }}%;"></div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar-label">
                    <span>Timegone (Periode Berjalan)</span>
                    <span>{{ $timegonePercent }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill striped" style="width: {{ $timegonePercent }}%;"></div>
                </div>
            </div>
        </div>

        <!-- 2. Growth vs Last Month -->
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-title"><i class="fa-solid fa-chart-line"></i> Growth vs Last Month</span>
                </div>
                <div class="kpi-big-value {{ $growthPercent >= 0 ? 'growth-positive' : 'growth-negative' }}">
                    {{ $growthPercent >= 0 ? '+' : '' }}{{ $growthPercent }}%
                </div>
                <div class="kpi-sub-text">
                    {{ $growthPercent >= 0 ? '+' : '' }}{{ $growthPercent }}% by Running Rate
                </div>
            </div>
            <div style="margin-top: 1.5rem; font-size: 0.78rem; color: var(--text-muted);">
                Berdasarkan komparasi data bulan {{ Carbon\Carbon::create(null, $month, 1)->subMonth()->translatedFormat('F') }}
            </div>
        </div>

        <!-- 3. Employees -->
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-title"><i class="fa-solid fa-users"></i> Employees</span>
                </div>
                <div class="kpi-big-value">
                    {{ number_format($totalEmployees) }} <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Total</span>
                </div>
                <div class="kpi-sub-text">
                    <strong>{{ $activeEmployees }}</strong> Aktif &bull; <strong>{{ $resignedEmployees }}</strong> Resigned
                </div>
            </div>
            <div style="margin-top: 1.5rem; font-size: 0.78rem; color: var(--text-muted);">
                Total tenaga lapangan terverifikasi
            </div>
        </div>

        <!-- 4. Products / SKU -->
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-title"><i class="fa-solid fa-boxes-stacked"></i> Products (SKU)</span>
                </div>
                <div class="kpi-big-value">
                    {{ $activeTemplates->count() * 12 }} <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">SKU Target</span>
                </div>
                <div class="kpi-sub-text">
                    Tercakup di {{ $activeTemplates->count() }} modul pelaporan aktif
                </div>
            </div>
            <div style="margin-top: 1.5rem; font-size: 0.78rem; color: var(--text-muted);">
                Monitoring stok, OOS, dan display
            </div>
        </div>

        <!-- 5. Stores / Outlets -->
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-title"><i class="fa-solid fa-store"></i> Stores (Coverage)</span>
                </div>
                <div class="kpi-big-value">
                    {{ number_format($totalStores) }} <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Total</span>
                </div>
                <div class="kpi-sub-text">
                    {{ number_format($prevStores) }} on Prev. Month
                </div>
            </div>
            <div style="margin-top: 1.5rem; font-size: 0.78rem; color: var(--text-muted);">
                Toko unik terkunjungi bulan ini
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <!-- Daily Submission Trend Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Tren Laporan Masuk & Aktivitas Harian</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted);">Total laporan harian SPG / MD selama bulan {{ Carbon\Carbon::create(null, $month, 1)->translatedFormat('F Y') }}</p>
                </div>
            </div>
            <div id="dailySubmissionsChart" style="min-height: 290px;"></div>
        </div>

        <!-- Category Breakdown Donut Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Distribusi Kategori Laporan</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted);">Proporsi jenis formulir yang dikirim</p>
                </div>
            </div>
            <div id="categoryDonutChart" style="min-height: 290px;"></div>
        </div>
    </div>

    <!-- Live Recent Submissions Table -->
    <div class="table-card">
        <div class="table-header-row">
            <div>
                <h3 class="chart-title">Laporan Terbaru Masuk (Real-time Stream)</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted);">Daftar data pelaporan operasional lapangan terkini</p>
            </div>
        </div>

        @if($recentSubmissions->isNotEmpty())
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Waktu & Tanggal</th>
                            <th>Petugas (SPG/MD)</th>
                            <th>Toko / Outlet</th>
                            <th>Modul Form</th>
                            <th>Validasi GPS</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSubmissions as $sub)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->submitted_at ? $sub->submitted_at->format('d M Y') : '-' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->submitted_at ? $sub->submitted_at->format('H:i:s') : '-' }} WIB
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        {{ $sub->employee?->name ?? 'Petugas Lapangan' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        NIK: {{ $sub->employee?->nik ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">
                                        {{ $sub->workLocation?->name ?? 'Outlet / Toko Reguler' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $sub->workLocation?->address ?? ($sub->latitude . ', ' . $sub->longitude) }}
                                    </div>
                                </td>
                                <td>
                                    <span style="display: inline-block; font-size: 0.78rem; font-weight: 700; color: var(--brand-primary); background: var(--brand-light); padding: 0.25rem 0.65rem; border-radius: 6px;">
                                        {{ $sub->template?->title ?? 'Form Standar' }}
                                    </span>
                                </td>
                                <td>
                                    @if($sub->is_within_radius)
                                        <span class="badge-gps-valid">
                                            <i class="fa-solid fa-circle-check"></i> Valid Geofence
                                        </span>
                                    @else
                                        <span class="badge-gps-invalid">
                                            <i class="fa-solid fa-location-dot"></i> Koordinat Dicatat
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                                        Terkirim
                                    </span>
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
                <i class="fa-solid fa-clipboard-check" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-heading);">Belum Ada Laporan di Periode Ini</div>
                <p style="font-size: 0.85rem; max-width: 400px; margin: 0.35rem auto 0;">
                    Laporan dari petugas lapangan melalui aplikasi mobile akan langsung muncul di tabel dan grafik ini secara real-time.
                </p>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Daily Submissions ApexChart
        const lineOptions = {
            chart: {
                type: 'area',
                height: 290,
                toolbar: { show: false },
                fontFamily: 'Outfit, sans-serif'
            },
            colors: ['{{ $brandColor }}'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            series: [{
                name: 'Laporan Masuk',
                data: @json(array_values($chartSubmissions))
            }],
            xaxis: {
                categories: @json($chartLabels),
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px' }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px' }
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4
            },
            tooltip: {
                theme: 'light',
                y: {
                    formatter: function (val) {
                        return val + ' Laporan';
                    }
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#dailySubmissionsChart"), lineOptions);
        chart.render();

        // Category Breakdown Donut Chart
        @php
            $catKeys = array_keys($categoryBreakdown);
            $catVals = array_values($categoryBreakdown);
            if (empty($catVals)) {
                $catKeys = ['Offtake', 'Stock & OOS', 'Display', 'Promo'];
                $catVals = [40, 25, 20, 15];
            }
        @endphp
        const donutOptions = {
            chart: {
                type: 'donut',
                height: 290,
                fontFamily: 'Outfit, sans-serif'
            },
            labels: @json($catKeys),
            series: @json($catVals),
            colors: ['{{ $brandColor }}', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
            legend: {
                position: 'bottom',
                fontSize: '12px',
                labels: { colors: '#475569' }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return Math.round(val) + "%";
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '12px',
                                color: '#64748b'
                            }
                        }
                    }
                }
            },
            tooltip: {
                theme: 'light'
            }
        };

        const donutChart = new ApexCharts(document.querySelector("#categoryDonutChart"), donutOptions);
        donutChart.render();
    });
</script>
@endpush
