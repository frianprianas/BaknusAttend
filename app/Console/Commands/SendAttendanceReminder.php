<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\KehadiranGuruTu;
use App\Models\IzinGuruTu;
use App\Notifications\PushBroadcastNotification;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class SendAttendanceReminder extends Command
{
    protected $signature = 'app:send-attendance-reminders';
    protected $description = 'Kirim notifikasi pengingat absen untuk Guru dan TU';

    public function handle()
    {
        // 1. Pastikan hari ini Senin - Jumat
        if (Carbon::now()->isWeekend()) {
            return;
        }

        $setting = SchoolSetting::first();
        if (!$setting || !$setting->is_reminder_active) {
            return;
        }

        $nowTime = Carbon::now()->format('H:i');
        
        $waktuMasuk = Carbon::parse($setting->reminder_masuk)->format('H:i');
        $waktuPulang = Carbon::parse($setting->reminder_pulang)->format('H:i');

        if ($nowTime === $waktuMasuk) {
            $this->kirimPengingatMasuk();
        } elseif ($nowTime === $waktuPulang) {
            $this->kirimPengingatPulang();
        }
    }

    private function kirimPengingatMasuk()
    {
        $users = User::whereIn('role', ['Guru', 'TU'])->get();
        $today = Carbon::today();

        foreach ($users as $user) {
            // Cek apakah punya izin/sakit hari ini
            $nipy = $user->nipy ?? $user->email;
            $hasIzin = IzinGuruTu::where(function($q) use ($nipy, $user) {
                    $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                })
                ->whereDate('tanggal', $today)
                ->whereIn('status', ['Diajukan', 'Disetujui'])
                ->exists();

            if ($hasIzin) continue;

            // Cek apakah sudah absen hari ini
            $hasHadir = KehadiranGuruTu::where(function($q) use ($nipy, $user) {
                    $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                })
                ->whereDate('waktu_tap', $today)
                ->exists();

            if (!$hasHadir) {
                $this->kirimNotifikasi($user, '⏰ Pengingat Absen Masuk!', 'Anda belum melakukan absen masuk hari ini. Segera absen ya!');
            }
        }
    }

    private function kirimPengingatPulang()
    {
        $users = User::whereIn('role', ['Guru', 'TU'])->get();
        $today = Carbon::today();

        foreach ($users as $user) {
            $nipy = $user->nipy ?? $user->email;
            
            // Hitung jumlah tap hari ini
            $totalTap = KehadiranGuruTu::where(function($q) use ($nipy, $user) {
                    $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                })
                ->whereDate('waktu_tap', $today)
                ->count();

            // Jika tap = 1, artinya sudah masuk tapi belum pulang
            if ($totalTap === 1) {
                $this->kirimNotifikasi($user, '⏰ Pengingat Absen Pulang!', 'Waktunya pulang! Jangan lupa untuk melakukan absen pulang.');
            }
        }
    }

    private function kirimNotifikasi(User $user, $title, $message)
    {
        if ($user->pushSubscriptions()->exists()) {
            $user->notify(new PushBroadcastNotification($title, $message));
        }

        Notification::make()
            ->title($title)
            ->body($message)
            ->icon('heroicon-o-clock')
            ->warning()
            ->sendToDatabase($user);
    }
}
