<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $tenantPrincipal->portal_title ?? ($tenantPrincipal->name . ' - Reporting Portal'))</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    @php
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $brandSecondary = $tenantPrincipal->theme_color_secondary ?? ($tenantPrincipal->theme_color ?? '#2563EB');
    @endphp

    <style>
        :root {
            --brand-primary: {{ $brandColor }};
            --brand-secondary: {{ $brandSecondary }};
            --brand-gradient: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            --brand-light: {{ $brandColor }}15;
            --brand-glow: {{ $brandColor }}25;
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-topbar: #ffffff;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            --card-bg: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --shadow-md: 0 4px 16px -2px rgba(0,0,0,0.06), 0 2px 6px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 28px -4px rgba(0,0,0,0.08), 0 4px 10px -2px rgba(0,0,0,0.04);
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        aside.portal-sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .sidebar-logo {
            max-height: 38px;
            max-width: 110px;
            object-fit: contain;
        }

        .brand-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--brand-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px var(--brand-glow);
            flex-shrink: 0;
        }

        .sidebar-brand-name {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1.2;
            letter-spacing: -0.3px;
        }

        .sidebar-brand-sub {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--brand-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .menu-category-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            padding: 0.85rem 0.75rem 0.4rem;
        }

        .sidebar-nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-body);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .sidebar-nav-item i.nav-icon {
            font-size: 1rem;
            width: 22px;
            text-align: center;
            color: var(--text-muted);
            transition: color 0.2s ease;
        }

        .sidebar-nav-item:hover {
            background-color: #f1f5f9;
            color: var(--brand-primary);
        }

        .sidebar-nav-item:hover i.nav-icon {
            color: var(--brand-primary);
        }

        .sidebar-nav-item.active {
            background: var(--brand-gradient);
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 4px 14px var(--brand-glow);
        }

        .sidebar-nav-item.active i.nav-icon {
            color: #ffffff;
        }

        .sidebar-nav-item.active .nav-badge-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .sidebar-nav-item .nav-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-badge-count {
            font-size: 0.7rem;
            font-weight: 700;
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            border: 1px solid var(--border-color);
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border-color);
            background: #fafafa;
        }

        .user-profile-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--brand-light);
            color: var(--brand-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            border: 1px solid var(--brand-glow);
        }

        .user-info {
            flex: 1;
            overflow: hidden;
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-heading);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .btn-logout {
            color: #ef4444;
            background: #fee2e2;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Main Container */
        .portal-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Topbar */
        header.portal-topbar {
            height: 68px;
            background-color: var(--bg-topbar);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: var(--shadow-sm);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .topbar-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.3px;
        }

        .topbar-breadcrumb {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .topbar-breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .topbar-breadcrumb a:hover {
            color: var(--brand-primary);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .tenant-selector-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.9rem;
            background: var(--brand-light);
            border: 1px solid var(--brand-glow);
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--brand-primary);
        }

        .topbar-timestamp {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Content Area */
        .portal-content {
            padding: 2rem;
            flex: 1;
        }

        /* Modern Portal Pagination Styling */
        .portal-pagination-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 0.25rem 0;
            width: 100%;
        }

        .portal-pagination-info {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .portal-pagination-info span {
            font-weight: 700;
            color: var(--text-heading);
        }

        .portal-pagination-controls {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .pagination-btn {
            min-width: 36px;
            height: 36px;
            padding: 0 0.6rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.84rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--text-heading);
            background: #ffffff;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .pagination-btn:hover:not(.disabled):not(.dots):not(.active) {
            background: #f8fafc;
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            transform: translateY(-1px);
        }

        .pagination-btn.active {
            background: var(--brand-gradient);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 2px 8px var(--brand-glow);
        }

        .pagination-btn.disabled {
            color: #cbd5e1;
            background: #f8fafc;
            border-color: #f1f5f9;
            cursor: not-allowed;
            box-shadow: none;
        }

        .pagination-btn.dots {
            border: none;
            background: transparent;
            color: var(--text-muted);
            box-shadow: none;
            cursor: default;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            aside.portal-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            aside.portal-sidebar.open {
                transform: translateX(0);
            }
            .portal-main {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar -->
    <aside class="portal-sidebar" id="portalSidebar">
        <div class="sidebar-header">
            @if(!empty($tenantPrincipal->logo_path))
                <img src="{{ asset('storage/' . $tenantPrincipal->logo_path) }}" alt="{{ $tenantPrincipal->name }}" class="sidebar-logo">
            @elseif(!empty($tenantPrincipal->logo))
                <img src="{{ asset('storage/' . $tenantPrincipal->logo) }}" alt="{{ $tenantPrincipal->name }}" class="sidebar-logo">
            @else
                <div class="sidebar-badge-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            @endif
            <div>
                <div class="sidebar-brand-name">{{ $tenantPrincipal->portal_title ?? $tenantPrincipal->name }}</div>
                <div class="sidebar-brand-sub">{{ $tenantPrincipal->name }}</div>
            </div>
        </div>

        <div class="sidebar-menu">
            @php
                $user = Auth::user();
                $isSuperAdmin = $user && ($user->isSuperAdmin() || $user->hasRole('super_admin') || $user->hasRole('Super Admin') || $user->hasRole('admin'));
                $hasPerm = function($perm) use ($user, $isSuperAdmin) {
                    if (!$user) return false;
                    if ($isSuperAdmin) return true;
                    return $user->can($perm);
                };

                $hasMasterData = $hasPerm('view_employees') || $hasPerm('view_areas') || $hasPerm('view_principals') || $hasPerm('view_companies') || $hasPerm('view_departments') || $hasPerm('view_positions') || $hasPerm('view_work_locations') || $hasPerm('view_shifts') || $hasPerm('view_holidays');
                $hasAttendance = $hasPerm('view_attendance') || $hasPerm('manage_roster') || $hasPerm('view_leave_requests') || $hasPerm('view_extra_hours') || $hasPerm('view_working_groups') || $hasPerm('view_unchecked_monitoring');
                $hasFieldOps = $hasPerm('view_itineraries') || $hasPerm('view_visit_reports') || $hasPerm('view_sales_reports') || $hasPerm('view_work_targets') || $hasPerm('view_payslips');
                $hasAnalytics = $hasPerm('view_manpower_report') || $hasPerm('view_mandays_report') || $hasPerm('view_turnover_report') || $hasPerm('view_odoo_sync');
                $hasSystem = $hasPerm('manage_users') || $hasPerm('manage_roles') || $hasPerm('manage_settings') || $hasPerm('view_blast_info') || $hasPerm('view_live_chat');
            @endphp

            <!-- 1. Ringkasan Eksekutif -->
            <div class="menu-category-label">Ringkasan Eksekutif</div>
            <a href="{{ route('portal.dashboard', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie nav-icon"></i>
                <span class="nav-text">Sales Summary Dashboard</span>
            </a>
            <a href="{{ route('portal.products', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.products*') ? 'active' : '' }}">
                <i class="fa-solid fa-boxes-stacked nav-icon"></i>
                <span class="nav-text">Katalog Produk (SKU)</span>
            </a>

            <!-- 2. Master Data (Role Permission Based) -->
            @if($hasMasterData)
                <div class="menu-category-label">Master Data</div>
                @if($hasPerm('view_employees'))
                    <a href="{{ route('portal.employees', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.employees*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users nav-icon"></i>
                        <span class="nav-text">Employees (Karyawan)</span>
                    </a>
                @endif
                @if($hasPerm('view_areas'))
                    <a href="{{ route('portal.areas', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.areas*') ? 'active' : '' }}">
                        <i class="fa-solid fa-map-location-dot nav-icon"></i>
                        <span class="nav-text">Areas / Cabang</span>
                    </a>
                @endif
                @if($hasPerm('view_work_locations'))
                    <a href="{{ route('portal.work_locations', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.work_locations*') ? 'active' : '' }}">
                        <i class="fa-solid fa-store nav-icon"></i>
                        <span class="nav-text">Work Locations (Lokasi Kerja)</span>
                    </a>
                @endif
                @if($hasPerm('view_shifts'))
                    <a href="{{ route('portal.shifts', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.shifts*') ? 'active' : '' }}">
                        <i class="fa-solid fa-business-time nav-icon"></i>
                        <span class="nav-text">Shifts (Shift Kerja)</span>
                    </a>
                @endif
                @if($hasPerm('view_principals'))
                    <a href="/admin/principals" class="sidebar-nav-item">
                        <i class="fa-solid fa-building-shield nav-icon"></i>
                        <span class="nav-text">Principals (Prinsiple)</span>
                    </a>
                @endif
                @if($hasPerm('view_companies'))
                    <a href="/admin/companies" class="sidebar-nav-item">
                        <i class="fa-solid fa-building nav-icon"></i>
                        <span class="nav-text">Companies (Perusahaan)</span>
                    </a>
                @endif
                @if($hasPerm('view_departments'))
                    <a href="/admin/departments" class="sidebar-nav-item">
                        <i class="fa-solid fa-sitemap nav-icon"></i>
                        <span class="nav-text">Departments (Departemen)</span>
                    </a>
                @endif
                @if($hasPerm('view_positions'))
                    <a href="/admin/positions" class="sidebar-nav-item">
                        <i class="fa-solid fa-id-badge nav-icon"></i>
                        <span class="nav-text">Positions (Jabatan)</span>
                    </a>
                @endif
                @if($hasPerm('view_holidays'))
                    <a href="/admin/holidays" class="sidebar-nav-item">
                        <i class="fa-regular fa-calendar-days nav-icon"></i>
                        <span class="nav-text">Holidays (Hari Libur)</span>
                    </a>
                @endif
            @endif

            <!-- 3. Attendance & Time Management (Role Permission Based) -->
            @if($hasAttendance)
                <div class="menu-category-label">Attendance & Kehadiran</div>
                @if($hasPerm('view_attendance'))
                    <a href="{{ route('portal.attendances', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.attendances*') ? 'active' : '' }}">
                        <i class="fa-solid fa-clipboard-user nav-icon"></i>
                        <span class="nav-text">Presensi / Absensi</span>
                    </a>
                @endif
                @if($hasPerm('manage_roster'))
                    <a href="{{ route('portal.schedules', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.schedules*') ? 'active' : '' }}">
                        <i class="fa-solid fa-calendar-week nav-icon"></i>
                        <span class="nav-text">Roster & Jadwal Kerja</span>
                    </a>
                @endif
                @if($hasPerm('view_leave_requests'))
                    <a href="{{ route('portal.leaves', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.leaves*') ? 'active' : '' }}">
                        <i class="fa-solid fa-envelope-open-text nav-icon"></i>
                        <span class="nav-text">Izin / Cuti</span>
                    </a>
                @endif
                @if($hasPerm('view_extra_hours'))
                    <a href="{{ route('portal.extra_hours', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.extra_hours*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-clock nav-icon"></i>
                        <span class="nav-text">Lembur (Extra Hours)</span>
                    </a>
                @endif
                @if($hasPerm('view_working_groups'))
                    <a href="/admin/working-groups" class="sidebar-nav-item">
                        <i class="fa-solid fa-users-gear nav-icon"></i>
                        <span class="nav-text">Pola Kerja (Working Groups)</span>
                    </a>
                @endif
                @if($hasPerm('view_unchecked_monitoring'))
                    <a href="{{ route('portal.unchecked', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.unchecked*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-slash nav-icon"></i>
                        <span class="nav-text">Monitoring Belum Check-in</span>
                    </a>
                @endif
            @endif

            <!-- 4. Field Operations & Sales (Role Permission Based) -->
            @if($hasFieldOps)
                <div class="menu-category-label">Operasional & Sales</div>
                @if($hasPerm('view_itineraries'))
                    <a href="{{ route('portal.itineraries', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.itineraries*') ? 'active' : '' }}">
                        <i class="fa-solid fa-route nav-icon"></i>
                        <span class="nav-text">Visit Schedule (Itinerari)</span>
                    </a>
                @endif
                @if($hasPerm('view_visit_reports'))
                    <a href="{{ route('portal.visit_reports', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.visit_reports*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-waveform nav-icon"></i>
                        <span class="nav-text">Laporan Kunjungan</span>
                    </a>
                @endif
                @if($hasPerm('view_sales_reports'))
                    <a href="/admin/sales-reports" class="sidebar-nav-item">
                        <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
                        <span class="nav-text">Laporan Penjualan</span>
                    </a>
                @endif
                @if($hasPerm('view_work_targets'))
                    <a href="/admin/work-targets" class="sidebar-nav-item">
                        <i class="fa-solid fa-bullseye nav-icon"></i>
                        <span class="nav-text">Target Kerja</span>
                    </a>
                @endif
                @if($hasPerm('view_payslips'))
                    <a href="/admin/payslips" class="sidebar-nav-item">
                        <i class="fa-solid fa-money-check-dollar nav-icon"></i>
                        <span class="nav-text">Slip Gaji (Payslips)</span>
                    </a>
                @endif
            @endif

            <!-- 5. Modul Pelaporan SOP -->
            @if(isset($activeTemplates) && $activeTemplates->isNotEmpty())
                <div class="menu-category-label">Modul Pelaporan SOP ({{ $activeTemplates->count() }})</div>

                @foreach($activeTemplates as $tpl)
                    @php
                        $iconClass = 'fa-solid fa-file-lines';
                        if (str_contains(strtolower($tpl->title), 'offtake') || str_contains(strtolower($tpl->title), 'jual') || str_contains(strtolower($tpl->title), 'sell')) {
                            $iconClass = 'fa-solid fa-cart-shopping';
                        } elseif (str_contains(strtolower($tpl->title), 'stok') || str_contains(strtolower($tpl->title), 'oos')) {
                            $iconClass = 'fa-solid fa-boxes-stacked';
                        } elseif (str_contains(strtolower($tpl->title), 'market') || str_contains(strtolower($tpl->title), 'kompetitor')) {
                            $iconClass = 'fa-solid fa-chart-pie';
                        } elseif (str_contains(strtolower($tpl->title), 'display') || str_contains(strtolower($tpl->title), 'sewa') || str_contains(strtolower($tpl->title), 'sos')) {
                            $iconClass = 'fa-solid fa-store';
                        } elseif (str_contains(strtolower($tpl->title), 'harga') || str_contains(strtolower($tpl->title), 'price') || str_contains(strtolower($tpl->title), 'promo')) {
                            $iconClass = 'fa-solid fa-tags';
                        } elseif (str_contains(strtolower($tpl->title), 'expired') || str_contains(strtolower($tpl->title), 'fefo')) {
                            $iconClass = 'fa-solid fa-clock-rotate-left';
                        } elseif (str_contains(strtolower($tpl->title), 'posm') || str_contains(strtolower($tpl->title), 'stiker')) {
                            $iconClass = 'fa-solid fa-bullhorn';
                        }
                        $isCurrent = request()->routeIs('portal.report.detail') && request()->route('code') === $tpl->code;
                    @endphp
                    <a href="{{ route('portal.report.detail', ['code' => $tpl->code, 'p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ $isCurrent ? 'active' : '' }}">
                        <i class="{{ $iconClass }} nav-icon"></i>
                        <span class="nav-text">{{ $tpl->title }}</span>
                        <span class="nav-badge-count">{{ $tpl->fields->count() }}f</span>
                    </a>
                @endforeach
            @endif

            <!-- 6. Reports & Analytics (Role Permission Based) -->
            @if($hasAnalytics)
                <div class="menu-category-label">Laporan & Analitik</div>
                @if($hasPerm('view_manpower_report'))
                    <a href="{{ route('portal.manpower_report', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.manpower_report*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-column nav-icon"></i>
                        <span class="nav-text">Manpower Report</span>
                    </a>
                @endif
                @if($hasPerm('view_mandays_report'))
                    <a href="{{ route('portal.mandays_report', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.mandays_report*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line nav-icon"></i>
                        <span class="nav-text">Mandays Report</span>
                    </a>
                @endif
                @if($hasPerm('view_turnover_report'))
                    <a href="{{ route('portal.turnover_report', ['p' => $tenantPrincipal->id]) }}" class="sidebar-nav-item {{ request()->routeIs('portal.turnover_report*') ? 'active' : '' }}">
                        <i class="fa-solid fa-arrow-right-arrow-left nav-icon"></i>
                        <span class="nav-text">Turnover Report</span>
                    </a>
                @endif
                @if($hasPerm('view_odoo_sync'))
                    <a href="/admin/odoo-sync-report" class="sidebar-nav-item">
                        <i class="fa-solid fa-arrows-rotate nav-icon"></i>
                        <span class="nav-text">Odoo Sync Report</span>
                    </a>
                @endif
            @endif

            <!-- 7. System & Settings (Role Permission Based) -->
            @if($hasSystem)
                <div class="menu-category-label">Sistem & Konfigurasi</div>
                @if($hasPerm('manage_users'))
                    <a href="/admin/users" class="sidebar-nav-item">
                        <i class="fa-solid fa-user-gear nav-icon"></i>
                        <span class="nav-text">Manajemen User</span>
                    </a>
                @endif
                @if($hasPerm('manage_roles'))
                    <a href="/admin/roles" class="sidebar-nav-item">
                        <i class="fa-solid fa-shield-halved nav-icon"></i>
                        <span class="nav-text">Roles & Permissions</span>
                    </a>
                @endif
                @if($hasPerm('manage_settings'))
                    <a href="/admin/manage-settings" class="sidebar-nav-item">
                        <i class="fa-solid fa-gear nav-icon"></i>
                        <span class="nav-text">Pengaturan Sistem</span>
                    </a>
                @endif
                @if($hasPerm('view_blast_info'))
                    <a href="/admin/blast-infos" class="sidebar-nav-item">
                        <i class="fa-solid fa-bullhorn nav-icon"></i>
                        <span class="nav-text">Blast Info (Broadcast)</span>
                    </a>
                @endif
                @if($hasPerm('view_live_chat'))
                    <a href="/admin/live-chat" class="sidebar-nav-item">
                        <i class="fa-solid fa-comments nav-icon"></i>
                        <span class="nav-text">Live Chat Support</span>
                    </a>
                @endif
            @endif

            <!-- 8. Akses Cepat -->
            <div class="menu-category-label">Akses Cepat</div>
            <a href="/?p={{ $tenantPrincipal->id }}" class="sidebar-nav-item" target="_blank">
                <i class="fa-solid fa-globe nav-icon"></i>
                <span class="nav-text">Lihat Landing Page</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-profile-row">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()?->name ?? 'P', 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()?->name ?? ($tenantPrincipal->name . ' Admin') }}</div>
                    <div class="user-role">{{ Auth::user()?->roles?->pluck('name')->first() ?? (Auth::user()?->email ?? 'Auditor / Client') }}</div>
                </div>
                @if(Auth::check())
                <form action="{{ route('tenant.logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout" title="Keluar dari Portal">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
                @else
                <a href="{{ route('tenant.login', ['p' => $tenantPrincipal->id]) }}" class="btn-logout" style="background: var(--brand-light); color: var(--brand-primary);" title="Login ke Akun">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </a>
                @endif
            </div>
        </div>
    </aside>

    <!-- Main Container -->
    <div class="portal-main">
        <!-- Topbar -->
        <header class="portal-topbar">
            <div class="topbar-left">
                <div>
                    <h1 class="topbar-title">@yield('page_title', 'Sales Summary Dashboard')</h1>
                    <div class="topbar-breadcrumb">
                        <a href="{{ route('portal.dashboard', ['p' => $tenantPrincipal->id]) }}">Home</a>
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
                        <span>@yield('breadcrumb_active', 'Dashboard')</span>
                    </div>
                </div>
            </div>

            <div class="topbar-right">
                @php
                    $allEntities = isset($tenantPrincipalsAll) ? $tenantPrincipalsAll->unique('name') : collect([$tenantPrincipal]);
                @endphp
                @if($allEntities->count() > 1)
                    <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                        @foreach($allEntities as $ent)
                            <a href="?p={{ $ent->id }}" class="tenant-selector-pill" style="text-decoration: none; cursor: pointer; transition: all 0.2s ease; {{ $tenantPrincipal->id == $ent->id ? 'background: var(--brand-primary); color: #fff; border-color: var(--brand-primary); font-weight: 800;' : 'background: #f1f5f9; color: #64748b; border-color: #cbd5e1;' }}">
                                <i class="fa-solid {{ $tenantPrincipal->id == $ent->id ? 'fa-circle-check' : 'fa-building' }}"></i>
                                {{ $ent->name }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="tenant-selector-pill">
                        <i class="fa-solid fa-shield-halved"></i>
                        {{ $tenantPrincipal->name }}
                    </div>
                @endif

                <div class="topbar-timestamp">
                    <i class="fa-regular fa-clock"></i>
                    <span>Updated on {{ Carbon\Carbon::now()->format('F d, Y H:i:s') }}</span>
                </div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="portal-content">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
