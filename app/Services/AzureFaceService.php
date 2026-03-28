<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AzureFaceService
{
    protected string $key;
    protected string $endpoint;

    public function __construct()
    {
        $this->key      = config('services.azure_face.key', env('AZURE_FACE_KEY', ''));
        $this->endpoint = rtrim(config('services.azure_face.endpoint', env('AZURE_FACE_ENDPOINT', '')), '/');
    }

    /**
     * Detect face in image and return faceId string, or null if failed / no face found.
     */
    public function detectFace(string $imagePath): ?string
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::error("AzureFace detectFace: file not found at [{$imagePath}]");
                return null;
            }

            $imageData = Storage::disk('public')->get($imagePath);

            // Use withBody() to send raw binary data (octet-stream)
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
            ])->withBody($imageData, 'application/octet-stream')
              ->post($this->endpoint . '/face/v1.0/detect?returnFaceId=true&recognitionModel=recognition_04&detectionModel=detection_03');

            if ($response->successful()) {
                $faces = $response->json();
                if (is_array($faces) && count($faces) > 0) {
                    return $faces[0]['faceId'];
                }
                Log::info("AzureFace detectFace: no face found in [{$imagePath}]");
                return null;
            }

            Log::error("AzureFace detectFace error [{$response->status()}]: " . $response->body());
            return null;

        } catch (\Throwable $e) {
            Log::error("AzureFace detectFace exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify if two faceIds belong to the same person.
     * Returns ['isIdentical' => bool, 'confidence' => float] or null on error.
     */
    public function verifyFaces(string $faceId1, string $faceId2): ?array
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
                'Content-Type'              => 'application/json',
            ])->post($this->endpoint . '/face/v1.0/verify', [
                'faceId1' => $faceId1,
                'faceId2' => $faceId2,
            ]);

            if ($response->successful()) {
                return $response->json(); // ['isIdentical' => bool, 'confidence' => float]
            }

            Log::error("AzureFace verifyFaces error [{$response->status()}]: " . $response->body());
            return null;

        } catch (\Throwable $e) {
            Log::error("AzureFace verifyFaces exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Full pipeline: detect reference face, detect selfie face, verify match.
     * Returns ['success' => bool, 'is_identical' => bool|null, 'confidence' => float|null, 'error' => string|null]
     */
    public function compare(string $selfiePath, string $referencePath): array
    {
        $refFaceId = $this->detectFace($referencePath);
        if (!$refFaceId) {
            return [
                'success' => false,
                'error'   => 'Gagal mendeteksi wajah pada foto referensi master. Hubungi Admin.',
            ];
        }

        $selfieFaceId = $this->detectFace($selfiePath);
        if (!$selfieFaceId) {
            return [
                'success' => false,
                'error'   => 'Wajah tidak terdeteksi pada foto selfie. Pastikan wajah terlihat jelas, tidak tertutup, dan pencahayaan terang.',
            ];
        }

        $result = $this->verifyFaces($refFaceId, $selfieFaceId);
        if (!$result) {
            return [
                'success' => false,
                'error'   => 'Gagal melakukan verifikasi wajah ke server Azure AI.',
            ];
        }

        return [
            'success'      => true,
            'is_identical' => (bool) ($result['isIdentical'] ?? false),
            'confidence'   => (float) ($result['confidence'] ?? 0),
            'message'      => ($result['isIdentical'] ?? false) ? 'Wajah Cocok!' : 'Wajah Tidak Cocok.',
        ];
    }
}
