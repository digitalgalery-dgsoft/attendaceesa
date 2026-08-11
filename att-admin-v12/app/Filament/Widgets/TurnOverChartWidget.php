<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employee;
use Carbon\Carbon;

class TurnOverChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Tren Masuk & Keluar';
    
    public ?string $year = null;
    public ?string $company_id = null;

    protected function getData(): array
    {
        $year = $this->year ?: date('Y');
        $datasets = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        $employees = Employee::select('id', 'company_id', 'join_date', 'resign_date', 'employment_status', 'updated_at')
            ->when($this->company_id, function ($q) {
                return $q->where('company_id', $this->company_id);
            })
            ->where(function($q) use ($year) {
                $q->whereYear('join_date', $year)
                  ->orWhereYear('resign_date', $year)
                  ->orWhere(function($sq) use ($year) {
                      $sq->where('employment_status', 'resigned')
                         ->whereYear('updated_at', $year)
                         ->whereNull('resign_date');
                  });
            })
            ->get();

        $joinedData = [];
        $resignedData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $joined = $employees->filter(function($emp) use ($year, $monthStr) {
                return $emp->join_date && substr($emp->join_date, 0, 7) === "{$year}-{$monthStr}";
            })->count();

            $resigned = $employees->filter(function($emp) use ($year, $monthStr) {
                if ($emp->resign_date && substr($emp->resign_date, 0, 7) === "{$year}-{$monthStr}") {
                    return true;
                }
                if (!$emp->resign_date && $emp->employment_status === 'resigned' && $emp->updated_at && $emp->updated_at->format('Y-m') === "{$year}-{$monthStr}") {
                    return true;
                }
                return false;
            })->count();

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
