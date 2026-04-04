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
                                    <div class='flex items-center gap-2 p-1 bg-success-50/50 rounded-lg border border-success-100 mb-1 w-full max-w-[140px]'>
                                        <div 
                                            @click=\"\$wire.mountTableAction('view_photo', {id: '{$data->id}'})\"
                                            class='flex-shrink-0 group relative cursor-zoom-in pointer-events-auto'
                                        >
                                            <img src='{$photoUrl}' class='w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm hover:scale-105 transition-all' />
                                            <div class='absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 rounded-lg flex items-center justify-center transition-opacity'>
                                                <svg class='w-4 h-4 text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'></path></svg>
                                            </div>
                                        </div>
                                        <div class='flex flex-col text-left'>
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
                                    <div class='flex items-center gap-2 p-1 bg-amber-50/50 rounded-lg border border-amber-100 w-full max-w-[140px]'>
                                        <div 
                                            @click=\"\$wire.mountTableAction('view_photo', {id: '{$data->id}'})\"
                                            class='flex-shrink-0 group relative cursor-zoom-in pointer-events-auto'
                                        >
                                            <img src='{$photoUrl}' class='w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm hover:scale-105 transition-all' />
                                            <div class='absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 rounded-lg flex items-center justify-center transition-opacity'>
                                                <svg class='w-4 h-4 text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'></path></svg>
                                            </div>
                                        </div>
                                        <div class='flex flex-col text-left'>
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
            ->actions([
                Tables\Actions\Action::make('view_photo')
                    ->label('Lihat Foto')
                    ->modalHeading('Detail Foto Presensi Siswa')
                    ->modalContent(fn ($arguments) => view('filament.components.image-modal', [
                        'url' => ($data = KehadiranSiswa::find($arguments['id'])) && $data->photo 
                             ? asset('storage/' . $data->photo) 
                             : url('/images/user-placeholder.png')
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraAttributes(['class' => 'hidden']),
            ])
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
