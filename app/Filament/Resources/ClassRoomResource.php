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
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Ruang Kelas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kelas')
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
                    ]),

                Forms\Components\Section::make('📱 Notifikasi WhatsApp Wali Kelas')
                    ->description('Atur pengiriman rekap presensi siswa kelas ini ke nomor WhatsApp Wali Kelas setiap pagi (mulai jam 08:00 WIB).')
                    ->schema([
                        Forms\Components\Toggle::make('is_wa_active')
                            ->label('Kirim Notifikasi WA ke Wali Kelas')
                            ->helperText('Aktifkan agar kelas ini mendapatkan laporan kehadiran siswa harian otomatis via WhatsApp.')
                            ->default(true),
                        Forms\Components\TextInput::make('wa_wali_kelas')
                            ->label('Nomor WhatsApp Wali Kelas')
                            ->placeholder('Contoh: 081234567890')
                            ->tel()
                            ->helperText('Gunakan nomor WhatsApp aktif Wali Kelas yang biasa berkomunikasi sehari-hari.'),
                    ]),
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
                Tables\Columns\ToggleColumn::make('is_wa_active')
                    ->label('WA Aktif'),
                Tables\Columns\TextColumn::make('wa_wali_kelas')
                    ->label('No. WA Wali Kelas')
                    ->placeholder('-')
                    ->searchable(),
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
