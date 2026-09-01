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

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->darkMode(false)
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->brandName($appName)
            ->brandLogo(function () use ($logoPath): ?string {
                if (!$logoPath) return null;
                try {
                    $disk = \Illuminate\Support\Facades\Storage::disk('public');
                    if ($disk->exists($logoPath)) {
                        $content = $disk->get($logoPath);
                        $mime    = $disk->mimeType($logoPath) ?: 'image/png';
                        return 'data:' . $mime . ';base64,' . base64_encode($content);
                    }
                } catch (\Exception $e) {}
                return null;
            })
            ->brandLogoHeight($logoPath ? '2.5rem' : null)
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
                fn (): string => '<link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    /* GLOBAL OUTFIT TYPOGRAPHY & CLEAN PRINCIPAL PORTAL THEME */
                    * {
                        font-family: \'Plus Jakarta Sans\', \'Outfit\', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                    }
                    
                    /* Background body */
                    .fi-body {
                        background-color: #f8fafc !important;
                    }
                    .dark .fi-body {
                        background-color: #0b1120 !important;
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
                        background-color: #f8fafc !important;
                    }
                    .dark .fi-simple-main {
                        background-color: #ffffff !important;
                        border-color: #e2e8f0 !important;
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
                        background-color: #0f172a !important;
                        border-right-color: #1e293b !important;
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
                        background-color: #0f172a !important;
                        border-bottom-color: #1e293b !important;
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
                        background-color: #1e293b !important;
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
                        background: linear-gradient(135deg, rgba(37, 99, 235, 0.22) 0%, rgba(59, 130, 246, 0.12) 100%) !important;
                        border-left: 3px solid #3b82f6 !important;
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
                        background-color: #0f172a !important;
                        border-bottom-color: #1e293b !important;
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
                    .dark .fi-wi-widget > div {
                        border-color: #1e293b !important;
                        background-color: #0f172a !important;
                        box-shadow: none !important;
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
                </style>'
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
                    <div style="display: flex; flex-direction: column; text-align: right; margin-right: 0.75rem; justify-content: center;">
                        <span style="font-size: 0.875rem; font-weight: 700; line-height: 1.25; color: inherit; margin-bottom: 2px;">{{ auth()->user()->name }}</span>
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; line-height: 1.25;">
                            {{ session()->has("impersonated_by") ? "Switch Mode" : (auth()->user()->roles->first()?->name ?? "User") }}
                        </span>
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
