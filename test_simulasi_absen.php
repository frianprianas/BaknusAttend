<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;

$targetEmail = 'elina_apiani@smk.baktinusantara666.sch.id';

echo "=======================================================\n";
echo " SIMULASI PRESENSI MANDIRI (MASUK & PULANG)\n";
echo " Target Email: {$targetEmail}\n";
echo "=======================================================\n\n";

// 1. Cari User di Database
$user = User::where('email', $targetEmail)->first();

if (!$user) {
    echo "[!] User dengan email '{$targetEmail}' TIDAK DITEMUKAN di database users.\n";
    echo "[!] Mencoba membuat user dummy untuk keperluan tes...\n";
    $user = new User();
    $user->name = 'Elina Apiani';
    $user->email = $targetEmail;
    $user->role = 'Guru';
    $user->nipy = 'ELINA001';
}

echo "[✓] Data User Ditemukan:\n";
echo "    - Nama  : {$user->name}\n";
echo "    - Email : {$user->email}\n";
echo "    - Role  : {$user->role}\n";
echo "    - NIPY  : " . ($user->nipy ?: '(kosong)') . "\n\n";

// 2. Jalankan Simulasi Absen Masuk
echo "-------------------------------------------------------\n";
echo " [1/2] Mensimulasikan Presensi MASUK...\n";
echo "-------------------------------------------------------\n";

$currentTime = now();
$tipeAbsens = 'Masuk';
$status = 'Hadir';
$keterangan = "{$tipeAbsens} - Presensi Mandiri (Simulasi Test)";

$isPulang = str_contains(strtolower($keterangan), 'pulang');
$tipeLabel = $isPulang ? 'Pulang' : 'Masuk';
$nowTime = $currentTime->format('H:i:s');
$waktuFormatted = $currentTime->format('d-m-Y H:i:s');
$uniqueSubject = "[BaknusAttend] Presensi {$tipeLabel} ({$nowTime})";
$userName = $user->name ?? 'Pengguna';
$userEmail = filter_var($user->email, FILTER_VALIDATE_EMAIL) ? $user->email : null;

$emailBody = "<p>Halo <b>{$userName}</b>,</p>
         <p>Aktivitas presensi kehadiran Anda telah tercatat dengan detail berikut:</p>
         <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
             <tr><td style='padding: 6px 0; font-weight: bold; width: 120px;'>Tipe Absen:</td><td><span style='background-color: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 13px;'>{$tipeLabel}</span></td></tr>
             <tr><td style='padding: 6px 0; font-weight: bold;'>Waktu:</td><td>{$waktuFormatted} WIB</td></tr>
             <tr><td style='padding: 6px 0; font-weight: bold;'>Status:</td><td><span style='background-color: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 13px;'>{$status}</span></td></tr>
             <tr><td style='padding: 6px 0; font-weight: bold;'>Keterangan:</td><td>{$keterangan}</td></tr>
         </table>
         <p style='margin-top: 15px;'>Terima kasih.</p>";

try {
    if ($user->role === 'Siswa') {
        $nis = !empty($user->nipy) ? $user->nipy : $user->email;
        KehadiranSiswa::create([
            'nis' => $nis,
            'rfid_uid' => $user->rfid ?? null,
            'waktu_tap' => $currentTime,
            'status' => $status,
            'lat' => '-6.914744',
            'long' => '107.609810',
            'photo' => 'simulasi.jpg',
            'keterangan' => $keterangan,
        ]);
    } else {
        KehadiranGuruTu::create([
            'nipy' => !empty($user->nipy) ? $user->nipy : $user->email,
            'rfid_uid' => $user->rfid ?? null,
            'waktu_tap' => $currentTime,
            'status' => $status,
            'lat' => '-6.914744',
            'long' => '107.609810',
            'photo' => 'simulasi.jpg',
            'keterangan' => $keterangan,
        ]);
    }

    echo "[✓] Record Presensi MASUK berhasil disimpan ke Database!\n";

    if ($userEmail) {
        MailService::sendNotification($userEmail, $uniqueSubject, "Presensi {$tipeLabel} Berhasil", $emailBody);
        echo "[✓] Function MailService::sendNotification DITRENGGATKAN!\n";
        echo "    Subject : {$uniqueSubject}\n";
        echo "    To      : {$userEmail}\n";
    }
} catch (\Throwable $e) {
    echo "[!] GAGAL Simulasi Masuk: " . $e->getMessage() . "\n";
}

// 3. Jalankan Simulasi Absen Pulang
echo "\n-------------------------------------------------------\n";
echo " [2/2] Mensimulasikan Presensi PULANG...\n";
echo "-------------------------------------------------------\n";

sleep(1); // Bedakan 1 detik
$currentTimePulang = now();
$tipeAbsensPulang = 'Pulang';
$keteranganPulang = "{$tipeAbsensPulang} - Presensi Mandiri (Simulasi Test)";

$isPulang = true;
$tipeLabel = 'Pulang';
$nowTimePulang = $currentTimePulang->format('H:i:s');
$waktuFormattedPulang = $currentTimePulang->format('d-m-Y H:i:s');
$uniqueSubjectPulang = "[BaknusAttend] Presensi {$tipeLabel} ({$nowTimePulang})";

$emailBodyPulang = "<p>Halo <b>{$userName}</b>,</p>
         <p>Aktivitas presensi kehadiran Anda telah tercatat dengan detail berikut:</p>
         <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
             <tr><td style='padding: 6px 0; font-weight: bold; width: 120px;'>Tipe Absen:</td><td><span style='background-color: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: 13px;'>{$tipeLabel}</span></td></tr>
             <tr><td style='padding: 6px 0; font-weight: bold;'>Waktu:</td><td>{$waktuFormattedPulang} WIB</td></tr>
             <tr><td style='padding: 6px 0; font-weight: bold;'>Status:</td><td><span style='background-color: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 13px;'>{$status}</span></td></tr>
             <tr><td style='padding: 6px 0; font-weight: bold;'>Keterangan:</td><td>{$keteranganPulang}</td></tr>
         </table>
         <p style='margin-top: 15px;'>Terima kasih.</p>";

try {
    if ($user->role === 'Siswa') {
        $nis = !empty($user->nipy) ? $user->nipy : $user->email;
        KehadiranSiswa::create([
            'nis' => $nis,
            'rfid_uid' => $user->rfid ?? null,
            'waktu_tap' => $currentTimePulang,
            'status' => $status,
            'lat' => '-6.914744',
            'long' => '107.609810',
            'photo' => 'simulasi.jpg',
            'keterangan' => $keteranganPulang,
        ]);
    } else {
        KehadiranGuruTu::create([
            'nipy' => !empty($user->nipy) ? $user->nipy : $user->email,
            'rfid_uid' => $user->rfid ?? null,
            'waktu_tap' => $currentTimePulang,
            'status' => $status,
            'lat' => '-6.914744',
            'long' => '107.609810',
            'photo' => 'simulasi.jpg',
            'keterangan' => $keteranganPulang,
        ]);
    }

    echo "[✓] Record Presensi PULANG berhasil disimpan ke Database!\n";

    if ($userEmail) {
        MailService::sendNotification($userEmail, $uniqueSubjectPulang, "Presensi {$tipeLabel} Berhasil", $emailBodyPulang);
        echo "[✓] Function MailService::sendNotification DITRENGGATKAN!\n";
        echo "    Subject : {$uniqueSubjectPulang}\n";
        echo "    To      : {$userEmail}\n";
    }
} catch (\Throwable $e) {
    echo "[!] GAGAL Simulasi Pulang: " . $e->getMessage() . "\n";
}

echo "\n=======================================================\n";
echo " SIMULASI SELESAI. Silakan cek Inbox Email '{$targetEmail}'!\n";
echo "=======================================================\n";
