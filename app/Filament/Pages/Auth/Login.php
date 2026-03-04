<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    protected static string $view = 'login'; // Use our custom view

    public function render(): \Illuminate\Contracts\View\View
    {
        return view(static::$view)
            ->layout('filament-panels::components.layout.base');
    }
}
