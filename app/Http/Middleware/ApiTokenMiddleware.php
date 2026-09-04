<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->input('api_token') ?? $request->header('X-API-Token');

        if (empty($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token otentikasi tidak ditemukan. Harap sertakan Authorization: Bearer <token>',
            ], 401);
        }

        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);

            if (!is_array($payload) || empty($payload['user_id'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Format token tidak valid.',
                ], 401);
            }

            // Cek masa berlaku token
            if (isset($payload['expires_at']) && Carbon\Carbon::createFromTimestamp($payload['expires_at'])->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi token telah kedaluwarsa. Silakan login kembali.',
                ], 401);
            }

            $user = User::find($payload['user_id']);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun pengguna tidak ditemukan.',
                ], 401);
            }

            // Bind user ke request dan Auth facade
            $request->setUserResolver(fn() => $user);
            Auth::setUser($user);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau telah rusak: ' . $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
