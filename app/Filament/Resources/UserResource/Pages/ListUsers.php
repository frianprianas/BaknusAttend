<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\MailcowService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncMailcow')
                ->label('Sinkron Mailcow')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function (MailcowService $mailcowService) {
                    try {
                        $count = $mailcowService->syncUsers();
                        Notification::make()
                            ->title('Sinkronisasi Berhasil')
                            ->body("Berhasil menyinkronkan {$count} akun dari Mailcow.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('broadcastPWA')
                ->label('Broadcast Pesan Massal')
                ->icon('heroicon-o-megaphone')
                ->color('warning')
                ->modalHeading('Kirim Pengumuman ke Semua HP')
                ->modalDescription('Pesan ini akan dikirimkan ke SELURUH Guru, TU, dan Siswa yang sudah mengaktifkan notifikasi PWA di HP mereka.')
                ->form([
                    \Filament\Forms\Components\TextInput::make('title')
                        ->label('Judul Pengumuman')
                        ->default('📢 PENGUMUMAN SEKOLAH')
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Isi Pesan')
                        ->placeholder('Tulis pengumuman penting di sini...')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $users = \App\Models\User::all()->filter(fn ($user) => $user->pushSubscriptions()->exists());
                    
                    if ($users->isEmpty()) {
                         Notification::make()->title('Gagal: Tidak ada HP terdaftar')->danger()->send();
                         return;
                    }

                    $successCount = 0;
                    foreach ($users as $user) {
                        try {
                            // 1. Kirim PWA Push (Background)
                            $user->notify(new \App\Notifications\PushBroadcastNotification(
                                $data['title'],
                                $data['message']
                            ));

                            // 2. Simpan ke Lonceng (Database)
                            Notification::make()
                                ->title($data['title'])
                                ->body($data['message'])
                                ->icon('heroicon-o-megaphone')
                                ->warning()
                                ->sendToDatabase($user);

                            $successCount++;
                        } catch (\Exception $e) { /* skip fails */ }
                    }

                    Notification::make()
                        ->title('Broadcast Terkirim!')
                        ->body("Pesan telah berhasil diteruskan ke {$successCount} perangkat aktif.")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
