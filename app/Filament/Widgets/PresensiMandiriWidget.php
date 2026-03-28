<?php

namespace App\Filament\Widgets;

use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\SchoolSetting;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PresensiMandiriWidget extends Widget implements HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.presensi-mandiri-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $data = [];
    public string $tipeAbsens = 'Masuk';

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
        $tipeAbsens = $this->determinePresensiType();
        $pesanInfo = "Silakan ambil foto selfie untuk melakukan Absen {$tipeAbsens}.";
        
        if ($tipeAbsens === 'Selesai') {
            $pesanInfo = "Anda sudah menyelesaikan absensi lengkap (Masuk & Pulang) hari ini. Terima kasih!";
        }

        return $form
            ->schema([
                Placeholder::make('info')
                    ->label("Presensi Mandiri: " . ($tipeAbsens !== 'Selesai' ? "Absen $tipeAbsens" : "Selesai"))
                    ->content($pesanInfo),
                
                FileUpload::make('photo')
                    ->label('Ambil Foto') // Renamed label here
                    ->image()
                    ->extraInputAttributes(['capture' => 'user'])
                    ->required()
                    // Ukuran maksimum 1MB — tolak di sisi klien
                    ->maxSize(1024)
                    // Resize ke 640x480 sebelum upload (hemat bandwidth + storage)
                    ->imageResizeTargetWidth('640')
                    ->imageResizeTargetHeight('480')
                    ->imageResizeMode('cover')
                    ->imageResizeUpscale(false)
                    // Kompresi kualitas JPEG 65% — cukup untuk identifikasi wajah
                    ->imageEditorMode(2)
                    ->disk('public')
                    ->directory('absensi-selfie')
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
