<?php

namespace App\Filament\Resources\MasterBiometrikGuruTuResource\Pages;

use App\Filament\Resources\MasterBiometrikGuruTuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterBiometrikGuruTu extends ListRecords
{
    protected static string $resource = MasterBiometrikGuruTuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Menu ini murni ringkasan
        ];
    }
}
