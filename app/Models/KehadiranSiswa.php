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
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'nis', 'nis');
    }
}
