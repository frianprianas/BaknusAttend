<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        // Custom Flash Message based on Role
        $roleName = $user->role ?? 'Staf';
        session()->flash('notification', [
            'message' => "Selamat Datang Kembali, {$roleName}!",
            'type' => 'success'
        ]);

        // All users use the /admin path in this setup (to keep it "tanpa ribet")
        // but we will filter their view based on role in the resources.
        return redirect()->intended('/admin');
    }
}
