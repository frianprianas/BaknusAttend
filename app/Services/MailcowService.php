<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\ProgramStudi;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MailcowService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('MAILCOW_URL', 'https://baknusmail.smkbn666.sch.id');
        $this->apiKey = env('MAILCOW_API_KEY', 'BAKNUS_ATTEND_SECRET');
    }

    public function syncUsers()
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->timeout(15)->get("{$this->baseUrl}/api/v1/get/mailbox/all");

        if (!$response->successful()) {
            Log::error('Gagal mengambil data mailbox dari Mailcow: ' . $response->body());
            return 0;
        }

        $mailboxes = $response->json();
        $syncedCount = 0;

        $defaultProdi = ProgramStudi::firstOrCreate(
            ['program_studi' => 'Belum Ditentukan'],
            ['id_prodi' => 1]
        );

        $defaultClass = ClassRoom::firstOrCreate(
            ['kelas' => 'Belum Ditentukan'],
            ['id_prodi' => $defaultProdi->id_prodi]
        );

        foreach ($mailboxes as $mailbox) {
            $email = $mailbox['username'] ?? null;
            if (!$email) continue;

            $fullName = $mailbox['name'] ?? 'No Name';
            $tags = $mailbox['tags'] ?? [];
            $comment = $mailbox['comment'] ?? '';

            // Mapping Role from Tag or Comment
            $role = $this->determineRole($tags, $comment, $email);

            // Update or Create User
            $existingUser = User::where('email', $email)->first();
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $fullName,
                    'role' => $role,
                    'password' => $existingUser ? $existingUser->password : Hash::make(Str::random(16)),
                ]
            );

            // Special handling for Student: PRESERVE EXISTING CLASS ROOM ID!
            if ($role === 'Siswa') {
                $nis = $this->extractNis($email, $comment);
                $existingStudent = Student::where('nis', $nis)->first();

                if ($existingStudent) {
                    // Update nama siswa tanpa menimpa/mereset kelas yang sudah diset!
                    $existingStudent->update([
                        'name' => $fullName,
                    ]);
                } else {
                    // Siswa baru: set default kelas
                    Student::create([
                        'nis' => $nis,
                        'name' => $fullName,
                        'class_room_id' => $defaultClass->id,
                    ]);
                }
            }

            $syncedCount++;
        }

        return $syncedCount;
    }

    public function syncSingleUser($email)
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->timeout(10)->get("{$this->baseUrl}/api/v1/get/mailbox/{$email}");

        if (!$response->successful() || str_contains($response->body(), 'error')) {
            return null;
        }

        $mailbox = $response->json();
        if (empty($mailbox) || !isset($mailbox['username']))
            return null;

        $fullName = $mailbox['name'] ?? 'No Name';
        $tags = $mailbox['tags'] ?? [];
        $comment = $mailbox['comment'] ?? '';
        $role = $this->determineRole($tags, $comment, $email);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $fullName,
                'role' => $role,
                'password' => Hash::make(Str::random(16)),
            ]
        );

        if ($role === 'Siswa') {
            $nis = $this->extractNis($email, $comment);
            $existingStudent = Student::where('nis', $nis)->first();

            if ($existingStudent) {
                // Jangan reset class_room_id jika siswa sudah ada!
                $existingStudent->update([
                    'name' => $fullName,
                ]);
            } else {
                $defaultProdi = ProgramStudi::firstOrCreate(
                    ['program_studi' => 'Belum Ditentukan'],
                    ['id_prodi' => 1]
                );

                $defaultClass = ClassRoom::firstOrCreate(
                    ['kelas' => 'Belum Ditentukan'],
                    ['id_prodi' => $defaultProdi->id_prodi]
                );

                Student::create([
                    'nis' => $nis,
                    'name' => $fullName,
                    'class_room_id' => $defaultClass->id,
                ]);
            }
        }

        return $user;
    }

    private function determineRole($tags, $comment, $email)
    {
        $tagsStr = is_array($tags) ? implode(' ', $tags) : (string)$tags;
        $allText = strtolower($tagsStr . ' ' . $comment . ' ' . $email);

        if (str_contains($allText, 'test') || str_contains($allText, 'penguji')) {
            return 'Test';
        }

        if (str_contains($allText, 'admin') || str_contains($allText, 'kepsek')) {
            return 'Admin';
        }

        if (str_contains($allText, 'guru') || str_contains($allText, 'pengajar') || str_contains($allText, 'teacher')) {
            return 'Guru';
        }

        if (str_contains($allText, 'tu') || str_contains($allText, 'staff') || str_contains($allText, 'staf')) {
            return 'TU';
        }

        // Cek pola angka NIS di email/username untuk siswa
        if (preg_match('/\d{6,}/', $email)) {
            return 'Siswa';
        }

        return 'Siswa'; // Fallback default
    }

    private function extractNis($email, $comment)
    {
        // 1. Cek dari comment jika ada pola NIS
        if (preg_match('/(\d{8,14})/', $comment, $matches)) {
            return $matches[1];
        }

        // 2. Cek dari username email (misal: 242510119001@smk.baktinusantara666.sch.id -> 242510119001)
        $username = explode('@', $email)[0];
        if (preg_match('/^\d+$/', $username)) {
            return $username;
        }

        return $username;
    }
}
