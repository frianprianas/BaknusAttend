<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompreFaceService
{
    protected string $endpoint;
    protected string $apiKey;
    const SIMILARITY_THRESHOLD = 0.90; // 90%

    public function __construct()
    {
        // Default to compreface-api (docker service name) and port 8000
        $this->endpoint = rtrim(config('services.compreface.endpoint', env('COMPREFACE_ENDPOINT', 'http://compreface-api:8000')), '/');
        $this->apiKey   = config('services.compreface.key', env('COMPREFACE_KEY', ''));
    }

    private function getImageBytes(string $path): ?string
    {
        $path = str_replace('public/', '', $path);
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }
        return null;
    }

    public function preFilter(string $imagePath): array
    {
        $path = str_replace('public/', '', $imagePath);

        if (!Storage::disk('public')->exists($path)) {
            return ['ok' => false, 'reason' => 'File foto tidak ditemukan.'];
        }

        $bytes = Storage::disk('public')->get($path);
        $size  = strlen($bytes);

        if ($size < 5120) {
            return ['ok' => false, 'reason' => 'Ukuran foto terlalu kecil. Gunakan kamera untuk mengambil foto selfie.'];
        }

        if (!function_exists('imagecreatefromstring')) {
            return ['ok' => true];
        }

        $img = @imagecreatefromstring($bytes);
        if (!$img) {
            return ['ok' => false, 'reason' => 'Format foto tidak valid.'];
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if ($w < 100 || $h < 100) {
            imagedestroy($img);
            return ['ok' => false, 'reason' => 'Resolusi foto terlalu kecil. Pastikan kamera perangkat aktif.'];
        }

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
                $samples[] = ($r + $g + $b) / 3;
            }
        }

        imagedestroy($img);

        if (count($samples) > 0) {
            $mean     = array_sum($samples) / count($samples);
            $variance = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $samples)) / count($samples);
            $stdDev   = sqrt($variance);

            if ($stdDev < 8.0) {
                return ['ok' => false, 'reason' => 'Foto terlalu gelap, terlalu terang, atau bukan foto wajah. Pastikan wajah Anda terlihat jelas.'];
            }
        }

        return ['ok' => true];
    }

    public function detectFace(string $imagePath): bool
    {
        try {
            $imageData = $this->getImageBytes($imagePath);
            if (!$imageData) return false;

            // Use the detection service of CompreFace
            // Requires a Detection API Key or we can use the Verification API Key if the service allows it.
            // Wait, CompreFace Detection service uses a different API Key.
            // To simplify, we can just use the Verification service with the same image twice to check if a face exists.
            
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])
            ->attach('source_image', $imageData, 'source.jpg')
            ->attach('target_image', $imageData, 'target.jpg')
            ->post($this->endpoint . '/api/v1/verification/verify');

            if (!$response->successful()) {
                Log::error("CompreFace detectFace API Error: " . $response->body());
                return false;
            }

            $result = $response->json();
            $faces = $result['result'] ?? [];

            if (count($faces) === 0) {
                Log::warning("CompreFace detectFace: Tidak ada wajah terdeteksi");
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error("CompreFace detectFace Exception: " . $e->getMessage());
            return false;
        }
    }

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

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])
            ->timeout(60)
            ->attach('source_image', $referenceBytes, 'source.jpg')
            ->attach('target_image', $selfieBytes, 'target.jpg')
            ->post($this->endpoint . '/api/v1/verification/verify');

            if (!$response->successful()) {
                $errorMsg = "Gagal memproses verifikasi AI (Error " . $response->status() . ").";
                $body = $response->json();
                
                Log::error("CompreFace compare API Error: " . $response->body());
                
                // Cek jika error karena wajah tidak ditemukan
                if ($response->status() === 400 && isset($body['message']) && str_contains(strtolower($body['message']), 'no face')) {
                    $errorMsg = "Wajah tidak terdeteksi. Pastikan wajah menghadap kamera dan pencahayaan cukup.";
                }
                // Jika API Key tidak valid atau belum di set
                elseif ($response->status() === 401 || $response->status() === 403) {
                    $errorMsg = "API Key CompreFace tidak valid. Hubungi administrator.";
                }
                
                return ['success' => false, 'error' => $errorMsg];
            }

            $resultData = $response->json();
            $matches = $resultData['result'] ?? [];

            if (count($matches) === 0 || !isset($matches[0]['face_matches']) || count($matches[0]['face_matches']) === 0) {
                return [
                    'success'      => true,
                    'is_identical' => false,
                    'confidence'   => 0,
                    'error'        => 'Wajah tidak terdeteksi pada foto. Pastikan wajah menghadap kamera dan pencahayaan cukup.',
                ];
            }

            // CompreFace returns similarity between 0 and 1
            $similarity = (float) $matches[0]['face_matches'][0]['similarity'];
            $similarityPercent = round($similarity * 100, 1);

            if ($similarity >= self::SIMILARITY_THRESHOLD) {
                Log::info("CompreFace compare: COCOK — similarity {$similarityPercent}%");
                return [
                    'success'      => true,
                    'is_identical' => true,
                    'confidence'   => $similarityPercent,
                    'message'      => "Wajah cocok ({$similarityPercent}%)",
                ];
            }

            Log::warning("CompreFace compare: TIDAK COCOK, threshold=" . (self::SIMILARITY_THRESHOLD * 100) . "%, got={$similarityPercent}%");
            return [
                'success'      => true,
                'is_identical' => false,
                'confidence'   => $similarityPercent,
                'error'        => 'Wajah tidak cocok dengan data master. Pastikan wajah menghadap depan tanpa penutup.',
            ];

        } catch (\Throwable $e) {
            Log::error("CompreFace compare Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal memproses verifikasi AI: ' . $e->getMessage()];
        }
    }
}
