<?php

namespace App\Filament\Resources\PresensiHariIniSiswaResource\Pages;

use App\Filament\Resources\PresensiHariIniSiswaResource;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ListPresensiHariIniSiswa extends ListRecords
{
    protected static string $resource = PresensiHariIniSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return '🎓 Presensi Siswa — Hari Ini';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $today = Carbon::today();
        $tanggal = $today->translatedFormat('l, j F Y');

        $totalHadir = KehadiranSiswa::whereDate('waktu_tap', $today)
            ->where('keterangan', 'like', '%Masuk%')
            ->distinct('nis')
            ->count('nis');

        $totalSiswa = Student::count();

        $belumHadir = max(0, $totalSiswa - $totalHadir);

        return new HtmlString("
            <div class='flex flex-wrap items-center gap-2 text-sm'>
                <span class='text-gray-500 dark:text-gray-400'>{$tanggal}</span>
                <span class='px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 rounded-full font-bold text-xs'>✅ Hadir: {$totalHadir}</span>
                <span class='px-2 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-full font-bold text-xs'>❌ Belum: {$belumHadir}</span>
                <span class='px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full text-xs'>Total: {$totalSiswa} Siswa</span>
            </div>
        ");
    }
}
