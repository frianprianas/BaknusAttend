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

    protected static ?string $heading = 'Rekap Kehadiran Siswa Hari Ini';

    // Override default Filament widget (default-nya 10)
    protected int|string|null $defaultTableRecordsPerPageSelectOption = 25;

    // Widget bisa di-expand / di-collapse oleh user
    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                fn() =>
                KehadiranSiswa::with('student')
                    ->whereDate('waktu_tap', $today)
                    ->latest('waktu_tap')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->default('Data tidak ditemukan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),

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
                    ->label('Jam Tap')
                    ->dateTime('H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->default('–')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->keterangan),
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
