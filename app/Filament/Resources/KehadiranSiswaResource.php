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

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Kehadiran Siswa';
    protected static ?string $navigationGroup = 'Laporan Presensi';

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
                    ->label('Foto Selfie')
                    ->circular()
                    ->defaultImageUrl(url('/images/user-placeholder.png'))
                    ->action(
                        Tables\Actions\Action::make('view_photo')
                            ->modalHeading('Foto Presensi')
                            ->modalContent(fn ($record) => view('components.image-modal', ['imageUrl' => $record->photo ? asset('storage/' . $record->photo) : null]))
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                    ),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('rfid_uid')
                    ->label('RFID UID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('waktu_tap')
                    ->label('Jam Tap')
                    ->dateTime('H:i:s')
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
                Tables\Columns\TextColumn::make('sumber_presensi')
                    ->label('Alat Presensi')
                    ->getStateUsing(function ($record) {
                        if (str_contains(strtolower($record->keterangan ?? ''), 'mandiri')) {
                            return 'HP / GPS';
                        } elseif (str_contains(strtolower($record->keterangan ?? ''), 'rfid')) {
                            return 'Mesin RFID';
                        }
                        return 'Manual';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'HP / GPS' => 'success',
                        'Mesin RFID' => 'info',
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
            ->defaultSort('waktu_tap', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranSiswas::route('/'),
        ];
    }
}
