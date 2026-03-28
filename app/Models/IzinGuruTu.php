<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class IzinGuruTu extends Model
{
    protected $table = 'izin_guru_tus';

    protected $fillable = [
        'nipy',
        'tanggal',
        'tipe',
        'alasan',
        'bukti',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nipy', 'nipy');
    }

    /**
     * Cek apakah Guru/TU memiliki izin aktif hari ini (Diajukan atau Disetujui)
     */
    public static function hasActiveIzinToday(string $nipy, string $email = null): bool
    {
        $query = static::whereDate('tanggal', Carbon::today())
            ->whereIn('status', ['Diajukan', 'Disetujui'])
            ->where('nipy', $nipy);

        if ($email) {
            $query->orWhere(function ($q) use ($email) {
                $q->whereDate('tanggal', Carbon::today())
                  ->whereIn('status', ['Diajukan', 'Disetujui'])
                  ->where('nipy', $email);
            });
        }

        return $query->exists();
    }
}
