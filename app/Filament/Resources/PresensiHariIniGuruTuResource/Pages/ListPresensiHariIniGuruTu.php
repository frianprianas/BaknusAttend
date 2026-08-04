<?php

namespace App\Filament\Resources\PresensiHariIniGuruTuResource\Pages;

use App\Filament\Resources\PresensiHariIniGuruTuResource;
use App\Models\KehadiranGuruTu;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ListPresensiHariIniGuruTu extends ListRecords
{
    protected static string $resource = PresensiHariIniGuruTuResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return '👔 Presensi Guru & TU — Hari Ini';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $today = Carbon::today();
        $tanggal = $today->translatedFormat('l, j F Y');

        $totalHadir = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->where('keterangan', 'like', '%Masuk%')
            ->distinct('nipy')
            ->count('nipy');

        $totalGuru = User::whereIn('role', ['Guru', 'TU'])->count();
        $belumHadir = max(0, $totalGuru - $totalHadir);

        $hadirGuru = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->where('keterangan', 'like', '%Masuk%')
            ->whereIn('nipy', User::where('role', 'Guru')->pluck('nipy'))
            ->distinct('nipy')->count('nipy');

        $hadirTU = KehadiranGuruTu::whereDate('waktu_tap', $today)
            ->where('keterangan', 'like', '%Masuk%')
            ->whereIn('nipy', User::where('role', 'TU')->pluck('nipy'))
            ->distinct('nipy')->count('nipy');

        return new HtmlString("
            <div class='flex flex-wrap items-center gap-2 text-sm'>
                <span class='text-gray-500 dark:text-gray-400'>{$tanggal}</span>
                <span class='px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 rounded-full font-bold text-xs'>✅ Hadir: {$totalHadir}</span>
                <span class='px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-full text-xs'>Guru: {$hadirGuru}</span>
                <span class='px-2 py-0.5 bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 rounded-full text-xs'>TU: {$hadirTU}</span>
                <span class='px-2 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-full font-bold text-xs'>❌ Belum: {$belumHadir}</span>
                <span class='px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full text-xs'>Total: {$totalGuru} Pegawai</span>
            </div>
        ");
    }
}
