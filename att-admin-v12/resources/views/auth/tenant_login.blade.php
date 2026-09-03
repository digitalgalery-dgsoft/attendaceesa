<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenantPrincipal?->name ?? 'Portal' }} - Portal Login</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @php
        $brandColor = $tenantPrincipal?->theme_color ?? '#0F52BA';
        $brandSecondary = $tenantPrincipal?->theme_color_secondary ?? ($tenantPrincipal?->theme_color ?? '#2563EB');
    @endphp

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --brand-primary: {{ $brandColor }};
            --brand-secondary: {{ $brandSecondary }};
            --brand-gradient: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            --brand-light: {{ $brandColor }}15;
            --brand-glow: {{ $brandColor }}33;
            --bg-main: #f8fafc;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --card-border-focus: {{ $brandColor }};
            --shadow-card: 0 10px 30px -5px rgba(0, 0, 0, 0.07), 0 4px 12px -2px rgba(0, 0, 0, 0.04);
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
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-image: 
                radial-gradient(circle at 10% 15%, {{ $brandColor }}12 0%, transparent 45%),
                radial-gradient(circle at 90% 85%, {{ $brandColor }}10 0%, transparent 45%);
            background-attachment: fixed;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: var(--shadow-card);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2.25rem;
            position: relative;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Top Lang Switcher in Card */
        .card-top-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 0.5rem;
        }

        .lang-switch-box {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
        }

        .lang-btn {
            border: none;
            background: transparent;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .lang-btn.active {
            background: #ffffff;
            color: var(--text-heading);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }

        .lang-btn:hover:not(.active) {
            color: var(--brand-primary);
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.15rem;
        }

        .brand-logo-img {
            max-height: 52px;
            max-width: 160px;
            object-fit: contain;
        }

        .brand-badge-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: var(--brand-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            box-shadow: 0 6px 16px var(--brand-glow);
        }

        .portal-brand-badge {
            display: inline-block;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--brand-primary);
            background: var(--brand-light);
            padding: 0.25rem 0.8rem;
            border-radius: 9999px;
            margin-bottom: 0.6rem;
            border: 1px solid var(--brand-glow);
        }

        .login-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.5px;
            line-height: 1.25;
            margin-bottom: 0.35rem;
        }

        .login-subtitle {
            font-size: 0.86rem;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 0.45rem;
        }

        .form-label span.req {
            color: #ef4444;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            font-size: 0.95rem;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            font-size: 0.92rem;
            color: var(--text-heading);
            background: #f8fafc;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            background: #ffffff;
            border-color: var(--card-border-focus);
            box-shadow: 0 0 0 3px var(--brand-glow);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.95rem;
            padding: 0.25rem;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-heading);
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.4rem;
            font-size: 0.84rem;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-body);
            cursor: pointer;
            user-select: none;
            font-weight: 500;
        }

        .remember-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--brand-primary);
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: var(--brand-gradient);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px var(--brand-glow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-submit:hover {
            filter: brightness(1.08);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--brand-glow);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            border-top: 1px solid var(--card-border);
            padding-top: 1.25rem;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-body);
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--brand-primary);
        }

        /* ===============================================================
           PROFESSIONAL LOGIN LOADING SCREEN STYLES
           =============================================================== */
        .portal-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                        visibility 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .portal-loading-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }

        .portal-loading-card {
            position: relative;
            width: 90%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.3),
                        0 0 0 1px rgba(0, 0, 0, 0.05),
                        0 12px 32px -4px var(--brand-glow, rgba(15, 82, 186, 0.25));
            border-radius: 24px;
            padding: 2.25rem 2rem 1.65rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            overflow: hidden;
            transform: scale(0.92) translateY(14px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .portal-loading-overlay.active .portal-loading-card {
            transform: scale(1) translateY(0);
        }

        .portal-loading-glow {
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--brand-glow, rgba(15, 82, 186, 0.3)) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            filter: blur(25px);
            pointer-events: none;
            z-index: 0;
        }

        .portal-spinner-wrapper {
            position: relative;
            width: 92px;
            height: 92px;
            margin-bottom: 1.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .portal-spinner-outer {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: var(--brand-primary, #0F52BA);
            border-right-color: var(--brand-secondary, #2563EB);
            animation: portalSpin 1.1s cubic-bezier(0.5, 0.1, 0.5, 0.9) infinite;
        }

        .portal-spinner-inner {
            position: absolute;
            inset: 7px;
            border-radius: 50%;
            border: 2px dashed rgba(15, 82, 186, 0.3);
            border-bottom-color: var(--brand-primary, #0F52BA);
            animation: portalSpinReverse 2.2s linear infinite;
        }

        .portal-spinner-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            animation: portalPulse 2s ease-in-out infinite;
            z-index: 2;
        }

        .portal-spinner-logo {
            max-width: 34px;
            max-height: 34px;
            object-fit: contain;
        }

        .portal-spinner-fallback {
            color: var(--brand-primary, #0F52BA);
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .portal-loading-text-group {
            position: relative;
            z-index: 1;
            margin-bottom: 1.25rem;
            max-width: 320px;
        }

        .portal-loading-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
            margin-bottom: 0.35rem;
        }

        .portal-loading-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.45;
            font-weight: 500;
        }

        .loading-dots span {
            display: inline-block;
            animation: loadingDots 1.4s infinite;
            font-weight: 800;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

        .portal-loading-progress-track {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 6px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 1.15rem;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);
        }

        .portal-loading-progress-bar {
            position: absolute;
            top: 0;
            bottom: 0;
            left: -40%;
            width: 45%;
            background: var(--brand-gradient, linear-gradient(90deg, #0F52BA, #3b82f6));
            border-radius: 999px;
            box-shadow: 0 0 12px var(--brand-glow, rgba(15, 82, 186, 0.4));
            animation: portalIndeterminate 1.6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        .portal-loading-meta {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            font-size: 0.74rem;
            color: #94a3b8;
            font-weight: 600;
            padding-top: 0.75rem;
            border-top: 1px dashed #e2e8f0;
        }

        .portal-meta-brand {
            color: var(--brand-primary, #0F52BA);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
        }

        .portal-meta-tip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        @keyframes portalSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes portalSpinReverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }

        @keyframes portalPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes portalIndeterminate {
            0% { left: -45%; width: 35%; }
            50% { left: 25%; width: 60%; }
            100% { left: 105%; width: 40%; }
        }

        @keyframes loadingDots {
            0%, 20% { opacity: 0; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-2px); }
            80%, 100% { opacity: 0; transform: translateY(0); }
        }
    </style>
</head>
<body x-data="{
    lang: localStorage.getItem('portal_lang') || '{{ request()->query('lang', 'en') }}',
    principalName: '{{ $tenantPrincipal->name }}',
    setLang(l) {
        this.lang = l;
        localStorage.setItem('portal_lang', l);
        document.documentElement.lang = l;
        document.title = (l === 'en') 
            ? (this.principalName + ' - Portal Login') 
            : ('Masuk - ' + ('{{ $tenantPrincipal->portal_title ?? ($tenantPrincipal->name . " Portal Pelaporan") }}'));
    }
}" x-init="setLang(lang)">

    <div class="login-card">
        <!-- Language Switcher in Card Header -->
        <div class="card-top-bar">
            <div class="lang-switch-box">
                <button type="button" class="lang-btn" :class="{ 'active': lang === 'en' }" @click="setLang('en')">
                    🇺🇸 EN
                </button>
                <button type="button" class="lang-btn" :class="{ 'active': lang === 'id' }" @click="setLang('id')">
                    🇮🇩 ID
                </button>
            </div>
        </div>

        <div class="login-header">
            <div class="brand-logo-box">
                @if($tenantPrincipal->logo_url)
                    <img src="{{ $tenantPrincipal->logo_url }}" alt="{{ $tenantPrincipal->name }}" class="brand-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="brand-badge-icon" style="display: none;">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                @elseif(!empty($setting->app_logo))
                    <img src="{{ asset('storage/' . $setting->app_logo) }}" alt="{{ $tenantPrincipal->name }}" class="brand-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="brand-badge-icon" style="display: none;">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                @else
                    <div class="brand-badge-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                @endif
            </div>

            @if(isset($tenantPrincipalsAll) && $tenantPrincipalsAll->count() > 1)
            <div style="display: flex; justify-content: center; gap: 0.4rem; margin: 0.75rem 0 1.25rem; flex-wrap: wrap;">
                @foreach($tenantPrincipalsAll->unique('id') as $ent)
                    @php
                        $entGradient = $ent->theme_gradient ?? 'linear-gradient(135deg, #0F52BA, #1E88E5)';
                    @endphp
                    <a href="?p={{ $ent->id }}" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.76rem; font-weight: 700; text-decoration: none; border: 1px solid {{ $tenantPrincipal->id == $ent->id ? 'transparent' : '#e2e8f0' }}; background: {{ $tenantPrincipal->id == $ent->id ? $entGradient : '#ffffff' }}; color: {{ $tenantPrincipal->id == $ent->id ? '#ffffff' : '#64748b' }}; box-shadow: {{ $tenantPrincipal->id == $ent->id ? '0 3px 10px rgba(0,0,0,0.15)' : 'none' }};">
                        <i class="fa-solid {{ $tenantPrincipal->id == $ent->id ? 'fa-circle-check' : 'fa-building' }}"></i>
                        {{ $ent->name }}
                    </a>
                @endforeach
            </div>
            @else
            <div class="portal-brand-badge">
                <i class="fa-solid fa-shield-halved"></i> {{ $tenantPrincipal->name }}
            </div>
            @endif

            <h1 class="login-title">
                <span x-show="lang === 'en'">{{ $tenantPrincipal->name }} Reporting &amp; Monitoring Portal</span>
                <span x-show="lang === 'id'" x-cloak>{{ $tenantPrincipal->portal_title ?? ('Portal Pelaporan & Monitoring ' . $tenantPrincipal->name) }}</span>
            </h1>
            <p class="login-subtitle">
                <span x-show="lang === 'en'">Sign in with your principal management credentials</span>
                <span x-show="lang === 'id'" x-cloak>Masuk dengan kredensial akun prinsiple Anda</span>
            </p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form action="{{ route('tenant.login.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">

            <div class="form-group">
                <label class="form-label" for="email">
                    <span x-show="lang === 'en'">Corporate Email Address</span>
                    <span x-show="lang === 'id'" x-cloak>Alamat Email</span>
                    <span class="req">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-input" 
                        :placeholder="lang === 'en' ? 'Enter your corporate email' : 'Masukkan alamat email Anda'"
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    <span x-show="lang === 'en'">Account Password</span>
                    <span x-show="lang === 'id'" x-cloak>Kata Sandi</span>
                    <span class="req">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input" 
                        :placeholder="lang === 'en' ? 'Enter your password' : 'Masukkan kata sandi akun'"
                        required
                    >
                    <i class="fa-solid fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>

            <div class="form-actions">
                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span x-show="lang === 'en'">Remember me</span>
                    <span x-show="lang === 'id'" x-cloak>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span x-show="lang === 'en'">Sign In to Portal</span>
                <span x-show="lang === 'id'" x-cloak>Masuk ke Portal</span>
            </button>
        </form>

        <div class="login-footer">
            <a href="/?p={{ $tenantPrincipal->id }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i>
                <span x-show="lang === 'en'">Return to Home</span>
                <span x-show="lang === 'id'" x-cloak>Kembali ke Halaman Utama</span>
            </a>
            <div>
                &copy; {{ date('Y') }} {{ $tenantPrincipal->name }}. 
                <span x-show="lang === 'en'">All rights reserved.</span>
                <span x-show="lang === 'id'" x-cloak>Hak cipta dilindungi.</span>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Professional Login Loading Screen Animation
        const loginForm = document.querySelector('form[action*="login"]');
        const loginOverlay = document.getElementById('loginLoadingOverlay');

        if (loginForm && loginOverlay) {
            loginForm.addEventListener('submit', function(e) {
                if (typeof loginForm.checkValidity === 'function' && !loginForm.checkValidity()) {
                    return;
                }
                loginOverlay.classList.add('active');
                loginOverlay.setAttribute('aria-hidden', 'false');
            });
        }
    </script>

    <!-- PROFESSIONAL LOGIN LOADING SCREEN OVERLAY -->
    <div id="loginLoadingOverlay" class="portal-loading-overlay" aria-hidden="true">
        <div class="portal-loading-card">
            <!-- Animated Ambient Glow -->
            <div class="portal-loading-glow"></div>

            <!-- Modern Dual-Orbit Spinner -->
            <div class="portal-spinner-wrapper">
                <div class="portal-spinner-outer"></div>
                <div class="portal-spinner-inner"></div>
                <div class="portal-spinner-icon">
                    @if(!empty($tenantPrincipal->logo_url))
                        <img src="{{ $tenantPrincipal->logo_url }}" alt="Logo" class="portal-spinner-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="portal-spinner-fallback" style="display: none;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    @else
                        <div class="portal-spinner-fallback">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Text & Status Message -->
            <div class="portal-loading-text-group">
                <h4 id="loginLoadingTitle" class="portal-loading-title">
                    <span x-show="lang === 'en'">Authenticating Account...</span>
                    <span x-show="lang === 'id'" x-cloak>Memverifikasi Kredensial...</span>
                </h4>
                <p id="loginLoadingSubtitle" class="portal-loading-subtitle">
                    <span x-show="lang === 'en'">Connecting to secure server, please wait</span>
                    <span x-show="lang === 'id'" x-cloak>Sedang menghubungkan ke server aman, mohon tunggu</span>
                    <span class="loading-dots"><span>.</span><span>.</span><span>.</span></span>
                </p>
            </div>

            <!-- Shimmering Indeterminate Progress Bar -->
            <div class="portal-loading-progress-track">
                <div class="portal-loading-progress-bar"></div>
            </div>

            <div class="portal-loading-meta">
                <span class="portal-meta-brand"><i class="fa-solid fa-shield-halved"></i> {{ $tenantPrincipal->name ?? 'Portal Principal' }}</span>
                <span class="portal-meta-tip"><i class="fa-solid fa-lock" style="font-size: 0.7rem;"></i> Enkripsi SSL 256-Bit</span>
            </div>
        </div>
    </div>
</body>
</html>
