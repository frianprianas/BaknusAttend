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
                    ->color('primary'),

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
                    ->label('Rincian Hadir')
                    ->html()
                    ->getStateUsing(function($record) {
                        $date = Carbon::parse($record->bulan_tahun . '-01');
                        $service = new \App\Services\AttendanceService();
                        $activeDays = $service->getEffectiveWorkingDays($date->month, $date->year);
                        $hadirCount = KehadiranGuruTu::where('nipy', $record->nipy)
                            ->whereMonth('waktu_tap', $date->month)
                            ->whereYear('waktu_tap', $date->year)
                            ->where('keterangan', 'like', '%Masuk%')
                            ->count();
                        
                        $persen = $activeDays > 0 ? round(($hadirCount / $activeDays) * 100) : 0;
                        $colorClass = $persen >= 80 ? 'bg-success-100 text-success-700' : ($persen >= 50 ? 'bg-warning-100 text-warning-700' : 'bg-danger-100 text-danger-700');
                        
                        return "
                            <div class='flex flex-col gap-1'>
                                <div class='flex items-center gap-1.5'>
                                    <span class='text-[10px] font-bold text-gray-500 uppercase'>Aktif: {$activeDays}</span>
                                    <span class='text-[10px] font-bold text-gray-400'>|</span>
                                    <span class='text-[10px] font-bold text-success-600 uppercase'>Hadir: {$hadirCount}</span>
                                </div>
                                <div class='flex items-center gap-2'>
                                    <div class='w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200'>
                                        <div class='h-full " . ($persen >= 80 ? 'bg-success-500' : ($persen >= 50 ? 'bg-warning-500' : 'bg-danger-500')) . "' style='width: {$persen}%'></div>
                                    </div>
                                    <span class='text-xs font-black {$colorClass} px-1.5 py-0.5 rounded'>{$persen}%</span>
                                </div>
                            </div>
                        ";
                    }),
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
