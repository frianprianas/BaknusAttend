<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\Auth\LoginResponse::class
        );
    }

    public function boot(): void
    {
        if (str_contains(config('app.url'), 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Auth::provider('mailcow', function ($app, array $config) {
            return new \App\Providers\MailcowUserProvider($app['hash'], $config['model']);
        });
    }
}
