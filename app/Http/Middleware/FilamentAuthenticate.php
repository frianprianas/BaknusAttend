<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as BaseAuthenticate;

/**
 * Override Filament's Authenticate middleware agar redirect
 * ke halaman login HTML kita yang sederhana (/login).
 */
class CustomAuthenticate extends BaseAuthenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
