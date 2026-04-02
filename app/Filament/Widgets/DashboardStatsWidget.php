<?php

namespace App\Filament\Widgets;

use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $heading = null;
    protected int|string|array $columnSpan = 'full';

    public ?int $targetMonth = null;
    public ?int $targetYear = null;

    protected $listeners = [
        'kehadiran-updated' => '$refresh',
        'month-changed' => 'updateMonthTarget'
    ];

    public function updateMonthTarget($month, $year)
    {
        $this->targetMonth = $month;
        $this->targetYear = $year;
    }

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        $today = Carbon::today();
        
        $m = $this->targetMonth ?? Carbon::now()->month;
        $y = $this->targetYear ?? Carbon::now()->year;
        
        $startOfMonth = Carbon::create($y, $m, 1)->startOfMonth();
        $endOfMonth = Carbon::create($y, $m, 1)->endOfMonth();
        $monthLabel = $startOfMonth->translatedFormat('F Y');

        // ----------------------------------------------------
        // KONDISI 1: Statistik Login Guru / TU / Siswa (Pribadi)
        // ----------------------------------------------------
        if ($user->role !== 'Admin') {
            $totalHadirBulanIni = 0;
            $totalIzinSakitBulanIni = 0;
            
            if ($user->role === 'Siswa') {
                // Gunakan NIS dari nipy, jika nipy kosong gunakan email asisten (safety)
                $nis = !empty($user->nipy) ? $user->nipy : (!empty($user->email) ? $user->email : 'NONE');
                $student = Student::where('nis', $nis)->first();
                if ($student && !empty($student->nis)) {
                    $totalHadirBulanIni = KehadiranSiswa::where('nis', $student->nis)
                        ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
                        ->whereIn('status', ['Hadir', 'Terlambat'])
                        ->select(DB::raw('DATE(waktu_tap) as date'))
                        ->distinct()
                        ->get()
                        ->count();
                    $totalIzinSakitBulanIni = KehadiranSiswa::where('nis', $student->nis)
                        ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
                        ->whereIn('status', ['Izin', 'Sakit'])
                        ->select(DB::raw('DATE(waktu_tap) as date'))
                        ->distinct()
                        ->get()
                        ->count();
                }
            } else {
                // Guru / TU
                $nipy = $user->nipy ?? $user->email; // Gunakan NIPY jika ada, jika tidak gunakan Email
                if (!empty($nipy)) {
                    $totalHadirBulanIni = KehadiranGuruTu::where(function($q) use ($user, $nipy) {
                            $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                        })
                        ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
                        ->whereIn('status', ['Hadir', 'Terlambat'])
                        ->select(DB::raw('DATE(waktu_tap) as date'))
                        ->distinct()
                        ->get()
                        ->count();
                    $totalIzinSakitBulanIni = IzinGuruTu::where(function($q) use ($user, $nipy) {
                            $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                        })
                        ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                        ->whereIn('status', ['Diajukan', 'Disetujui'])
                        ->count();
                }
            }

            return [
                Stat::make('Total Kehadiran', $totalHadirBulanIni . ' Hari')
                    ->description('Bulan: ' . $monthLabel)
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('success')
                    ->icon('heroicon-o-calendar-days'),

                Stat::make('Total Izin / Sakit', $totalIzinSakitBulanIni . ' Hari')
                    ->description('Bulan: ' . $monthLabel)
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color($totalIzinSakitBulanIni > 0 ? 'warning' : 'gray')
                    ->icon('heroicon-o-document-text'),
            ];
        }

        // ----------------------------------------------------
        // KONDISI 2: Statistik Global ADMIN
        // ----------------------------------------------------
        $totalSiswa = Student::count();
        $totalGuru   = User::where('role', 'Guru')->count();
        $totalTU     = User::where('role', 'TU')->count();
        $totalGuruTU = $totalGuru + $totalTU;

        // Hitung Kehadiran HARI INI
        $hadirGuruTUHariIni = KehadiranGuruTu::whereDate('waktu_tap', $today)->where('status', 'Hadir')->count();
        $hadirSiswaHariIni  = KehadiranSiswa::whereDate('waktu_tap', $today)->where('status', 'Hadir')->count();
        $terlambatSiswa     = KehadiranSiswa::whereDate('waktu_tap', $today)->where('status', 'Terlambat')->count();

        $pctSiswa  = $totalSiswa  > 0 ? round(($hadirSiswaHariIni  / $totalSiswa)  * 100) : 0;
        $pctGuruTU = $totalGuruTU > 0 ? round(($hadirGuruTUHariIni / $totalGuruTU) * 100) : 0;

        $izinPending = IzinGuruTu::whereDate('tanggal', $today)->where('status', 'Diajukan')->count();

        return [
            Stat::make('Total Siswa', number_format($totalSiswa))
                ->description('Terdaftar di sistem')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Guru & TU', number_format($totalGuruTU))
                ->description("{$totalGuru} Guru · {$totalTU} TU"),

            Stat::make('Hadir Siswa – Hari Ini', $hadirSiswaHariIni . ' / ' . $totalSiswa)
                ->description("{$pctSiswa}% hadir · {$terlambatSiswa} terlambat")
                ->color($pctSiswa >= 80 ? 'success' : 'warning'),

            Stat::make('Hadir Guru & TU – Hari Ini', $hadirGuruTUHariIni . ' / ' . $totalGuruTU)
                ->description("{$pctGuruTU}% hadir hari ini")
                ->color($pctGuruTU >= 80 ? 'success' : 'warning'),

            Stat::make('Izin / Sakit Pending', $izinPending)
                ->description($izinPending > 0 ? 'Menunggu persetujuan Admin' : 'Tidak ada pengajuan baru')
                ->color($izinPending > 0 ? 'warning' : 'gray')
                ->url('/admin/izin-guru-tus'),
        ];
    }
}
