<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\SchoolSetting;
use App\Models\Student;
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
        $setting = SchoolSetting::first();

        // Konfigurasi dinamis dari Admin Panel
        $showGuru          = $setting ? (bool)($setting->slide_show_guru ?? true) : true;
        $showTu            = $setting ? (bool)($setting->slide_show_tu ?? true) : true;
        $showKelas         = $setting ? (bool)($setting->slide_show_kelas ?? true) : true;
        $minStudents       = $setting && isset($setting->slide_min_students) ? (int)$setting->slide_min_students : 6;
        $slideDuration     = $setting && isset($setting->slide_duration) ? (int)$setting->slide_duration : 6;

        $excludedRolesStr  = $setting->slide_excluded_roles ?? 'Test';
        $excludedRolesArr  = array_filter(array_map('trim', explode(',', $excludedRolesStr)));

        $teacherGrid = [];
        $tuGrid = [];
        $classSlides = [];

        // 1. Dewan Guru (Jika Diaktifkan & Berdasarkan Pilihan Admin)
        if ($showGuru) {
            $selectedTeacherIds = $setting->slide_selected_teacher_ids ?? [];
            if (!is_array($selectedTeacherIds)) {
                $selectedTeacherIds = json_decode($selectedTeacherIds, true) ?? [];
            }

            $teachersQuery = User::where('role', 'Guru')
                ->whereNotIn('role', $excludedRolesArr)
                ->orderBy('name');

            if (!empty($selectedTeacherIds)) {
                $teachersQuery->whereIn('id', $selectedTeacherIds);
            }

            $teachers = $teachersQuery->get();

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
        }

        // 2. Staff TU (Jika Diaktifkan & Berdasarkan Pilihan Admin)
        if ($showTu) {
            $selectedTuIds = $setting->slide_selected_tu_ids ?? [];
            if (!is_array($selectedTuIds)) {
                $selectedTuIds = json_decode($selectedTuIds, true) ?? [];
            }

            $tuQuery = User::where('role', 'TU')
                ->whereNotIn('role', $excludedRolesArr)
                ->orderBy('name');

            if (!empty($selectedTuIds)) {
                $tuQuery->whereIn('id', $selectedTuIds);
            }

            $staffTu = $tuQuery->get();

            $teacherTaps = KehadiranGuruTu::whereDate('waktu_tap', $today)
                ->get()
                ->groupBy('nipy');

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
        }

        // 3. Slide Kelas (Jika Diaktifkan & Berdasarkan Pilihan Admin)
        if ($showKelas) {
            $selectedClassIds = $setting->slide_selected_class_ids ?? [];
            if (!is_array($selectedClassIds)) {
                $selectedClassIds = json_decode($selectedClassIds, true) ?? [];
            }

            $classesQuery = ClassRoom::where('kelas', '!=', 'Belum Ditentukan')
                ->with(['students' => function ($q) {
                    $q->orderBy('name', 'asc');
                }])
                ->orderBy('kelas');

            if (!empty($selectedClassIds)) {
                $classesQuery->whereIn('id', $selectedClassIds);
            }

            $classes = $classesQuery->get()
                ->filter(function ($c) use ($minStudents, $selectedClassIds) {
                    if (!empty($selectedClassIds)) {
                        return true;
                    }
                    return $c->students->count() > $minStudents;
                })
                ->values();

            $allStudentNis = $classes->pluck('students')->flatten()->pluck('nis')->filter();

            $studentTaps = KehadiranSiswa::whereIn('nis', $allStudentNis)
                ->whereDate('waktu_tap', $today)
                ->get()
                ->groupBy('nis');

            $classSlides = $classes->map(function ($classRoom) use ($studentTaps) {
                $studentGrid = $classRoom->students->map(function ($student, $idx) use ($studentTaps) {
                    $taps = $studentTaps->get($student->nis, collect());
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

                    $nisClean = strtolower(trim($student->nis));
                    $studentEmail = $nisClean . '@smk.baktinusantara666.sch.id';
                    $avatarUrl = "https://baknusmail.smkbn666.sch.id/api/auth/avatar/" . urlencode($studentEmail);

                    return [
                        'seat_number' => sprintf('#%02d', $idx + 1),
                        'name'        => $student->name,
                        'code'        => $student->nis,
                        'status_code' => $statusCode,
                        'waktu_tap'   => $waktu,
                        'tap_jam'     => $tapDetails['jam'] ?? null,
                        'tap_metode'  => $tapDetails['metode'] ?? null,
                        'tap_gps'     => $tapDetails['gps'] ?? null,
                        'avatar_url'  => $avatarUrl,
                    ];
                });

                return [
                    'id'           => $classRoom->id,
                    'kelas'        => $classRoom->kelas,
                    'total'        => $studentGrid->count(),
                    'student_grid' => $studentGrid->values()->toArray(),
                ];
            })->values()->toArray();
        }

        return view('auth.login', compact('teacherGrid', 'tuGrid', 'classSlides', 'showGuru', 'showTu', 'showKelas', 'slideDuration'));
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
