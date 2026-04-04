<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranSiswaResource\Pages;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class KehadiranSiswaResource extends Resource
{
    protected static ?string $model = KehadiranSiswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Laporan Siswa';
    protected static ?string $navigationGroup = 'Laporan';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['Admin', 'Siswa']);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Admin', 'Siswa']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        $query->select([
            DB::raw('MIN(id) as id'),
            'nis',
            DB::raw('DATE(waktu_tap) as tanggal'),
            DB::raw('MAX(status) as status'),
        ])
        ->groupBy('nis', DB::raw('DATE(waktu_tap)'));

        if ($user && $user->role === 'Siswa') {
            $student = Student::where('email', $user->email)->first();
            if ($student) {
                $query->where('nis', $student->nis);
            } else {
                $query->where('nis', 'none');
            }
        }

        return $query->orderBy('tanggal', 'desc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => null,
                'sm' => 1,
            ])
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('tanggal')
                        ->label('Tanggal')
                        ->date('d/m/y')
                        ->grow(false)
                        ->fontFamily('mono')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('student_name')
                        ->label('Nama Siswa')
                        ->getStateUsing(function ($record) {
                            return $record->student?->name ?? $record->nis;
                        })
                        ->description(fn($record) => $record->nis)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where('nis', 'like', "%{$search}%");
                        }),

                    Tables\Columns\Layout\Stack::make([
                        // MASUK
                        Tables\Columns\TextColumn::make('masuk')
                            ->html()
                            ->getStateUsing(function ($record) {
                                $data = KehadiranSiswa::where('nis', $record->nis)
                                    ->whereDate('waktu_tap', $record->tanggal)
                                    ->where('keterangan', 'like', '%Masuk%')
                                    ->orderBy('waktu_tap', 'asc')
                                    ->first();

                                if (!$data) return "<div class='text-[10px] text-gray-300'>--- No CheckIn</div>";

                                $jam = Carbon::parse($data->waktu_tap)->format('H:i');
                                $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
                                
                                return "
                                    <div class='flex items-center gap-2 p-1 bg-success-50/50 rounded-lg border border-success-100 mb-1 w-full max-w-[150px]'>
                                        <div x-data=\"{ open: false }\" class='relative'>
                                            <img src='{$photoUrl}' @click='open = true' class='w-10 h-10 rounded-lg object-cover cursor-zoom-in ring-2 ring-white shadow-sm hover:scale-105 transition-all' />
                                            <div x-show='open' x-cloak 
                                                 x-transition:enter='transition ease-out duration-300'
                                                 x-transition:enter-start='opacity-0 scale-95'
                                                 x-transition:enter-end='opacity-100 scale-100'
                                                 x-transition:leave='transition ease-in duration-200'
                                                 x-transition:leave-start='opacity-100 scale-100'
                                                 x-transition:leave-end='opacity-0 scale-95'
                                                 @click.away='open = false' 
                                                 class='fixed inset-0 z-[999] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md'>
                                                <div class='relative max-w-[90vw] max-h-[90vh] flex items-center justify-center'>
                                                    <img src='{$photoUrl}' class='rounded-xl shadow-2xl border-[6px] border-white object-contain max-w-full max-h-full transition-transform' />
                                                    <button @click='open = false' class='absolute -top-12 -right-4 text-white hover:text-red-400 p-2 transition-colors'>
                                                        <svg class='w-10 h-10' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='flex flex-col'>
                                            <span class='text-xs font-bold text-success-700 leading-none'>{$jam}</span>
                                            <span class='text-[8px] text-success-500 uppercase font-bold tracking-tight'>Masuk</span>
                                        </div>
                                    </div>
                                ";
                            }),

                        // PULANG
                        Tables\Columns\TextColumn::make('pulang')
                            ->html()
                            ->getStateUsing(function ($record) {
                                $data = KehadiranSiswa::where('nis', $record->nis)
                                    ->whereDate('waktu_tap', $record->tanggal)
                                    ->where('keterangan', 'like', '%Pulang%')
                                    ->orderBy('waktu_tap', 'desc')
                                    ->first();

                                if (!$data) return "<div class='text-[10px] text-gray-300'>--- No CheckOut</div>";

                                $jam = Carbon::parse($data->waktu_tap)->format('H:i');
                                $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
                                
                                return "
                                    <div class='flex items-center gap-2 p-1 bg-amber-50/50 rounded-lg border border-amber-100 w-full max-w-[150px]'>
                                         <div x-data=\"{ open: false }\" class='relative'>
                                            <img src='{$photoUrl}' @click='open = true' class='w-10 h-10 rounded-lg object-cover cursor-zoom-in ring-2 ring-white shadow-sm hover:scale-105 transition-all' />
                                            <div x-show='open' x-cloak 
                                                 x-transition:enter='transition ease-out duration-300'
                                                 x-transition:enter-start='opacity-0 scale-95'
                                                 x-transition:enter-end='opacity-100 scale-100'
                                                 x-transition:leave='transition ease-in duration-200'
                                                 x-transition:leave-start='opacity-100 scale-100'
                                                 x-transition:leave-end='opacity-0 scale-95'
                                                 @click.away='open = false' 
                                                 class='fixed inset-0 z-[999] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md'>
                                                <div class='relative max-w-[90vw] max-h-[90vh] flex items-center justify-center'>
                                                    <img src='{$photoUrl}' class='rounded-xl shadow-2xl border-[6px] border-white object-contain max-w-full max-h-full transition-transform' />
                                                    <button @click='open = false' class='absolute -top-12 -right-4 text-white hover:text-red-400 p-2 transition-colors'>
                                                        <svg class='w-10 h-10' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='flex flex-col'>
                                            <span class='text-xs font-bold text-amber-700 leading-none'>{$jam}</span>
                                            <span class='text-[8px] text-amber-500 uppercase font-bold tracking-tight'>Pulang</span>
                                        </div>
                                    </div>
                                ";
                            }),
                    ])->space(1),

                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'Hadir' => 'success',
                            'Izin' => 'info',
                            'Sakit' => 'warning',
                            'Alpa' => 'danger',
                            'Terlambat' => 'warning',
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
            'index' => Pages\ManageKehadiranSiswas::route('/'),
        ];
    }
}
