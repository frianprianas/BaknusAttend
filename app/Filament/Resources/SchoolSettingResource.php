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

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Sekolah';
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
                        Forms\Components\Grid::make()->columns(['default' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->required()
                                    ->step('0.00000001'),
                                Forms\Components\TextInput::make('long')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->required()
                                    ->step('0.00000001'),
                                Forms\Components\TextInput::make('radius')
                                    ->label('Radius (Meter)')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Notifikasi Pengingat Otomatis')
                    ->description('Atur jam berapa pesan pengingat dikirim ke HP Guru (Senin - Jumat) jika belum absen.')
                    ->schema([
                        Forms\Components\Toggle::make('is_reminder_active')
                            ->label('Aktifkan Notifikasi Pengingat')
                            ->default(true),
                        Forms\Components\Grid::make()->columns(['default' => 2])
                            ->schema([
                                Forms\Components\TimePicker::make('reminder_masuk')
                                    ->label('Pengingat Masuk')
                                    ->default('08:00:00')
                                    ->required(),
                                Forms\Components\TimePicker::make('reminder_pulang')
                                    ->label('Pengingat Pulang')
                                    ->default('15:00:00')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('🔒 Keamanan: Validasi IP Publik Sekolah')
                    ->description('Jika diaktifkan, user hanya bisa absen dari koneksi internet sekolah (WiFi sekolah). IP publik sekolah harus diisi. Validasi ini berjalan SEBELUM pengecekan AWS sehingga menghemat biaya dan mencegah Fake GPS.')
                    ->schema([
                        Forms\Components\Toggle::make('is_ip_validation_active')
                            ->label('Aktifkan Validasi IP Publik')
                            ->helperText(function() {
                                $ips = [
                                    'Standard (Request IP)' => request()->ip(),
                                    'X-Forwarded-For' => request()->header('X-Forwarded-For'),
                                    'X-Real-IP' => request()->header('X-Real-IP'),
                                    'CF-Connecting-IP' => request()->header('CF-Connecting-IP'),
                                ];
                                
                                $clientIp = request()->ip();
                                if ($cf = request()->header('CF-Connecting-IP')) $clientIp = $cf;
                                elseif ($real = request()->header('X-Real-IP')) $clientIp = $real;
                                elseif ($forward = request()->header('X-Forwarded-For')) $clientIp = trim(explode(',', $forward)[0]);

                                $info = "IP Terdeteksi Server: <b class='text-danger'>$clientIp</b><br/>";
                                $info .= "IP Terdeteksi Client (Gunakan IP ini): <b id='client-ip-debug' class='text-success'>Mendeteksi...</b><br/>";
                                
                                $info .= "<small>Detail Header (Debug):<br/>";
                                foreach($ips as $key => $val) {
                                    if($val) $info .= "- $key: $val<br/>";
                                }
                                $info .= "</small>";

                                return new \Illuminate\Support\HtmlString("
                                    Jika aktif, absensi hanya bisa dilakukan dari jaringan internet sekolah.<br/>" . $info . "
                                    <script>
                                        const providers = [
                                            'https://api.ipify.org?format=json',
                                            'https://checkip.amazonaws.com'
                                        ];

                                        async function getPublicIp() {
                                            const el = document.getElementById('client-ip-debug');
                                            try {
                                                // Coba Ipify (JSON)
                                                let res = await fetch('https://api.ipify.org?format=json');
                                                let data = await res.json();
                                                el.innerText = data.ip;
                                                return;
                                            } catch (e) {}

                                            try {
                                                // Coba Amazon (Plain Text)
                                                let res = await fetch('https://checkip.amazonaws.com');
                                                let ip = await res.text();
                                                el.innerText = ip.trim();
                                                return;
                                            } catch (e) {}

                                            el.innerText = 'Gagal mendeteksi IP Publik (Cek Firewall Sekolah)';
                                            el.classList.replace('text-success', 'text-danger');
                                        }
                                        getPublicIp();
                                    </script>
                                ");
                            })
                            ->default(false)
                            ->live(),
                        Forms\Components\Grid::make()->columns(['default' => 1, 'md' => 2])
                            ->schema([
                                Forms\Components\TextInput::make('allowed_ip_1')
                                    ->label('IP Publik Sekolah #1')
                                    ->placeholder('Contoh: 114.125.10.20')
                                    ->helperText('IP utama (wajib diisi jika fitur aktif)')
                                    ->ip()
                                    ->requiredIf('is_ip_validation_active', true),
                                Forms\Components\TextInput::make('allowed_ip_2')
                                    ->label('IP Publik Sekolah #2')
                                    ->placeholder('Opsional — ISP cadangan')
                                    ->ip()
                                    ->nullable(),
                                Forms\Components\TextInput::make('allowed_ip_3')
                                    ->label('IP Publik Sekolah #3')
                                    ->placeholder('Opsional — ISP cadangan 2')
                                    ->ip()
                                    ->nullable(),
                                Forms\Components\TextInput::make('allowed_ip_4')
                                    ->label('IP Publik Sekolah #4')
                                    ->placeholder('Opsional — ISP cadangan 3')
                                    ->ip()
                                    ->nullable(),
                            ]),
                    ])
                    ->collapsible(),
                
                Forms\Components\Section::make('📅 Acuan Kehadiran Kerja')
                    ->description('Tentukan standar jumlah hari kerja guru/TU dalam satu bulan. Nilai ini akan menjadi acuan saat menghitung persentase kehadiran guru.')
                    ->schema([
                        Forms\Components\TextInput::make('default_target_hari_kerja')
                            ->label('Standar Target Sekolah (Hari)')
                            ->numeric()
                            ->default(20)
                            ->suffix('Hari')
                            ->helperText('Contoh: 20 hari kerja/bulan. Nilai ini akan dipakai otomatis jika di profil Guru tsb tidak diatur secara khusus.')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('radius')->suffix(' m'),
                Tables\Columns\IconColumn::make('is_reminder_active')->boolean()->label('Pengingat Aktif'),
                Tables\Columns\TextColumn::make('reminder_masuk')->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('reminder_pulang')->label('Jam Pulang'),
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
