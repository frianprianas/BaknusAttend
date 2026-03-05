<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MailcowService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('mailcow.api_key');
        $this->baseUrl = rtrim(config('mailcow.url'), '/');
    }

    public function syncUsers()
    {
        // Berikan waktu lebih lama untuk sinkronisasi jika data banyak
        set_time_limit(180);

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->timeout(60)->get("{$this->baseUrl}/api/v1/get/mailbox/all");

        if (!$response->successful()) {
            throw new \Exception("Gagal menghubungi Mailcow API: " . $response->body());
        }

        $mailboxes = $response->json();
        $syncedCount = 0;

        // Ensure a default ProgramStudi exists to avoid foreign key failure
        $defaultProdi = ProgramStudi::firstOrCreate(
            ['program_studi' => 'Belum Ditentukan'],
            ['id_prodi' => 1]
        );

        // Default ClassRoom if not specified
        $defaultClass = ClassRoom::firstOrCreate(
            ['kelas' => 'Belum Ditentukan'],
            ['id_prodi' => $defaultProdi->id_prodi]
        );

        foreach ($mailboxes as $mailbox) {
            $email = $mailbox['username'] ?? null;
            $fullName = $mailbox['name'] ?? 'No Name';
            $tags = $mailbox['tags'] ?? [];
            $comment = $mailbox['comment'] ?? '';

            if (!$email)
                continue;

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

            // Special handling for Student
            if ($role === 'Siswa') {
                $nis = $this->extractNis($email, $comment);
                Student::updateOrCreate(
                    ['nis' => $nis],
                    [
                        'name' => $fullName,
                        'class_room_id' => $defaultClass->id,
                    ]
                );
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
            $defaultProdi = ProgramStudi::firstOrCreate(
                ['program_studi' => 'Belum Ditentukan'],
                ['id_prodi' => 1]
            );

            $defaultClass = ClassRoom::firstOrCreate(
                ['kelas' => 'Belum Ditentukan'],
                ['id_prodi' => $defaultProdi->id_prodi]
            );

            Student::updateOrCreate(
                ['nis' => $this->extractNis($email, $comment)],
                [
                    'name' => $fullName,
                    'class_room_id' => $defaultClass->id,
                ]
            );
        }

        return $user;
    }

    protected function determineRole($tags, $comment, $email)
    {
        $searchable = array_map('strtolower', array_merge((array) $tags, explode(' ', (string) $comment)));

        if (in_array('admin', $searchable))
            return 'Admin';
        if (in_array('guru', $searchable))
            return 'Guru';
        if (in_array('tu', $searchable))
            return 'TU';
        if (in_array('siswa', $searchable))
            return 'Siswa';

        // Deprecated checks if no tags found
        if (str_contains(strtolower((string) $comment), 'siswa'))
            return 'Siswa';
        if (preg_match('/^[0-9]+@/i', $email))
            return 'Siswa';

        return 'TU';
    }

    protected function extractNis($email, $comment)
    {
        // Try to get NIS from email name part (usually starts with numbers) or comment
        preg_match('/([0-9]{4,10})/', $email, $matches);
        if (isset($matches[1]))
            return $matches[1];

        preg_match('/NIS:\s*([0-9]+)/i', $comment, $matches);
        if (isset($matches[1]))
            return $matches[1];

        return explode('@', $email)[0]; // Fallback to email username part
    }
}
