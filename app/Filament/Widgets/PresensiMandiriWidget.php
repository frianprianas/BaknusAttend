<?php

namespace App\Filament\Widgets;

use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\AzureFaceService;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Contracts\HasForms;
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
                Placeholder::make('info')
                    ->label("Presensi Mandiri: " . ($tipeAbsens !== 'Selesai' ? "Absen $tipeAbsens" : "Selesai"))
                    ->content($pesanInfo),
                
                FileUpload::make('photo')
                    ->label(!$hasMaster ? 'Ambil Foto Master' : 'Ambil Foto Selfie')
                    ->image()
                    ->extraInputAttributes(['capture' => 'user'])
                    ->required()
                    // Ukuran maksimum untuk Master boleh lebih besar (2MB), Selfie (1MB)
                    ->maxSize(!$hasMaster ? 2048 : 1024)
                    // Kompresi kualitas JPEG 80% untuk Master, 65% untuk Selfie
                    ->imageEditorMode(2)
                    ->disk('public')
                    ->directory(!$hasMaster ? 'face-references' : 'absensi-selfie')
                    ->hidden($tipeAbsens === 'Selesai'),
                
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
        $faceService = new AzureFaceService();
        $isInitializing = false;
        
        $model = $user;
        if ($user->role === 'Siswa') {
            $model = Student::where('email', $user->email)->first();
        }

        // Jika belum ada foto master
        if (!$model->face_reference) {
            $isInitializing = true;
            // Deteksi apakah ada wajah di foto ini
            $faceId = $faceService->detectFace($formData['photo']);
            if (!$faceId) {
                // Hapus fotonya karena gagal
                Storage::disk('public')->delete($formData['photo']);
                Notification::make()
                    ->title('Wajah Tidak Terdeteksi!')
                    ->body('Gagal mendaftarkan wajah. Pastikan wajah terlihat jelas tanpa masker/penutup dan pencahayaan terang.')
                    ->danger()->send();
                return;
            }
            // Simpan sebagai referensi
            $model->update(['face_reference' => $formData['photo']]);
            Notification::make()
                ->title('Foto Master Berhasil Disimpan!')
                ->body('Data wajah Anda telah terdaftar. Selanjutnya sistem akan selalu mencocokkan wajah Anda dengan foto ini.')
                ->success()->send();
        } else {
            // Sudah ada foto master -> Lakukan verifikasi
            $check = $faceService->compare($formData['photo'], $model->face_reference);
            
            if (!$check['success']) {
                Notification::make()->title('Gagal Memproses Wajah')->body($check['error'])->danger()->send();
                return;
            }

            if (!$check['is_identical']) {
                $conf = round($check['confidence'] * 100);
                Notification::make()
                    ->title('Wajah Tidak Cocok! (' . $conf . '%)')
                    ->body('Sistem mendeteksi wajah yang berbeda. Silakan coba lagi dengan posisi dan pencahayaan yang lebih baik.')
                    ->danger()->send();
                return;
            }
            
            // Wajah cocok -> Lanjut simpan absensi
        }
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
                'photo' => $formData['photo'],
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
                'photo' => $formData['photo'],
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
