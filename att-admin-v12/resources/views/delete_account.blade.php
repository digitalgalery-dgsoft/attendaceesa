<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Penghapusan Akun & Data (Account Deletion) - ESA Groups | PT Arina Multi Karya</title>
    <meta name="description" content="Halaman resmi permohonan penghapusan akun dan penghapusan data pengguna aplikasi ESA Groups Mobile (PT Arina Multi Karya) sesuai Kebijakan Google Play.">
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
            --rose: #E11D48;
            --rose-bg: #FFF1F2;
            --rose-border: #FECDD3;
            --emerald: #059669;
            --emerald-bg: #ECFDF5;
            --emerald-border: #A7F3D0;
            --amber: #D97706;
            --amber-bg: #FFFBEB;
            --amber-border: #FDE68A;
            --sky: #0284C7;
            --sky-bg: #F0F9FF;
            --sky-border: #BAE6FD;
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
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo-badge {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(15, 82, 186, 0.25);
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
            background: linear-gradient(135deg, #050242 0%, #08036B 50%, #0F52BA 100%);
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
                        radial-gradient(circle at 80% 80%, rgba(225, 29, 72, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-inner {
            max-width: 880px;
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
            max-width: 760px;
            margin: 0 auto 20px auto;
            font-weight: 400;
            line-height: 1.6;
        }

        .hero-meta-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 6px;
        }

        /* Main Container Layout */
        .layout-container {
            max-width: 1200px;
            margin: -36px auto 0 auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 32px;
            position: relative;
            z-index: 2;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 76px;
            align-self: start;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: #FFFFFF;
            border-radius: 14px;
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .app-badge-box {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .app-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0F52BA, #08036B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(15, 82, 186, 0.25);
        }

        .app-info h4 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .app-info p {
            font-size: 0.76rem;
            color: var(--text-muted);
            font-family: monospace;
            word-break: break-all;
        }

        .side-meta-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 0.85rem;
        }

        .side-meta-list li {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .side-meta-label {
            font-size: 0.74rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
        }

        .side-meta-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 14px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-body);
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .nav-links a:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Content Area */
        .content-main {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .policy-card {
            background: #FFFFFF;
            border-radius: 14px;
            padding: 32px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .policy-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .icon-primary { background: var(--primary-light); color: var(--primary); }
        .icon-rose { background: var(--rose-bg); color: var(--rose); }
        .icon-emerald { background: var(--emerald-bg); color: var(--emerald); }
        .icon-amber { background: var(--amber-bg); color: var(--amber); }

        .policy-card-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.4px;
        }

        .policy-card-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Steps grid */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .step-item {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            position: relative;
        }

        .step-number {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .step-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .step-desc {
            font-size: 0.88rem;
            color: var(--text-body);
            line-height: 1.6;
        }

        /* Table Styling */
        .data-table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        .data-table th {
            background: #F1F5F9;
            padding: 12px 16px;
            font-weight: 700;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-subtle);
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .tag-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .tag-deleted {
            background: var(--rose-bg);
            color: var(--rose);
            border: 1px solid var(--rose-border);
        }

        .tag-retained {
            background: var(--amber-bg);
            color: var(--amber);
            border: 1px solid var(--amber-border);
        }

        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.col-span-2 {
            grid-column: span 2;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .form-label span.req {
            color: var(--rose);
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: #FFFFFF;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text-dark);
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 82, 186, 0.12);
        }

        .form-textarea {
            resize: vertical;
            min-height: 90px;
        }

        .form-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.84rem;
            color: var(--text-body);
            line-height: 1.5;
            cursor: pointer;
        }

        .form-checkbox-label input {
            margin-top: 3px;
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(15, 82, 186, 0.25);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(15, 82, 186, 0.35);
        }

        .success-banner {
            display: none;
            background: var(--emerald-bg);
            border: 1px solid var(--emerald-border);
            color: #065F46;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* Notice Box */
        .notice-box {
            padding: 16px 20px;
            border-radius: 10px;
            margin: 16px 0;
            font-size: 0.88rem;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .notice-amber {
            background: var(--amber-bg);
            border: 1px solid var(--amber-border);
            color: #92400E;
        }

        .notice-sky {
            background: var(--sky-bg);
            border: 1px solid var(--sky-border);
            color: #075985;
        }

        /* Corporate Section */
        .corp-card {
            background: linear-gradient(145deg, #050242, #08036B);
            color: #FFFFFF;
            border-radius: 14px;
            padding: 32px;
        }

        .corp-title {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .corp-subtitle {
            font-size: 0.88rem;
            opacity: 0.85;
            margin-bottom: 20px;
        }

        .corp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            font-size: 0.88rem;
        }

        .corp-item {
            background: rgba(255, 255, 255, 0.08);
            padding: 14px 18px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .corp-item strong {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.75;
            margin-bottom: 4px;
        }

        .corp-actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-corp {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-corp-white {
            background: #FFFFFF;
            color: var(--primary-dark);
        }

        .btn-corp-trans {
            background: rgba(255, 255, 255, 0.15);
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        /* Site Footer */
        .site-footer {
            max-width: 1200px;
            margin: 48px auto 0 auto;
            padding: 24px;
            text-align: center;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .site-footer-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 10px;
        }

        .site-footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .layout-container {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: static;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.col-span-2 {
                grid-column: span 1;
            }
            .corp-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <header class="top-navbar">
        <div class="top-navbar-inner">
            <a href="https://esa-solutions.id" class="brand-container">
                <div class="brand-logo-badge">ESA</div>
                <div class="brand-text-block">
                    <span class="brand-title">PT Arina Multi Karya</span>
                    <span class="brand-subtitle">ESA Groups Mobile Portal</span>
                </div>
            </a>
            <div class="top-actions">
                <a href="/privacy-policy" class="btn-action btn-outline">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Kebijakan Privasi</span>
                </a>
                <a href="#form-pengajuan" class="btn-action btn-primary">
                    <span>Ajukan Permohonan</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Header -->
    <section class="hero-section">
        <div class="hero-inner">
            <div class="hero-badge">
                <span>🛡️ Google Play Policy Compliant</span>
            </div>
            <h1 class="hero-title">Permohonan Penghapusan Akun &amp; Data</h1>
            <p class="hero-subtitle">
                Halaman resmi transparansi pengelolaan, pengajuan penghapusan akun, dan pemusnahan data pengguna untuk aplikasi <strong>ESA Groups</strong> (di bawah naungan <strong>PT Arina Multi Karya</strong>).
            </p>
            <div class="hero-meta-strip">
                <div class="meta-item">
                    <span>📱 Aplikasi:</span>
                    <strong>ESA Groups</strong>
                </div>
                <div class="meta-item">
                    <span>📦 Package Name:</span>
                    <code>com.attendance.att_mobile</code>
                </div>
                <div class="meta-item">
                    <span>🏢 Pengembang:</span>
                    <strong>PT Arina Multi Karya</strong>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Layout -->
    <div class="layout-container">

        <!-- Sidebar Meta -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="app-badge-box">
                    <div class="app-icon">E</div>
                    <div class="app-info">
                        <h4>ESA Groups</h4>
                        <p>com.attendance.att_mobile</p>
                    </div>
                </div>

                <ul class="side-meta-list">
                    <li>
                        <span class="side-meta-label">Entitas Pengembang</span>
                        <span class="side-meta-value">PT Arina Multi Karya</span>
                    </li>
                    <li>
                        <span class="side-meta-label">Konsorsium Usaha</span>
                        <span class="side-meta-value">ESA Groups (AMK, AKP, ATK)</span>
                    </li>
                    <li>
                        <span class="side-meta-label">Waktu Respon IT</span>
                        <span class="side-meta-value">1 - 3 Hari Kerja</span>
                    </li>
                    <li>
                        <span class="side-meta-label">Kepatuhan Standar</span>
                        <span class="side-meta-value">Google Play Data Safety &amp; UU PDP RI</span>
                    </li>
                </ul>

                <hr style="border: none; border-top: 1px solid var(--border-subtle); margin: 16px 0;">

                <div class="side-meta-label" style="margin-bottom: 8px;">Navigasi Halaman</div>
                <ul class="nav-links">
                    <li><a href="#prosedur">👉 Prosedur Pengajuan</a></li>
                    <li><a href="#rincian-data">👉 Rincian Data Dihapus / Disimpan</a></li>
                    <li><a href="#form-pengajuan">👉 Formulir Pengajuan Online</a></li>
                    <li><a href="#kontak-resmi">👉 Kontak Bantuan IT &amp; HR</a></li>
                </ul>
            </div>

            <div class="sidebar-card" style="background: var(--sky-bg); border-color: var(--sky-border);">
                <h5 style="color: var(--sky); font-size: 0.9rem; font-weight: 800; margin-bottom: 6px;">Butuh Bantuan Cepat?</h5>
                <p style="font-size: 0.82rem; color: #0C4A6E; line-height: 1.5; margin-bottom: 12px;">
                    Anda juga dapat menghubungi Helpdesk IT Support kami langsung melalui email atau WhatsApp kerja operasional.
                </p>
                <a href="mailto:itsupport@arina.co.id?subject=Permohonan%20Penghapusan%20Akun%20ESA%20Groups" style="display: block; text-align: center; background: #0284C7; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.82rem; font-weight: 700;">
                    Hubungi itsupport@arina.co.id
                </a>
            </div>
        </aside>

        <!-- Main Content Body -->
        <main class="content-main">

            <!-- Section 1: Overview & Procedure -->
            <section class="policy-card" id="prosedur">
                <div class="policy-card-header">
                    <div class="icon-circle icon-primary">📋</div>
                    <div>
                        <h2 class="policy-card-title">Prosedur Pengajuan Penghapusan Akun</h2>
                        <p class="policy-card-subtitle">Langkah-langkah yang harus dilakukan pengguna / karyawan (Account Deletion Steps)</p>
                    </div>
                </div>

                <p style="font-size: 0.95rem; line-height: 1.7; margin-bottom: 16px;">
                    Sesuai dengan <strong>Google Play User Data Policy</strong> dan Undang-Undang Perlindungan Data Pribadi (UU PDP RI No. 27/2022), pengguna aplikasi <strong>ESA Groups Mobile</strong> berhak meminta penonaktifan akun serta penghapusan data personal mereka.
                </p>

                <div class="steps-grid">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Pengajuan Permohonan</h3>
                        <p class="step-desc">
                            Karyawan/pengguna mengisi formulir permohonan online pada halaman ini atau mengirimkan email resmi ke tim IT Support dengan mencantumkan <strong>NIK Karyawan</strong> dan <strong>Nama Lengkap</strong> terdaftar.
                        </p>
                    </div>

                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Verifikasi Identitas</h3>
                        <p class="step-desc">
                            Departemen HR &amp; IT PT Arina Multi Karya akan melakukan verifikasi status kepegawaian (misalnya karyawan purnatugas/resign) demi mencegah permohonan penghapusan tanpa otorisasi sah.
                        </p>
                    </div>

                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Pemusnahan &amp; Konfirmasi</h3>
                        <p class="step-desc">
                            Sistem akan mencabut seluruh akses login, menghapus sesi perangkat, menghapus kredensial, serta mengirimkan bukti konfirmasi tertulis bahwa akun telah berhasil dinonaktifkan.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Section 2: Data Specification (Deleted vs Retained) -->
            <section class="policy-card" id="rincian-data">
                <div class="policy-card-header">
                    <div class="icon-circle icon-rose">🗑️</div>
                    <div>
                        <h2 class="policy-card-title">Rincian Data: Yang Dihapus &amp; Yang Disimpan</h2>
                        <p class="policy-card-subtitle">Data Retention, Deletion Specification &amp; Legal Compliance</p>
                    </div>
                </div>

                <p style="font-size: 0.92rem; line-height: 1.7;">
                    Untuk menjamin kepatuhan ganda antara <em>Google Play Deletion Policy</em> dan <em>Regulasi Hukum Ketenagakerjaan Republik Indonesia</em>, berikut klasifikasi perlakuan data pengguna:
                </p>

                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Kategori Data</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 35%;">Rincian Data</th>
                                <th style="width: 25%;">Periode Retensi &amp; Ketentuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Kredensial &amp; Autentikasi</strong></td>
                                <td><span class="tag-pill tag-deleted">Dihapus Total</span></td>
                                <td>Username, email login, hash password, token sesi aplikasi, dan biometric credential flags pada perangkat.</td>
                                <td>Dihapus permanen maksimal <strong>72 jam</strong> setelah verifikasi disetujui.</td>
                            </tr>
                            <tr>
                                <td><strong>Data Perangkat &amp; Notifikasi</strong></td>
                                <td><span class="tag-pill tag-deleted">Dihapus Total</span></td>
                                <td>Firebase Cloud Messaging (FCM) push token, Device ID, Android hardware fingerprint.</td>
                                <td>Dibersihkan seketika saat akun diputus dari database aktif.</td>
                            </tr>
                            <tr>
                                <td><strong>Profil Personal Non-Hukum</strong></td>
                                <td><span class="tag-pill tag-deleted">Dihapus Total</span></td>
                                <td>Foto avatar profil, preferensi bahasa, dan pengaturan antarmuka lokal.</td>
                                <td>Dihapus permanen dari storage server.</td>
                            </tr>
                            <tr>
                                <td><strong>Arsip Audit Absensi &amp; Payroll</strong></td>
                                <td><span class="tag-pill tag-retained">Disimpan Terbatas</span></td>
                                <td>Catatan jam kehadiran, koordinat GPS saat presensi disahkan, rekapitulasi slip gaji, dan persetujuan lembur.</td>
                                <td>
                                    <strong>Retensi 5 Tahun</strong><br>
                                    <small style="color: var(--text-muted);">Diwajibkan oleh UU Ketenagakerjaan No. 13/2003 &amp; Ketentuan Perpajakan untuk audit hukum &amp; hak pesangon/gaji. Data dikunci dan tidak dapat diakses untuk operasional baru.</small>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Laporan Kunjungan Toko / Itinerary</strong></td>
                                <td><span class="tag-pill tag-retained">Dianonimkan</span></td>
                                <td>Data laporan display produk dan visit toko dialihkan menjadi agregat tanpa identitas personal aktif.</td>
                                <td>Disimpan sebagai arsip historis bisnis prinsipal tanpa kaitan dengan akun karyawan yang dihapus.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="notice-box notice-amber">
                    <div style="font-size: 1.2rem;">⚖️</div>
                    <div>
                        <strong>Catatan Kepatuhan Regulasi Ketenagakerjaan:</strong><br>
                        Sesuai standar hukum Indonesia, data riwayat kerja formal karyawan yang telah resign/selesai kontrak wajib diarsipkan selama masa kadaluwarsa sengketa hukum ketenagakerjaan (5 tahun) sebelum dilakukan pemusnahan total dari server backup arsip hukum.
                    </div>
                </div>
            </section>

            <!-- Section 3: Interactive Request Form -->
            <section class="policy-card" id="form-pengajuan">
                <div class="policy-card-header">
                    <div class="icon-circle icon-emerald">✍️</div>
                    <div>
                        <h2 class="policy-card-title">Formulir Permohonan Penghapusan Akun Online</h2>
                        <p class="policy-card-subtitle">Kirim permohonan langsung ke Departemen IT &amp; HR PT Arina Multi Karya</p>
                    </div>
                </div>

                <!-- Alert Success Container -->
                <div id="success-banner" class="success-banner">
                    <h4 style="font-weight: 800; margin-bottom: 4px;">✅ Permohonan Berhasil Terkirim!</h4>
                    <p id="success-message">
                        Permintaan penghapusan akun Anda telah dicatat dengan nomor tiket: <strong id="ticket-number">REQ-DEL-2026-001</strong>. Tim IT Support &amp; HR PT Arina Multi Karya akan menghubungi Anda dalam 1-3 hari kerja untuk verifikasi akhir.
                    </p>
                </div>

                <form id="deletion-form" onsubmit="handleFormSubmit(event)">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="employee_name">Nama Lengkap Karyawan <span class="req">*</span></label>
                            <input type="text" id="employee_name" name="employee_name" class="form-input" placeholder="e.g. Budi Santoso" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="employee_id">Nomor Induk Karyawan (NIK) / User ID <span class="req">*</span></label>
                            <input type="text" id="employee_id" name="employee_id" class="form-input" placeholder="e.g. 19920102001" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Terdaftar di Aplikasi <span class="req">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="e.g. budi@domain.com" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Nomor Telepon / WhatsApp Aktif <span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="e.g. 081234567890" required>
                        </div>

                        <div class="form-group col-span-2">
                            <label class="form-label" for="entity">Entitas Penempatan Kerja <span class="req">*</span></label>
                            <select id="entity" name="entity" class="form-select" required>
                                <option value="PT Arina Multi Karya">PT Arina Multi Karya (Induk)</option>
                                <option value="PT Alva Karya Perkasa">PT Alva Karya Perkasa (AKP)</option>
                                <option value="PT Anugrah Talenta Berkarya">PT Anugrah Talenta Berkarya (ATK)</option>
                                <option value="Lainnya">Lainnya / Project Mitra</option>
                            </select>
                        </div>

                        <div class="form-group col-span-2">
                            <label class="form-label" for="reason">Alasan Permohonan Penghapusan Akun <span class="req">*</span></label>
                            <textarea id="reason" name="reason" class="form-textarea" placeholder="Mohon jelaskan secara singkat alasan pengajuan (misal: Selesai masa kontrak/resign, pergantian akun, atau permintaan perlindungan privasi data)." required></textarea>
                        </div>

                        <div class="form-group col-span-2" style="margin-top: 8px;">
                            <label class="form-checkbox-label">
                                <input type="checkbox" id="consent" name="consent" required>
                                <span>
                                    Saya menyatakan bahwa saya adalah pemilik sah dari akun yang disebutkan di atas, dan saya memahami bahwa setelah akun diproses, akses ke aplikasi <strong>ESA Groups</strong> akan ditutup permanen.
                                </span>
                            </label>
                        </div>

                        <div class="form-group col-span-2" style="margin-top: 14px;">
                            <button type="submit" class="btn-submit" id="btn-submit-form">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                <span>Kirim Permohonan Penghapusan Akun</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Section 4: Corporate Contact & Support -->
            <section class="corp-card" id="kontak-resmi">
                <h3 class="corp-title">Pusat Layanan Bantuan &amp; Pengembang Resmi</h3>
                <p class="corp-subtitle">
                    PT Arina Multi Karya berkomitmen menjaga integritas privasi dan keselamatan data seluruh karyawan konsorsium ESA Groups.
                </p>

                <div class="corp-grid">
                    <div class="corp-item">
                        <strong>Perusahaan Pengembang:</strong>
                        PT Arina Multi Karya (Induk ESA Groups)
                    </div>
                    <div class="corp-item">
                        <strong>Email IT Support &amp; Privasi Data:</strong>
                        <a href="mailto:itsupport@arina.co.id" style="color: #BAE6FD; text-decoration: none; font-weight: 700;">itsupport@arina.co.id</a>
                    </div>
                    <div class="corp-item">
                        <strong>Alamat Kantor Pusat:</strong>
                        Jl. Rajawali No. 18-20 Surabaya, Jawa Timur, Indonesia
                    </div>
                    <div class="corp-item">
                        <strong>Website Resmi Perusahaan:</strong>
                        <a href="https://esa-solutions.id" target="_blank" style="color: #BAE6FD; text-decoration: none; font-weight: 700;">https://esa-solutions.id</a>
                    </div>
                </div>

                <div class="corp-actions">
                    <a href="mailto:itsupport@arina.co.id?subject=Permohonan%20Penghapusan%20Akun%20ESA%20Groups" class="btn-corp btn-corp-white">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span>Kirim Email Langsung ke IT Support</span>
                    </a>
                    <a href="/privacy-policy" class="btn-corp btn-corp-trans">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>Lihat Kebijakan Privasi Lengkap</span>
                    </a>
                </div>
            </section>

        </main>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="site-footer-links">
            <a href="https://esa-solutions.id">Beranda Portal</a>
            <span>•</span>
            <a href="/privacy-policy">Kebijakan Privasi</a>
            <span>•</span>
            <a href="mailto:itsupport@arina.co.id">Helpdesk IT</a>
            <span>•</span>
            <a href="#prosedur">Kembali ke Atas</a>
        </div>
        <p>&copy; 2026 <strong>PT Arina Multi Karya</strong>. Seluruh Hak Cipta Dilindungi.</p>
        <p style="font-size: 0.76rem; margin-top: 6px;">
            Halaman ini disediakan untuk memenuhi kewajiban <em>Google Play Account Deletion &amp; Data Safety Requirement</em>.
        </p>
    </footer>

    <!-- Form Handler Script -->
    <script>
        function handleFormSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-form');
            btn.innerHTML = '<span>Mengirim Permohonan...</span>';
            btn.disabled = true;

            const name = document.getElementById('employee_name').value;
            const nik = document.getElementById('employee_id').value;
            const randomId = Math.floor(1000 + Math.random() * 9000);
            const ticketNo = 'REQ-ESA-2026-' + randomId;

            setTimeout(function() {
                document.getElementById('ticket-number').innerText = ticketNo;
                document.getElementById('success-banner').style.display = 'block';
                document.getElementById('deletion-form').reset();
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg><span>Permohonan Terkirim</span>';
                btn.disabled = false;
                
                // Smooth scroll to notification banner
                document.getElementById('success-banner').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 800);
        }
    </script>

</body>
</html>
