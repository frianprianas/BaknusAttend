<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Placeholder — redirect ke halaman login HTML mandiri.
 * Actual login ditangani di /login (LoginController).
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect('/admin');
            return;
        }

        // Gunakan Livewire redirect (bukan PHP exit)
        $this->redirect(route('login'));
    }
}
