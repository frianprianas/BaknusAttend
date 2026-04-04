<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if ($user && $user->role === 'Siswa') {
            return parent::getEloquentQuery()
                ->where('attendable_id', $user->id)
                ->where('attendable_type', \App\Models\User::class);
        }
        return parent::getEloquentQuery();
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Presensi / Absensi';

    protected static ?string $pluralLabel = 'Presensi / Absensi';

    protected static ?string $modelLabel = 'Presensi';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\Select::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                        'Terlambat' => 'Terlambat',
                    ])
                    ->required(),
                Forms\Components\MorphToSelect::make('attendable')
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(\App\Models\Student::class)
                            ->titleAttribute('name')
                            ->label('Siswa'),
                        Forms\Components\MorphToSelect\Type::make(\App\Models\User::class)
                            ->titleAttribute('name')
                            ->label('Guru / Staff (TU)'),
                    ])
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('remarks')
                    ->placeholder('Catatan (opsional)...')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendable.name')
                    ->label('Nama')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn($record) => $record->attendable?->name),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Sakit' => 'warning',
                        'Izin' => 'info',
                        'Alpa' => 'danger',
                        'Terlambat' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('attendable_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn($state) => str_contains($state, 'Student') ? 'Siswa' : 'Guru/Staff')
                    ->badge()
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
