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
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nipy', 'nipy');
    }
}
