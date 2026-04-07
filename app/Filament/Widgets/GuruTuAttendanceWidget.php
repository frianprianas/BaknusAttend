<?php

namespace App\Filament\Widgets;

use App\Models\KehadiranGuruTu;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class GuruTuAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rekap Kehadiran Guru & TU — Hari Ini';

    protected static bool $isLazy = true;

    public function getHeading(): string
    {
        $user = auth()->user();
        if ($user && $user->role !== 'Admin') {
            return 'Rekap Kehadiran — ' . $user->name;
        }
        return 'Rekap Kehadiran Guru & TU – Hari Ini';
    }

    public function getDescription(): ?string
    {
        $user = auth()->user();
        if ($user && $user->role !== 'Admin') {
            return $user->email;
        }
        return null;
    }

    protected int|string|null $defaultTableRecordsPerPageSelectOption = 25;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(function () use ($today) {
                $query = KehadiranGuruTu::latest('waktu_tap')
                    ->whereDate('waktu_tap', $today);
                $user = auth()->user();
                if ($user && $user->role !== 'Admin') {
                    $query->where(function($q) use ($user) {
                        $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
                    });
                }
                return $query;
            })
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->disk('public')
                    ->size(35)
                    ->getStateUsing(function ($record) {
                        $isRfid = str_contains(strtolower($record->keterangan ?? ''), 'rfid');
                        if ($record->photo === 'rfid_placeholder' || ($isRfid && empty($record->photo))) {
                            return null; // Gunakan defaultImageUrl
                        }
                        return $record->photo;
                    })
                    ->defaultImageUrl(asset('images/rfid_placeholder.png'))
                    ->visibility(fn () => true),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Nama')
                    ->limit(20)
                    ->getStateUsing(function ($record) {
                        $user = User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                        return $user?->name ?? '–';
                    }),

                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY')
                    ->searchable()
                    ->hiddenFrom('md'),

                Tables\Columns\TextColumn::make('user_role')
                    ->label('Jabatan')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function ($record) {
                        $user = User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                        return $user?->role ?? '–';
                    })
                    ->hiddenFrom('md'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Terlambat' => 'warning',
                        'Alpa' => 'danger',
                        'Izin' => 'primary',
                        'Sakit' => 'info',
                        default => 'gray',
                    }),

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
                        $keterangan = strtolower($record->keterangan ?? '');
                        if (str_contains($keterangan, 'mandiri')) {
                            return 'HP / GPS';
                        } elseif (str_contains($keterangan, 'rfid')) {
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
                    ->searchable()
                    ->hiddenFrom('md'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make(),
                ])->visible(fn () => auth()->user()?->role === 'Admin'),
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
            ->paginated(true)
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->emptyStateHeading('Belum ada kehadiran hari ini')
            ->emptyStateDescription('Data akan muncul setelah guru/TU melakukan tap RFID.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
