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
use Filament\Tables\Enums\FiltersLayout;
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
        return auth()->user()?->role === 'Admin' || auth()->user()?->is_kepsek;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin' || auth()->user()?->is_kepsek;
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
                Tables\Columns\TextColumn::make('face_reference')
                    ->label('Foto')
                    ->html()
                    ->getStateUsing(function($record) {
                        if (!$record->face_reference) {
                            return "<div class='flex items-center justify-center w-10 h-10 bg-gray-50 border border-dashed border-gray-300 rounded-lg'><span class='text-[7px] text-gray-400 italic text-center leading-tight'>Kosong</span></div>";
                        }
                        $url = asset('storage/' . $record->face_reference);
                        return "
                            <div x-data='{ open: false }' class='relative'>
                                <img 
                                    @click='open = true'
                                    src='{$url}' 
                                    class='w-10 h-10 rounded-lg object-cover ring-1 ring-white shadow-sm hover:scale-110 transition-transform cursor-zoom-in' 
                                />
                                <template x-teleport='body'>
                                    <div x-show='open' x-cloak @click='open = false' class='fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-8'>
                                        <div class='relative max-w-[280px]'>
                                            <img src='{$url}' class='w-full rounded-xl border-[4px] border-white shadow-xl' />
                                            <div class='absolute -top-3 -right-3 bg-red-500 text-white p-1.5 rounded-full shadow-lg'>
                                                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M6 18L18 6M6 6l12 12' stroke-linecap='round' stroke-linejoin='round' stroke-width='3'></path></svg>
                                            </div>
                                            <div class='text-center mt-2 text-white font-bold text-xs'>{$record->name}</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        ";
                    })
                    ->grow(false),

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
                            $formDate = $table->getLivewire()->tableFilters ?? [];
                            $selMonth = $formDate['bulan']['value'] ?? request()->query('tableFilters')['bulan']['value'] ?? now()->format('m');
                            $selYear = $formDate['tahun']['value'] ?? request()->query('tableFilters')['tahun']['value'] ?? now()->format('Y');
                            
                            $month = (int) $selMonth;
                            $year = (int) $selYear;

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
                                        <span class='text-[10px] text-gray-500 uppercase'>Aktif bulan ini: <b class='text-gray-700'>{$activeDays} hr</b></span>
                                        <span class='text-[10px] font-bold text-gray-400'>|</span>
                                        <span class='text-[10px] text-success-600 uppercase'>Hadir: <b class='text-success-800'>{$hadirCount}x</b></span>
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
                            return "<span class='text-[10px] text-gray-400 italic px-2 py-1 bg-gray-50 rounded border border-gray-200 shadow-sm'>Menghitung data...</span>";
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
                    ->query(fn (Builder $query) => $query)
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
                    ->query(fn (Builder $query) => $query)
                    ->default(now()->year),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
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
