<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = ['name', 'lat', 'long', 'radius', 'is_reminder_active', 'reminder_masuk', 'reminder_pulang', 'is_ip_validation_active', 'allowed_ip_1', 'allowed_ip_2', 'allowed_ip_3', 'default_target_hari_kerja'];

    public static function getFirst()
    {
        return self::first();
    }
}
