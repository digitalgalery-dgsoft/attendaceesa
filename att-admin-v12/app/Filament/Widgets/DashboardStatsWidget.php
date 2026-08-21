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
        $employeeQuery = Employee::query()->where('is_active', true);
        $inactiveQuery = Employee::query()->where('is_active', false);
        $attendanceTodayQuery = Attendance::where('attendance_date', Carbon::today()->toDateString());
        $principalQuery = \App\Models\Principal::query();
        $branchQuery = \App\Models\Branch::query();

        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            if (auth()->user()->hasBranchRestriction()) {
                $accessibleBranches = auth()->user()->getAccessibleBranchIds();
                $employeeQuery->whereIn('branch_id', $accessibleBranches);
                $inactiveQuery->whereIn('branch_id', $accessibleBranches);
                $attendanceTodayQuery->whereHas('employee', fn($q) => $q->whereIn('branch_id', $accessibleBranches));
                $branchQuery->whereIn('id', $accessibleBranches);
            }
            if (auth()->user()->hasPrincipalRestriction()) {
                $accessiblePrincipals = auth()->user()->getAccessiblePrincipalIds();
                $employeeQuery->whereIn('principal_id', $accessiblePrincipals);
                $inactiveQuery->whereIn('principal_id', $accessiblePrincipals);
                $attendanceTodayQuery->whereHas('employee', fn($q) => $q->whereIn('principal_id', $accessiblePrincipals));
                $principalQuery->whereIn('id', $accessiblePrincipals);
            }
        }

        $activeEmployees = $employeeQuery->count();
        $inactiveEmployees = $inactiveQuery->count();
        $presentToday = $attendanceTodayQuery->count();
        $totalPrincipals = $principalQuery->count();
        $totalAreas = $branchQuery->count();

        return [
            Stat::make('Total Employees', number_format($activeEmployees))
                ->description(number_format($inactiveEmployees) . ' Resign/Non-Aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Present Today', number_format($presentToday))
                ->description('Total check-in hari ini')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),
            Stat::make('Total Principals', number_format($totalPrincipals))
                ->description('Jumlah principal/klien')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),
            Stat::make('Total Areas', number_format($totalAreas))
                ->description('Jumlah area/cabang terdaftar')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('warning'),
        ];
    }
}
