<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranGuruTuResource\Pages;
use App\Filament\Resources\KehadiranGuruTuResource\RelationManagers;
use App\Models\KehadiranGuruTu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KehadiranGuruTuResource extends Resource
{
    protected static ?string $model = KehadiranGuruTu::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Laporan Kehadiran';
    protected static ?string $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Admin', 'Guru', 'TU']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role !== 'Admin') {
            $query->where(function ($q) use ($user) {
                $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nipy')
                    ->label('NIPY')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('rfid_uid')
                    ->label('RFID UID')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('waktu_tap')
                    ->label('Waktu Tap')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                        'Dinas Luar' => 'Dinas Luar',
                    ])
                    ->required()
                    ->default('Hadir'),
                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/user-placeholder.png'))
                    ->disk('public')
                    ->visibility(fn () => true)
                    ->action(
                        Tables\Actions\Action::make('view_photo')
                            ->modalHeading('Foto Presensi Guru/TU')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                            ->modalContent(fn ($record) => view('filament.components.image-modal', [
                                'url' => $record->photo ? asset('storage/' . $record->photo) : url('/images/user-placeholder.png')
                            ]))
                    ),

                // Kolom Nama hanya terlihat oleh Admin
                Tables\Columns\TextColumn::make('pegawai_name')
                    ->label('Nama Pegawai')
                    ->getStateUsing(function ($record) {
                        $user = \App\Models\User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                        return $user ? $user->name : $record->nipy;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('nipy', 'like', "%{$search}%");
                    })
                    ->visible(fn () => auth()->user()?->role === 'Admin'),

                Tables\Columns\TextColumn::make('waktu_tap_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->getStateUsing(fn($record) => $record->waktu_tap)
                    ->sortable(),

                Tables\Columns\TextColumn::make('waktu_tap')
                    ->label('Jam')
                    ->dateTime('H:i')
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Izin' => 'info',
                        'Sakit' => 'warning',
                        'Dinas Luar' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->visible(fn () => auth()->user()?->role === 'Admin'),
            ])
            ->defaultSort('waktu_tap', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('waktu_tap')
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
            ->actions(auth()->user()?->role === 'Admin' ? [
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ] : [])
            ->bulkActions(auth()->user()?->role === 'Admin' ? [
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ] : [])
            ->paginated(true)
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranGuruTus::route('/'),
        ];
    }
}
