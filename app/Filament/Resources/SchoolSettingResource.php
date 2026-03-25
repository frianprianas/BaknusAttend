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

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Pengaturan Lokasi';
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
                        Forms\Components\Grid::make(3)
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('lat'),
                Tables\Columns\TextColumn::make('long'),
                Tables\Columns\TextColumn::make('radius')->suffix(' m'),
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
