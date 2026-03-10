<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['attendable_id', 'attendable_type', 'date', 'status', 'remarks'];

    public function attendable()
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::created(function ($attendance) {
            try {
                $attendable = $attendance->attendable; // Student or User
                if (!$attendable)
                    return;

                $driveUrl = env('BAKNUSDRIVE_URL', 'https://baknusdrive.smkbn666.sch.id') . '/api/attend/upload';
                $apiKey = env('BAKNUS_ATTEND_API_KEY', 'BAKNUS_ATTEND_SECRET');

                $role = 'siswa';
                $kelas = '-';
                $nis = '-';

                if ($attendance->attendable_type === \App\Models\Student::class) {
                    $role = 'siswa';
                    $kelas = $attendable->classRoom ? $attendable->classRoom->kelas : '-';
                    $nis = $attendable->nis ?? '-';
                } elseif ($attendance->attendable_type === \App\Models\User::class) {
                    $role = strtolower($attendable->role) === 'tu' ? 'TU' : 'guru';
                    $nis = $attendable->nipy ?? '-';
                }

                \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Attend-API-Key' => $apiKey
                ])->asForm()->post($driveUrl, [
                            'NIS' => $nis,
                            'Nama' => $attendable->name,
                            'kelas' => $kelas,
                            'role' => $role,
                            'waktu_tap' => \Carbon\Carbon::parse($attendance->created_at)->format('H:i:s'),
                            'status' => 'Masuk',
                            'keterangan' => $attendance->remarks ?? $attendance->status ?? 'Hadir',
                        ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('BaknusDrive Sync Error (Attendance manual): ' . $e->getMessage());
            }
        });
    }
}
