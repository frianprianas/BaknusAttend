<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
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
        
        // ✅ Cek duplikat: cover semua sumber (RFID dan Web/Scan Wajah)
        // Format RFID: "MASUK - Tap RFID Mesin"
        // Format Web:  "Masuk - Presensi Mandiri (Dashboard)"
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

        // ✅ Cek duplikat: cover semua sumber (RFID dan Web/Scan Wajah)
        $alreadyAbsen = KehadiranGuruTu::where('nipy', $nipy)
            ->whereDate('waktu_tap', $currentTime)
            ->whereRaw('LOWER(keterangan) LIKE ?', [strtolower($mode) . '%'])
            ->exists();

        if ($alreadyAbsen) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => "Anda Sudah $mode!",
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
