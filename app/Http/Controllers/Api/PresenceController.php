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
}
