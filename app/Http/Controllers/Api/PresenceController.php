<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\IzinGuruTu;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; 
use PhpMqtt\Client\MqttClient;      
use PhpMqtt\Client\ConnectionSettings;

class PresenceController extends Controller
{
    public function store(Request $request)
    {
        // Arduino mengirim "rfid_uid" dan "status" (MASUK atau PULANG)
        $rfid = str_replace(' ', '', strtoupper($request->rfid_uid));
        $mode = strtoupper($request->status); // Tombol Fisik: MASUK / PULANG

        // Cari di tabel Students
        $student = Student::where('rfid', $rfid)->first();
        if ($student) {
            return $this->handleStudentPresence($student, $rfid, $mode);
        }

        // Cari di tabel Users (Guru/TU)
        $user = User::where('rfid', $rfid)->first();
        if ($user) {
            return $this->handleUserPresence($user, $rfid, $mode);
        }

        return response()->json([
            'status' => 'ERROR',
            'message' => 'Kartu tidak terdaftar!'
        ]);
    }

    private function handleStudentPresence($student, $rfid, $mode)
    {
        $currentTime = Carbon::now();
        
        // ✅ Layer 0: Urutan wajib — tidak bisa PULANG sebelum ada MASUK
        if ($mode === 'PULANG') {
            $hasMasuk = KehadiranSiswa::where('nis', $student->nis)
                ->whereDate('waktu_tap', $currentTime)
                ->whereRaw('LOWER(keterangan) LIKE ?', ['masuk%'])
                ->exists();
            if (!$hasMasuk) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => 'Belum Absen MASUK Hari Ini!',
                ]);
            }
        }

        // ✅ Layer 1: Cek duplikat mode yang sama (case-insensitive)
        $alreadyAbsen = KehadiranSiswa::where('nis', $student->nis)
            ->whereDate('waktu_tap', $currentTime)
            ->whereRaw('LOWER(keterangan) LIKE ?', [strtolower($mode) . '%'])
            ->exists();

        if ($alreadyAbsen) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => "Sudah Absen $mode!",
            ]);
        }

        // Tentukan status (Hadir/Terlambat) hanya untuk mode MASUK
        $statusRecord = 'Hadir';
        if ($mode === 'MASUK' && $currentTime->format('H:i') > '07:15') {
            $statusRecord = 'Terlambat';
        }

        $kehadiran = KehadiranSiswa::create([
            'nis'        => $student->nis,
            'rfid_uid'   => $rfid,
            'waktu_tap'  => $currentTime,
            'status'     => $statusRecord,
            'keterangan' => $mode . ' - Tap RFID Mesin',
            'photo'      => 'rfid_placeholder', // Penanda foto dari mesin RFID
        ]);

        // ✅ KIRIM TRIGGER KE MQTT (Pemicu Kamera) - Jalur Latar Belakang (Anti-Lemot)
        dispatch(function () use ($student, $kehadiran) {
            $this->dispatchMqttTrigger($student->nis, $student->name, 'siswa', $kehadiran->id);
        })->afterResponse();

        return response()->json([
            'status'  => 'SUCCESS',
            'message' => "Absen $mode Berhasil!",
            'data'    => [
                'nis'              => (string)$student->nis, // Pastikan jadi String
                'nama'             => (string)$student->name,
                'kelas'            => (string)($student->classRoom ? $student->classRoom->kelas : '-'),
                'status_kehadiran' => $mode,
                'server_time'      => $currentTime->format('Y-m-d H:i:s'),
                'id_kehadiran'     => (string)$kehadiran->id,
            ]
        ]);
    }

    private function handleUserPresence($user, $rfid, $mode)
    {
        $currentTime = Carbon::now();
        $nipy = $user->nipy ?? $user->email;

        // ✅ Layer -1: Cek Status Izin / Sakit
        if (IzinGuruTu::hasActiveIzinToday($nipy, $user->email)) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Anda Sedang Izin/Sakit!',
            ]);
        }

        // ✅ Identifikasi user: cari pakai NIPY dan EMAIL sekaligus
        //    (agar tidak terlewat jika ada user yang NIPY-nya kosong)
        $baseQuery = KehadiranGuruTu::where(function ($q) use ($user, $nipy) {
            $q->where('nipy', $nipy)
              ->orWhere('nipy', $user->email);
        })->whereDate('waktu_tap', $currentTime);

        // Layer 1: Jika sudah ada 2 data atau lebih (Masuk + Pulang sudah lengkap)
        if ($baseQuery->count() >= 2) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Absen Hari Ini Sudah Lengkap!',
            ]);
        }

        // ✅ Layer 0: Urutan wajib — tidak bisa PULANG sebelum MASUK
        if ($mode === 'PULANG') {
            $hasMasuk = (clone $baseQuery)
                ->whereRaw('LOWER(keterangan) LIKE ?', ['masuk%'])
                ->exists();
            if (!$hasMasuk) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => 'Belum Absen MASUK Hari Ini!',
                ]);
            }
        }

        // ✅ Layer 2: Cek mode spesifik (case-insensitive, cover RFID & Web)
        $alreadyThisMode = (clone $baseQuery)
            ->whereRaw('LOWER(keterangan) LIKE ?', [strtolower($mode) . '%'])
            ->exists();

        if ($alreadyThisMode) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => "Anda Sudah Absen $mode Hari Ini!",
            ]);
        }

        $kehadiran = KehadiranGuruTu::create([
            'nipy'       => $nipy,
            'rfid_uid'   => $rfid,
            'waktu_tap'  => $currentTime,
            'status'     => 'Hadir',
            'keterangan' => $mode . ' - Tap RFID Mesin',
            'photo'      => 'rfid_placeholder', // Penanda foto dari mesin RFID
        ]);

        // ✅ KIRIM TRIGGER KE MQTT (Pemicu Kamera) - Jalur Latar Belakang (Anti-Lemot)
        dispatch(function () use ($nipy, $user, $kehadiran) {
            $this->dispatchMqttTrigger($nipy, $user->name, 'guru', $kehadiran->id);
        })->afterResponse();

        return response()->json([
            'status'  => 'SUCCESS',
            'message' => "Absen $mode Berhasil!",
            'data'    => [
                'nis'              => (string)$nipy,
                'nama'             => (string)$user->name,
                'kelas'            => 'GURU/TU',
                'status_kehadiran' => $mode,
                'server_time'      => $currentTime->format('Y-m-d H:i:s'),
                'id_kehadiran'     => (string)$kehadiran->id,
            ]
        ]);
    }

    public function getDateTime()
    {
        $now = Carbon::now();
        $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        return response()->json(['date' => $now->format('Y-m-d'), 'time' => $now->format('H:i:s'), 'day' => $days[$now->format('l')]]);
    }

    public function getDashboardStats(Request $request)
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        $expectedKey = env('DASHBOARD_API_KEY', 'baknus_secret_dashboard_key_2026');

        if (empty($apiKey) || $apiKey !== $expectedKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $today = Carbon::today();

        // 1. Guru attendance
        $guruPresent = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->whereHas('user', function ($q) {
                $q->where('role', 'Guru');
            })
            ->distinct('nipy')
            ->count('nipy');

        $totalGuru = User::where('role', 'Guru')->count();

        // 2. TU attendance
        $tuPresent = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->whereHas('user', function ($q) {
                $q->where('role', 'TU');
            })
            ->distinct('nipy')
            ->count('nipy');

        $totalTU = User::where('role', 'TU')->count();

        // 3. Siswa attendance
        $siswaPresent = KehadiranSiswa::whereDate('waktu_tap', $today)
            ->distinct('nis')
            ->count('nis');

        $totalSiswa = Student::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'tanggal' => $today->format('Y-m-d'),
                'guru' => [
                    'hadir' => $guruPresent,
                    'total' => $totalGuru,
                ],
                'tu' => [
                    'hadir' => $tuPresent,
                    'total' => $totalTU,
                ],
                'siswa' => [
                    'hadir' => $siswaPresent,
                    'total' => $totalSiswa,
                ]
            ]
        ]);
    }

    /**
     * 🔥 Fungsi untuk mengirim perintah capture ke MQTT
     */
    private function dispatchMqttTrigger($id, $name, $type, $attendanceId)
    {
        try {
            $mqtt = new MqttClient(env('MQTT_HOST', 'mosquitto'), 1883, 'baknus_trigger');
            $mqtt->connect();
            
            $payload = json_encode([
                'id_kehadiran' => (string)$attendanceId,
                'nis'          => (string)$id,
                'nama'         => (string)$name,
                'tipe'         => (string)$type,
                'action'       => 'capture',
                'timestamp'    => now()->toDateTimeString()
            ]);

            // Kirim ke topic sekolah Mas
            $mqtt->publish('baknusattend/trigger/camera', $payload, 0);
            $mqtt->disconnect();
        } catch (\Exception $e) {
            \Log::error("MQTT Trigger Failed: " . $e->getMessage());
        }
    }

    public function getUserStats(Request $request)
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        $expectedKey = env('DASHBOARD_API_KEY', 'baknus_secret_dashboard_key_2026');

        if (empty($apiKey) || $apiKey !== $expectedKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $email = $request->query('email');
        if (empty($email)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email parameter is required'
            ], 400);
        }

        // Find user by email
        $user = User::where('email', strtolower($email))->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $month = $request->query('month');
        $year = $request->query('year');

        if ($month && $year) {
            $date = Carbon::createFromDate((int)$year, (int)$month, 1);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
        } else {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
        }

        $totalKehadiran = 0;
        $detailKehadiran = [];

        if (strtolower($user->role) === 'siswa') {
            // Find Student record using NIS extracted from email or directly
            $nis = null;
            if (preg_match('/^([0-9]+)@/', $user->email, $matches)) {
                $nis = $matches[1];
            } else {
                $nis = explode('@', $user->email)[0];
            }

            $student = Student::where('nis', $nis)->first();
            
            if ($student) {
                // Get all tap records for student in this month
                $taps = KehadiranSiswa::where('nis', $student->nis)
                    ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
                    ->orderBy('waktu_tap', 'desc')
                    ->get();

                // Group by date to count unique presence days
                $presentDays = [];
                foreach ($taps as $tap) {
                    $date = Carbon::parse($tap->waktu_tap)->format('Y-m-d');
                    if (strtolower($tap->status) !== 'alpa') {
                        $presentDays[$date] = true;
                    }
                    
                    $photoUrl = null;
                    if (!empty($tap->photo) && $tap->photo !== 'rfid_placeholder') {
                        $photoUrl = url('storage/' . ltrim($tap->photo, '/'));
                    }

                    $detailKehadiran[] = [
                        'waktu_tap' => $tap->waktu_tap,
                        'status' => $tap->status,
                        'keterangan' => $tap->keterangan,
                        'lat' => $tap->lat,
                        'long' => $tap->long,
                        'is_dinas_luar' => $tap->is_dinas_luar,
                        'lokasi_dinas_luar' => $tap->lokasi_dinas_luar,
                        'photo_url' => $photoUrl,
                    ];
                }
                $totalKehadiran = count($presentDays);
            }
        } else {
            // Guru/TU
            $nipy = $user->nipy ?? $user->email;

            $taps = KehadiranGuruTu::where(function ($q) use ($user, $nipy) {
                    $q->where('nipy', $nipy)
                      ->orWhere('nipy', $user->email);
                })
                ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
                ->orderBy('waktu_tap', 'desc')
                ->get();

            $presentDays = [];
            foreach ($taps as $tap) {
                $date = Carbon::parse($tap->waktu_tap)->format('Y-m-d');
                if (strtolower($tap->status) !== 'alpa') {
                    $presentDays[$date] = true;
                }

                $photoUrl = null;
                if (!empty($tap->photo) && $tap->photo !== 'rfid_placeholder') {
                    $photoUrl = url('storage/' . ltrim($tap->photo, '/'));
                }

                $detailKehadiran[] = [
                    'waktu_tap' => $tap->waktu_tap,
                    'status' => $tap->status,
                    'keterangan' => $tap->keterangan,
                    'lat' => $tap->lat,
                    'long' => $tap->long,
                    'is_dinas_luar' => $tap->is_dinas_luar,
                    'lokasi_dinas_luar' => $tap->lokasi_dinas_luar,
                    'photo_url' => $photoUrl,
                ];
            }
            $totalKehadiran = count($presentDays);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'total_kehadiran_bulan_ini' => $totalKehadiran,
                'detail_kehadiran' => $detailKehadiran,
            ]
        ]);
    }

    /**
     * Endpoint API untuk mendapatkan daftar email user yang BELUM presensi hari ini
     * GET /api/presence/unattended-emails?type=masuk atau type=pulang
     */
    public function getUnattendedEmails(Request $request)
    {
        $type = strtolower($request->query('type', 'masuk')); // 'masuk' atau 'pulang'
        $today = Carbon::today();

        // 1. Ambil NIPY / Email Guru/TU yang SUDAH presensi sesuai type hari ini
        $guruTuQuery = KehadiranGuruTu::whereDate('waktu_tap', $today);
        if ($type === 'pulang') {
            $guruTuQuery->where(function($q) {
                $q->whereRaw('LOWER(keterangan) LIKE ?', ['%pulang%'])
                  ->orWhereRaw('LOWER(status) LIKE ?', ['%pulang%']);
            });
        }
        $alreadyAttendedGuruTuNipy = $guruTuQuery->pluck('nipy')->toArray();

        // Izin / Sakit Guru/TU dianggap tidak perlu dikirimi pengingat
        $izinsGuruTu = \App\Models\IzinGuruTu::whereDate('tanggal', $today)
            ->whereIn('status', ['Diajukan', 'Disetujui'])
            ->pluck('nipy')
            ->toArray();

        $excludeGuruTuIdentifiers = array_unique(array_merge($alreadyAttendedGuruTuNipy, $izinsGuruTu));

        // Cari Email Guru/TU yang BELUM presensi
        $unattendedGuruTuEmails = User::whereIn('role', ['Guru', 'TU'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->filter(function($user) use ($excludeGuruTuIdentifiers) {
                $nipy = !empty($user->nipy) ? $user->nipy : $user->email;
                return !in_array($user->nipy, $excludeGuruTuIdentifiers) && !in_array($user->email, $excludeGuruTuIdentifiers);
            })
            ->pluck('email')
            ->toArray();

        // 2. Ambil NIS / Email Siswa yang SUDAH presensi sesuai type hari ini
        $siswaQuery = KehadiranSiswa::whereDate('waktu_tap', $today);
        if ($type === 'pulang') {
            $siswaQuery->where(function($q) {
                $q->whereRaw('LOWER(keterangan) LIKE ?', ['%pulang%'])
                  ->orWhereRaw('LOWER(status) LIKE ?', ['%pulang%']);
            });
        }
        $alreadyAttendedSiswaNis = $siswaQuery->pluck('nis')->toArray();

        $unattendedSiswaEmails = User::where('role', 'Siswa')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->filter(function($user) use ($alreadyAttendedSiswaNis) {
                $nis = !empty($user->nipy) ? $user->nipy : $user->email;
                return !in_array($nis, $alreadyAttendedSiswaNis) && !in_array($user->email, $alreadyAttendedSiswaNis);
            })
            ->pluck('email')
            ->toArray();

        $allUnattendedEmails = array_values(array_unique(array_merge($unattendedGuruTuEmails, $unattendedSiswaEmails)));

        return response()->json([
            'status' => 'SUCCESS',
            'type' => $type,
            'date' => $today->format('Y-m-d'),
            'total_unattended' => count($allUnattendedEmails),
            'emails' => $allUnattendedEmails
        ]);
    }
}
