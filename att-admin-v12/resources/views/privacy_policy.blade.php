<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi (Privacy Policy) - PT Arina Multi Karya | ESA Groups Mobile</title>
    <meta name="description" content="Kebijakan Privasi resmi aplikasi ESA Groups Mobile di bawah naungan PT Arina Multi Karya sebagai induk usaha ESA Groups.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0F52BA;
            --primary-dark: #08036B;
            --primary-deep: #050242;
            --primary-light: #EEF4FF;
            --primary-border: #C7D9FB;
            --text-dark: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --bg-body: #F8FAFC;
            --card-bg: #FFFFFF;
            --border-color: #E2E8F0;
            --border-subtle: #F1F5F9;
            --emerald: #059669;
            --emerald-bg: #ECFDF5;
            --emerald-border: #A7F3D0;
            --amber: #D97706;
            --amber-bg: #FFFBEB;
            --amber-border: #FDE68A;
            --sky: #0284C7;
            --sky-bg: #F0F9FF;
            --sky-border: #BAE6FD;
            --purple: #7C3AED;
            --purple-bg: #F5F3FF;
            --purple-border: #DDD6FE;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            line-height: 1.75;
            -webkit-font-smoothing: antialiased;
            padding-bottom: 60px;
        }

        /* Top Bar */
        .top-navbar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.95);
        }

        .top-navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .brand-logo {
            height: 38px;
            width: auto;
            display: block;
        }

        .brand-text-block {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-body);
            border-color: var(--border-color);
        }

        .btn-outline:hover {
            background: var(--bg-body);
            color: var(--primary);
            border-color: var(--primary-border);
        }

        .btn-primary {
            background: var(--primary);
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-dark) 50%, var(--primary) 100%);
            color: white;
            padding: 56px 24px 72px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(2, 132, 199, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-inner {
            max-width: 860px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            padding: 6px 18px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            text-transform: uppercase;
        }

        .hero-title {
            font-size: 2.35rem;
            font-weight: 800;
            margin-bottom: 14px;
            letter-spacing: -0.8px;
            line-height: 1.25;
        }

        .hero-subtitle {
            font-size: 1.05rem;
            opacity: 0.92;
            max-width: 720px;
            margin: 0 auto 20px auto;
            font-weight: 400;
            line-height: 1.6;
        }

        .hero-company-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(15, 82, 186, 0.35);
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Main Container Layout */
        .layout-container {
            max-width: 1200px;
            margin: -40px auto 0 auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
            position: relative;
            z-index: 2;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: sticky;
            top: 76px;
            align-self: start;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .sidebar-card {
            background: #FFFFFF;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
        }

        .sidebar-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            color: var(--text-body);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: var(--bg-body);
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .nav-link:hover .nav-num {
            background: var(--primary);
            color: white;
        }

        /* Content Card */
        .main-content {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
        }

        /* Meta Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            background: var(--bg-body);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 36px;
        }

        .meta-box {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .meta-val {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.35;
        }

        .meta-val.highlight {
            color: var(--primary);
        }

        /* Sections */
        .policy-section {
            margin-bottom: 40px;
            scroll-margin-top: 90px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .section-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .icon-blue { background: var(--primary-light); color: var(--primary); }
        .icon-emerald { background: var(--emerald-bg); color: var(--emerald); }
        .icon-amber { background: var(--amber-bg); color: var(--amber); }
        .icon-purple { background: var(--purple-bg); color: var(--purple); }
        .icon-sky { background: var(--sky-bg); color: var(--sky); }

        .section-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.3px;
        }

        p {
            margin-bottom: 16px;
            font-size: 0.95rem;
            color: var(--text-body);
        }

        p:last-child {
            margin-bottom: 0;
        }

        /* Highlight & Info Cards */
        .callout-card {
            border-radius: 12px;
            padding: 20px 22px;
            margin: 18px 0;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .callout-icon {
            font-size: 1.5rem;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .callout-content {
            flex: 1;
        }

        .callout-title {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .callout-desc {
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .callout-emerald {
            background: var(--emerald-bg);
            border: 1px solid var(--emerald-border);
            color: #065F46;
        }
        .callout-emerald .callout-title { color: #047857; }

        .callout-sky {
            background: var(--sky-bg);
            border: 1px solid var(--sky-border);
            color: #075985;
        }
        .callout-sky .callout-title { color: #0284C7; }

        .callout-amber {
            background: var(--amber-bg);
            border: 1px solid var(--amber-border);
            color: #92400E;
        }
        .callout-amber .callout-title { color: #B45309; }

        .callout-purple {
            background: var(--purple-bg);
            border: 1px solid var(--purple-border);
            color: #5B21B6;
        }
        .callout-purple .callout-title { color: #6D28D9; }

        /* Custom Styled List */
        .feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 18px 0;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: var(--bg-body);
            padding: 14px 18px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-size: 0.92rem;
        }

        .feature-bullet {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            flex-shrink: 0;
            margin-top: 9px;
        }

        .feature-text strong {
            color: var(--text-dark);
            font-weight: 700;
        }

        /* Step Guide for Account Deletion */
        .step-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .step-card {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px;
            position: relative;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .step-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .step-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.55;
        }

        /* Official Corporate Contact Card */
        .corporate-card {
            background: linear-gradient(135deg, var(--primary-light) 0%, #FFFFFF 100%);
            border: 1.5px solid var(--primary-border);
            border-radius: 16px;
            padding: 28px;
            margin-top: 24px;
        }

        .corp-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(15, 82, 186, 0.15);
        }

        .corp-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .corp-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.2;
        }

        .corp-status {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--primary);
        }

        .corp-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin-bottom: 24px;
        }

        .corp-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.92rem;
        }

        .corp-row-icon {
            font-size: 1.15rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .corp-row-content strong {
            color: var(--text-dark);
            display: block;
            margin-bottom: 2px;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .corp-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-corp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-corp-primary {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }

        .btn-corp-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-corp-secondary {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary-border);
        }

        .btn-corp-secondary:hover {
            background: var(--primary-light);
        }

        /* Footer */
        .site-footer {
            max-width: 1200px;
            margin: 40px auto 0 auto;
            padding: 24px;
            text-align: center;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .site-footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .site-footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .site-footer-links a:hover {
            text-decoration: underline;
        }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            .layout-container {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .sidebar {
                display: none;
            }

            .main-content {
                padding: 24px;
            }

            .hero-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 600px) {
            .top-navbar-inner {
                padding: 10px 16px;
            }

            .hero-section {
                padding: 40px 16px 60px 16px;
            }

            .layout-container {
                padding: 0 16px;
            }

            .meta-grid {
                grid-template-columns: 1fr;
            }

            .corporate-card {
                padding: 20px;
            }

            .corp-buttons {
                flex-direction: column;
            }

            .btn-corp {
                justify-content: center;
                width: 100%;
            }
        }

        @media print {
            .top-navbar, .sidebar, .btn-action, .corp-buttons {
                display: none !important;
            }
            .layout-container {
                margin-top: 0;
                grid-template-columns: 1fr;
            }
            .main-content {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            body {
                background: white;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <div class="top-navbar-inner">
            <a href="https://esa-solutions.id" class="brand-container" title="ESA Solutions">
                @if(file_exists(public_path('images/Logo_ESA.png')))
                    <img src="{{ asset('images/Logo_ESA.png') }}" alt="Logo ESA Groups" class="brand-logo">
                @elseif(file_exists(public_path('Logo_ESA.png')))
                    <img src="{{ asset('Logo_ESA.png') }}" alt="Logo ESA Groups" class="brand-logo">
                @endif
                <div class="brand-text-block">
                    <span class="brand-title">PT Arina Multi Karya</span>
                    <span class="brand-subtitle">Induk Usaha ESA Groups</span>
                </div>
            </a>
            <div class="top-actions">
                <button onclick="window.print()" class="btn-action btn-outline" title="Cetak atau Simpan sebagai PDF">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    <span>Cetak PDF</span>
                </button>
                <a href="mailto:itsupport@arina.co.id" class="btn-action btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <span>Hubungi IT Support</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Header -->
    <section class="hero-section">
        <div class="hero-inner">
            <div class="hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <span>DOKUMEN RESMI KEBIJAKAN PRIVASI</span>
            </div>
            <h1 class="hero-title">Kebijakan Privasi (Privacy Policy)</h1>
            <p class="hero-subtitle">
                Standar perlindungan data pribadi, transparansi penggunaan izin perangkat, dan hak privasi pengguna aplikasi mobile presensi & aktivitas kerja.
            </p>
            <div class="hero-company-pill">
                <span>🏢 <strong>PT Arina Multi Karya</strong> — Induk Usaha Konsorsium ESA Groups Mobile</span>
            </div>
        </div>
    </section>

    <!-- Main Container -->
    <div class="layout-container">

        <!-- Sticky Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-title">DAFTAR ISI KEBIJAKAN</div>
                <ul class="nav-list">
                    <li><a href="#pendahuluan" class="nav-link"><span class="nav-num">1</span> Pendahuluan & Induk</a></li>
                    <li><a href="#data-dikumpulkan" class="nav-link"><span class="nav-num">2</span> Data yang Dikumpulkan</a></li>
                    <li><a href="#izin-perangkat" class="nav-link"><span class="nav-num">3</span> Penggunaan Izin Khusus</a></li>
                    <li><a href="#kerahasiaan-data" class="nav-link"><span class="nav-num">4</span> Larangan Bagi Pihak Ketiga</a></li>
                    <li><a href="#keamanan-enkripsi" class="nav-link"><span class="nav-num">5</span> Keamanan & Enkripsi</a></li>
                    <li><a href="#penghapusan-akun" class="nav-link"><span class="nav-num">6</span> Penghapusan Akun & Data</a></li>
                    <li><a href="#privasi-anak" class="nav-link"><span class="nav-num">7</span> Batasan Usia & Anak</a></li>
                    <li><a href="#kontak-resmi" class="nav-link"><span class="nav-num">8</span> Kontak Resmi Perusahaan</a></li>
                </ul>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">

            <!-- Meta Information Grid -->
            <div class="meta-grid">
                <div class="meta-box">
                    <span class="meta-label">Badan Hukum / Induk Usaha</span>
                    <span class="meta-val highlight">PT Arina Multi Karya</span>
                </div>
                <div class="meta-box">
                    <span class="meta-label">Nama Aplikasi</span>
                    <span class="meta-val">ESA Groups Mobile</span>
                </div>
                <div class="meta-box">
                    <span class="meta-label">ID Paket (Package ID)</span>
                    <span class="meta-val">com.attendance.att_mobile</span>
                </div>
                <div class="meta-box">
                    <span class="meta-label">Pembaruan Terakhir</span>
                    <span class="meta-val">15 September 2026</span>
                </div>
            </div>

            <!-- 1. Pendahuluan -->
            <section id="pendahuluan" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-blue">1</div>
                    <h2 class="section-title">Pendahuluan & Kedudukan Entitas</h2>
                </div>
                <p>
                    Selamat datang di aplikasi <strong>ESA Groups Mobile</strong>. Aplikasi ini dikembangkan dan dikelola di bawah naungan <strong>PT Arina Multi Karya</strong> selaku Induk Usaha dari ekosistem ESA Groups, bersama dengan seluruh entitas anak perusahaan dan afiliasi operasionalnya, meliputi <strong>PT Alva Karya Perkasa</strong>, <strong>PT Anugrah Talenta Berkarya</strong>, serta <strong>ESA Solutions</strong> (selanjutnya disebut sebagai "Perusahaan", "Kami", atau "ESA Groups").
                </p>
                <p>
                    Kebijakan Privasi ini merupakan wujud komitmen mutlak Kami untuk menghormati, mengamankan, dan melindungi privasi setiap karyawan, tenaga kerja lapangan, surveyor, sales promotor, dan pengguna terdaftar lainnya. Dokumen ini merincikan secara transparan tata cara pengumpulan, pemrosesan, penyimpanan, dan perlindungan data pribadi Anda selama menggunakan aplikasi.
                </p>
                <div class="callout-card callout-sky">
                    <div class="callout-icon">ℹ️</div>
                    <div class="callout-content">
                        <div class="callout-title">Pernyataan Lingkup Penggunaan</div>
                        <div class="callout-desc">
                            Aplikasi ESA Groups Mobile merupakan aplikasi internal yang ditujukan khusus bagi karyawan terdaftar di lingkungan PT Arina Multi Karya dan entitas grup untuk pencatatan kehadiran kerja (presensi), pelaporan dinas harian, dan monitoring rute kunjungan outlet.
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Data yang Dikumpulkan -->
            <section id="data-dikumpulkan" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-purple">2</div>
                    <h2 class="section-title">Informasi & Data yang Dikumpulkan</h2>
                </div>
                <p>
                    Untuk menunjang fungsionalitas presensi, validasi radius tempat kerja, dan pelaporan kunjungan dinas, sistem mengumpulkan kategori informasi berikut:
                </p>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Identitas Profil Karyawan:</strong> Nama lengkap, Nomor Induk Karyawan (NIK), alamat email perusahaan, nomor telepon, divisi penugasan, jabatan, dan kantor cabang kerja.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Data Presensi & Jam Kerja:</strong> Waktu catatan absensi masuk (Clock-In), waktu catatan absensi pulang (Clock-Out), durasi shift harian, riwayat lembur, dan status jadwal kerja.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Data Lokasi Geografis Presisi (GPS):</strong> Titik koordinat latitude dan longitude saat melakukan absensi kerja (untuk validasi radius kantor/outlet) dan riwayat rute perjalanan saat mode kunjungan dinas aktif (Live Tracking).
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Foto Verifikasi Biometrik Wajah:</strong> Foto selfie yang diambil secara langsung melalui kamera aplikasi saat absensi masuk/pulang untuk verifikasi biometrik wajah (Face Liveness Detection).
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Foto Dokumentasi Kegiatan Lapangan:</strong> Foto display produk, nota, atau kondisi outlet fisik yang diambil dan diunggah langsung oleh pengguna saat membuat laporan kunjungan.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Integritas Perangkat Keras:</strong> Model ponsel, merek pabrikan, versi OS Android, Device ID, dan status integritas keamanan (pendeteksian manipulasi Mock Location / Fake GPS dan status Root).
                        </div>
                    </li>
                </ul>
            </section>

            <!-- 3. Penggunaan Izin Khusus -->
            <section id="izin-perangkat" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-emerald">3</div>
                    <h2 class="section-title">Deklarasi Penggunaan Izin Khusus (Prominent Disclosure)</h2>
                </div>
                <p>
                    Sesuai dengan Kebijakan Pengembang Google Play Store, Kami menyatakan secara terbuka tujuan penggunaan izin sistem yang diminta oleh aplikasi:
                </p>

                <!-- Lokasi -->
                <div class="callout-card callout-emerald">
                    <div class="callout-icon">📍</div>
                    <div class="callout-content">
                        <div class="callout-title">Izin Akses Lokasi Presisi (ACCESS_FINE_LOCATION & ACCESS_COARSE_LOCATION)</div>
                        <div class="callout-desc">
                            Digunakan untuk memverifikasi bahwa karyawan benar-benar berada di dalam radius area kerja yang sah (Geofencing) saat menekan tombol absensi masuk/pulang. Data lokasi hanya dikonsumsi pada saat aksi absensi dilakukan dan tidak dipantau secara diam-diam tanpa persetujuan pengguna.
                        </div>
                    </div>
                </div>

                <!-- Foreground Service -->
                <div class="callout-card callout-sky">
                    <div class="callout-icon">🏃</div>
                    <div class="callout-content">
                        <div class="callout-title">Layanan Latar Depan (FOREGROUND_SERVICE_LOCATION)</div>
                        <div class="callout-desc">
                            Bagi karyawan dengan tugas lapangan (surveyor / sales), fitur Live Tracking rute kunjungan dijalankan menggunakan Layanan Latar Depan (Foreground Service). Selama pelacakan aktif, Android menampilkan notifikasi persisten di status bar: <em>"Aplikasi sedang melacak perjalanan dinas"</em>. Pelacakan dapat dihentikan kapan saja oleh pengguna dengan menyelesaikan kunjungan (Stop Visit).
                        </div>
                    </div>
                </div>

                <!-- Kamera -->
                <div class="callout-card callout-purple">
                    <div class="callout-icon">📷</div>
                    <div class="callout-content">
                        <div class="callout-title">Izin Akses Kamera (CAMERA)</div>
                        <div class="callout-desc">
                            Digunakan secara eksklusif untuk pengambilan foto swafoto (selfie) saat presensi guna memvalidasi identitas pengguna, serta mengambil foto bukti kegiatan dinas di lapangan. Aplikasi <strong>TIDAK PERNAH</strong> mengakses galeri foto pribadi pengguna di luar dokumen yang dipilih sendiri oleh pengguna.
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. Kerahasiaan Data -->
            <section id="kerahasiaan-data" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-amber">4</div>
                    <h2 class="section-title">Kerahasiaan Data & Larangan Pihak Ketiga</h2>
                </div>
                <p>
                    <strong>PT Arina Multi Karya dan konsorsium ESA Groups TIDAK PERNAH menjual, menyewakan, memperdagangkan, atau membagikan data pribadi Anda kepada pihak ketiga</strong> untuk keperluan periklanan komersial, agensi pemasaran, maupun pialang data (data brokers).
                </p>
                <p>
                    Data presensi dan rute perjalanan Anda hanya dapat diakses secara terbatas oleh departemen internal yang berwenang:
                </p>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Manajemen Personalia (HRD):</strong> Untuk penghitungan rekapitulasi kehadiran, payroll gaji, dan evaluasi disiplin kerja.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Atasan Langsung (Supervisor / Branch Manager):</strong> Untuk verifikasi keabsahan rute kunjungan toko dan laporan dinas harian.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Administrator Sistem IT:</strong> Untuk pemeliharaan teknis, audit keamanan data, dan pencegahan kecurangan sistem.
                        </div>
                    </li>
                </ul>
            </section>

            <!-- 5. Keamanan & Enkripsi -->
            <section id="keamanan-enkripsi" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-blue">5</div>
                    <h2 class="section-title">Standar Keamanan Siber & Enkripsi</h2>
                </div>
                <p>
                    Kami menerapkan standar keamanan siber enterprise tingkat tinggi untuk melindungi data Anda dari akses tanpa hak:
                </p>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Komunikasi Terenkripsi Penuh (SSL/TLS):</strong> Seluruh pertukaran data antara aplikasi mobile dan server menggunakan protokol HTTPS (TLS 1.3). Koneksi teks biasa tanpa enkripsi diblokir secara total (<code>usesCleartextTraffic=false</code>).
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Obfuscation & Shrinking (R8/Proguard):</strong> Kode sumber aplikasi mobile diacak dan dilindungi untuk mencegah upaya dekompilasi, reverse engineering, atau injeksi malware oleh pihak luar.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Security Headers Middleware:</strong> Server web diperkuat dengan header HTTP keamanan modern (HSTS, <code>X-Frame-Options: SAMEORIGIN</code>, <code>X-Content-Type-Options: nosniff</code>) guna menangkal serangan web seperti Clickjacking dan MIME Sniffing.
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-bullet"></span>
                        <div class="feature-text">
                            <strong>Penyimpanan Kredensial Terenkripsi:</strong> Token otentikasi disimpan di secure hardware storage perangkat dan kata sandi di-hash menggunakan algoritma Bcrypt/Argon2.
                        </div>
                    </li>
                </ul>
            </section>

            <!-- 6. Penghapusan Akun & Retensi Data -->
            <section id="penghapusan-akun" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-emerald">6</div>
                    <h2 class="section-title">Hak Pengguna, Retensi Data & Penghapusan Akun</h2>
                </div>
                <p>
                    Sesuai dengan ketentuan Kebijakan Privasi Google Play mengenai <em>Account Deletion</em> dan undang-undang perlindungan data pribadi, setiap pengguna memiliki hak penuh terhadap datanya:
                </p>

                <div class="step-container">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-title">Pengajuan Permohonan</div>
                        <div class="step-desc">
                            Karyawan atau mantan karyawan dapat mengajukan permohonan penghapusan akun melalui email ke <strong>itsupport@arina.co.id</strong> atau melalui HRD cabang.
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-title">Verifikasi Identitas</div>
                        <div class="step-desc">
                            Tim IT Support akan memverifikasi NIK, Nama Lengkap, dan status kepegawaian untuk memastikan keabsahan kepemilikan akun.
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-title">Penghapusan / Deaktivasi</div>
                        <div class="step-desc">
                            Data akun, identitas biometrik, dan sesi perangkat akan dihapus atau dinonaktifkan secara permanen dalam kurun waktu 14 hari kerja.
                        </div>
                    </div>
                </div>

                <p style="font-size: 0.88rem; color: var(--text-muted);">
                    <em>Catatan Retensi:</em> Dokumen log kehadiran historis yang berkaitan dengan kepatuhan hukum ketenagakerjaan dan audit perpajakan/payroll akan disimpan sesuai ketentuan retensi perundang-undangan Republik Indonesia sebelum dianonimkan.
                </p>
            </section>

            <!-- 7. Privasi Anak -->
            <section id="privasi-anak" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-amber">7</div>
                    <h2 class="section-title">Kebijakan Privasi Usia & Anak-Anak</h2>
                </div>
                <p>
                    Aplikasi ESA Groups Mobile dirancang secara khusus untuk keperluan operasional ketenagakerjaan profesional. Target pengguna adalah karyawan berusia <strong>18 tahun ke atas</strong>. Kami tidak dengan sengaja mengumpulkan atau memproses informasi pribadi apa pun dari anak-anak di bawah usia 18 tahun.
                </p>
            </section>

            <!-- 8. Kontak Resmi -->
            <section id="kontak-resmi" class="policy-section">
                <div class="section-header">
                    <div class="section-icon-badge icon-sky">8</div>
                    <h2 class="section-title">Informasi Entitas Resmi & Kontak Dukungan</h2>
                </div>
                <p>
                    Apabila Anda memiliki pertanyaan, saran, atau permohonan terkait privasi dan perlindungan data pribadi Anda, silakan menghubungi kantor resmi Kami:
                </p>

                <!-- Corporate Card -->
                <div class="corporate-card">
                    <div class="corp-header">
                        <div class="corp-icon-box">🏢</div>
                        <div>
                            <div class="corp-name">PT Arina Multi Karya</div>
                            <div class="corp-status">Induk Perusahaan & Pengelola Ekosistem ESA Groups</div>
                        </div>
                    </div>
                    <div class="corp-details">
                        <div class="corp-row">
                            <div class="corp-row-icon">📍</div>
                            <div class="corp-row-content">
                                <strong>Alamat Kantor Pusat:</strong>
                                Jl. Rajawali No. 18-20 Surabaya, Jawa Timur, Indonesia
                            </div>
                        </div>
                        <div class="corp-row">
                            <div class="corp-row-icon">📧</div>
                            <div class="corp-row-content">
                                <strong>Email Dukungan IT & Privasi Data:</strong>
                                <a href="mailto:itsupport@arina.co.id" style="color: var(--primary); text-decoration: none; font-weight: 700;">itsupport@arina.co.id</a>
                            </div>
                        </div>
                        <div class="corp-row">
                            <div class="corp-row-icon">🌐</div>
                            <div class="corp-row-content">
                                <strong>Situs Web Resmi:</strong>
                                <a href="https://esa-solutions.id" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 700;">https://esa-solutions.id</a>
                            </div>
                        </div>
                        <div class="corp-row">
                            <div class="corp-row-icon">🤝</div>
                            <div class="corp-row-content">
                                <strong>Afiliasi Konsorsium ESA Groups:</strong>
                                PT Arina Multi Karya (Induk) | PT Alva Karya Perkasa | PT Anugrah Talenta Berkarya
                            </div>
                        </div>
                    </div>
                    <div class="corp-buttons">
                        <a href="mailto:itsupport@arina.co.id?subject=Pertanyaan%20Kebijakan%20Privasi%20ESA%20Mobile" class="btn-corp btn-corp-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <span>Kirim Email Dukungan</span>
                        </a>
                        <a href="https://maps.google.com/?q=Jl.+Rajawali+No.+18-20+Surabaya+Jawa+Timur" target="_blank" class="btn-corp btn-corp-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Petunjuk Arah Kantor</span>
                        </a>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="site-footer-links">
            <a href="https://esa-solutions.id">Beranda ESA Solutions</a>
            <span>•</span>
            <a href="mailto:itsupport@arina.co.id">Bantuan IT Support</a>
            <span>•</span>
            <a href="#pendahuluan">Kembali ke Atas</a>
        </div>
        <p>&copy; 2026 <strong>PT Arina Multi Karya</strong> (Induk ESA Groups). Seluruh Hak Cipta Dilindungi Undang-Undang.</p>
        <p style="font-size: 0.78rem; margin-top: 4px;">
            Dokumen Kebijakan Privasi ini mematuhi standar Kebijakan Data Pengguna Google Play Developer Policy.
        </p>
    </footer>

</body>
</html>
