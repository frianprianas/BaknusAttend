<?php

namespace App\Filament\Widgets;

use App\Models\KehadiranSiswa;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentStudentAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];
    protected static ?string $heading = 'Kehadiran Siswa Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn() =>
                KehadiranSiswa::with('student')
                    ->whereDate('waktu_tap', Carbon::today())
                    ->latest('waktu_tap')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->default('–')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS'),
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
