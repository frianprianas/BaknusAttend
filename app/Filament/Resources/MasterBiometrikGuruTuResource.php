<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MasterBiometrikGuruTuResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Master Biometrik';
    protected static ?string $navigationLabel = 'Master Foto Guru & TU';
    protected static ?string $label = 'Foto Master Guru & TU';
    protected static ?string $pluralLabel = 'Master Foto Guru & TU';
    protected static ?string $navigationIcon = 'heroicon-o-identification'; 
    
    // Kita letakan di posisi kedua grup
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool => false;

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::query()->whereIn('role', ['Guru', 'TU'])->whereNotNull('face_reference'))
            ->columns([
                Tables\Columns\ImageColumn::make('face_reference')
                    ->label('Foto Patokan')
                    ->circular()
                    ->disk('public')
                    // Memastikan foto besar dan jelas
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY / Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Guru' => 'success',
                        'TU'   => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tgl Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'Guru' => 'Guru',
                        'TU'   => 'TU',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reset_master')
                    ->label('Reset Foto')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Foto Master?')
                    ->modalDescription('Ini akan menghapus foto patokan guru ini. Mereka akan diminta mendaftarkan ulang wajahnya saat pertama kali presensi lagi.')
                    ->action(function (User $record) {
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
            'index' => Pages\ListMasterBiometrikGuruTu::route('/'),
        ];
    }
}
