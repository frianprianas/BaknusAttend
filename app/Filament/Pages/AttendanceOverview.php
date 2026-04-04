<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\RecentGuruAttendanceWidget;
use App\Filament\Widgets\RecentStudentAttendanceWidget;

class AttendanceOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Presensi / Absensi';
    protected static ?string $title = 'Rekapitulasi Presensi / Absensi';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.attendance-overview';

    public static function canAccess(): bool
    {
        return auth()->user() && auth()->user()->role === 'Admin';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentStudentAttendanceWidget::class,
            RecentGuruAttendanceWidget::class,
        ];
    }
}
