<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Kalender Libur';
    protected static ?string $navigationGroup = 'Sistem';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tipe')
                    ->label('Jenis / Kategori Libur')
                    ->options([
                        'Internal Sekolah' => 'Libur Internal Sekolah',
                        'Nasional' => 'Libur Nasional / Tanggal Merah',
                    ])
                    ->default('Internal Sekolah')
                    ->required()
                    ->helperText('Pilih apakah ini hari libur internal sekolah atau libur nasional.'),
                Forms\Components\DatePicker::make('holiday_date')
                    ->label('Tanggal Libur')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->placeholder('Pilih tanggal...'),
                Forms\Components\TextInput::make('name')
                    ->label('Keterangan Libur')
                    ->placeholder('Contoh: Libur Akhir Semester, Classmeeting, Libur Awal Ramadhan, Cuti Bersama')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('holiday_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Keterangan')
                    ->searchable()
                    ->placeholder('Libur Sekolah'),
                Tables\Columns\TextColumn::make('tipe')
                    ->label('Jenis Libur')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Nasional' => 'info',
                        'Internal Sekolah' => 'warning',
                        default => 'gray',
                    })
                    ->default('Internal Sekolah')
                    ->sortable(),
            ])
            ->defaultSort('holiday_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Jenis Libur')
                    ->options([
                        'Internal Sekolah' => 'Libur Internal Sekolah',
                        'Nasional' => 'Libur Nasional',
                    ]),
                Tables\Filters\Filter::make('tahun_ini')
                    ->label('Tahun Ini (' . now()->year . ')')
                    ->query(fn ($query) => $query->whereYear('holiday_date', now()->year))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
