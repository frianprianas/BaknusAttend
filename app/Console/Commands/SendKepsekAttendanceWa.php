<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\KehadiranGuruTu;
use App\Models\IzinGuruTu;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendKepsekAttendanceWa extends Command
{
    protected $signature = 'app:send-kepsek-attendance-wa {--force : Paksa kirim pesan tanpa mengecek jam atau hari kerja}';
    protected $description = 'Kirim laporan ringkasan kehadiran Guru & TU hari ini ke WhatsApp Kepala Sekolah via Fonnte';

    public function handle()
    {
        $force = $this->option('force');
        $setting = SchoolSetting::first();

        // 1. Cek Hari Kerja (Senin - Jumat) jika tidak dipaksa
        if (!$force) {
            if (Carbon::now()->isWeekend()) {
                $this->info('Hari ini akhir pekan (weekend). Pengiriman dibatalkan.');
                return 0;
            }

            if (!$setting || !$setting->is_wa_kepsek_active) {
                $this->info('Fitur notifikasi WA Kepala Sekolah sedang dinonaktifkan.');
                return 0;
            }

            $nowTime = Carbon::now()->format('H:i');
            $targetTime = Carbon::parse($setting->wa_kepsek_time ?? '10:30:00')->format('H:i');

            if ($nowTime !== $targetTime) {
                // Belum waktunya kirim
                return 0;
            }
        }

        // 2. Cek Nomor WhatsApp Tujuan
        $targets = $setting?->wa_kepsek_numbers;
        if (empty($targets)) {
            $msg = 'GAGAL: Nomor WhatsApp Kepala Sekolah belum diisi di Pengaturan Sekolah.';
            $this->error($msg);
            Log::warning("SendKepsekAttendanceWa: {$msg}");
            return 1;
        }

        $this->info("Menyiapkan data kehadiran Guru & TU untuk dikirim ke {$targets}...");

        // 3. Ambil Seluruh Dewan Guru & Staff TU Aktif
        $users = User::whereIn('role', ['Guru', 'TU'])
            ->where('role', '!=', 'Test')
            ->orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada data Guru atau TU yang ditemukan.');
            return 0;
        }

        $today = Carbon::today();

        // 4. Ambil Data Presensi Hari Ini
        $kehadiranMap = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->select([
                'nipy',
                DB::raw('MIN(waktu_tap) as waktu_masuk'),
                DB::raw('MAX(waktu_tap) as waktu_pulang'),
                DB::raw('COUNT(*) as total_tap'),
                DB::raw('MAX(rfid_uid) as rfid_uid'),
                DB::raw('MAX(photo) as photo'),
                DB::raw('MAX(is_dinas_luar) as is_dinas_luar'),
                DB::raw('MAX(lokasi_dinas_luar) as lokasi_dinas_luar'),
                DB::raw("GROUP_CONCAT(keterangan SEPARATOR ' | ') as keterangan"),
            ])
            ->groupBy('nipy')
            ->get()
            ->keyBy('nipy');

        // 5. Ambil Data Izin / Sakit Hari Ini
        $izinMap = IzinGuruTu::whereDate('tanggal', $today)
            ->whereIn('status', ['Diajukan', 'Disetujui'])
            ->get()
            ->keyBy('nipy');

        $listHadir = [];
        $listIzin = [];
        $listBelumHadir = [];

        foreach ($users as $user) {
            $nipy = $user->nipy ?: $user->email;
            $hadir = $kehadiranMap->get($nipy) ?? $kehadiranMap->get($user->email) ?? $kehadiranMap->get($user->nipy);

            if ($hadir) {
                $waktuMasuk = Carbon::parse($hadir->waktu_masuk)->format('H:i');
                $ketLower = strtolower($hadir->keterangan ?? '');

                if ($hadir->is_dinas_luar) {
                    $metode = 'Dinas Luar';
                } elseif (str_contains($ketLower, 'bluetooth') || str_contains($ketLower, 'ble')) {
                    $metode = 'BLE Wemos';
                } elseif (str_contains($ketLower, 'nfc') || str_contains($ketLower, 'hp') || str_contains($ketLower, 'smartphone')) {
                    $metode = 'RFID HP';
                } elseif (!empty($hadir->photo) && $hadir->photo !== 'rfid_placeholder') {
                    $metode = 'Foto App';
                } else {
                    $metode = 'Mesin RFID';
                }

                $listHadir[] = [
                    'name'   => $user->name,
                    'role'   => $user->role,
                    'jam'    => $waktuMasuk,
                    'metode' => $metode,
                ];
            } else {
                $izin = $izinMap->get($nipy) ?? $izinMap->get($user->email) ?? $izinMap->get($user->nipy);
                if ($izin) {
                    $listIzin[] = [
                        'name'     => $user->name,
                        'role'     => $user->role,
                        'kategori' => $izin->kategori ?? 'Izin',
                        'alasan'   => $izin->keterangan ?? '-',
                    ];
                } else {
                    $listBelumHadir[] = [
                        'name' => $user->name,
                        'role' => $user->role,
                    ];
                }
            }
        }

        // 6. Kalkulasi Statistik
        $totalPersonel = $users->count();
        $totalHadir = count($listHadir);
        $totalIzin = count($listIzin);
        $totalBelum = count($listBelumHadir);
        $persenHadir = $totalPersonel > 0 ? round(($totalHadir / $totalPersonel) * 100, 1) : 0;

        $countRfid  = count(array_filter($listHadir, fn($i) => $i['metode'] === 'Mesin RFID'));
        $countFoto  = count(array_filter($listHadir, fn($i) => $i['metode'] === 'Foto App'));
        $countBle   = count(array_filter($listHadir, fn($i) => $i['metode'] === 'BLE Wemos'));
        $countNfc   = count(array_filter($listHadir, fn($i) => $i['metode'] === 'RFID HP'));
        $countDinas = count(array_filter($listHadir, fn($i) => $i['metode'] === 'Dinas Luar'));

        // Format Tanggal Indonesia
        $namaHari = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ][Carbon::now()->format('l')] ?? Carbon::now()->format('l');

        $namaBulan = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ][Carbon::now()->format('F')] ?? Carbon::now()->format('F');

        $tanggalStr = $namaHari . ', ' . Carbon::now()->format('d') . ' ' . $namaBulan . ' ' . Carbon::now()->format('Y');
        $jamStr = Carbon::now()->format('H:i');
        $schoolName = $setting?->name ?: 'SMK BAKTI NUSANTARA 666';

        // 7. Susun Pesan WhatsApp
        $msg = "📊 *LAPORAN KEHADIRAN GURU & TU*\n";
        $msg .= "🏛 *{$schoolName}*\n";
        $msg .= "📅 {$tanggalStr}\n";
        $msg .= "⏰ Rekap Pukul: *{$jamStr} WIB*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $msg .= "👥 *RINGKASAN KEHADIRAN:*\n";
        $msg .= "• Total Personel : *{$totalPersonel}* orang\n";
        $msg .= "• Sudah Hadir    : *{$totalHadir}* orang ({$persenHadir}%)\n";
        if ($countRfid > 0)  $msg .= "  ├ 🏷️ Mesin RFID   : {$countRfid} orang\n";
        if ($countFoto > 0)  $msg .= "  ├ 📱 Foto/Aplikasi: {$countFoto} orang\n";
        if ($countBle > 0)   $msg .= "  ├ 📶 BLE Wemos    : {$countBle} orang\n";
        if ($countNfc > 0)   $msg .= "  ├ 📲 RFID HP      : {$countNfc} orang\n";
        if ($countDinas > 0) $msg .= "  └ 🚗 Dinas Luar   : {$countDinas} orang\n";
        $msg .= "• Izin / Sakit   : *{$totalIzin}* orang\n";
        $msg .= "• Belum Absen    : *{$totalBelum}* orang\n\n";

        if ($totalHadir > 0) {
            $msg .= "✅ *DAFTAR HADIR ({$totalHadir}):*\n";
            foreach ($listHadir as $idx => $item) {
                $num = $idx + 1;
                $msg .= "{$num}. *{$item['name']}* ({$item['jam']} - {$item['metode']})\n";
            }
            $msg .= "\n";
        }

        if ($totalIzin > 0) {
            $msg .= "📋 *DAFTAR IZIN / SAKIT ({$totalIzin}):*\n";
            foreach ($listIzin as $idx => $item) {
                $num = $idx + 1;
                $alasanStr = ($item['alasan'] && $item['alasan'] !== '-') ? " ({$item['alasan']})" : "";
                $msg .= "{$num}. *{$item['name']}* [{$item['kategori']}{$alasanStr}]\n";
            }
            $msg .= "\n";
        }

        if ($totalBelum > 0) {
            $msg .= "⚠️ *BELUM HADIR / BELUM ABSEN ({$totalBelum}):*\n";
            foreach ($listBelumHadir as $idx => $item) {
                $num = $idx + 1;
                $msg .= "{$num}. {$item['name']} [{$item['role']}]\n";
            }
            $msg .= "\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "_Pesan otomatis dibuat & dikirim oleh Sistem BaknusAttend._";

        // 8. Kirim Pesan via FonnteService
        $result = FonnteService::sendMessage($targets, $msg);

        if ($result['success']) {
            $this->info("BERHASIL: Laporan presensi Guru & TU berhasil dikirim ke {$targets}.");
            return 0;
        } else {
            $this->error("GAGAL: " . $result['message']);
            return 1;
        }
    }
}
