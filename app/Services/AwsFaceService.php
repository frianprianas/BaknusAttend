<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AwsFaceService
{
    protected RekognitionClient $client;

    // Threshold minimal kecocokan wajah (90% = sangat ketat, tidak bisa lolos masker)
    const SIMILARITY_THRESHOLD = 90.0;

    public function __construct()
    {
        $this->client = new RekognitionClient([
            'region'      => config('services.aws.region', env('AWS_DEFAULT_REGION', 'ap-southeast-1')),
            'version'     => 'latest',
            'credentials' => [
                'key'    => config('services.aws.key', env('AWS_ACCESS_KEY_ID')),
                'secret' => config('services.aws.secret', env('AWS_SECRET_ACCESS_KEY')),
            ],
        ]);
    }

    /**
     * Resolve storage path dan kembalikan byte gambar.
     */
    private function getImageBytes(string $path): ?string
    {
        $path = str_replace('public/', '', $path);
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }
        Log::error("AwsFace: File tidak ditemukan di [{$path}]");
        return null;
    }

    /**
     * PRE-FILTER LOKAL (GRATIS - tanpa panggil AWS).
     *
     * Menolak foto yang jelas bukan selfie wajah menggunakan PHP GD:
     *  - File terlalu kecil (<5KB)  → bukan foto asli kamera
     *  - Resolusi terlalu kecil     → bukan kamera, mungkin ikon
     *  - Gambar terlalu uniform     → foto tembok polos / langit / layar putih
     *
     * Return: ['ok' => true] atau ['ok' => false, 'reason' => '...']
     */
    public function preFilter(string $imagePath): array
    {
        $path = str_replace('public/', '', $imagePath);

        // 1. Cek keberadaan file
        if (!Storage::disk('public')->exists($path)) {
            return ['ok' => false, 'reason' => 'File foto tidak ditemukan.'];
        }

        $bytes = Storage::disk('public')->get($path);
        $size  = strlen($bytes);

        // 2. File terlalu kecil (<5KB) → pasti bukan foto kamera
        if ($size < 5120) {
            Log::warning("preFilter: Ditolak — file terlalu kecil ({$size} bytes) [{$path}]");
            return ['ok' => false, 'reason' => 'Ukuran foto terlalu kecil. Gunakan kamera untuk mengambil foto selfie.'];
        }

        // 3. Pastikan format valid & ambil dimensi via GD
        $img = @imagecreatefromstring($bytes);
        if (!$img) {
            Log::warning("preFilter: Ditolak — bukan gambar valid [{$path}]");
            return ['ok' => false, 'reason' => 'Format foto tidak valid.'];
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // 4. Resolusi terlalu kecil (<100x100)
        if ($w < 100 || $h < 100) {
            imagedestroy($img);
            Log::warning("preFilter: Ditolak — resolusi terlalu kecil ({$w}x{$h}) [{$path}]");
            return ['ok' => false, 'reason' => 'Resolusi foto terlalu kecil. Pastikan kamera perangkat aktif.'];
        }

        // 5. Deteksi gambar terlalu uniform (tembok polos, layar putih, foto langit)
        //    Ambil sampel 10x10 piksel dari area tengah, hitung standard deviation RGB
        $samples  = [];
        $stepX    = max(1, intval($w / 12));
        $stepY    = max(1, intval($h / 12));
        $startX   = intval($w * 0.2);
        $startY   = intval($h * 0.2);
        $endX     = intval($w * 0.8);
        $endY     = intval($h * 0.8);

        for ($x = $startX; $x < $endX; $x += $stepX) {
            for ($y = $startY; $y < $endY; $y += $stepY) {
                $rgb       = imagecolorat($img, $x, $y);
                $r         = ($rgb >> 16) & 0xFF;
                $g         = ($rgb >> 8) & 0xFF;
                $b         = $rgb & 0xFF;
                $samples[] = ($r + $g + $b) / 3; // luminance sederhana
            }
        }

        imagedestroy($img);

        if (count($samples) > 0) {
            $mean   = array_sum($samples) / count($samples);
            $variance = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $samples)) / count($samples);
            $stdDev = sqrt($variance);

            // StdDev < 8 → gambar sangat uniform (tembok putih, layar hitam, dll)
            if ($stdDev < 8.0) {
                Log::warning("preFilter: Ditolak — gambar terlalu uniform (stdDev={$stdDev}) [{$path}]");
                return ['ok' => false, 'reason' => 'Foto terlalu gelap, terlalu terang, atau bukan foto wajah. Pastikan wajah Anda terlihat jelas.'];
            }
        }

        return ['ok' => true];
    }

    /**
     * Deteksi wajah di gambar (untuk validasi foto Master).
     * Menolak: wajah terlalu kecil, pakai masker, tidak ada wajah.
     */
    public function detectFace(string $imagePath): bool
    {
        try {
            $imageData = $this->getImageBytes($imagePath);
            if (!$imageData) return false;

            $result = $this->client->detectFaces([
                'Image'      => ['Bytes' => $imageData],
                'Attributes' => ['ALL'], // Aktifkan semua atribut termasuk ProtectiveMask
            ]);

            $faces = $result['FaceDetails'] ?? [];

            if (count($faces) === 0) {
                Log::warning("AwsFace detectFace: Tidak ada wajah terdeteksi di [{$imagePath}]");
                return false;
            }

            $face = $faces[0];

            // Tolak jika wajah terlalu kecil (< 5% area gambar)
            $faceSize = ($face['BoundingBox']['Width'] ?? 0) * ($face['BoundingBox']['Height'] ?? 0);
            if ($faceSize < 0.05) {
                Log::warning("AwsFace detectFace: Wajah terlalu kecil ({$faceSize}) di [{$imagePath}]");
                return false;
            }

            // Tolak jika pakai masker / wajah tertutup
            foreach ($face['ProtectiveMaskDetails'] ?? [] as $mask) {
                if (($mask['Value'] ?? '') === 'FACE_OCCLUDED' && ($mask['Confidence'] ?? 0) > 85) {
                    Log::warning("AwsFace detectFace: Masker/penutup wajah terdeteksi di [{$imagePath}]");
                    return false;
                }
            }

            // Validasi tambahan: deteksi mata terbuka (confidence > 50%)
            $eyeConfidence = $face['EyesOpen']['Confidence'] ?? 100;
            if ($eyeConfidence < 50) {
                Log::warning("AwsFace detectFace: Kepercayaan deteksi mata rendah ({$eyeConfidence}%)");
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error("AwsFace detectFace Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bandingkan selfie dengan foto master.
     * Sebelum membandingkan, selfie divalidasi dulu:
     * - Wajah harus terdeteksi
     * - Tidak boleh pakai masker
     * - Kualitas foto harus cukup (tidak gelap/blur)
     * - Threshold kemiripan: 90% (sangat ketat)
     */
    public function compare(string $selfiePath, string $referencePath): array
    {
        try {
            $selfieBytes    = $this->getImageBytes($selfiePath);
            $referenceBytes = $this->getImageBytes($referencePath);

            if (!$selfieBytes) {
                return ['success' => false, 'error' => 'Foto selfie tidak ditemukan di storage.'];
            }
            if (!$referenceBytes) {
                return ['success' => false, 'error' => 'Foto master tidak ditemukan di storage.'];
            }

            // -------------------------------------------------------
            // TAHAP 1: Validasi selfie sebelum dibandingkan
            // -------------------------------------------------------
            $detectResult = $this->client->detectFaces([
                'Image'      => ['Bytes' => $selfieBytes],
                'Attributes' => ['ALL'],
            ]);

            $selfiFaces = $detectResult['FaceDetails'] ?? [];

            if (count($selfiFaces) === 0) {
                return [
                    'success'      => true,
                    'is_identical' => false,
                    'confidence'   => 0,
                    'error'        => 'Wajah tidak terdeteksi pada foto selfie. Pastikan wajah menghadap kamera dan pencahayaan cukup.',
                ];
            }

            $selfFace = $selfiFaces[0];

            // Tolak jika wajah terlalu kecil (terlalu jauh dari kamera)
            $faceSize = ($selfFace['BoundingBox']['Width'] ?? 0) * ($selfFace['BoundingBox']['Height'] ?? 0);
            if ($faceSize < 0.04) {
                return [
                    'success'      => true,
                    'is_identical' => false,
                    'confidence'   => 0,
                    'error'        => 'Wajah terlalu jauh dari kamera. Dekatkan wajah Anda ke kamera.',
                ];
            }

            // Tolak jika pakai masker atau wajah tertutup
            foreach ($selfFace['ProtectiveMaskDetails'] ?? [] as $mask) {
                if (($mask['Value'] ?? '') === 'FACE_OCCLUDED' && ($mask['Confidence'] ?? 0) > 80) {
                    return [
                        'success'      => true,
                        'is_identical' => false,
                        'confidence'   => 0,
                        'error'        => '❌ Terdeteksi masker atau penutup wajah. Lepaskan masker/penutup untuk melakukan absensi.',
                    ];
                }
            }

            // Tolak jika kualitas foto buruk
            $brightness = $selfFace['Quality']['Brightness'] ?? 100;
            $sharpness  = $selfFace['Quality']['Sharpness']  ?? 100;
            if ($brightness < 25 || $sharpness < 20) {
                return [
                    'success'      => true,
                    'is_identical' => false,
                    'confidence'   => 0,
                    'error'        => "Foto terlalu gelap atau buram. Pastikan pencahayaan cukup (Kecerahan: {$brightness}%, Ketajaman: {$sharpness}%).",
                ];
            }

            // -------------------------------------------------------
            // TAHAP 2: Bandingkan selfie dengan foto master
            // -------------------------------------------------------
            $result = $this->client->compareFaces([
                'SourceImage'         => ['Bytes' => $referenceBytes], // Foto Master
                'TargetImage'         => ['Bytes' => $selfieBytes],    // Foto Selfie
                'SimilarityThreshold' => self::SIMILARITY_THRESHOLD,   // 90%
                'QualityFilter'       => 'HIGH',                        // Hanya proses foto berkualitas tinggi
            ]);

            $matches = $result['FaceMatches'] ?? [];

            if (count($matches) > 0) {
                $similarity = round((float) $matches[0]['Similarity'], 1);
                Log::info("AwsFace compare: COCOK — similarity {$similarity}% [{$selfiePath}]");
                return [
                    'success'      => true,
                    'is_identical' => true,
                    'confidence'   => $similarity,
                    'message'      => "Wajah cocok ({$similarity}%)",
                ];
            }

            // Ambil info dari unmatched untuk logging
            $unmatched   = $result['UnmatchedFaces'] ?? [];
            $unmatchConf = count($unmatched) > 0 ? round((float)($unmatched[0]['Confidence'] ?? 0), 1) : 0;

            Log::warning("AwsFace compare: TIDAK COCOK, threshold=" . self::SIMILARITY_THRESHOLD . "% [{$selfiePath}]");
            return [
                'success'      => true,
                'is_identical' => false,
                'confidence'   => $unmatchConf,
                'error'        => 'Wajah tidak cocok dengan data master. Pastikan wajah menghadap depan tanpa penutup.',
            ];

        } catch (\Throwable $e) {
            Log::error("AwsFace compare Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal memproses verifikasi AI: ' . $e->getMessage()];
        }
    }
}
