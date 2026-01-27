<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Admin\Pages\Auth\Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->font('Poppins')
            ->brandName('Istana Top Up')
            ->brandLogo(asset('/assets/logo/logo.webp'))
            ->favicon(asset('/assets/logo/favicon.webp'))
            ->colors([
                'primary' => '#2563EB', // Bright Blue (Dashboard Button)
                'secondary' => '#64748B', // Slate
                'success' => '#10B981',
                'warning' => '#F59E0B',
                'danger' => '#EF4444',
                'gray' => [
                    50 => '#f1f5f9',
                    100 => '#e2e8f0',
                    200 => '#cbd5e1',
                    300 => '#94a3b8',
                    400 => '#64748b',
                    500 => '#475569',
                    600 => '#334155',
                    700 => '#1e293b', // Card lighter
                    800 => '#0f172a', // Sidebar/Card darker
                    900 => '#020617', // Main Background
                    950 => '#020617', // Deepest
                ],
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                \App\Filament\Admin\Widgets\UserStatsOverview::class,
                \App\Filament\Admin\Widgets\ProductMetrics::class,
                \App\Filament\Admin\Widgets\RecentActivities::class,
                \App\Filament\Admin\Widgets\UserTierChart::class,
            ])
            ->navigationGroups([
                'Products',
                'Manajemen Transaksi',
                'User Management',
                'Configuration',
                'Reports',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                'check.role',
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.admin.custom-head'),
            );
    }
}
