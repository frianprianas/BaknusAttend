<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassRoomResource\Pages;
use App\Filament\Resources\ClassRoomResource\RelationManagers;
use App\Models\ClassRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClassRoomResource extends Resource
{
    protected static ?string $model = ClassRoom::class;

    public static function canViewAny(): bool
    {
        return auth()->user() && in_array(auth()->user()->role, ['Admin', 'TU']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Ruang Kelas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_prodi')
                    ->label('Program Studi')
                    ->relationship('programStudi', 'program_studi')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('kelas')
                    ->label('Nama Kelas')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('nipy')
                    ->label('Wali Kelas')
                    ->options(fn() => \App\Models\User::whereIn('role', ['Guru', 'TU'])->pluck('name', 'nipy'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Wali Kelas'),
                Forms\Components\TextInput::make('km')
                    ->label('Ketua Murid (KM)')
                    ->maxLength(255),
                Forms\Components\TextInput::make('wkm')
                    ->label('Wakil Ketua Murid (WKM)')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kelas')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('programStudi.program_studi')
                    ->label('Program Studi')
                    ->sortable()
                    ->searchable()
                    ->limit(20)
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY / Wali Kelas')
                    ->searchable()
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('km')
                    ->label('Ketua Murid')
                    ->searchable()
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('wkm')
                    ->label('Wakil KM')
                    ->searchable()
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassRooms::route('/'),
            'create' => Pages\CreateClassRoom::route('/create'),
            'edit' => Pages\EditClassRoom::route('/{record}/edit'),
        ];
    }
}
