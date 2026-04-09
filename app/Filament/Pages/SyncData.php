<?php

namespace App\Filament\Pages;

use App\Models\ClassRoom;
use App\Models\Student;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SyncData extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Singkron Data';
    protected static ?string $title = 'Singkron Data Siswa';
    protected static ?string $navigationGroup = 'Admin Tools';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.sync-data';

    public $csvFile;
    public $isSyncing = false;
    public $logs = [];
    public $currentIndex = 0;
    public $totalLines = 0;
    public $delimiter = ',';
    public $tempPath = '';
    
    public $report = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'time' => 0,
    ];

    public static function canAccess(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('csvFile')
                    ->label('Upload File CSV (Nama & Kelas)')
                    ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain'])
                    ->required()
                    ->live(),
            ]);
    }

    public function startSync()
    {
        $this->validate([
            'csvFile' => 'required',
        ]);

        // csvFile is an array in Filament v3 if not configured otherwise, but let's assume it's the path
        // Actually FileUpload returns a string (the path) or an array if multiple
        $path = is_array($this->csvFile) ? reset($this->csvFile) : $this->csvFile;
        
        if (!Storage::disk('public')->exists($path)) {
            Notification::make()->title('File tidak ditemukan')->danger()->send();
            return;
        }

        $this->tempPath = Storage::disk('public')->path($path);

        $this->isSyncing = true;
        $this->logs = [];
        $this->currentIndex = 0;
        $this->report = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'time' => 0,
        ];

        // Read total lines and detect delimiter
        $file = fopen($this->tempPath, 'r');
        $header = fgetcsv($file, 1000, ",");
        if (count($header) < 2) {
            rewind($file);
            $header = fgetcsv($file, 1000, ";");
            $this->delimiter = ";";
        } else {
            $this->delimiter = ",";
        }
        
        $lines = 0;
        while (fgetcsv($file, 1000, $this->delimiter)) {
            $lines++;
        }
        fclose($file);

        $this->totalLines = $lines;
        $this->report['total'] = $lines;
        $this->addLog("Memulai singkronisasi... Total " . $lines . " data ditemukan.");
        
        // Start processing the first chunk
        $this->dispatch('start-processing');
    }

    public function processNext()
    {
        if (!$this->isSyncing) return;

        $file = fopen($this->tempPath, 'r');
        
        // Skip to current index + header
        for ($i = 0; $i <= $this->currentIndex; $i++) {
            fgetcsv($file, 1000, $this->delimiter);
        }

        $chunkSize = 5; // Process 5 at a time for "terminal" feel
        $processed = 0;

        while ($processed < $chunkSize && ($data = fgetcsv($file, 1000, $this->delimiter)) !== FALSE) {
            $this->currentIndex++;
            $processed++;

            $name = trim($data[0] ?? null);
            $className = trim($data[1] ?? null);

            if (!$name || !$className) {
                $this->addLog("Baris {$this->currentIndex}: Lewati (Data tidak lengkap)");
                $this->report['failed']++;
                continue;
            }

            try {
                $classRoom = ClassRoom::firstOrCreate(['kelas' => $className]);

                Student::updateOrCreate(
                    ['name' => $name],
                    ['class_room_id' => $classRoom->id]
                );

                $this->addLog("Singkron: $name -> $className");
                $this->report['success']++;
            } catch (\Exception $e) {
                $this->addLog("Error pada $name: " . $e->getMessage());
                $this->report['failed']++;
            }
        }

        fclose($file);

        if ($this->currentIndex >= $this->totalLines) {
            $this->isSyncing = false;
            $this->addLog("Singkronisasi Selesai!");
            Notification::make()->title('Singkronisasi Selesai')->success()->send();
        } else {
            $this->dispatch('process-next');
        }
    }

    private function addLog($message)
    {
        $this->logs[] = "[" . date('H:i:s') . "] " . $message;
        if (count($this->logs) > 100) {
            array_shift($this->logs);
        }
    }
}
