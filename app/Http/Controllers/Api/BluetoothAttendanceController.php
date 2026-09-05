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
    public function getChallenge(Request $request): JsonResponse
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

        // 2. Generate Nonce/Challenge acak 32 karakter hexadecimal (16 bytes)
        $challengeCode = strtoupper(bin2hex(random_bytes(16)));
        $expiresIn = 60; // 60 detik

        // 3. Simpan ke Cache berdasar user_id
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
     * Verifikasi signature HMAC-SHA256 dari Wemos dan simpan presensi
     * POST /api/presence/bluetooth/verify
     */
    public function verifyAndSubmit(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Validasi Input Request
        $request->validate([
            'device_id'         => 'required|string',
            'challenge_code'    => 'required|string',
            'signature'         => 'required|string',
            'lat'               => 'nullable|numeric',
            'long'              => 'nullable|numeric',
            'is_dinas_luar'     => 'nullable|boolean',
            'lokasi_dinas_luar' => 'required_if:is_dinas_luar,1,true|nullable|string',
        ], [
            'device_id.required'      => 'Device ID Bluetooth Wemos wajib dikirim.',
            'challenge_code.required' => 'Challenge code wajib dikirim.',
            'signature.required'      => 'Signature HMAC-SHA256 dari Wemos wajib dikirim.',
            'lokasi_dinas_luar.required_if' => 'Tempat / Keterangan Dinas Luar wajib diisi jika mode dinas luar diaktifkan.',
        ]);

        $deviceId = trim($request->input('device_id'));
        $clientChallenge = trim($request->input('challenge_code'));
        $clientSignature = trim($request->input('signature'));
        $lat = $request->filled('lat') ? (float)$request->input('lat') : null;
        $long = $request->filled('long') ? (float)$request->input('long') : null;
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
                'message' => 'Kode challenge tidak valid atau telah kedaluwarsa (maks 60 detik). Silakan minta challenge baru.',
            ], 422);
        }

        // 4. Cari Perangkat Bluetooth Wemos
        $device = BluetoothDevice::where('device_id', $deviceId)->active()->first();

        if (!$device) {
            return response()->json([
                'status'  => 'error',
                'message' => "Perangkat Bluetooth dengan ID '{$deviceId}' tidak terdaftar atau sedang dinonaktifkan.",
            ], 404);
        }

        // 5. Hitung Ulang Signature HMAC-SHA256 di Server
        $expectedSignature = hash_hmac('sha256', $storedChallenge, $device->secret_key);

        if (!hash_equals(strtolower($expectedSignature), strtolower($clientSignature))) {
            Log::warning("BLE Signature Mismatch - User ID: {$user->id}, Device: {$deviceId}, Expected: {$expectedSignature}, Received: {$clientSignature}");

            return response()->json([
                'status'  => 'error',
                'message' => 'Signature Bluetooth tidak valid! Sinyal palsu atau secret key alat tidak cocok.',
            ], 422);
        }

        // 6. Validasi Geofencing GPS terhadap koordinat alat (jika alat memiliki GPS terdaftar)
        if (!$isDinasLuar && !empty($device->latitude) && !empty($device->longitude)) {
            if ($lat === null || $long === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Koordinat GPS ponsel diperlukan untuk memvalidasi radius perangkat.',
                ], 422);
            }

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

        // 7. Hapus challenge dari cache setelah berhasil (Anti-Replay Attack)
        Cache::forget($cacheKey);

        $currentTime = Carbon::now();

        // 8. Simpan Record Kehadiran ke Database
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
                'is_dinas_luar'      => (bool)$record->is_dinas_luar,
                'lokasi_dinas_luar'  => $record->lokasi_dinas_luar,
            ],
        ]);
    }
}
