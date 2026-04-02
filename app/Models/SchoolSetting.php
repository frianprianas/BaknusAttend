<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = ['name', 'lat', 'long', 'radius', 'is_reminder_active', 'reminder_masuk', 'reminder_pulang'];

    public static function getFirst()
    {
        return self::first();
    }
}
