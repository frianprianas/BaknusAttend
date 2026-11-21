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
            Actions\Action::make('tambahLiburInternal')
                ->label('Tambah Libur Internal Sekolah')
                ->icon('heroicon-o-academic-cap')
                ->color('warning')
                ->modalHeading('Tetapkan Hari Libur Internal Sekolah')
                ->modalDescription('Admin dapat menetapkan tanggal tertentu atau rentang beberapa hari sebagai libur internal sekolah (misal: Libur Semester, Classmeeting, dll.). Selama tanggal libur ini, pesan WhatsApp otomatis TIDAK akan dikirim.')
                ->modalSubmitActionLabel('Simpan Hari Libur Internal')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Keterangan Libur Internal')
                        ->placeholder('Contoh: Libur Akhir Semester Ganjil, Classmeeting, Libur Awal Ramadhan')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\Grid::make(2)
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('start_date')
                                ->label('Tanggal Mulai Libur')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()),
                            \Filament\Forms\Components\DatePicker::make('end_date')
                                ->label('Tanggal Selesai (Sampai Dengan)')
                                ->helperText('Kosongkan jika libur hanya 1 hari')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                        ]),
                    \Filament\Forms\Components\Toggle::make('exclude_weekends')
                        ->label('Lewati Akhir Pekan (Sabtu & Minggu)')
                        ->helperText('Sabtu & Minggu sudah otomatis libur, sehingga tidak perlu diduplikasi.')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $startDate = \Carbon\Carbon::parse($data['start_date']);
                    $endDate = !empty($data['end_date']) ? \Carbon\Carbon::parse($data['end_date']) : $startDate->copy();
                    if ($endDate->lt($startDate)) {
                        $temp = $startDate;
                        $startDate = $endDate;
                        $endDate = $temp;
                    }
                    $excludeWeekends = $data['exclude_weekends'] ?? true;
                    $name = trim($data['name']);

                    $count = 0;
                    $curr = $startDate->copy();
                    while ($curr->lte($endDate)) {
                        if ($excludeWeekends && $curr->isWeekend()) {
                            $curr->addDay();
                            continue;
                        }
                        $dateStr = $curr->toDateString();
                        $existing = \App\Models\Holiday::where('holiday_date', $dateStr)->first();
                        if (!$existing) {
                            \App\Models\Holiday::create([
                                'holiday_date' => $dateStr,
                                'name'         => $name,
                                'tipe'         => 'Internal Sekolah',
                            ]);
                            $count++;
                        } else {
                            $existing->update([
                                'name' => $name,
                                'tipe' => 'Internal Sekolah',
                            ]);
                            $count++;
                        }
                        $curr->addDay();
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Libur Internal Sekolah Berhasil Ditetapkan 🎉')
                        ->body("Sebanyak {$count} hari berhasil didaftarkan sebagai Libur Internal Sekolah: \"{$name}\". Pada tanggal tersebut pesan WA otomatis TIDAK akan dikirim.")
                        ->success()
                        ->send();
                }),
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

                                $exists = \App\Models\Holiday::where('holiday_date', $date)->first();
                                
                                if (!$exists) {
                                    \App\Models\Holiday::create([
                                        'holiday_date' => $date,
                                        'name' => $name,
                                        'tipe' => 'Nasional',
                                    ]);
                                    $count++;
                                } else {
                                    if (empty($exists->tipe)) {
                                        $exists->update(['tipe' => 'Nasional']);
                                    }
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
                ->label('Tambah 1 Hari Libur Manual'),
        ];
    }
}
