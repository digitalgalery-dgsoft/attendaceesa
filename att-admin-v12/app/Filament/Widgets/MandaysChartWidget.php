<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MandaysChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Target vs Aktual Mandays (Top 10)';
    
    public ?string $month = null;
    public ?string $year = null;
    public ?string $branch_id = null;
    public ?string $principal_id = null;

    protected function getData(): array
    {
        @ini_set('memory_limit', '512M');

        $month = str_pad($this->month ?: date('m'), 2, '0', STR_PAD_LEFT);
        $year = $this->year ?: date('Y');
        $monthYear = "{$year}-{$month}";

        $startDateStr = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfMonth()->toDateString();
        $endDateStr = Carbon::createFromDate((int)$year, (int)$month, 1)->endOfMonth()->toDateString();

        $employees = DB::table('employees')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when(!empty($this->branch_id), function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->when(!empty($this->principal_id), function ($q) {
                return $q->where('principal_id', $this->principal_id);
            })
            ->select(['id', 'full_name'])
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();

        if (empty($employeeIds)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $targets = DB::table('work_targets')
            ->whereIn('employee_id', $employeeIds)
            ->where('month_year', $monthYear)
            ->pluck('target_hk', 'employee_id');

        $attendances = DB::table('attendances')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$startDateStr, $endDateStr])
            ->whereIn('status', ['present', 'late', 'permit'])
            ->select('employee_id', DB::raw('count(*) as total'))
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $chartData = [];

        foreach ($employees as $emp) {
            $targetHK = (int)($targets[$emp->id] ?? 0);
            $aktualHK = (int)($attendances[$emp->id] ?? 0);

            if ($targetHK > 0 || $aktualHK > 0) {
                $name = $emp->full_name;
                if (strlen($name) > 16) {
                    $name = substr($name, 0, 14) . '..';
                }

                $chartData[] = [
                    'name' => $name,
                    'target' => $targetHK,
                    'aktual' => $aktualHK,
                ];
            }
        }

        // Sort by aktual desc, then target desc
        usort($chartData, function ($a, $b) {
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
                    'backgroundColor' => 'rgba(99, 102, 241, 0.85)',
                    'borderColor' => '#4f46e5',
                    'borderRadius' => 6,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Aktual HK',
                    'data' => array_column($chartData, 'aktual'),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.85)',
                    'borderColor' => '#059669',
                    'borderRadius' => 6,
                    'borderWidth' => 1,
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
