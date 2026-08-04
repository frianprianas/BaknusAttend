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
        // Tampilkan untuk Guru, TU, dan Siswa
        $user = auth()->user();
        return $user && in_array($user->role, ['Guru', 'TU', 'Siswa']);
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
        if ($user->role === 'Siswa') {
            $student = \App\Models\Student::where('email', $user->email)->first();
            $nis = $student ? $student->nis : 'none';
            
            $presences = \App\Models\KehadiranSiswa::where('nis', $nis)
                ->whereBetween('waktu_tap', [$startOfMonth, $endOfMonth])
                ->orderBy('waktu_tap', 'asc')
                ->get();
                
            $izins = []; // Siswa tidak menggunakan tabel IzinGuruTu terpisah
        } else {
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
        }

        // 3. Ambil data Libur Nasional / Sekolah
        $holidays = \App\Models\Holiday::whereBetween('holiday_date', [$startOfMonth, $endOfMonth])->get();

        $this->presenceData = [];

        // A. Masukkan libur akhir pekan (Minggu)
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $date = Carbon::create($this->currentYear, $this->currentMonth, $day);
            if ($date->isSunday()) {
                $this->presenceData[$day] = [
                    'status' => 'red-holiday',
                    'jam_masuk' => 'Libur',
                    'jam_pulang' => 'Hari Minggu',
                    'is_izin' => true,
                ];
            }
        }

        // B. Masukkan data Libur dari database
        foreach ($holidays as $h) {
            $day = (int) Carbon::parse($h->holiday_date)->format('j');
            $this->presenceData[$day] = [
                'status' => 'red-holiday',
                'jam_masuk' => 'Libur',
                'jam_pulang' => $h->name,
                'is_izin' => true,
            ];
        }

        // C. Masukkan data Izin / Sakit (hanya untuk Guru/TU)
        foreach ($izins as $i) {
            $day = (int) Carbon::parse($i->tanggal)->format('j');
            $this->presenceData[$day] = [
                'status' => 'red',
                'jam_masuk' => $i->tipe,
                'jam_pulang' => $i->alasan,
                'is_izin' => true,
            ];
        }

        // D. Masukkan data Presensi (menimpa hari libur/weekend/izin)
        foreach ($presences as $p) {
            $day = (int) Carbon::parse($p->waktu_tap)->format('j');
            $time = Carbon::parse($p->waktu_tap)->format('H:i');
            
            // Jika user adalah siswa, cek apakah status record ini berupa izin/sakit
            if ($user->role === 'Siswa') {
                $statusLower = strtolower($p->status ?? '');
                if ($statusLower === 'izin' || $statusLower === 'sakit') {
                    $this->presenceData[$day] = [
                        'status' => 'red',
                        'jam_masuk' => $p->status,
                        'jam_pulang' => $p->keterangan ?? 'Izin Sekolah',
                        'is_izin' => true,
                    ];
                    continue;
                }
            }

            $isDL = (bool) ($p->is_dinas_luar ?? false);
            
            if (!isset($this->presenceData[$day]) || ($this->presenceData[$day]['is_izin'] ?? false)) {
                // Presensi pertama kali (Masuk) atau menimpa status Izin/Libur/Akhir Pekan
                $this->presenceData[$day] = [
                    'status' => $isDL ? 'orange' : 'light',
                    'jam_masuk' => $time,
                    'jam_pulang' => '-',
                    'is_dinas_luar' => $isDL,
                    'is_izin' => false,
                ];
            } else {
                // Presensi kedua (Pulang)
                $this->presenceData[$day]['jam_pulang'] = $time;
                
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
