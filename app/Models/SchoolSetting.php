<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'name',
        'lat',
        'long',
        'radius',
        'is_reminder_active',
        'reminder_masuk',
        'reminder_pulang',
        'is_ip_validation_active',
        'allowed_ip_1',
        'allowed_ip_2',
        'allowed_ip_3',
        'allowed_ip_4',
        'allowed_ip_5',
        'allowed_ip_6',
        'default_target_hari_kerja',
        'is_wa_kepsek_active',
        'wa_kepsek_time',
        'wa_kepsek_numbers',
        'fonnte_token',
        'is_wa_wali_kelas_active',
        'wa_wali_kelas_time',
        'wa_delay_min_minutes',
        'wa_delay_max_minutes',
        'wa_delay_min_seconds',
        'wa_delay_max_seconds',
        'slide_show_guru',
        'slide_show_tu',
        'slide_show_kelas',
        'slide_min_students',
        'slide_excluded_roles',
        'slide_duration',
        'slide_selected_class_ids',
        'slide_selected_teacher_ids',
        'slide_selected_tu_ids',
    ];

    protected $casts = [
        'slide_selected_class_ids'   => 'array',
        'slide_selected_teacher_ids' => 'array',
        'slide_selected_tu_ids'      => 'array',
        'slide_show_guru'            => 'boolean',
        'slide_show_tu'              => 'boolean',
        'slide_show_kelas'           => 'boolean',
        'is_wa_kepsek_active'        => 'boolean',
        'is_wa_wali_kelas_active'    => 'boolean',
    ];

    public static function getFirst()
    {
        return self::first();
    }

    protected static function booted()
    {
        static::saving(function ($setting) {
            if (isset($setting->wa_delay_min_minutes)) {
                $setting->wa_delay_min_seconds = (int)$setting->wa_delay_min_minutes * 60;
            }
            if (isset($setting->wa_delay_max_minutes)) {
                $setting->wa_delay_max_seconds = (int)$setting->wa_delay_max_minutes * 60;
            }
        });
    }

    public function getWaDelayMinMinutesAttribute($value)
    {
        if ($value !== null) {
            return (int) $value;
        }
        if (!empty($this->attributes['wa_delay_min_seconds'])) {
            return max(1, (int) round($this->attributes['wa_delay_min_seconds'] / 60));
        }
        return 2;
    }

    public function getWaDelayMaxMinutesAttribute($value)
    {
        if ($value !== null) {
            return (int) $value;
        }
        if (!empty($this->attributes['wa_delay_max_seconds'])) {
            return max(2, (int) round($this->attributes['wa_delay_max_seconds'] / 60));
        }
        return 5;
    }
}
