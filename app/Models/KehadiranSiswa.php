<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KehadiranSiswa extends Model
{
    protected $table = 'kehadiran_siswas';

    protected $fillable = [
        'nis',
        'rfid_uid',
        'waktu_tap',
        'status',
        'keterangan',
        'lat',
        'long',
        'photo',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'nis', 'nis');
    }

    protected static function booted()
    {
        static::created(function ($kehadiran) {
            try {
                $student = \App\Models\Student::with('classRoom')->where('nis', $kehadiran->nis)->first();
                if (!$student)
                    return;

                $driveUrl = env('BAKNUSDRIVE_URL', 'https://baknusdrive.smkbn666.sch.id') . '/api/attend/upload';
                $apiKey = env('BAKNUS_ATTEND_API_KEY', 'BAKNUS_ATTEND_SECRET');

                \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Attend-API-Key' => $apiKey
                ])->asForm()->post($driveUrl, [
                            'NIS' => $student->nis,
                            'Nama' => $student->name,
                            'kelas' => $student->classRoom ? $student->classRoom->kelas : '-',
                            'role' => 'siswa',
                            'waktu_tap' => \Carbon\Carbon::parse($kehadiran->waktu_tap ?? now())->format('H:i:s'),
                            'status' => 'Masuk',
                            'keterangan' => $kehadiran->keterangan ?? $kehadiran->status ?? 'Hadir',
                        ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('BaknusDrive Sync Error (KehadiranSiswa manual): ' . $e->getMessage());
            }
        });
    }
}
