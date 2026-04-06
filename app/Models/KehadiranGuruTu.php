<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KehadiranGuruTu extends Model
{
    protected $table = 'kehadiran_guru_tus';

    protected $fillable = [
        'nipy',
        'rfid_uid',
        'waktu_tap',
        'status',
        'keterangan',
        'lat',
        'long',
        'photo',
        'is_dinas_luar',
        'lokasi_dinas_luar',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nipy', 'nipy');
    }

    protected static function booted()
    {
        static::created(function ($kehadiran) {
            try {
                $user = User::where('nipy', $kehadiran->nipy)->orWhere('email', $kehadiran->nipy)->first();
                if (!$user) return;

                $data = [
                    'NIS' => $user->nipy ?? $user->email, // Use nipy as ID
                    'Nama' => $user->name,
                    'kelas' => '-',
                    'role' => 'guru',
                    'waktu_tap' => \Carbon\Carbon::parse($kehadiran->waktu_tap ?? now())->format('H:i:s'),
                    'status' => 'Masuk', 
                    'keterangan' => $kehadiran->keterangan ?? 'Hadir',
                ];

                \App\Jobs\SyncAttendanceToBaknusDrive::dispatch($data);
            } catch (\Exception $e) {
                \Log::error('BaknusDrive Queue Error (Guru): ' . $e->getMessage());
            }
        });
    }
}
