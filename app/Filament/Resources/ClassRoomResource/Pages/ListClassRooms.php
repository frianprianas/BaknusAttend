<?php

namespace App\Filament\Resources\ClassRoomResource\Pages;

use App\Filament\Resources\ClassRoomResource;
use App\Models\ClassRoom;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;

class ListClassRooms extends ListRecords
{
    protected static string $resource = ClassRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('aturJadwalCepat')
                ->label('Atur Jadwal Cepat (Pekan Biasa / Ujian)')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->modalHeading('Pengaturan Jadwal Cepat Jam Kirim WA Kelas')
                ->modalDescription('Pilih preset jadwal untuk menyetel jam kirim WA seluruh kelas secara otomatis, baik untuk pekan sekolah normal maupun pekan ujian dengan perbedaan jam shift masuk (08:00, 09:00, 10:00).')
                ->modalSubmitActionLabel('Terapkan Jadwal ke Semua Kelas')
                ->form([
                    Forms\Components\Radio::make('mode_jadwal')
                        ->label('Pilih Pola Jadwal')
                        ->options([
                            'normal' => 'Pekan Normal: Seluruh tingkat (X, XI, XII) masuk pagi (Mulai jam 08:00)',
                            'ujian'  => 'Pekan Ujian: Tingkat X, XI, dan XII masuk bergelombang / shift berbeda',
                        ])
                        ->default('normal')
                        ->live()
                        ->required(),

                    Forms\Components\Grid::make(3)
                        ->visible(fn ($get) => $get('mode_jadwal') === 'ujian')
                        ->schema([
                            Forms\Components\TimePicker::make('jam_x')
                                ->label('Jam Kirim Tingkat X')
                                ->seconds(false)
                                ->default('08:30')
                                ->helperText('Shift 1: Siswa masuk jam 08:00')
                                ->required(),
                            Forms\Components\TimePicker::make('jam_xi')
                                ->label('Jam Kirim Tingkat XI')
                                ->seconds(false)
                                ->default('10:15')
                                ->helperText('Shift 2: Siswa masuk jam 09:30/10:00')
                                ->required(),
                            Forms\Components\TimePicker::make('jam_xii')
                                ->label('Jam Kirim Tingkat XII')
                                ->seconds(false)
                                ->default('11:30')
                                ->helperText('Shift 3: Siswa masuk jam 11:00')
                                ->required(),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->visible(fn ($get) => $get('mode_jadwal') === 'normal')
                        ->schema([
                            Forms\Components\TimePicker::make('jam_normal')
                                ->label('Jam Mulai Pengiriman')
                                ->seconds(false)
                                ->default('08:00')
                                ->required(),
                            Forms\Components\TextInput::make('jeda_normal')
                                ->label('Selisih Jeda Antar-Kelas')
                                ->numeric()
                                ->default(3)
                                ->suffix('Menit')
                                ->helperText('Setiap kelas dikirim berjenjang')
                                ->required(),
                        ]),
                ])
                ->action(function (array $data) {
                    $mode = $data['mode_jadwal'] ?? 'normal';
                    $classes = ClassRoom::orderBy('kelas')->get();
                    $count = 0;

                    if ($mode === 'ujian') {
                        // Pola Shift Ujian: Tingkat X, XI, XII
                        $groups = [
                            'X'   => ['start' => Carbon::parse($data['jam_x']),   'step' => 3, 'idx' => 0],
                            'XI'  => ['start' => Carbon::parse($data['jam_xi']),  'step' => 3, 'idx' => 0],
                            'XII' => ['start' => Carbon::parse($data['jam_xii']), 'step' => 3, 'idx' => 0],
                        ];

                        foreach ($classes as $c) {
                            $name = trim($c->kelas);
                            $groupKey = 'X';
                            if (str_starts_with($name, 'XII ') || $name === 'XII') {
                                $groupKey = 'XII';
                            } elseif (str_starts_with($name, 'XI ') || $name === 'XI') {
                                $groupKey = 'XI';
                            } elseif (str_starts_with($name, 'X ') || $name === 'X') {
                                $groupKey = 'X';
                            }

                            $grp = &$groups[$groupKey];
                            $scheduledTime = $grp['start']->copy()->addMinutes($grp['idx'] * $grp['step']);
                            $grp['idx']++;

                            $c->update([
                                'jam_kirim_wa' => $scheduledTime->format('H:i:00'),
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title('Jadwal Pekan Ujian Diterapkan 🎉')
                            ->body("Berhasil menerapkan jadwal shift ujian untuk {$count} kelas (Tingkat X mulai {$data['jam_x']}, XI mulai {$data['jam_xi']}, XII mulai {$data['jam_xii']}).")
                            ->success()
                            ->send();
                    } else {
                        // Pola Normal: Sebar seluruh kelas berurutan
                        $startTime = Carbon::parse($data['jam_normal']);
                        $step = (int) ($data['jeda_normal'] ?? 3);

                        foreach ($classes as $idx => $c) {
                            $scheduledTime = $startTime->copy()->addMinutes($idx * $step);
                            $c->update([
                                'jam_kirim_wa' => $scheduledTime->format('H:i:00'),
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title('Jadwal Pekan Normal Diterapkan 🎉')
                            ->body("Berhasil menerapkan jadwal pekan normal mulai jam {$data['jam_normal']} untuk seluruh {$count} kelas.")
                            ->success()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}

