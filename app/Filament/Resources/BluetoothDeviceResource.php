<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BluetoothDeviceResource\Pages;
use App\Models\BluetoothDevice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BluetoothDeviceResource extends Resource
{
    protected static ?string $model = BluetoothDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Perangkat Bluetooth (ESP32)';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Perangkat')
                    ->description('Konfigurasi kredensial perangkat keras Wemos D1 R32 ESP32 offline.')
                    ->schema([
                        Forms\Components\TextInput::make('device_id')
                            ->label('Device ID (Unik)')
                            ->placeholder('Contoh: WEMOS_GERBANG_01')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Harus sama persis dengan yang di-flash pada firmware ESP32.'),

                        Forms\Components\TextInput::make('device_name')
                            ->label('Nama Perangkat / Lokasi')
                            ->placeholder('Contoh: Gerbang Depan')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('secret_key')
                            ->label('Secret Key (HMAC-SHA256)')
                            ->placeholder('Kunci rahasia bersama ESP32')
                            ->required()
                            ->password()
                            ->revealable()
                            ->default(fn () => Str::random(32))
                            ->helperText('Kunci rahasia untuk menghasilkan signature HMAC-SHA256 bersama ESP32.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generateSecret')
                                    ->icon('heroicon-m-arrow-path')
                                    ->tooltip('Generate Kunci Acak')
                                    ->action(function ($set) {
                                        $set('secret_key', Str::random(32));
                                    })
                            ),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Hanya perangkat aktif yang diizinkan untuk verifikasi presensi.'),
                    ])->columns(2),

                Forms\Components\Section::make('Validasi Lokasi GPS (Geofencing)')
                    ->description('Opsional: Batasi presensi Bluetooth hanya jika ponsel berada di radius perangkat ini.')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->placeholder('Contoh: -6.938812')
                            ->helperText('Kosongkan jika tidak ingin membatasi radius GPS alat.'),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->placeholder('Contoh: 107.721245'),

                        Forms\Components\TextInput::make('radius_meters')
                            ->label('Radius Toleransi (Meter)')
                            ->numeric()
                            ->default(50)
                            ->minValue(5)
                            ->maxValue(1000)
                            ->suffix('meter'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('device_name')
                    ->label('Lokasi / Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('radius_meters')
                    ->label('Radius')
                    ->suffix(' m')
                    ->sortable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('Koordinat')
                    ->formatStateUsing(fn ($record) => ($record->latitude && $record->longitude) 
                        ? round($record->latitude, 4) . ', ' . round($record->longitude, 4) 
                        : 'Tanpa Batas GPS'
                    )
                    ->color(fn ($record) => ($record->latitude && $record->longitude) ? 'gray' : 'warning'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBluetoothDevices::route('/'),
        ];
    }
}
