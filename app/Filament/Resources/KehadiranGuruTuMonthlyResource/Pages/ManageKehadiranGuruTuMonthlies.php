<?php

namespace App\Filament\Resources\KehadiranGuruTuMonthlyResource\Pages;

use App\Filament\Resources\KehadiranGuruTuMonthlyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKehadiranGuruTuMonthlies extends ManageRecords
{
    protected static string $resource = KehadiranGuruTuMonthlyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printReport')
                ->label('Cetak Laporan (PDF)')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(function() {
                    $formDate = $this->tableFilters ?? [];
                    $bulan = $formDate['bulan']['value'] ?? request()->query('tableFilters')['bulan']['value'] ?? now()->format('m');
                    $tahun = $formDate['tahun']['value'] ?? request()->query('tableFilters')['tahun']['value'] ?? now()->format('Y');
                    $role = $formDate['role']['value'] ?? request()->query('tableFilters')['role']['value'] ?? 'all';

                    return route('admin.rekap-guru-tu.print', [
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'role'  => $role,
                    ]);
                })
                ->openUrlInNewTab(),

            Actions\Action::make('exportCsv')
                ->label('Export CSV / Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(function() {
                    $formDate = $this->tableFilters ?? [];
                    $bulan = $formDate['bulan']['value'] ?? request()->query('tableFilters')['bulan']['value'] ?? now()->format('m');
                    $tahun = $formDate['tahun']['value'] ?? request()->query('tableFilters')['tahun']['value'] ?? now()->format('Y');
                    $role = $formDate['role']['value'] ?? request()->query('tableFilters')['role']['value'] ?? 'all';

                    return route('admin.rekap-guru-tu.export-csv', [
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'role'  => $role,
                    ]);
                })
                ->openUrlInNewTab(),
        ];
    }
}
