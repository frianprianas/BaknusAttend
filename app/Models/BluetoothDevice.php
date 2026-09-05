<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BluetoothDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_name',
        'secret_key',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
    ];

    protected $casts = [
        'latitude'      => 'float',
        'longitude'     => 'float',
        'radius_meters' => 'integer',
        'is_active'     => 'boolean',
    ];

    /**
     * Scope untuk perangkat yang berstatus aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
