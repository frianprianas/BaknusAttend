<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AwsFaceService
{
    protected RekognitionClient $client;

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
     * Detect if a face exists in the image and return it (True/False)
     */
    public function detectFace(string $imagePath): bool
    {
        try {
            // Kita pastikan path-nya benar
            if (!Storage::disk('public')->exists($imagePath)) {
                // Coba bersihkan path jika ada prefix 'public/' yang tidak sengaja terbawa
                $cleanPath = str_replace('public/', '', $imagePath);
                if (!Storage::disk('public')->exists($cleanPath)) {
                    Log::error("AwsFace: File not found at [{$imagePath}] or [{$cleanPath}]");
                    return false;
                }
                $imagePath = $cleanPath;
            }

            $imageData = Storage::disk('public')->get($imagePath);

            $result = $this->client->detectFaces([
                'Image' => [
                    'Bytes' => $imageData,
                ],
                'Attributes' => ['DEFAULT'],
            ]);

            return count($result['FaceDetails']) > 0;

        } catch (\Throwable $e) {
            Log::error("AwsFace detectFace Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compare two images (Selfie vs Master) using Amazon Rekognition
     * Returns ['success' => bool, 'is_identical' => bool, 'confidence' => float, 'error' => ?string]
     */
    public function compare(string $selfiePath, string $referencePath): array
    {
        try {
            // Bersihkan path
            $selfiePath = str_replace('public/', '', $selfiePath);
            $referencePath = str_replace('public/', '', $referencePath);

            if (!Storage::disk('public')->exists($selfiePath)) {
                return ['success' => false, 'error' => "Foto selfie tidak ditemukan: $selfiePath"];
            }
            if (!Storage::disk('public')->exists($referencePath)) {
                return ['success' => false, 'error' => "Foto master tidak ditemukan: $referencePath"];
            }

            $sourceImageBytes = Storage::disk('public')->get($referencePath); // Master
            $targetImageBytes = Storage::disk('public')->get($selfiePath);    // Selfie

            $result = $this->client->compareFaces([
                'SourceImage' => [
                    'Bytes' => $sourceImageBytes,
                ],
                'TargetImage' => [
                    'Bytes' => $targetImageBytes,
                ],
                'SimilarityThreshold' => 70, // Kita pakai threshold 70% agar aman
            ]);

            $matches = $result['FaceMatches'];

            if (count($matches) > 0) {
                return [
                    'success'      => true,
                    'is_identical' => true,
                    'confidence'   => (float) $matches[0]['Similarity'],
                    'message'      => 'Wajah Cocok!',
                ];
            }

            return [
                'success'      => true,
                'is_identical' => false,
                'confidence'   => 0,
                'message'      => 'Wajah Tidak Cocok.',
            ];

        } catch (\Throwable $e) {
            Log::error("AwsFace compare Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal memproses AI: ' . $e->getMessage()];
        }
    }
}
