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
                Forms\Components\TextInput::make('wa_wali_kelas')
                    ->label('Nomor WhatsApp Wali Kelas')
                    ->placeholder('Contoh: 081234567890')
                    ->tel()
                    ->helperText('Nomor WhatsApp aktif Wali Kelas untuk menerima rekap presensi siswa setiap pagi.'),
                Forms\Components\TimePicker::make('jam_kirim_wa')
                    ->label('Jam & Menit Pengiriman WA')
                    ->seconds(false)
                    ->default('08:00')
                    ->required()
                    ->helperText('Atur waktu Jam:Menit (contoh: 08:05, 08:12). Beri selisih waktu antarkelas agar natural dan bebas blokir WA.'),
                Forms\Components\Checkbox::make('is_wa_active')
                    ->label('Centang untuk Kirim Laporan WA ke Wali Kelas ini')
                    ->helperText('Centang checkbox ini agar kelas ini otomatis dikirimi rekap presensi siswa harian pada Jam:Menit yang ditentukan.')
                    ->default(true),
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
                    ->color('info')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('nipy')
                    ->label('Pilih Wali Kelas')
                    ->options(fn () => \App\Models\User::whereIn('role', ['Guru', 'TU'])
                        ->where('role', '!=', 'Test')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($u) => [($u->nipy ?: $u->email) => $u->name])
                        ->toArray()
                    )
                    ->placeholder('Pilih Wali Kelas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\CheckboxColumn::make('is_wa_active')
                    ->label('Kirim WA?')
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('wa_wali_kelas')
                    ->label('No. WA Wali Kelas')
                    ->placeholder('0812xxxxxx')
                    ->rules(['nullable', 'string', 'max:25'])
                    ->searchable(),
                Tables\Columns\TextInputColumn::make('jam_kirim_wa')
                    ->label('Jam:Menit Kirim')
                    ->placeholder('08:00')
                    ->rules(['nullable', 'string', 'max:8'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('programStudi.program_studi')
                    ->label('Program Studi')
                    ->sortable()
                    ->searchable()
                    ->limit(20)
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
