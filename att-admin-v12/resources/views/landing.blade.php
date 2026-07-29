<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $setting->app_name ?? 'Attendance System' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --bg-start: #0f172a;
            --bg-end: #1e1b4b;
            --text-main: #f8fafc;
            --text-muted: #cbd5e1;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--bg-start) 0%, var(--bg-end) 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Background Animation */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 20s infinite alternate ease-in-out;
        }

        .shape-1 {
            width: 500px;
            height: 500px;
            background: rgba(79, 70, 229, 0.3);
            top: -100px;
            left: -100px;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            background: rgba(236, 72, 153, 0.2);
            bottom: -50px;
            right: -50px;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(50px, 30px) scale(1.1); }
            100% { transform: translate(-30px, 80px) scale(0.9); }
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .brand img {
            height: 40px;
            border-radius: 8px;
        }

        .nav-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 99px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .badge {
            background: rgba(79, 70, 229, 0.15);
            border: 1px solid rgba(79, 70, 229, 0.3);
            color: #818cf8;
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 2rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 1.125rem;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .btn-download {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: 99px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-download:hover {
            background: var(--primary-hover);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 35px -5px rgba(79, 70, 229, 0.6);
        }

        .btn-download svg {
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease;
        }

        .btn-download:hover svg {
            transform: translateY(2px);
        }

        /* Stats Section */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            width: 100%;
            margin-top: 4rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            padding: 2rem;
            border-radius: 24px;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Features Section */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 1.5rem;
        }
        
        .feature-item {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 2rem;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(79, 70, 229, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #818cf8;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }

        .feature-desc {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                padding: 1rem;
            }
            .hero {
                padding: 2rem 1rem;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <!-- Navigation -->
    <nav>
        <a href="/" class="brand">
            @if(isset($setting) && $setting->logo_path)
                <img src="{{ Storage::url($setting->logo_path) }}" alt="Logo">
            @endif
            {{ $setting->app_name ?? 'Attendance System' }}
        </a>
        <a href="/admin" class="nav-btn btn-login">Admin Login</a>
    </nav>

    <!-- Hero Section -->
    <main class="hero">
        <div class="badge">Sistem Absensi Cerdas</div>
        
        <h1>Pantau Kinerja<br>Di Mana Saja</h1>
        
        <p class="subtitle">
            Tingkatkan efisiensi manajemen kehadiran dengan pelacakan lokasi real-time, verifikasi foto, dan sistem roster yang terintegrasi.
        </p>
        
        @if(isset($setting) && $setting->mobile_app_url)
            <a href="{{ $setting->mobile_app_url }}" class="btn-download" target="_blank" rel="noopener noreferrer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download APK
            </a>
        @else
            <button class="btn-download" style="opacity: 0.5; cursor: not-allowed;" title="URL belum diatur admin">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                APK Belum Tersedia
            </button>
        @endif

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['employees'] ?? 0) }}</div>
                <div class="stat-label">Total Karyawan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['areas'] ?? 0) }}</div>
                <div class="stat-label">Area Terdaftar</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['principals'] ?? 0) }}</div>
                <div class="stat-label">Principal Aktif</div>
            </div>
        </div>
    </main>

    <!-- Features Section -->
    <section class="features-grid">
        <div class="feature-item">
            <div class="feature-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            </div>
            <h3 class="feature-title">Live Tracking</h3>
            <p class="feature-desc">Pelacakan lokasi akurat berbasis GPS untuk memastikan karyawan berada di area atau radius yang telah ditentukan oleh sistem.</p>
        </div>
        <div class="feature-item">
            <div class="feature-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <h3 class="feature-title">Roster Management</h3>
            <p class="feature-desc">Manajemen jadwal yang fleksibel dan tersistem, memungkinkan penugasan khusus harian untuk setiap karyawan.</p>
        </div>
        <div class="feature-item">
            <div class="feature-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
            </div>
            <h3 class="feature-title">Photo Verification</h3>
            <p class="feature-desc">Keamanan ekstra dengan wajib foto selfie (watermark lokasi & waktu) saat melakukan Check-In, Check-Out, maupun Kunjungan.</p>
        </div>
    </section>
</body>
</html>
