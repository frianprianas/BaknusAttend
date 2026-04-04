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
        return parent::getEloquentQuery()
            ->select([
                DB::raw('MIN(id) as id'),
                'nis',
                DB::raw('DATE(waktu_tap) as tanggal'),
                DB::raw('MAX(status) as status'),
            ])
            ->groupBy('nis', DB::raw('DATE(waktu_tap)'))
            ->when(auth()->user()?->role === 'Siswa', function ($query) {
                $student = \App\Models\Student::where('email', auth()->user()->email)->first();
                $query->where('nis', $student ? $student->nis : 'none');
            })
            ->orderBy('tanggal', 'desc');
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
                        Tables\Columns\ViewColumn::make('masuk')
                            ->view('filament.tables.columns.attendance-session')
                            ->viewData([
                                'isMasuk' => true,
                                'label' => 'Masuk',
                                'modelClass' => KehadiranSiswa::class
                            ]),

                        Tables\Columns\ViewColumn::make('pulang')
                            ->view('filament.tables.columns.attendance-session')
                            ->viewData([
                                'isMasuk' => false,
                                'label' => 'Pulang',
                                'modelClass' => KehadiranSiswa::class
                            ]),
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
                        'url' => ($data = KehadiranSiswa::find($arguments['id'] ?? null)) && $data->photo 
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
