<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramStudiResource\Pages;
use App\Filament\Resources\ProgramStudiResource\RelationManagers;
use App\Models\ProgramStudi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProgramStudiResource extends Resource
{
    protected static ?string $model = ProgramStudi::class;

    public static function canViewAny(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Program Studi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('program_studi')
                    ->label('Nama Program Studi / Jurusan')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('program_studi')
                    ->label('Program Studi')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('id_prodi')
                    ->label('ID Prodi')
                    ->sortable()
                    ->hiddenFrom('md'),
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
            'index' => Pages\ListProgramStudis::route('/'),
            'create' => Pages\CreateProgramStudi::route('/create'),
            'edit' => Pages\EditProgramStudi::route('/{record}/edit'),
        ];
    }
}
