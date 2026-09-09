<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStaffRole
{
    /**
     * Handle an incoming request.
     * Hanya pengguna dengan role Guru, TU, atau Admin yang diizinkan.
     * Jika role Siswa atau selain staff, kembalikan HTTP 403 Forbidden.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token otentikasi tidak ditemukan atau sesi telah berakhir.',
            ], 401);
        }

        $role = strtolower(trim($user->role ?? ''));
        $allowedRoles = ['guru', 'tu', 'admin', 'administrator', 'kepsek', 'kepala sekolah'];

        if (!in_array($role, $allowedRoles, true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Data kehadiran kelas hanya dapat diakses oleh Guru dan Staf TU.',
            ], 403);
        }

        return $next($request);
    }
}
