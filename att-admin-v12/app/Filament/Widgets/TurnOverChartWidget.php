<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TurnOverChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Tren Karyawan Masuk vs Keluar';
    
    public ?string $year = null;
    public ?string $principal_id = null;

    protected function getData(): array
    {
        @ini_set('memory_limit', '512M');

        $year = $this->year ?: date('Y');
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $employees = DB::table('employees')
            ->whereNull('deleted_at')
            ->when(!empty($this->principal_id), function ($q) {
                return $q->where('principal_id', $this->principal_id);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('join_date', [$startDate, $endDate])
                  ->orWhereBetween('resign_date', [$startDate, $endDate])
                  ->orWhere(function ($sq) use ($startDate, $endDate) {
                      $sq->where('employment_status', 'resigned')
                         ->whereBetween('updated_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                         ->whereNull('resign_date');
                  });
            })
            ->select([
                'id',
                'principal_id',
                DB::raw("SUBSTRING(CAST(join_date AS VARCHAR), 1, 10) as join_date_str"),
                DB::raw("SUBSTRING(CAST(resign_date AS VARCHAR), 1, 10) as resign_date_str"),
                DB::raw("SUBSTRING(CAST(updated_at AS VARCHAR), 1, 10) as updated_at_str"),
                'employment_status',
            ])
            ->get();

        $joinedData = [];
        $resignedData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $prefix = "{$year}-{$monthStr}";

            $joined = 0;
            $resigned = 0;

            foreach ($employees as $emp) {
                if (!empty($emp->join_date_str) && str_starts_with($emp->join_date_str, $prefix)) {
                    $joined++;
                }

                if (!empty($emp->resign_date_str) && str_starts_with($emp->resign_date_str, $prefix)) {
                    $resigned++;
                } elseif (empty($emp->resign_date_str) && $emp->employment_status === 'resigned' && !empty($emp->updated_at_str) && str_starts_with($emp->updated_at_str, $prefix)) {
                    $resigned++;
                }
            }

            $joinedData[] = $joined;
            $resignedData[] = $resigned;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Karyawan Masuk (Join)',
                    'data' => $joinedData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.85)',
                    'borderColor' => '#059669',
                    'borderRadius' => 6,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Karyawan Keluar (Resign)',
                    'data' => $resignedData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.85)',
                    'borderColor' => '#dc2626',
                    'borderRadius' => 6,
                    'borderWidth' => 1,
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
