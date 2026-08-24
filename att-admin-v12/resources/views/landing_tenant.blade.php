<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenantPrincipal->portal_title ?? ($tenantPrincipal->name . ' - Portal Pelaporan') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    @php
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
    @endphp

    <style>
        :root {
            --brand-primary: {{ $brandColor }};
            --brand-glow: {{ $brandColor }}66;
            --brand-light: {{ $brandColor }}22;
            --bg-start: #090d16;
            --bg-end: #0f172a;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-card: rgba(15, 23, 42, 0.65);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-border-hover: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(145deg, var(--bg-start) 0%, var(--bg-end) 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Dynamic Background Glows */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.35;
            animation: float 18s infinite alternate ease-in-out;
        }

        .shape-1 {
            width: 600px;
            height: 600px;
            background: var(--brand-primary);
            top: -150px;
            left: -150px;
        }

        .shape-2 {
            width: 500px;
            height: 500px;
            background: #6366f1;
            bottom: -100px;
            right: -100px;
            animation-delay: -6s;
        }

        .shape-3 {
            width: 350px;
            height: 350px;
            background: var(--brand-primary);
            top: 40%;
            left: 60%;
            opacity: 0.15;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 40px) scale(1.1); }
            100% { transform: translate(-40px, 90px) scale(0.95); }
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 6%;
            background: rgba(9, 13, 22, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            text-decoration: none;
        }

        .brand-logo-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo-img {
            max-height: 44px;
            max-width: 140px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px var(--brand-glow));
        }

        .brand-badge-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand-primary), #1e293b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            box-shadow: 0 4px 15px var(--brand-glow);
            border: 1px solid rgba(255,255,255,0.15);
        }

        .brand-info {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-portal-login {
            background: linear-gradient(135deg, var(--brand-primary), #1e1b4b);
            color: #ffffff;
            padding: 0.65rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 16px var(--brand-glow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-portal-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px var(--brand-glow);
            border-color: rgba(255,255,255,0.4);
        }

        /* Hero Section */
        .hero {
            padding: 4.5rem 6% 3.5rem;
            text-align: center;
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .tenant-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 1.2rem;
            background: var(--brand-light);
            border: 1px solid var(--brand-glow);
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 1.75rem;
            box-shadow: 0 2px 10px var(--brand-glow);
        }

        .tenant-pill i {
            color: #38bdf8;
        }

        .hero h1 {
            font-size: 3.25rem;
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            letter-spacing: -1.5px;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero h1 span.highlight {
            background: linear-gradient(135deg, #60a5fa 0%, var(--brand-primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.15rem;
            color: var(--text-muted);
            max-width: 780px;
            line-height: 1.7;
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .hero-cta-group {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 4rem;
        }

        .btn-primary-glow {
            background: var(--brand-primary);
            color: white;
            padding: 0.9rem 2.2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 6px 20px var(--brand-glow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255,255,255,0.25);
        }

        .btn-primary-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px var(--brand-glow);
        }

        .btn-secondary-glass {
            background: var(--glass-bg);
            color: #fff;
            padding: 0.9rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .btn-secondary-glass:hover {
            background: rgba(255,255,255,0.08);
            border-color: var(--glass-border-hover);
            transform: translateY(-3px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            width: 100%;
            max-width: 1140px;
            margin: 0 auto 5rem;
            padding: 0 6%;
        }

        .stat-card {
            background: var(--glass-card);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 1.75rem 1.5rem;
            border-radius: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--brand-primary), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--glass-border-hover);
            box-shadow: 0 12px 30px rgba(0,0,0,0.3);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            font-size: 1.75rem;
            color: var(--brand-primary);
            margin-bottom: 0.75rem;
            display: inline-block;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
            margin-bottom: 0.4rem;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Reporting Templates Section */
        .section-container {
            max-width: 1140px;
            margin: 0 auto 5rem;
            padding: 0 6%;
            width: 100%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-badge {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--brand-primary);
            background: var(--brand-light);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
            border: 1px solid var(--brand-glow);
        }

        .section-title {
            font-size: 2.25rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
        }

        .section-desc {
            font-size: 1rem;
            color: var(--text-muted);
            max-width: 650px;
            margin: 0 auto;
        }

        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .template-card {
            background: var(--glass-card);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .template-card:hover {
            transform: translateY(-5px);
            border-color: var(--brand-glow);
            box-shadow: 0 12px 35px var(--brand-glow);
        }

        .template-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .template-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--brand-light);
            border: 1px solid var(--brand-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #fff;
        }

        .template-code-badge {
            font-size: 0.7rem;
            font-weight: 700;
            font-family: monospace;
            background: rgba(255,255,255,0.06);
            color: #94a3b8;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .template-card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .template-card-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .template-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 0.8rem;
            color: #cbd5e1;
        }

        .field-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #38bdf8;
            font-weight: 600;
        }

        /* Features Section */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--glass-card);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            padding: 2rem 1.75rem;
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: var(--glass-border-hover);
        }

        .feature-icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--brand-light), transparent);
            border: 1px solid var(--brand-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 1.25rem;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.6rem;
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Footer */
        footer {
            margin-top: auto;
            padding: 2.5rem 6%;
            border-top: 1px solid var(--glass-border);
            background: rgba(9, 13, 22, 0.9);
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
            gap: 0.75rem;
        }

        .footer-logo {
            font-weight: 700;
            color: #fff;
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

    <!-- Dynamic Glow Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <!-- Whitelabel Navbar -->
    <nav>
        <a href="/" class="brand-container">
            <div class="brand-logo-wrapper">
                @if(!empty($tenantPrincipal->logo))
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
                <span class="brand-title">{{ $tenantPrincipal->name }}</span>
                <span class="brand-subtitle">Enterprise Reporting Portal</span>
            </div>
        </a>

        <div class="nav-actions">
            <a href="/admin/login" class="btn-portal-login">
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
                <a href="/admin/login" class="btn-primary-glow">
                    <i class="fa-solid fa-shield-halved"></i>
                    Masuk ke Portal Manajemen
                </a>
                <a href="/app-release.apk" class="btn-secondary-glass">
                    <i class="fa-brands fa-android" style="color: #4ade80;"></i>
                    Unduh Aplikasi Mobile (SPG)
                </a>
            </div>
        </section>

        <!-- Dynamic Scoped Stats -->
        <section class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-users stat-icon"></i>
                <div class="stat-value">{{ number_format($stats['employees']) }}</div>
                <div class="stat-label">Promotor / SPG Terdaftar</div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-map-location-dot stat-icon"></i>
                <div class="stat-value">{{ number_format($stats['areas']) }}</div>
                <div class="stat-label">Area Operasional</div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-clipboard-list stat-icon"></i>
                <div class="stat-value">{{ number_format($stats['templates']) }}</div>
                <div class="stat-label">Modul Form Pelaporan</div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-chart-line stat-icon"></i>
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
                        // Pilih icon berdasarkan tipe laporan
                        $iconClass = 'fa-solid fa-file-lines';
                        if (str_contains(strtolower($tpl->title), 'offtake') || str_contains(strtolower($tpl->title), 'jual')) {
                            $iconClass = 'fa-solid fa-cart-shopping';
                        } elseif (str_contains(strtolower($tpl->title), 'stok') || str_contains(strtolower($tpl->title), 'oos')) {
                            $iconClass = 'fa-solid fa-boxes-stacked';
                        } elseif (str_contains(strtolower($tpl->title), 'market') || str_contains(strtolower($tpl->title), 'kompetitor')) {
                            $iconClass = 'fa-solid fa-chart-pie';
                        } elseif (str_contains(strtolower($tpl->title), 'tinting') || str_contains(strtolower($tpl->title), 'display')) {
                            $iconClass = 'fa-solid fa-paint-roller';
                        } elseif (str_contains(strtolower($tpl->title), 'kunjungan') || str_contains(strtolower($tpl->title), 'tl') || str_contains(strtolower($tpl->title), 'toko')) {
                            $iconClass = 'fa-solid fa-store';
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
                                {{ $tpl->fields->count() }} Parameter Input
                            </span>
                            <span style="color: #4ade80; font-weight: 600; display: flex; align-items: center; gap: 0.3rem;">
                                <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
                                Aktif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <!-- Enterprise Features -->
        <section class="section-container">
            <div class="section-header">
                <span class="section-badge">Keamanan & Otomasi</span>
                <h2 class="section-title">Keunggulan Pelaporan Digital Kami</h2>
                <p class="section-desc">
                    Didukung fitur keamanan data tingkat enterprise untuk memastikan integritas data lapangan 100% valid.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <h3 class="feature-title">Watermark & Geotag Anti-Fraud</h3>
                    <p class="feature-desc">
                        Setiap foto bukti offtake atau display otomatis dibubuhi stempel koordinat GPS, nama toko, tanggal, dan nama promotor secara permanen.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3 class="feature-title">Offline-First & Auto-Sync</h3>
                    <p class="feature-desc">
                        Promotor tetap dapat mengisi laporan di area minim sinyal/gudang toko. Data tersimpan di SQLite lokal dan otomatis terunggah saat online.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <h3 class="feature-title">Rekap & Analisis Instan</h3>
                    <p class="feature-desc">
                        Export data laporan harian, mingguan, dan bulanan ke format Excel (XLSX) dan PDF lengkap dengan pivot summary untuk meeting manajemen.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-brand">
            <span class="footer-logo">{{ $tenantPrincipal->name }}</span>
            <span>&bull;</span>
            <span>Powered by {{ $setting->app_name ?? 'ESA Attendance System' }}</span>
        </div>
        <div>
            &copy; {{ date('Y') }} {{ $tenantPrincipal->name }}. All Rights Reserved.
        </div>
    </footer>

</body>
</html>
