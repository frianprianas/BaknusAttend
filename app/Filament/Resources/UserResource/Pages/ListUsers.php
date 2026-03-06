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
            Actions\CreateAction::make(),
        ];
    }
}
