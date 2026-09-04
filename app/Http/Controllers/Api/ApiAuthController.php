<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceVerificationService;
use App\Services\MailcowAuth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ApiAuthController extends Controller
{
    protected AttendanceVerificationService $attendanceService;

    public function __construct(AttendanceVerificationService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Login via Email / NIS / NIPY dan Password
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required_without:email|string',
            'email'    => 'required_without:username|string',
            'password' => 'required|string',
        ], [
            'username.required_without' => 'Username, NIS, NIPY, atau Email wajib diisi.',
            'email.required_without'    => 'Username, NIS, NIPY, atau Email wajib diisi.',
            'password.required'         => 'Password wajib diisi.',
        ]);

        $input = trim($request->input('username') ?? $request->input('email'));
        $password = $request->input('password');

        // Cari user berdasarkan email, nipy, atau format email siswa (nis@smk.baktinusantara666.sch.id)
        $user = User::where('email', $input)
            ->orWhere('nipy', $input)
            ->first();

        if (!$user && !str_contains($input, '@')) {
            $candidateEmail = $input . '@smk.baktinusantara666.sch.id';
            $user = User::where('email', $candidateEmail)->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak ditemukan pada sistem BaknusAttend.',
            ], 404);
        }

        $email = $user->email;
        $authenticated = false;

        // 1. Verifikasi via Mailcow IMAP
        try {
            $authenticated = MailcowAuth::check($email, $password);
        } catch (\Throwable $e) {
            Log::error('API Login IMAP error', ['email' => $email, 'error' => $e->getMessage()]);
        }

        // 2. Fallback: Verifikasi via Hash lokal database
        if (!$authenticated) {
            $authenticated = Hash::check($password, $user->password);
        }

        if (!$authenticated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah atau akun email tidak valid.',
            ], 401);
        }

        // Sinkronisasi cache password lokal jika berhasil login via IMAP
        if (!Hash::check($password, $user->password)) {
            $user->password = Hash::make($password);
            $user->save();
        }

        // Buat Stateless Bearer Token (Berlaku 30 Hari)
        $expiresAt = Carbon::now()->addDays(30)->timestamp;
        $token = Crypt::encryptString(json_encode([
            'user_id'    => $user->id,
            'role'       => $user->role,
            'created_at' => Carbon::now()->timestamp,
            'expires_at' => $expiresAt,
        ]));

        $userData = $this->formatUserData($user);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Login berhasil.',
            'token'      => $token,
            'expires_at' => Carbon::createFromTimestamp($expiresAt)->toIso8601String(),
            'user'       => $userData,
        ]);
    }

    /**
     * Ambil profil user saat ini
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'status' => 'success',
            'user'   => $this->formatUserData($user),
        ]);
    }

    private function formatUserData(User $user): array
    {
        $identifier = $user->nipy ?? $user->email;
        $className = null;
        $hasMaster = false;
        $masterPhotoUrl = null;

        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            if (str_contains($nis, '@')) {
                $nis = explode('@', $nis)[0];
            }
            $student = Student::with('classRoom')->where('nis', $nis)->first();
            if ($student) {
                $identifier = $student->nis;
                $className = $student->classRoom?->kelas ?? 'Kelas Tidak Terdaftar';
                $hasMaster = !empty($student->face_reference);
                if ($hasMaster) {
                    $masterPhotoUrl = url('storage/' . ltrim(str_replace('public/', '', $student->face_reference), '/'));
                }
            }
        } else {
            $hasMaster = !empty($user->face_reference);
            if ($hasMaster) {
                $masterPhotoUrl = url('storage/' . ltrim(str_replace('public/', '', $user->face_reference), '/'));
            }
        }

        return [
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'identifier'       => (string)$identifier,
            'role'             => $user->role,
            'class'            => $className,
            'avatar_url'       => $user->avatar_url,
            'has_face_master'  => $hasMaster,
            'master_photo_url' => $masterPhotoUrl,
        ];
    }
}
