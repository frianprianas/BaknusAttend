<?php

namespace App\Filament\Pages;

use App\Models\ClassRoom;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class DaftarSiswaWaliPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Daftar Siswa Wali';
    protected static ?string $title           = 'Presensi Bioskop Siswa Wali';
    protected static ?string $navigationGroup = 'Wali Kelas';
    protected static ?int $navigationSort     = 1;
    protected static string $view             = 'filament.pages.daftar-siswa-wali-page';

    public ?int $selectedClassId = null;
    public ?array $modalStudent = null;
    public bool $showModal = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'Admin' || $user->is_kepsek || $user->isWaliKelas());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Daftar Siswa Wali — Presensi Screen Bioskop';
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $managedClassIds = $user->managedClassIds();

        if (!empty($managedClassIds)) {
            $this->selectedClassId = $managedClassIds[0];
        } else {
            // Admin atau Kepsek: Pilih kelas pertama yang tersedia
            $firstClass = ClassRoom::where('kelas', '!=', 'Belum Ditentukan')->first();
            $this->selectedClassId = $firstClass?->id;
        }
    }

    public function selectClass(int $classId): void
    {
        $this->selectedClassId = $classId;
        $this->showModal = false;
        $this->modalStudent = null;
    }

    public function openStudentModal(string $nis): void
    {
        $student = Student::with('classRoom')->where('nis', $nis)->first();
        if (!$student) return;

        $todayTaps = KehadiranSiswa::where('nis', $nis)
            ->whereDate('waktu_tap', Carbon::today())
            ->orderBy('waktu_tap', 'asc')
            ->get();

        $firstTap = $todayTaps->first();
        $lastTap  = $todayTaps->count() > 1 ? $todayTaps->last() : null;

        // Hitung statistik presensi bulan ini
        $activeDays = (new \App\Services\AttendanceService())->getEffectiveWorkingDays(now()->month, now()->year);
        $hadirMonth = KehadiranSiswa::where('nis', $nis)
            ->whereMonth('waktu_tap', now()->month)
            ->whereYear('waktu_tap', now()->year)
            ->where('keterangan', 'like', '%Masuk%')
            ->count();
        $persenMonth = $activeDays > 0 ? round(($hadirMonth / $activeDays) * 100) : 0;

        $this->modalStudent = [
            'id'             => $student->id,
            'name'           => $student->name,
            'nis'            => $student->nis,
            'kelas'          => $student->classRoom?->kelas ?? '-',
            'rfid'           => $student->rfid ?? '-',
            'photo'          => $student->face_reference ? asset('storage/' . $student->face_reference) : null,
            'waktu_masuk'    => $firstTap ? Carbon::parse($firstTap->waktu_tap)->format('H:i:s') : null,
            'waktu_pulang'   => $lastTap ? Carbon::parse($lastTap->waktu_tap)->format('H:i:s') : null,
            'status_masuk'   => $firstTap ? ($firstTap->status ?? 'Hadir') : 'Belum Tap',
            'keterangan'     => $firstTap ? ($firstTap->keterangan ?? '-') : 'Belum Melakukan Presensi Hari Ini',
            'total_tap_today'=> $todayTaps->count(),
            'persen_bulan'   => $persenMonth,
            'hadir_bulan'    => $hadirMonth,
            'aktif_bulan'    => $activeDays,
        ];

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->modalStudent = null;
    }

    public function getAvailableClassesProperty()
    {
        $user = auth()->user();
        if (!$user) return collect();

        if ($user->role === 'Admin' || $user->is_kepsek) {
            return ClassRoom::where('kelas', '!=', 'Belum Ditentukan')->orderBy('kelas')->get();
        }

        return ClassRoom::whereIn('id', $user->managedClassIds())->orderBy('kelas')->get();
    }

    public function getViewDataProperty(): array
    {
        if (!$this->selectedClassId) {
            return [
                'currentClass' => null,
                'studentGrid'  => collect(),
                'stats'        => ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'belum' => 0, 'persen' => 0]
            ];
        }

        $currentClass = ClassRoom::find($this->selectedClassId);
        if (!$currentClass) {
            return [
                'currentClass' => null,
                'studentGrid'  => collect(),
                'stats'        => ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'belum' => 0, 'persen' => 0]
            ];
        }

        $students = Student::where('class_room_id', $this->selectedClassId)
            ->orderBy('name', 'asc')
            ->get();

        $todayTaps = KehadiranSiswa::whereIn('nis', $students->pluck('nis'))
            ->whereDate('waktu_tap', Carbon::today())
            ->get()
            ->groupBy('nis');

        $totalCount     = $students->count();
        $hadirCount     = 0;
        $terlambatCount = 0;
        $izinCount      = 0;
        $belumCount     = 0;

        $studentGrid = $students->map(function ($student, $index) use ($todayTaps, &$hadirCount, &$terlambatCount, &$izinCount, &$belumCount) {
            $taps = $todayTaps->get($student->nis, collect());
            $firstTap = $taps->sortBy('waktu_tap')->first();
            $lastTap  = $taps->count() > 1 ? $taps->sortBy('waktu_tap')->last() : null;

            $statusCode = 'BELUM';
            $statusLabel = 'Belum Tap';
            $waktuTap = '-';

            if ($firstTap) {
                $statusStr = strtolower($firstTap->status . ' ' . $firstTap->keterangan);
                $waktuTap = Carbon::parse($firstTap->waktu_tap)->format('H:i');

                if (str_contains($statusStr, 'terlambat')) {
                    $statusCode  = 'TERLAMBAT';
                    $statusLabel = "Terlambat ($waktuTap)";
                    $terlambatCount++;
                } elseif (str_contains($statusStr, 'izin') || str_contains($statusStr, 'sakit')) {
                    $statusCode  = 'IZIN';
                    $statusLabel = 'Izin / Sakit';
                    $izinCount++;
                } else {
                    $statusCode  = 'HADIR';
                    $statusLabel = "Hadir ($waktuTap)";
                    $hadirCount++;
                }
            } else {
                $belumCount++;
            }

            return [
                'seat_number'  => sprintf('#%02d', $index + 1),
                'student_id'   => $student->id,
                'nis'          => $student->nis,
                'name'         => $student->name,
                'status_code'  => $statusCode, // HADIR, TERLAMBAT, IZIN, BELUM
                'status_label' => $statusLabel,
                'waktu_masuk'  => $firstTap ? Carbon::parse($firstTap->waktu_tap)->format('H:i') : null,
                'waktu_pulang' => $lastTap ? Carbon::parse($lastTap->waktu_tap)->format('H:i') : null,
                'photo_url'    => $student->face_reference ? asset('storage/' . $student->face_reference) : null,
            ];
        });

        $totalHadirAll = $hadirCount + $terlambatCount;
        $persenHadir   = $totalCount > 0 ? round(($totalHadirAll / $totalCount) * 100) : 0;

        return [
            'currentClass' => $currentClass,
            'studentGrid'  => $studentGrid,
            'stats'        => [
                'total'     => $totalCount,
                'hadir'     => $hadirCount,
                'terlambat' => $terlambatCount,
                'izin'      => $izinCount,
                'belum'     => $belumCount,
                'persen'    => $persenHadir,
            ]
        ];
    }
}
