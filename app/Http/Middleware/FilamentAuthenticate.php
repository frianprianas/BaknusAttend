<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

/**
 * Override Filament's Authenticate middleware agar redirect
 * ke halaman login HTML kita yang sederhana (/login),
 * bukan ke /admin/login milik Filament yang pakai Livewire.
 */
class FilamentAuthenticate extends FilamentAuthenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
