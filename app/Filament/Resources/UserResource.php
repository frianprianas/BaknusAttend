<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('role', ['Admin', 'Guru', 'TU']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Guru & Staff (TU)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email / Username')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nipy')
                    ->label('NIPY')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->label('Jabatan / Role')
                    ->options([
                        'Admin' => 'Admin',
                        'Guru' => 'Guru',
                        'TU' => 'Tenaga Usaha (TU)',
                    ])
                    ->required()
                    ->default('TU'),
                Forms\Components\TextInput::make('target_hari_kerja')
                    ->label('Target Presensi (Hari)')
                    ->helperText('Jumlah hari target kehadiran dalam satu bulan (Gunakan sebagai acuan laporan)')
                    ->numeric()
                    ->default(20)
                    ->suffix('Hari')
                    ->prefix('Min.')
                    ->required(),
                Forms\Components\TextInput::make('rfid')
                    ->label('Kode Kartu RFID')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Contoh: 0012345678')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('nipy')
                    ->label('NIPY')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->hiddenFrom('md'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Jabatan')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Guru' => 'success',
                        'TU' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfid')
                    ->label('Kode RFID')
                    ->searchable()
                    ->placeholder('-')
                    ->hiddenFrom('md'),

                Tables\Columns\IconColumn::make('push_status')
                    ->label('Notif HP')
                    ->getStateUsing(fn(User $record): string => $record->pushSubscriptions()->exists() ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(string $state): string => $state === 'heroicon-o-check-circle' ? 'success' : 'gray')
                    ->tooltip(fn(User $record): string => $record->pushSubscriptions()->exists() ? 'Perangkat terhubung' : 'Belum mengaktifkan notifikasi'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter Jabatan')
                    ->options([
                        'Admin' => 'Admin',
                        'Guru' => 'Guru',
                        'TU' => 'TU',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('sendPush')
                    ->label('Kirim Pesan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('indigo')
                    ->hidden(fn (User $record): bool => !$record->pushSubscriptions()->exists())
                    ->modalHeading('Kirim Notifikasi ke HP')
                    ->modalDescription('Pesan ini akan langsung muncul di bar notifikasi HP Guru.')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Notifikasi')
                            ->default('Pesan dari Admin')
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Isi Pesan')
                            ->placeholder('Tulis pesan Anda di sini...')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        // 1. Eksekusi pengiriman payload ke Web Push Server (Background iPhone/Android)
                        $record->notify(new \App\Notifications\PushBroadcastNotification(
                            $data['title'],
                            $data['message']
                        ));

                        // 2. Simpan juga ke Database Filament agar terekam di Lonceng Notifikasi 
                        \Filament\Notifications\Notification::make()
                            ->title($data['title'])
                            ->body($data['message'])
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->sendToDatabase($record);

                        // 3. Info sukses ke Admin
                        \Filament\Notifications\Notification::make()
                            ->title('Pesan Terkirim!')
                            ->body('Notifikasi telah dikirim ke perangkat ' . $record->name . ' dan terekam di sistem.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
