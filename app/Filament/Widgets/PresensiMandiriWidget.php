<?php

namespace App\Filament\Widgets;

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

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('info')
                    ->label('Presensi Mandiri')
                    ->content('Silakan ambil foto selfie untuk melakukan absensi.'),
                
                FileUpload::make('photo')
                    ->label('Ambil Foto Selfie')
                    ->image()
                    ->extraInputAttributes(['capture' => 'user'])
                    ->required()
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('600')
                    ->imageResizeMode('cover')
                    ->disk('public')
                    ->directory('absensi-selfie'),
                
                Hidden::make('lat'),
                Hidden::make('long'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();
        $user = auth()->user();
        
        if (!$formData['lat'] || !$formData['long']) {
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

        if ($user->role === 'Siswa') {
            $student = Student::where('email', $user->email)->first();
            if (!$student) {
                Notification::make()->title('Data Siswa tidak ditemukan!')->danger()->send();
                return;
            }

            if ($currentTime->format('H:i') > '07:05') $status = 'Terlambat';

            KehadiranSiswa::create([
                'nis' => $student->nis,
                'rfid_uid' => $student->rfid,
                'waktu_tap' => $currentTime,
                'status' => $status,
                'lat' => $formData['lat'],
                'long' => $formData['long'],
                'photo' => $formData['photo'],
                'keterangan' => 'Presensi Mandiri (Dashboard)',
            ]);
        } else {
            // Guru / TU
            if ($currentTime->format('H:i') > '07:15') $status = 'Terlambat';
            
            KehadiranGuruTu::create([
                'nipy' => $user->nipy ?? $user->email,
                'rfid_uid' => $user->rfid,
                'waktu_tap' => $currentTime,
                'status' => $status,
                'lat' => $formData['lat'],
                'long' => $formData['long'],
                'photo' => $formData['photo'],
                'keterangan' => 'Presensi Mandiri (Dashboard)',
            ]);
        }

        Notification::make()
            ->title('Berhasil Absen!')
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
