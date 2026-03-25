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
                if (!$student) return;

                $data = [
                    'NIS' => $student->nis,
                    'Nama' => $student->name,
                    'kelas' => $student->classRoom ? $student->classRoom->kelas : '-',
                    'role' => 'siswa',
                    'waktu_tap' => \Carbon\Carbon::parse($kehadiran->waktu_tap ?? now())->format('H:i:s'),
                    'status' => 'Masuk', // Default Masuk via Tap RFID
                    'keterangan' => $kehadiran->keterangan ?? $kehadiran->status ?? 'Hadir',
                ];

                \App\Jobs\SyncAttendanceToBaknusDrive::dispatch($data);
            } catch (\Exception $e) {
                \Log::error('BaknusDrive Queue Error (Siswa): ' . $e->getMessage());
            }
        });
    }
}
