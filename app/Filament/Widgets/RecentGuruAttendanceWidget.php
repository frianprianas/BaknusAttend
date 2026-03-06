<?php

namespace App\Filament\Widgets;

use App\Models\KehadiranGuruTu;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentGuruAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rekap Kehadiran Guru & TU Hari Ini';

    protected int|string|null $defaultTableRecordsPerPageSelectOption = 25;

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                fn() =>
                KehadiranGuruTu::latest('waktu_tap')
                    ->whereDate('waktu_tap', $today)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY')
                    ->searchable(),

                // Join ke tabel users berdasarkan nipy
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Nama')
                    ->getStateUsing(function ($record) {
                        $user = User::where('nipy', $record->nipy)->first();
                        return $user?->name ?? '–';
                    }),

                Tables\Columns\TextColumn::make('user_role')
                    ->label('Jabatan')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function ($record) {
                        $user = User::where('nipy', $record->nipy)->first();
                        return $user?->role ?? '–';
                    }),

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
                    ->limit(30),
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
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->emptyStateHeading('Belum ada kehadiran hari ini')
            ->emptyStateDescription('Data akan muncul setelah guru/TU melakukan tap RFID.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
