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
use Illuminate\Support\Facades\Storage;

class PresensiMandiriWidget extends Widget implements HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.presensi-mandiri-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $data = [];
    public string $tipeAbsens = 'Masuk';
    public string $labelTombol = 'KIRIM PRESENSI';

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
        $this->tipeAbsens = $this->determinePresensiType();
        $this->form->fill();
    }

    private function determinePresensiType(): string
    {
        $user = auth()->user();
        if (!$user) return 'Masuk';
        $today = Carbon::today();
        
        if ($user->role === 'Siswa') {
            $student = Student::where('email', $user->email)->first();
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
        
        // Cek apakah sudah punya foto master
        $hasMaster = false;
        if ($user->role === 'Siswa') {
            $student = Student::where('email', $user->email)->first();
            $hasMaster = $student?->face_reference ?? false;
        } else {
            $hasMaster = $user->face_reference ?? false;
        }

        if (!$hasMaster) {
            $pesanInfo = "⚠️ **Belum ada Foto Master.** Silakan ambil foto selfie dengan wajah terlihat jelas dan pencahayaan terang untuk mendaftarkan wajah Anda ke sistem.";
            $labelTombol = "Daftarkan Wajah & Absen " . $tipeAbsens;
        } else {
            $pesanInfo = "Silakan ambil foto selfie untuk melakukan Absen {$tipeAbsens}.";
            $labelTombol = "Kirim Presensi " . $tipeAbsens;
        }
        if ($tipeAbsens === 'Selesai') {
            $pesanInfo = "Anda sudah menyelesaikan absensi lengkap (Masuk & Pulang) hari ini. Terima kasih!";
            $labelTombol = "Selesai";
        }

        $this->labelTombol = $labelTombol;

        return $form
            ->schema([
                Wizard::make([
                    Step::make('Step 1: Pendaftaran Wajah Master')
                        ->description('Ambil foto wajah jelas untuk patokan sistem AI.')
                        ->hidden(fn () => $hasMaster)
                        ->schema([
                            Placeholder::make('info_master')
                                ->content("Sistem mendeteksi Anda belum memiliki foto master. Silakan ambil foto dengan wajah menghadap depan dan pencahayaan terang."),
                            FileUpload::make('photo_master')
                                ->label('Ambil Foto Master')
                                ->image()
                                ->extraInputAttributes(['capture' => 'user'])
                                ->required()
                                ->maxSize(2048)
                                ->imageEditorMode(2)
                                ->disk('public')
                                ->directory('face-references'),
                        ]),
                    
                    Step::make('Step 2: Presensi Mandiri')
                        ->description("Ambil selfie untuk melakukan Absen $tipeAbsens")
                        ->schema([
                            Placeholder::make('info_absen')
                                ->content($hasMaster ? "Silakan ambil foto selfie untuk memverifikasi kehadiran Anda." : "Wajah Anda sudah terdaftar! Sekarang silakan ambil foto selfie terakhir untuk absen."),
                            FileUpload::make('photo_selfie')
                                ->label('Ambil Foto Selfie')
                                ->image()
                                ->extraInputAttributes(['capture' => 'user'])
                                ->required()
                                ->maxSize(1024)
                                ->imageEditorMode(2)
                                ->disk('public')
                                ->directory('absensi-selfie'),
                        ]),
                ])
                ->submitAction(view('filament.widgets.presensi-submit-button', ['label' => $labelTombol]))
                ->startOnStep($hasMaster ? 2 : 1),

                Hidden::make('lat'),
                Hidden::make('long'),
            ])
            ->statePath('data');
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

        // --- FACE RECOGNITION LOGIC ---
        $faceService = new AwsFaceService();
        
        $model = $user;
        if ($user->role === 'Siswa') {
            $model = Student::where('email', $user->email)->first();
        }

        // Cek apakah baru saja mendaftarkan Master (dari Step 1)
        if (isset($formData['photo_master']) && !$model->face_reference) {
            $hasFace = $faceService->detectFace($formData['photo_master']);
            if (!$hasFace) {
                Storage::disk('public')->delete($formData['photo_master'] ?? '');
                Notification::make()
                    ->title('Maaf Verifikasi gagal')
                    ->body('Wajah tidak terdeteksi pada Foto Master. Pastikan wajah terlihat jelas tanpa penutup.')
                    ->danger()->send();
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

        $check = $faceService->compare($photoSelfie, $model->face_reference);
        
        if (!$check['success']) {
            Notification::make()->title('Maaf Verifikasi gagal')->body($check['error'])->danger()->send();
            return;
        }

        if (!$check['is_identical']) {
            Notification::make()
                ->title('Maaf Verifikasi gagal')
                ->body('Wajah selfie tidak cocok dengan foto master. Silakan coba lagi.')
                ->danger()->send();
            return;
        }
        
        // Verifikasi BERHASIL
        Notification::make()
            ->title("Verifikasi berhasil!")
            ->body("Anda sudah presensi " . $tipeAbsens)
            ->success()->persistent()->send();

        $photoFinal = $photoSelfie;
        // --- END FACE RECOGNITION ---

        $currentTime = now();
        $status = 'Hadir';
        $keterangan = "{$tipeAbsens} - Presensi Mandiri (Dashboard)";

        if ($user->role === 'Siswa') {
            $student = Student::where('email', $user->email)->first();
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
            ->body('Presensi mandiri Anda telah tercatat.')
            ->success()
            ->send();

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
}
