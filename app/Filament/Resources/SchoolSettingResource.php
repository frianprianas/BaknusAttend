<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolSettingResource\Pages;
use App\Models\SchoolSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolSettingResource extends Resource
{
    protected static ?string $model = SchoolSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Sekolah';
    protected static ?string $navigationGroup = 'Sistem';

    public static function canViewAny(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Titik Lokasi Absensi')
                    ->description('Tentukan koordinat pusat sekolah dan radius toleransi (meter).')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required(),
                        Forms\Components\Grid::make()->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->required()
                                    ->step('0.00000001'),
                                Forms\Components\TextInput::make('long')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->required()
                                    ->step('0.00000001'),
                                Forms\Components\TextInput::make('radius')
                                    ->label('Radius (Meter)')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Notifikasi Pengingat Otomatis')
                    ->description('Atur jam berapa pesan pengingat dikirim ke HP Guru (Senin - Jumat) jika belum absen.')
                    ->schema([
                        Forms\Components\Toggle::make('is_reminder_active')
                            ->label('Aktifkan Notifikasi Pengingat')
                            ->default(true),
                        Forms\Components\Grid::make()->columns(2)
                            ->schema([
                                Forms\Components\TimePicker::make('reminder_masuk')
                                    ->label('Pengingat Masuk')
                                    ->default('08:00:00')
                                    ->required(),
                                Forms\Components\TimePicker::make('reminder_pulang')
                                    ->label('Pengingat Pulang')
                                    ->default('15:00:00')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('radius')->suffix(' m'),
                Tables\Columns\IconColumn::make('is_reminder_active')->boolean()->label('Pengingat Aktif'),
                Tables\Columns\TextColumn::make('reminder_masuk')->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('reminder_pulang')->label('Jam Pulang'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchoolSettings::route('/'),
            'create' => Pages\CreateSchoolSetting::route('/create'),
            'edit' => Pages\EditSchoolSetting::route('/{record}/edit'),
        ];
    }
}
