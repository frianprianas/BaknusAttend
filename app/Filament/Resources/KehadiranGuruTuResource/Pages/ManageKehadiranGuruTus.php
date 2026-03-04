<?php

namespace App\Filament\Resources\KehadiranGuruTuResource\Pages;

use App\Filament\Resources\KehadiranGuruTuResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKehadiranGuruTus extends ManageRecords
{
    protected static string $resource = KehadiranGuruTuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
