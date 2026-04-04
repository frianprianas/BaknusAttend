<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;

class TimelapseSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationLabel = 'Pengaturan Kilas Balik';
    protected static ?string $title = 'Manajemen Musik Kilas Balik';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.timelapse-settings';

    public ?array $data = [];
    public array $musicFiles = [];

    public static function canAccess(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    public function mount(): void
    {
        $this->form->fill();
        $this->loadMusic();
    }

    public function loadMusic(): void
    {
        $path = public_path('timelapse_music');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $files = File::files($path);
        $this->musicFiles = [];
        foreach ($files as $file) {
            if ($file->getExtension() === 'mp3') {
                $this->musicFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                    'path' => $file->getRealPath(),
                    'url' => asset('timelapse_music/' . $file->getFilename()),
                ];
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tambah Musik Baru')
                    ->description('Upload file MP3 (Maksimal 3MB) untuk dijadikan pilihan backsound bagi user.')
                    ->schema([
                        FileUpload::make('music')
                            ->label('File MP3')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])
                            ->maxSize(3072) // 3MB
                            ->disk('public')
                            ->directory('timelapse_music')
                            ->storeFileNamesIn('original_name')
                            ->required(),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $tempPath = $state['music'] ?? null;

        if (!$tempPath) return;

        // Path di storage/app/public/...
        $storageDir = storage_path('app/public');
        $fullPath = $storageDir . '/' . $tempPath;

        if (File::exists($fullPath)) {
            $filename = basename($fullPath);
            // Bersihkan nama file (slug) agar tidak ada karakter aneh
            $cleanName = time() . '_' . str_replace([' ', '#', '&'], '_', $filename);
            
            $targetPath = public_path('timelapse_music/' . $cleanName);
            
            if (!File::exists(public_path('timelapse_music'))) {
                File::makeDirectory(public_path('timelapse_music'), 0755, true);
            }

            File::move($fullPath, $targetPath);

            Notification::make()
                ->title('Musik Berhasil Diunggah')
                ->body("File $cleanName telah ditambahkan ke koleksi.")
                ->success()
                ->send();
        }

        $this->data = []; // Reset form data
        $this->form->fill();
        $this->loadMusic();
    }

    public function deleteMusic(string $filename): void
    {
        $path = public_path('timelapse_music/' . $filename);
        if (File::exists($path)) {
            File::delete($path);
            Notification::make()
                ->title('Musik Berhasil Dihapus')
                ->success()
                ->send();
            $this->loadMusic();
        }
    }
}
