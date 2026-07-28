<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SalesPipeline;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class SalesPipelineChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Tahapan Pipeline';
    
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = Auth::user();
        if (!$user) return ['datasets' => [], 'labels' => []];

        $employee = $user->employee;
        $query = SalesPipeline::query();

        // Apply access control similar to dashboard
        if ($employee && !(method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            $subordinateIds = Employee::where('supervisor_id', $employee->id)->pluck('id');
            if ($subordinateIds->count() > 0) {
                $subordinateIds->push($employee->id);
                $query->whereIn('employee_id', $subordinateIds);
            } else {
                $query->where('employee_id', $employee->id);
            }
        }

        $data = [
            'prospecting' => (clone $query)->where('stage', 'prospecting')->count(),
            'negotiation' => (clone $query)->where('stage', 'negotiation')->count(),
            'closed_won' => (clone $query)->where('stage', 'closed_won')->count(),
            'closed_lost' => (clone $query)->where('stage', 'closed_lost')->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pipeline',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#9ca3af', // gray
                        '#f59e0b', // warning
                        '#10b981', // success
                        '#ef4444', // danger
                    ],
                ],
            ],
            'labels' => ['Prospecting', 'Negotiation', 'Closed Won', 'Closed Lost'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
