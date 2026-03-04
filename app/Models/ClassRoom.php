<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $fillable = ['kelas', 'id_prodi', 'nipy', 'km', 'wkm'];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'id_prodi', 'id_prodi');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
