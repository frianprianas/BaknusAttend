<?php

namespace App\Filament\Widgets;

use App\Models\KehadiranSiswa;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentStudentAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rekap Kehadiran';

    public function getHeading(): string
    {
        $user = auth()->user();
        if ($user && $user->role === 'Siswa') {
            return 'Rekap Kehadiran — ' . $user->name;
        }
        return 'Rekap Kehadiran Siswa – Hari Ini';
    }

    public function getDescription(): ?string
    {
        $user = auth()->user();
        if ($user && $user->role === 'Siswa') {
            return $user->email;
        }
        return null;
    }

    // Override default Filament widget (default-nya 10)
    protected int|string|null $defaultTableRecordsPerPageSelectOption = 25;

    // Widget bisa di-expand / di-collapse oleh user
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user?->role === 'Admin' || $user?->role === 'Siswa';
    }

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(function () use ($today) {
                $query = KehadiranSiswa::with('student')
                    ->whereDate('waktu_tap', $today)
                    ->latest('waktu_tap');
                
                $user = auth()->user();
                if ($user && $user->role === 'Siswa') {
                    $student = \App\Models\Student::where('email', $user->email)->first();
                    if ($student) {
                        $query->where('nis', $student->nis);
                    } else {
                        // Jika bukan admin dan tidak ada data siswa terhubung, kosongkan hasil
                        $query->where('nis', 'none'); 
                    }
                }
                
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->default('Data tidak ditemukan')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->hiddenFrom('md'),

                Tables\Columns\TextColumn::make('student.classRoom.kelas')
                    ->label('Kelas')
                    ->default('–')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Hadir',
                        'warning' => 'Terlambat',
                        'danger' => 'Alpa',
                        'primary' => 'Izin',
                        'info' => 'Sakit',
                    ]),

                Tables\Columns\TextColumn::make('waktu_tap')
                    ->label('Jam')
                    ->dateTime('H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipe_absen')
                    ->label('Tipe')
                    ->getStateUsing(function ($record) {
                        if (str_contains(strtolower($record->keterangan ?? ''), 'masuk')) return 'Masuk';
                        if (str_contains(strtolower($record->keterangan ?? ''), 'pulang')) return 'Pulang';
                        return 'Masuk';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Masuk' => 'success',
                        'Pulang' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sumber_presensi')
                    ->label('Alat Presensi')
                    ->getStateUsing(function ($record) {
                        if (str_contains(strtolower($record->keterangan), 'mandiri')) {
                            return 'HP / GPS';
                        } elseif (str_contains(strtolower($record->keterangan), 'rfid')) {
                            return 'Mesin RFID';
                        }
                        return 'Manual';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'HP / GPS' => 'success',
                        'Mesin RFID' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Ket')
                    ->default('–')
                    ->limit(15)
                    ->tooltip(fn($record) => $record->keterangan)
                    ->searchable()
                    ->hiddenFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Terlambat' => 'Terlambat',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                        'Alpa' => 'Alpa',
                    ]),
            ])
            ->defaultSort('waktu_tap', 'desc')
            ->paginated([25, 50, 100, 'all'])
            ->striped()
            ->emptyStateHeading('Belum ada kehadiran hari ini')
            ->emptyStateDescription('Data akan muncul setelah siswa melakukan tap RFID.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
