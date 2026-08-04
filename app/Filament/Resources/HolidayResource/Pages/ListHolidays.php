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
                ->label('Impor Libur Nasional')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Pilih Tahun')
                        ->options(function () {
                            $currentYear = now()->year;
                            return [
                                $currentYear - 1 => (string)($currentYear - 1),
                                $currentYear => (string)$currentYear,
                                $currentYear + 1 => (string)($currentYear + 1),
                            ];
                        })
                        ->default(now()->year)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    try {
                        $year = (int)$data['year'];
                        $response = \Illuminate\Support\Facades\Http::get("https://api-hari-libur.vercel.app/api", [
                            'year' => $year,
                        ]);
                        
                        if ($response->successful()) {
                            $res = $response->json();
                            $holidays = $res['data'] ?? [];
                            
                            if (empty($holidays) || !is_array($holidays)) {
                                throw new \Exception('Data dari API kosong atau tidak sesuai format.');
                            }

                            $count = 0;
                            foreach ($holidays as $h) {
                                $date = $h['date'] ?? null;
                                $name = $h['description'] ?? 'Libur Nasional';

                                if (!$date) continue;
                                
                                // Pastikan HANYA ambil data tahun terpilih
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
