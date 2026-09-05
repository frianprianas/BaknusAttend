<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BluetoothDevice;
use App\Services\AttendanceVerificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BluetoothAttendanceController extends Controller
{
    protected AttendanceVerificationService $attendanceService;

    public function __construct(AttendanceVerificationService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Request challenge token acak untuk dikirim ke Wemos via Bluetooth BLE
     * GET /api/presence/bluetooth/challenge
     */
    public function getBluetoothChallenge(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Cek apakah user diizinkan absen hari ini (Minggu/Libur/Izin/Selesai)
        $statusInfo = $this->attendanceService->determinePresensiType($user);
        if (!$statusInfo['can_attend']) {
            return response()->json([
                'status'  => 'error',
                'message' => $statusInfo['reason'] ?? 'Absensi tidak diizinkan saat ini.',
                'type'    => $statusInfo['type'],
            ], 422);
        }

        // 2. Generate Nonce/Challenge acak 32 karakter huruf kapital & angka
        $challengeCode = strtoupper(Str::random(32));
        $expiresIn = 60; // 60 detik

        // 3. Simpan ke Cache berdasar user_id selama 60 detik
        Cache::put('ble_challenge_' . $user->id, $challengeCode, now()->addSeconds($expiresIn));

        // Dapatkan nama untuk ditampilkan di LCD Wemos (maks 16 karakter baris pertama)
        $profile = $this->attendanceService->getProfileModel($user);
        $displayName = $profile->name ?? $user->name;

        return response()->json([
            'status'         => 'success',
            'challenge_code' => $challengeCode,
            'expires_in'     => $expiresIn,
            'user_name'      => $displayName,
            'tipe'           => $statusInfo['type'],
        ]);
    }

    /**
     * Alias method untuk getBluetoothChallenge
     */
    public function getChallenge(Request $request): JsonResponse
    {
        return $this->getBluetoothChallenge($request);
    }

    /**
     * Verifikasi signature HMAC-SHA256 dari Wemos dan simpan presensi
     * POST /api/presence/bluetooth/verify
     */
    public function verifyBluetoothAttendance(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Validasi Input Request
        $request->validate([
            'device_id'         => 'required|string',
            'challenge_code'    => 'required|string',
            'signature'         => 'required|string',
            'lat'               => 'required',
            'long'              => 'required',
            'is_dinas_luar'     => 'nullable|boolean',
            'lokasi_dinas_luar' => 'required_if:is_dinas_luar,1,true|nullable|string',
        ], [
            'device_id.required'      => 'Device ID Bluetooth Wemos wajib dikirim.',
            'challenge_code.required' => 'Challenge code wajib dikirim.',
            'signature.required'      => 'Signature HMAC-SHA256 dari Wemos wajib dikirim.',
            'lat.required'            => 'Koordinat Latitude GPS wajib dikirim.',
            'long.required'           => 'Koordinat Longitude GPS wajib dikirim.',
            'lokasi_dinas_luar.required_if' => 'Tempat / Keterangan Dinas Luar wajib diisi jika mode dinas luar diaktifkan.',
        ]);

        $deviceId = trim($request->input('device_id'));
        $clientChallenge = trim($request->input('challenge_code'));
        $clientSignature = trim($request->input('signature'));
        $lat = (float)$request->input('lat');
        $long = (float)$request->input('long');
        $isDinasLuar = $request->boolean('is_dinas_luar');
        $lokasiDinasLuar = $request->input('lokasi_dinas_luar');

        // 2. Cek apakah status presensi hari ini masih bisa absen
        $statusInfo = $this->attendanceService->determinePresensiType($user);
        if (!$statusInfo['can_attend']) {
            return response()->json([
                'status'  => 'error',
                'message' => $statusInfo['reason'] ?? 'Absensi tidak diizinkan saat ini.',
                'type'    => $statusInfo['type'],
            ], 422);
        }

        $tipeAbsens = $statusInfo['type']; // 'Masuk' atau 'Pulang'

        // 3. Verifikasi Challenge Code di Cache (Anti-Replay Attack)
        $cacheKey = 'ble_challenge_' . $user->id;
        $storedChallenge = Cache::get($cacheKey);

        if (empty($storedChallenge) || $storedChallenge !== $clientChallenge) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token keamanan Bluetooth kadaluwarsa atau tidak valid. Silakan coba lagi.',
            ], 422);
        }

        // 4. Ambil data perangkat Wemos
        $device = BluetoothDevice::where('device_id', $deviceId)->active()->first();

        if (!$device) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Perangkat Bluetooth Wemos tidak terdaftar di sistem.',
            ], 404);
        }

        // 5. Verifikasi Signature Kriptografi (HMAC-SHA256)
        $expectedSignature = hash_hmac('sha256', $clientChallenge, $device->secret_key);

        if (!hash_equals(strtolower($expectedSignature), strtolower($clientSignature))) {
            Log::warning("BLE Signature Mismatch - User ID: {$user->id}, Device: {$deviceId}, Expected: {$expectedSignature}, Received: {$clientSignature}");

            return response()->json([
                'status'  => 'error',
                'message' => 'Signature perangkat Wemos tidak valid / sinyal palsu.',
            ], 403);
        }

        // 6. Validasi Geofencing GPS terhadap koordinat alat (jika alat memiliki GPS terdaftar)
        if (!$isDinasLuar && !empty($device->latitude) && !empty($device->longitude)) {
            $distance = $this->attendanceService->haversineGreatCircleDistance(
                $lat,
                $long,
                $device->latitude,
                $device->longitude
            );

            $allowedRadius = (float)($device->radius_meters ?: 50);

            if ($distance > $allowedRadius) {
                return response()->json([
                    'status'         => 'error',
                    'message'        => "Anda berada di luar jangkauan perangkat {$device->device_name} (" . round($distance) . "m dari alat, batas toleransi {$allowedRadius}m).",
                    'distance'       => round($distance, 1),
                    'allowed_radius' => $allowedRadius,
                ], 422);
            }
        }

        // 7. Hapus token dari Cache (Anti-Replay Attack)
        Cache::forget($cacheKey);

        $currentTime = Carbon::now();

        // 8. Simpan Presensi ke Database
        $record = $this->attendanceService->recordBluetoothAttendance(
            $user,
            $tipeAbsens,
            $currentTime,
            $lat,
            $long,
            $device->device_name,
            $isDinasLuar,
            $lokasiDinasLuar
        );

        return response()->json([
            'status'  => 'success',
            'message' => "Presensi {$tipeAbsens} (Bluetooth BLE) Berhasil!",
            'data'    => [
                'id'                 => $record->id,
                'tipe'               => $tipeAbsens,
                'status_kehadiran'   => $record->status,
                'device_id'          => $device->device_id,
                'device_name'        => $device->device_name,
                'waktu'              => $currentTime->format('Y-m-d H:i:s'),
                'jam'                => $currentTime->format('H:i'),
            ],
        ]);
    }

    /**
     * Alias method untuk verifyBluetoothAttendance
     */
    public function verifyAndSubmit(Request $request): JsonResponse
    {
        return $this->verifyBluetoothAttendance($request);
    }
}
