<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\Auth\LoginResponse::class
        );
    }

    public function boot(): void
    {
        if (str_contains(config('app.url'), 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Auth::provider('mailcow', function ($app, array $config) {
            return new \App\Providers\MailcowUserProvider($app['hash'], $config['model']);
        });

        // Event listener for Student presence
        \App\Models\KehadiranSiswa::created(function ($model) {
            $user = \App\Models\User::where('email', 'like', $model->nis . '@%')->first();
            $email = $user ? $user->email : $model->nis . '@smk.baktinusantara666.sch.id';
            $name = $user ? $user->name : 'Siswa';
            $waktu = \Carbon\Carbon::parse($model->waktu_tap)->format('d-m-Y H:i:s');
            
            \App\Services\MailService::sendNotification(
                $email,
                "[BaknusAttend] Notifikasi Presensi",
                "Presensi Kehadiran Berhasil",
                "<p>Halo <b>{$name}</b>,</p>
                 <p>Aktivitas presensi kehadiran Anda telah tercatat dengan detail berikut:</p>
                 <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                     <tr><td style='padding: 6px 0; font-weight: bold; width: 120px;'>Waktu Tap:</td><td>{$waktu} WIB</td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Status:</td><td><span style='background-color: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 13px;'>{$model->status}</span></td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Keterangan:</td><td>{$model->keterangan}</td></tr>
                 </table>
                 <p style='margin-top: 15px;'>Terima kasih.</p>"
            );
        });

        // Event listener for Guru/TU presence
        \App\Models\KehadiranGuruTu::created(function ($model) {
            $user = \App\Models\User::where('nipy', $model->nipy)->orWhere('email', $model->nipy)->first();
            $email = $user ? $user->email : $model->nipy;
            $name = $user ? $user->name : 'Guru/Staff';
            $waktu = \Carbon\Carbon::parse($model->waktu_tap)->format('d-m-Y H:i:s');
            
            \App\Services\MailService::sendNotification(
                $email,
                "[BaknusAttend] Notifikasi Presensi",
                "Presensi Kehadiran Berhasil",
                "<p>Halo <b>{$name}</b>,</p>
                 <p>Aktivitas presensi kehadiran Anda telah tercatat dengan detail berikut:</p>
                 <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                     <tr><td style='padding: 6px 0; font-weight: bold; width: 120px;'>Waktu Tap:</td><td>{$waktu} WIB</td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Status:</td><td><span style='background-color: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 13px;'>{$model->status}</span></td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Keterangan:</td><td>{$model->keterangan}</td></tr>
                 </table>
                 <p style='margin-top: 15px;'>Terima kasih.</p>"
            );
        });

        // Event listener for Guru/TU permissions (Izin/Sakit)
        \App\Models\IzinGuruTu::created(function ($model) {
            $user = \App\Models\User::where('nipy', $model->nipy)->orWhere('email', $model->nipy)->first();
            $email = $user ? $user->email : $model->nipy;
            $name = $user ? $user->name : 'Guru/Staff';
            $tanggal = \Carbon\Carbon::parse($model->tanggal)->format('d-m-Y');
            
            \App\Services\MailService::sendNotification(
                $email,
                "[BaknusAttend] Notifikasi Izin/Sakit",
                "Pengajuan Izin/Sakit Berhasil",
                "<p>Halo <b>{$name}</b>,</p>
                 <p>Pengajuan izin/sakit Anda telah dicatat oleh sistem dengan detail berikut:</p>
                 <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                     <tr><td style='padding: 6px 0; font-weight: bold; width: 120px;'>Tanggal:</td><td>{$tanggal}</td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Tipe:</td><td><span style='background-color: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-size: 13px;'>{$model->tipe}</span></td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Status:</td><td>{$model->status}</td></tr>
                     <tr><td style='padding: 6px 0; font-weight: bold;'>Alasan:</td><td>{$model->alasan}</td></tr>
                 </table>
                 <p style='margin-top: 15px;'>Terima kasih.</p>"
            );
        });
    }
}
