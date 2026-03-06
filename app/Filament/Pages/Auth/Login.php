<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\MailcowAuth;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'login';

    public function render(): \Illuminate\Contracts\View\View
    {
        return view(static::$view)
            ->layout('filament-panels::components.layout.base');
    }

    /**
     * Override authenticate untuk menangani login langsung via Mailcow IMAP
     * tanpa melalui jalur provider yang kompleks.
     */
    public function authenticate(): ?LoginResponse
    {
        // 1. Ambil data form (email & password)
        $data = $this->form->getState();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            $this->throwFailure('Email dan password tidak boleh kosong.');
            return null;
        }

        // 2. Cari user di database lokal
        $user = User::where('email', $email)->first();

        // 3. Jika user belum ada, coba sync dari Mailcow (JIT Provisioning)
        if (!$user) {
            try {
                $mailcowService = app(\App\Services\MailcowService::class);
                $user = $mailcowService->syncSingleUser($email);
            } catch (\Exception $e) {
                Log::error('Login: Gagal sync user dari Mailcow', ['email' => $email, 'error' => $e->getMessage()]);
            }

            if (!$user) {
                $this->throwFailure('Akun tidak ditemukan. Pastikan email Mailcow Anda terdaftar.');
                return null;
            }
        }

        // 4. Verifikasi password via Mailcow IMAP
        $imapOk = false;
        try {
            $imapOk = MailcowAuth::check($email, $password);
        } catch (\Exception $e) {
            Log::error('Login: Error saat IMAP check', ['email' => $email, 'error' => $e->getMessage()]);
        }

        // 5. Jika IMAP gagal, coba password lokal sebagai fallback
        if (!$imapOk) {
            if (!Hash::check($password, $user->password)) {
                Log::warning('Login: Autentikasi gagal (IMAP + local)', ['email' => $email]);
                $this->throwFailure('Email atau password salah. Pastikan Anda menggunakan password Mailcow sekolah.');
                return null;
            }
        }

        // 6. Login berhasil - update password lokal untuk cache
        if ($imapOk) {
            $user->password = Hash::make($password);
            $user->save();
        }

        // 7. Login ke session Laravel
        Auth::guard(Filament::getAuthGuard())->login($user, $data['remember'] ?? false);

        // 8. Redirect berdasarkan role
        session()->flash('status', 'Selamat datang, ' . $user->name . '!');

        $this->redirect('/admin');

        return null;
    }

    /**
     * Helper: lempar ValidationException dengan pesan error yang user-friendly.
     */
    private function throwFailure(string $message): void
    {
        throw ValidationException::withMessages([
            'data.email' => $message,
        ]);
    }
}
