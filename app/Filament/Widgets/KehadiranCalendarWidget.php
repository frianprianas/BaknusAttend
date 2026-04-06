<?php

namespace App\Filament\Widgets;

use App\Models\KehadiranGuruTu;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class KehadiranCalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.kehadiran-calendar-widget';

    protected int | string | array $columnSpan = 'full';

    public $currentMonth;
    public $currentYear;
    public $daysInMonth;
    public $firstDayOfMonth;
    public $presenceData = [];

    protected $listeners = ['kehadiran-updated' => 'refreshCalendarData'];

    public function refreshCalendarData()
    {
        $this->fetchPresenceData();
    }

    public static function canView(): bool
    {
        // Hanya tampilkan untuk Guru / TU (bukan Admin / Siswa di resource ini)
        $user = auth()->user();
        return $user && in_array($user->role, ['Guru', 'TU']);
    }

    public function mount()
    {
        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
        $this->fetchPresenceData();
    }

    public function fetchPresenceData()
    {
        $user = auth()->user();
        if (!$user) return;

        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $this->daysInMonth = $startOfMonth->daysInMonth;
        $this->firstDayOfMonth = $startOfMonth->dayOfWeek; // 0 (Sun) - 6 (Sat)

        // 1. Ambil data presensi (Masuk/Pulang/Dinas Luar)
        $presences = KehadiranGuruTu::where(function($q) use ($user) {
                if ($user->nipy) $q->orWhere('nipy', $user->nipy);
                if ($user->email) $q->orWhere('nipy', $user->email);
            })
            ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
            ->orderBy('waktu_tap', 'asc')
            ->get();

        // 2. Ambil data Izin / Sakit
        $izins = \App\Models\IzinGuruTu::where(function($q) use ($user) {
                if ($user->nipy) $q->orWhere('nipy', $user->nipy);
                if ($user->email) $q->orWhere('nipy', $user->email);
            })
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['Diajukan', 'Disetujui'])
            ->get();

        $this->presenceData = [];

        // Masukkan data Izin dulu (Bisa ditimpa oleh presensi jika nekat absen saat izin)
        foreach ($izins as $i) {
            $day = (int) Carbon::parse($i->tanggal)->format('j');
            $this->presenceData[$day] = [
                'status' => 'red',
                'jam_masuk' => $i->tipe,
                'jam_pulang' => $i->alasan,
                'is_izin' => true,
            ];
        }

        // Masukkan data Presensi
        foreach ($presences as $p) {
            $day = (int) Carbon::parse($p->waktu_tap)->format('j');
            $time = Carbon::parse($p->waktu_tap)->format('H:i');
            $isDL = (bool) ($p->is_dinas_luar ?? false);
            
            if (!isset($this->presenceData[$day]) || ($this->presenceData[$day]['is_izin'] ?? false)) {
                // Presensi pertama kali di hari itu (Masuk) atau menimpa status Izin
                $this->presenceData[$day] = [
                    'status' => $isDL ? 'orange' : 'light',
                    'jam_masuk' => $time,
                    'jam_pulang' => '-',
                    'is_dinas_luar' => $isDL,
                    'is_izin' => false,
                ];
            } else {
                // Presensi kedua dan seterusnya di hari yang sama (Pulang)
                $this->presenceData[$day]['jam_pulang'] = $time;
                
                // Jika salah satu (masuk atau pulang) berstatus Dinas Luar, set status jadi orange
                if ($isDL || ($this->presenceData[$day]['is_dinas_luar'] ?? false)) {
                    $this->presenceData[$day]['status'] = 'orange';
                    $this->presenceData[$day]['is_dinas_luar'] = true;
                } else {
                    $this->presenceData[$day]['status'] = 'dark';
                }
            }
        }
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->fetchPresenceData();
        $this->dispatch('month-changed', $this->currentMonth, $this->currentYear);
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->fetchPresenceData();
        $this->dispatch('month-changed', $this->currentMonth, $this->currentYear);
    }
}
