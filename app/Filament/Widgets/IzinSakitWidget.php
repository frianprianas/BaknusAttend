<?php

namespace App\Filament\Widgets;

use App\Models\IzinGuruTu;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class IzinSakitWidget extends Widget implements HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.izin-sakit-widget';

    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 0;

    public ?array $data = [];

    /** @var IzinGuruTu|null */
    public ?IzinGuruTu $izinHariIni = null;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Guru', 'TU']);
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
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tipe')
                    ->label('Jenis Ketidakhadiran')
                    ->options(['Izin' => '📋 Izin', 'Sakit' => '🤒 Sakit'])
                    ->required()
                    ->placeholder('Pilih jenis...'),

                Textarea::make('alasan')
                    ->label('Alasan / Keterangan')
                    ->placeholder('Tuliskan alasan izin atau sakit Anda...')
                    ->rows(3)
                    ->required(),

                FileUpload::make('bukti')
                    ->label('Lampiran Bukti (Opsional)')
                    ->helperText('Surat dokter, surat keterangan, dll. (jpg/pdf, maks. 2MB)')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory('izin-bukti')
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $user = auth()->user();
        if (!$user) return;

        // Pastikan belum ada izin aktif hari ini
        $this->refreshIzin();
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
                ->body('Pengajuan izin/sakit Anda telah dibatalkan. Anda bisa melakukan absensi kembali.')
                ->warning()->send();
            $this->refreshIzin();
        }
    }
}
