<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    protected $primaryKey = 'id_prodi';
    protected $fillable = ['program_studi'];

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class, 'id_prodi', 'id_prodi');
    }
}
