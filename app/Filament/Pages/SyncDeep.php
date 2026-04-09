<?php

namespace App\Filament\Pages;

use App\Models\ClassRoom;
use App\Models\Student;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class SyncDeep extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Singkron Mendalam';
    protected static ?string $title = 'Singkronisasi Mendalam';
    protected static ?string $navigationGroup = 'Singkron Data';
    protected static ?int $navigationSort = 101;

    protected static string $view = 'filament.pages.sync-deep';

    public ?array $data = [];
    
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
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('csvFile')
                    ->label('Upload File CSV Acuan (Nama & Kelas)')
                    ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain'])
                    ->disk('public')
                    ->directory('temp-sync')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
                    ->whereNull('class_room_id')
                    ->orWhere('class_room_id', 0)
                    ->orWhereDoesntHave('classRoom')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Student $record): string => $record->nis ?? 'NIS Kosong'),
                TextColumn::make('created_at')
                    ->label('Tgl Kadaluwarsa/Daftar')
                    ->dateTime()
                    ->sortable(),
            ])
            ->heading('Siswa Tanpa Kelas Valid')
            ->description('Daftar siswa yang ada di database namun tidak terikat dengan kelas manapun.')
            ->emptyStateHeading('Semua siswa memiliki kelas')
            ->emptyStateDescription('Tidak ditemukan anomali data siswa tanpa kelas.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public function startSync()
    {
        try {
            $state = $this->form->getState();
            $path = $state['csvFile'] ?? null;
            
            if (!$path) {
                Notification::make()->title('Silakan pilih file terlebih dahulu')->warning()->send();
                return;
            }
            
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
            $this->addLog("Memulai singkronisasi mendalam... Delimiter: " . ($this->delimiter == ',' ? 'Koma' : 'Titik Koma'));
            
            $this->dispatch('start-deep-processing');

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

                // Deep sync logic: cari siswa dengan nama yang sama, lalu update kelasnya.
                // Jika tidak ketemu dengan nama eksis, kita buat baru
                Student::updateOrCreate(
                    ['name' => $name],
                    ['class_room_id' => $classRoom->id]
                );

                $this->addLog("[$this->currentIndex/$this->totalLines] Deep Sync: $name -> $className");
                $this->report['success']++;
            } catch (\Exception $e) {
                $this->addLog("[$this->currentIndex] Error $name: " . substr($e->getMessage(), 0, 50));
                $this->report['failed']++;
            }
        }

        fclose($file);

        if ($this->currentIndex >= $this->totalLines) {
            $this->isSyncing = false;
            $this->addLog(">>> SINGKRONISASI MENDALAM SELESAI <<<");
            
            Notification::make()->title('Singkronisasi Mendalam Selesai')->success()->send();
            $this->dispatch('refresh-table'); // Refresh table after sync
        } else {
            $this->dispatch('process-deep-next');
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
