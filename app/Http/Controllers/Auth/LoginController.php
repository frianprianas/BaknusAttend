<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuruTu;
use App\Models\User;
use App\Services\MailcowAuth;
use Carbon\Carbon;
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

        $today = Carbon::today();

        // 1. Dewan Guru
        $teachers = User::where('role', 'Guru')
            ->orderBy('name')
            ->get();

        $teacherTaps = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->get()
            ->groupBy('nipy');

        $teacherGrid = $teachers->map(function ($user, $idx) use ($teacherTaps) {
            $nipyKey = $user->nipy ?: $user->email;
            $taps = $teacherTaps->get($nipyKey, $teacherTaps->get($user->email, collect()));
            $firstTap = $taps->sortBy('waktu_tap')->first();
            
            $statusCode = 'BELUM';
            $waktu = '-';
            $tapDetails = null;

            if ($firstTap) {
                $statusStr = strtolower($firstTap->status . ' ' . $firstTap->keterangan);
                $waktu = Carbon::parse($firstTap->waktu_tap)->format('H:i');
                if (str_contains($statusStr, 'terlambat')) {
                    $statusCode = 'TERLAMBAT';
                } elseif (str_contains($statusStr, 'izin') || str_contains($statusStr, 'sakit')) {
                    $statusCode = 'IZIN';
                } else {
                    $statusCode = 'HADIR';
                }
                $tapDetails = $this->parseTapDetails($firstTap);
            }

            // BaknusMail Avatar Endpoint API
            $emailClean = strtolower(trim($user->email));
            $avatarUrl = "https://baknusmail.smkbn666.sch.id/api/auth/avatar/" . urlencode($emailClean);

            return [
                'seat_number' => sprintf('#G%02d', $idx + 1),
                'name'        => $user->name,
                'code'        => 'Guru',
                'email'       => $user->email,
                'status_code' => $statusCode,
                'waktu_tap'   => $waktu,
                'tap_jam'     => $tapDetails['jam'] ?? null,
                'tap_metode'  => $tapDetails['metode'] ?? null,
                'tap_gps'     => $tapDetails['gps'] ?? null,
                'avatar_url'  => $avatarUrl,
            ];
        })->values()->toArray();

        // 2. Staff TU
        $staffTu = User::where('role', 'TU')
            ->orderBy('name')
            ->get();

        $tuGrid = $staffTu->map(function ($user, $idx) use ($teacherTaps) {
            $nipyKey = $user->nipy ?: $user->email;
            $taps = $teacherTaps->get($nipyKey, $teacherTaps->get($user->email, collect()));
            $firstTap = $taps->sortBy('waktu_tap')->first();
            
            $statusCode = 'BELUM';
            $waktu = '-';
            $tapDetails = null;

            if ($firstTap) {
                $statusStr = strtolower($firstTap->status . ' ' . $firstTap->keterangan);
                $waktu = Carbon::parse($firstTap->waktu_tap)->format('H:i');
                if (str_contains($statusStr, 'terlambat')) {
                    $statusCode = 'TERLAMBAT';
                } elseif (str_contains($statusStr, 'izin') || str_contains($statusStr, 'sakit')) {
                    $statusCode = 'IZIN';
                } else {
                    $statusCode = 'HADIR';
                }
                $tapDetails = $this->parseTapDetails($firstTap);
            }

            $emailClean = strtolower(trim($user->email));
            $avatarUrl = "https://baknusmail.smkbn666.sch.id/api/auth/avatar/" . urlencode($emailClean);

            return [
                'seat_number' => sprintf('#T%02d', $idx + 1),
                'name'        => $user->name,
                'code'        => 'Staff TU',
                'email'       => $user->email,
                'status_code' => $statusCode,
                'waktu_tap'   => $waktu,
                'tap_jam'     => $tapDetails['jam'] ?? null,
                'tap_metode'  => $tapDetails['metode'] ?? null,
                'tap_gps'     => $tapDetails['gps'] ?? null,
                'avatar_url'  => $avatarUrl,
            ];
        })->values()->toArray();

        // Kelas dinonaktifkan sementara sesuai permintaan user
        $classSlides = [];

        return view('auth.login', compact('teacherGrid', 'tuGrid', 'classSlides'));
    }

    private function parseTapDetails($tapRecord): array
    {
        if (!$tapRecord) {
            return ['jam' => '-', 'metode' => '⚪ Belum', 'gps' => null];
        }

        $jam = Carbon::parse($tapRecord->waktu_tap)->format('H:i:s');
        $ketLower = strtolower($tapRecord->keterangan ?? '');

        // Tentukan Metode Presensi
        $metode = '💳 RFID';
        if (!empty($tapRecord->lat) && !empty($tapRecord->long)) {
            $metode = '📍 GPS HP';
        } elseif (!empty($tapRecord->is_dinas_luar)) {
            $metode = '🚗 Dinas Luar';
        } elseif (str_contains($ketLower, 'wajah') || str_contains($ketLower, 'face') || str_contains($ketLower, 'foto')) {
            $metode = '👤 Face AI';
        } elseif (str_contains($ketLower, 'manual') || str_contains($ketLower, 'wali')) {
            $metode = '✍️ Manual';
        }

        // Tentukan GPS / Koordinat Lokasi
        $gps = null;
        if (!empty($tapRecord->lat) && !empty($tapRecord->long)) {
            $gps = round((float)$tapRecord->lat, 4) . ', ' . round((float)$tapRecord->long, 4);
        } elseif (!empty($tapRecord->lokasi_dinas_luar)) {
            $gps = $tapRecord->lokasi_dinas_luar;
        }

        return [
            'jam'    => $jam,
            'metode' => $metode,
            'gps'    => $gps,
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email atau Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $email = trim($request->input('email'));
        if (!str_contains($email, '@')) {
            $email .= '@smk.baktinusantara666.sch.id';
        }
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
