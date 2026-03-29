<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
