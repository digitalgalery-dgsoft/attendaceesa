<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - {{ $tenantPrincipal->portal_title ?? ($tenantPrincipal->name . ' Portal Pelaporan') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
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

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
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
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.5px;
            line-height: 1.25;
            margin-bottom: 0.35rem;
        }

        .login-subtitle {
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
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

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.84rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-body);
            cursor: pointer;
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
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
            font-weight: 700;
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
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--brand-primary);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="brand-logo-box">
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

            @if(isset($tenantPrincipalsAll) && $tenantPrincipalsAll->count() > 1)
            <div style="display: flex; justify-content: center; gap: 0.4rem; margin: 0.75rem 0 1.25rem; flex-wrap: wrap;">
                @foreach($tenantPrincipalsAll->unique('name') as $ent)
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

            <h1 class="login-title">{{ $tenantPrincipal->portal_title ?? ($tenantPrincipal->name . ' Portal Pelaporan') }}</h1>
            <p class="login-subtitle">Masuk dengan kredensial akun prinsiple Anda</p>
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
                    Alamat Email <span class="req">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-input" 
                        placeholder="Masukkan alamat email Anda"
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Kata Sandi <span class="req">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input" 
                        placeholder="Masukkan kata sandi akun"
                        required
                    >
                    <i class="fa-solid fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>

            <div class="form-actions">
                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i>
                Masuk ke Portal
            </button>
        </form>

        <div class="login-footer">
            <a href="/?p={{ $tenantPrincipal->id }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Utama
            </a>
            <div>
                &copy; {{ date('Y') }} {{ $tenantPrincipal->name }}. All rights reserved.
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
    </script>
</body>
</html>
