<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolSetting;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\KehadiranSiswa;
use App\Models\User;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendWaliKelasAttendanceWa extends Command
{
    protected $signature = 'app:send-wali-kelas-attendance-wa {--force : Paksa kirim pesan tanpa mengecek jam atau hari kerja} {--class= : Kirim hanya untuk ID kelas tertentu} {--wa= : Nomor WhatsApp tujuan khusus/override untuk testing}';
    protected $description = 'Kirim laporan ringkasan kehadiran siswa ke WhatsApp Wali Kelas masing-masing secara bertahap dan acak (anti-spam)';

    public function handle()
    {
        set_time_limit(0); // Mencegah timeout jika banyak kelas dengan jeda acak
        $force = $this->option('force');
        $targetClassId = $this->option('class');
        $overrideWa = $this->option('wa');
        $setting = SchoolSetting::first();

        $currentTime = Carbon::now()->format('H:i');

        // 1. Cek Hari Kerja & Status Pengaturan
        if (!$force && !$targetClassId && !$overrideWa) {
            if (Carbon::now()->isWeekend()) {
                return 0;
            }

            if (!$setting || !$setting->is_wa_wali_kelas_active) {
                return 0;
            }
        }

        // 2. Ambil Kelas yang Aktif Notifikasi WA
        $query = ClassRoom::query();
        if ($targetClassId) {
            $query->where('id', $targetClassId);
        } elseif ($overrideWa) {
            $query->where('is_wa_active', true);
        } else {
            $query->where('is_wa_active', true)
                ->whereNotNull('wa_wali_kelas')
                ->where('wa_wali_kelas', '!=', '');

            // Jika berjalan otomatis via scheduler rutin (tanpa force),
            // kirim hanya untuk kelas yang Jam & Menit kirimnya cocok dengan menit saat ini!
            if (!$force) {
                $query->where(function ($q) use ($currentTime) {
                    $q->whereRaw("TIME_FORMAT(jam_kirim_wa, '%H:%i') = ?", [$currentTime])
                      ->orWhere(function ($sub) use ($currentTime) {
                          $sub->whereNull('jam_kirim_wa')
                              ->whereRaw("? = '08:00'", [$currentTime]);
                      });
                });
            }
        }

        $classes = $query->get();

        if ($classes->isEmpty()) {
            if ($force || $targetClassId || $overrideWa) {
                $this->warn('Tidak ada kelas aktif dengan nomor WA Wali Kelas yang terdaftar.');
            }
            return 0;
        }

        // Acak urutan kirim kelas agar pengiriman lebih natural dan tidak berpola
        if (!$targetClassId) {
            $classes = $classes->shuffle();
        }

        $today = Carbon::today();
        $totalClasses = $classes->count();
        $this->info("Menyiapkan pengiriman laporan presensi siswa ke {$totalClasses} kelas...");

        $minDelay = $setting?->wa_delay_min_seconds ?? 15;
        $maxDelay = $setting?->wa_delay_max_seconds ?? 40;
        if ($minDelay > $maxDelay) $maxDelay = $minDelay + 10;

        // Tanggal dan Jam
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

        $sentCount = 0;
        $failedCount = 0;

        foreach ($classes as $index => $classRoom) {
            $waTarget = $overrideWa ?: trim($classRoom->wa_wali_kelas ?? '');
            if (empty($waTarget)) {
                $this->warn("Kelas {$classRoom->kelas}: Nomor WA Wali Kelas kosong, dilewati.");
                continue;
            }

            // Identifikasi nama Wali Kelas
            $waliUser = null;
            if ($classRoom->nipy) {
                $waliUser = User::where('nipy', $classRoom->nipy)->orWhere('email', $classRoom->nipy)->first();
            }
            $namaWali = $waliUser?->name ?: ('Bapak/Ibu Wali Kelas ' . $classRoom->kelas);

            // Ambil siswa dalam kelas ini
            $students = Student::where('class_room_id', $classRoom->id)->orderBy('name', 'asc')->get();
            $totalStudents = $students->count();

            if ($totalStudents === 0) {
                $this->warn("Kelas {$classRoom->kelas}: Belum memiliki siswa terdaftar, dilewati.");
                continue;
            }

            $studentNises = $students->pluck('nis')->toArray();

            // Ambil data presensi siswa hari ini untuk kelas ini
            $kehadiranMap = KehadiranSiswa::whereDate('waktu_tap', $today)
                ->whereIn('nis', $studentNises)
                ->select([
                    'nis',
                    DB::raw('MIN(waktu_tap) as waktu_masuk'),
                    DB::raw('COUNT(*) as total_tap'),
                    DB::raw('MAX(rfid_uid) as rfid_uid'),
                    DB::raw('MAX(photo) as photo'),
                    DB::raw("GROUP_CONCAT(keterangan SEPARATOR ' | ') as keterangan"),
                ])
                ->groupBy('nis')
                ->get()
                ->keyBy('nis');

            $listHadir = [];
            foreach ($students as $student) {
                $hadir = $kehadiranMap->get($student->nis);
                if ($hadir) {
                    $waktuMasuk = Carbon::parse($hadir->waktu_masuk)->format('H:i');
                    $ketLower = strtolower($hadir->keterangan ?? '');

                    if (str_contains($ketLower, 'bluetooth') || str_contains($ketLower, 'ble')) {
                        $metode = 'BLE Wemos';
                    } elseif (str_contains($ketLower, 'nfc') || str_contains($ketLower, 'hp') || str_contains($ketLower, 'smartphone')) {
                        $metode = 'RFID HP';
                    } elseif (!empty($hadir->photo) && $hadir->photo !== 'rfid_placeholder') {
                        $metode = 'Foto App';
                    } else {
                        $metode = 'Mesin RFID';
                    }

                    $listHadir[] = [
                        'name'   => $student->name,
                        'nis'    => $student->nis,
                        'jam'    => $waktuMasuk,
                        'metode' => $metode,
                    ];
                }
            }

            $totalHadir = count($listHadir);
            $persenHadir = $totalStudents > 0 ? round(($totalHadir / $totalStudents) * 100, 1) : 0;

            $countRfid = count(array_filter($listHadir, fn($i) => $i['metode'] === 'Mesin RFID'));
            $countFoto = count(array_filter($listHadir, fn($i) => $i['metode'] === 'Foto App'));
            $countBle  = count(array_filter($listHadir, fn($i) => $i['metode'] === 'BLE Wemos'));
            $countNfc  = count(array_filter($listHadir, fn($i) => $i['metode'] === 'RFID HP'));

            // Variasi Kalimat Anti-Spam (Berbeda untuk setiap kelas)
            $greetings = [
                "Selamat pagi {$namaWali}, semoga sehat dan lancar selalu aktivitasnya.",
                "Assalamu'alaikum Wr. Wb. Selamat pagi {$namaWali}, salam hangat di pagi hari.",
                "Selamat pagi Yth. {$namaWali}, semoga hari ini penuh berkah dan kemudahan.",
                "Semangat pagi {$namaWali}, izin menyampaikan pembaruan presensi kelas hari ini.",
                "Salam hormat {$namaWali}, semoga senantiasa diberikan kelancaran hari ini.",
            ];

            $intros = [
                "Berikut laporan ringkasan kehadiran siswa-siswi kelas *{$classRoom->kelas}* per pukul {$jamStr} WIB:",
                "Berikut pembaruan data kehadiran siswa kelas *{$classRoom->kelas}* yang telah tercatat di sistem:",
                "Izin melaporkan kehadiran kelas *{$classRoom->kelas}* pagi ini ({$tanggalStr}, {$jamStr} WIB):",
                "Update data presensi anak-anak kelas *{$classRoom->kelas}* per {$jamStr} WIB adalah sebagai berikut:",
                "Laporan presensi siswa kelas *{$classRoom->kelas}* pada hari {$namaHari} tercatat per {$jamStr} WIB:",
            ];

            $closings = [
                "Terima kasih atas perhatian dan bimbingan Bapak/Ibu Wali Kelas. Selamat beraktivitas!",
                "Semoga proses belajar mengajar hari ini berjalan lancar. Terima kasih banyak Bapak/Ibu.",
                "Demikian informasi kehadiran kelas kami sampaikan. Semangat membimbing siswa-siswi kita!",
                "Terima kasih atas kerja sama dan dedikasi Bapak/Ibu demi putra-putri kita.",
                "Pesan otomatis dibuat & dikirim oleh Sistem BaknusAttend.",
            ];

            $salam = $greetings[array_rand($greetings)];
            $intro = $intros[array_rand($intros)];
            $penutup = $closings[array_rand($closings)];

            // Susun Pesan WhatsApp
            $msg = "📊 *PRESENSI KELAS {$classRoom->kelas}*\n";
            $msg .= "🏛 *{$schoolName}*\n";
            $msg .= "📅 {$tanggalStr} | ⏰ {$jamStr} WIB\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";

            $msg .= "{$salam}\n\n";
            $msg .= "📢 *Catatan:*\n";
            $msg .= "Mohon Bapak/Ibu untuk selalu mengingatkan siswanya melakukan presensi.\n\n";
            $msg .= "{$intro}\n\n";

            $msg .= "👥 *RINGKASAN KEHADIRAN:*\n";
            $msg .= "• Siswa Hadir : *{$totalHadir}* dari *{$totalStudents}* anak ({$persenHadir}%)\n";
            if ($countRfid > 0) $msg .= "  ├ 🏷️ Mesin RFID   : {$countRfid} anak\n";
            if ($countFoto > 0) $msg .= "  ├ 📱 Foto/Aplikasi: {$countFoto} anak\n";
            if ($countBle > 0)  $msg .= "  ├ 📶 BLE Wemos    : {$countBle} anak\n";
            if ($countNfc > 0)  $msg .= "  ├ 📲 RFID HP      : {$countNfc} anak\n";
            $msg .= "\n";

            if ($totalHadir > 0) {
                $msg .= "✅ *DAFTAR SISWA YANG SUDAH HADIR ({$totalHadir}):*\n";
                foreach ($listHadir as $idx => $item) {
                    $num = $idx + 1;
                    $msg .= "{$num}. *{$item['name']}* ({$item['jam']} - {$item['metode']})\n";
                }
                $msg .= "\n";
            } else {
                $msg .= "ℹ️ _Belum ada siswa kelas {$classRoom->kelas} yang tercatat presensi hingga pukul {$jamStr} WIB._\n\n";
            }

            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "_{$penutup}_";

            // Kirim via FonnteService
            $this->line("[Kirim " . ($index + 1) . "/{$totalClasses}] Kelas {$classRoom->kelas} -> {$waTarget}...");
            $res = FonnteService::sendMessage($waTarget, $msg);

            if ($res['success']) {
                $this->info("  -> BERHASIL terkirim ke {$waTarget}");
                $sentCount++;
            } else {
                $this->error("  -> GAGAL: " . ($res['message'] ?? 'Error tidak diketahui'));
                $failedCount++;
            }

            // Jeda Acak (Random Delay) antara 15 - 40 detik jika masih ada kelas berikutnya (Anti-Spam WA)
            if ($index < $totalClasses - 1 && !$targetClassId) {
                $randomWait = rand($minDelay, $maxDelay);
                $this->comment("  Menunggu jeda natural {$randomWait} detik sebelum kelas berikutnya...");
                sleep($randomWait);
            }
        }

        $this->info("Selesai! Berhasil terkirim: {$sentCount}, Gagal: {$failedCount}.");
        return 0;
    }
}
