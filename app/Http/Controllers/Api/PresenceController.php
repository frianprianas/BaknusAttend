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
        
        // Cek apakah mode ini (Masuk/Pulang) sudah dilakukan hari ini
        // Kita cek di kolom 'keterangan' atau 'status' yang ada kata kuncinya
        $alreadyAbsen = KehadiranSiswa::where('nis', $student->nis)
            ->whereDate('waktu_tap', $currentTime)
            ->where('keterangan', 'LIKE', $mode . '%')
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

        // Cek apakah mode ini sudah dilakukan
        $alreadyAbsen = KehadiranGuruTu::where('nipy', $nipy)
            ->whereDate('waktu_tap', $currentTime)
            ->where('status', 'LIKE', $mode . '%') // Guru biasanya simpan di status
            ->exists();

        if ($alreadyAbsen) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => "Sudah Absen $mode!",
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
}
