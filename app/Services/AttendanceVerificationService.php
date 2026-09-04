<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceVerificationService
{
    protected CompreFaceService $faceService;

    public function __construct()
    {
        $this->faceService = new CompreFaceService();
    }

    /**
     * Dapatkan model Student jika user adalah Siswa, atau User jika Guru/TU
     */
    public function getProfileModel(User $user): object
    {
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            if (str_contains($nis, '@')) {
                $nis = explode('@', $nis)[0];
            }
            $student = Student::with('classRoom')->where('nis', $nis)->first();
            if ($student) {
                return $student;
            }
        }
        return $user;
    }

    /**
     * Tentukan tipe presensi hari ini (Masuk, Pulang, Selesai, atau Libur)
     */
    public function determinePresensiType(User $user, ?Carbon $date = null): array
    {
        $today = $date ? $date->copy()->startOfDay() : Carbon::today();

        // 1. Cek Hari Minggu
        if ($today->isSunday()) {
            return [
                'type' => 'Libur',
                'reason' => 'Hari Minggu — Libur Akhir Pekan',
                'can_attend' => false,
            ];
        }

        // 2. Cek Hari Libur dari Database
        $holiday = Holiday::whereDate('holiday_date', $today)->first();
        if ($holiday) {
            return [
                'type' => 'Libur',
                'reason' => 'Hari Libur: ' . $holiday->name,
                'can_attend' => false,
            ];
        }

        // 3. Cek Izin / Sakit Guru / TU
        if (in_array($user->role, ['Guru', 'TU'])) {
            if (IzinGuruTu::hasActiveIzinToday($user->nipy ?? '', $user->email)) {
                return [
                    'type' => 'Izin',
                    'reason' => 'Anda memiliki izin/sakit yang aktif hari ini.',
                    'can_attend' => false,
                ];
            }
        }

        // 4. Hitung riwayat kehadiran hari ini
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            if (str_contains($nis, '@')) {
                $nis = explode('@', $nis)[0];
            }
            $student = Student::where('nis', $nis)->first();
            $count = $student 
                ? KehadiranSiswa::where('nis', $student->nis)->whereDate('waktu_tap', $today)->count() 
                : 0;
        } else {
            $count = KehadiranGuruTu::where(function ($q) use ($user) {
                if (!empty($user->nipy)) {
                    $q->where('nipy', $user->nipy);
                }
                if (!empty($user->email)) {
                    $q->orWhere('nipy', $user->email);
                }
            })->whereDate('waktu_tap', $today)->count();
        }

        if ($count === 0) {
            return ['type' => 'Masuk', 'reason' => null, 'can_attend' => true];
        }

        if ($count === 1) {
            return ['type' => 'Pulang', 'reason' => null, 'can_attend' => true];
        }

        return [
            'type' => 'Selesai',
            'reason' => 'Anda sudah menyelesaikan absensi hari ini (Masuk & Pulang).',
            'can_attend' => false,
        ];
    }

    /**
     * Validasi lokasi GPS (Geofencing) menggunakan rumus Haversine
     */
    public function validateGeofencing(float $clientLat, float $clientLong, SchoolSetting $setting): array
    {
        $distance = $this->haversineGreatCircleDistance(
            $clientLat,
            $clientLong,
            (float)$setting->lat,
            (float)$setting->long
        );

        $allowedRadius = (float)($setting->radius ?? 100);

        if ($distance > $allowedRadius) {
            return [
                'valid' => false,
                'distance' => round($distance, 1),
                'allowed_radius' => $allowedRadius,
                'message' => "Anda berada di luar jangkauan absensi sekolah (" . round($distance) . "m dari sekolah, toleransi {$allowedRadius}m).",
            ];
        }

        return [
            'valid' => true,
            'distance' => round($distance, 1),
            'allowed_radius' => $allowedRadius,
        ];
    }

    /**
     * Hitung jarak dua titik koordinat dalam meter
     */
    public function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000): float
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Validasi IP publik jaringan (Anti-Fake GPS)
     */
    public function validateIp(?string $clientIp, SchoolSetting $setting): array
    {
        if (!$setting->is_ip_validation_active) {
            return ['valid' => true];
        }

        $allowedIps = array_filter(array_map('trim', [
            $setting->allowed_ip_1,
            $setting->allowed_ip_2,
            $setting->allowed_ip_3,
            $setting->allowed_ip_4,
            $setting->allowed_ip_5,
            $setting->allowed_ip_6,
        ]));

        if (empty($allowedIps)) {
            return ['valid' => true];
        }

        $clientIp = trim($clientIp ?? '');
        if (empty($clientIp)) {
            $clientIp = request()->header('CF-Connecting-IP')
                ?? request()->header('X-Real-IP')
                ?? request()->header('X-Forwarded-For')
                ?? request()->ip();

            if (str_contains($clientIp, ',')) {
                $clientIp = trim(explode(',', $clientIp)[0]);
            }
        }

        foreach ($allowedIps as $pattern) {
            if ($this->ipMatches($clientIp, $pattern)) {
                return ['valid' => true, 'ip' => $clientIp];
            }
        }

        return [
            'valid' => false,
            'ip' => $clientIp,
            'message' => "Akses Ditolak. Harap gunakan koneksi Wifi Sekolah. IP terdeteksi: {$clientIp}",
        ];
    }

    private function ipMatches(string $clientIp, string $allowedIpPattern): bool
    {
        $clientIp = trim($clientIp);
        $allowedIpPattern = trim($allowedIpPattern);

        if ($clientIp === $allowedIpPattern) return true;

        if (str_contains($allowedIpPattern, '*')) {
            $regex = str_replace(['.', '*'], ['\.', '.*'], $allowedIpPattern);
            return (bool) preg_match('/^' . $regex . '$/', $clientIp);
        }

        if (str_contains($allowedIpPattern, '/')) {
            return $this->ipInNetwork($clientIp, $allowedIpPattern);
        }

        return false;
    }

    private function ipInNetwork(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) return false;
        if (strlen($ipBin) !== strlen($subnetBin)) return false;

        $mask = '';
        $bytes = strlen($ipBin);
        for ($i = 0; $i < $bytes; $i++) {
            $bitsInByte = min($bits - ($i * 8), 8);
            if ($bitsInByte <= 0) {
                $mask .= chr(0);
            } elseif ($bitsInByte >= 8) {
                $mask .= chr(255);
            } else {
                $mask .= chr(256 - (1 << (8 - $bitsInByte)));
            }
        }

        return ($ipBin & $mask) === ($subnetBin & $mask);
    }

    /**
     * Verifikasi kecocokan wajah selfie dengan foto master via CompreFace
     */
    public function verifyFaceMatch(string $selfieRelativePath, string $masterRelativePath): array
    {
        // 1. Pre-filter lokal
        $pre = $this->faceService->preFilter($selfieRelativePath);
        if (!$pre['ok']) {
            return [
                'success' => false,
                'message' => $pre['reason'] ?? 'Kualitas foto selfie tidak memenuhi standar.',
            ];
        }

        // 2. Perbandingan Wajah via CompreFace
        $check = $this->faceService->compare($selfieRelativePath, $masterRelativePath);

        if (!$check['success']) {
            return [
                'success' => false,
                'message' => $check['error'] ?? 'Gagal memproses verifikasi AI.',
            ];
        }

        if (!$check['is_identical']) {
            return [
                'success' => false,
                'similarity' => $check['confidence'] ?? 0,
                'message' => 'Wajah selfie tidak cocok dengan foto master (' . ($check['confidence'] ?? 0) . '% kecocokan). Harap hadapkan wajah ke kamera dengan pencahayaan cukup.',
            ];
        }

        return [
            'success' => true,
            'similarity' => $check['confidence'] ?? 100,
            'message' => 'Wajah cocok terverifikasi.',
        ];
    }

    /**
     * Tambahkan Watermark pada foto selfie
     */
    public function addWatermark(string $photoPath, Carbon $time, float $lat, float $long, string $userName): void
    {
        $fullPath = storage_path('app/public/' . ltrim(str_replace('public/', '', $photoPath), '/'));

        if (!file_exists($fullPath)) {
            Log::warning("Watermark gagal: File tidak ditemukan " . $fullPath);
            return;
        }

        $mime = @mime_content_type($fullPath);
        $img = null;
        if ($mime == 'image/jpeg') $img = @imagecreatefromjpeg($fullPath);
        elseif ($mime == 'image/png') $img = @imagecreatefrompng($fullPath);
        elseif ($mime == 'image/webp') $img = @imagecreatefromwebp($fullPath);

        if (!$img) {
            Log::warning("Watermark gagal: Format tidak didukung " . $mime);
            return;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        $bannerHeight = 65;
        $bannerY = $height - $bannerHeight;

        imagealphablending($img, true);
        imagesavealpha($img, true);

        $blackAlpha = imagecolorallocatealpha($img, 0, 0, 0, 40);
        $white = imagecolorallocate($img, 255, 255, 255);
        $yellow = imagecolorallocate($img, 255, 255, 0);
        $cyan = imagecolorallocate($img, 0, 255, 255);

        imagefilledrectangle($img, 0, $bannerY, $width, $height, $blackAlpha);

        $font = 5;
        $userStr = "Nama   : " . $userName;
        $timeStr = "Waktu  : " . $time->format('d M Y H:i:s') . " WIB";
        $locStr  = "Lokasi : " . round($lat, 5) . ", " . round($long, 5);

        imagestring($img, $font, 15, $bannerY + 8,  $userStr, $cyan);
        imagestring($img, $font, 15, $bannerY + 25, $timeStr, $white);
        imagestring($img, $font, 15, $bannerY + 42, $locStr, $yellow);

        $logoPath = public_path('images/logo_BG.png');
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                imagealphablending($logo, true);
                imagesavealpha($logo, true);

                $logoWidth = imagesx($logo);
                $logoHeight = imagesy($logo);
                $newLogoHeight = 50;
                $newLogoWidth = (int) round(($logoWidth / $logoHeight) * $newLogoHeight);

                $logoX = (int) round($width - $newLogoWidth - 15);
                $logoY = (int) round($bannerY + (($bannerHeight - $newLogoHeight) / 2));

                imagecopyresampled($img, $logo, $logoX, $logoY, 0, 0, $newLogoWidth, $newLogoHeight, $logoWidth, $logoHeight);
                imagedestroy($logo);
            }
        }

        if ($mime == 'image/jpeg') imagejpeg($img, $fullPath, 90);
        elseif ($mime == 'image/png') imagepng($img, $fullPath);
        elseif ($mime == 'image/webp') imagewebp($img, $fullPath, 90);

        imagedestroy($img);
    }

    /**
     * Simpan rekaman presensi ke database
     */
    public function recordAttendance(
        User $user,
        string $tipeAbsens,
        Carbon $currentTime,
        float $lat,
        float $long,
        string $photoPath,
        bool $isDinasLuar = false,
        ?string $lokasiDinasLuar = null
    ): object {
        $status = $isDinasLuar ? 'Dinas Luar' : 'Hadir';
        $keterangan = "{$tipeAbsens} - Presensi Mandiri (Mobile App)";

        if ($isDinasLuar && !empty($lokasiDinasLuar)) {
            $keterangan .= " [Dinas Luar: {$lokasiDinasLuar}]";
        }

        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            if (str_contains($nis, '@')) {
                $nis = explode('@', $nis)[0];
            }
            $student = Student::where('nis', $nis)->first();

            if ($tipeAbsens === 'Masuk' && $currentTime->format('H:i') > '07:05' && !$isDinasLuar) {
                $status = 'Terlambat';
            }

            $record = KehadiranSiswa::create([
                'nis' => $student ? $student->nis : $nis,
                'rfid_uid' => $student?->rfid,
                'waktu_tap' => $currentTime,
                'status' => $status,
                'lat' => $lat,
                'long' => $long,
                'photo' => $photoPath,
                'keterangan' => $keterangan,
                'is_dinas_luar' => $isDinasLuar,
                'lokasi_dinas_luar' => $lokasiDinasLuar,
            ]);

            // Sync ke BaknusDrive (opsional, non-blocking)
            $this->syncToBaknusDrive(
                $student ? $student->nis : $nis,
                $student ? $student->name : $user->name,
                $student?->classRoom?->kelas ?? '-',
                'siswa',
                $currentTime,
                $tipeAbsens,
                $status
            );

            return $record;
        }

        // Guru / TU
        $nipy = !empty($user->nipy) ? $user->nipy : $user->email;

        $record = KehadiranGuruTu::create([
            'nipy' => $nipy,
            'rfid_uid' => $user->rfid,
            'waktu_tap' => $currentTime,
            'status' => $status,
            'lat' => $lat,
            'long' => $long,
            'photo' => $photoPath,
            'keterangan' => $keterangan,
            'is_dinas_luar' => $isDinasLuar,
            'lokasi_dinas_luar' => $lokasiDinasLuar,
        ]);

        $roleInApi = strtolower($user->role) === 'tu' ? 'TU' : 'guru';
        $this->syncToBaknusDrive(
            $nipy,
            $user->name,
            '-',
            $roleInApi,
            $currentTime,
            $tipeAbsens,
            $status
        );

        return $record;
    }

    private function syncToBaknusDrive($id, $name, $kelas, $role, $now, $type, $desc): void
    {
        try {
            $driveBase = env('BAKNUSDRIVE_URL');
            if (empty($driveBase)) return;

            $driveUrl = rtrim($driveBase, '/') . '/api/attend/upload';
            $apiKey = env('BAKNUS_ATTEND_API_KEY');

            Http::timeout(2)
                ->withHeaders(['X-Attend-API-Key' => $apiKey])
                ->asForm()
                ->post($driveUrl, [
                    'NIS' => $id,
                    'Nama' => $name,
                    'kelas' => $kelas,
                    'role' => $role,
                    'waktu_tap' => $now->format('H:i:s'),
                    'status' => $type,
                    'keterangan' => $desc,
                ]);
        } catch (\Throwable $e) {
            Log::warning("Gagal kirim ke BaknusDrive: " . $e->getMessage());
        }
    }
}
