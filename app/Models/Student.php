<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['name', 'nis', 'class_room_id', 'rfid'];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function attendances()
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }
}
