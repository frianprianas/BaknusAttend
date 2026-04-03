<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\VideoTimelapseService;
use Filament\Notifications\Notification;

class VideoTimelapse extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Kilas Balik Video';
    protected static ?string $title = 'Kenangan Presensi Anda';
    protected static ?string $navigationGroup = 'Personal';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.video-timelapse';

    public $recentPhotos = [];
    public $selectedPhotos = [];
    public $isGenerating = false;

    public static function canAccess(): bool
    {
        // Memastikan menu sidebar dan halaman ini bisa diakses role selain Admin
        $user = auth()->user();
        return $user !== null;
    }

    public function mount()
    {
        $this->fetchPhotos();
    }

    public function fetchPhotos()
    {
        $user = auth()->user();
        if (!$user) return;

        $photos = [];
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::where('nis', $nis)->first();
            if ($student) {
                // Ambil hingga 60 foto terbaru yang ada foto-nya
                $photos = KehadiranSiswa::where('nis', $student->nis)
                    ->whereNotNull('photo')
                    ->orderBy('waktu_tap', 'desc')
                    ->limit(60)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'path' => $item->photo,
                            'date' => Carbon::parse($item->waktu_tap)->format('d M Y H:i'),
                        ];
                    })
                    ->toArray();
            }
        } else {
            $nipy = $user->nipy ?? $user->email;
            $photos = KehadiranGuruTu::where(function($q) use ($nipy, $user) {
                    $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                })
                ->whereNotNull('photo')
                ->orderBy('waktu_tap', 'desc')
                ->limit(60)
                ->get()
                ->map(function ($item) {
                    return [
                        'path' => $item->photo,
                        'date' => Carbon::parse($item->waktu_tap)->format('d M Y H:i'),
                    ];
                })
                ->toArray();
        }

        // Balikkan array agar foto dari yang terlama ke terbaru (kronologis natural untuk timelapse)
        $this->recentPhotos = array_reverse($photos);
    }

    public function togglePhoto($path)
    {
        if (in_array($path, $this->selectedPhotos)) {
            // Hapus dari selected
            $this->selectedPhotos = array_diff($this->selectedPhotos, [$path]);
        } else {
            // Cek limit 20
            if (count($this->selectedPhotos) >= 20) {
                Notification::make()
                    ->title('Batas Maksimal')
                    ->body('Anda hanya dapat memilih maksimal 20 foto untuk video.')
                    ->warning()
                    ->send();
                return;
            }
            $this->selectedPhotos[] = $path;
        }
    }

    public function selectAllTampil()
    {
        // Fitur pilih semua dari yang paling baru
        $this->selectedPhotos = [];
        $count = count($this->recentPhotos);
        // Ambil 20 paling baru (berarti posisi array paling belakang karena sudah direverse)
        $startIndex = max(0, $count - 20);
        for ($i = $startIndex; $i < $count; $i++) {
            $this->selectedPhotos[] = $this->recentPhotos[$i]['path'];
        }
    }

    public function generateVideo(VideoTimelapseService $service)
    {
        if (count($this->selectedPhotos) < 3) {
            Notification::make()
                ->title('Terlalu Sedikit')
                ->body('Pilih minimal 3 foto untuk membuat video.')
                ->warning()
                ->send();
            return;
        }

        $this->isGenerating = true;

        try {
            $user = auth()->user();
            
            // Urutkan kembali sesuai kronologis aslinya (berikut index pada recentPhotos)
            $orderedSelected = [];
            foreach ($this->recentPhotos as $rp) {
                if (in_array($rp['path'], $this->selectedPhotos)) {
                    $orderedSelected[] = $rp['path'];
                }
            }

            // Panggil Service baru
            $videoUrl = $service->generateFromPhotos($user, $orderedSelected);

            Notification::make()
                ->title('Video Kilas Balik Siap!')
                ->body('Video Anda berhasil diproses dan akan terunduh otomatis.')
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download MP4')
                        ->url($videoUrl)
                        ->openUrlInNewTab(),
                ])
                ->send();

            $this->dispatch('video-ready', ['url' => $videoUrl]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Membuat Video')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }
}
