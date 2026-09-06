<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use App\Models\Employee;
use App\Models\OdooSyncLog;
use App\Models\Principal;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActiveEmployeesHourlyChartWidget extends ChartWidget implements HasActions, HasSchemas
{
    use HasFiltersSchema;
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected ?string $heading = 'Perubahan Employee Aktif Tiap Jam (Odoo Sync)';
    protected ?string $description = 'Tren pergerakan jumlah karyawan aktif, penambahan karyawan baru, dan mutasi resign hasil sinkronisasi Odoo';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '350px';
    protected ?string $pollingInterval = '30s';

    protected string $view = 'filament.widgets.active-employees-hourly-chart-widget';

    public function mount(): void
    {
        parent::mount();
        $this->mountHasFiltersSchema();
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('time_range')
                    ->label('Rentang Waktu')
                    ->options([
                        '12h'   => '12 Jam Terakhir (Default)',
                        '24h'   => '24 Jam Terakhir',
                        'today' => 'Hari Ini (Sejak 00:00)',
                        '7d'    => '7 Hari Terakhir',
                        '30d'   => '30 Hari Terakhir',
                    ])
                    ->default('12h')
                    ->selectablePlaceholder(false)
                    ->live(),
                Select::make('principal_id')
                    ->label('Filter Prinsiple')
                    ->placeholder('Semua Prinsiple')
                    ->options($this->getPrincipalOptions())
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('branch_id')
                    ->label('Filter Area / Cabang')
                    ->placeholder('Semua Area')
                    ->options($this->getBranchOptions())
                    ->searchable()
                    ->preload()
                    ->live(),
            ]);
    }

    public function getFiltersTriggerAction(): Action
    {
        $hasActiveFilter = (!empty($this->filters['time_range']) && $this->filters['time_range'] !== '12h')
            || !empty($this->filters['principal_id'])
            || !empty($this->filters['branch_id']);

        return Action::make('filter')
            ->label($hasActiveFilter ? 'Filter: Aktif' : 'Filter Jam & Area')
            ->icon('heroicon-m-funnel')
            ->color($hasActiveFilter ? 'primary' : 'gray')
            ->badge($hasActiveFilter ? 'Aktif' : null)
            ->badgeColor('primary')
            ->button()
            ->size('sm');
    }

    public function resetFiltersForm(): void
    {
        $this->filters = ['time_range' => '12h'];
        $this->resetFiltersSchema();
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

        return $query->pluck('name', 'id')->toArray();
    }

    public function getBranchOptions(): array
    {
        $query = Branch::orderBy('name');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
        }

        return $query->pluck('name', 'id')->toArray();
    }

    public function getTimeSlots(): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $range = $this->filters['time_range'] ?? '12h';
        $slots = [];

        if ($range === '24h') {
            $totalHours = 24;
            $interval = 'hour';
        } elseif ($range === 'today') {
            $totalHours = (int)$now->format('H') + 1;
            $interval = 'hour';
        } elseif ($range === '7d') {
            $totalDays = 7;
            $interval = 'day';
        } elseif ($range === '30d') {
            $totalDays = 30;
            $interval = 'day';
        } else {
            $totalHours = 12;
            $interval = 'hour';
        }

        if ($interval === 'day') {
            for ($i = $totalDays - 1; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $start = $date->copy()->startOfDay();
                $end   = $date->copy()->endOfDay();

                $slots[] = [
                    'start'      => $start,
                    'end'        => $end,
                    'label'      => $date->translatedFormat('D, d M'),
                    'full_label' => $date->translatedFormat('l, d F Y'),
                ];
            }
        } else {
            for ($i = $totalHours - 1; $i >= 0; $i--) {
                $time = $now->copy()->subHours($i);
                $start = $time->copy()->startOfHour();
                $end   = $time->copy()->endOfHour();

                $slots[] = [
                    'start'      => $start,
                    'end'        => $end,
                    'label'      => $start->format('H:00'),
                    'full_label' => $start->translatedFormat('D, d M H:00') . ' - ' . $end->format('H:59'),
                ];
            }
        }

        return $slots;
    }

    public function computeHourlyData(): array
    {
        $slots = $this->getTimeSlots();
        if (empty($slots)) {
            return [
                'slots'   => [],
                'active'  => [],
                'new'     => [],
                'resign'  => [],
                'summary' => [
                    'totalActive'    => 0,
                    'totalInactive'  => 0,
                    'totalNew'       => 0,
                    'totalResigned'  => 0,
                    'netChange'      => 0,
                    'latestSyncTime' => null,
                ],
            ];
        }

        $windowStart = $slots[0]['start'];
        $windowEnd   = end($slots)['end'];

        $principalId = $this->filters['principal_id'] ?? null;
        $branchId    = $this->filters['branch_id'] ?? null;

        // Base query for current active & inactive employees in database
        $baseActiveQuery   = Employee::query()->where('is_active', true);
        $baseInactiveQuery = Employee::query()->where('is_active', false);

        if (!empty($principalId)) {
            $baseActiveQuery->where('principal_id', $principalId);
            $baseInactiveQuery->where('principal_id', $principalId);
        }
        if (!empty($branchId)) {
            $baseActiveQuery->where('branch_id', $branchId);
            $baseInactiveQuery->where('branch_id', $branchId);
        }
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            if (auth()->user()->hasBranchRestriction()) {
                $baseActiveQuery->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
                $baseInactiveQuery->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
            }
            if (auth()->user()->hasPrincipalRestriction()) {
                $baseActiveQuery->whereIn('principal_id', auth()->user()->getAccessiblePrincipalIds());
                $baseInactiveQuery->whereIn('principal_id', auth()->user()->getAccessiblePrincipalIds());
            }
        }

        $currentTotalActive   = $baseActiveQuery->count();
        $currentTotalInactive = $baseInactiveQuery->count();

        // Query new employees created via Odoo Sync in this window
        $newEmpQuery = Employee::query()
            ->where('created_at', '>=', $windowStart->toDateTimeString())
            ->where('created_at', '<=', $windowEnd->toDateTimeString());

        if (!empty($principalId)) {
            $newEmpQuery->where('principal_id', $principalId);
        }
        if (!empty($branchId)) {
            $newEmpQuery->where('branch_id', $branchId);
        }
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            if (auth()->user()->hasBranchRestriction()) {
                $newEmpQuery->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
            }
            if (auth()->user()->hasPrincipalRestriction()) {
                $newEmpQuery->whereIn('principal_id', auth()->user()->getAccessiblePrincipalIds());
            }
        }
        $newEmployees = $newEmpQuery->get(['id', 'created_at']);

        // Query employees who resigned / deactivated in this window
        $resignedEmpQuery = Employee::query()
            ->where('is_active', false)
            ->where(function ($q) use ($windowStart, $windowEnd) {
                $q->whereBetween('updated_at', [$windowStart->toDateTimeString(), $windowEnd->toDateTimeString()])
                  ->orWhereBetween('resign_date', [$windowStart->toDateString(), $windowEnd->toDateString()]);
            });

        if (!empty($principalId)) {
            $resignedEmpQuery->where('principal_id', $principalId);
        }
        if (!empty($branchId)) {
            $resignedEmpQuery->where('branch_id', $branchId);
        }
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            if (auth()->user()->hasBranchRestriction()) {
                $resignedEmpQuery->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
            }
            if (auth()->user()->hasPrincipalRestriction()) {
                $resignedEmpQuery->whereIn('principal_id', auth()->user()->getAccessiblePrincipalIds());
            }
        }
        $resignedEmployees = $resignedEmpQuery->get(['id', 'updated_at', 'resign_date']);

        // Query Odoo sync logs in this window
        $syncLogs = OdooSyncLog::where('created_at', '>=', $windowStart->toDateTimeString())
            ->where('created_at', '<=', $windowEnd->toDateTimeString())
            ->get(['id', 'created_at', 'new_count', 'resign_count', 'update_count', 'total_employee_count']);

        $latestSyncLog = OdooSyncLog::latest('created_at')->first();
        $latestSyncTime = $latestSyncLog 
            ? Carbon::parse($latestSyncLog->created_at)->timezone('Asia/Jakarta')->translatedFormat('D, d M H:i WIB') 
            : null;

        $newSeries    = [];
        $resignSeries = [];

        foreach ($slots as $slot) {
            $slotStart = $slot['start'];
            $slotEnd   = $slot['end'];

            $slotNewCount = 0;
            foreach ($newEmployees as $ne) {
                $cTime = Carbon::parse($ne->created_at)->timezone('Asia/Jakarta');
                if ($cTime->between($slotStart, $slotEnd)) {
                    $slotNewCount++;
                }
            }

            $slotResignCount = 0;
            foreach ($resignedEmployees as $re) {
                $uTime = Carbon::parse($re->updated_at)->timezone('Asia/Jakarta');
                if ($uTime->between($slotStart, $slotEnd)) {
                    $slotResignCount++;
                }
            }

            // Cross-check dengan data odoo_sync_logs jika ada batch sync pada jam tersebut
            foreach ($syncLogs as $sLog) {
                $lTime = Carbon::parse($sLog->created_at)->timezone('Asia/Jakarta');
                if ($lTime->between($slotStart, $slotEnd)) {
                    $slotNewCount    = max($slotNewCount, (int)$sLog->new_count);
                    $slotResignCount = max($slotResignCount, (int)$sLog->resign_count);
                }
            }

            $newSeries[]    = $slotNewCount;
            $resignSeries[] = $slotResignCount;
        }

        // Kalkulasi running total employee aktif ke belakang dari data terkini (currentTotalActive)
        $reversedSlots        = array_reverse($slots);
        $newReversed          = array_reverse($newSeries);
        $resignReversed       = array_reverse($resignSeries);
        $activeSeriesReversed = [];
        $runningActive        = $currentTotalActive;

        foreach ($reversedSlots as $idx => $slot) {
            $activeSeriesReversed[] = $runningActive;
            $deltaNew    = $newReversed[$idx] ?? 0;
            $deltaResign = $resignReversed[$idx] ?? 0;

            // Mundur ke jam sebelumnya: kurangi yang baru masuk, tambahkan yang keluar
            $runningActive = max(0, $runningActive - $deltaNew + $deltaResign);
        }

        $activeSeries = array_reverse($activeSeriesReversed);

        $totalNew      = array_sum($newSeries);
        $totalResigned = array_sum($resignSeries);

        return [
            'slots'   => $slots,
            'active'  => $activeSeries,
            'new'     => $newSeries,
            'resign'  => $resignSeries,
            'summary' => [
                'totalActive'    => $currentTotalActive,
                'totalInactive'  => $currentTotalInactive,
                'totalNew'       => $totalNew,
                'totalResigned'  => $totalResigned,
                'netChange'      => $totalNew - $totalResigned,
                'latestSyncTime' => $latestSyncTime,
            ],
        ];
    }

    public function getSummaryStats(): array
    {
        return $this->computeHourlyData()['summary'];
    }

    protected function getData(): array
    {
        $computed = $this->computeHourlyData();
        $labels = array_column($computed['slots'], 'label');

        return [
            'datasets' => [
                [
                    'label'                => 'Total Employee Aktif',
                    'data'                 => $computed['active'],
                    'borderColor'          => '#0F52BA',
                    'backgroundColor'      => 'rgba(15, 82, 186, 0.12)',
                    'fill'                 => true,
                    'tension'              => 0.35,
                    'borderWidth'          => 3,
                    'pointRadius'          => 4.5,
                    'pointHoverRadius'     => 7,
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor'     => '#0F52BA',
                    'pointBorderWidth'     => 2.5,
                    'yAxisID'              => 'y',
                ],
                [
                    'label'                => 'Karyawan Baru (+) Odoo',
                    'data'                 => $computed['new'],
                    'borderColor'          => '#10B981',
                    'backgroundColor'      => 'rgba(16, 185, 129, 0.15)',
                    'fill'                 => false,
                    'tension'              => 0.25,
                    'borderWidth'          => 2.5,
                    'pointRadius'          => 4,
                    'pointHoverRadius'     => 6,
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor'     => '#10B981',
                    'pointBorderWidth'     => 2,
                    'borderDash'           => [5, 4],
                    'yAxisID'              => 'y1',
                ],
                [
                    'label'                => 'Resign / Non-Aktif (-) Odoo',
                    'data'                 => $computed['resign'],
                    'borderColor'          => '#EF4444',
                    'backgroundColor'      => 'rgba(239, 68, 68, 0.15)',
                    'fill'                 => false,
                    'tension'              => 0.25,
                    'borderWidth'          => 2.5,
                    'pointRadius'          => 4,
                    'pointHoverRadius'     => 6,
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor'     => '#EF4444',
                    'pointBorderWidth'     => 2,
                    'borderDash'           => [3, 3],
                    'yAxisID'              => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 8,
                        'boxHeight' => 8,
                        'padding' => 16,
                        'font' => [
                            'family' => "'Outfit', 'Plus Jakarta Sans', sans-serif",
                            'size' => 12,
                            'weight' => '600',
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'titleFont' => [
                        'family' => "'Outfit', 'Plus Jakarta Sans', sans-serif",
                        'size' => 13,
                        'weight' => 'bold',
                    ],
                    'bodyFont' => [
                        'family' => "'Outfit', 'Plus Jakarta Sans', sans-serif",
                        'size' => 12,
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'family' => "'Outfit', sans-serif",
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => false,
                    'title' => [
                        'display' => true,
                        'text' => 'Total Employee Aktif',
                        'font' => [
                            'family' => "'Outfit', sans-serif",
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'font' => [
                            'family' => "'Outfit', sans-serif",
                            'size' => 11,
                        ],
                    ],
                    'grid' => [
                        'color' => 'rgba(226, 232, 240, 0.6)',
                        'borderDash' => [3, 3],
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Perubahan Odoo (+ / -)',
                        'font' => [
                            'family' => "'Outfit', sans-serif",
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                        'font' => [
                            'family' => "'Outfit', sans-serif",
                            'size' => 11,
                        ],
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
