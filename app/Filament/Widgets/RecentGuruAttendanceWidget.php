<?php

namespace App\Filament\Widgets;

use App\Models\KehadiranGuruTu;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentGuruAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    protected static ?string $heading = 'Kehadiran Guru & TU Hari Ini';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn() =>
                KehadiranGuruTu::latest('waktu_tap')
                    ->whereDate('waktu_tap', Carbon::today())
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Hadir',
                        'warning' => 'Terlambat',
                        'danger' => 'Alpa',
                        'info' => ['Izin', 'Sakit'],
                    ]),
                Tables\Columns\TextColumn::make('waktu_tap')
                    ->label('Waktu')
                    ->dateTime('H:i:s')
                    ->timezone('Asia/Jakarta'),
            ])
            ->paginated(false);
    }
}
