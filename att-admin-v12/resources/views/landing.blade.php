<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if(isset($isEntityServer) && $isEntityServer && isset($currentEntity))
            {{ $setting->app_name ?? $currentEntity['name'] }} - Sistem Presensi & Manajemen Kinerja
        @else
            {{ $setting->app_name ?? 'ESA Solutions' }} - Ekosistem Presensi & Manajemen Kinerja Terintegrasi
        @endif
    </title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Alpine.js 3.x for Reactive Filter & Modal -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }

        :root {
            --brand-primary: {{ isset($currentEntity) && $currentEntity['color'] ? $currentEntity['color'] : '#4F46E5' }};
            --brand-primary-hover: {{ isset($currentEntity) && $currentEntity['color_secondary'] ? $currentEntity['color_secondary'] : '#4338CA' }};
            --brand-primary-light: #EEF2FF;
            --brand-primary-glow: rgba(79, 70, 229, 0.22);
            
            --brand-secondary: #0284C7;
            --brand-secondary-hover: #0369A1;
            --brand-secondary-light: #E0F2FE;
            
            --brand-accent: #10B981;
            --brand-accent-light: #ECFDF5;
            
            --bg-body: #F8FAFC;
            --surface: #FFFFFF;
            --surface-alt: #F1F5F9;
            --surface-hover: #F8FAFC;
            
            --border: #E2E8F0;
            --border-hover: #CBD5E1;
            
            --text-heading: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --text-light: #94A3B8;
            
            --shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-sm: 0 2px 4px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.03);
            --shadow-md: 0 4px 12px -1px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 12px 24px -4px rgba(15, 23, 42, 0.08), 0 4px 8px -3px rgba(15, 23, 42, 0.04);
            --shadow-xl: 0 24px 38px -6px rgba(15, 23, 42, 0.10), 0 8px 16px -6px rgba(15, 23, 42, 0.05);
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
                radial-gradient(circle at 12% 8%, rgba(79, 70, 229, 0.05) 0%, transparent 42%),
                radial-gradient(circle at 88% 12%, rgba(2, 132, 199, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 50% 60%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* ─── TOP SERVER CONTEXT BAR (FOR ENTITY SUBDOMAINS) ─────────── */
        .entity-top-bar {
            background: #0F172A;
            color: #E2E8F0;
            padding: 0.45rem 6%;
            font-size: 0.78rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .entity-top-bar a {
            color: #38BDF8;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .entity-top-bar a:hover {
            text-decoration: underline;
        }

        /* ─── NAVBAR ─────────────────────────────────────────────────── */
        nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
            font-size: 1.22rem;
            letter-spacing: -0.02em;
        }

        .brand-logo-img {
            height: 40px;
            max-width: 170px;
            object-fit: contain;
        }

        .brand-badge {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px var(--brand-primary-glow);
            letter-spacing: -0.02em;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.75rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .nav-links a:hover {
            color: var(--brand-primary);
        }

        .nav-highlight-link {
            background: rgba(79, 70, 229, 0.08);
            color: var(--brand-primary) !important;
            padding: 0.45rem 0.95rem;
            border-radius: 999px;
            border: 1px solid rgba(79, 70, 229, 0.2);
            font-weight: 700 !important;
        }

        .nav-highlight-link:hover {
            background: rgba(79, 70, 229, 0.15);
            transform: translateY(-1px);
        }

        .nav-pulse-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #10B981;
            color: white;
            padding: 0.15rem 0.45rem;
            border-radius: 99px;
            line-height: 1;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-nav-portal {
            background: #F1F5F9;
            color: var(--text-heading);
            padding: 0.6rem 1.15rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid var(--border);
        }

        .btn-nav-portal:hover {
            background: #E2E8F0;
            color: var(--text-heading);
        }

        .btn-nav-login {
            background: var(--text-heading);
            color: white;
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-size: 0.85rem;
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
            color: white;
        }

        /* ─── HERO SECTION ───────────────────────────────────────────── */
        .hero {
            max-width: 1240px;
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
            gap: 0.6rem;
            padding: 0.45rem 1.15rem;
            background: var(--brand-primary-light);
            color: var(--brand-primary);
            border: 1px solid rgba(79, 70, 229, 0.25);
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
        }

        .hero-title {
            font-size: clamp(2.35rem, 5.2vw, 3.9rem);
            font-weight: 900;
            line-height: 1.15;
            color: var(--text-heading);
            letter-spacing: -0.035em;
            max-width: 1020px;
            margin-bottom: 1.35rem;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 50%, #06B6D4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.12rem;
            color: var(--text-muted);
            max-width: 780px;
            margin-bottom: 2.5rem;
            line-height: 1.7;
            font-weight: 450;
        }

        /* ─── DOWNLOAD BUTTONS & HERO CTA ────────────────────────────── */
        .hero-cta-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3.5rem;
        }

        /* Android Download Button */
        .btn-download-android {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-download-android:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: #10B981;
            color: white;
        }

        .btn-download-android i {
            color: #10B981;
            font-size: 1.65rem;
        }

        /* iOS Download Button */
        .btn-download-ios {
            background: #FFFFFF;
            color: var(--text-heading);
            padding: 0.75rem 1.5rem;
            border-radius: 14px;
            border: 1px solid var(--border);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-download-ios:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: #0F172A;
            background: #F8FAFC;
        }

        .btn-download-ios i {
            color: #0F172A;
            font-size: 1.75rem;
        }

        .btn-cta-text {
            text-align: left;
            line-height: 1.25;
        }

        .btn-cta-sub {
            font-size: 0.72rem;
            display: block;
            opacity: 0.8;
            font-weight: 500;
        }

        .btn-cta-main {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .badge-dev-tag {
            font-size: 0.68rem;
            font-weight: 800;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FDE68A;
            margin-left: 0.35rem;
            text-transform: uppercase;
        }

        .btn-hero-portal-hub {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            color: white;
            padding: 0.85rem 1.75rem;
            border-radius: 14px;
            font-size: 0.98rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-hero-portal-hub:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px -4px rgba(79, 70, 229, 0.55);
            color: white;
        }

        .btn-hero-admin {
            background: #F1F5F9;
            color: var(--text-heading);
            padding: 0.85rem 1.6rem;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            border: 1px solid var(--border);
            transition: all 0.25s ease;
        }

        .btn-hero-admin:hover {
            background: #E2E8F0;
            color: var(--text-heading);
            transform: translateY(-2px);
        }

        /* ─── STATS COUNTER BAR ──────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            width: 100%;
            max-width: 1160px;
            margin: 0 auto;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.6rem 1.35rem;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            text-align: left;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }

        .stat-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .stat-card.blue .stat-icon-wrap { background: #EEF2FF; color: #4F46E5; }
        .stat-card.sky .stat-icon-wrap { background: #E0F2FE; color: #0284C7; }
        .stat-card.emerald .stat-icon-wrap { background: #ECFDF5; color: #10B981; }
        .stat-card.amber .stat-icon-wrap { background: #FEF3C7; color: #D97706; }

        .stat-number {
            font-size: 2.35rem;
            font-weight: 900;
            color: var(--text-heading);
            line-height: 1;
            margin-bottom: 0.35rem;
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.03em;
        }

        .stat-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 0.2rem;
        }

        .stat-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* ─── SECTION WRAPPER & HEADERS ─────────────────────────────── */
        .section-wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding: 5rem 1.5rem;
            width: 100%;
        }

        .section-header {
            text-align: center;
            max-width: 760px;
            margin: 0 auto 3.5rem;
        }

        .section-tag {
            color: var(--brand-primary);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.65rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--brand-primary-light);
            padding: 0.35rem 0.9rem;
            border-radius: 99px;
            border: 1px solid rgba(79, 70, 229, 0.2);
        }

        .section-title {
            font-size: clamp(2rem, 3.8vw, 2.75rem);
            font-weight: 900;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            margin-bottom: 0.85rem;
            line-height: 1.2;
        }

        .section-subtitle {
            font-size: 1.05rem;
            color: var(--text-muted);
            line-height: 1.65;
        }

        /* ─── SUBDOMAIN HUB (PORTAL NAVIGATOR) ───────────────────────── */
        .subdomain-hub-container {
            background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 3rem 2.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .hub-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #F1F5F9;
            padding: 0.35rem;
            border-radius: 14px;
            border: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 0.55rem 1.1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .tab-btn.active {
            background: #FFFFFF;
            color: var(--text-heading);
            box-shadow: var(--shadow-sm);
        }

        .tab-btn .badge-count {
            font-size: 0.72rem;
            padding: 0.15rem 0.5rem;
            border-radius: 99px;
            background: rgba(15, 23, 42, 0.08);
            color: inherit;
        }

        .tab-btn.active .badge-count {
            background: var(--brand-primary-light);
            color: var(--brand-primary);
        }

        .search-box-wrap {
            position: relative;
            min-width: 280px;
            flex-grow: 1;
            max-width: 380px;
        }

        .search-box-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .search-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.6rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 0.88rem;
            outline: none;
            background: #FFFFFF;
            color: var(--text-heading);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .subdomain-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .subdomain-card {
            background: #FFFFFF;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .subdomain-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-accent, var(--brand-primary));
            opacity: 0.9;
        }

        .subdomain-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--border-hover);
        }

        .card-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .category-pill {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            background: #F1F5F9;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .category-pill.company { background: #EEF2FF; color: #4338CA; border-color: #C7D2FE; }
        .category-pill.principal { background: #F0FDF4; color: #15803D; border-color: #BBF7D0; }
        .category-pill.system { background: #F5F3FF; color: #6D28D9; border-color: #DDD6FE; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #10B981;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10B981;
            box-shadow: 0 0 6px #10B981;
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
        }

        .brand-profile-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .brand-avatar {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 900;
            font-size: 1.15rem;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
        }

        .brand-info {
            overflow: hidden;
        }

        .brand-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.015em;
            line-height: 1.25;
            margin-bottom: 0.2rem;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .brand-subdomain-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-family: 'Outfit', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--brand-primary);
        }

        .brand-subdomain-chip:hover {
            border-color: var(--brand-primary);
        }

        .copy-sub-btn {
            background: transparent;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 0.75rem;
            padding: 1px 3px;
            transition: color 0.15s ease;
        }

        .copy-sub-btn:hover {
            color: var(--brand-primary);
        }

        .card-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 1.25rem;
            flex-grow: 1;
        }

        .card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-bottom: 1.35rem;
        }

        .mini-feature-tag {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            background: #F8FAFC;
            color: #475569;
            border: 1px solid #E2E8F0;
        }

        .card-action-group {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.65rem;
            margin-top: auto;
            padding-top: 1.15rem;
            border-top: 1px solid #F1F5F9;
        }

        .btn-open-subdomain {
            background: var(--card-accent, var(--brand-primary));
            color: white;
            text-decoration: none;
            padding: 0.7rem 1.15rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .btn-open-subdomain:hover {
            filter: brightness(1.08);
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
            color: white;
        }

        .btn-login-subdomain {
            background: #F8FAFC;
            color: var(--text-heading);
            text-decoration: none;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .btn-login-subdomain:hover {
            background: #F1F5F9;
            border-color: var(--border-hover);
            color: var(--text-heading);
        }

        .empty-search-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3.5rem 1.5rem;
            color: var(--text-muted);
        }

        .empty-search-state i {
            font-size: 2.5rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        /* ─── IOS MODAL NOTIFICATION ─────────────────────────────────── */
        .ios-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(8px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .ios-modal-card {
            background: #FFFFFF;
            border-radius: 24px;
            padding: 2.5rem 2rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
            position: relative;
            animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalPop {
            0% { transform: scale(0.92); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .ios-modal-icon {
            width: 68px;
            height: 68px;
            border-radius: 20px;
            background: #0F172A;
            color: white;
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
        }

        .ios-badge-pill {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FDE68A;
            padding: 0.3rem 0.85rem;
            border-radius: 99px;
            margin-bottom: 1rem;
        }

        .ios-modal-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 0.75rem;
            letter-spacing: -0.015em;
        }

        .ios-modal-desc {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .ios-modal-tip {
            background: #F0F9FF;
            border: 1px solid #BAE6FD;
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font-size: 0.84rem;
            color: #0369A1;
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            text-align: left;
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        .ios-modal-tip i {
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .ios-modal-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-modal-android {
            background: #0F172A;
            color: white;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            transition: all 0.2s ease;
        }

        .btn-modal-android:hover {
            background: #000000;
            color: white;
        }

        .btn-modal-close {
            background: #F1F5F9;
            color: var(--text-muted);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-modal-close:hover {
            background: #E2E8F0;
            color: var(--text-heading);
        }

        /* ─── TOAST NOTIFICATION ─────────────────────────────────────── */
        .toast-copied {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #0F172A;
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: var(--shadow-xl);
            z-index: 9999;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ─── FEATURES GRID ──────────────────────────────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
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
            background: var(--surface-alt);
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

        /* ─── MOBILE APP BANNER ──────────────────────────────────────── */
        .app-download-box {
            background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 100%);
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
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .app-download-box::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -80px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .app-download-content {
            max-width: 620px;
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
            font-size: clamp(1.85rem, 3.5vw, 2.45rem);
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
            margin-bottom: 2.25rem;
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

        .app-download-action-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-banner-android {
            background: #FFFFFF;
            color: #0F172A;
            padding: 0.85rem 1.6rem;
            border-radius: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            font-weight: 800;
            font-size: 0.95rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.25s ease;
        }

        .btn-banner-android:hover {
            transform: translateY(-2px);
            background: #F8FAFC;
            color: #000;
        }

        .btn-banner-android i {
            color: #10B981;
            font-size: 1.5rem;
        }

        .btn-banner-ios {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            color: #FFFFFF;
            padding: 0.85rem 1.6rem;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-banner-ios:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
        }

        .btn-banner-ios i {
            font-size: 1.5rem;
        }

        .app-phone-mockup {
            position: relative;
            z-index: 2;
            flex-shrink: 0;
            text-align: center;
        }

        .mockup-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 24px;
            padding: 1.65rem;
            width: 280px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55);
            text-align: left;
        }

        /* ─── FOOTER ─────────────────────────────────────────────────── */
        footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 4rem 6% 2.5rem;
            margin-top: auto;
        }

        .footer-grid {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            padding-bottom: 3rem;
            border-bottom: 1px solid var(--border);
        }

        .footer-brand-col {
            max-width: 360px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 800;
            color: var(--text-heading);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            text-decoration: none;
        }

        .footer-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.25rem;
        }

        .footer-col-title {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-heading);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.25rem;
        }

        .footer-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-list a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 500;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .footer-list a:hover {
            color: var(--brand-primary);
        }

        .footer-bottom {
            max-width: 1240px;
            margin: 2rem auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* ─── RESPONSIVE ─────────────────────────────────────────────── */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
            .app-download-box {
                flex-direction: column;
                text-align: center;
                padding: 2.75rem 1.5rem;
            }
            .app-download-content {
                max-width: 100%;
            }
            .app-highlights {
                text-align: left;
            }
            .app-download-action-row {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            nav {
                padding: 0.8rem 4%;
            }
            .nav-links {
                display: none;
            }
            .hero {
                padding: 3rem 1rem 1.5rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .subdomain-hub-container {
                padding: 2rem 1.25rem;
            }
            .subdomain-grid {
                grid-template-columns: 1fr;
            }
            .hub-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box-wrap {
                max-width: 100%;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .app-highlights {
                grid-template-columns: 1fr;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body x-data="landingApp()">

    @php
        $apkDownloadUrl = $setting->mobile_app_url ?: '/app-release.apk';
    @endphp

    <!-- ─── TOP CONTEXT STRIP (JIKA SEDANG MENGAKSES SUBDOMAIN ENTITAS) ── -->
    @if(isset($isEntityServer) && $isEntityServer && isset($currentEntity))
        <div class="entity-top-bar">
            <div>
                <i class="fa-solid fa-server" style="color: #38BDF8; margin-right: 6px;"></i>
                <span>Anda sedang mengakses Node Server: <strong>{{ $currentEntity['tag'] }}</strong></span>
            </div>
            <div>
                <a href="https://{{ $baseDomain ?? 'esa-solutions.id' }}">
                    <span>Portal Utama esa-solutions.id</span>
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- ─── NAVBAR ───────────────────────────────────────────────────── -->
    <nav>
        <a href="/" class="brand-link">
            @if(isset($setting) && $setting->logo_path)
                <img src="/app-logo" alt="{{ $setting->app_name ?? 'Logo' }}" class="brand-logo-img" onerror="this.style.display='none'; document.getElementById('nav-brand-fallback').style.display='flex';">
                <div id="nav-brand-fallback" style="display: none; align-items: center; gap: 0.85rem;">
                    <div class="brand-badge">{{ isset($currentEntity) ? $currentEntity['code'] : 'ESA' }}</div>
                    <span>{{ $setting->app_name ?? (isset($currentEntity) ? $currentEntity['name'] : 'ESA Solutions') }}</span>
                </div>
            @else
                <div class="brand-badge">{{ isset($currentEntity) ? $currentEntity['code'] : 'ESA' }}</div>
                <span>{{ $setting->app_name ?? (isset($currentEntity) ? $currentEntity['name'] : 'ESA Solutions') }}</span>
            @endif
        </a>

        <ul class="nav-links">
            @if(!isset($isEntityServer) || !$isEntityServer)
                <li>
                    <a href="#subdomain-hub" class="nav-highlight-link">
                        <i class="fa-solid fa-cubes-stacked"></i>
                        <span>Akses Subdomain</span>
                        <span class="nav-pulse-badge">Live</span>
                    </a>
                </li>
            @endif
            <li><a href="#fitur"><i class="fa-solid fa-layer-group"></i> Fitur Sistem</a></li>
            <li><a href="#statistik"><i class="fa-solid fa-chart-pie"></i> Statistik</a></li>
            <li><a href="#download"><i class="fa-solid fa-mobile-screen"></i> Download Aplikasi</a></li>
            <li><a href="/login"><i class="fa-solid fa-id-card-clip"></i> Portal Login</a></li>
        </ul>

        <div class="nav-actions">
            @if(!isset($isEntityServer) || !$isEntityServer)
                <a href="#subdomain-hub" class="btn-nav-portal">
                    <i class="fa-solid fa-compass" style="color: var(--brand-primary);"></i>
                    <span>Pilih Portal</span>
                </a>
            @else
                <a href="/login" class="btn-nav-portal">
                    <i class="fa-solid fa-right-to-bracket" style="color: var(--brand-primary);"></i>
                    <span>Login Karyawan</span>
                </a>
            @endif
            <a href="/admin" class="btn-nav-login">
                <i class="fa-solid fa-lock" style="font-size: 0.8rem;"></i>
                <span>Admin Panel</span>
            </a>
        </div>
    </nav>

    <!-- ─── HERO SECTION ─────────────────────────────────────────────── -->
    <header class="hero">
        <div class="hero-badge">
            <i class="fa-solid fa-shield-halved"></i>
            @if(isset($isEntityServer) && $isEntityServer && isset($currentEntity))
                <span>{{ $currentEntity['name'] }} — Dedicated Attendance & Performance Hub</span>
            @else
                <span>Ekosistem Presensi & Manajemen Kinerja Multi-Server Cloud</span>
            @endif
        </div>

        <h1 class="hero-title">
            @if(isset($isEntityServer) && $isEntityServer && isset($currentEntity))
                Pantau Kehadiran & Kinerja Lapangan <span class="gradient-text">{{ $currentEntity['name'] }}</span>
            @else
                Infrastruktur Terpadu untuk Pengelolaan Tim Lapangan <span class="gradient-text">Skala Nasional</span>
            @endif
        </h1>

        <p class="hero-subtitle">
            @if(isset($isEntityServer) && $isEntityServer && isset($currentEntity))
                Sistem operasional terintegrasi bagi seluruh karyawan dan manajemen {{ $currentEntity['name'] }}. Didukung verifikasi biometrik AI liveness, geofencing GPS akurat, roster multi-shift, pengajuan BAP manual, dan pelaporan dinamis.
            @else
                Akses portal terpusat <strong>esa-solutions.id</strong> yang menghubungkan klaster server perusahaan (AMK, AKP, ATK), portal pelaporan mitra principal whitelabel (Dulux, Fonterra, MamaSuka, Wings), dan sinkronisasi presensi biometrik AI real-time.
            @endif
        </p>

        <!-- Download Buttons (Android & iOS) + Quick Actions in Hero -->
        <div class="hero-cta-group">
            <!-- 1. Tombol Download Android -->
            <a href="{{ $apkDownloadUrl }}" class="btn-download-android" target="_blank" rel="noopener noreferrer">
                <i class="fa-brands fa-android"></i>
                <div class="btn-cta-text">
                    <span class="btn-cta-sub">Unduh untuk</span>
                    <span class="btn-cta-main">Android (APK)</span>
                </div>
                <span style="font-size: 0.72rem; background: rgba(255,255,255,0.18); padding: 0.15rem 0.45rem; border-radius: 6px; margin-left: 0.2rem;">v1.0.114</span>
            </a>

            <!-- 2. Tombol Download iOS (Under Development Notice) -->
            <button type="button" class="btn-download-ios" @click="showIosNotice()">
                <i class="fa-brands fa-apple"></i>
                <div class="btn-cta-text">
                    <span class="btn-cta-sub">Tersedia di</span>
                    <span class="btn-cta-main">Apple iOS</span>
                </div>
                <span class="badge-dev-tag">Dev</span>
            </button>

            <!-- 3. Tombol Portal Hub (hanya di main domain) / Admin Console -->
            @if(!isset($isEntityServer) || !$isEntityServer)
                <a href="#subdomain-hub" class="btn-hero-portal-hub">
                    <i class="fa-solid fa-cubes"></i>
                    <span>Akses Subdomain</span>
                    <i class="fa-solid fa-arrow-down" style="font-size: 0.82rem;"></i>
                </a>
            @else
                <a href="/admin" class="btn-hero-admin">
                    <i class="fa-solid fa-gauge-high" style="color: var(--brand-primary);"></i>
                    <span>Admin Panel</span>
                </a>
            @endif
        </div>

        <!-- ─── STATISTIK LIVE OPERASIONAL ────────────────────────────── -->
        <section id="statistik" class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['employees'] ?? 0) }}</div>
                <div class="stat-title">Total Karyawan Aktif</div>
                <div class="stat-desc">Karyawan terlindungi & termonitor</div>
            </div>

            <div class="stat-card sky">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['principals'] ?? 0) }}</div>
                <div class="stat-title">Prinsiple & Mitra Bisnis</div>
                <div class="stat-desc">Brand terdaftar dalam ekosistem</div>
            </div>

            <div class="stat-card emerald">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['areas'] ?? 0) }}</div>
                <div class="stat-title">Area & Cabang Operasional</div>
                <div class="stat-desc">Cakupan pulau & kota di Indonesia</div>
            </div>

            <div class="stat-card amber">
                <div class="stat-icon-wrap">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="stat-number">{{ number_format($stats['locations'] ?? 0) }}</div>
                <div class="stat-title">Lokasi Kerja & Toko</div>
                <div class="stat-desc">Titik geofence radius tervalidasi</div>
            </div>
        </section>
    </header>

    <!-- ─── SUBDOMAIN ACCESS HUB SECTION (HANYA DITAMPILKAN PADA DOMAIN UTAMA) ── -->
    @if(!isset($isEntityServer) || !$isEntityServer)
        <section id="subdomain-hub" class="section-wrap" style="padding-top: 2rem;">
            <div class="section-header">
                <span class="section-tag">
                    <i class="fa-solid fa-network-wired"></i> Portal Navigator Ekosistem
                </span>
                <h2 class="section-title">Akses Subdomain & Portal Layanan Terdaftar</h2>
                <p class="section-subtitle">
                    Pilih server entitas perusahaan atau portal principal mitra untuk membuka antarmuka presensi, laporan display lapangan, rekap audit stok, dan master data.
                </p>
            </div>

            <div class="subdomain-hub-container">
                <!-- Filter Bar & Search Input -->
                <div class="hub-controls">
                    <div class="filter-tabs">
                        <button type="button" class="tab-btn" :class="{ 'active': activeTab === 'all' }" @click="activeTab = 'all'">
                            <i class="fa-solid fa-border-all"></i>
                            <span>Semua Layanan</span>
                            <span class="badge-count" x-text="items.length"></span>
                        </button>
                        <button type="button" class="tab-btn" :class="{ 'active': activeTab === 'company' }" @click="activeTab = 'company'">
                            <i class="fa-solid fa-building-user"></i>
                            <span>Server Entitas ESA</span>
                            <span class="badge-count" x-text="items.filter(i => i.category === 'company').length"></span>
                        </button>
                        <button type="button" class="tab-btn" :class="{ 'active': activeTab === 'principal' }" @click="activeTab = 'principal'">
                            <i class="fa-solid fa-store"></i>
                            <span>Portal Principal / Mitra</span>
                            <span class="badge-count" x-text="items.filter(i => i.category === 'principal').length"></span>
                        </button>
                        <button type="button" class="tab-btn" :class="{ 'active': activeTab === 'system' }" @click="activeTab = 'system'">
                            <i class="fa-solid fa-server"></i>
                            <span>Infrastruktur & API</span>
                            <span class="badge-count" x-text="items.filter(i => i.category === 'system').length"></span>
                        </button>
                    </div>

                    <div class="search-box-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="search-input" placeholder="Cari subdomain, mitra, atau kode..." x-model="searchQuery">
                    </div>
                </div>

                <!-- Subdomain Cards Grid -->
                <div class="subdomain-grid">
                    <template x-for="item in filteredItems" :key="item.subdomain">
                        <div class="subdomain-card" :style="`--card-accent: ${item.color};`">
                            <!-- Top Row: Category & Status -->
                            <div class="card-top-row">
                                <span class="category-pill" :class="item.category" x-text="item.categoryLabel"></span>
                                <span class="status-badge">
                                    <span class="status-dot"></span>
                                    <span x-text="item.status"></span>
                                </span>
                            </div>

                            <!-- Brand Profile: Avatar & Name -->
                            <div class="brand-profile-wrap">
                                <div class="brand-avatar" :style="`background: linear-gradient(135deg, ${item.color} 0%, ${item.colorSecondary} 100%);`">
                                    <template x-if="item.icon">
                                        <i :class="item.icon"></i>
                                    </template>
                                    <template x-if="!item.icon">
                                        <span x-text="item.initials"></span>
                                    </template>
                                </div>
                                <div class="brand-info">
                                    <h3 class="brand-title" :title="item.name" x-text="item.name"></h3>
                                    <div class="brand-subdomain-chip">
                                        <span x-text="item.host"></span>
                                        <button type="button" class="copy-sub-btn" @click="copyToClipboard(item.url)" title="Salin URL">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <p class="card-desc" x-text="item.desc"></p>

                            <!-- Feature Tags -->
                            <div class="card-tags">
                                <template x-for="feat in item.features" :key="feat">
                                    <span class="mini-feature-tag" x-text="feat"></span>
                                </template>
                            </div>

                            <!-- Action Buttons -->
                            <div class="card-action-group">
                                <a :href="item.url" class="btn-open-subdomain" target="_blank" rel="noopener noreferrer">
                                    <span>Buka Subdomain</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                                <template x-if="item.loginUrl">
                                    <a :href="item.loginUrl" class="btn-login-subdomain" title="Login Portal">
                                        <i class="fa-solid fa-right-to-bracket"></i>
                                        <span>Login</span>
                                    </a>
                                </template>
                                <template x-if="!item.loginUrl && item.adminUrl">
                                    <a :href="item.adminUrl" class="btn-login-subdomain" title="Admin Console">
                                        <i class="fa-solid fa-gauge"></i>
                                        <span>Admin</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State if no search match -->
                    <div class="empty-search-state" x-show="filteredItems.length === 0" x-cloak>
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        <h4 style="font-size: 1.15rem; color: var(--text-heading); font-weight: 700; margin-bottom: 0.35rem;">Subdomain Tidak Ditemukan</h4>
                        <p style="font-size: 0.88rem;">Tidak ada subdomain yang cocok dengan kata kunci "<span x-text="searchQuery" style="font-weight: 700;"></span>".</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ─── MODAL NOTIFIKASI IOS (UNDER DEVELOPMENT) ─────────────────── -->
    <div class="ios-modal-backdrop" x-show="showIosModal" x-transition.opacity x-cloak @click.self="showIosModal = false">
        <div class="ios-modal-card" x-show="showIosModal" x-transition.scale.95>
            <div class="ios-modal-icon">
                <i class="fa-brands fa-apple"></i>
            </div>
            <span class="ios-badge-pill">Under Development</span>
            <h3 class="ios-modal-title">Aplikasi iOS Sedang Dalam Pengembangan</h3>
            <p class="ios-modal-desc">
                Aplikasi <strong>ESA Attendance untuk perangkat Apple iOS (iPhone & iPad)</strong> saat ini sedang dalam tahap akhir pengembangan dan persiapan review Apple App Store.
            </p>
            <div class="ios-modal-tip">
                <i class="fa-solid fa-circle-info"></i>
                <span>Untuk saat ini, silakan gunakan smartphone Android atau portal web untuk aktivitas presensi biometrik dan pelaporan kerja.</span>
            </div>
            <div class="ios-modal-actions">
                <a href="{{ $apkDownloadUrl }}" class="btn-modal-android" target="_blank">
                    <i class="fa-brands fa-android" style="color: #10B981;"></i>
                    <span>Unduh APK Android Versi Terbaru (v1.0.114)</span>
                </a>
                <button type="button" class="btn-modal-close" @click="showIosModal = false">
                    Tutup Notifikasi
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Copy Notification -->
    <div class="toast-copied" x-show="showCopiedToast" x-cloak>
        <i class="fa-solid fa-circle-check" style="color: #10B981;"></i>
        <span x-text="copiedMessage"></span>
    </div>

    <!-- ─── FITUR SISTEM UNGGULAN ────────────────────────────────────── -->
    <main id="fitur" class="section-wrap">
        <div class="section-header">
            <span class="section-tag">
                <i class="fa-solid fa-sparkles"></i> Keunggulan Arsitektur Sistem
            </span>
            <h2 class="section-title">Teknologi Cerdas untuk Efisiensi Tim Lapangan</h2>
            <p class="section-subtitle">
                Dirancang khusus untuk mengelola puluhan ribu tenaga kerja lapangan, promotor, sales retail, dan staf operasional dalam satu platform multi-tenant tangguh.
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
                    <span class="mini-feature-tag">Radius Meter</span>
                    <span class="mini-feature-tag">Anti Mock GPS</span>
                    <span class="mini-feature-tag">Histori Rute</span>
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
                    Verifikasi wajah biometrik otomatis anti-spoofing (kedipan & gerakan) tanpa tombol shutter manual untuk menjamin keaslian kehadiran karyawan di lokasi kerja.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-feature-tag">Auto Capture</span>
                    <span class="mini-feature-tag">Master Enrollment</span>
                    <span class="mini-feature-tag">Anti Foto Tiruan</span>
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
                    <span class="mini-feature-tag">Auto Sum Calculation</span>
                    <span class="mini-feature-tag">Month-Year Picker</span>
                    <span class="mini-feature-tag">Multi Photo</span>
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
                    <span class="mini-feature-tag">Import Excel Roster</span>
                    <span class="mini-feature-tag">Pola Shift Otomatis</span>
                    <span class="mini-feature-tag">Monitoring Alpha</span>
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
                    Perencanaan jadwal kunjungan toko harian bagi SPG & MD. Alur terstruktur mulai dari Visit-In di lokasi, pengisian laporan display/stok, hingga Visit-Out.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-feature-tag">Target Toko</span>
                    <span class="mini-feature-tag">Strict Routing</span>
                    <span class="mini-feature-tag">Check-in Terjadwal</span>
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
                    <span class="mini-feature-tag">Auto Validasi Jadwal</span>
                    <span class="mini-feature-tag">Approval Admin</span>
                    <span class="mini-feature-tag">Penghilang Alpha</span>
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
                    Portal khusus berbasis subdomain mandiri untuk setiap prinsiple guna memantau tim, rekap kehadiran, dan ekspor laporan kerja.
                </p>
                <div class="feature-footer-tags">
                    <span class="mini-feature-tag">Custom Branding</span>
                    <span class="mini-feature-tag">Subdomain Tenant</span>
                    <span class="mini-feature-tag">Ekspor Excel / PDF</span>
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
                    <span class="mini-feature-tag">Google Maps Parser</span>
                    <span class="mini-feature-tag">Foto Toko</span>
                    <span class="mini-feature-tag">Approval Master Data</span>
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
                    <span class="mini-feature-tag">Timer Real-Time</span>
                    <span class="mini-feature-tag">Kuota Cuti</span>
                    <span class="mini-feature-tag">Notifikasi FCM</span>
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
                    <span class="mini-feature-tag">Sales Pipeline</span>
                    <span class="mini-feature-tag">PDF Payslip</span>
                    <span class="mini-feature-tag">Aman & Terenkripsi</span>
                </div>
            </div>
        </div>
    </main>

    <!-- ─── DOWNLOAD APPLICATION BOX SECTION (2 JENIS: ANDROID & IOS) ── -->
    <section id="download" class="section-wrap" style="padding-top: 1rem;">
        <div class="app-download-box">
            <div class="app-download-content">
                <span class="app-badge-version">
                    <i class="fa-brands fa-android"></i> Versi Rilis Terbaru v1.0.114
                </span>
                <h2 class="app-download-title">Unduh Aplikasi Mobile Presensi ESA Groups</h2>
                <p class="app-download-desc">
                    Tersedia untuk smartphone Android dan Apple iOS. Nikmati kemudahan check-in cepat dengan AI liveness camera, pelaporan dinamis, tracking rute kunjungan, dan sinkronisasi offline.
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

                <!-- 2 Jenis Tombol Download: Android & iOS -->
                <div class="app-download-action-row">
                    <!-- Tombol 1: Android -->
                    <a href="{{ $apkDownloadUrl }}" class="btn-banner-android" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-android"></i>
                        <div style="text-align: left; line-height: 1.2;">
                            <span style="font-size: 0.72rem; color: #64748B; display: block; font-weight: 600;">Download Langsung</span>
                            <span>Android APK (106 MB)</span>
                        </div>
                    </a>

                    <!-- Tombol 2: iOS (Under Development) -->
                    <button type="button" class="btn-banner-ios" @click="showIosNotice()">
                        <i class="fa-brands fa-apple"></i>
                        <div style="text-align: left; line-height: 1.2;">
                            <span style="font-size: 0.72rem; color: #CBD5E1; display: block; font-weight: 500;">Apple App Store</span>
                            <span>Apple iOS (Dev)</span>
                        </div>
                        <span class="badge-dev-tag" style="background: rgba(254, 243, 199, 0.2); color: #FDE68A; border-color: rgba(253, 230, 138, 0.4);">Review</span>
                    </button>
                </div>
            </div>

            <div class="app-phone-mockup">
                <div class="mockup-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="font-size: 0.75rem; color: #94A3B8; font-weight: 700;">ESA ATTENDANCE</span>
                        <span style="font-size: 0.72rem; color: #10B981; font-weight: 700;"><span class="status-dot" style="display:inline-block; margin-right:4px;"></span>LIVE</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.06); padding: 1.1rem; border-radius: 16px; margin-bottom: 0.85rem; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="font-size: 0.75rem; color: #94A3B8;">Status Platform Mobile</div>
                        <div style="font-size: 1.15rem; font-weight: 800; color: #38BDF8; margin-top: 3px;">Android & iOS Ready</div>
                        <div style="font-size: 0.72rem; color: #A5B4FC; margin-top: 4px;">Biometric AI Liveness v1.0.114</div>
                    </div>
                    <div style="font-size: 0.78rem; color: #CBD5E1; line-height: 1.45;">
                        Aman, akurat, anti-fake GPS, dan terlindungi enkripsi 256-bit SSL.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── FOOTER ───────────────────────────────────────────────────── -->
    <footer>
        <div class="footer-grid">
            <div class="footer-brand-col">
                <a href="/" class="footer-logo">
                    @if(isset($setting) && $setting->logo_path)
                        <img src="/app-logo" alt="{{ $setting->app_name ?? 'Logo' }}" style="height: 34px; max-width: 150px; object-fit: contain;" onerror="this.style.display='none'; document.getElementById('footer-brand-fallback').style.display='flex';">
                        <div id="footer-brand-fallback" style="display: none; align-items: center; gap: 0.75rem;">
                            <div class="brand-badge" style="width: 32px; height: 32px; font-size: 0.85rem;">{{ isset($currentEntity) ? $currentEntity['code'] : 'ESA' }}</div>
                            <span>{{ $setting->app_name ?? (isset($currentEntity) ? $currentEntity['name'] : 'ESA Solutions') }}</span>
                        </div>
                    @else
                        <div class="brand-badge" style="width: 32px; height: 32px; font-size: 0.85rem;">{{ isset($currentEntity) ? $currentEntity['code'] : 'ESA' }}</div>
                        <span>{{ $setting->app_name ?? (isset($currentEntity) ? $currentEntity['name'] : 'ESA Solutions') }}</span>
                    @endif
                </a>
                <p class="footer-desc">
                    {{ $setting->company_name ?? 'PT ESA Groups' }} - Solusi ekosistem presensi digital biometrik AI, roster shift adaptif, dan pelaporan dinamis multi-server terkemuka di Indonesia.
                </p>
                <div style="display: flex; gap: 0.65rem; color: var(--text-light); font-size: 0.82rem;">
                    <span><i class="fa-solid fa-lock" style="color:#10B981;"></i> 256-bit SSL</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-bolt" style="color:#F59E0B;"></i> Multi-Server Node</span>
                </div>
            </div>

            <!-- Server Entitas Links -->
            <div>
                <h4 class="footer-col-title">Server Entitas</h4>
                <ul class="footer-list">
                    <li><a href="https://amk.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> PT Arina Multi Karya</a></li>
                    <li><a href="https://akp.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> PT Alva Karya Perkasa</a></li>
                    <li><a href="https://atk.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> PT Anugrah Talenta Berkarya</a></li>
                    <li><a href="https://api.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> Smart Gateway API</a></li>
                </ul>
            </div>

            <!-- Principal Portals Links -->
            <div>
                <h4 class="footer-col-title">Portal Principal</h4>
                <ul class="footer-list">
                    <li><a href="https://dulux.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> Dulux (ICI Paints)</a></li>
                    <li><a href="https://fonterra.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> Fonterra Brands</a></li>
                    <li><a href="https://mamasuka.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> MamaSuka (Jico Agung)</a></li>
                    <li><a href="https://wings.{{ $baseDomain ?? 'esa-solutions.id' }}" target="_blank"><i class="fa-solid fa-angle-right" style="font-size:0.75rem;"></i> Wings Group / Lion Wings</a></li>
                </ul>
            </div>

            <!-- Download & Navigasi -->
            <div>
                <h4 class="footer-col-title">Aplikasi Mobile</h4>
                <ul class="footer-list">
                    <li><a href="{{ $apkDownloadUrl }}" target="_blank"><i class="fa-brands fa-android" style="color: #10B981;"></i> Download Android APK</a></li>
                    <li><a href="javascript:void(0)" @click="showIosNotice()"><i class="fa-brands fa-apple"></i> Apple iOS (Under Dev)</a></li>
                    <li><a href="/login"><i class="fa-solid fa-right-to-bracket"></i> Login Whitelabel Portal</a></li>
                    <li><a href="/admin"><i class="fa-solid fa-shield-halved"></i> Admin Master Console</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>&copy; {{ date('Y') }} {{ $setting->company_name ?? 'PT ESA Groups' }}. All rights reserved.</div>
            <div>Integrated Workforce, Attendance & Field Performance Management Ecosystem</div>
        </div>
    </footer>

    <!-- ─── ALPINE.JS CLIENT-SIDE DATA STORE & INTERACTIVITY ─────────── -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('landingApp', () => ({
                activeTab: 'all',
                searchQuery: '',
                showCopiedToast: false,
                copiedMessage: '',
                copyTimeout: null,
                showIosModal: false,

                showIosNotice() {
                    this.showIosModal = true;
                },

                items: [
                    @if(isset($serverNodes) && count($serverNodes) > 0)
                        @foreach($serverNodes as $srv)
                        {
                            subdomain: '{{ $srv['subdomain'] }}',
                            host: '{{ $srv['subdomain'] }}.{{ $baseDomain ?? 'esa-solutions.id' }}',
                            name: '{{ $srv['name'] }}',
                            shortName: '{{ $srv['short_name'] }}',
                            category: 'company',
                            categoryLabel: 'Server Entitas ESA',
                            status: '{{ $srv['status'] }}',
                            color: '{{ $srv['color'] }}',
                            colorSecondary: '{{ $srv['color_secondary'] }}',
                            icon: '{{ $srv['icon'] }}',
                            initials: '{{ $srv['code'] }}',
                            desc: '{{ addslashes($srv['desc']) }}',
                            url: '{{ $srv['url'] }}',
                            adminUrl: '{{ $srv['admin_url'] ?? '' }}',
                            loginUrl: '{{ $srv['login_url'] ?? '' }}',
                            features: {!! json_encode($srv['features']) !!}
                        },
                        @endforeach
                    @endif

                    @if(isset($registeredPrincipals) && $registeredPrincipals->isNotEmpty())
                        @foreach($registeredPrincipals as $p)
                        {
                            subdomain: '{{ $p->subdomain }}',
                            host: '{{ $p->subdomain }}.{{ $baseDomain ?? 'esa-solutions.id' }}',
                            name: '{{ $p->name }}',
                            shortName: '{{ strtoupper($p->subdomain) }}',
                            category: 'principal',
                            categoryLabel: 'Portal Principal / Klien',
                            status: 'Live Portal',
                            color: '{{ $p->theme_color ?: '#0F52BA' }}',
                            colorSecondary: '{{ $p->theme_color_secondary ?: '#2563EB' }}',
                            icon: 'fa-solid fa-store',
                            initials: '{{ strtoupper(substr($p->subdomain, 0, 2)) }}',
                            desc: '{{ addslashes($p->description ?? "Portal monitoring data kehadiran, formulir kunjungan, audit toko, dan performa tim promotor {$p->name}.") }}',
                            url: 'https://{{ $p->subdomain }}.{{ $baseDomain ?? 'esa-solutions.id' }}',
                            loginUrl: 'https://{{ $p->subdomain }}.{{ $baseDomain ?? 'esa-solutions.id' }}/login',
                            adminUrl: '',
                            features: ['Whitelabel Portal', 'Dynamic Reporting', 'Rekap Kehadiran', 'Ekspor Excel']
                        },
                        @endforeach
                    @endif

                    @if(isset($systemServices) && count($systemServices) > 0)
                        @foreach($systemServices as $sys)
                        {
                            subdomain: '{{ $sys['subdomain'] }}',
                            host: '{{ $sys['subdomain'] }}.{{ $baseDomain ?? 'esa-solutions.id' }}',
                            name: '{{ $sys['name'] }}',
                            shortName: '{{ $sys['short_name'] }}',
                            category: 'system',
                            categoryLabel: 'Infrastruktur Cloud',
                            status: '{{ $sys['status'] }}',
                            color: '{{ $sys['color'] }}',
                            colorSecondary: '{{ $sys['color_secondary'] }}',
                            icon: '{{ $sys['icon'] }}',
                            initials: '{{ strtoupper(substr($sys['code'], 0, 2)) }}',
                            desc: '{{ addslashes($sys['desc']) }}',
                            url: '{{ $sys['url'] }}',
                            loginUrl: '',
                            adminUrl: '',
                            features: {!! json_encode($sys['features']) !!}
                        },
                        @endforeach
                    @endif
                ],

                get filteredItems() {
                    let result = this.items;

                    if (this.activeTab !== 'all') {
                        result = result.filter(item => item.category === this.activeTab);
                    }

                    if (this.searchQuery && this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase().trim();
                        result = result.filter(item => {
                            return item.name.toLowerCase().includes(q) ||
                                   item.subdomain.toLowerCase().includes(q) ||
                                   item.host.toLowerCase().includes(q) ||
                                   item.desc.toLowerCase().includes(q);
                        });
                    }

                    return result;
                },

                copyToClipboard(text) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => {
                            this.triggerCopiedToast(`Tersalin: ${text}`);
                        }).catch(() => {
                            this.fallbackCopy(text);
                        });
                    } else {
                        this.fallbackCopy(text);
                    }
                },

                fallbackCopy(text) {
                    const temp = document.createElement('input');
                    temp.value = text;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    this.triggerCopiedToast(`Tersalin: ${text}`);
                },

                triggerCopiedToast(msg) {
                    this.copiedMessage = msg;
                    this.showCopiedToast = true;
                    if (this.copyTimeout) clearTimeout(this.copyTimeout);
                    this.copyTimeout = setTimeout(() => {
                        this.showCopiedToast = false;
                    }, 2500);
                }
            }));
        });
    </script>
</body>
</html>
