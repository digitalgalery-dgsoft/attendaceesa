<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;

class ManPowerChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Man Power per Bulan';
    
    public ?string $year = null;
    public ?string $company_id = null;
    public ?string $branch_id = null;

    protected function getData(): array
    {
        $year = $this->year ?: date('Y');
        
        $companies = Company::when($this->company_id, function ($q) {
            return $q->where('id', $this->company_id);
        })->get();

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
            
            for ($month = 1; $month <= 12; $month++) {
                $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                
                $count = Employee::where('company_id', $company->id)
                    ->when($this->branch_id, function ($q) {
                        return $q->where('branch_id', $this->branch_id);
                    })
                    ->where(function ($q) use ($endOfMonth) {
                        $q->whereNull('join_date')
                          ->orWhere('join_date', '<=', $endOfMonth->toDateString());
                    })
                    ->where(function ($q) use ($endOfMonth) {
                        $q->whereNull('resign_date')
                          ->orWhere('resign_date', '>', $endOfMonth->toDateString());
                    })
                    ->where(function($q) use ($endOfMonth) {
                         $q->where('employment_status', '!=', 'resigned')
                           ->orWhere(function($sq) use ($endOfMonth) {
                               $sq->where('employment_status', 'resigned')->whereNotNull('resign_date')->where('resign_date', '>', $endOfMonth->toDateString());
                           });
                    })
                    ->count();

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
