<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Services\MailService;
use Illuminate\Support\Facades\Mail;

$targetEmail = 'elina_apiani@smk.baktinusantara666.sch.id';

echo "=======================================================\n";
echo " DIAGNOSTIK & SIMULASI DIAGNOSTIK EMAIL PRESENSI\n";
echo " Target Email: {$targetEmail}\n";
echo "=======================================================\n\n";

// 1. Cek Konfigurasi Mail
echo "[1] MEMERIKSA KONFIGURASI MAIL (.env):\n";
echo "    - MAIL_MAILER     : " . config('mail.default') . "\n";
echo "    - MAIL_HOST       : " . config('mail.mailers.smtp.host') . "\n";
echo "    - MAIL_PORT       : " . config('mail.mailers.smtp.port') . "\n";
echo "    - MAIL_USERNAME   : " . config('mail.mailers.smtp.username') . "\n";
echo "    - MAIL_ENCRYPTION : " . config('mail.mailers.smtp.encryption') . "\n";
echo "    - MAIL_FROM       : " . config('mail.from.address') . "\n\n";

// 2. Cari User di Database
echo "[2] MEMERIKSA DATA USER DI DATABASE:\n";
$user = User::where('email', $targetEmail)->first();

if (!$user) {
    echo "    [!] User dengan email '{$targetEmail}' TIDAK DITEMUKAN di database users.\n";
    echo "    [!] Membuat data dummy sementara untuk pengujian...\n";
    $user = new User();
    $user->name = 'Elina Apiani';
    $user->email = $targetEmail;
    $user->role = 'Guru';
    $user->nipy = 'ELINA001';
} else {
    echo "    [✓] User ditemukan:\n";
    echo "        - Name : {$user->name}\n";
    echo "        - Role : {$user->role}\n";
    echo "        - NIPY : " . ($user->nipy ?: '(kosong)') . "\n";
}
echo "\n";

// 3. Tes Pengiriman Email SMTP Langsung (Tanpa Catch Silent)
echo "[3] MENCOBA PENGIRIMAN EMAIL SMTP LANGSUNG...\n";
$nowTime = now()->format('H:i:s');
$subject = "[BaknusAttend TEST] Presensi Masuk ({$nowTime})";

try {
    Mail::raw("Ini adalah email uji coba simulasi presensi BaknusAttend untuk {$user->name} pada {$nowTime}.", function ($message) use ($targetEmail, $subject) {
        $message->from(config('mail.from.address', 'admin@smk.baktinusantara666.sch.id'), 'BaknusAttend')
                ->to($targetEmail)
                ->subject($subject);
    });
    echo "    [✓] SUCCESS: Email SMTP berhasil terkirim ke {$targetEmail}!\n";
} catch (\Throwable $e) {
    echo "    [❌] ERROR GAGAL KIRIM SMTP: " . $e->getMessage() . "\n";
    echo "    Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// 4. Tes Panggilan MailService::sendNotification
echo "[4] MENCOBA PANGGILAN MailService::sendNotification...\n";
try {
    MailService::sendNotification(
        $targetEmail,
        "[BaknusAttend SIMULASI] Presensi Pulang ({$nowTime})",
        "Presensi Pulang Berhasil",
        "<p>Halo <b>{$user->name}</b>, ini simulasi presensi pulang.</p>"
    );
    echo "    [✓] MailService::sendNotification selesai dipanggil.\n";
} catch (\Throwable $e) {
    echo "    [❌] ERROR MailService: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=======================================================\n";
echo " ISU LOG TERAKHIR DARI storage/logs/laravel.log:\n";
echo "=======================================================\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -15);
    echo implode("", $lastLines);
} else {
    echo "File log tidak ditemukan.\n";
}
echo "=======================================================\n";
