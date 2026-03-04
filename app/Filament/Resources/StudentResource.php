<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    public static function canViewAny(): bool
    {
        return auth()->user() && in_array(auth()->user()->role, ['Admin', 'TU', 'Guru']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Daftar Siswa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nis')
                    ->label('NIS')
                    ->maxLength(255),
                Forms\Components\Select::make('class_room_id')
                    ->label('Kelas')
                    ->relationship('classRoom', 'kelas')
                    ->preload()
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('rfid')
                    ->label('Kode Kartu RFID')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Contoh: 0012345678')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('classRoom.kelas')
                    ->label('Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfid')
                    ->label('Kode RFID')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
