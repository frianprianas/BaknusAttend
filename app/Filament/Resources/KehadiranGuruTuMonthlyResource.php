<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranGuruTuMonthlyResource\Pages;
use App\Models\KehadiranGuruTu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class KehadiranGuruTuMonthlyResource extends Resource
{
    protected static ?string $model = KehadiranGuruTu::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Rekap Bulanan Guru/TU';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                DB::raw('MIN(id) as id'),
                'nipy',
                DB::raw("DATE_FORMAT(waktu_tap, '%Y-%m') as bulan_tahun"),
            ])
            ->groupBy('nipy', DB::raw("DATE_FORMAT(waktu_tap, '%Y-%m')"))
            ->orderBy('bulan_tahun', 'desc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bulan_tahun')
                    ->label('Periode')
                    ->getStateUsing(fn($record) => Carbon::parse($record->bulan_tahun . '-01')->translatedFormat('F Y'))
                    ->weight('bold')
                    ->color('primary')
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

                Tables\Columns\TextColumn::make('statistik')
                    ->label('Statistik Kehadiran')
                    ->html()
                    ->getStateUsing(function($record) {
                        $date = Carbon::parse($record->bulan_tahun . '-01');
                        $month = $date->month;
                        $year = $date->year;
                        
                        $service = new \App\Services\AttendanceService();
                        $activeDays = $service->getEffectiveWorkingDays($month, $year);
                        
                        $hadirCount = KehadiranGuruTu::where('nipy', $record->nipy)
                            ->whereMonth('waktu_tap', $month)
                            ->whereYear('waktu_tap', $year)
                            ->where('keterangan', 'like', '%Masuk%')
                            ->count();
                        
                        $persen = $activeDays > 0 ? round(($hadirCount / $activeDays) * 100) : 0;
                        
                        return "
                            <div class='flex items-center gap-2'>
                                <span class='px-2 py-0.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold border border-gray-200'>Aktif: {$activeDays}</span>
                                <span class='px-2 py-0.5 bg-success-50 text-success-700 rounded-lg text-xs font-bold border border-success-100'>Hadir: {$hadirCount}</span>
                                <span class='px-2 py-0.5 bg-info-50 text-info-700 rounded-lg text-xs font-bold border border-info-100'>{$persen}%</span>
                            </div>
                        ";
                    }),

                Tables\Columns\ProgressBarColumn::make('progress')
                    ->label('Grafik')
                    ->getStateUsing(function($record) {
                        $date = Carbon::parse($record->bulan_tahun . '-01');
                        $service = new \App\Services\AttendanceService();
                        $activeDays = $service->getEffectiveWorkingDays($date->month, $date->year);
                        $hadirCount = KehadiranGuruTu::where('nipy', $record->nipy)
                            ->whereMonth('waktu_tap', $date->month)
                            ->whereYear('waktu_tap', $date->year)
                            ->where('keterangan', 'like', '%Masuk%')
                            ->count();
                        
                        return $activeDays > 0 ? ($hadirCount / $activeDays) : 0;
                    })
                    ->color(fn($state) => $state >= 0.8 ? 'success' : ($state >= 0.5 ? 'warning' : 'danger')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Filter Bulan')
                    ->options(function() {
                        $months = [];
                        for ($i = 0; $i < 12; $i++) {
                            $m = now()->subMonths($i);
                            $months[$m->format('Y-m')] = $m->translatedFormat('F Y');
                        }
                        return $months;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->where(DB::raw("DATE_FORMAT(waktu_tap, '%Y-%m')"), $data['value']);
                    })
                    ->default(now()->format('Y-m')),
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated(true)
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranGuruTuMonthlies::route('/'),
        ];
    }
}
