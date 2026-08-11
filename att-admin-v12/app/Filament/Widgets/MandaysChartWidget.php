<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employee;
use App\Models\WorkTarget;
use App\Models\Attendance;

class MandaysChartWidget extends ChartWidget
{
    protected ?string $heading = 'Target vs Aktual HK (Top 10)';
    
    public ?string $month = null;
    public ?string $year = null;
    public ?string $branch_id = null;
    public ?string $principal_id = null;

    protected function getData(): array
    {
        $month = $this->month ?: date('m');
        $year = $this->year ?: date('Y');
        $monthYear = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        $employees = Employee::with(['branch', 'principal'])
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->when($this->principal_id, function ($q) {
                return $q->where('principal_id', $this->principal_id);
            })
            ->where('is_active', true)
            ->limit(10) // Limit to top 10 for chart readability
            ->get();

        $labels = [];
        $targetData = [];
        $aktualData = [];

        foreach ($employees as $emp) {
            $labels[] = substr($emp->full_name, 0, 15) . (strlen($emp->full_name) > 15 ? '...' : '');

            $target = WorkTarget::where('employee_id', $emp->id)
                ->where('month_year', $monthYear)
                ->first();
                
            $targetHK = $target ? $target->target_hk : 0;

            $aktualHK = Attendance::where('employee_id', $emp->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->whereIn('status', ['present', 'late', 'permit'])
                ->count();

            $targetData[] = $targetHK;
            $aktualData[] = $aktualHK;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Target HK',
                    'data' => $targetData,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.6)',
                    'borderColor' => 'rgb(156, 163, 175)',
                ],
                [
                    'label' => 'Aktual HK',
                    'data' => $aktualData,
                    'backgroundColor' => 'rgba(14, 165, 233, 0.8)',
                    'borderColor' => 'rgb(14, 165, 233)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
