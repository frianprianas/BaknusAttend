<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranGuruTuMonthlyResource\Pages;
use App\Models\User;
use App\Models\KehadiranGuruTu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class KehadiranGuruTuMonthlyResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Rekap Bulanan Guru/TU';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'Admin' || auth()->user()?->is_kepsek;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin' || auth()->user()?->is_kepsek;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('role', ['Guru', 'TU'])
            ->orderBy('name', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('face_reference')
                    ->label('Foto')
                    ->html()
                    ->getStateUsing(function($record) {
                        if (!$record->face_reference) {
                            return "<div class='flex items-center justify-center w-10 h-10 bg-gray-50 border border-dashed border-gray-300 rounded-lg'><span class='text-[7px] text-gray-400 italic text-center leading-tight'>Kosong</span></div>";
                        }
                        $url = asset('storage/' . $record->face_reference);
                        return "
                            <div x-data='{ open: false }' class='relative'>
                                <img 
                                    @click='open = true'
                                    src='{$url}' 
                                    class='w-10 h-10 rounded-lg object-cover ring-1 ring-white shadow-sm hover:scale-110 transition-transform cursor-zoom-in' 
                                />
                                <template x-teleport='body'>
                                    <div x-show='open' x-cloak @click='open = false' class='fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-8'>
                                        <div class='relative max-w-[280px]'>
                                            <img src='{$url}' class='w-full rounded-xl border-[4px] border-white shadow-xl' />
                                            <div class='absolute -top-3 -right-3 bg-red-500 text-white p-1.5 rounded-full shadow-lg'>
                                                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M6 18L18 6M6 6l12 12' stroke-linecap='round' stroke-linejoin='round' stroke-width='3'></path></svg>
                                            </div>
                                            <div class='text-center mt-2 text-white font-bold text-xs'>{$record->name}</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        ";
                    })
                    ->grow(false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->description(fn($record) => "ID: " . ($record->nipy ?? $record->email))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('statistik')
                    ->label('Statistik Kehadiran')
                    ->html()
                    ->getStateUsing(function($record, Tables\Table $table) {
                        try {
                            $formDate = $table->getLivewire()->tableFilters ?? [];
                            $selMonth = $formDate['bulan']['value'] ?? request()->query('tableFilters')['bulan']['value'] ?? now()->format('m');
                            $selYear = $formDate['tahun']['value'] ?? request()->query('tableFilters')['tahun']['value'] ?? now()->format('Y');
                            
                            $month = (int) $selMonth;
                            $year = (int) $selYear;

                            $service = new \App\Services\AttendanceService();
                            $activeDays = $service->getEffectiveWorkingDays($month, $year);
                            
                            // Ambil SEMUA records (Masuk & Pulang) agar bisa dideteksi jika salah satunya DL
                            $allMonthRecords = KehadiranGuruTu::where(function($q) use ($record) {
                                    $q->where('nipy', $record->nipy)->orWhere('nipy', $record->email);
                                })
                                ->whereMonth('waktu_tap', $month)
                                ->whereYear('waktu_tap', $year)
                                ->whereIn('status', ['Hadir', 'Terlambat', 'Dinas Luar'])
                                ->get()
                                ->groupBy(fn($item) => Carbon::parse($item->waktu_tap)->format('Y-m-d'));
                            
                            $hadirSekolah = 0;
                            $hadirDL      = 0;

                            foreach ($allMonthRecords as $date => $dayRecords) {
                                // Jika ada salah satu saja di hari itu yang DL, maka hitung sebagai DL
                                if ($dayRecords->contains('is_dinas_luar', true) || $dayRecords->contains('status', 'Dinas Luar')) {
                                    $hadirDL++;
                                } else {
                                    $hadirSekolah++;
                                }
                            }
                            
                            $totalHadir = $hadirSekolah + $hadirDL;

                            $izins = \App\Models\IzinGuruTu::where(function($q) use ($record) {
                                    $q->where('nipy', $record->nipy)->orWhere('nipy', $record->email);
                                })
                                ->whereMonth('tanggal', $month)
                                ->whereYear('tanggal', $year)
                                ->whereIn('status', ['Diajukan', 'Disetujui'])
                                ->get();
                            
                            $sakitCount = $izins->where('tipe', 'Sakit')->count();
                            $izinCount  = $izins->whereNotIn('tipe', ['Sakit'])->count();
                            
                            $persen = $activeDays > 0 ? round(($totalHadir / $activeDays) * 100) : 0;
                            $colorText = $persen >= 80 ? 'text-success-700' : ($persen >= 50 ? 'text-warning-700' : 'text-danger-700');
                            $colorBar  = $persen >= 80 ? 'bg-success-500' : ($persen >= 50 ? 'bg-warning-500' : 'bg-danger-500');
                            
                            return "
                                <div class='flex flex-col gap-1.5 min-w-[220px]'>
                                    <div class='flex items-center gap-1.5 flex-wrap'>
                                        <span class='text-[9px] bg-green-600 text-white px-2 py-0.5 rounded shadow-sm font-bold'>⭐ TOTAL HADIR: {$totalHadir}</span>
                                        <span class='text-[10px] text-gray-500 uppercase ml-auto'>Aktif: <b>{$activeDays}</b></span>
                                    </div>
                                    <div class='flex items-center gap-1.5 flex-wrap'>
                                        <span class='text-[9px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-100 font-bold'>🏠 SEKOLAH: {$hadirSekolah}</span>
                                        <span class='text-[9px] bg-amber-50 text-orange-700 px-1.5 py-0.5 rounded border border-orange-100 font-bold'>🚗 DINAS LUAR: {$hadirDL}</span>
                                    </div>
                                    <div class='flex items-center gap-1.5'>
                                        <span class='text-[9px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded border border-red-100 font-bold'>🏥 SAKIT: {$sakitCount}</span>
                                        <span class='text-[9px] bg-gray-50 text-gray-600 px-1.5 py-0.5 rounded border border-gray-100 font-bold'>📑 IZIN: {$izinCount}</span>
                                        
                                        <div class='flex-1 flex flex-col items-end ml-2'>
                                            <div class='w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200'>
                                                <div class='h-full {$colorBar}' style='width: {$persen}%'></div>
                                            </div>
                                            <span class='text-[10px] font-bold {$colorText}'>{$persen}% HADIR</span>
                                        </div>
                                    </div>
                                </div>
                            ";
                        } catch (\Exception $e) {
                            return "<span class='text-[10px] text-gray-400 italic px-2 py-1 bg-gray-50 rounded border border-gray-200 shadow-sm'>Menghitung data...</span>";
                        }
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                        '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                    ])
                    ->query(fn (Builder $query) => $query)
                    ->default(now()->format('m')),

                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function() {
                        $years = [];
                        $currentYear = (int) now()->year;
                        for ($i = $currentYear; $i >= $currentYear - 2; $i--) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->query(fn (Builder $query) => $query)
                    ->default(now()->year),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Jabatan')
                    ->options([
                        'all' => 'Semua (Guru & TU)',
                        'Guru' => 'Guru',
                        'TU' => 'TU',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value']) && $data['value'] !== 'all') {
                            $query->where('role', $data['value']);
                        }
                    })
                    ->default('all'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('detailHarian')
                    ->label('Rincian Harian')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => "Rincian Kehadiran - " . $record->name)
                    ->modalContent(function($record, Tables\Table $table) {
                        $formDate = $table->getLivewire()->tableFilters ?? [];
                        $selMonth = $formDate['bulan']['value'] ?? request()->query('tableFilters')['bulan']['value'] ?? now()->format('m');
                        $selYear = $formDate['tahun']['value'] ?? request()->query('tableFilters')['tahun']['value'] ?? now()->format('Y');

                        $month = (int) $selMonth;
                        $year = (int) $selYear;
                        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

                        $kehadirans = KehadiranGuruTu::where(function($q) use ($record) {
                                $q->where('nipy', $record->nipy)->orWhere('nipy', $record->email);
                            })
                            ->whereMonth('waktu_tap', $month)
                            ->whereYear('waktu_tap', $year)
                            ->get()
                            ->groupBy(fn($item) => Carbon::parse($item->waktu_tap)->format('Y-m-d'));

                        $izins = \App\Models\IzinGuruTu::where(function($q) use ($record) {
                                $q->where('nipy', $record->nipy)->orWhere('nipy', $record->email);
                            })
                            ->whereMonth('tanggal', $month)
                            ->whereYear('tanggal', $year)
                            ->get()
                            ->keyBy(fn($item) => Carbon::parse($item->tanggal)->format('Y-m-d'));

                        $holidays = \App\Models\Holiday::whereMonth('holiday_date', $month)
                            ->whereYear('holiday_date', $year)
                            ->get()
                            ->keyBy(fn($item) => Carbon::parse($item->holiday_date)->format('Y-m-d'));

                        $rowsHtml = "";
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dateObj = Carbon::create($year, $month, $day);
                            $dateStr = $dateObj->format('Y-m-d');
                            $dayName = $dateObj->translatedFormat('l, d M Y');

                            $dayKehadiran = $kehadirans->get($dateStr);
                            $dayIzin = $izins->get($dateStr);
                            $dayHoliday = $holidays->get($dateStr);

                            $statusBadge = "";
                            $detailInfo = "-";

                            if ($dayKehadiran && $dayKehadiran->count() > 0) {
                                $masuk = $dayKehadiran->first(fn($i) => str_contains(strtolower($i->keterangan ?? ''), 'masuk') || !str_contains(strtolower($i->keterangan ?? ''), 'pulang')) ?? $dayKehadiran->first();
                                $pulang = $dayKehadiran->first(fn($i) => str_contains(strtolower($i->keterangan ?? ''), 'pulang'));

                                $waktuMasuk = $masuk ? Carbon::parse($masuk->waktu_tap)->format('H:i:s') : '-';
                                $waktuPulang = $pulang ? Carbon::parse($pulang->waktu_tap)->format('H:i:s') : '-';

                                $isDl = $dayKehadiran->contains('is_dinas_luar', true) || $dayKehadiran->contains('status', 'Dinas Luar');
                                $isTerlambat = $dayKehadiran->contains('status', 'Terlambat');

                                if ($isDl) {
                                    $statusBadge = "<span class='bg-orange-100 text-orange-800 text-xs px-2 py-0.5 rounded font-bold'>Dinas Luar</span>";
                                    $detailInfo = "Masuk: {$waktuMasuk} | Pulang: {$waktuPulang}";
                                } elseif ($isTerlambat) {
                                    $statusBadge = "<span class='bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded font-bold'>Terlambat</span>";
                                    $detailInfo = "Masuk: {$waktuMasuk} | Pulang: {$waktuPulang}";
                                } else {
                                    $statusBadge = "<span class='bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded font-bold'>Hadir</span>";
                                    $detailInfo = "Masuk: {$waktuMasuk} | Pulang: {$waktuPulang}";
                                }
                            } elseif ($dayIzin) {
                                $statusBadge = "<span class='bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded font-bold'>{$dayIzin->tipe}</span>";
                                $detailInfo = "Alasan: {$dayIzin->alasan}";
                            } elseif ($dayHoliday) {
                                $statusBadge = "<span class='bg-purple-100 text-purple-800 text-xs px-2 py-0.5 rounded font-bold'>Libur</span>";
                                $detailInfo = $dayHoliday->holiday_name;
                            } elseif ($dateObj->isWeekend()) {
                                $statusBadge = "<span class='bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-bold'>Akhir Pekan</span>";
                                $detailInfo = "Sabtu / Minggu";
                            } else {
                                $statusBadge = "<span class='bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded font-bold'>Tanpa Keterangan</span>";
                                $detailInfo = "Tidak ada presensi";
                            }

                            $rowsHtml .= "
                                <tr class='border-b hover:bg-gray-50 text-xs'>
                                    <td class='px-3 py-2 font-medium text-gray-900'>{$dayName}</td>
                                    <td class='px-3 py-2'>{$statusBadge}</td>
                                    <td class='px-3 py-2 text-gray-600'>{$detailInfo}</td>
                                </tr>
                            ";
                        }

                        return new \Illuminate\Support\HtmlString("
                            <div class='max-h-[60vh] overflow-y-auto border rounded-lg shadow-sm'>
                                <table class='w-full text-left border-collapse'>
                                    <thead class='bg-gray-100 text-xs uppercase font-semibold text-gray-700 sticky top-0'>
                                        <tr>
                                            <th class='px-3 py-2 border-b'>Hari & Tanggal</th>
                                            <th class='px-3 py-2 border-b'>Status</th>
                                            <th class='px-3 py-2 border-b'>Rincian Waktu / Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {$rowsHtml}
                                    </tbody>
                                </table>
                            </div>
                        ");
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([])
            ->paginated(true)
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranGuruTuMonthlies::route('/'),
        ];
    }
}
