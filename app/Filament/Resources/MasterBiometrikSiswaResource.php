<?php

namespace App\Filament\Resources;

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
    protected static ?string $navigationIcon = 'heroicon-o-face-id'; // Membutuhkan heroicons-o di v3
    
    // Kita letakan di posisi awal grup
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool => false;

    public static function table(Table $table): Table
    {
        return $table
            ->query(Student::query()->whereNotNull('face_reference'))
            ->columns([
                Tables\Columns\ImageColumn::make('face_reference')
                    ->label('Foto Patokan')
                    ->circular()
                    ->disk('public')
                    // Memastikan foto besar dan jelas
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
