<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $setting->app_name ?? 'ESA Groups' }} - Sistem Presensi & Manajemen Kinerja Terintegrasi</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --primary-light: #EEF2FF;
            --secondary: #0284C7;
            --accent: #10B981;
            --bg-body: #F8FAFC;
            --surface: #FFFFFF;
            --surface-hover: #F1F5F9;
            --border: #E2E8F0;
            --border-focus: #CBD5E1;
            --text-heading: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.06), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 30px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-body);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 5%, rgba(79, 70, 229, 0.04) 0%, transparent 45%),
                radial-gradient(circle at 90% 15%, rgba(2, 132, 199, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* ─── NAVBAR ─────────────────────────────────────────────────── */
        nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: var(--text-heading);
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.02em;
        }

        .brand-logo-img {
            height: 38px;
            max-width: 140px;
            object-fit: contain;
            border-radius: 8px;
        }

        .brand-badge {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.95rem;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.92rem;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-nav-login {
            background: var(--text-heading);
            color: white;
            padding: 0.65rem 1.4rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .btn-nav-login:hover {
            background: #000;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* ─── HERO SECTION ───────────────────────────────────────────── */
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4.5rem 1.5rem 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid rgba(79, 70, 229, 0.2);
            border-radius: 99px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: clamp(2.25rem, 5vw, 3.85rem);
            font-weight: 900;
            line-height: 1.15;
            color: var(--text-heading);
            letter-spacing: -0.03em;
            max-width: 950px;
            margin-bottom: 1.25rem;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.12rem;
            color: var(--text-muted);
            max-width: 720px;
            margin-bottom: 2.5rem;
            line-height: 1.65;
            font-weight: 450;
        }

        .hero-cta-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3.5rem;
        }

        .btn-primary-cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 0.95rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-primary-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 30px -4px rgba(79, 70, 229, 0.5);
            color: white;
        }

        .btn-secondary-cta {
            background: var(--surface);
            color: var(--text-heading);
            padding: 0.95rem 1.8rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
        }

        .btn-secondary-cta:hover {
            background: var(--surface-hover);
            border-color: var(--border-focus);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ─── METRICS STATS BAR ──────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.6rem 1.25rem;
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
            text-align: left;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-focus);
        }

        .stat-icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 0.9rem;
        }

        .stat-card.blue .stat-icon-wrap { background: #EEF2FF; color: #4F46E5; }
        .stat-card.sky .stat-icon-wrap { background: #E0F2FE; color: #0284C7; }
        .stat-card.emerald .stat-icon-wrap { background: #ECFDF5; color: #10B981; }
        .stat-card.amber .stat-icon-wrap { background: #FEF3C7; color: #D97706; }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--text-heading);
            line-height: 1;
            margin-bottom: 0.35rem;
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.03em;
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 0.15rem;
        }

        .stat-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* ─── SECTION HEADERS ────────────────────────────────────────── */
        .section-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 5rem 1.5rem;
            width: 100%;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 3.5rem;
        }

        .section-tag {
            color: var(--primary);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
            display: block;
        }

        .section-title {
            font-size: clamp(1.85rem, 3.5vw, 2.6rem);
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
        }

        .section-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ─── FEATURES GRID (10 FITUR LENGKAP) ────────────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.85rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(79, 70, 229, 0.3);
        }

        .feature-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .feature-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .feature-badge-tag {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            background: var(--surface-hover);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .feature-title {
            font-size: 1.18rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 0.55rem;
            letter-spacing: -0.01em;
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .feature-footer-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: auto;
            padding-top: 0.85rem;
            border-top: 1px solid #F1F5F9;
        }

        .mini-tag {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 5px;
            background: #F8FAFC;
            color: #475569;
            border: 1px solid #E2E8F0;
        }

        /* ─── MOBILE APP BANNER ──────────────────────────────────────── */
        .app-download-box {
            background: linear-gradient(135deg, #1E1B4B 0%, #0F172A 100%);
            border-radius: 28px;
            padding: 3.5rem 3rem;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .app-download-box::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 350px;
            height: 350px;
            background: rgba(79, 70, 229, 0.25);
            border-radius: 50%;
            filter: blur(80px);
        }

        .app-download-content {
            max-width: 600px;
            position: relative;
            z-index: 2;
        }

        .app-badge-version {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            color: #A5B4FC;
            padding: 0.35rem 0.85rem;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .app-download-title {
            font-size: clamp(1.8rem, 3.5vw, 2.4rem);
            font-weight: 900;
            line-height: 1.2;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .app-download-desc {
            color: #CBD5E1;
            font-size: 0.98rem;
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .app-highlights {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .app-highlight-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.86rem;
            color: #E2E8F0;
            font-weight: 500;
        }

        .app-highlight-item i {
            color: #10B981;
            font-size: 0.95rem;
        }

        .btn-app-action {
            background: var(--primary);
            color: white;
            padding: 0.95rem 2.2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.5);
            transition: all 0.3s ease;
        }

        .btn-app-action:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 16px 30px -4px rgba(79, 70, 229, 0.6);
        }

        .app-phone-mockup {
            position: relative;
            z-index: 2;
            flex-shrink: 0;
            text-align: center;
        }

        .mockup-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 1.5rem;
            width: 270px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .mockup-status-dot {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        /* ─── FOOTER ─────────────────────────────────────────────────── */
        footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 3rem 6% 2rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 800;
            color: var(--text-heading);
            font-size: 1.15rem;
        }

        .footer-links {
            display: flex;
            gap: 1.75rem;
            list-style: none;
        }

        .footer-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 1.5rem auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* ─── RESPONSIVE ─────────────────────────────────────────────── */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .app-download-box {
                flex-direction: column;
                text-align: center;
                padding: 2.5rem 1.5rem;
            }
            .app-download-content {
                max-width: 100%;
            }
            .app-highlights {
                text-align: left;
            }
            .nav-links {
                display: none;
            }
        }

        @media (max-width: 640px) {
            nav {
                padding: 0.75rem 4%;
            }
            .hero {
                padding: 3rem 1rem 1.5rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .app-highlights {
                grid-template-columns: 1fr;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }
            .footer-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- ─── NAVBAR ───────────────────────────────────────────────────── -->
    <nav>
        <a href="/" class="brand-link">
            @if(isset($setting) && $setting->logo_path)
                <img src="{{ Storage::url($setting->logo_path) }}" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="brand-badge" style="display: none;">ESA</div>
            @else
                <div class="brand-badge">ESA</div>
            @endif
            <span>{{ $setting->app_name ?? 'ESA Groups' }}</span>
        </a>

        <ul class="nav-links">
            <li><a href="#fitur">Fitur Unggulan</a></li>
            <li><a href="#statistik">Statistik Operasional</a></li>
            <li><a href="#download">Download APK</a></li>
            <li><a href="/login">Portal Prinsiple</a></li>
        </ul>

        <div class="nav-actions">
            <a href="/admin" class="btn-nav-login">
                <i class="fa-solid fa-lock" style="font-size: 0.8rem;"></i>
                <span>Admin Login</span>
            </a>
        </div>
    </nav>

    <!-- ─── HERO SECTION ─────────────────────────────────────────────── -->
    <header class="hero">
        <div class="hero-badge">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Sistem Presensi & Manajemen Kinerja Cerdas</span>
        </div>

        <h1 class="hero-title">
            Pantau Kehadiran & Kinerja Lapangan <span class="gradient-text">Secara Akurat</span>
        </h1>

        <p class="hero-subtitle">
            Platform operasional terintegrasi dengan verifikasi biometrik AI liveness, geofencing GPS real-time, jadwal multi-shift roster, dan sistem formulir pelaporan dinamis multi-tenant.
        </p>

        <div class="hero-cta-group">
            @php
                $apkDownloadUrl = $setting->mobile_app_url ?: '/app-release.apk';
            @endphp
            <a href="{{ $apkDownloadUrl }}" class="btn-primary-cta" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-download"></i>
                <span>Download APK Mobile</span>
                <span style="font-size: 0.75rem; background: rgba(255,255,255,0.22); padding: 0.2rem 0.5rem; border-radius: 6px;">v1.0.114</span>
            </a>

            <a href="/admin" class="btn-secondary-cta">
                <i class="fa-solid fa-gauge-high" style="color: var(--primary);"></i>
                <span>Buka Admin Panel</span>
            </a>
        </div>

        <!-- ─── STATISTIK LIVE OPERASIONAL ────────────────────────────── -->
        <section id="statistik" class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['employees'] ?? 0) }}</div>
                <div class="stat-title">Total Karyawan Aktif</div>
                <div class="stat-desc">Karyawan aktif terlindungi</div>
            </div>

            <div class="stat-card sky">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['principals'] ?? 0) }}</div>
                <div class="stat-title">Prinsiple & Mitra Aktif</div>
                <div class="stat-desc">Brand & unit bisnis terdaftar</div>
            </div>

            <div class="stat-card emerald">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['areas'] ?? 0) }}</div>
                <div class="stat-title">Area & Cabang</div>
                <div class="stat-desc">Cakupan wilayah operasional</div>
            </div>

            <div class="stat-card amber">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['locations'] ?? 0) }}</div>
                <div class="stat-title">Lokasi Kerja & Outlet</div>
                <div class="stat-desc">Titik toko & geofence aktif</div>
            </div>
        </section>
    </header>

    <!-- ─── FITUR SISTEM UNGGULAN ────────────────────────────────────── -->
    <main id="fitur" class="section-wrap">
        <div class="section-header">
            <span class="section-tag">Fitur Lengkap & Terpadu</span>
            <h2 class="section-title">Teknologi Cerdas untuk Efisiensi Tim Lapangan</h2>
            <p class="section-subtitle">
                Dirancang khusus untuk mengelola ribuan tenaga kerja lapangan, promotor, sales, dan staf kantor dalam satu ekosistem terpusat.
            </p>
        </div>

        <div class="features-grid">
            <!-- 1. Live Tracking & Geofence -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #EEF2FF; color: #4F46E5;">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </div>
                    <span class="feature-badge-tag">Geofencing</span>
                </div>
                <h3 class="feature-title">Live GPS & Geofence Lock</h3>
                <p class="feature-desc">
                    Validasi presensi akurat berbasis radius titik koordinat toko atau kantor. Dilengkapi proteksi anti fake-GPS dan interval tracking pergerakan real-time.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Radius Meter</span>
                    <span class="mini-tag">Anti Mock GPS</span>
                    <span class="mini-tag">Histori Rute</span>
                </div>
            </div>

            <!-- 2. AI Liveness Face Recognition -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #E0F2FE; color: #0284C7;">
                        <i class="fa-solid fa-face-smile-beam"></i>
                    </div>
                    <span class="feature-badge-tag">AI Biometrics</span>
                </div>
                <h3 class="feature-title">AI Liveness Face Recognition</h3>
                <p class="feature-desc">
                    Verifikasi wajah biometrik otomatis anti-spoofing (kedipan & gerakan) tanpa tombol shutter manual untuk menjamin keaslian kehadiran karyawan di lokasi.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Auto Capture</span>
                    <span class="mini-tag">Master Enrollment</span>
                    <span class="mini-tag">Anti Foto Tiruan</span>
                </div>
            </div>

            <!-- 3. Dynamic Form Builder -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #ECFDF5; color: #10B981;">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <span class="feature-badge-tag">Reporting Hub</span>
                </div>
                <h3 class="feature-title">Dynamic Form Reporting Hub</h3>
                <p class="feature-desc">
                    Formulir laporan kunjungan lapangan yang fleksibel untuk berbagai prinsiple (Dulux, Wings, Fonterra, Mamasuka) dengan kalkulasi otomatis dan foto multi-angle.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Auto Sum Calculation</span>
                    <span class="mini-tag">Month-Year Picker</span>
                    <span class="mini-tag">Multi Photo</span>
                </div>
            </div>

            <!-- 4. Adaptive Roster & Multi-Shift -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #FEF3C7; color: #D97706;">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <span class="feature-badge-tag">Shift Management</span>
                </div>
                <h3 class="feature-title">Adaptive Roster & Multi-Shift</h3>
                <p class="feature-desc">
                    Penjadwalan harian fleksibel, pengaturan kelompok kerja (working groups), kalender libur nasional, dan dashboard monitoring karyawan belum check-in.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Import Excel Roster</span>
                    <span class="mini-tag">Pola Shift Otomatis</span>
                    <span class="mini-tag">Monitoring Alpha</span>
                </div>
            </div>

            <!-- 5. Itinerary & Visit Scheduling -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #F3E8FF; color: #9333EA;">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <span class="feature-badge-tag">Field Visit</span>
                </div>
                <h3 class="feature-title">Itinerary & Visit Scheduling</h3>
                <p class="feature-desc">
                    Perencanaan jadwal kunjungan toko harian bagi SPG & MD. Alur terstruktur mulai dari Visit-In di lokasi, pengisian laporan toko, hingga Visit-Out.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Target Toko</span>
                    <span class="mini-tag">Strict Routing</span>
                    <span class="mini-tag">Check-in Terjadwal</span>
                </div>
            </div>

            <!-- 6. Form BAP (Bukti Absensi Manual) -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #EFF6FF; color: #2563EB;">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <span class="feature-badge-tag">Manual Proof</span>
                </div>
                <h3 class="feature-title">BAP & Bukti Absensi Manual</h3>
                <p class="feature-desc">
                    Solusi resmi saat terjadi kendala teknis (aplikasi error, GPS down, no signal) dengan mengunggah screenshot timestamp camera yang diverifikasi oleh Admin/HR.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Auto Validasi Jadwal</span>
                    <span class="mini-tag">Approval Admin</span>
                    <span class="mini-tag">Penghilang Alpha</span>
                </div>
            </div>

            <!-- 7. Whitelabel Tenant Portal -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #FFF1F2; color: #E11D48;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <span class="feature-badge-tag">Multi-Tenant</span>
                </div>
                <h3 class="feature-title">Whitelabel Principal Portal</h3>
                <p class="feature-desc">
                    Portal khusus berbasis subdomain mandiri untuk setiap prinsiple (misal: dulux.appsend.my.id) guna memantau tim, rekap kehadiran, dan ekspor laporan kerja.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Custom Branding</span>
                    <span class="mini-tag">Subdomain Tenant</span>
                    <span class="mini-tag">Ekspor Excel / PDF</span>
                </div>
            </div>

            <!-- 8. Request Lokasi Baru -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #ECFEFF; color: #0891B2;">
                        <i class="fa-solid fa-map-pin"></i>
                    </div>
                    <span class="feature-badge-tag">New Outlet</span>
                </div>
                <h3 class="feature-title">Request Lokasi Toko Baru</h3>
                <p class="feature-desc">
                    Kemudahan bagi staf lapangan untuk mengajukan pendaftaran toko/outlet baru dari aplikasi mobile dengan auto-extract koordinat dari tautan Google Maps.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Google Maps Parser</span>
                    <span class="mini-tag">Foto Toko</span>
                    <span class="mini-tag">Approval Master Data</span>
                </div>
            </div>

            <!-- 9. Extra Hours & Overtime -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #FDF2F8; color: #DB2777;">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                    <span class="feature-badge-tag">Overtime</span>
                </div>
                <h3 class="feature-title">Lembur (Extra Hours) & Izin</h3>
                <p class="feature-desc">
                    Pencatatan jam lembur berbasis durasi GPS langsung dari aplikasi mobile serta sistem pengajuan izin/cuti tahunan & cuti peraturan perusahaan.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Timer Real-Time</span>
                    <span class="mini-tag">Kuota Cuti</span>
                    <span class="mini-tag">Notifikasi FCM</span>
                </div>
            </div>

            <!-- 10. Work Target & Payslip Digital -->
            <div class="feature-card">
                <div class="feature-top">
                    <div class="feature-icon-box" style="background: #F0FDF4; color: #16A34A;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <span class="feature-badge-tag">Payroll & Target</span>
                </div>
                <h3 class="feature-title">Target Penjualan & Slip Gaji</h3>
                <p class="feature-desc">
                    Pemantauan target omzet dan pipeline prospek penjualan bulanan, serta distribusi slip gaji digital terenkripsi yang dapat diakses langsung oleh karyawan.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-tag">Sales Pipeline</span>
                    <span class="mini-tag">PDF Payslip</span>
                    <span class="mini-tag">Aman & Terenkripsi</span>
                </div>
            </div>
        </div>
    </main>

    <!-- ─── DOWNLOAD APK BOX SECTION ─────────────────────────────────── -->
    <section id="download" class="section-wrap" style="padding-top: 1rem;">
        <div class="app-download-box">
            <div class="app-download-content">
                <span class="app-badge-version">
                    <i class="fa-brands fa-android"></i> Versi Rilis Terbaru v1.0.114
                </span>
                <h2 class="app-download-title">Unduh Aplikasi Mobile ESA Groups Sekarang</h2>
                <p class="app-download-desc">
                    Tersedia untuk smartphone Android. Nikmati kemudahan check-in cepat dengan AI liveness camera, pelaporan dinamis, dan pemantauan jadwal kerja harian Anda.
                </p>

                <div class="app-highlights">
                    <div class="app-highlight-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>AI Liveness Face Recognition</span>
                    </div>
                    <div class="app-highlight-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Form Pengajuan BAP Manual</span>
                    </div>
                    <div class="app-highlight-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>10+ Template Laporan Kunjungan</span>
                    </div>
                    <div class="app-highlight-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Offline Queue & Sync Otomatis</span>
                    </div>
                </div>

                <a href="{{ $apkDownloadUrl }}" class="btn-app-action" target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-download"></i>
                    <span>Download APK Installer (106 MB)</span>
                </a>
            </div>

            <div class="app-phone-mockup">
                <div class="mockup-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="font-size: 0.75rem; color: #94A3B8; font-weight: 700;">ESA ATTENDANCE</span>
                        <span style="font-size: 0.72rem; color: #10B981; font-weight: 700;"><span class="mockup-status-dot"></span>LIVE</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.06); padding: 1rem; border-radius: 14px; margin-bottom: 0.85rem; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="font-size: 0.75rem; color: #94A3B8;">Status Kehadiran</div>
                        <div style="font-size: 1.15rem; font-weight: 800; color: #38BDF8; margin-top: 2px;">Terjadwal & Aktif</div>
                    </div>
                    <div style="font-size: 0.75rem; color: #CBD5E1; line-height: 1.4;">
                        Kompatibel dengan Android 8.0 ke atas. Aman, cepat, dan terproteksi.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── FOOTER ───────────────────────────────────────────────────── -->
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="brand-badge" style="width: 30px; height: 30px; font-size: 0.8rem; border-radius: 8px;">ESA</div>
                <span>{{ $setting->app_name ?? 'ESA Groups' }}</span>
            </div>

            <ul class="footer-links">
                <li><a href="#fitur">Fitur Sistem</a></li>
                <li><a href="#statistik">Statistik</a></li>
                <li><a href="#download">Download APK</a></li>
                <li><a href="/login">Portal Prinsiple</a></li>
                <li><a href="/admin">Admin Panel</a></li>
            </ul>
        </div>

        <div class="footer-bottom">
            <div>&copy; {{ date('Y') }} {{ $setting->company_name ?? 'PT ESA Groups' }}. All rights reserved.</div>
            <div>Integrated Workforce & Attendance Management System</div>
        </div>
    </footer>

</body>
</html>
