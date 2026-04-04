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
        return $user && in_array($user->role, ['Admin', 'Guru', 'TU']);
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
            ->when(auth()->user()?->role !== 'Admin', function ($query) {
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
                        ->label('Tanggal')
                        ->date('d/m/y')
                        ->grow(false)
                        ->fontFamily('mono')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('pegawai_name')
                        ->label('Nama Pegawai')
                        ->getStateUsing(function ($record) {
                            $user = \App\Models\User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                            return $user ? $user->name : $record->nipy;
                        })
                        ->description(fn($record) => $record->nipy)
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
            ->actions([
                Tables\Actions\Action::make('view_photo')
                    ->label('Lihat Foto')
                    ->modalHeading('Detail Foto Presensi')
                    ->modalContent(fn ($arguments) => view('filament.components.image-modal', [
                        'url' => ($data = KehadiranGuruTu::find($arguments['id'] ?? null)) && $data->photo 
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
            'index' => Pages\ManageKehadiranGuruTus::route('/'),
        ];
    }
}
