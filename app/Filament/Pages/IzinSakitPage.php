<?php

namespace App\Filament\Pages;

use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class IzinSakitPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Izin / Sakit';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort     = 2;
    protected static string $view             = 'filament.pages.izin-sakit-page';

    public ?array $data = [];

    /** @var IzinGuruTu|null */
    public ?IzinGuruTu $izinHariIni = null;

    /** @var bool - sudah absen hari ini (Masuk/Pulang) */
    public bool $sudahAbsenHariIni = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Guru', 'TU']);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Izin / Sakit';
    }

    public function mount(): void
    {
        $this->refreshIzin();
        $this->form->fill();
    }

    public function refreshIzin(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $this->izinHariIni = IzinGuruTu::whereDate('tanggal', Carbon::today())
            ->whereIn('status', ['Diajukan', 'Disetujui'])
            ->where(function ($q) use ($user) {
                $q->where('nipy', $user->nipy ?? '')
                  ->orWhere('nipy', $user->email);
            })
            ->first();

        // Cek apakah sudah ada absensi hari ini
        $nipy = $user->nipy ?? $user->email;
        $this->sudahAbsenHariIni = KehadiranGuruTu::whereDate('waktu_tap', Carbon::today())
            ->where(function ($q) use ($nipy, $user) {
                $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
            })
            ->exists();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tipe')
                    ->label('Jenis Ketidakhadiran')
                    ->options(['Izin' => '📋 Izin Tidak Masuk', 'Sakit' => '🤒 Sakit'])
                    ->required()
                    ->placeholder('Pilih jenis...'),

                Textarea::make('alasan')
                    ->label('Alasan / Keterangan')
                    ->placeholder('Tuliskan alasan izin atau sakit Anda di sini...')
                    ->rows(4)
                    ->required(),

                FileUpload::make('bukti')
                    ->label('Lampiran Bukti (Opsional)')
                    ->helperText('Surat dokter, foto, atau surat keterangan (jpg/png/pdf, maks. 2MB)')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory('izin-bukti')
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $this->refreshIzin();

        // Jika sudah ada absensi hari ini, tidak bisa mengajukan izin
        if ($this->sudahAbsenHariIni) {
            Notification::make()
                ->title('Tidak Dapat Mengajukan Izin!')
                ->body('Anda sudah tercatat hadir (Masuk/Pulang) hari ini. Tidak bisa mengajukan izin setelah absen.')
                ->danger()->send();
            return;
        }

        if ($this->izinHariIni) {
            Notification::make()
                ->title('Pengajuan Sudah Ada!')
                ->body('Anda sudah memiliki pengajuan ' . $this->izinHariIni->tipe . ' aktif hari ini.')
                ->warning()->send();
            return;
        }

        $formData = $this->form->getState();
        $nipy = $user->nipy ?? $user->email;

        IzinGuruTu::create([
            'nipy'    => $nipy,
            'tanggal' => Carbon::today(),
            'tipe'    => $formData['tipe'],
            'alasan'  => $formData['alasan'],
            'bukti'   => $formData['bukti'] ?? null,
            'status'  => 'Diajukan',
        ]);

        Notification::make()
            ->title('Pengajuan ' . $formData['tipe'] . ' Berhasil!')
            ->body('Pengajuan Anda untuk hari ini telah tercatat. Anda tidak bisa melakukan absensi hari ini.')
            ->success()->send();

        $this->refreshIzin();
        $this->form->fill();
    }

    public function batalkan(): void
    {
        $this->refreshIzin();
        if ($this->izinHariIni) {
            $this->izinHariIni->update(['status' => 'Dibatalkan']);
            Notification::make()
                ->title('Pengajuan Dibatalkan')
                ->body('Pengajuan izin/sakit Anda telah dibatalkan. Anda kini bisa melakukan absensi kembali.')
                ->warning()->send();
            $this->refreshIzin();
        }
    }
}
