<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenantPrincipal->portal_title ?? ($tenantPrincipal->name . ' - Portal Pelaporan') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
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
            --bg-main: #f8fafc;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --card-border-hover: #cbd5e1;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --shadow-md: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 6px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 12px 32px -4px rgba(0,0,0,0.08), 0 4px 12px -2px rgba(0,0,0,0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 10%, rgba(15, 23, 42, 0.03) 0%, transparent 40%),
                radial-gradient(circle at 85% 60%, rgba(15, 23, 42, 0.02) 0%, transparent 35%),
                radial-gradient(circle at 50% 90%, #6366f105 0%, transparent 40%);
            background-attachment: fixed;
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 6%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 4px rgba(0,0,0,0.02);
        }

        .brand-container {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .brand-logo-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-logo-img {
            max-height: 42px;
            max-width: 140px;
            object-fit: contain;
            display: block;
        }

        .brand-badge-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--brand-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4px 12px var(--brand-glow);
        }

        .brand-info {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            white-space: nowrap;
        }

        .brand-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.4px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .brand-subtitle {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .btn-portal-login {
            background: var(--brand-gradient);
            color: #ffffff;
            padding: 0.65rem 1.4rem;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 0.88rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 14px var(--brand-glow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-portal-login:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 6px 20px var(--brand-glow);
        }

        /* Hero Section */
        .hero {
            padding: 4.5rem 6% 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .tenant-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 1.15rem;
            background: var(--brand-light);
            color: var(--brand-primary);
            border: 1px solid var(--brand-glow);
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }

        .tenant-pill i {
            font-size: 0.85rem;
            color: var(--brand-primary);
        }

        .hero h1 {
            font-size: 3.25rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 1.25rem;
            max-width: 950px;
        }

        .hero h1 span.highlight {
            color: var(--brand-primary);
            font-weight: 900;
        }

        .hero p {
            font-size: 1.1rem;
            color: #475569;
            max-width: 760px;
            line-height: 1.7;
            margin-bottom: 2.25rem;
            font-weight: 500;
        }

        .hero-cta-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-primary-glow {
            background: var(--brand-gradient);
            color: #ffffff;
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 6px 20px var(--brand-glow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary-glow:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 8px 26px var(--brand-glow);
        }

        .btn-secondary-glass {
            background: #ffffff;
            color: #0f172a;
            padding: 0.85rem 1.8rem;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid #e2e8f0;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
        }

        .btn-secondary-glass:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        /* Brand Switcher */
        .brand-switcher-wrapper {
            margin-top: 2.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
        }

        .brand-switcher-title {
            font-size: 0.78rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-switcher-pills {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.55rem;
            max-width: 900px;
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 1.15rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
            color: #475569;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }

        .brand-pill:hover {
            color: var(--brand-primary);
            border-color: var(--brand-primary);
            background: var(--brand-light);
            transform: translateY(-1px);
        }

        .brand-pill.active {
            color: #ffffff;
            background: var(--brand-gradient);
            border-color: transparent;
            box-shadow: 0 4px 14px var(--brand-glow);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            max-width: 1150px;
            width: 100%;
            margin: 0 auto 4.5rem;
            padding: 0 6%;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.6rem;
            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            font-size: 1.35rem;
            color: var(--brand-primary);
            margin-bottom: 1.15rem;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--brand-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 0.35rem;
            letter-spacing: -0.8px;
        }

        .stat-label {
            font-size: 0.84rem;
            color: #64748b;
            font-weight: 700;
        }

        /* Templates Section */
        .section-container {
            max-width: 1150px;
            margin: 0 auto 5rem;
            padding: 0 6%;
            width: 100%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2.75rem;
        }

        .section-badge {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--brand-primary);
            background: var(--brand-light);
            padding: 0.3rem 0.9rem;
            border-radius: 9999px;
            margin-bottom: 0.65rem;
            border: 1px solid var(--brand-glow);
        }

        .section-title {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 0.6rem;
            letter-spacing: -0.5px;
        }

        .section-desc {
            font-size: 0.98rem;
            color: var(--text-muted);
            max-width: 620px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 1.35rem;
        }

        .template-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.6rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .template-card:hover {
            transform: translateY(-5px);
            border-color: var(--brand-primary);
            box-shadow: var(--shadow-lg);
        }

        .template-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.15rem;
        }

        .template-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--brand-light);
            color: var(--brand-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .template-code-badge {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            background: #f1f5f9;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-family: monospace;
            border: 1px solid #e2e8f0;
        }

        .template-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 0.45rem;
            line-height: 1.35;
        }

        .template-card-desc {
            font-size: 0.87rem;
            color: var(--text-body);
            line-height: 1.5;
            margin-bottom: 1.25rem;
        }

        .template-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.8rem;
        }

        .field-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--brand-primary);
            font-weight: 700;
            background: var(--brand-light);
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
        }

        .template-action-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--text-heading);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .template-action-link:hover {
            color: var(--brand-primary);
        }

        /* Features Section */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.35rem;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 1.85rem 1.6rem;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            border-color: var(--card-border-hover);
            box-shadow: var(--shadow-md);
        }

        .feature-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--brand-light);
            color: var(--brand-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 1.15rem;
        }

        .feature-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.88rem;
            color: var(--text-body);
            line-height: 1.6;
        }

        /* Footer */
        footer {
            margin-top: auto;
            padding: 2.25rem 6%;
            border-top: 1px solid var(--card-border);
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-weight: 700;
            color: var(--text-heading);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .hero h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .hero h1 {
                font-size: 2rem;
            }
            .hero p {
                font-size: 1rem;
            }
            .nav-actions {
                display: none;
            }
            footer {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Whitelabel Navbar -->
    <nav>
        <a href="/?p={{ $tenantPrincipal->id }}" class="brand-container">
            <div class="brand-logo-wrapper">
                @if(!empty($tenantPrincipal->logo_path))
                    <img src="{{ asset('storage/' . $tenantPrincipal->logo_path) }}" alt="{{ $tenantPrincipal->name }}" class="brand-logo-img">
                @elseif(!empty($tenantPrincipal->logo))
                    <img src="{{ asset('storage/' . $tenantPrincipal->logo) }}" alt="{{ $tenantPrincipal->name }}" class="brand-logo-img">
                @elseif(!empty($setting->app_logo))
                    <img src="{{ asset('storage/' . $setting->app_logo) }}" alt="{{ $tenantPrincipal->name }}" class="brand-logo-img">
                @else
                    <div class="brand-badge-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                @endif
            </div>
            <div class="brand-info">
                <span class="brand-title">{{ $tenantPrincipal->portal_title ?? $tenantPrincipal->name }}</span>
                <span class="brand-subtitle">Enterprise Reporting Portal &bull; {{ $tenantPrincipal->name }}</span>
            </div>
        </a>

        <div class="nav-actions">
            <a href="{{ route('tenant.login', ['p' => $tenantPrincipal->id]) }}" class="btn-portal-login">
                <i class="fa-solid fa-lock"></i>
                Masuk ke Portal
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main>
        <section class="hero">
            <div class="tenant-pill">
                <i class="fa-solid fa-circle-check"></i>
                PORTAL RESMI &bull; {{ strtoupper($tenantPrincipal->name) }}
            </div>

            <h1>
                Portal Pelaporan Terpadu<br>
                <span class="highlight">{{ $tenantPrincipal->name }}</span>
            </h1>

            <p>
                Platform pelaporan terintegrasi untuk pemantauan offtake penjualan harian, stok & OOS (Out of Stock), 
                analisis market share kompetitor, serta aktivitas promotor lapangan secara real-time dan akurat.
            </p>

            <div class="hero-cta-group">
                <a href="{{ route('tenant.login', ['p' => $tenantPrincipal->id]) }}" class="btn-primary-glow">
                    <i class="fa-solid fa-shield-halved"></i>
                    Masuk ke Portal Manajemen
                </a>
                <a href="/app-release.apk" class="btn-secondary-glass">
                    <i class="fa-brands fa-android" style="color: #16a34a;"></i>
                    Unduh Aplikasi Mobile (SPG)
                </a>
            </div>

            @php
                $uniqueEntities = isset($tenantPrincipalsAll) ? $tenantPrincipalsAll->unique('name') : collect([$tenantPrincipal]);
            @endphp
            @if($uniqueEntities->count() > 1)
            <div class="brand-switcher-wrapper">
                <span class="brand-switcher-title"><i class="fa-solid fa-layer-group"></i> Pilih Entitas Prinsiple Terkait:</span>
                <div class="brand-switcher-pills">
                    @foreach($uniqueEntities as $entity)
                        <a href="?p={{ $entity->id }}" class="brand-pill {{ $tenantPrincipal->name === $entity->name ? 'active' : '' }}">
                            <i class="fa-solid {{ $tenantPrincipal->name === $entity->name ? 'fa-circle-check' : 'fa-building' }}"></i>
                            {{ $entity->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </section>

        <!-- Dynamic Scoped Stats -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-value">{{ number_format($stats['employees']) }}</div>
                <div class="stat-label">Promotor / SPG Terdaftar</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="stat-value">{{ number_format($stats['areas']) }}</div>
                <div class="stat-label">Area Operasional</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div class="stat-value">{{ number_format($stats['templates']) }}</div>
                <div class="stat-label">Modul Form Pelaporan</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="stat-value">{{ number_format($stats['submissions']) }}</div>
                <div class="stat-label">Total Laporan Masuk</div>
            </div>
        </section>

        <!-- Active Reporting Templates for this Principal -->
        @if($activeTemplates->isNotEmpty())
        <section class="section-container">
            <div class="section-header">
                <span class="section-badge">Standard Operating Procedures</span>
                <h2 class="section-title">Form Pelaporan Standar {{ $tenantPrincipal->name }}</h2>
                <p class="section-desc">
                    Seluruh formulir pelaporan disesuaikan dengan kebutuhan analisis pasar dan operasional brand Anda.
                </p>
            </div>

            <div class="templates-grid">
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
                    @endphp
                    <div class="template-card">
                        <div>
                            <div class="template-card-top">
                                <div class="template-icon-box">
                                    <i class="{{ $iconClass }}"></i>
                                </div>
                                <span class="template-code-badge">{{ $tpl->code }}</span>
                            </div>
                            <h3 class="template-card-title">{{ $tpl->title }}</h3>
                            <p class="template-card-desc">{{ $tpl->description ?? 'Formulir pelaporan operasional standar lapangan.' }}</p>
                        </div>
                        <div class="template-card-footer">
                            <span class="field-count-pill">
                                <i class="fa-solid fa-list-check"></i>
                                {{ $tpl->fields->count() }} Field Input
                            </span>
                            <a href="/login" class="template-action-link">
                                Buka Form <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <!-- Features -->
        <section class="section-container">
            <div class="section-header">
                <span class="section-badge">Enterprise Features</span>
                <h2 class="section-title">Keunggulan Platform Pelaporan</h2>
                <p class="section-desc">
                    Infrastruktur pelaporan berbasis mobile & web untuk efisiensi operasional lapangan secara menyeluruh.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </div>
                    <h3 class="feature-title">Validasi Geofencing & GPS</h3>
                    <p class="feature-desc">
                        Setiap laporan diverifikasi dengan titik koordinat GPS toko yang presisi untuk menjamin integritas kehadiran dan keaslian kunjungan.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <h3 class="feature-title">Bukti Foto & Watermark</h3>
                    <p class="feature-desc">
                        Pengambilan foto kondisi display, stok, dan rak dengan watermark otomatis tanggal, jam, dan lokasi toko anti-manipulasi.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h3 class="feature-title">Executive Analytics</h3>
                    <p class="feature-desc">
                        Dashboard analitik langsung merekap pencapaian offtake harian, stok habis (OOS), dan share of shelf secara otomatis.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-brand">
            <i class="fa-solid fa-shield-halved" style="color: var(--brand-primary);"></i>
            <span>{{ $tenantPrincipal->portal_title ?? $tenantPrincipal->name }}</span>
        </div>
        <div>
            &copy; {{ date('Y') }} {{ $tenantPrincipal->name }}. Powered by <strong>{{ $setting->app_name ?? 'ESA Groups' }}</strong>.
        </div>
    </footer>

</body>
</html>
