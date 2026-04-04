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
                        // Coba pakai API alternatif yang lebih stabil
                        $response = \Illuminate\Support\Facades\Http::get("https://api-harilibur.vercel.app/api?year={$year}");
                        
                        if ($response->successful()) {
                            $holidays = $response->json();
                            
                            if (empty($holidays) || !is_array($holidays)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Data Kosong')
                                    ->body("Belum ada data hari libur nasional resmi untuk tahun {$year} di server pusat.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $count = 0;
                            foreach ($holidays as $h) {
                                // Struktur dari API-HariLibur: 'holiday_date' dan 'holiday_name'
                                $date = $h['holiday_date'] ?? $h['tanggal'] ?? null;
                                $name = $h['holiday_name'] ?? $h['keterangan'] ?? 'Libur Nasional';

                                if (!$date) continue;

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
                                ->title('Berhasil Impor')
                                ->body($count > 0 
                                    ? $count . ' hari libur nasional baru telah ditambahkan.' 
                                    : 'Semua hari libur nasional untuk tahun ' . $year . ' sudah ada di daftar.')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('API merespon dengan kode: ' . $response->status());
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Impor')
                            ->body('Terjadi kesalahan sinkronisasi: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make()
                ->label('Tambah Libur Baru'),
        ];
    }
}
