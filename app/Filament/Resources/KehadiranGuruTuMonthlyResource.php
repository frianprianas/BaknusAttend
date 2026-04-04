<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranGuruTuMonthlyResource\Pages;
use App\Models\User;
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
    protected static ?string $model = User::class;

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
            ->whereIn('role', ['Guru', 'TU'])
            ->orderBy('name', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->description(fn($record) => "ID: " . ($record->nipy ?? $record->email))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('statistik')
                    ->label('Statistik Kehadiran')
                    ->html()
                    ->getStateUsing(function($record, Tables\Table $table) {
                        try {
                            $bulan = request()->query('tableFilters')['bulan']['value'] ?? now()->format('m');
                            $tahun = request()->query('tableFilters')['tahun']['value'] ?? now()->format('Y');
                            
                            $month = (int) $bulan;
                            $year = (int) $tahun;

                            $service = new \App\Services\AttendanceService();
                            $activeDays = $service->getEffectiveWorkingDays($month, $year);
                            
                            $hadirCount = KehadiranGuruTu::where(function($q) use ($record) {
                                    $q->where('nipy', $record->nipy)->orWhere('nipy', $record->email);
                                })
                                ->whereMonth('waktu_tap', $month)
                                ->whereYear('waktu_tap', $year)
                                ->where('keterangan', 'like', '%Masuk%')
                                ->count();
                            
                            $persen = $activeDays > 0 ? round(($hadirCount / $activeDays) * 100) : 0;
                            $colorClass = $persen >= 80 ? 'bg-success-100 text-success-700 font-bold' : ($persen >= 50 ? 'bg-warning-100 text-warning-700 font-bold' : 'bg-danger-100 text-danger-700 font-bold');
                            
                            return "
                                <div class='flex flex-col gap-1'>
                                    <div class='flex items-center gap-1.5'>
                                        <span class='text-[10px] text-gray-500 uppercase'>Hari aktif bulan ini: <b class='text-gray-700'>{$activeDays} hari</b></span>
                                        <span class='text-[10px] font-bold text-gray-400'>|</span>
                                        <span class='text-[10px] text-success-600 uppercase'>Total Hadir: <b class='text-success-800'>{$hadirCount}x</b></span>
                                    </div>
                                    <div class='flex items-center gap-2'>
                                        <div class='w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200'>
                                            <div class='h-full " . ($persen >= 80 ? 'bg-success-500' : ($persen >= 50 ? 'bg-warning-500' : 'bg-danger-500')) . "' style='width: {$persen}%'></div>
                                        </div>
                                        <span class='text-xs {$colorClass} px-1.5 py-0.5 rounded'>{$persen}%</span>
                                    </div>
                                </div>
                            ";
                        } catch (\Exception $e) {
                            return "<span class='text-[10px] text-gray-400 italic'>Statistik belum tersedia...</span>";
                        }
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                        '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                    ])
                    ->query(fn (Builder $query) => $query) // SANGAT PENTING: Jangan ubah query tabel User
                    ->default(now()->format('m')),

                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function() {
                        $years = [];
                        $currentYear = (int) now()->year;
                        for ($i = $currentYear; $i >= $currentYear - 2; $i--) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->query(fn (Builder $query) => $query) // SANGAT PENTING: Jangan ubah query tabel User
                    ->default(now()->year),
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
