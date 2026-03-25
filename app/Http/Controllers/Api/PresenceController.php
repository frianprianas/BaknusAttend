<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function store(Request $request)
    {
        $rfid = str_replace(' ', '', strtoupper($request->rfid_uid));
        $statusTap = strtoupper($request->status); // MASUK atau PULANG

        // Cari di tabel Students
        $student = Student::where('rfid', $rfid)->first();
        if ($student) {
            return $this->handleStudentPresence($student, $rfid, $statusTap);
        }

        // Cari di tabel Users (Guru/TU)
        $user = User::where('rfid', $rfid)->first();
        if ($user) {
            return $this->handleUserPresence($user, $rfid, $statusTap);
        }

        return response()->json([
            'status' => 'ERROR',
            'message' => 'Kartu tidak terdaftar!'
        ]);
    }

    private function handleStudentPresence($student, $rfid, $statusTap)
    {
        $currentTime = Carbon::now();
        $status = 'Hadir';

        // Logika keterlambatan sederhana (contoh: di atas jam 07:05 dianggap telat)
        if ($statusTap === 'MASUK' && $currentTime->format('H:i') > '07:05') {
            $status = 'Terlambat';
        }

        $kehadiran = KehadiranSiswa::create([
            'nis' => $student->nis,
            'rfid_uid' => $rfid,
            'waktu_tap' => $currentTime,
            'status' => $status,
            'keterangan' => 'Tap RFID (' . $statusTap . ')',
        ]);

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Absensi Berhasil!',
            'data' => [
                'nipy' => $student->nis, // ESP32 expect nipy
                'nama' => $student->name,
                'id_kehadiran' => (string) $kehadiran->id,
                'server_time' => $currentTime->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    private function handleUserPresence($user, $rfid, $statusTap)
    {
        $currentTime = Carbon::now();
        $status = 'Hadir'; // Tidak ada logika terlambat untuk guru

        $kehadiran = KehadiranGuruTu::create([
            'nipy' => $user->nipy ?? $user->email,
            'rfid_uid' => $rfid,
            'waktu_tap' => $currentTime,
            'status' => $status,
            'keterangan' => 'Tap RFID (' . $statusTap . ')',
        ]);

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Absensi Berhasil!',
            'data' => [
                'nipy' => $user->nipy ?? '-',
                'nama' => $user->name,
                'id_kehadiran' => (string) $kehadiran->id,
                'server_time' => $currentTime->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function getDateTime()
    {
        $now = Carbon::now();
        
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        return response()->json([
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i:s'),
            'day' => $days[$now->format('l')]
        ]);
    }
}
