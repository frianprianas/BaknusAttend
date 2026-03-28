<?php

namespace App\Filament\Resources\IzinGuruTuResource\Pages;

use App\Filament\Resources\IzinGuruTuResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageIzinGuruTus extends ManageRecords
{
    protected static string $resource = IzinGuruTuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
