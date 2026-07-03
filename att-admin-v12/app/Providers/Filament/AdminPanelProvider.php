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

        $appName = $setting?->app_name ?? 'AbsensiKu';
        $themeColor = $setting?->theme_color ?? '#0A192F';

        if ($setting && $setting->logo_path) {
            $panel->brandLogo(asset('storage/' . $setting->logo_path));
            $panel->brandLogoHeight('2rem');
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->brandName($appName)
            ->font('Public Sans')
            ->colors([
                'primary' => $themeColor,
                'danger' => '#EA5455',
                'success' => '#28C76F',
                'warning' => '#FF9F43',
                'info' => '#00CFE8',
                'gray' => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>
                    aside.fi-sidebar {
                        background-color: ' . $themeColor . ' !important;
                    }
                    .fi-sidebar-header {
                        background-color: ' . $themeColor . ' !important;
                        border-bottom: 1px solid rgba(255,255,255,0.1);
                    }
                    .fi-sidebar .fi-sidebar-item-label, .fi-sidebar .fi-sidebar-item-icon, .fi-sidebar-group-label {
                        color: #cbd5e1 !important;
                    }
                    .fi-sidebar .fi-sidebar-item.fi-active > a, 
                    .fi-sidebar .fi-sidebar-item.fi-active > button {
                        background-color: rgba(255, 255, 255, 0.15) !important;
                    }
                    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label, 
                    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                        color: #ffffff !important;
                        font-weight: 600 !important;
                    }
                    /* Logo in sidebar header */
                    .fi-sidebar-header .fi-logo {
                        color: #ffffff !important;
                    }
                    /* Logo in topbar */
                    .fi-topbar .fi-logo {
                        color: ' . $themeColor . ' !important;
                    }
                    .dark .fi-topbar .fi-logo {
                        color: #ffffff !important;
                    }
                </style>'
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <div style="display: flex; flex-direction: column; text-align: right; margin-right: 0.75rem; justify-content: center;">
                        <span style="font-size: 0.875rem; font-weight: 700; line-height: 1.25; color: inherit; margin-bottom: 2px;">{{ auth()->user()->name }}</span>
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; line-height: 1.25;">Super Admin</span>
                    </div>
                ')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
