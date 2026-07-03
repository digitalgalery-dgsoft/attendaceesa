<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Employee;
use App\Models\Company;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Employees', Employee::count())
                ->description('Jumlah karyawan terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Present Today', Attendance::whereDate('created_at', Carbon::today())->count())
                ->description('Total check-in hari ini')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),
            Stat::make('Total Companies', Company::count())
                ->description('Jumlah perusahaan/klien')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),
            Stat::make('Total Areas', \App\Models\Branch::count())
                ->description('Jumlah area/cabang terdaftar')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('warning'),
        ];
    }
}
