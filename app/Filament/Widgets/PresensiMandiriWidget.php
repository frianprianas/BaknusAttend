<?php

namespace App\Filament\Widgets;

use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\AwsFaceService;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class PresensiMandiriWidget extends Widget implements HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.presensi-mandiri-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $data = [];
    public string $tipeAbsens = 'Masuk';
    public string $labelTombol = 'KIRIM PRESENSI';
    public ?string $userName = null;
    public ?string $userEmail = null;
    public ?string $userClass = null;
    public ?string $userAvatar = null;

    public static function canView(): bool
    {
        // Guru/TU yang punya izin aktif hari ini tidak bisa absen
        $user = auth()->user();
        if (!$user) return false;
        if ($user->role === 'Admin') return false; // Admin tidak perlu absen
        if (in_array($user->role, ['Guru', 'TU'])) {
            // Sembunyikan jika ada izin aktif hari ini
            if (IzinGuruTu::hasActiveIzinToday($user->nipy ?? '', $user->email)) {
                return false;
            }
        }
        return true;
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->userName = $user?->name;
        $this->userEmail = $user?->email;
        $this->userAvatar = $user?->avatar_url;

        if ($user && $user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::with('classRoom')->where('nis', $nis)->first();
            if ($student) {
                $this->userName = $student->name;
                $this->userClass = $student->classRoom?->kelas ?? 'Kelas Tidak Terdaftar';
            }
        }

        $this->tipeAbsens = $this->determinePresensiType();
        $this->form->fill();
    }

    private function determinePresensiType(): string
    {
        $user = auth()->user();
        if (!$user) return 'Masuk';
        $today = Carbon::today();
        
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::where('nis', $nis)->first();
            if (!$student) return 'Masuk';
            $count = KehadiranSiswa::where('nis', $student->nis)->whereDate('waktu_tap', $today)->count();
        } else {
            $count = KehadiranGuruTu::where(function($q) use ($user) {
                $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
            })->whereDate('waktu_tap', $today)->count();
        }
        
        if ($count === 0) return 'Masuk';
        if ($count === 1) return 'Pulang';
        return 'Selesai';
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $tipeAbsens = $this->determinePresensiType();
        
        // Cek apakah sudah punya foto master (cast ke boolean eksplisit)
        $hasMaster = false;
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::where('nis', $nis)->first();
            $hasMaster = !empty($student?->face_reference);
        } else {
            $hasMaster = !empty($user->face_reference);
        }

        $labelTombol = $tipeAbsens === 'Selesai'
            ? 'Selesai'
            : ($hasMaster ? "Kirim Presensi $tipeAbsens" : "Daftarkan Wajah & Absen $tipeAbsens");

        $this->labelTombol = $labelTombol;

        // -------------------------------------------------------
        // KONDISI A: Sudah ada Foto Master → Form 1 Langkah Saja
        // -------------------------------------------------------
        if ($hasMaster) {
            return $form
                ->schema([
                    Placeholder::make('info_selfie')
                        ->label("Presensi Mandiri — Absen $tipeAbsens")
                        ->content($tipeAbsens === 'Selesai'
                            ? "✅ Anda sudah menyelesaikan absensi hari ini (Masuk & Pulang). Terima kasih!"
                            : "Silakan ambil foto selfie untuk verifikasi kehadiran Anda."),
                    FileUpload::make('photo_selfie')
                        ->label('📷 Ambil Foto Selfie')
                        ->image()
                        ->acceptedFileTypes(['image/*'])
                        ->extraInputAttributes(['capture' => 'user', 'accept' => 'image/*'])
                        ->required($tipeAbsens !== 'Selesai')
                        ->maxSize(8192)
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('800')
                        ->imageResizeTargetHeight('800')
                        ->imageEditorMode(2)
                        ->disk('public')
                        ->directory('absensi-selfie')
                        ->hidden($tipeAbsens === 'Selesai'),
                    Hidden::make('lat'),
                    Hidden::make('long'),
                    Hidden::make('client_public_ip')
                        ->extraAttributes([
                            'x-init' => "
                                fetch('https://api.ipify.org?format=json')
                                    .then(response => response.json())
                                    .then(data => \$state = data.ip)
                                    .catch(() => {})
                            ",
                        ]),
                ])
                ->statePath('data');
        }

        // -------------------------------------------------------
        // KONDISI B: Belum ada Foto Master → Wizard 2 Langkah
        // -------------------------------------------------------
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Langkah 1: Daftarkan Wajah Master')
                        ->description('Ambil foto wajah jelas untuk patokan sistem BaknusAI.')
                        ->icon('heroicon-o-camera')
                        ->schema([
                            Placeholder::make('info_master')
                                ->label('')
                                ->content("⚠️ Anda belum memiliki foto master. Ambil foto dengan wajah menghadap kamera, tanpa masker, pencahayaan terang."),
                            FileUpload::make('photo_master')
                                ->label('📷 Ambil Foto Wajah Master')
                                ->image()
                                ->acceptedFileTypes(['image/*'])
                                ->extraInputAttributes(['capture' => 'user', 'accept' => 'image/*'])
                                ->required()
                                ->maxSize(8192)
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('800')
                                ->imageResizeTargetHeight('800')
                                ->imageEditorMode(2)
                                ->disk('public')
                                ->directory('face-references'),
                        ]),

                    Step::make('Langkah 2: Selfie Presensi')
                        ->description("Ambil selfie untuk menyelesaikan absen $tipeAbsens")
                        ->icon('heroicon-o-face-smile')
                        ->schema([
                            Placeholder::make('info_selfie')
                                ->label('')
                                ->content("✅ Foto master sudah diambil. Sekarang ambil selfie terakhir untuk absen $tipeAbsens."),
                            FileUpload::make('photo_selfie')
                                ->label('📷 Ambil Foto Selfie')
                                ->image()
                                ->acceptedFileTypes(['image/*'])
                                ->extraInputAttributes(['capture' => 'user', 'accept' => 'image/*'])
                                ->required()
                                ->maxSize(8192)
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('800')
                                ->imageResizeTargetHeight('800')
                                ->imageEditorMode(2)
                                ->disk('public')
                                ->directory('absensi-selfie'),
                        ]),
                ])
                ->submitAction(view('filament.widgets.presensi-submit-button', ['label' => $labelTombol])),
 
                 Hidden::make('lat'),
                 Hidden::make('long'),
                 Hidden::make('client_public_ip')
                    ->extraAttributes([
                        'x-init' => "
                            fetch('https://api.ipify.org?format=json')
                                .then(response => response.json())
                                .then(data => \$state = data.ip)
                                .catch(() => {})
                        ",
                    ]),
             ])
             ->statePath('data');
    }

    public function resetSelfie()
    {
        // Reset state Livewire agar Filepond kembali ke tampilan "Kamera"
        $this->form->fill();
    }

    public function submit(): void
    {
        $tipeAbsens = $this->determinePresensiType();
        if ($tipeAbsens === 'Selesai') {
            Notification::make()->title('Selesai!')->body('Anda sudah melakukan Absen Masuk dan Pulang hari ini.')->warning()->send();
            return;
        }

        $formData = $this->form->getState();
        $user = auth()->user();
        
        if (!isset($formData['lat']) || !isset($formData['long'])) {
            Notification::make()->title('GPS Tidak Aktif')->danger()->send();
            return;
        }

        $setting = SchoolSetting::first();
        if (!$setting) {
             Notification::make()->title('Pengaturan GPS Sekolah belum ada!')->danger()->send();
             return;
        }

        // --- VALIDASI IP PUBLIK (Anti Fake GPS via koneksi luar sekolah) ---
        if ($setting->is_ip_validation_active) {
            $allowedIps = array_filter(array_map('trim', [
                $setting->allowed_ip_1,
                $setting->allowed_ip_2,
                $setting->allowed_ip_3,
                $setting->allowed_ip_4,
            ]));

            if (!empty($allowedIps)) {
                // Gunakan IP yang dikirim dari browser (via API external) jika ada
                $clientIp = trim($formData['client_public_ip'] ?? '');

                // Jika browser gagal ambil IP eksternal, fallback ke deteksi server
                if (empty($clientIp)) {
                    $clientIp = request()->ip();
                    if ($cf = request()->header('CF-Connecting-IP')) $clientIp = $cf;
                    elseif ($real = request()->header('X-Real-IP')) $clientIp = $real;
                    elseif ($forward = request()->header('X-Forwarded-For')) $clientIp = trim(explode(',', $forward)[0]);
                }

                if (!in_array($clientIp, $allowedIps)) {
                    Notification::make()
                        ->title('Akses Ditolak: Bukan Jaringan Sekolah')
                        ->body("IP Anda terdeteksi: <b>$clientIp</b>. Daftar IP sekolah yang diizinkan: " . implode(', ', $allowedIps))
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }
            }
        }
        // --- AKHIR VALIDASI IP ---

        $distance = $this->haversineGreatCircleDistance(
            $formData['lat'], 
            $formData['long'], 
            $setting->lat, 
            $setting->long
        );

        if ($distance > $setting->radius) {
            Notification::make()
                ->title('Gagal: Di luar Radius!')
                ->body('Jarak Anda ' . round($distance) . 'm dari sekolah. Maksimum ' . $setting->radius . 'm.')
                ->danger()
                ->send();
            return;
        }

        // --- SISTEM ANTI-SPAM (RATE LIMITER) UNTUK AWS ---
        // Batasi maksimal 3 KALI percobaan dalam 5 MENIT. 
        $rateLimitKey = 'face_verification_attempt_' . $user->id;
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Notification::make()
                ->title('TERKUNCI SEMENTARA 🔒')
                ->body('Anda telah mencoba verifikasi wajah terlalu banyak. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.')
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        // Catat/Hitung percobaan untuk kunci rate limiter ini
        RateLimiter::hit($rateLimitKey, 300); // 300 detik = 5 menit lockout

        // --- FACE RECOGNITION LOGIC ---
        $faceService = new AwsFaceService();
        
        $model = $user;
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $model = Student::where('nis', $nis)->first();
        }

        // Cek apakah baru saja mendaftarkan Master (dari Step 1)
        if (isset($formData['photo_master']) && !$model->face_reference) {
            // ── PRE-FILTER LOKAL (gratis, sebelum AWS) ──
            $pre = $faceService->preFilter($formData['photo_master']);
            if (!$pre['ok']) {
                Storage::disk('public')->delete($formData['photo_master'] ?? '');
                Notification::make()
                    ->title('Foto Master Ditolak')
                    ->body($pre['reason'])
                    ->danger()->send();
                $this->resetSelfie();
                return;
            }

            $hasFace = $faceService->detectFace($formData['photo_master']);
            if (!$hasFace) {
                Storage::disk('public')->delete($formData['photo_master'] ?? '');
                Notification::make()
                    ->title('Maaf Verifikasi gagal')
                    ->body('Wajah tidak terdeteksi pada Foto Master. Pastikan wajah terlihat jelas tanpa penutup.')
                    ->danger()->send();
                $this->resetSelfie();
                return;
            }
            // Simpan Master
            $model->update(['face_reference' => $formData['photo_master']]);
        }

        // Verifikasi Selfie (Step 2)
        $photoSelfie = $formData['photo_selfie'] ?? null;
        if (!$photoSelfie) {
            Notification::make()->title('Foto Selfie dibutuhkan')->danger()->send();
            return;
        }

        // ── PRE-FILTER LOKAL untuk selfie (gratis, sebelum AWS) ──
        $preSelfie = $faceService->preFilter($photoSelfie);
        if (!$preSelfie['ok']) {
            Storage::disk('public')->delete($photoSelfie);
            Notification::make()
                ->title('Foto Selfie Ditolak')
                ->body($preSelfie['reason'])
                ->danger()->send();
            $this->resetSelfie();
            return;
        }

        $check = $faceService->compare($photoSelfie, $model->face_reference);
        
        if (!$check['success']) {
            Notification::make()->title('Maaf Verifikasi gagal')->body($check['error'])->danger()->send();
            $this->resetSelfie();
            return;
        }

        if (!$check['is_identical']) {
            Notification::make()
                ->title('Maaf Verifikasi gagal')
                ->body('Wajah selfie tidak cocok dengan foto master. Silakan coba lagi.')
                ->danger()->send();
            $this->resetSelfie();
            return;
        }
        
        // Verifikasi BERHASIL -> Bersihkan jejak hukuman/spam limiter
        RateLimiter::clear($rateLimitKey);

        Notification::make()
            ->title("Verifikasi berhasil!")
            ->body("Anda sudah presensi " . $tipeAbsens)
            ->success()->persistent()->send();

        $photoFinal = $photoSelfie;
        // --- END FACE RECOGNITION ---

        $currentTime = now();

        try {
            $this->addWatermarkToImage($photoFinal, clone $currentTime, $formData['lat'], $formData['long']);
        } catch (\Exception $e) {
            // Fallback: Jika GD Library bermasalah, foto akan tetap tersimpan tanpa watermark.
        }

        $status = 'Hadir';
        $keterangan = "{$tipeAbsens} - Presensi Mandiri (Dashboard)";

        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::where('nis', $nis)->first();
            if (!$student) {
                Notification::make()->title('Data Siswa tidak ditemukan!')->danger()->send();
                return;
            }

            if ($tipeAbsens === 'Masuk' && $currentTime->format('H:i') > '07:05') {
                $status = 'Terlambat';
            }

            KehadiranSiswa::create([
                'nis' => $student->nis,
                'rfid_uid' => $student->rfid,
                'waktu_tap' => $currentTime,
                'status' => $status,
                'lat' => $formData['lat'],
                'long' => $formData['long'],
                'photo' => $photoFinal,
                'keterangan' => $keterangan,
            ]);
        } else {
            // Guru / TU
            KehadiranGuruTu::create([
                'nipy' => $user->nipy ?? $user->email,
                'rfid_uid' => $user->rfid,
                'waktu_tap' => $currentTime,
                'status' => $status,
                'lat' => $formData['lat'],
                'long' => $formData['long'],
                'photo' => $photoFinal,
                'keterangan' => $keterangan,
            ]);
        }

        Notification::make()
            ->title('Berhasil Absen ' . $tipeAbsens . '!')
            ->body('Presensi mandiri Anda telah tercatat pada ' . now()->format('H:i'))
            ->success()
            ->send()
            ->sendToDatabase($user);

        // Minta dashboard widgets lain me-refresh ulang (terutama karena bulan baru / nambah presensi)
        $this->dispatch('kehadiran-updated');

        $this->form->fill();
    }

    private function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }

    private function addWatermarkToImage(string $path, Carbon $time, $lat, $long)
    {
        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) return;

        $mime = mime_content_type($fullPath);
        $img = null;
        if ($mime == 'image/jpeg') $img = @imagecreatefromjpeg($fullPath);
        elseif ($mime == 'image/png') $img = @imagecreatefrompng($fullPath);
        elseif ($mime == 'image/webp') $img = @imagecreatefromwebp($fullPath);

        if (!$img) return;

        $width = imagesx($img);
        $height = imagesy($img);

        // Tinggi background banner
        $bannerHeight = 65;
        $bannerY = $height - $bannerHeight;

        // Siapkan warna
        // Aktifkan alpha blending untuk background transparan
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // Hitam transparan (40 dari 127 = sekitar 30% tembus pandang)
        $blackAlpha = imagecolorallocatealpha($img, 0, 0, 0, 40); 
        $white = imagecolorallocate($img, 255, 255, 255);
        $yellow = imagecolorallocate($img, 255, 255, 0);
        $cyan = imagecolorallocate($img, 0, 255, 255);

        // Gambar background banner di bawah
        imagefilledrectangle($img, 0, $bannerY, $width, $height, $blackAlpha);

        // Pakai font native terbesar GD = ukuran 5
        $font = 5;
        
        $userName = $this->userName ?? 'Unknown';
        // Pisahkan teks
        $userStr = "Nama   : " . $userName;
        $timeStr = "Waktu  : " . $time->format('d M Y H:i:s') . " WIB";
        $locStr  = "Lokasi : " . $lat . ", " . $long;

        // Cetak teks berurutan
        imagestring($img, $font, 15, $bannerY + 8,  $userStr, $cyan);
        imagestring($img, $font, 15, $bannerY + 25, $timeStr, $white);
        imagestring($img, $font, 15, $bannerY + 42, $locStr, $yellow);

        // Simpan timpa foto lama
        if ($mime == 'image/jpeg') imagejpeg($img, $fullPath, 90);
        elseif ($mime == 'image/png') imagepng($img, $fullPath);
        elseif ($mime == 'image/webp') imagewebp($img, $fullPath, 90);

        imagedestroy($img);
    }
}
