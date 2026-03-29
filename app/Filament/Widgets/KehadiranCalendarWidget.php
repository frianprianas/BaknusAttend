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

        // Ambil data hari yang ada absensi (Distinct Date)
        $presences = KehadiranGuruTu::where(function($q) use ($user) {
                $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
            })
            ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
            ->select(DB::raw('DATE(waktu_tap) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->get();

        $this->presenceData = [];
        foreach ($presences as $p) {
            $day = (int) Carbon::parse($p->date)->format('j');
            // Jika total 2 berarti Masuk & Pulang (Biru Tua), Jika 1 berarti Masuk saja (Biru Muda)
            $this->presenceData[$day] = $p->total >= 2 ? 'dark' : 'light';
        }
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->fetchPresenceData();
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->fetchPresenceData();
    }
}
