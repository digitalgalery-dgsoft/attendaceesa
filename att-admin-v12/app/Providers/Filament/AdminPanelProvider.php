<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $setting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
        } catch (\Exception $e) {
            $setting = null;
        }

        $appName = $setting?->app_name ?? 'ESA Groups';
        $themeColor = $setting?->theme_color ?? '#0F52BA';
        $logoPath = $setting?->logo_path;
        $darkModeEnabled = (bool) ($setting?->dark_mode_enabled ?? true);
        $darkModeTheme = $setting?->dark_mode_theme ?? 'dark_navy';

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->darkMode($darkModeEnabled)
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->brandName(fn (): string => \App\Models\Setting::first()?->app_name ?? 'ESA Groups')
            ->brandLogo(function (): ?string {
                try {
                    $setting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
                    $path = $setting?->logo_path;
                    if (!$path) return null;

                    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');
                    $candidates = [
                        storage_path('app/public/' . $cleanPath),
                        storage_path('app/public/' . $path),
                        storage_path('app/public/logos/' . basename($path)),
                        storage_path('app/' . $cleanPath),
                        storage_path('app/' . $path),
                        storage_path('app/logos/' . basename($path)),
                        storage_path('app/private/' . $cleanPath),
                        storage_path('app/private/' . $path),
                        storage_path('app/private/logos/' . basename($path)),
                        public_path('storage/' . $cleanPath),
                        public_path('storage/' . $path),
                        public_path('storage/logos/' . basename($path)),
                        public_path($path),
                        public_path($cleanPath),
                        base_path('storage/app/public/' . $cleanPath),
                        base_path('storage/app/public/' . $path),
                        base_path('storage/app/' . $cleanPath),
                        base_path('storage/app/' . $path),
                    ];

                    foreach ($candidates as $filePath) {
                        if (file_exists($filePath) && !is_dir($filePath)) {
                            $content = file_get_contents($filePath);
                            $mime = @mime_content_type($filePath) ?: 'image/png';
                            return 'data:' . $mime . ';base64,' . base64_encode($content);
                        }
                    }

                    return '/app-logo';
                } catch (\Exception $e) {
                    return null;
                }
            })
            ->brandLogoHeight('2.75rem')
            ->font('Outfit')
            ->colors([
                'primary' => [
                    50  => '#eff6ff',
                    100 => '#dbeafe',
                    200 => '#bfdbfe',
                    300 => '#93c5fd',
                    400 => '#60a5fa',
                    500 => '#2563eb',
                    600 => '#0F52BA',
                    700 => '#1d4ed8',
                    800 => '#1e40af',
                    900 => '#1e3a8a',
                    950 => '#172554',
                ],
                'danger' => '#EF4444',
                'success' => '#10B981',
                'warning' => '#F59E0B',
                'info' => '#06B6D4',
                'gray' => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): string {
                    $setting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
                    $themeFromDb = $setting?->dark_mode_theme ?? 'pitch_black';

                    return '<link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
                    <script>
                        (function() {
                            try {
                                const dbTheme = \'' . $themeFromDb . '\';
                                let activeTheme = localStorage.getItem(\'esa_dark_theme\');
                                if (!activeTheme || activeTheme === \'undefined\' || activeTheme === \'null\') {
                                    activeTheme = dbTheme;
                                    localStorage.setItem(\'esa_dark_theme\', dbTheme);
                                }
                                document.documentElement.setAttribute(\'data-dark-theme\', activeTheme);
                                if (document.body) {
                                    document.body.setAttribute(\'data-dark-theme\', activeTheme);
                                }
                                const isDark = localStorage.getItem(\'theme\') === \'dark\' || (!(\'theme\' in localStorage) && window.matchMedia(\'(prefers-color-scheme: dark)\').matches);
                                if (isDark) {
                                    document.documentElement.classList.add(\'dark\');
                                    if (document.body) document.body.classList.add(\'dark\');
                                }
                            } catch(e) {}
                        })();
                    </script>
                    <style>
                        /* GLOBAL OUTFIT TYPOGRAPHY & CLEAN PRINCIPAL PORTAL THEME */
                        * {
                            font-family: \'Plus Jakarta Sans\', \'Outfit\', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                        }
                        
                        /* Background body */
                        .fi-body {
                            background-color: #f8fafc !important;
                        }

                        /* ══════════════════════════════════════════════════════════════
                           THEME 1: PITCH BLACK (Pure AMOLED Black / Hitam Pekat)
                           ══════════════════════════════════════════════════════════════ */
                        html[data-dark-theme="pitch_black"].dark,
                        html[data-dark-theme="pitch_black"] body.dark,
                        body[data-dark-theme="pitch_black"].dark,
                        html.dark[data-dark-theme="pitch_black"] {
                            --fi-bg-body: #000000 !important;
                            --fi-bg-surface: #0a0a0a !important;
                            --fi-bg-card: #111111 !important;
                            --fi-bg-input: #161616 !important;
                            --fi-border-main: #222222 !important;
                            --fi-border-subtle: #333333 !important;
                            --fi-hover-bg: #1a1a1a !important;
                            --fi-active-border: #60a5fa !important;
                            --fi-accent-gradient: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.03) 100%) !important;
                        }
                        html[data-dark-theme="pitch_black"].dark,
                        html[data-dark-theme="pitch_black"].dark .fi-body,
                        html[data-dark-theme="pitch_black"].dark .fi-main,
                        html[data-dark-theme="pitch_black"].dark .fi-page,
                        html[data-dark-theme="pitch_black"].dark .fi-simple-layout,
                        html[data-dark-theme="pitch_black"].dark main {
                            background-color: #000000 !important;
                        }
                        html[data-dark-theme="pitch_black"].dark aside.fi-sidebar,
                        html[data-dark-theme="pitch_black"].dark .fi-sidebar-header,
                        html[data-dark-theme="pitch_black"].dark .fi-topbar {
                            background-color: #070707 !important;
                            border-color: #1a1a1a !important;
                        }
                        html[data-dark-theme="pitch_black"].dark .fi-section,
                        html[data-dark-theme="pitch_black"].dark .fi-card,
                        html[data-dark-theme="pitch_black"].dark .fi-ta-ctn,
                        html[data-dark-theme="pitch_black"].dark .fi-modal-window,
                        html[data-dark-theme="pitch_black"].dark .fi-dropdown-panel,
                        html[data-dark-theme="pitch_black"].dark .fi-wi-widget > div,
                        html[data-dark-theme="pitch_black"].dark .fi-simple-main {
                            background-color: #0d0d0d !important;
                            border-color: #1f1f1f !important;
                        }
                        html[data-dark-theme="pitch_black"].dark .fi-ta-header,
                        html[data-dark-theme="pitch_black"].dark .fi-ta-header-toolbar,
                        html[data-dark-theme="pitch_black"].dark .fi-modal-header,
                        html[data-dark-theme="pitch_black"].dark .fi-modal-footer {
                            background-color: #0d0d0d !important;
                            border-color: #1f1f1f !important;
                        }
                        html[data-dark-theme="pitch_black"].dark .fi-input-wrp,
                        html[data-dark-theme="pitch_black"].dark .fi-select-input {
                            background-color: #141414 !important;
                            border-color: #2b2b2b !important;
                        }
                        html[data-dark-theme="pitch_black"].dark .fi-sidebar-item:hover > a,
                        html[data-dark-theme="pitch_black"].dark .fi-sidebar-item:hover > button,
                        html[data-dark-theme="pitch_black"].dark .fi-ta-row:hover {
                            background-color: #171717 !important;
                        }

                        /* ══════════════════════════════════════════════════════════════
                           THEME 2: DARK NAVY (Default Midnight Blue)
                           ══════════════════════════════════════════════════════════════ */
                        html[data-dark-theme="dark_navy"].dark,
                        html.dark:not([data-dark-theme]) {
                            --fi-bg-body: #0b1120 !important;
                            --fi-bg-surface: #0f172a !important;
                            --fi-bg-card: #1e293b !important;
                            --fi-bg-input: #1e293b !important;
                            --fi-border-main: #1e293b !important;
                            --fi-border-subtle: #334155 !important;
                            --fi-hover-bg: #1e293b !important;
                            --fi-active-border: #3b82f6 !important;
                            --fi-accent-gradient: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(59, 130, 246, 0.12) 100%) !important;
                        }
                        html[data-dark-theme="dark_navy"].dark .fi-body,
                        html[data-dark-theme="dark_navy"].dark .fi-main,
                        html[data-dark-theme="dark_navy"].dark .fi-simple-layout {
                            background-color: #0b1120 !important;
                        }
                        html[data-dark-theme="dark_navy"].dark aside.fi-sidebar,
                        html[data-dark-theme="dark_navy"].dark .fi-sidebar-header,
                        html[data-dark-theme="dark_navy"].dark .fi-topbar {
                            background-color: #0f172a !important;
                            border-color: #1e293b !important;
                        }
                        html[data-dark-theme="dark_navy"].dark .fi-section,
                        html[data-dark-theme="dark_navy"].dark .fi-card,
                        html[data-dark-theme="dark_navy"].dark .fi-ta-ctn,
                        html[data-dark-theme="dark_navy"].dark .fi-modal-window,
                        html[data-dark-theme="dark_navy"].dark .fi-dropdown-panel,
                        html[data-dark-theme="dark_navy"].dark .fi-wi-widget > div,
                        html[data-dark-theme="dark_navy"].dark .fi-simple-main {
                            background-color: #0f172a !important;
                            border-color: #1e293b !important;
                        }

                        /* ══════════════════════════════════════════════════════════════
                           THEME 3: DARK GREY (Charcoal Slate / Abu-Abu Modern)
                           ══════════════════════════════════════════════════════════════ */
                        html[data-dark-theme="dark_grey"].dark {
                            --fi-bg-body: #141416 !important;
                            --fi-bg-surface: #1e1e22 !important;
                            --fi-bg-card: #25252b !important;
                            --fi-bg-input: #2a2a32 !important;
                            --fi-border-main: #32323a !important;
                            --fi-border-subtle: #454550 !important;
                            --fi-hover-bg: #2d2d35 !important;
                            --fi-active-border: #38bdf8 !important;
                            --fi-accent-gradient: linear-gradient(135deg, rgba(56, 189, 248, 0.2) 0%, rgba(14, 165, 233, 0.08) 100%) !important;
                        }
                        html[data-dark-theme="dark_grey"].dark .fi-body,
                        html[data-dark-theme="dark_grey"].dark .fi-main,
                        html[data-dark-theme="dark_grey"].dark .fi-simple-layout {
                            background-color: #141416 !important;
                        }
                        html[data-dark-theme="dark_grey"].dark aside.fi-sidebar,
                        html[data-dark-theme="dark_grey"].dark .fi-sidebar-header,
                        html[data-dark-theme="dark_grey"].dark .fi-topbar {
                            background-color: #1a1a1e !important;
                            border-color: #2b2b32 !important;
                        }
                        html[data-dark-theme="dark_grey"].dark .fi-section,
                        html[data-dark-theme="dark_grey"].dark .fi-card,
                        html[data-dark-theme="dark_grey"].dark .fi-ta-ctn,
                        html[data-dark-theme="dark_grey"].dark .fi-modal-window,
                        html[data-dark-theme="dark_grey"].dark .fi-dropdown-panel,
                        html[data-dark-theme="dark_grey"].dark .fi-wi-widget > div,
                        html[data-dark-theme="dark_grey"].dark .fi-simple-main {
                            background-color: #222227 !important;
                            border-color: #32323a !important;
                        }

                        /* ══════════════════════════════════════════════════════════════
                           THEME 4: DARK EMERALD (Deep Forest / Hijau Gelap Mewah)
                           ══════════════════════════════════════════════════════════════ */
                        html[data-dark-theme="dark_emerald"].dark {
                            --fi-bg-body: #021a14 !important;
                            --fi-bg-surface: #042920 !important;
                            --fi-bg-card: #06392c !important;
                            --fi-bg-input: #084c3b !important;
                            --fi-border-main: #064e3b !important;
                            --fi-border-subtle: #059669 !important;
                            --fi-hover-bg: #074334 !important;
                            --fi-active-border: #34d399 !important;
                            --fi-accent-gradient: linear-gradient(135deg, rgba(52, 211, 153, 0.22) 0%, rgba(16, 185, 129, 0.1) 100%) !important;
                        }
                        html[data-dark-theme="dark_emerald"].dark .fi-body,
                        html[data-dark-theme="dark_emerald"].dark .fi-main,
                        html[data-dark-theme="dark_emerald"].dark .fi-simple-layout {
                            background-color: #021a14 !important;
                        }
                        html[data-dark-theme="dark_emerald"].dark aside.fi-sidebar,
                        html[data-dark-theme="dark_emerald"].dark .fi-sidebar-header,
                        html[data-dark-theme="dark_emerald"].dark .fi-topbar {
                            background-color: #042920 !important;
                            border-color: #064e3b !important;
                        }
                        html[data-dark-theme="dark_emerald"].dark .fi-section,
                        html[data-dark-theme="dark_emerald"].dark .fi-card,
                        html[data-dark-theme="dark_emerald"].dark .fi-ta-ctn,
                        html[data-dark-theme="dark_emerald"].dark .fi-modal-window,
                        html[data-dark-theme="dark_emerald"].dark .fi-dropdown-panel,
                        html[data-dark-theme="dark_emerald"].dark .fi-wi-widget > div,
                        html[data-dark-theme="dark_emerald"].dark .fi-simple-main {
                            background-color: #06392c !important;
                            border-color: #075e47 !important;
                        }

                        /* ══════════════════════════════════════════════════════════════
                           THEME 5: DARK PURPLE (Royal Amethyst / Ungu Gelap)
                           ══════════════════════════════════════════════════════════════ */
                        html[data-dark-theme="dark_purple"].dark {
                            --fi-bg-body: #0b041a !important;
                            --fi-bg-surface: #14082e !important;
                            --fi-bg-card: #1f0d45 !important;
                            --fi-bg-input: #29125c !important;
                            --fi-border-main: #331770 !important;
                            --fi-border-subtle: #581c87 !important;
                            --fi-hover-bg: #2b135e !important;
                            --fi-active-border: #c084fc !important;
                            --fi-accent-gradient: linear-gradient(135deg, rgba(192, 132, 252, 0.22) 0%, rgba(168, 85, 247, 0.1) 100%) !important;
                        }
                        html[data-dark-theme="dark_purple"].dark .fi-body,
                        html[data-dark-theme="dark_purple"].dark .fi-main,
                        html[data-dark-theme="dark_purple"].dark .fi-simple-layout {
                            background-color: #0b041a !important;
                        }
                        html[data-dark-theme="dark_purple"].dark aside.fi-sidebar,
                        html[data-dark-theme="dark_purple"].dark .fi-sidebar-header,
                        html[data-dark-theme="dark_purple"].dark .fi-topbar {
                            background-color: #14082e !important;
                            border-color: #2b135e !important;
                        }
                        html[data-dark-theme="dark_purple"].dark .fi-section,
                        html[data-dark-theme="dark_purple"].dark .fi-card,
                        html[data-dark-theme="dark_purple"].dark .fi-ta-ctn,
                        html[data-dark-theme="dark_purple"].dark .fi-modal-window,
                        html[data-dark-theme="dark_purple"].dark .fi-dropdown-panel,
                        html[data-dark-theme="dark_purple"].dark .fi-wi-widget > div,
                        html[data-dark-theme="dark_purple"].dark .fi-simple-main {
                            background-color: #1f0d45 !important;
                            border-color: #371878 !important;
                        }

                        .dark .fi-body {
                            background-color: var(--fi-bg-body) !important;
                        }

                    /* ─── CLEAN FILAMENT LOGIN PAGE STYLING ─── */
                    .fi-simple-layout {
                        background-color: #f8fafc !important;
                        background-image: 
                            radial-gradient(circle at 12% 10%, rgba(79, 70, 229, 0.05) 0%, transparent 45%),
                            radial-gradient(circle at 88% 85%, rgba(2, 132, 199, 0.05) 0%, transparent 45%) !important;
                        background-attachment: fixed !important;
                        min-height: 100vh !important;
                    }

                    .fi-simple-main {
                        background-color: #ffffff !important;
                        border: 1px solid #e2e8f0 !important;
                        border-radius: 24px !important;
                        box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.06), 0 8px 12px -4px rgba(0, 0, 0, 0.03) !important;
                        padding: 2.5rem 2.25rem !important;
                        max-width: 440px !important;
                    }

                    .dark .fi-simple-layout {
                        background-color: var(--fi-bg-body) !important;
                    }
                    .dark .fi-simple-main {
                        background-color: var(--fi-bg-surface) !important;
                        border-color: var(--fi-border-main) !important;
                    }

                    .fi-simple-header {
                        margin-bottom: 2rem !important;
                        text-align: center !important;
                    }

                    .fi-simple-header .fi-logo {
                        font-size: 1.5rem !important;
                        font-weight: 800 !important;
                        color: #0f172a !important;
                        margin-bottom: 0.5rem !important;
                    }

                    .fi-simple-header-heading {
                        font-size: 1.55rem !important;
                        font-weight: 800 !important;
                        color: #0f172a !important;
                        letter-spacing: -0.02em !important;
                    }

                    .fi-simple-header-subheading {
                        font-size: 0.9rem !important;
                        color: #64748b !important;
                        margin-top: 0.4rem !important;
                        line-height: 1.5 !important;
                    }

                    /* Simple Page Inputs */
                    .fi-simple-main .fi-input-wrp {
                        background-color: #ffffff !important;
                        border: 1px solid #cbd5e1 !important;
                        border-radius: 12px !important;
                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
                        transition: all 0.2s ease !important;
                    }

                    .fi-simple-main .fi-input-wrp:focus-within {
                        border-color: #4F46E5 !important;
                        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
                    }

                    .fi-simple-main label {
                        font-size: 0.86rem !important;
                        font-weight: 700 !important;
                        color: #334155 !important;
                    }

                    /* Simple Page Button */
                    .fi-simple-main button[type="submit"],
                    .fi-simple-main .fi-btn-color-primary {
                        background: linear-gradient(135deg, #4F46E5 0%, #0284C7 100%) !important;
                        border-radius: 12px !important;
                        padding: 0.85rem 1.5rem !important;
                        font-size: 0.95rem !important;
                        font-weight: 700 !important;
                        color: #ffffff !important;
                        box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.4) !important;
                        border: none !important;
                        transition: all 0.25s ease !important;
                    }

                    .fi-simple-main button[type="submit"]:hover,
                    .fi-simple-main .fi-btn-color-primary:hover {
                        transform: translateY(-2px) !important;
                        box-shadow: 0 12px 25px -4px rgba(79, 70, 229, 0.5) !important;
                    }

                    /* SIDEBAR CLEAN DESIGN */
                    aside.fi-sidebar {
                        background-color: #ffffff !important;
                        border-right: 1px solid #e2e8f0 !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
                    }
                    .dark aside.fi-sidebar {
                        background-color: var(--fi-bg-surface) !important;
                        border-right-color: var(--fi-border-main) !important;
                        box-shadow: none !important;
                    }

                    /* Sidebar Header */
                    .fi-sidebar-header {
                        background-color: #ffffff !important;
                        border-bottom: 1px solid #f1f5f9 !important;
                        padding-top: 1.15rem !important;
                        padding-bottom: 1.15rem !important;
                    }
                    .dark .fi-sidebar-header {
                        background-color: var(--fi-bg-surface) !important;
                        border-bottom-color: var(--fi-border-main) !important;
                    }
                    .fi-sidebar-header .fi-logo {
                        color: #0f172a !important;
                        font-weight: 800 !important;
                        letter-spacing: -0.3px !important;
                    }
                    .dark .fi-sidebar-header .fi-logo {
                        color: #f8fafc !important;
                    }

                    /* Sidebar Navigation Group Labels */
                    .fi-sidebar-group-label {
                        font-size: 0.68rem !important;
                        font-weight: 800 !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.8px !important;
                        color: #64748b !important;
                        padding-top: 0.85rem !important;
                    }
                    .dark .fi-sidebar-group-label {
                        color: #94a3b8 !important;
                    }

                    /* Sidebar Items */
                    .fi-sidebar-item-button,
                    .fi-sidebar-item > a,
                    .fi-sidebar-item > button {
                        border-radius: 10px !important;
                        margin-bottom: 2px !important;
                        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    }
                    .fi-sidebar-item-label {
                        font-size: 0.86rem !important;
                        font-weight: 600 !important;
                        color: #334155 !important;
                    }
                    .dark .fi-sidebar-item-label {
                        color: #cbd5e1 !important;
                    }
                    .fi-sidebar-item-icon {
                        color: #64748b !important;
                        transition: color 0.15s ease !important;
                    }
                    .dark .fi-sidebar-item-icon {
                        color: #94a3b8 !important;
                    }

                    /* Sidebar Hover State */
                    .fi-sidebar-item:hover > a,
                    .fi-sidebar-item:hover > button {
                        background-color: #f1f5f9 !important;
                    }
                    .dark .fi-sidebar-item:hover > a,
                    .dark .fi-sidebar-item:hover > button {
                        background-color: var(--fi-hover-bg) !important;
                    }
                    .fi-sidebar-item:hover .fi-sidebar-item-label {
                        color: #0f172a !important;
                    }
                    .dark .fi-sidebar-item:hover .fi-sidebar-item-label {
                        color: #ffffff !important;
                    }
                    .fi-sidebar-item:hover .fi-sidebar-item-icon {
                        color: #0F52BA !important;
                    }
                    .dark .fi-sidebar-item:hover .fi-sidebar-item-icon {
                        color: #60a5fa !important;
                    }

                    /* Sidebar Active State (Portal-style highlight) */
                    .fi-sidebar-item.fi-active > a,
                    .fi-sidebar-item.fi-active > button {
                        background: linear-gradient(135deg, rgba(15, 82, 186, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%) !important;
                        border-left: 3px solid #0F52BA !important;
                    }
                    .dark .fi-sidebar-item.fi-active > a,
                    .dark .fi-sidebar-item.fi-active > button {
                        background: var(--fi-accent-gradient) !important;
                        border-left: 3px solid var(--fi-active-border) !important;
                    }
                    .fi-sidebar-item.fi-active .fi-sidebar-item-label {
                        color: #0F52BA !important;
                        font-weight: 700 !important;
                    }
                    .dark .fi-sidebar-item.fi-active .fi-sidebar-item-label {
                        color: #60a5fa !important;
                    }
                    .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                        color: #0F52BA !important;
                    }
                    .dark .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                        color: #60a5fa !important;
                    }

                    /* TOPBAR CLEAN DESIGN */
                    .fi-topbar {
                        background-color: #ffffff !important;
                        border-bottom: 1px solid #e2e8f0 !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
                    }
                    .dark .fi-topbar {
                        background-color: var(--fi-bg-surface) !important;
                        border-bottom-color: var(--fi-border-main) !important;
                        box-shadow: none !important;
                    }
                    .fi-topbar .fi-logo {
                        color: #0F52BA !important;
                        font-weight: 800 !important;
                    }
                    .dark .fi-topbar .fi-logo {
                        color: #60a5fa !important;
                    }

                    /* CLEAN CARDS, SECTIONS, & TABLES */
                    .fi-section,
                    .fi-ta-ctn,
                    .fi-wi-widget > div {
                        border-radius: 14px !important;
                        border: 1px solid #e2e8f0 !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02) !important;
                    }
                    .dark .fi-section,
                    .dark .fi-ta-ctn,
                    .dark .fi-wi-widget > div,
                    .dark .fi-modal-window,
                    .dark .fi-dropdown-panel {
                        border-color: var(--fi-border-main) !important;
                        background-color: var(--fi-bg-surface) !important;
                        box-shadow: none !important;
                    }
                    .dark .fi-ta-header,
                    .dark .fi-ta-header-toolbar,
                    .dark .fi-modal-header,
                    .dark .fi-modal-footer {
                        background-color: var(--fi-bg-surface) !important;
                        border-color: var(--fi-border-main) !important;
                    }
                    .dark .fi-ta-row:hover {
                        background-color: var(--fi-hover-bg) !important;
                    }
                    .dark .fi-input-wrp,
                    .dark .fi-select-input {
                        background-color: var(--fi-bg-input) !important;
                        border-color: var(--fi-border-subtle) !important;
                    }

                    /* BUTTONS & CONTROLS */
                    .fi-btn {
                        border-radius: 10px !important;
                        font-weight: 700 !important;
                        transition: all 0.15s ease !important;
                    }
                    .fi-input-wrp {
                        border-radius: 10px !important;
                    }

                    /* Custom logo override */
                    .fi-logo-custom-img { display: flex; align-items: center; }
                </style>';
                }
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9;">
                        <a href="/" style="font-size: 0.85rem; font-weight: 700; color: #4F46E5; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: opacity 0.2s ease;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">
                            <span>← Kembali ke Halaman Utama</span>
                        </a>
                    </div>
                ')
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => session()->has('impersonated_by') ? \Illuminate\Support\Facades\Blade::render('
                    <div style="background: linear-gradient(90deg, #fef3c7, #fffbeb); border: 1px solid #f59e0b; border-left: 5px solid #d97706; border-radius: 10px; padding: 12px 18px; margin-top: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 22px;">⚠️</span>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #92400e; margin-bottom: 2px;">
                                    Mode Switch Akun: Anda sedang melihat sistem sebagai <u>{{ auth()->user()->name }}</u> ({{ auth()->user()->email }})
                                </div>
                                <div style="font-size: 11px; color: #b45309;">
                                    Data dan menu yang tampil dibatasi sesuai hak akses Area & Prinsiple user ini.
                                </div>
                            </div>
                        </div>
                        <a href="/admin/stop-impersonation" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; background: #d97706; color: #ffffff; font-size: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,0.12); white-space: nowrap; transition: background 0.15s ease;" onmouseover="this.style.background=\'#b45309\'" onmouseout="this.style.background=\'#d97706\'">
                            ✕ Kembali ke Super Admin
                        </a>
                    </div>
                ') : ''
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-right: 0.75rem;">
                        ' . view('filament.partials.theme-switcher')->render() . '
                        <div style="display: flex; flex-direction: column; text-align: right; justify-content: center;">
                            <span style="font-size: 0.875rem; font-weight: 700; line-height: 1.25; color: inherit; margin-bottom: 2px;">{{ auth()->user()->name }}</span>
                            <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; line-height: 1.25;">
                                {{ session()->has("impersonated_by") ? "Switch Mode" : (auth()->user()->roles->first()?->name ?? "User") }}
                            </span>
                        </div>
                    </div>
                ')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.live-notifications-watcher')->render()
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                     ->label('Master Data'),
                \Filament\Navigation\NavigationGroup::make()
                     ->label('Employee Management'),
                \Filament\Navigation\NavigationGroup::make()
                     ->label('Attendance & Time Management'),
                \Filament\Navigation\NavigationGroup::make()
                     ->label('Field Operations & Sales'),
                \Filament\Navigation\NavigationGroup::make()
                     ->label('Communication'),
                \Filament\Navigation\NavigationGroup::make()
                     ->label('Reports & Analytics'),
                \Filament\Navigation\NavigationGroup::make()
                     ->label('System & Settings'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\CheckInstalled::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
