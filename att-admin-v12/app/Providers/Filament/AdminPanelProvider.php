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

        $appName = $setting?->app_name ?? 'AbsensiKu';
        $themeColor = $setting?->theme_color ?? '#0A192F';
        $logoPath = $setting?->logo_path;

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
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
                    /* Custom logo override */
                    .fi-logo-custom-img { display: flex; align-items: center; }
                </style>'
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => session()->has('impersonated_by') ? \Illuminate\Support\Facades\Blade::render('
                    <div style="background: linear-gradient(90deg, #fef3c7, #fffbeb); border: 1px solid #f59e0b; border-left: 5px solid #d97706; border-radius: 8px; padding: 10px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">⚠️</span>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #92400e;">
                                    Mode Switch Akun: Anda sedang melihat sistem sebagai <u>{{ auth()->user()->name }}</u> ({{ auth()->user()->email }})
                                </div>
                                <div style="font-size: 11px; color: #b45309;">
                                    Data dan menu yang tampil dibatasi sesuai hak akses Area & Prinsiple user ini.
                                </div>
                            </div>
                        </div>
                        <a href="/admin/stop-impersonation" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; background: #d97706; color: #ffffff; font-size: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.1); white-space: nowrap;">
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
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
