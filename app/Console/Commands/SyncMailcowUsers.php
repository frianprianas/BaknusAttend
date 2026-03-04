<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MailcowService;

class SyncMailcowUsers extends Command
{
    protected $signature = 'app:sync-mailcow';
    protected $description = 'Sync users from Mailcow mailboxes to local DB';

    public function handle(MailcowService $mailcow)
    {
        $this->info('Memulai sinkronisasi data dari Mailcow...');

        try {
            $count = $mailcow->syncUsers();
            $this->info("Sinkronisasi berhasil! {$count} user telah diperbarui.");
        } catch (\Exception $e) {
            $this->error("Sinkronisasi Gagal: " . $e->getMessage());
        }
    }
}
