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
    @endphp

    <style>
        :root {
            --brand-primary: {{ $brandColor }};
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

        .sidebar-badge-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--brand-primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 2px 8px var(--brand-glow);
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
            background-color: var(--brand-light);
            color: var(--brand-primary);
            font-weight: 700;
        }

        .sidebar-nav-item.active i.nav-icon {
            color: var(--brand-primary);
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
            <div class="menu-category-label">Ringkasan Eksekutif</div>
            
            <a href="{{ route('portal.dashboard') }}" class="sidebar-nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie nav-icon"></i>
                <span class="nav-text">Sales Summary Dashboard</span>
            </a>

            @if(isset($activeTemplates) && $activeTemplates->isNotEmpty())
                <div class="menu-category-label">Modul Pelaporan ({{ $activeTemplates->count() }})</div>

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
                    <a href="{{ route('portal.report.detail', $tpl->code) }}" class="sidebar-nav-item {{ $isCurrent ? 'active' : '' }}">
                        <i class="{{ $iconClass }} nav-icon"></i>
                        <span class="nav-text">{{ $tpl->title }}</span>
                        <span class="nav-badge-count">{{ $tpl->fields->count() }}f</span>
                    </a>
                @endforeach
            @endif

            <div class="menu-category-label">Akses Cepat</div>
            <a href="/" class="sidebar-nav-item" target="_blank">
                <i class="fa-solid fa-globe nav-icon"></i>
                <span class="nav-text">Lihat Landing Page</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-profile-row">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->name ?? 'P', 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name ?? 'Principal Client' }}</div>
                    <div class="user-role">{{ Auth::user()->email ?? $tenantPrincipal->name }}</div>
                </div>
                <form action="{{ route('tenant.logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout" title="Keluar dari Portal">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
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
                        <a href="{{ route('portal.dashboard') }}">Home</a>
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
                        <span>@yield('breadcrumb_active', 'Dashboard')</span>
                    </div>
                </div>
            </div>

            <div class="topbar-right">
                <div class="tenant-selector-pill">
                    <i class="fa-solid fa-shield-halved"></i>
                    {{ $tenantPrincipal->name }}
                </div>

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
