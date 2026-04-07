<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncAttendanceToBaknusDrive implements ShouldQueue
{
    use Queueable;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            $driveUrl = env('BAKNUSDRIVE_URL', 'https://baknusdrive.smkbn666.sch.id') . '/api/attend/upload';
            $apiKey = env('BAKNUS_ATTEND_API_KEY', 'BAKNUS_ATTEND_SECRET');

            $response = Http::withHeaders([
                'X-Attend-API-Key' => $apiKey
            ])->timeout(5)->connectTimeout(3)->asForm()->post($driveUrl, $this->data);

            if ($response->failed()) {
                Log::error('BaknusDrive Sync failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('BaknusDrive Sync Job Error: ' . $e->getMessage());
        }
    }
}
