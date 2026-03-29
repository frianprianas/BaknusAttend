<?php

namespace App\Filament\Resources\KehadiranGuruTuResource\Pages;

use App\Filament\Resources\KehadiranGuruTuResource;
use App\Filament\Widgets\KehadiranCalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageKehadiranGuruTus extends ManageRecords
{
    protected static string $resource = KehadiranGuruTuResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            KehadiranCalendarWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        $user = auth()->user();
        if ($user && $user->role !== 'Admin') {
            return 'Laporan Kehadiran — ' . $user->name;
        }
        return 'Laporan Kehadiran Guru & TU';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $user = auth()->user();
        if ($user && $user->role !== 'Admin') {
            return $user->email;
        }
        return null;
    }
}

