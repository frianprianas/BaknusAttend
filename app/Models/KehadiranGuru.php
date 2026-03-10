<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KehadiranGuru extends Model
{
    use HasFactory;

    protected $table = 'kehadiran_guru';

    protected $fillable = [
        'nipy',
        'rfid',
        'waktu_tap',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'waktu_tap' => 'datetime',
    ];
}
