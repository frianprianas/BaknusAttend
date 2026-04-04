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
                        // Pakai API referensi User yang terbukti work dan lengkap (thanks!)
                        $response = \Illuminate\Support\Facades\Http::get("https://libur.deno.dev/api");
                        
                        if ($response->successful()) {
                            $holidays = $response->json();
                            
                            if (empty($holidays) || !is_array($holidays)) {
                                throw new \Exception('Data dari API kosong atau tidak sesuai format.');
                            }

                            $count = 0;
                            foreach ($holidays as $h) {
                                $date = $h['date'] ?? null;
                                $name = $h['name'] ?? 'Libur Nasional';

                                if (!$date) continue;
                                
                                // Pastikan HANYA ambil data tahun berjalan
                                if (!str_starts_with($date, (string)$year)) continue;

                                $exists = \App\Models\Holiday::where('holiday_date', $date)->exists();
                                
                                if (!$exists) {
                                    \App\Models\Holiday::create([
                                        'holiday_date' => $date,
                                        'name' => $name,
                                    ]);
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Sinkronisasi Berhasil ✨')
                                ->body($count > 0 
                                    ? $count . ' hari libur nasional untuk tahun ' . $year . ' telah ditambahkan.' 
                                    : 'Kalender libur tahun ' . $year . ' Anda sudah sesuai dengan data pusat.')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Koneksi Gagal: ' . $response->status());
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Sinkronisasi')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make()
                ->label('Tambah Libur Baru'),
        ];
    }
}
