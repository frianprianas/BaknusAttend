<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\Kehadiran;
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
        // Contoh: masuk sebelum jam 07:15 dianggap Hadir, lewat itu Terlambat.
        // Format waktu dalam string H:i
        $jamTap = $now->format('H:i');
        $batasTerlambat = '07:15';
        $statusHadir = ($jamTap <= $batasTerlambat) ? 'Hadir' : 'Terlambat';

        // 1. Cek apakan Siswa
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
        $tapsHariIni = Kehadiran::where('nis', $student->nis)
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

        // Simpan kehadiran
        Kehadiran::create([
            'nis' => $student->nis,
            'rfid' => $student->rfid,
            'waktu_tap' => $now,
            'status' => $statusAbsen,
            'keterangan' => $keteranganAbsen . ' (' . $machineId . ')',
        ]);

        // Kirim ke API BaknusDrive
        try {
            $driveUrl = env('BAKNUSDRIVE_URL') . '/api/attend/upload';
            $apiKey = env('BAKNUS_ATTEND_API_KEY');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Attend-API-Key' => $apiKey
            ])->asForm()->post($driveUrl, [
                        'NIS' => $student->nis,
                        'Nama' => $student->name,
                        'kelas' => $student->classRoom ? $student->classRoom->name : '-',
                        'role' => 'siswa',
                        'waktu_tap' => $now->format('H:i:s'),
                        'status' => $statusAbsen,
                        'keterangan' => $keteranganAbsen,
                    ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('API BaknusDrive error (Siswa): ' . $response->body());
            } else {
                \Illuminate\Support\Facades\Log::info('API BaknusDrive sukses (Siswa): ' . $response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal kirim ke BaknusDrive (Siswa): ' . $e->getMessage());
        }

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
        $tapsHariIni = KehadiranGuru::where('nipy', $user->nipy)
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

        // Simpan kehadiran
        KehadiranGuru::create([
            'nipy' => $user->nipy,
            'rfid' => $user->rfid,
            'waktu_tap' => $now,
            'status' => $statusAbsen,
            'keterangan' => $keteranganAbsen . ' (' . $machineId . ')',
        ]);

        // Kirim ke API BaknusDrive
        try {
            // Role di BaknusDrive API harus 'guru', 'TU', atau 'siswa'
            $roleInApi = 'guru'; // default
            if (strtolower($user->role) === 'tu') {
                $roleInApi = 'TU';
            } elseif (strtolower($user->role) === 'guru') {
                $roleInApi = 'guru';
            }

            $driveUrl = env('BAKNUSDRIVE_URL') . '/api/attend/upload';
            $apiKey = env('BAKNUS_ATTEND_API_KEY');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Attend-API-Key' => $apiKey
            ])->asForm()->post($driveUrl, [
                        'NIS' => $user->nipy,
                        'Nama' => $user->name,
                        'kelas' => '-',
                        'role' => $roleInApi,
                        'waktu_tap' => $now->format('H:i:s'),
                        'status' => $statusAbsen,
                        'keterangan' => $keteranganAbsen,
                    ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('API BaknusDrive error (Guru/TU): ' . $response->body());
            } else {
                \Illuminate\Support\Facades\Log::info('API BaknusDrive sukses (Guru/TU): ' . $response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal kirim ke BaknusDrive (Guru/TU): ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil: {$user->name}",
            'name' => $user->name,
            'role' => $user->role,
            'status_kehadiran' => $statusHadir
        ]);
    }
}
