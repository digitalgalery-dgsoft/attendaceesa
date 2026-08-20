<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ManPowerChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Tren Manpower per Prinsiple';
    
    public ?string $year = null;
    public ?string $principal_id = null;
    public ?string $branch_id = null;

    protected function getData(): array
    {
        @ini_set('memory_limit', '512M');

        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');
        $selectedYear = (int)($this->year ?: date('Y'));

        $maxValidMonth = 12;
        if ($selectedYear === $currentYear) {
            $maxValidMonth = $currentMonth;
        } elseif ($selectedYear > $currentYear) {
            $maxValidMonth = 0;
        }

        $startOfYear = "{$selectedYear}-01-01";
        $endOfYear = "{$selectedYear}-12-31";

        $principalsQuery = DB::table('principals')->orderBy('name');
        if (!empty($this->principal_id)) {
            $principalsQuery->where('id', $this->principal_id);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $principalsQuery->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
        }
        $principals = $principalsQuery->select('id', 'name')->get();

        if ($principals->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            ];
        }

        $principalIds = $principals->pluck('id')->toArray();

        $employeesQuery = DB::table('employees')
            ->whereIn('principal_id', $principalIds)
            ->whereNull('deleted_at');

        if (!empty($this->branch_id)) {
            $employeesQuery->where('branch_id', $this->branch_id);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $employeesQuery->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
        }

        $employees = $employeesQuery
            ->where(function ($q) use ($endOfYear) {
                $q->whereNull('join_date')
                  ->orWhere('join_date', '<=', $endOfYear);
            })
            ->where(function ($q) use ($startOfYear) {
                $q->whereNull('resign_date')
                  ->orWhere('resign_date', '>=', $startOfYear);
            })
            ->select([
                'id',
                'principal_id',
                DB::raw("SUBSTRING(CAST(join_date AS VARCHAR), 1, 10) as join_date_str"),
                DB::raw("SUBSTRING(CAST(resign_date AS VARCHAR), 1, 10) as resign_date_str"),
                'employment_status',
            ])
            ->get();

        $endOfMonths = [];
        for ($month = 1; $month <= 12; $month++) {
            $endOfMonths[$month] = Carbon::createFromDate($selectedYear, $month, 1)->endOfMonth()->toDateString();
        }

        $employeesByPrincipal = $employees->groupBy('principal_id');
        $datasets = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $palette = [
            ['border' => '#4f46e5', 'bg' => 'rgba(79, 70, 229, 0.08)'],
            ['border' => '#059669', 'bg' => 'rgba(5, 150, 105, 0.08)'],
            ['border' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.08)'],
            ['border' => '#dc2626', 'bg' => 'rgba(220, 38, 38, 0.08)'],
            ['border' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.08)'],
            ['border' => '#0891b2', 'bg' => 'rgba(8, 145, 178, 0.08)'],
            ['border' => '#ea580c', 'bg' => 'rgba(234, 88, 12, 0.08)'],
        ];

        foreach ($principals as $index => $principal) {
            $monthlyData = [];
            $principalEmps = $employeesByPrincipal->get($principal->id, collect());

            for ($month = 1; $month <= 12; $month++) {
                if ($month > $maxValidMonth) {
                    $monthlyData[] = null;
                    continue;
                }

                $endOfMonth = $endOfMonths[$month];
                $count = 0;

                foreach ($principalEmps as $emp) {
                    $joinDate = $emp->join_date_str;
                    $resignDate = $emp->resign_date_str;

                    $joinedBefore = empty($joinDate) || $joinDate <= $endOfMonth;
                    $resignedAfter = empty($resignDate) || $resignDate > $endOfMonth;
                    $statusOk = $emp->employment_status !== 'resigned' || (!empty($resignDate) && $resignDate > $endOfMonth);

                    if ($joinedBefore && $resignedAfter && $statusOk) {
                        $count++;
                    }
                }

                $monthlyData[] = $count;
            }

            $theme = $palette[$index % count($palette)];

            $datasets[] = [
                'label' => $principal->name,
                'data' => $monthlyData,
                'borderColor' => $theme['border'],
                'backgroundColor' => $theme['bg'],
                'fill' => true,
                'tension' => 0.35,
                'borderWidth' => 2.5,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'spanGaps' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
