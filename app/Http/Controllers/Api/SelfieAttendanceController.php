<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceVerificationService;
use App\Services\CompreFaceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class SelfieAttendanceController extends Controller
{
    protected AttendanceVerificationService $attendanceService;
    protected CompreFaceService $compreFaceService;

    public function __construct(AttendanceVerificationService $attendanceService, CompreFaceService $compreFaceService)
    {
        $this->attendanceService = $attendanceService;
        $this->compreFaceService = $compreFaceService;
    }

    /**
     * Dapatkan status presensi hari ini, geofencing sekolah, dan riwayat tap
     * GET /api/presence/status
     */
    public function getTodayStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $setting = SchoolSetting::first();

        $statusInfo = $this->attendanceService->determinePresensiType($user, $today);

        // Ambil info master photo
        $profile = $this->attendanceService->getProfileModel($user);
        $hasMaster = !empty($profile->face_reference);
        $masterPhotoUrl = $hasMaster 
            ? url('storage/' . ltrim(str_replace('public/', '', $profile->face_reference), '/'))
            : null;

        // Ambil riwayat kehadiran hari ini
        $todayRecords = [];
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            if (str_contains($nis, '@')) {
                $nis = explode('@', $nis)[0];
            }
            $records = KehadiranSiswa::where('nis', $nis)
                ->whereDate('waktu_tap', $today)
                ->orderBy('waktu_tap', 'asc')
                ->get();
        } else {
            $records = KehadiranGuruTu::where(function ($q) use ($user) {
                if (!empty($user->nipy)) $q->where('nipy', $user->nipy);
                if (!empty($user->email)) $q->orWhere('nipy', $user->email);
            })
            ->whereDate('waktu_tap', $today)
            ->orderBy('waktu_tap', 'asc')
            ->get();
        }

        foreach ($records as $rec) {
            $photoUrl = null;
            if (!empty($rec->photo) && $rec->photo !== 'rfid_placeholder') {
                $photoUrl = url('storage/' . ltrim(str_replace('public/', '', $rec->photo), '/'));
            }

            $todayRecords[] = [
                'id'                => $rec->id,
                'waktu_tap'         => Carbon::parse($rec->waktu_tap)->format('Y-m-d H:i:s'),
                'jam'               => Carbon::parse($rec->waktu_tap)->format('H:i'),
                'status'            => $rec->status,
                'keterangan'        => $rec->keterangan,
                'lat'               => $rec->lat ? (float)$rec->lat : null,
                'long'              => $rec->long ? (float)$rec->long : null,
                'is_dinas_luar'     => (bool)$rec->is_dinas_luar,
                'lokasi_dinas_luar' => $rec->lokasi_dinas_luar,
                'photo_url'         => $photoUrl,
            ];
        }

        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'date'             => $today->format('Y-m-d'),
                'day_name'         => $days[$today->format('l')] ?? $today->format('l'),
                'presensi_type'    => $statusInfo['type'], // 'Masuk' | 'Pulang' | 'Selesai' | 'Libur' | 'Izin'
                'can_attend'       => $statusInfo['can_attend'],
                'reason'           => $statusInfo['reason'],
                'has_face_master'  => $hasMaster,
                'master_photo_url' => $masterPhotoUrl,
                'school_setting'   => [
                    'lat'                     => $setting ? (float)$setting->lat : null,
                    'long'                    => $setting ? (float)$setting->long : null,
                    'radius_meters'           => $setting ? (float)$setting->radius : 100,
                    'is_ip_validation_active' => $setting ? (bool)$setting->is_ip_validation_active : false,
                ],
                'today_records'    => $todayRecords,
            ],
        ]);
    }

    /**
     * Submit Absen Selfie dengan Verifikasi Wajah AI CompreFace
     * POST /api/presence/selfie
     */
    public function submitSelfie(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Validasi Input
        $request->validate([
            'photo'             => 'required|image|max:10240', // Maks 10MB
            'lat'               => 'required|numeric',
            'long'              => 'required|numeric',
            'is_dinas_luar'     => 'nullable|boolean',
            'lokasi_dinas_luar' => 'required_if:is_dinas_luar,1,true|nullable|string',
            'client_public_ip'  => 'nullable|ip',
        ], [
            'photo.required'             => 'Foto selfie wajib dilampirkan.',
            'photo.image'                => 'File harus berupa format gambar (JPG/PNG).',
            'lat.required'               => 'Koordinat Latitude GPS wajib dikirim.',
            'long.required'              => 'Koordinat Longitude GPS wajib dikirim.',
            'lokasi_dinas_luar.required_if' => 'Tempat / Keterangan Dinas Luar wajib diisi jika mode dinas luar diaktifkan.',
        ]);

        $lat = (float)$request->input('lat');
        $long = (float)$request->input('long');
        $isDinasLuar = $request->boolean('is_dinas_luar');
        $lokasiDinasLuar = $request->input('lokasi_dinas_luar');

        // 2. Cek Apakah Hari Ini Bisa Absen
        $statusInfo = $this->attendanceService->determinePresensiType($user);
        if (!$statusInfo['can_attend']) {
            return response()->json([
                'status'  => 'error',
                'message' => $statusInfo['reason'] ?? 'Absensi tidak diizinkan saat ini.',
                'type'    => $statusInfo['type'],
            ], 422);
        }

        $tipeAbsens = $statusInfo['type']; // 'Masuk' atau 'Pulang'

        // 3. Ambil Foto Master User
        $profile = $this->attendanceService->getProfileModel($user);
        if (empty($profile->face_reference)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda belum memiliki Foto Master terdaftar di sistem. Harap daftarkan foto wajah master terlebih dahulu.',
            ], 422);
        }

        $setting = SchoolSetting::first();
        if (!$setting) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pengaturan GPS sekolah belum dikonfigurasi oleh administrator.',
            ], 500);
        }

        // 4. Validasi Geofencing & IP (Kecuali Dinas Luar)
        if (!$isDinasLuar) {
            // Validasi Jarak Geofencing
            $geoCheck = $this->attendanceService->validateGeofencing($lat, $long, $setting);
            if (!$geoCheck['valid']) {
                return response()->json([
                    'status'         => 'error',
                    'message'        => $geoCheck['message'],
                    'distance'       => $geoCheck['distance'],
                    'allowed_radius' => $geoCheck['allowed_radius'],
                ], 422);
            }

            // Validasi IP Wifi Sekolah (Jika Aktif)
            $ipCheck = $this->attendanceService->validateIp($request->input('client_public_ip'), $setting);
            if (!$ipCheck['valid']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $ipCheck['message'],
                ], 403);
            }
        }

        // 5. Anti-Spam Rate Limiter (CompreFace Guard)
        $rateLimitKey = 'face_verification_attempt_' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 15)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terlalu banyak percobaan verifikasi wajah yang gagal. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 300);

        // 6. Simpan File Selfie Sementara
        $uploadedFile = $request->file('photo');
        $storedPath = $uploadedFile->store('absensi-selfie', 'public');

        if (!$storedPath) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan file foto selfie ke server.',
            ], 500);
        }

        // 7. Lakukan Verifikasi Kecocokan Wajah (CompreFace AI)
        $verifyResult = $this->attendanceService->verifyFaceMatch($storedPath, $profile->face_reference);

        if (!$verifyResult['success']) {
            // Hapus file selfie jika gagal verifikasi
            Storage::disk('public')->delete($storedPath);

            return response()->json([
                'status'             => 'error',
                'message'            => $verifyResult['message'],
                'similarity_percent' => $verifyResult['similarity'] ?? 0,
            ], 422);
        }

        // Verifikasi Sukses -> Bersihkan Rate Limiter
        RateLimiter::clear($rateLimitKey);

        $currentTime = Carbon::now();

        // 8. Tambahkan Watermark pada Foto Selfie
        try {
            $this->attendanceService->addWatermark($storedPath, $currentTime, $lat, $long, $user->name);
        } catch (\Throwable $e) {
            Log::error("Watermark API Selfie gagal: " . $e->getMessage());
        }

        // 9. Simpan Record Kehadiran ke Database
        $record = $this->attendanceService->recordAttendance(
            $user,
            $tipeAbsens,
            $currentTime,
            $lat,
            $long,
            $storedPath,
            $isDinasLuar,
            $lokasiDinasLuar
        );

        $photoFullUrl = url('storage/' . ltrim(str_replace('public/', '', $storedPath), '/'));

        return response()->json([
            'status'  => 'success',
            'message' => "Presensi {$tipeAbsens} Berhasil!",
            'data'    => [
                'id'                 => $record->id,
                'tipe'               => $tipeAbsens,
                'status_kehadiran'   => $record->status,
                'waktu'              => $currentTime->format('Y-m-d H:i:s'),
                'jam'                => $currentTime->format('H:i'),
                'similarity_percent' => $verifyResult['similarity'] ?? 100,
                'photo_url'          => $photoFullUrl,
                'is_dinas_luar'      => (bool)$record->is_dinas_luar,
                'lokasi_dinas_luar'  => $record->lokasi_dinas_luar,
            ],
        ]);
    }

    /**
     * Daftarkan / Perbarui Foto Master Wajah
     * POST /api/presence/register-face
     */
    public function registerMasterFace(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|image|max:10240',
        ], [
            'photo.required' => 'Foto wajah master wajib dilampirkan.',
            'photo.image'    => 'File harus berupa gambar.',
        ]);

        $profile = $this->attendanceService->getProfileModel($user);

        // Simpan file sementara
        $uploadedFile = $request->file('photo');
        $storedPath = $uploadedFile->store('face-references', 'public');

        // Pre-filter lokal
        $pre = $this->compreFaceService->preFilter($storedPath);
        if (!$pre['ok']) {
            Storage::disk('public')->delete($storedPath);
            return response()->json([
                'status'  => 'error',
                'message' => $pre['reason'] ?? 'Foto master tidak memenuhi kriteria.',
            ], 422);
        }

        // Cek deteksi wajah
        $hasFace = $this->compreFaceService->detectFace($storedPath);
        if (!$hasFace) {
            Storage::disk('public')->delete($storedPath);
            return response()->json([
                'status'  => 'error',
                'message' => 'Wajah tidak terdeteksi pada foto. Pastikan wajah terlihat jelas menghadap kamera dan tanpa masker/penutup.',
            ], 422);
        }

        // Hapus foto master lama jika ada
        if (!empty($profile->face_reference) && Storage::disk('public')->exists($profile->face_reference)) {
            Storage::disk('public')->delete($profile->face_reference);
        }

        // Simpan ke database
        $profile->update(['face_reference' => $storedPath]);

        $photoFullUrl = url('storage/' . ltrim(str_replace('public/', '', $storedPath), '/'));

        return response()->json([
            'status'    => 'success',
            'message'   => 'Foto master wajah berhasil didaftarkan.',
            'photo_url' => $photoFullUrl,
        ]);
    }
}
