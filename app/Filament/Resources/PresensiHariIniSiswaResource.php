<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PresensiHariIniSiswaResource\Pages;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PresensiHariIniSiswaResource extends Resource
{
    protected static ?string $model = KehadiranSiswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Hadir Hari Ini — Siswa';
    protected static ?string $navigationGroup = 'Presensi Hari Ini';
    protected static ?int    $navigationSort = 1;
    protected static ?string $slug = 'presensi-hari-ini-siswa';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'Admin' || $user->is_kepsek);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'Admin' || $user->is_kepsek);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                DB::raw('MIN(id) as id'),
                'nis',
                DB::raw('MIN(waktu_tap) as waktu_masuk'),
                DB::raw('MAX(waktu_tap) as waktu_pulang'),
                DB::raw('COUNT(*) as jumlah_tap'),
                DB::raw('MAX(status) as status'),
                DB::raw('MAX(keterangan) as keterangan'),
                DB::raw('MAX(photo) as photo'),
            ])
            ->whereDate('waktu_tap', Carbon::today())
            ->groupBy('nis')
            ->orderBy('waktu_masuk', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    // Badge Estetik Mesin RFID / Foto
                    Tables\Columns\TextColumn::make('foto_rfid')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            if (!empty($record->photo) && $record->photo !== 'rfid_placeholder' && file_exists(public_path('storage/' . $record->photo))) {
                                $imgUrl = asset('storage/' . $record->photo);
                                return "<img src='{$imgUrl}' style='width:48px; height:48px; min-width:48px; min-height:48px; max-width:48px; max-height:48px; object-fit:cover; border-radius:9999px;' class='border-2 border-indigo-500 shadow-sm' alt='Foto Absen'>";
                            }
                            
                            // Badge Estetik Mesin RFID Kartu
                            return "
                                <div class='flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 text-white shadow-md border border-indigo-300/40 p-1 text-center transition-transform duration-200 hover:scale-105' title='Presensi via Mesin RFID Kartu'>
                                    <svg class='w-5 h-5 text-white mb-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'></path>
                                    </svg>
                                    <span class='text-[7.5px] font-black uppercase tracking-wider text-blue-100 leading-tight'>RFID</span>
                                </div>
                            ";
                        })
                        ->grow(false),

                    // Nama + NIS + Kelas
                    Tables\Columns\TextColumn::make('nama_siswa')
                        ->label('Nama Siswa')
                        ->html()
                        ->getStateUsing(function ($record) {
                            $student = $record->student;
                            $name  = $student?->name  ?? $record->nis;
                            $nis   = $record->nis;
                            $kelas = $student?->classRoom?->kelas ?? '-';
                            return "
                                <div class='flex flex-col gap-0.5'>
                                    <span class='text-sm font-bold text-gray-900 dark:text-white'>{$name}</span>
                                    <span class='text-xs text-gray-500 dark:text-gray-400'>NIS: {$nis} &nbsp;·&nbsp; {$kelas}</span>
                                </div>
                            ";
                        })
                        ->searchable(query: fn (Builder $q, string $s) => $q->where('nis', 'like', "%{$s}%")),

                    // Waktu Masuk & Pulang
                    Tables\Columns\TextColumn::make('waktu_masuk')
                        ->label('Waktu')
                        ->html()
                        ->getStateUsing(function ($record) {
                            $masuk  = $record->waktu_masuk  ? Carbon::parse($record->waktu_masuk)->format('H:i')  : '-';
                            $pulang = $record->waktu_pulang ? Carbon::parse($record->waktu_pulang)->format('H:i') : '-';
                            $sameBadge = ($masuk === $pulang) ? 
                                "<span class='text-[10px] px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-md border border-gray-200 dark:border-gray-700 ml-1'>Belum Pulang</span>" :
                                "<span class='text-[10px] px-1.5 py-0.5 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-md border border-orange-200 dark:border-orange-700 font-bold ml-1'>Pulang: {$pulang}</span>";
                            return "
                                <div class='flex flex-wrap items-center gap-1'>
                                    <span class='text-[10px] px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-md border border-emerald-200 dark:border-emerald-700 font-bold'>Masuk: {$masuk}</span>
                                    {$sameBadge}
                                </div>
                            ";
                        })
                        ->grow(false),

                    // Badge Status
                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Hadir'     => 'success',
                            'Terlambat' => 'warning',
                            'Izin'      => 'info',
                            'Sakit'     => 'warning',
                            default     => 'gray',
                        })
                        ->grow(false),
                ])->from('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kelas')
                    ->label('Filter Kelas')
                    ->options(function () {
                        return \App\Models\ClassRoom::pluck('kelas', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        $niss = Student::where('class_room_id', $data['value'])->pluck('nis');
                        return $query->whereIn('nis', $niss);
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated(true)
            ->defaultPaginationPageOption(50)
            ->poll('60s');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresensiHariIniSiswa::route('/'),
        ];
    }
}
