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

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Kehadiran Guru/TU';
    protected static ?string $navigationGroup = 'Laporan Presensi';

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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY')
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
            'index' => Pages\ManageKehadiranGuruTus::route('/'),
        ];
    }
}
