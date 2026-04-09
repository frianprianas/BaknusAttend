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
                    ->disk('public')
                    ->directory('temp-sync')
                    ->required()
                    ->live(),
            ]);
    }

    public function startSync()
    {
        try {
            $this->validate([
                'csvFile' => 'required',
            ]);

            $path = is_array($this->csvFile) ? reset($this->csvFile) : $this->csvFile;
            
            if (!Storage::disk('public')->exists($path)) {
                Notification::make()->title('File tidak ditemukan di disk')->danger()->send();
                return;
            }

            $this->tempPath = Storage::disk('public')->path($path);

            if (!is_readable($this->tempPath)) {
                Notification::make()->title('File tidak dapat dibaca (Izin PHP)')->danger()->send();
                return;
            }

            $this->isSyncing = true;
            $this->logs = [];
            $this->currentIndex = 0;
            $this->report = [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'time' => 0,
            ];

            // Detect delimiter and total lines
            $file = fopen($this->tempPath, 'r');
            if (!$file) throw new \Exception("Gagal membuka file.");

            $header = fgetcsv($file, 1000, ",");
            if (!$header || count($header) < 2) {
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
            $this->addLog("Memulai singkronisasi... Delimiter: " . ($this->delimiter == ',' ? 'Koma' : 'Titik Koma'));
            
            $this->dispatch('start-processing');

        } catch (\Exception $e) {
            $this->isSyncing = false;
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function processNext()
    {
        if (!$this->isSyncing || !$this->tempPath) return;

        $file = fopen($this->tempPath, 'r');
        if (!$file) {
            $this->addLog("Error: Gagal membuka file untuk diproses.");
            $this->isSyncing = false;
            return;
        }
        
        // Skip header + current index
        for ($i = 0; $i <= $this->currentIndex; $i++) {
            fgetcsv($file, 1000, $this->delimiter);
        }

        $chunkSize = 5;
        $processed = 0;

        while ($processed < $chunkSize && ($data = fgetcsv($file, 1000, $this->delimiter)) !== FALSE) {
            $this->currentIndex++;
            $processed++;

            $name = trim($data[0] ?? '');
            $className = trim($data[1] ?? '');

            if (empty($name) || empty($className)) {
                $this->addLog("Baris {$this->currentIndex}: Lewati (Kosong)");
                $this->report['failed']++;
                continue;
            }

            try {
                // Find or create classroom
                $classRoom = ClassRoom::firstOrCreate(
                    ['kelas' => $className]
                );

                // Update or create student
                Student::updateOrCreate(
                    ['name' => $name],
                    ['class_room_id' => $classRoom->id]
                );

                $this->addLog("[$this->currentIndex/$this->totalLines] Success: $name ($className)");
                $this->report['success']++;
            } catch (\Exception $e) {
                $this->addLog("[$this->currentIndex] Error $name: " . substr($e->getMessage(), 0, 50));
                $this->report['failed']++;
            }
        }

        fclose($file);

        if ($this->currentIndex >= $this->totalLines) {
            $this->isSyncing = false;
            $this->addLog(">>> SINGKRONISASI SELESAI <<<");
            
            // Clean up file if needed
            // Storage::disk('public')->delete(is_array($this->csvFile) ? reset($this->csvFile) : $this->csvFile);
            
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
