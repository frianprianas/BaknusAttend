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
use App\Models\TimelapseMusic;
use Filament\Forms\Components\TextInput;

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
    public $musicFiles = [];
    
    public $editingMusicId = null;
    public $editingTitle = '';

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
        $this->musicFiles = TimelapseMusic::orderBy('created_at', 'desc')->get()->toArray();
        
        $path = public_path('timelapse_music');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tambah Musik Baru')
                    ->description('Upload file MP3 (Maksimal 3MB) untuk dijadikan pilihan backsound bagi user.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Musik')
                            ->placeholder('Contoh: Semangat Pagi')
                            ->required(),
                        FileUpload::make('music')
                            ->label('File MP3')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])
                            ->maxSize(3072) // 3MB
                            ->disk('public')
                            ->directory('timelapse_music')
                            ->required(),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $tempPath = $state['music'] ?? null;
        $title = $state['title'] ?? 'Tanpa Judul';

        if (!$tempPath) return;

        $storageDir = storage_path('app/public');
        $fullPath = $storageDir . '/' . $tempPath;

        if (File::exists($fullPath)) {
            $filename = basename($fullPath);
            $cleanName = time() . '_' . str_replace([' ', '#', '&'], '_', $filename);
            $targetPath = public_path('timelapse_music/' . $cleanName);
            
            File::move($fullPath, $targetPath);

            TimelapseMusic::create([
                'title' => $title,
                'filename' => $cleanName,
                'size' => round(File::size($targetPath) / 1024 / 1024, 2) . ' MB',
            ]);

            Notification::make()
                ->title('Musik Berhasil Diunggah')
                ->success()
                ->send();
        }

        $this->data = [];
        $this->form->fill();
        $this->loadMusic();
    }

    public function editTitle($id)
    {
        $music = TimelapseMusic::find($id);
        if ($music) {
            $this->editingMusicId = $id;
            $this->editingTitle = $music->title;
            // Menggunakan format array explisit untuk menghindari error named parameter di PHP
            $this->dispatch('open-modal', id: 'edit-title-modal');
        }
    }

    public function updateTitle()
    {
        $music = TimelapseMusic::find($this->editingMusicId);
        if ($music) {
            $music->update(['title' => $this->editingTitle]);
            
            Notification::make()
                ->title('Judul Diperbarui')
                ->success()
                ->send();
                
            $this->editingMusicId = null;
            $this->dispatch('close-modal', id: 'edit-title-modal');
            $this->loadMusic();
        }
    }

    public function deleteMusic($id): void
    {
        $music = TimelapseMusic::find($id);
        if ($music) {
            $path = public_path('timelapse_music/' . $music->filename);
            if (File::exists($path)) {
                File::delete($path);
            }
            
            $music->delete();

            Notification::make()
                ->title('Musik Berhasil Dihapus')
                ->success()
                ->send();
            $this->loadMusic();
        }
    }
}
