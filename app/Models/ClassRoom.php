<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $fillable = [
        'kelas',
        'id_prodi',
        'nipy',
        'km',
        'wkm',
        'is_wa_active',
        'wa_wali_kelas',
        'jam_kirim_wa',
    ];

    protected $casts = [
        'is_wa_active' => 'boolean',
    ];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'id_prodi', 'id_prodi');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
