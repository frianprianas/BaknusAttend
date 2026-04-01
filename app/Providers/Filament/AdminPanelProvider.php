<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->font('Outfit')
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Zinc,
            ])
            ->favicon(secure_asset('images/logo_BG.png'))
            ->brandName('BaknusAttend')
            ->brandLogo(fn() => secure_asset('images/BA.png'))
            ->brandLogoHeight('3.5rem')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth('full')
            ->breadcrumbs(true)
            ->databaseNotifications()
            ->renderHook(
                'panels::styles.after',
                fn(): string => '<link rel="stylesheet" href="' . asset('css/modern-filament.css') . '">'
            )
            ->renderHook(
                'panels::topbar.start',
                fn(): string => '<div class="flex flex-col justify-center px-2 leading-tight">
                    <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium tracking-wide uppercase">Aplikasi Kehadiran Terintegrasi</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-black">SMK BAKTI NUSANTARA 666</span>
                    <span class="text-[8px] text-indigo-500 font-bold tracking-widest italic">by BaknusAI</span>
                </div>'
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\IzinSakitPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\DashboardStatsWidget::class,
                \App\Filament\Widgets\RecentStudentAttendanceWidget::class,
                \App\Filament\Widgets\RecentGuruAttendanceWidget::class,
                \App\Filament\Widgets\PresensiMandiriWidget::class,
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
                FilamentAuthenticate::class,
            ]);
    }
}
