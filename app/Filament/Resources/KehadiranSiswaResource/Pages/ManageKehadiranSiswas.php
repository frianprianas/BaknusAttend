<?php

namespace App\Filament\Resources\KehadiranSiswaResource\Pages;

use App\Filament\Resources\KehadiranSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageKehadiranSiswas extends ManageRecords
{
    protected static string $resource = KehadiranSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        $user = auth()->user();
        if ($user && $user->role === 'Siswa') {
            return 'Laporan Kehadiran — ' . $user->name;
        }
        return 'Laporan Kehadiran Siswa';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $user = auth()->user();
        if ($user && $user->role === 'Siswa') {
            return $user->email;
        }
        return null;
    }
}

