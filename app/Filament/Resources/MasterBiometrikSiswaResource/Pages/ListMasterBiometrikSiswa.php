<?php

namespace App\Filament\Resources\MasterBiometrikSiswaResource\Pages;

use App\Filament\Resources\MasterBiometrikSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterBiometrikSiswa extends ListRecords
{
    protected static string $resource = MasterBiometrikSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Menu ini murni ringkasan, tidak ada create manual karena daftar dari device user
        ];
    }
}
