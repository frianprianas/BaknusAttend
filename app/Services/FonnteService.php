<?php

namespace App\Services;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp melalui Fonnte Gateway.
     *
     * @param string|array $targets Nomor tujuan (bisa string koma atau array)
     * @param string $message Isi pesan
     * @param string|null $token Custom token Fonnte (opsional)
     * @return array
     */
    public static function sendMessage($targets, string $message, ?string $token = null): array
    {
        $setting = SchoolSetting::first();
        $apiToken = $token ?: ($setting?->fonnte_token ?: config('services.fonnte.token'));

        if (empty($apiToken)) {
            Log::warning('FonnteService: Token API Fonnte belum dikonfigurasi.');
            return [
                'success' => false,
                'message' => 'Token API Fonnte belum dikonfigurasi di pengaturan atau .env.',
            ];
        }

        // Normalisasi nomor tujuan
        if (is_array($targets)) {
            $targets = implode(',', $targets);
        }

        $cleanTargets = collect(explode(',', (string) $targets))
            ->map(fn($num) => trim($num))
            ->filter(fn($num) => !empty($num))
            ->unique()
            ->implode(',');

        if (empty($cleanTargets)) {
            Log::warning('FonnteService: Nomor tujuan WhatsApp kosong.');
            return [
                'success' => false,
                'message' => 'Nomor tujuan WhatsApp belum diisi.',
            ];
        }

        $apiUrl = config('services.fonnte.url', 'https://api.fonnte.com/send');

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiToken,
            ])
            ->timeout(25)
            ->post($apiUrl, [
                'target'      => $cleanTargets,
                'message'     => $message,
                'countryCode' => '62',
            ]);

            $result = $response->json();

            if ($response->successful() && ($result['status'] ?? false)) {
                Log::info("FonnteService: Pesan WhatsApp berhasil dikirim ke {$cleanTargets}");
                return [
                    'success'  => true,
                    'message'  => 'Pesan WhatsApp berhasil dikirim.',
                    'response' => $result,
                ];
            }

            $reason = $result['reason'] ?? $result['message'] ?? 'Gagal mengirim pesan via Fonnte.';
            Log::error("FonnteService: Gagal kirim WA ke {$cleanTargets}. Alasan: {$reason}");

            return [
                'success'  => false,
                'message'  => $reason,
                'response' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error("FonnteService Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway Fonnte error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek status koneksi device WhatsApp di Fonnte.
     */
    public static function checkDevice(?string $token = null): array
    {
        $setting = SchoolSetting::first();
        $apiToken = $token ?: ($setting?->fonnte_token ?: config('services.fonnte.token'));

        if (empty($apiToken)) {
            return ['status' => false, 'message' => 'Token API belum diatur.'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $apiToken])
                ->timeout(15)
                ->post('https://api.fonnte.com/device');

            return $response->json() ?? ['status' => false, 'message' => 'Tidak ada respon dari server Fonnte.'];
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
