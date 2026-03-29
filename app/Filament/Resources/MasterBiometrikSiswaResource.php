<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterBiometrikSiswaResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MasterBiometrikSiswaResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationGroup = 'Master Biometrik';
    protected static ?string $navigationLabel = 'Master Foto Siswa';
    protected static ?string $label = 'Foto Master Siswa';
    protected static ?string $pluralLabel = 'Master Foto Siswa';
    protected static ?string $navigationIcon = 'heroicon-o-user-circle'; 
    
    // Kita letakan di posisi awal grup
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Siswa')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Nama Siswa')->readOnly(),
                        Forms\Components\TextInput::make('nis')->label('NIS')->readOnly(),
                        Forms\Components\FileUpload::make('face_reference')
                            ->label('Foto Master (Patokan)')
                            ->disk('public')
                            ->image()
                            ->readOnly(),
                    ])->columns(2),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('face_reference');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('face_reference')
                    ->label('Foto Patokan')
                    ->circular()
                    ->disk('public')
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classRoom.kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tgl Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_room_id')
                    ->label('Filter Kelas')
                    ->relationship('classRoom', 'kelas'),
            ])
            ->actions([
                Tables\Actions\Action::make('reset_master')
                    ->label('Reset Foto')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Foto Master?')
                    ->modalDescription('Ini akan menghapus foto patokan siswa ini. Siswa tersebut akan diminta mendaftarkan ulang wajahnya saat pertama kali presensi lagi.')
                    ->action(function (Student $record) {
                        if ($record->face_reference) {
                            Storage::disk('public')->delete($record->face_reference);
                        }
                        $record->update(['face_reference' => null]);
                    })
                    ->successNotificationTitle('Foto master berhasil di-reset'),
                
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('reset_selected')
                        ->label('Reset Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                if ($record->face_reference) {
                                    Storage::disk('public')->delete($record->face_reference);
                                }
                                $record->update(['face_reference' => null]);
                            }
                        })
                        ->successNotificationTitle('Foto terpilih berhasil di-reset'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterBiometrikSiswa::route('/'),
        ];
    }
}
