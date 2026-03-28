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
        $this->key = config('services.azure_face.key') ?? env('AZURE_FACE_KEY');
        $this->endpoint = rtrim(config('services.azure_face.endpoint') ?? env('AZURE_FACE_ENDPOINT'), '/');
    }

    /**
     * Detect face and return faceId
     */
    public function detectFace(string $imagePath): ?string
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::error("AzureFace: File not found at $imagePath");
                return null;
            }

            $imageData = Storage::disk('public')->get($imagePath);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
                'Content-Type' => 'application/octet-stream',
            ])->post($this->endpoint . '/face/v1.0/detect?returnFaceId=true&recognitionModel=recognition_04&detectionModel=detection_03', $imageData);

            if ($response->successful()) {
                $data = $response->json();
                if (count($data) > 0) {
                    return $data[0]['faceId'];
                }
            } else {
                Log::error("AzureFace Detect Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("AzureFace Detect Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Verify if two faces belong to the same person
     * Returns similarity score or null on failure
     */
    public function verifyFaces(string $faceId1, string $faceId2): ?array
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . '/face/v1.0/verify', [
                'faceId1' => $faceId1,
                'faceId2' => $faceId2,
            ]);

            if ($response->successful()) {
                return $response->json(); // contains 'isIdentical' and 'confidence'
            } else {
                Log::error("AzureFace Verify Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("AzureFace Verify Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Full flow: Compare a new selfie against a reference photo
     */
    public function compare(string $selfiePath, string $referencePath): array
    {
        $faceId1 = $this->detectFace($referencePath);
        if (!$faceId1) {
            return ['success' => false, 'error' => 'Gagal mendeteksi wajah pada foto referensi master.'];
        }

        $faceId2 = $this->detectFace($selfiePath);
        if (!$faceId2) {
            return ['success' => false, 'error' => 'Wajah tidak terdeteksi pada foto selfie. Pastikan wajah terlihat jelas dan pencahayaan terang.'];
        }

        $result = $this->verifyFaces($faceId1, $faceId2);
        if (!$result) {
            return ['success' => false, 'error' => 'Gagal melakukan verifikasi wajah ke server AI.'];
        }

        return [
            'success' => true,
            'is_identical' => $result['isIdentical'],
            'confidence' => $result['confidence'],
            'message' => $result['isIdentical'] ? 'Wajah Cocok!' : 'Wajah Tidak Cocok.'
        ];
    }
}
