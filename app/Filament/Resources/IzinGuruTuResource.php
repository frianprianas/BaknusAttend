<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IzinGuruTuResource\Pages;
use App\Models\IzinGuruTu;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IzinGuruTuResource extends Resource
{
    protected static ?string $model = IzinGuruTu::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Izin / Sakit Guru & TU';
    protected static ?string $navigationGroup = 'Laporan Presensi';
    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('nipy')
                    ->label('Pegawai')
                    ->options(User::whereIn('role', ['Guru', 'TU'])->pluck('name', 'nipy'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')->label('Tanggal')->required()->default(now()),
                Forms\Components\Select::make('tipe')
                    ->label('Jenis')
                    ->options(['Izin' => 'Izin', 'Sakit' => 'Sakit'])
                    ->required(),
                Forms\Components\Textarea::make('alasan')->label('Alasan')->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(['Diajukan' => 'Diajukan', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak', 'Dibatalkan' => 'Dibatalkan'])
                    ->required()
                    ->default('Diajukan'),
                Forms\Components\FileUpload::make('bukti')
                    ->label('Lampiran Bukti')
                    ->image()
                    ->disk('public')
                    ->directory('izin-bukti')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pegawai')
                    ->label('Nama Pegawai')
                    ->getStateUsing(fn($record) => User::where('nipy', $record->nipy)->orWhere('email', $record->nipy)->first()?->name ?? $record->nipy)
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('nipy', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('tipe')->label('Jenis')->badge()
                    ->color(fn(string $state) => match($state) { 'Izin' => 'info', 'Sakit' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('alasan')->label('Alasan')->limit(40)->tooltip(fn($record) => $record->alasan),
                Tables\Columns\ImageColumn::make('bukti')->label('Bukti')->circular()->defaultImageUrl(null)
                    ->action(
                        Tables\Actions\Action::make('view_bukti')
                            ->modalHeading('Lampiran Bukti')
                            ->modalContent(fn($record) => view('components.image-modal', ['imageUrl' => $record->bukti ? asset('storage/' . $record->bukti) : null]))
                            ->modalSubmitAction(false)->modalCancelAction(false)
                    ),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn(string $state) => match($state) {
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        'Dibatalkan' => 'gray',
                        default => 'warning'
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Diajukan Pada')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')->options(['Izin' => 'Izin', 'Sakit' => 'Sakit']),
                Tables\Filters\SelectFilter::make('status')->options(['Diajukan' => 'Diajukan', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak', 'Dibatalkan' => 'Dibatalkan']),
            ])
            ->actions([
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn($record) => $record->status === 'Diajukan')
                    ->action(fn($record) => $record->update(['status' => 'Disetujui']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn($record) => $record->status === 'Diajukan')
                    ->action(fn($record) => $record->update(['status' => 'Ditolak']))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIzinGuruTus::route('/'),
        ];
    }
}
