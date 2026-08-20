<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Attendance Overview';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today()->endOfDay();

        $attQuery = Attendance::query();
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            $attQuery = \App\Traits\ScopesUserData::applyUserAccessScope($attQuery);
        }

        $attendances = $attQuery->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as aggregate'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->pluck('aggregate', 'date');
            
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('D'); // Mon, Tue, etc
            $dateString = $date->toDateString();
            $data[] = $attendances->has($dateString) ? $attendances[$dateString] : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Check-ins',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
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
