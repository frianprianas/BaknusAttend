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
        $ipValidationRule = [
            function () {
                return function (string $attribute, $value, \Closure $fail) {
                    $val = trim($value);
                    if (empty($val)) return;

                    // Wildcard matching format (IPv4 or IPv6 contains * and valid characters)
                    if (str_contains($val, '*')) {
                        if (preg_match('/^[0-9a-fA-F.:*]+$/', $val)) {
                            return;
                        }
                    }

                    // Subnet CIDR matching format (contains /)
                    if (str_contains($val, '/')) {
                        $parts = explode('/', $val);
                        if (count($parts) === 2) {
                            $ip = $parts[0];
                            $bits = $parts[1];
                            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                                $isIpv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
                                if (ctype_digit($bits)) {
                                    $bits = (int) $bits;
                                    if ($isIpv6 && $bits >= 0 && $bits <= 128) return;
                                    if (!$isIpv6 && $bits >= 0 && $bits <= 32) return;
                                }
                            }
                        }
                    }

                    // Single IP format
                    if (filter_var($val, FILTER_VALIDATE_IP)) {
                        return;
                    }

                    $fail('Format IP tidak valid. Gunakan format IP tunggal (192.168.1.1), wildcard (192.168.1.*), atau subnet CIDR (192.168.1.0/24).');
                };
            }
        ];

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

                                        function handleIp(data) {
                                            const el = document.getElementById('client-ip-debug');
                                            el.innerText = data.ip + ' (via JSONP)';
                                        }

                                        const script = document.createElement('script');
                                        script.src = 'https://api.ipify.org?format=jsonp&callback=handleIp';
                                        document.body.appendChild(script);

                                        setTimeout(() => {
                                            const el = document.getElementById('client-ip-debug');
                                            if (el.innerText === 'Mendeteksi...') {
                                                el.innerText = 'Gagal Deteksi (JSONP Diblokir)';
                                                el.classList.replace('text-success', 'text-danger');
                                            }
                                        }, 5000);
                                    </script>
                                ");
                            })
                            ->default(false)
                            ->live(),
                        Forms\Components\Grid::make()->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('allowed_ip_1')
                                    ->label('IP Publik Sekolah #1')
                                    ->placeholder('Contoh: 114.125.10.20')
                                    ->helperText('IP utama (wajib diisi jika fitur aktif)')
                                    ->rules($ipValidationRule)
                                    ->requiredIf('is_ip_validation_active', true),
                                Forms\Components\TextInput::make('allowed_ip_2')
                                    ->label('IP Publik Sekolah #2')
                                    ->placeholder('Opsional — ISP cadangan')
                                    ->rules($ipValidationRule)
                                    ->nullable(),
                                Forms\Components\TextInput::make('allowed_ip_3')
                                    ->label('IP Publik Sekolah #3')
                                    ->placeholder('Opsional — ISP cadangan 2')
                                    ->rules($ipValidationRule)
                                    ->nullable(),
                                Forms\Components\TextInput::make('allowed_ip_4')
                                    ->label('IP Publik Sekolah #4')
                                    ->placeholder('Opsional — ISP cadangan 3')
                                    ->rules($ipValidationRule)
                                    ->nullable(),
                                Forms\Components\TextInput::make('allowed_ip_5')
                                    ->label('IP Publik Sekolah #5')
                                    ->placeholder('Opsional — ISP cadangan 4')
                                    ->rules($ipValidationRule)
                                    ->nullable(),
                                Forms\Components\TextInput::make('allowed_ip_6')
                                    ->label('IP Publik Sekolah #6')
                                    ->placeholder('Opsional — ISP cadangan 5')
                                    ->rules($ipValidationRule)
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

                Forms\Components\Section::make('📺 Pengaturan Slideshow Halaman Login (Bioskop View)')
                    ->description('Atur secara dinamis slide mana saja yang ditampilkan pada layar TV/monitor halaman login.')
                    ->schema([
                        Forms\Components\Grid::make()->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                Forms\Components\Toggle::make('slide_show_guru')
                                    ->label('Tampilkan Slide Dewan Guru')
                                    ->default(true),
                                Forms\Components\Toggle::make('slide_show_tu')
                                    ->label('Tampilkan Slide Staff TU')
                                    ->default(true),
                                Forms\Components\Toggle::make('slide_show_kelas')
                                    ->label('Tampilkan Slide Kelas Siswa')
                                    ->default(true),
                            ]),
                        Forms\Components\Grid::make()->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('slide_min_students')
                                    ->label('Min. Siswa Per Kelas')
                                    ->numeric()
                                    ->default(6)
                                    ->helperText('Hanya tampilkan kelas jika siswanya > batas ini (misal: 6)')
                                    ->required(),
                                Forms\Components\TextInput::make('slide_duration')
                                    ->label('Durasi Autoplay (Detik)')
                                    ->numeric()
                                    ->default(6)
                                    ->suffix('Detik')
                                    ->helperText('Waktu perputaran antar-slide (misal: 6 detik)')
                                    ->required(),
                                Forms\Components\TextInput::make('slide_excluded_roles')
                                    ->label('Role Disembunyikan')
                                    ->placeholder('Contoh: Test')
                                    ->default('Test')
                                    ->helperText('Daftar role yang disembunyikan dari slide (pisahkan dengan koma)')
                                    ->nullable(),
                            ]),
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
