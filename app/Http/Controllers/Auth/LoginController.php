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

        $today = \Carbon\Carbon::today();

        // 1. Dewan Guru
        $teachers = User::where('role', 'Guru')
            ->orderBy('name')
            ->get();

        $teacherTaps = \App\Models\KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->get()
            ->groupBy('nipy');

        $teacherGrid = $teachers->map(function ($user, $idx) use ($teacherTaps) {
            $nipyKey = $user->nipy ?: $user->email;
            $taps = $teacherTaps->get($nipyKey, $teacherTaps->get($user->email, collect()));
            $firstTap = $taps->sortBy('waktu_tap')->first();
            
            $statusCode = 'BELUM';
            $waktu = '-';
            if ($firstTap) {
                $statusStr = strtolower($firstTap->status . ' ' . $firstTap->keterangan);
                $waktu = \Carbon\Carbon::parse($firstTap->waktu_tap)->format('H:i');
                if (str_contains($statusStr, 'terlambat')) {
                    $statusCode = 'TERLAMBAT';
                } elseif (str_contains($statusStr, 'izin') || str_contains($statusStr, 'sakit')) {
                    $statusCode = 'IZIN';
                } else {
                    $statusCode = 'HADIR';
                }
            }

            $photoUrl = null;
            if (!empty($user->face_reference)) {
                $photoUrl = asset('storage/' . $user->face_reference);
            }

            return [
                'seat_number' => sprintf('#G%02d', $idx + 1),
                'name'        => $user->name,
                'code'        => $user->nipy ?? 'GURU',
                'status_code' => $statusCode,
                'waktu_tap'   => $waktu,
                'photo_url'   => $photoUrl,
            ];
        });

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
            if ($firstTap) {
                $statusStr = strtolower($firstTap->status . ' ' . $firstTap->keterangan);
                $waktu = \Carbon\Carbon::parse($firstTap->waktu_tap)->format('H:i');
                if (str_contains($statusStr, 'terlambat')) {
                    $statusCode = 'TERLAMBAT';
                } elseif (str_contains($statusStr, 'izin') || str_contains($statusStr, 'sakit')) {
                    $statusCode = 'IZIN';
                } else {
                    $statusCode = 'HADIR';
                }
            }

            $photoUrl = null;
            if (!empty($user->face_reference)) {
                $photoUrl = asset('storage/' . $user->face_reference);
            }

            return [
                'seat_number' => sprintf('#T%02d', $idx + 1),
                'name'        => $user->name,
                'code'        => $user->nipy ?? 'TU',
                'status_code' => $statusCode,
                'waktu_tap'   => $waktu,
                'photo_url'   => $photoUrl,
            ];
        });

        // 3. Kelas yang siswanya LEBIH DARI 6 ORANG saja
        $classes = \App\Models\ClassRoom::where('kelas', '!=', 'Belum Ditentukan')
            ->has('students', '>', 6)
            ->with(['students' => function ($q) {
                $q->orderBy('name', 'asc');
            }])
            ->orderBy('kelas')
            ->get();

        $allStudentNis = $classes->pluck('students')->flatten()->pluck('nis')->filter();

        $studentTaps = \App\Models\KehadiranSiswa::whereIn('nis', $allStudentNis)
            ->whereDate('waktu_tap', $today)
            ->get()
            ->groupBy('nis');

        $classSlides = $classes->map(function ($classRoom) use ($studentTaps) {
            $studentGrid = $classRoom->students->map(function ($student, $idx) use ($studentTaps) {
                $taps = $studentTaps->get($student->nis, collect());
                $firstTap = $taps->sortBy('waktu_tap')->first();

                $statusCode = 'BELUM';
                $waktu = '-';
                if ($firstTap) {
                    $statusStr = strtolower($firstTap->status . ' ' . $firstTap->keterangan);
                    $waktu = \Carbon\Carbon::parse($firstTap->waktu_tap)->format('H:i');
                    if (str_contains($statusStr, 'terlambat')) {
                        $statusCode = 'TERLAMBAT';
                    } elseif (str_contains($statusStr, 'izin') || str_contains($statusStr, 'sakit')) {
                        $statusCode = 'IZIN';
                    } else {
                        $statusCode = 'HADIR';
                    }
                }

                $photoUrl = null;
                if (!empty($student->face_reference)) {
                    $photoUrl = asset('storage/' . $student->face_reference);
                }

                return [
                    'seat_number' => sprintf('#%02d', $idx + 1),
                    'name'        => $student->name,
                    'code'        => $student->nis,
                    'status_code' => $statusCode,
                    'waktu_tap'   => $waktu,
                    'photo_url'   => $photoUrl,
                ];
            });

            return [
                'id'           => $classRoom->id,
                'kelas'        => $classRoom->kelas,
                'total'        => $studentGrid->count(),
                'student_grid' => $studentGrid,
            ];
        });

        return view('auth.login', compact('teacherGrid', 'tuGrid', 'classSlides'));
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
