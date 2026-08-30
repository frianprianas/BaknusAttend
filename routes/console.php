<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkronisasi Mailcow dikonfigurasi MANUAL SAJA (dapat dijalankan via tombol admin / artisan app:sync-mailcow)
// Schedule::command('app:sync-mailcow')->hourly();

Schedule::command('app:send-attendance-reminders')->everyMinute();
