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

        // Cek batas 2 tap per hari
        $countToday = KehadiranSiswa::where('nis', $student->nis)
            ->whereDate('waktu_tap', $currentTime)
            ->count();

        if ($countToday >= 2) {
            return response()->json([
                'status'  => 'WARNING',
                'message' => 'Absensi sudah lengkap (Masuk & Pulang) hari ini!',
                'data'    => ['nama' => $student->name],
            ]);
        }

        // Auto-detect Masuk atau Pulang
        $tipeTap = $countToday === 0 ? 'Masuk' : 'Pulang';
        $status  = 'Hadir';

        if ($tipeTap === 'Masuk' && $currentTime->format('H:i') > '07:05') {
            $status = 'Terlambat';
        }

        $kehadiran = KehadiranSiswa::create([
            'nis'        => $student->nis,
            'rfid_uid'   => $rfid,
            'waktu_tap'  => $currentTime,
            'status'     => $status,
            'keterangan' => $tipeTap . ' - Tap RFID',
        ]);

        return response()->json([
            'status'  => 'SUCCESS',
            'message' => 'Absensi ' . $tipeTap . ' Berhasil!',
            'data'    => [
                'nipy'         => $student->nis,
                'nama'         => $student->name,
                'tipe'         => $tipeTap,
                'id_kehadiran' => (string) $kehadiran->id,
                'server_time'  => $currentTime->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    private function handleUserPresence($user, $rfid, $statusTap)
    {
        $currentTime = Carbon::now();
        $nipy = $user->nipy ?? $user->email;

        // Cek batas 2 tap per hari
        $countToday = KehadiranGuruTu::where(function ($q) use ($nipy, $user) {
                $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
            })
            ->whereDate('waktu_tap', $currentTime)
            ->count();

        if ($countToday >= 2) {
            return response()->json([
                'status'  => 'WARNING',
                'message' => 'Absensi sudah lengkap (Masuk & Pulang) hari ini!',
                'data'    => ['nama' => $user->name],
            ]);
        }

        // Auto-detect Masuk atau Pulang
        $tipeTap = $countToday === 0 ? 'Masuk' : 'Pulang';

        $kehadiran = KehadiranGuruTu::create([
            'nipy'       => $nipy,
            'rfid_uid'   => $rfid,
            'waktu_tap'  => $currentTime,
            'status'     => 'Hadir',
            'keterangan' => $tipeTap . ' - Tap RFID',
        ]);

        return response()->json([
            'status'  => 'SUCCESS',
            'message' => 'Absensi ' . $tipeTap . ' Berhasil!',
            'data'    => [
                'nipy'         => $nipy,
                'nama'         => $user->name,
                'tipe'         => $tipeTap,
                'id_kehadiran' => (string) $kehadiran->id,
                'server_time'  => $currentTime->format('Y-m-d H:i:s'),
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

    /**
     * Mengambil foto wajah (face_reference) dari database untuk disinkronkan ke aplikasi eksternal
     */
    public function getUserImage(Request $request)
    {
        $search = $request->query('search'); // Bisa email atau rfid
        
        if (!$search) {
            return response()->json(['status' => 'ERROR', 'message' => 'Parameter search (email/rfid) diperlukan'], 400);
        }

        $user = User::where('email', $search)
            ->orWhere('rfid', $search)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'ERROR', 'message' => 'User tidak ditemukan'], 404);
        }

        if (!$user->face_reference) {
            return response()->json(['status' => 'ERROR', 'message' => 'User belum mendaftarkan foto wajah'], 404);
        }

        $path = storage_path('app/public/' . $user->face_reference);

        if (!file_exists($path)) {
            return response()->json(['status' => 'ERROR', 'message' => 'File foto tidak ditemukan di server'], 404);
        }

        $imageData = base64_encode(file_get_contents($path));
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return response()->json([
            'status' => 'SUCCESS',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'filename' => basename($user->face_reference),
                'byte_size' => filesize($path),
                'base64' => 'data:image/' . $extension . ';base64,' . $imageData
            ]
        ]);
    }
}
