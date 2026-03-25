<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = ['name', 'lat', 'long', 'radius'];

    public static function getFirst()
    {
        return self::first();
    }
}
