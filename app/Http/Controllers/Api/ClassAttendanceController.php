<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassAttendanceController extends Controller
{
    /**
     * Role validator guard: memastikan hanya Guru, TU, dan Admin yang diizinkan.
     * Jika pemanggil adalah siswa atau peran lain, kembalikan HTTP 403 Forbidden.
     */
    protected function validateStaffAccess(?object $user): ?JsonResponse
    {
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token otentikasi tidak ditemukan atau sesi telah berakhir.',
            ], 401);
        }

        $role = strtolower(trim($user->role ?? ''));
        $allowedRoles = ['guru', 'tu', 'admin', 'administrator', 'kepsek', 'kepala sekolah'];

        if (!in_array($role, $allowedRoles, true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Data kehadiran kelas hanya dapat diakses oleh Guru dan Staf TU.',
            ], 403);
        }

        return null;
    }

    /**
     * 1. Endpoint Master Daftar Kelas
     * GET /api/presence/classes
     */
    public function getClasses(Request $request): JsonResponse
    {
        if ($deny = $this->validateStaffAccess($request->user())) {
            return $deny;
        }

        // Ambil seluruh data ruang kelas beserta relasi program studinya
        $classRooms = ClassRoom::with('programStudi')->orderBy('kelas', 'asc')->get();

        $data = $classRooms->map(function ($c) {
            $rawName = trim($c->kelas ?? '');

            // Format code: "X PPLG 1" -> "X-PPLG-1"
            $code = strtoupper(str_replace([' ', '_'], '-', $rawName));

            // Ekstraksi tingkat (10, 11, 12)
            $tingkat = 10;
            if (preg_match('/^(XII|12)\b/i', $rawName)) {
                $tingkat = 12;
            } elseif (preg_match('/^(XI|11)\b/i', $rawName)) {
                $tingkat = 11;
            } elseif (preg_match('/^(X|10)\b/i', $rawName)) {
                $tingkat = 10;
            }

            // Ekstraksi jurusan
            $jurusan = null;
            if ($c->programStudi && !empty($c->programStudi->program_studi)) {
                $jurusan = $c->programStudi->program_studi;
            } else {
                $cleaned = preg_replace('/^(X|XI|XII|10|11|12)[\s\-_]+/i', '', $rawName);
                $cleaned = preg_replace('/[\s\-_]+\d+$/', '', $cleaned);
                $jurusan = trim($cleaned) ?: '-';
            }

            // Jika ada singkatan dalam kurung, ambil singkatannya (e.g. "Animasi (ANIM)" -> "ANIM")
            if (preg_match('/\(([^)]+)\)/', $jurusan, $matches)) {
                $jurusan = trim($matches[1]);
            }

            return [
                'id'      => (int)$c->id,
                'code'    => $code,
                'name'    => $rawName,
                'tingkat' => $tingkat,
                'jurusan' => $jurusan,
            ];
        })->values();

        // Urutkan berdasarkan tingkat lalu nama kelas
        $sortedData = $data->sortBy([
            ['tingkat', 'asc'],
            ['name', 'asc'],
        ])->values()->all();

        return response()->json([
            'status' => 'success',
            'data'   => $sortedData,
        ], 200);
    }

    /**
     * 2. Endpoint Rekap Kehadiran Kelas Hari Ini
     * GET /api/presence/today-by-class
     * Query Parameters:
     * - class_id (string/integer, wajib): ID atau Kode/Nama Kelas
     * - date (string YYYY-MM-DD, opsional, default: hari ini)
     */
    public function getTodayByClass(Request $request): JsonResponse
    {
        if ($deny = $this->validateStaffAccess($request->user())) {
            return $deny;
        }

        // 1. Validasi class_id wajib
        $classIdInput = trim((string)($request->query('class_id') ?? $request->input('class_id') ?? ''));
        if (empty($classIdInput)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter class_id wajib disertakan (contoh: class_id=1 atau class_id=X-PPLG-1).',
            ], 422);
        }

        // 2. Tentukan tanggal (default: hari ini)
        $dateInput = trim((string)($request->query('date') ?? $request->input('date') ?? ''));
        if (!empty($dateInput)) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $dateInput)->startOfDay();
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format parameter date tidak valid. Gunakan format YYYY-MM-DD (contoh: ' . date('Y-m-d') . ').',
                ], 422);
            }
        } else {
            $targetDate = Carbon::today();
        }

        // 3. Resolusi Data Kelas (Bisa via ID integer atau Kode / Nama kelas)
        $classRoom = null;
        if (is_numeric($classIdInput)) {
            $classRoom = ClassRoom::find($classIdInput);
        }

        if (!$classRoom) {
            $slugCode = strtoupper(str_replace([' ', '_'], '-', $classIdInput));
            $spaceName = str_replace('-', ' ', $classIdInput);

            $classRoom = ClassRoom::where('kelas', $classIdInput)
                ->orWhere('kelas', $spaceName)
                ->orWhereRaw("UPPER(REPLACE(REPLACE(kelas, ' ', '-'), '_', '-')) = ?", [$slugCode])
                ->first();
        }

        if (!$classRoom) {
            return response()->json([
                'status'  => 'error',
                'message' => "Kelas dengan identifikasi '{$classIdInput}' tidak ditemukan dalam sistem.",
            ], 404);
        }

        // 4. Ambil semua siswa aktif di kelas tersebut (diurutkan berdasarkan nama)
        $students = Student::where('class_room_id', $classRoom->id)
            ->orderBy('name', 'asc')
            ->get();

        $totalSiswa = $students->count();

        // 5. Query log presensi pada tanggal target untuk seluruh siswa di kelas ini (Indexed: nis, waktu_tap)
        $nises = $students->pluck('nis')->filter()->values()->toArray();

        $attendanceLogs = collect();
        if (!empty($nises)) {
            $attendanceLogs = KehadiranSiswa::whereIn('nis', $nises)
                ->whereDate('waktu_tap', $targetDate)
                ->orderBy('waktu_tap', 'asc')
                ->get()
                ->groupBy('nis');
        }

        $hadirList = [];
        $belumHadirList = [];

        foreach ($students as $student) {
            $studentNis = (string)($student->nis ?? '');
            $hasAttendance = !empty($studentNis) && isset($attendanceLogs[$studentNis]) && $attendanceLogs[$studentNis]->isNotEmpty();

            if ($hasAttendance) {
                // Ambil tap pertama pada hari tersebut sebagai waktu masuk
                $firstTap = $attendanceLogs[$studentNis]->first();
                $waktuMasuk = Carbon::parse($firstTap->waktu_tap)->format('H:i:s');

                // Deteksi metode presensi: Selfie / Bluetooth / NFC Tap / RFID
                $keteranganLower = strtolower($firstTap->keterangan ?? '');
                $photo = $firstTap->photo;
                $hasPhoto = !empty($photo) && $photo !== 'rfid_placeholder';

                if ($hasPhoto || str_contains($keteranganLower, 'selfie') || str_contains($keteranganLower, 'mandiri')) {
                    $metode = 'Selfie';
                } elseif (str_contains($keteranganLower, 'bluetooth') || str_contains($keteranganLower, 'ble')) {
                    $metode = 'Bluetooth';
                } elseif (str_contains($keteranganLower, 'nfc')) {
                    $metode = 'NFC Tap';
                } elseif (str_contains($keteranganLower, 'rfid')) {
                    $metode = 'NFC Tap';
                } else {
                    $metode = $hasPhoto ? 'Selfie' : 'NFC Tap';
                }

                // Normalisasi status kehadiran: Tepat Waktu / Terlambat / Dinas Luar
                $statusRaw = trim($firstTap->status ?? 'Hadir');
                if (strcasecmp($statusRaw, 'Hadir') === 0 || str_contains(strtolower($statusRaw), 'tepat')) {
                    $statusDisplay = 'Tepat Waktu';
                } elseif (strcasecmp($statusRaw, 'Terlambat') === 0) {
                    $statusDisplay = 'Terlambat';
                } elseif (strcasecmp($statusRaw, 'Dinas Luar') === 0) {
                    $statusDisplay = 'Dinas Luar';
                } else {
                    $statusDisplay = $statusRaw ?: 'Tepat Waktu';
                }

                // URL foto selfie jika tersedia
                $fotoUrl = null;
                if ($hasPhoto) {
                    $cleanPath = ltrim(str_replace('public/', '', $photo), '/');
                    $fotoUrl = url('storage/' . $cleanPath);
                }

                $hadirList[] = [
                    'nis'         => $studentNis,
                    'nama'        => $student->name,
                    'waktu_masuk' => $waktuMasuk,
                    'metode'      => $metode,
                    'status'      => $statusDisplay,
                    'foto_url'    => $fotoUrl,
                ];
            } else {
                $belumHadirList[] = [
                    'nis'        => $studentNis,
                    'nama'       => $student->name,
                    'keterangan' => 'Belum Ada Keterangan',
                ];
            }
        }

        $hadirCount = count($hadirList);
        $belumHadirCount = count($belumHadirList);
        $persentase = $totalSiswa > 0 ? round(($hadirCount / $totalSiswa) * 100, 2) : 0;

        return response()->json([
            'status'       => 'success',
            'date'         => $targetDate->format('Y-m-d'),
            'generated_at' => Carbon::now()->format('H:i:s'),
            'kelas'        => [
                'id'   => (int)$classRoom->id,
                'name' => $classRoom->kelas,
            ],
            'summary'      => [
                'total_siswa'          => $totalSiswa,
                'hadir'                => $hadirCount,
                'belum_hadir'          => $belumHadirCount,
                'persentase_kehadiran' => number_format($persentase, 2, '.', '') . '%',
            ],
            'hadir_list'       => $hadirList,
            'belum_hadir_list' => $belumHadirList,
        ], 200);
    }

    /**
     * 3. Endpoint Rekap Guru & Staf TU yang Sudah Hadir Hari Ini
     * GET /api/presence/teachers-today
     */
    public function getTeachersToday(Request $request): JsonResponse
    {
        return app(\App\Http\Controllers\Api\PresenceController::class)->getTeachersToday($request);
    }
}