<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranGuruTuResource\Pages;
use App\Models\KehadiranGuruTu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class KehadiranGuruTuResource extends Resource
{
    protected static ?string $model = KehadiranGuruTu::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Laporan Kehadiran';
    protected static ?string $navigationGroup = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== 'Admin';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Admin', 'Guru', 'TU']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Grouping data berdasarkan NIPY dan Tanggal agar jadi satu baris
        $query->select([
            DB::raw('MIN(id) as id'), // ID bayangan untuk Filament
            'nipy',
            DB::raw('DATE(waktu_tap) as tanggal'),
            DB::raw('MAX(status) as status'),
        ])
        ->groupBy('nipy', DB::raw('DATE(waktu_tap)'));

        if ($user && $user->role !== 'Admin') {
            $query->where(function ($q) use ($user) {
                $q->where('nipy', $user->nipy)->orWhere('nipy', $user->email);
            });
        }

        return $query->orderBy('tanggal', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nipy')
                    ->label('NIPY')
                    ->required(),
                Forms\Components\DateTimePicker::make('waktu_tap')
                    ->label('Waktu Tap')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                        'Dinas Luar' => 'Dinas Luar',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pegawai_name')
                    ->label('Nama Pegawai')
                    ->getStateUsing(function ($record) {
                        $user = \App\Models\User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first();
                        return $user ? $user->name : $record->nipy;
                    })
                    ->description(fn($record) => $record->nipy)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('nipy', 'like', "%{$search}%");
                    }),

                // KOLOM MASUK (JAM + FOTO)
                Tables\Columns\TextColumn::make('masuk')
                    ->label('Sesi Masuk')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $data = KehadiranGuruTu::where('nipy', $record->nipy)
                            ->whereDate('waktu_tap', $record->tanggal)
                            ->where('keterangan', 'like', '%Masuk%')
                            ->orderBy('waktu_tap', 'asc')
                            ->first();

                        if (!$data) return '<span class="text-gray-400">---</span>';

                        $jam = Carbon::parse($data->waktu_tap)->format('H:i');
                        $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
                        
                        return "
                            <div class='flex items-center gap-3'>
                                <img src='{$photoUrl}' class='w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm' />
                                <div class='flex flex-col'>
                                    <span class='font-bold text-success-600'>{$jam}</span>
                                    <span class='text-[10px] text-gray-400 uppercase tracking-tighter'>Absen Masuk</span>
                                </div>
                            </div>
                        ";
                    }),

                // KOLOM PULANG (JAM + FOTO)
                Tables\Columns\TextColumn::make('pulang')
                    ->label('Sesi Pulang')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $data = KehadiranGuruTu::where('nipy', $record->nipy)
                            ->whereDate('waktu_tap', $record->tanggal)
                            ->where('keterangan', 'like', '%Pulang%')
                            ->orderBy('waktu_tap', 'desc')
                            ->first();

                        if (!$data) return '<span class="text-gray-400 text-[10px] italic">Belum Pulang</span>';

                        $jam = Carbon::parse($data->waktu_tap)->format('H:i');
                        $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
                        
                        return "
                            <div class='flex items-center gap-3'>
                                <img src='{$photoUrl}' class='w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm' />
                                <div class='flex flex-col'>
                                    <span class='font-bold text-warning-600'>{$jam}</span>
                                    <span class='text-[10px] text-gray-400 uppercase tracking-tighter'>Absen Pulang</span>
                                </div>
                            </div>
                        ";
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Izin' => 'info',
                        'Sakit' => 'warning',
                        'Dinas Luar' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tanggal')
                    ->label('Periode')
                    ->options([
                        'today'   => 'Hari Ini',
                        'week'    => 'Minggu Ini',
                        'month'   => 'Bulan Ini',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('waktu_tap', now()),
                            'week'  => $query->whereBetween('waktu_tap', [now()->startOfWeek(), now()->endOfWeek()]),
                            'month' => $query->whereMonth('waktu_tap', now()->month)->whereYear('waktu_tap', now()->year),
                            default => $query,
                        };
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated(true)
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranGuruTus::route('/'),
        ];
    }
}
