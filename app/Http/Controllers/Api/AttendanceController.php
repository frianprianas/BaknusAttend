<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Endpoint untuk memproses tap RFID dari mesin (misal MPS1).
     * Mendukung GET dan POST.
     */
    public function tap(Request $request)
    {
        // Mendukung request POST JSON, form-data, atau GET query parameter
        $rfid = trim($request->input('rfid') ?? $request->query('rfid'));
        $machineId = trim($request->input('machine_id') ?? $request->query('machine_id') ?? 'Mesin RFID');

        if (empty($rfid)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode RFID kosong (Parameter "rfid" diperlukan).'
            ], 400);
        }

        $now = Carbon::now();
        $today = Carbon::today();

        // Logika untuk menentukan status Hadir / Terlambat
        $jamTap = $now->format('H:i');
        $batasTerlambat = '07:15';
        $statusHadir = ($jamTap <= $batasTerlambat) ? 'Hadir' : 'Terlambat';

        // 1. Cek apakah Siswa
        $student = Student::where('rfid', $rfid)->first();
        if ($student) {
            return $this->processStudentAttendance($student, $today, $now, $statusHadir, $machineId);
        }

        // 2. Cek apakah Guru / TU
        $user = User::where('rfid', $rfid)->first();
        if ($user) {
            return $this->processGuruTuAttendance($user, $today, $now, $statusHadir, $machineId);
        }

        // 3. Tidak ditemukan sama sekali
        Log::warning("Tap RFID Tidak Dikenal: {$rfid} di {$machineId}");
        return response()->json([
            'status' => 'error',
            'message' => 'Kartu tidak terdaftar'
        ], 404);
    }

    private function processStudentAttendance($student, $today, $now, $statusHadir, $machineId)
    {
        // Cek jumlah tap hari ini (untuk menentukan Masuk/Pulang)
        $tapsHariIni = KehadiranSiswa::where('nis', $student->nis)
            ->whereDate('waktu_tap', $today)
            ->get();

        $statusAbsen = 'Masuk';
        $keteranganAbsen = $statusHadir;

        if ($tapsHariIni->count() === 1) {
            $statusAbsen = 'Pulang';
            $keteranganAbsen = 'Pulang';
        } elseif ($tapsHariIni->count() >= 2) {
            return response()->json([
                'status' => 'info',
                'message' => 'Sudah Tap Pulang Hari Ini',
                'name' => $student->name,
                'role' => 'Siswa'
            ]);
        }

        // Simpan kehadiran di tabel baru KehadiranSiswa
        KehadiranSiswa::create([
            'nis' => $student->nis,
            'rfid_uid' => $student->rfid,
            'waktu_tap' => $now,
            'status' => $statusAbsen === 'Masuk' ? $keteranganAbsen : 'Hadir', 
            'keterangan' => "RFID Tap ({$statusAbsen}) via {$machineId}",
        ]);

        // Kirim ke API BaknusDrive
        $this->syncToBaknusDrive($student->nis, $student->name, ($student->classRoom ? $student->classRoom->kelas : '-'), 'siswa', $now, $statusAbsen, $keteranganAbsen);

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil: {$student->name}",
            'name' => $student->name,
            'role' => 'Siswa',
            'status_kehadiran' => $statusHadir
        ]);
    }

    private function processGuruTuAttendance($user, $today, $now, $statusHadir, $machineId)
    {
        // Cek jumlah tap hari ini
        $tapsHariIni = KehadiranGuruTu::where('nipy', $user->nipy)
            ->whereDate('waktu_tap', $today)
            ->get();

        $statusAbsen = 'Masuk';
        $keteranganAbsen = $statusHadir;

        if ($tapsHariIni->count() === 1) {
            $statusAbsen = 'Pulang';
            $keteranganAbsen = 'Pulang';
        } elseif ($tapsHariIni->count() >= 2) {
            return response()->json([
                'status' => 'info',
                'message' => 'Sudah Tap Pulang Hari Ini',
                'name' => $user->name,
                'role' => $user->role
            ]);
        }

        // Simpan kehadiran di tabel baru KehadiranGuruTu
        KehadiranGuruTu::create([
            'nipy' => $user->nipy,
            'rfid_uid' => $user->rfid,
            'waktu_tap' => $now,
            'status' => $statusAbsen === 'Masuk' ? $keteranganAbsen : 'Hadir',
            'keterangan' => "RFID Tap ({$statusAbsen}) via {$machineId}",
        ]);

        // Kirim ke API BaknusDrive
        $roleInApi = strtolower($user->role) === 'tu' ? 'TU' : 'guru';
        $this->syncToBaknusDrive($user->nipy, $user->name, '-', $roleInApi, $now, $statusAbsen, $keteranganAbsen);

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil: {$user->name}",
            'name' => $user->name,
            'role' => $user->role,
            'status_kehadiran' => $statusHadir
        ]);
    }

    private function syncToBaknusDrive($id, $name, $kelas, $role, $now, $type, $desc)
    {
        try {
            $driveUrl = env('BAKNUSDRIVE_URL') . '/api/attend/upload';
            $apiKey = env('BAKNUS_ATTEND_API_KEY');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Attend-API-Key' => $apiKey
            ])->asForm()->post($driveUrl, [
                'NIS' => $id,
                'Nama' => $name,
                'kelas' => $kelas,
                'role' => $role,
                'waktu_tap' => $now->format('H:i:s'),
                'status' => $type,
                'keterangan' => $desc,
            ]);

            if ($response->failed()) {
                Log::error("API BaknusDrive error ({$role}): " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Gagal kirim ke BaknusDrive ({$role}): " . $e->getMessage());
        }
    }
}
