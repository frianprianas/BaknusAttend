<?php

namespace App\Filament\Resources\KehadiranGuruTuMonthlyResource\Pages;

use App\Filament\Resources\KehadiranGuruTuMonthlyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKehadiranGuruTuMonthlies extends ManageRecords
{
    protected static string $resource = KehadiranGuruTuMonthlyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Bisa tambah export ke Excel di sini nanti Mas
        ];
    }
}
