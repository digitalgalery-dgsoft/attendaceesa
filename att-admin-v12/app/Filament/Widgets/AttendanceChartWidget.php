<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Attendance;
use App\Models\Principal;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Attendance Overview per Prinsiple';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '360px';

    protected string $view = 'filament.widgets.attendance-chart-widget';

    public ?string $principal_id = '';
    public ?string $branch_id = '';

    public function updatedPrincipalId(): void
    {
        $this->cachedData = null;
        $this->updateChartData();
    }

    public function updatedBranchId(): void
    {
        $this->cachedData = null;
        $this->updateChartData();
    }

    public function getPrincipalOptions(): array
    {
        $query = Principal::where('is_active', true)
            ->whereHas('activeEmployees')
            ->orderBy('name');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
        }

        return ['' => 'Semua Prinsiple'] + $query->pluck('name', 'id')->toArray();
    }

    public function getBranchOptions(): array
    {
        $query = Branch::orderBy('name');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
        }

        return ['' => 'Semua Area'] + $query->pluck('name', 'id')->toArray();
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $days = 7;
        $labels = [];
        $dateStrings = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->translatedFormat('D, d M');
            $dateStrings[] = $date->toDateString();
        }

        // Query Prinsiple yang aktif dan memiliki karyawan
        $principalsQuery = Principal::where('is_active', true)
            ->whereHas('activeEmployees')
            ->orderBy('name');

        if (!empty($this->principal_id)) {
            $principalsQuery->where('id', $this->principal_id);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $principalsQuery->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
        }

        $principals = $principalsQuery->get();

        if ($principals->isEmpty()) {
            $fallbackQuery = Principal::where('is_active', true);
            if (!empty($this->principal_id)) {
                $fallbackQuery->where('id', $this->principal_id);
            }
            $principals = $fallbackQuery->limit(10)->get();
        }

        // Palette Warna Modern & Berbeda untuk Masing-Masing Prinsiple
        $palette = [
            ['border' => 'rgb(59, 130, 246)', 'bg' => 'rgba(59, 130, 246, 0.08)'],   // Blue
            ['border' => 'rgb(16, 185, 129)', 'bg' => 'rgba(16, 185, 129, 0.08)'], // Emerald
            ['border' => 'rgb(245, 158, 11)', 'bg' => 'rgba(245, 158, 11, 0.08)'], // Amber
            ['border' => 'rgb(139, 92, 246)', 'bg' => 'rgba(139, 92, 246, 0.08)'], // Purple
            ['border' => 'rgb(244, 63, 94)',  'bg' => 'rgba(244, 63, 94, 0.08)'],  // Rose
            ['border' => 'rgb(6, 182, 212)',  'bg' => 'rgba(6, 182, 212, 0.08)'],  // Cyan
            ['border' => 'rgb(249, 115, 22)', 'bg' => 'rgba(249, 115, 22, 0.08)'], // Orange
            ['border' => 'rgb(236, 72, 153)', 'bg' => 'rgba(236, 72, 153, 0.08)'], // Pink
            ['border' => 'rgb(20, 184, 166)', 'bg' => 'rgba(20, 184, 166, 0.08)'], // Teal
            ['border' => 'rgb(99, 102, 241)', 'bg' => 'rgba(99, 102, 241, 0.08)'], // Indigo
            ['border' => 'rgb(168, 85, 247)', 'bg' => 'rgba(168, 85, 247, 0.08)'], // Violet
            ['border' => 'rgb(34, 197, 94)',  'bg' => 'rgba(34, 197, 94, 0.08)'],  // Green
        ];

        // Ambil Data Absensi per Principal dan Tanggal
        $attQuery = DB::table('attendances')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->select(
                'employees.principal_id',
                'attendances.attendance_date as date',
                DB::raw('count(*) as total')
            )
            ->whereBetween('attendances.attendance_date', [$dateStrings[0], end($dateStrings)])
            ->whereIn('employees.principal_id', $principals->pluck('id'))
            ->whereNull('employees.deleted_at');

        if (!empty($this->branch_id)) {
            $attQuery->where('employees.branch_id', $this->branch_id);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $attQuery->whereIn('employees.branch_id', auth()->user()->getAccessibleBranchIds());
        }

        $rawAttendances = $attQuery->groupBy('employees.principal_id', 'attendances.attendance_date')->get();

        $attendanceMap = [];
        foreach ($rawAttendances as $row) {
            $attendanceMap[$row->principal_id][$row->date] = (int)$row->total;
        }

        $datasets = [];
        foreach ($principals as $index => $principal) {
            $counts = [];
            foreach ($dateStrings as $dateStr) {
                $counts[] = $attendanceMap[$principal->id][$dateStr] ?? 0;
            }

            $theme = $palette[$index % count($palette)];

            $datasets[] = [
                'label' => $principal->name,
                'data' => $counts,
                'borderColor' => $theme['border'],
                'backgroundColor' => $theme['bg'],
                'fill' => true,
                'tension' => 0.35,
                'borderWidth' => 2.5,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        if (empty($datasets)) {
            $overallCounts = [];
            $overallAttQuery = Attendance::whereBetween('attendance_date', [$dateStrings[0], end($dateStrings)]);
            if (!empty($this->branch_id)) {
                $overallAttQuery->whereHas('employee', fn($q) => $q->where('branch_id', $this->branch_id));
            }
            $overallAtt = $overallAttQuery->groupBy('attendance_date')
                ->select('attendance_date', DB::raw('count(*) as total'))
                ->pluck('total', 'attendance_date');

            foreach ($dateStrings as $dateStr) {
                $overallCounts[] = $overallAtt[$dateStr] ?? 0;
            }

            $datasets[] = [
                'label' => 'Total Check-in',
                'data' => $overallCounts,
                'borderColor' => 'rgb(59, 130, 246)',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'fill' => true,
                'tension' => 0.35,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
