<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranSiswaResource\Pages;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class KehadiranSiswaResource extends Resource
{
    protected static ?string $model = KehadiranSiswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Laporan Siswa';
    protected static ?string $navigationGroup = 'Laporan';

    public static function shouldRegisterNavigation(): bool
    {
        // Admin dan Siswa bisa melihat menu ini
        return in_array(auth()->user()?->role, ['Admin', 'Siswa']);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['Admin', 'Siswa']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Grouping data berdasarkan NIS dan Tanggal agar jadi satu baris
        $query->select([
            DB::raw('MIN(id) as id'),
            'nis',
            DB::raw('DATE(waktu_tap) as tanggal'),
            DB::raw('MAX(status) as status'),
        ])
        ->groupBy('nis', DB::raw('DATE(waktu_tap)'));

        if ($user && $user->role === 'Siswa') {
            $student = Student::where('email', $user->email)->first();
            if ($student) {
                $query->where('nis', $student->nis);
            } else {
                $query->where('nis', 'none');
            }
        }

        return $query->orderBy('tanggal', 'desc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student_name')
                    ->label('Nama Siswa')
                    ->getStateUsing(function ($record) {
                        return $record->student?->name ?? $record->nis;
                    })
                    ->description(fn($record) => $record->nis)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('nis', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('masuk')
                    ->label('Masuk')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $data = KehadiranSiswa::where('nis', $record->nis)
                            ->whereDate('waktu_tap', $record->tanggal)
                            ->where('keterangan', 'like', '%Masuk%')
                            ->orderBy('waktu_tap', 'asc')
                            ->first();

                        if (!$data) return '<span class="text-gray-400 text-xs italic italic">---</span>';

                        $jam = Carbon::parse($data->waktu_tap)->format('H:i');
                        $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
                        
                        return "
                            <div class='flex items-center gap-2'>
                                <img src='{$photoUrl}' 
                                    wire:click=\"mountTableAction('view_photo', {id: '{$data->id}'})\"
                                    class='w-8 h-8 rounded-full object-cover border border-gray-200 cursor-zoom-in pointer-events-auto' 
                                />
                                <span class='font-bold text-success-600'>{$jam}</span>
                            </div>
                        ";
                    }),

                Tables\Columns\TextColumn::make('pulang')
                    ->label('Pulang')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $data = KehadiranSiswa::where('nis', $record->nis)
                            ->whereDate('waktu_tap', $record->tanggal)
                            ->where('keterangan', 'like', '%Pulang%')
                            ->orderBy('waktu_tap', 'desc')
                            ->first();

                        if (!$data) return '<span class="text-gray-400 text-xs italic italic">---</span>';

                        $jam = Carbon::parse($data->waktu_tap)->format('H:i');
                        $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
                        
                        return "
                            <div class='flex items-center gap-2'>
                                <img src='{$photoUrl}' 
                                    wire:click=\"mountTableAction('view_photo', {id: '{$data->id}'})\"
                                    class='w-8 h-8 rounded-full object-cover border border-gray-200 cursor-zoom-in pointer-events-auto' 
                                />
                                <span class='font-bold text-warning-600'>{$jam}</span>
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
                        'Alpa' => 'danger',
                        'Terlambat' => 'warning',
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
            ->actions([
                Tables\Actions\Action::make('view_photo')
                    ->label('Lihat Foto')
                    ->modalHeading('Detail Foto Presensi Siswa')
                    ->modalContent(fn ($arguments) => view('filament.components.image-modal', [
                        'url' => ($data = KehadiranSiswa::find($arguments['id'])) && $data->photo 
                             ? asset('storage/' . $data->photo) 
                             : url('/images/user-placeholder.png')
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->hidden(),
            ])
            ->paginated(true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKehadiranSiswas::route('/'),
        ];
    }
}
