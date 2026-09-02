<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PresensiHariIniGuruTuResource\Pages;
use App\Models\KehadiranGuruTu;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PresensiHariIniGuruTuResource extends Resource
{
    protected static ?string $model = KehadiranGuruTu::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Hadir Hari Ini — Guru/TU';
    protected static ?string $navigationGroup = 'Presensi Hari Ini';
    protected static ?int    $navigationSort = 2;
    protected static ?string $slug = 'presensi-hari-ini-guru-tu';

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
                'nipy',
                DB::raw('MIN(waktu_tap) as waktu_masuk'),
                DB::raw('MAX(waktu_tap) as waktu_pulang'),
                DB::raw('COUNT(*) as jumlah_tap'),
                DB::raw('MAX(status) as status'),
                DB::raw('MAX(keterangan) as keterangan'),
                DB::raw('MAX(photo) as photo'),
                DB::raw('MAX(is_dinas_luar) as is_dinas_luar'),
                DB::raw('MAX(lokasi_dinas_luar) as lokasi_dinas_luar'),
            ])
            ->whereDate('waktu_tap', Carbon::today())
            ->groupBy('nipy')
            ->orderBy('waktu_masuk', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    // Badge Estetik RFID Mesin / RFID HP / Foto Wajah
                    Tables\Columns\TextColumn::make('foto_rfid')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            if (!empty($record->photo) && $record->photo !== 'rfid_placeholder' && file_exists(public_path('storage/' . $record->photo))) {
                                $imgUrl = asset('storage/' . $record->photo);
                                return "<img src='{$imgUrl}' style='width:48px; height:48px; min-width:48px; min-height:48px; max-width:48px; max-height:48px; object-fit:cover; border-radius:9999px;' class='border-2 border-emerald-500 shadow-sm' alt='Foto Absen'>";
                            }

                            $ketLower = strtolower($record->keterangan ?? '');
                            $isNfcHp = str_contains($ketLower, 'nfc') || str_contains($ketLower, 'hp') || str_contains($ketLower, 'smartphone');

                            if ($isNfcHp) {
                                // Badge Estetik RFID HP
                                return "
                                    <div class='flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-700 text-white shadow-md border border-teal-300/40 p-1 text-center transition-transform duration-200 hover:scale-105' title='Presensi via RFID / NFC Smartphone (HP)'>
                                        <svg class='w-5 h-5 text-white mb-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'></path>
                                        </svg>
                                        <span class='text-[7px] font-black uppercase tracking-wider text-teal-100 leading-tight whitespace-nowrap'>RFID HP</span>
                                    </div>
                                ";
                            }

                            // Default: Badge Estetik RFID Mesin
                            return "
                                <div class='flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 text-white shadow-md border border-indigo-300/40 p-1 text-center transition-transform duration-200 hover:scale-105' title='Presensi via Mesin RFID Fisik'>
                                    <svg class='w-5 h-5 text-white mb-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'></path>
                                    </svg>
                                    <span class='text-[7px] font-black uppercase tracking-wider text-blue-100 leading-tight whitespace-nowrap'>RFID MESIN</span>
                                </div>
                            ";
                        })
                        ->grow(false),

                    // Nama + Role + NIPY
                    Tables\Columns\TextColumn::make('nama_pegawai')
                        ->label('Nama Pegawai')
                        ->html()
                        ->getStateUsing(function ($record) {
                            $user  = User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                            $name  = $user?->name  ?? $record->nipy;
                            $role  = $user?->role  ?? '-';
                            $nipy  = $record->nipy;
                            $roleColor = $role === 'Guru' ? 'blue' : 'purple';
                            return "
                                <div class='flex flex-col gap-0.5'>
                                    <span class='text-sm font-bold text-gray-900 dark:text-white'>{$name}</span>
                                    <div class='flex items-center gap-1.5'>
                                        <span class='text-[10px] px-1.5 py-0.5 bg-{$roleColor}-50 dark:bg-{$roleColor}-900/30 text-{$roleColor}-700 dark:text-{$roleColor}-300 rounded border border-{$roleColor}-200 dark:border-{$roleColor}-700 font-bold'>{$role}</span>
                                        <span class='text-xs text-gray-400 dark:text-gray-500'>{$nipy}</span>
                                    </div>
                                </div>
                            ";
                        })
                        ->searchable(query: fn (Builder $q, string $s) => $q->where('nipy', 'like', "%{$s}%")),

                    // Waktu Masuk & Pulang
                    Tables\Columns\TextColumn::make('waktu_masuk')
                        ->label('Waktu')
                        ->html()
                        ->getStateUsing(function ($record) {
                            $masuk  = $record->waktu_masuk  ? Carbon::parse($record->waktu_masuk)->format('H:i')  : '-';
                            $pulang = $record->waktu_pulang && $record->waktu_pulang !== $record->waktu_masuk
                                ? Carbon::parse($record->waktu_pulang)->format('H:i') : null;
                            $pulangBadge = $pulang
                                ? "<span class='text-[10px] px-1.5 py-0.5 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-md border border-orange-200 dark:border-orange-700 font-bold'>Pulang: {$pulang}</span>"
                                : "<span class='text-[10px] px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-md border border-gray-200 dark:border-gray-700'>Belum Pulang</span>";
                            return "
                                <div class='flex flex-wrap items-center gap-1'>
                                    <span class='text-[10px] px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-md border border-emerald-200 dark:border-emerald-700 font-bold'>Masuk: {$masuk}</span>
                                    {$pulangBadge}
                                </div>
                            ";
                        })
                        ->grow(false),

                    // Badge Status
                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Hadir'      => 'success',
                            'Dinas Luar' => 'primary',
                            'Izin'       => 'info',
                            'Sakit'      => 'warning',
                            default      => 'gray',
                        })
                        ->grow(false),
                ])->from('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options([
                        'Guru' => 'Guru',
                        'TU'   => 'Tata Usaha (TU)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        $nipys = User::where('role', $data['value'])->pluck('nipy');
                        return $query->whereIn('nipy', $nipys);
                    }),

                Tables\Filters\TernaryFilter::make('is_dinas_luar')
                    ->label('Dinas Luar')
                    ->trueLabel('Dinas Luar Saja')
                    ->falseLabel('Di Sekolah Saja')
                    ->nullable(),
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
            'index' => Pages\ListPresensiHariIniGuruTu::route('/'),
        ];
    }
}
