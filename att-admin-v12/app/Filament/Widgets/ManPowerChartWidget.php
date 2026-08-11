<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;

class ManPowerChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Tren Man Power per Bulan';
    
    public ?string $year = null;
    public ?string $company_id = null;
    public ?string $branch_id = null;

    protected function getData(): array
    {
        $year = $this->year ?: date('Y');
        $startOfYear = "{$year}-01-01";
        $endOfYear = "{$year}-12-31";
        
        $companies = Company::when($this->company_id, function ($q) {
            return $q->where('id', $this->company_id);
        })->get();
        
        $companyIds = $companies->pluck('id')->toArray();

        $employees = Employee::select('id', 'company_id', 'join_date', 'resign_date', 'employment_status')
            ->whereIn('company_id', $companyIds)
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->where(function($q) use ($endOfYear) {
                $q->whereNull('join_date')
                  ->orWhere('join_date', '<=', $endOfYear);
            })
            ->where(function($q) use ($startOfYear) {
                $q->whereNull('resign_date')
                  ->orWhere('resign_date', '>=', $startOfYear);
            })
            ->get();

        $datasets = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        $colors = [
            'rgb(59, 130, 246)', // blue
            'rgb(16, 185, 129)', // green
            'rgb(245, 158, 11)', // yellow
            'rgb(239, 68, 68)', // red
            'rgb(139, 92, 246)', // purple
        ];

        foreach ($companies as $index => $company) {
            $monthlyData = [];
            
            $companyEmployees = $employees->where('company_id', $company->id);
            
            for ($month = 1; $month <= 12; $month++) {
                $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                
                $count = 0;
                foreach($companyEmployees as $emp) {
                    $joinedBeforeEndOfMonth = is_null($emp->join_date) || $emp->join_date <= $endOfMonth;
                    $resignedAfterEndOfMonth = is_null($emp->resign_date) || $emp->resign_date > $endOfMonth;
                    $statusOk = $emp->employment_status !== 'resigned' || ($emp->employment_status === 'resigned' && !is_null($emp->resign_date) && $emp->resign_date > $endOfMonth);
                    
                    if ($joinedBeforeEndOfMonth && $resignedAfterEndOfMonth && $statusOk) {
                        $count++;
                    }
                }

                $monthlyData[] = $count;
            }

            $color = $colors[$index % count($colors)];

            $datasets[] = [
                'label' => $company->name,
                'data' => $monthlyData,
                'borderColor' => $color,
                'backgroundColor' => 'transparent',
                'tension' => 0.4,
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
