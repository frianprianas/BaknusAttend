<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelapseMusic extends Model
{
    protected $table = 'timelapse_musics';
    protected $fillable = ['title', 'filename', 'size'];

    public function getUrlAttribute()
    {
        return asset('timelapse_music/' . $this->filename);
    }
}
