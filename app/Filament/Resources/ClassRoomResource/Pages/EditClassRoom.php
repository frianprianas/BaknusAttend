<?php

namespace App\Filament\Resources\ClassRoomResource\Pages;

use App\Filament\Resources\ClassRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassRoom extends EditRecord
{
    protected static string $resource = ClassRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('testKirimWaKelas')
                ->label('Test Kirim WA Kelas Ini')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => "Kirim Test WA ke Wali Kelas {$record->kelas}")
                ->modalDescription(function ($record) {
                    $wa = $record->wa_wali_kelas ?: '(Belum diatur)';
                    return "Sistem akan merangkum siswa yang sudah hadir di kelas {$record->kelas} hari ini dan mengirimkannya ke nomor {$wa}.";
                })
                ->modalSubmitActionLabel('Kirim Sekarang')
                ->action(function ($record) {
                    if (empty($record->wa_wali_kelas)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Nomor WA Belum Diisi')
                            ->body('Silakan isi dan simpan Nomor WhatsApp Wali Kelas terlebih dahulu.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        $exitCode = \Illuminate\Support\Facades\Artisan::call('app:send-wali-kelas-attendance-wa', [
                            '--force' => true,
                            '--class' => $record->id,
                        ]);
                        $output = \Illuminate\Support\Facades\Artisan::output();

                        if ($exitCode === 0 && !str_contains($output, 'GAGAL')) {
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Terkirim')
                                ->body("Laporan kehadiran kelas {$record->kelas} berhasil dikirimkan ke {$record->wa_wali_kelas}.")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Mengirim')
                                ->body(trim($output) ?: 'Periksa kembali nomor WhatsApp dan gateway Fonnte.')
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
}
