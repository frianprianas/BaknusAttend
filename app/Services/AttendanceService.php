<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Carbon;

class AttendanceService
{
    /**
     * Menghitung estimasi hari aktif (Senin-Jumat) dikurangi libur manual.
     */
    public function getEffectiveWorkingDays($month = null, $year = null): int
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $weekdaysCount = 0;
        $tempDate = $startDate->copy();

        // 1. Hitung seluruh Senin-Jumat di bulan tersebut
        while ($tempDate <= $endDate) {
            if ($tempDate->isWeekday()) {
                $weekdaysCount++;
            }
            $tempDate->addDay();
        }

        // 2. Cari libur manual di tabel holidays yang jatuh pada Senin-Jumat di bulan tersebut
        $holidaysCount = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->get()
            ->filter(function ($holiday) {
                // Hanya hitung libur yang jatuh di Senin-Jumat (karena Sabtu-Minggu memang bkn hari kerja)
                return $holiday->holiday_date->isWeekday();
            })
            ->count();

        $effectiveDays = $weekdaysCount - $holidaysCount;

        return $effectiveDays > 0 ? $effectiveDays : 0;
    }
}
