<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHolidays extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importNationalHolidays')
                ->label('Impor Libur Nasional ' . now()->year)
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $year = now()->year;
                        $response = \Illuminate\Support\Facades\Http::get("https://dayoffapi.vercel.app/api?year={$year}");
                        
                        if ($response->successful()) {
                            $holidays = $response->json();
                            $count = 0;
                            
                            foreach ($holidays as $h) {
                                // Cek apakah sudah ada di database berdasarkan tanggal
                                $exists = \App\Models\Holiday::where('holiday_date', $h['tanggal'])->exists();
                                
                                if (!$exists) {
                                    \App\Models\Holiday::create([
                                        'holiday_date' => $h['tanggal'],
                                        'name' => $h['keterangan'] ?? 'Libur Nasional',
                                    ]);
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Impor')
                                ->body($count . ' hari libur nasional baru telah ditambahkan.')
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Impor')
                            ->body('Terjadi kesalahan saat menghubungi server hari libur: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make()
                ->label('Tambah Libur Baru'),
        ];
    }
}
