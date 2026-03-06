<?php

namespace App\Http\Middleware;

// Alias "BaseAuthenticate" agar tidak konflik dengan nama class di bawah
use Filament\Http\Middleware\Authenticate as BaseAuthenticate;

/**
 * Override Filament Authenticate middleware → redirect ke /login HTML kita.
 * PENTING: Nama class HARUS sama dengan nama file (FilamentAuthenticate.php)
 */
class FilamentAuthenticate extends BaseAuthenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
