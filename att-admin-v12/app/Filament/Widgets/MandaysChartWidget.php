<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employee;
use App\Models\WorkTarget;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class MandaysChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Target vs Aktual HK (Top 10)';
    
    public ?string $month = null;
    public ?string $year = null;
    public ?string $branch_id = null;
    public ?string $company_id = null;

    protected function getData(): array
    {
        $month = $this->month ?: date('m');
        $year = $this->year ?: date('Y');
        $monthYear = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        $employees = Employee::with(['branch', 'company'])
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->when($this->company_id, function ($q) {
                return $q->where('company_id', $this->company_id);
            })
            ->where('is_active', true)
            ->get();
            
        $employeeIds = $employees->pluck('id')->toArray();
        
        $targets = WorkTarget::whereIn('employee_id', $employeeIds)
            ->where('month_year', $monthYear)
            ->pluck('target_hk', 'employee_id');

        $attendances = Attendance::select('employee_id', DB::raw('count(*) as total'))
            ->whereIn('employee_id', $employeeIds)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->whereIn('status', ['present', 'late', 'permit'])
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $chartData = [];

        foreach ($employees as $emp) {
            $targetHK = $targets[$emp->id] ?? 0;
            $aktualHK = $attendances[$emp->id] ?? 0;

            if ($targetHK > 0 || $aktualHK > 0) {
                $chartData[] = [
                    'name' => substr($emp->full_name, 0, 15) . (strlen($emp->full_name) > 15 ? '...' : ''),
                    'target' => $targetHK,
                    'aktual' => $aktualHK,
                ];
            }
        }
        
        // Sort by aktual desc, target desc
        usort($chartData, function($a, $b) {
            if ($a['aktual'] === $b['aktual']) {
                return $b['target'] <=> $a['target'];
            }
            return $b['aktual'] <=> $a['aktual'];
        });
        
        $chartData = array_slice($chartData, 0, 10);

        return [
            'datasets' => [
                [
                    'label' => 'Target HK',
                    'data' => array_column($chartData, 'target'),
                    'backgroundColor' => 'rgb(59, 130, 246)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Aktual HK',
                    'data' => array_column($chartData, 'aktual'),
                    'backgroundColor' => 'rgb(16, 185, 129)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
            ],
            'labels' => array_column($chartData, 'name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
