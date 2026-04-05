<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranGuruTuResource\Pages;
use App\Models\KehadiranGuruTu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class KehadiranGuruTuResource extends Resource
{
    protected static ?string $model = KehadiranGuruTu::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Laporan Guru/TU';
    protected static ?string $navigationGroup = 'Laporan';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['Admin', 'Guru', 'TU']);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'Admin' || $user->is_kepsek || in_array($user->role, ['Guru', 'TU']));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                DB::raw('MIN(id) as id'),
                'nipy',
                DB::raw('DATE(waktu_tap) as tanggal'),
                DB::raw('MAX(status) as status'),
            ])
            ->groupBy('nipy', DB::raw('DATE(waktu_tap)'))
            ->when(!(auth()->user()?->role === 'Admin' || auth()->user()?->is_kepsek), function ($query) {
                $user = auth()->user();
                $query->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
            })
            ->orderBy('tanggal', 'desc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('tanggal')
                        ->label('Tanggal & Statistik')
                        ->html()
                        ->getStateUsing(function($record) {
                            $date = Carbon::parse($record->tanggal);
                            $formattedDate = $date->translatedFormat('l, j F Y');
                            
                            $service = new \App\Services\AttendanceService();
                            $activeDays = $service->getEffectiveWorkingDays($date->month, $date->year);
                            
                            $hadirCount = KehadiranGuruTu::where('nipy', $record->nipy)
                                ->whereMonth('waktu_tap', $date->month)
                                ->whereYear('waktu_tap', $date->year)
                                ->where('keterangan', 'like', '%Masuk%')
                                ->count();
                            
                            $persen = $activeDays > 0 ? round(($hadirCount / $activeDays) * 100) : 0;
                            
                            return "
                                <div class='flex flex-col gap-0.5'>
                                    <span class='text-sm font-bold text-primary-600 dark:text-primary-400'>{$formattedDate}</span>
                                    <div class='flex items-center gap-1.5'>
                                        <span class='text-[10px] px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-md border border-gray-200 dark:border-gray-700 font-medium'>Aktif: {$activeDays}</span>
                                        <span class='text-[10px] px-1.5 py-0.5 bg-success-50 dark:bg-success-900/30 text-success-700 dark:text-success-400 rounded-md border border-success-100 dark:border-success-800 font-bold'>Hadir: {$hadirCount}</span>
                                        <span class='text-[10px] px-1.5 py-0.5 bg-info-50 dark:bg-info-900/30 text-info-700 dark:text-info-400 rounded-md border border-info-100 dark:border-info-800 font-black'>{$persen}%</span>
                                    </div>
                                </div>
                            ";
                        })
                        ->grow(false)
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('pegawai_name')
                        ->label('Nama Pegawai')
                        ->getStateUsing(function ($record) {
                            $user = \App\Models\User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                            return $user ? $user->name : $record->nipy;
                        })
                        ->description(fn($record) => "ID: " . $record->nipy)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where('nipy', 'like', "%{$search}%");
                        }),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\ViewColumn::make('masuk')
                            ->view('filament.tables.columns.attendance-session')
                            ->viewData([
                                'isMasuk' => true,
                                'label' => 'Masuk',
                                'modelClass' => KehadiranGuruTu::class
                            ]),

                        Tables\Columns\ViewColumn::make('pulang')
                            ->view('filament.tables.columns.attendance-session')
                            ->viewData([
                                'isMasuk' => false,
                                'label' => 'Pulang',
                                'modelClass' => KehadiranGuruTu::class
                            ]),
                    ])->space(1),

                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'Hadir' => 'success',
                            'Izin' => 'info',
                            'Sakit' => 'warning',
                            'Dinas Luar' => 'primary',
                            default => 'gray',
                        })
                        ->grow(false),
                ])->from('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tanggal')
                    ->label('Periode')
                    ->options([
                        'today'   => 'Hari Ini',
                        'week'    => 'Minggu Ini',
                        'month'   => 'Bulan Ini',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('waktu_tap', now()),
                            'week'  => $query->whereBetween('waktu_tap', [now()->startOfWeek(), now()->endOfWeek()]),
                            'month' => $query->whereMonth('waktu_tap', now()->month)->whereYear('waktu_tap', now()->year),
                            default => $query,
                        };
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated(true)
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranGuruTus::route('/'),
        ];
    }
}
