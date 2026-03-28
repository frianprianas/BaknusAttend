<?php

namespace App\Filament\Widgets;

use App\Models\IzinGuruTu;
use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $heading = null;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalSiswa  = Student::count();
        $totalGuru   = User::where('role', 'Guru')->count();
        $totalTU     = User::where('role', 'TU')->count();
        $totalGuruTU = $totalGuru + $totalTU;

        $hadirSiswaHariIni  = KehadiranSiswa::whereDate('waktu_tap', $today)->where('status', 'Hadir')->count();
        $terlambatSiswa     = KehadiranSiswa::whereDate('waktu_tap', $today)->where('status', 'Terlambat')->count();
        $hadirGuruTUHariIni = KehadiranGuruTu::whereDate('waktu_tap', $today)->where('status', 'Hadir')->count();

        $pctSiswa  = $totalSiswa  > 0 ? round(($hadirSiswaHariIni  / $totalSiswa)  * 100) : 0;
        $pctGuruTU = $totalGuruTU > 0 ? round(($hadirGuruTUHariIni / $totalGuruTU) * 100) : 0;

        // Pengajuan Izin/Sakit pending hari ini
        $izinPending = IzinGuruTu::whereDate('tanggal', $today)->where('status', 'Diajukan')->count();

        return [
            Stat::make('Total Siswa', number_format($totalSiswa))
                ->description('Terdaftar di sistem')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->icon('heroicon-o-academic-cap'),

            Stat::make('Guru & TU', number_format($totalGuruTU))
                ->description("{$totalGuru} Guru · {$totalTU} TU")
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->icon('heroicon-o-users'),

            Stat::make('Hadir Siswa – Hari Ini', $hadirSiswaHariIni . ' / ' . $totalSiswa)
                ->description("{$pctSiswa}% hadir · {$terlambatSiswa} terlambat")
                ->descriptionIcon('heroicon-m-clock')
                ->color($pctSiswa >= 80 ? 'success' : ($pctSiswa >= 60 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-circle'),

            Stat::make('Hadir Guru & TU – Hari Ini', $hadirGuruTUHariIni . ' / ' . $totalGuruTU)
                ->description("{$pctGuruTU}% hadir hari ini")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color($pctGuruTU >= 80 ? 'success' : 'warning')
                ->icon('heroicon-o-building-office'),

            Stat::make('Izin / Sakit Pending', $izinPending)
                ->description($izinPending > 0 ? 'Menunggu persetujuan Admin' : 'Tidak ada pengajuan baru')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($izinPending > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-document-text')
                ->url('/admin/izin-guru-tus'),
        ];
    }
}
