<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'nipy',
        'password',
        'role',
        'rfid',
        'face_reference',
    ];

    public function attendances()
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Izinkan semua yang login untuk masuk ke dasbor (Filament v3 requirement)
        return true;
    }

    /**
     * Accessor untuk mengambil Avatar dari BaknusMail secara otomatis.
     */
    public function getAvatarUrlAttribute(): string
    {
        $cleanEmail = strtolower(trim($this->email ?? ''));
        return "https://baknusmail.smkbn666.sch.id/api/public/avatar/" . $cleanEmail;
    }
}
