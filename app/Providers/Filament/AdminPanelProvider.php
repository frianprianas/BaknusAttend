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
            ->brandLogo(fn() => secure_asset('images/logo_BG.png'))
            ->brandLogoHeight('3.5rem')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth('full')
            ->breadcrumbs(true)
            ->databaseNotifications()
            ->renderHook(
                'panels::styles.after',
                fn(): string => '<link rel="stylesheet" href="' . asset('css/modern-filament.css') . '">
                                 <link rel="manifest" href="' . secure_asset('manifest.json') . '">
                                 <link rel="apple-touch-icon" href="' . secure_asset('images/logo_BG.png') . '">
                                 <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
            )
            ->renderHook(
                'panels::scripts.after',
                fn(): string => '<script>
                    function urlBase64ToUint8Array(base64String) {
                        const padding = "=".repeat((4 - base64String.length % 4) % 4);
                        const base64 = (base64String + padding).replace(/\-/g, "+").replace(/_/g, "/");
                        const rawData = window.atob(base64);
                        const outputArray = new Uint8Array(rawData.length);
                        for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
                        return outputArray;
                    }

                    if ("serviceWorker" in navigator && "PushManager" in window) {
                        navigator.serviceWorker.register("' . secure_asset('sw.js') . '").then(function(swReg) {
                            console.log("PWA SW terdaftar!");

                            window.initWebPush = function() {
                                const subscribePush = () => {
                                    swReg.pushManager.getSubscription().then(function(sub) {
                                        if (!sub) {
                                            swReg.pushManager.subscribe({
                                                userVisibleOnly: true,
                                                applicationServerKey: urlBase64ToUint8Array("BIhhWXd5_hBDnjAblgmWRSXiXuGfwEncegv6HCJ9a752kAXkfI1YhV4Ug5RqyLj87uVxZxSxCrrFwonn0U9vTgA")
                                            }).then(res => kirimTokenKeServer(res));
                                        } else {
                                            kirimTokenKeServer(sub);
                                        }
                                    });
                                };

                                if (Notification.permission === "granted") {
                                    subscribePush();
                                } else if (Notification.permission === "default") {
                                    Notification.requestPermission().then(function(permission) {
                                        if (permission === "granted") {
                                            subscribePush();
                                        }
                                    });
                                }
                            };

                            // Jalankan otomatis jika DULU waktu lalu sudah pernah dijawab "Izinkan"
                            // (iOS hanya melarang requestPermission otomatis, tapi kalau sudah diizinkan, getSubscription() boleh)
                            if (Notification.permission === "granted") {
                                window.initWebPush();
                            }
                        });
                    }

                    function kirimTokenKeServer(sub) {
                        fetch("/push/subscribe", {
                            method: "POST",
                            body: JSON.stringify(sub),
                            headers: {
                                "Accept": "application/json",
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "' . csrf_token() . '"
                            }
                        }).then(() => console.log("Token HP Terkirim ke MySQL!"));
                    }
                </script>'
            )
            ->renderHook(
                'panels::topbar.start',
                fn(): string => '<div class="flex flex-col justify-center px-2 leading-tight">
                    <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium tracking-wide uppercase">Aplikasi Kehadiran Terintegrasi</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-black">SMK BAKTI NUSANTARA 666</span>
                    <span class="text-[8px] text-indigo-500 font-bold tracking-widest italic">by BaknusAI</span>
                </div>'
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => '<script>
                    function isIos() {
                        const ua = window.navigator.userAgent.toLowerCase();
                        return /iphone|ipad|ipod/.test(ua);
                    }
                    function isInStandaloneMode() {
                        return ("standalone" in window.navigator) && window.navigator.standalone;
                    }
                    if (isIos() && !isInStandaloneMode()) {
                        const banner = document.createElement("div");
                        banner.innerHTML = `<div style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);width:90%;max-width:400px;background:rgba(15,23,42,0.95);backdrop-filter:blur(10px);color:#fff;padding:16px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.3);z-index:99999;border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:12px;">
                            <div style="font-size:24px;">💡</div>
                            <div style="flex:1;">
                                <p style="margin:0;font-size:0.8rem;font-weight:700;font-family:sans-serif;color:#f8fafc;">Aplikasi Belum Terinstal!</p>
                                <p style="margin:4px 0 0;font-size:0.7rem;line-height:1.4;color:#cbd5e1;font-family:sans-serif;">Ketuk tombol <b style="color:#60a5fa;">Share (Kotak Panah)</b> di bawah, lalu pilih <b style="color:#60a5fa;">"Add to Home Screen"</b> agar notifikasi Absen berfungsi.</p>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" style="background:none;border:none;color:#94a3b8;font-size:1.2rem;cursor:pointer;padding:8px;">&times;</button>
                        </div>`;
                        document.body.appendChild(banner);
                    }

                    // Task: Limit Notification Badge to 9+
                    setInterval(() => {
                        document.querySelectorAll(".fi-topbar-item-badge span, .fi-badge").forEach(el => {
                            let text = el.innerText.trim();
                            if(!text.includes("+") && !isNaN(text)) {
                                let num = parseInt(text);
                                if (num > 9) {
                                    el.innerText = "9+";
                                    // Make sure layout isnt broken by old large numbers
                                    el.style.fontSize = "0.75rem";
                                }
                            }
                        });
                    }, 1000);
                </script>'
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\AttendanceOverview::class,
                \App\Filament\Pages\IzinSakitPage::class,
                \App\Filament\Pages\VideoTimelapse::class,
                \App\Filament\Pages\TimelapseSettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\DashboardStatsWidget::class,
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
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Ganti Foto Profil')
                    ->icon('heroicon-o-camera')
                    ->url('https://baknusmail.smkbn666.sch.id')
                    ->openUrlInNewTab(),
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
            ]);
    }
}
