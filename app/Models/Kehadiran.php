<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kehadiran extends Model
{
    use HasFactory;

    protected $table = 'kehadiran';

    protected $fillable = [
        'nis',
        'rfid',
        'waktu_tap',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'waktu_tap' => 'datetime',
    ];
}
