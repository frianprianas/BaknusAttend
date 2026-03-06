<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailcowAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/admin');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // Cari user di database
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun tidak ditemukan di sistem.']);
        }

        // Cek via IMAP Mailcow
        $authenticated = false;
        try {
            $authenticated = MailcowAuth::check($email, $password);
        } catch (\Exception $e) {
            Log::error('Login IMAP error', ['email' => $email, 'msg' => $e->getMessage()]);
        }

        // Fallback: cek password lokal (jika IMAP timeout)
        if (!$authenticated) {
            $authenticated = Hash::check($password, $user->password);
        }

        if (!$authenticated) {
            Log::warning('Login gagal', ['email' => $email]);
            return back()->withInput($request->only('email'))
                ->withErrors(['password' => 'Email atau password salah.']);
        }

        // Cache password agar fallback bekerja
        if (!Hash::check($password, $user->password)) {
            $user->password = Hash::make($password);
            $user->save();
        }

        // Login
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        Log::info('Login berhasil', ['email' => $email, 'role' => $user->role]);

        return redirect()->intended('/admin');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
