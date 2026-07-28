<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\SalesPipeline;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class SalesStatsWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        $employee = $user->employee;
        $query = SalesPipeline::query();

        // Apply access control similar to dashboard
        if ($employee && !(method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            // Get subordinates
            $subordinateIds = Employee::where('supervisor_id', $employee->id)->pluck('id');
            // If they are supervisor, show stats for subordinates and themselves
            if ($subordinateIds->count() > 0) {
                $subordinateIds->push($employee->id);
                $query->whereIn('employee_id', $subordinateIds);
            } else {
                $query->where('employee_id', $employee->id);
            }
        }

        $totalRevenue = (clone $query)->whereIn('stage', ['negotiation', 'prospecting', 'closed_won'])->sum('expected_revenue');
        $wonRevenue = (clone $query)->where('stage', 'closed_won')->sum('expected_revenue');
        $activePipelines = (clone $query)->whereIn('stage', ['prospecting', 'negotiation'])->count();
        $totalPipelines = (clone $query)->count();
        
        $winRate = $totalPipelines > 0 ? round(((clone $query)->where('stage', 'closed_won')->count() / $totalPipelines) * 100, 1) : 0;

        return [
            Stat::make('Total Expected Revenue', 'Rp ' . number_format($totalRevenue, 2, ',', '.'))
                ->description('Dari semua pipeline aktif & won')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Win Rate', $winRate . '%')
                ->description('Persentase closing berhasil')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($winRate >= 50 ? 'success' : 'warning'),
            Stat::make('Active Pipelines', $activePipelines)
                ->description('Prospek & Negosiasi')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
