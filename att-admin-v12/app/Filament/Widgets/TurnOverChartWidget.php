<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employee;
use Carbon\Carbon;

class TurnOverChartWidget extends ChartWidget
{
    protected ?string $heading = 'Tren Masuk & Keluar';
    
    public ?string $year = null;
    public ?string $company_id = null;

    protected function getData(): array
    {
        $year = $this->year ?: date('Y');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        $joinedData = [];
        $resignedData = [];

        for ($month = 1; $month <= 12; $month++) {
            $joined = Employee::when($this->company_id, function ($q) {
                    return $q->where('company_id', $this->company_id);
                })
                ->whereYear('join_date', $year)
                ->whereMonth('join_date', $month)
                ->count();

            $resigned = Employee::when($this->company_id, function ($q) {
                    return $q->where('company_id', $this->company_id);
                })
                ->where(function($q) use ($year, $month) {
                    $q->where(function($sq) use ($year, $month) {
                        $sq->whereYear('resign_date', $year)
                           ->whereMonth('resign_date', $month);
                    })->orWhere(function($sq) use ($year, $month) {
                        $sq->where('employment_status', 'resigned')
                           ->whereYear('updated_at', $year)
                           ->whereMonth('updated_at', $month)
                           ->whereNull('resign_date');
                    });
                })
                ->count();

            $joinedData[] = $joined;
            $resignedData[] = $resigned;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Karyawan Masuk',
                    'data' => $joinedData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
                [
                    'label' => 'Karyawan Keluar',
                    'data' => $resignedData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
