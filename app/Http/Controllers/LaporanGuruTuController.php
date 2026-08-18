<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KehadiranGuruTu;
use App\Models\IzinGuruTu;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanGuruTuController extends Controller
{
    /**
     * Tampilkan halaman cetak laporan rekap bulanan Guru/TU
     */
    public function print(Request $request)
    {
        // Khusus Admin atau Kepsek
        if (!auth()->check() || (!in_array(auth()->user()->role, ['Admin']) && !auth()->user()->is_kepsek)) {
            abort(403, 'Akses ditolak. Menu ini hanya untuk Admin atau Kepala Sekolah.');
        }

        $month = (int) ($request->query('bulan') ?? now()->format('m'));
        $year = (int) ($request->query('tahun') ?? now()->format('Y'));
        $roleFilter = $request->query('role') ?? 'all';

        $attendanceService = new AttendanceService();
        $effectiveDays = $attendanceService->getEffectiveWorkingDays($month, $year);

        $query = User::whereIn('role', ['Guru', 'TU']);
        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }
        $users = $query->orderBy('name', 'asc')->get();

        $rekapData = [];
        foreach ($users as $user) {
            $identifier = !empty($user->nipy) ? $user->nipy : $user->email;

            // Ambil seluruh record presensi bulan tersebut
            $allMonthRecords = KehadiranGuruTu::where(function ($q) use ($user) {
                    $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
                })
                ->whereMonth('waktu_tap', $month)
                ->whereYear('waktu_tap', $year)
                ->whereIn('status', ['Hadir', 'Terlambat', 'Dinas Luar'])
                ->get()
                ->groupBy(fn($item) => Carbon::parse($item->waktu_tap)->format('Y-m-d'));

            $hadirSekolah = 0;
            $hadirDL = 0;
            $terlambatCount = 0;

            foreach ($allMonthRecords as $date => $dayRecords) {
                if ($dayRecords->contains('is_dinas_luar', true) || $dayRecords->contains('status', 'Dinas Luar')) {
                    $hadirDL++;
                } else {
                    $hadirSekolah++;
                }

                if ($dayRecords->contains('status', 'Terlambat')) {
                    $terlambatCount++;
                }
            }

            $totalHadir = $hadirSekolah + $hadirDL;

            // Data Izin / Sakit
            $izins = IzinGuruTu::where(function ($q) use ($user) {
                    $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
                })
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->whereIn('status', ['Diajukan', 'Disetujui'])
                ->get();

            $sakitCount = $izins->where('tipe', 'Sakit')->count();
            $izinCount = $izins->whereNotIn('tipe', ['Sakit'])->count();

            // Estimasi Tanpa Keterangan (Alpa)
            $alpaCount = max(0, $effectiveDays - $totalHadir - $sakitCount - $izinCount);

            $persentase = $effectiveDays > 0 ? round(($totalHadir / $effectiveDays) * 100, 1) : 0;

            $rekapData[] = [
                'user' => $user,
                'nipy' => $identifier,
                'name' => $user->name,
                'role' => $user->role,
                'hadir_sekolah' => $hadirSekolah,
                'hadir_dl' => $hadirDL,
                'terlambat' => $terlambatCount,
                'total_hadir' => $totalHadir,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
                'persentase' => $persentase,
            ];
        }

        $namaBulan = Carbon::create($year, $month, 1)->translatedFormat('F');

        return view('laporan.rekap-guru-tu-print', [
            'rekapData' => $rekapData,
            'month' => $month,
            'year' => $year,
            'namaBulan' => $namaBulan,
            'effectiveDays' => $effectiveDays,
            'roleFilter' => $roleFilter,
        ]);
    }

    /**
     * Download CSV Rekap Bulanan Guru/TU
     */
    public function exportCsv(Request $request)
    {
        if (!auth()->check() || (!in_array(auth()->user()->role, ['Admin']) && !auth()->user()->is_kepsek)) {
            abort(403);
        }

        $month = (int) ($request->query('bulan') ?? now()->format('m'));
        $year = (int) ($request->query('tahun') ?? now()->format('Y'));
        $roleFilter = $request->query('role') ?? 'all';

        $attendanceService = new AttendanceService();
        $effectiveDays = $attendanceService->getEffectiveWorkingDays($month, $year);

        $query = User::whereIn('role', ['Guru', 'TU']);
        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }
        $users = $query->orderBy('name', 'asc')->get();

        $namaBulan = Carbon::create($year, $month, 1)->translatedFormat('F');
        $fileName = "Rekap_Kehadiran_GuruTU_{$namaBulan}_{$year}.csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($users, $month, $year, $effectiveDays) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            // Header Kolom
            fputcsv($file, [
                'No',
                'NIPY / ID',
                'Nama Pegawai',
                'Jabatan',
                'Hari Kerja Efektif',
                'Hadir Sekolah',
                'Dinas Luar',
                'Terlambat',
                'Total Hadir',
                'Sakit',
                'Izin',
                'Tanpa Keterangan (Alpa)',
                'Persentase Kehadiran (%)'
            ]);

            $no = 1;
            foreach ($users as $user) {
                $identifier = !empty($user->nipy) ? $user->nipy : $user->email;

                $allMonthRecords = KehadiranGuruTu::where(function ($q) use ($user) {
                        $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
                    })
                    ->whereMonth('waktu_tap', $month)
                    ->whereYear('waktu_tap', $year)
                    ->whereIn('status', ['Hadir', 'Terlambat', 'Dinas Luar'])
                    ->get()
                    ->groupBy(fn($item) => Carbon::parse($item->waktu_tap)->format('Y-m-d'));

                $hadirSekolah = 0;
                $hadirDL = 0;
                $terlambatCount = 0;

                foreach ($allMonthRecords as $date => $dayRecords) {
                    if ($dayRecords->contains('is_dinas_luar', true) || $dayRecords->contains('status', 'Dinas Luar')) {
                        $hadirDL++;
                    } else {
                        $hadirSekolah++;
                    }

                    if ($dayRecords->contains('status', 'Terlambat')) {
                        $terlambatCount++;
                    }
                }

                $totalHadir = $hadirSekolah + $hadirDL;

                $izins = IzinGuruTu::where(function ($q) use ($user) {
                        $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
                    })
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->whereIn('status', ['Diajukan', 'Disetujui'])
                    ->get();

                $sakitCount = $izins->where('tipe', 'Sakit')->count();
                $izinCount = $izins->whereNotIn('tipe', ['Sakit'])->count();

                $alpaCount = max(0, $effectiveDays - $totalHadir - $sakitCount - $izinCount);
                $persentase = $effectiveDays > 0 ? round(($totalHadir / $effectiveDays) * 100, 1) : 0;

                fputcsv($file, [
                    $no++,
                    $identifier,
                    $user->name,
                    $user->role,
                    $effectiveDays,
                    $hadirSekolah,
                    $hadirDL,
                    $terlambatCount,
                    $totalHadir,
                    $sakitCount,
                    $izinCount,
                    $alpaCount,
                    $persentase . '%'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
