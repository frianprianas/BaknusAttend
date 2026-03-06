<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Halaman login Filament hanya berfungsi sebagai jembatan:
 * Langsung forward user ke halaman login HTML mandiri kita.
 * Ini menghindari semua kompleksitas Livewire form submission.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        // Jika sudah login, langsung ke dashboard
        if (auth()->check()) {
            $this->redirect('/admin');
            return;
        }

        // Redirect ke halaman login HTML biasa kita
        redirect()->to(route('login'))->send();
        exit;
    }
}
