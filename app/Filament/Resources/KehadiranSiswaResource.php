<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranSiswaResource\Pages;
use App\Filament\Resources\KehadiranSiswaResource\RelationManagers;
use App\Models\KehadiranSiswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KehadiranSiswaResource extends Resource
{
    protected static ?string $model = KehadiranSiswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Laporan Kehadiran';
    protected static ?string $navigationGroup = null;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Admin', 'Siswa']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === 'Siswa') {
            $student = \App\Models\Student::where('email', $user->email)->first();
            if ($student) {
                $query->where('nis', $student->nis);
            } else {
                $query->where('nis', 'none');
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nis')
                    ->label('NIS')
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
                        'Alpa' => 'Alpa',
                        'Terlambat' => 'Terlambat',
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
                    // Memaksa foto muncul di HP
                    ->visibility(fn () => true),

                // Nama hanya terlihat oleh Admin
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()?->role === 'Admin'),

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
                        'Alpa' => 'danger',
                        'Terlambat' => 'warning',
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
            'index' => Pages\ManageKehadiranSiswas::route('/'),
        ];
    }
}
