<?php

namespace App\Filament\Resources\SchoolSettingResource\Pages;

use App\Filament\Resources\SchoolSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditSchoolSetting extends EditRecord
{
    protected static string $resource = SchoolSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('testKirimWa')
                ->label('Test Kirim WA Rekap')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Kirim Test Laporan WA ke Kepsek')
                ->modalDescription('Sistem akan merangkum kehadiran Guru & TU hari ini dan langsung mengirimkannya ke nomor WhatsApp yang tersimpan via Fonnte Gateway.')
                ->modalSubmitActionLabel('Kirim Sekarang')
                ->action(function () {
                    try {
                        $exitCode = \Illuminate\Support\Facades\Artisan::call('app:send-kepsek-attendance-wa', [
                            '--force' => true,
                        ]);
                        $output = \Illuminate\Support\Facades\Artisan::output();

                        if ($exitCode === 0 && !str_contains($output, 'GAGAL')) {
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Terkirim')
                                ->body('Laporan kehadiran Guru & TU berhasil dikirimkan ke WhatsApp Kepsek.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Mengirim')
                                ->body(trim($output) ?: 'Periksa kembali nomor WhatsApp dan token Fonnte.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Terjadi Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.resources.school-setting.map-picker');
    }
}
