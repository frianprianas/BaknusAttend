<?php

namespace App\Filament\Resources\BluetoothDeviceResource\Pages;

use App\Filament\Resources\BluetoothDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBluetoothDevices extends ManageRecords
{
    protected static string $resource = BluetoothDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Perangkat Wemos')
                ->icon('heroicon-o-plus'),
        ];
    }
}
