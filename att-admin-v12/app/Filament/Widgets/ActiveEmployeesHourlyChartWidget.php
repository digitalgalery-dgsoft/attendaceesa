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
use App\Models\Attendance;
use App\Models\Principal;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActiveEmployeesHourlyChartWidget extends ChartWidget implements HasActions, HasSchemas
{
    use HasFiltersSchema;
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected ?string $heading = 'Perubahan Employee Aktif Tiap Jam';
    protected ?string $description = 'Tren real-time pergerakan karyawan aktif, check-in baru, dan check-out dalam 12 jam terakhir';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '340px';
    protected static ?string $pollingInterval = '30s';

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
        $now = Carbon::now();
        $range = $this->filters['time_range'] ?? '12h';
        $slots = [];

        if ($range === '24h') {
            $totalHours = 24;
        } elseif ($range === 'today') {
            $totalHours = (int)$now->format('H') + 1;
        } else {
            $totalHours = 12;
        }

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

        return $slots;
    }

    public function computeHourlyData(): array
    {
        $slots = $this->getTimeSlots();
        if (empty($slots)) {
            return [
                'slots'    => [],
                'active'   => [],
                'checkin'  => [],
                'checkout' => [],
                'summary'  => [
                    'currentActive'  => 0,
                    'peakActive'     => 0,
                    'peakHour'       => '-',
                    'totalCheckins'  => 0,
                    'totalCheckouts' => 0,
                    'diff'           => 0,
                ],
            ];
        }

        $windowStart     = $slots[0]['start'];
        $windowEnd       = end($slots)['end'];
        $windowStartDate = $windowStart->toDateString();
        $windowEndDate   = $windowEnd->toDateString();

        $principalId = $this->filters['principal_id'] ?? null;
        $branchId    = $this->filters['branch_id'] ?? null;

        $attQuery = DB::table('attendances')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->select([
                'attendances.id',
                'attendances.employee_id',
                'attendances.checkin_at',
                'attendances.checkout_at',
                'attendances.attendance_date',
                'employees.principal_id',
                'employees.branch_id',
            ])
            ->where('attendances.attendance_date', '>=', $windowStartDate)
            ->where('attendances.attendance_date', '<=', $windowEndDate)
            ->whereNotNull('attendances.checkin_at')
            ->where('attendances.checkin_at', '<=', $windowEnd)
            ->where(function ($q) use ($windowStart) {
                $q->whereNull('attendances.checkout_at')
                  ->orWhere('attendances.checkout_at', '>=', $windowStart);
            })
            ->whereNull('employees.deleted_at');

        if (!empty($principalId)) {
            $attQuery->where('employees.principal_id', $principalId);
        }
        if (!empty($branchId)) {
            $attQuery->where('employees.branch_id', $branchId);
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            if (auth()->user()->hasBranchRestriction()) {
                $attQuery->whereIn('employees.branch_id', auth()->user()->getAccessibleBranchIds());
            }
            if (auth()->user()->hasPrincipalRestriction()) {
                $attQuery->whereIn('employees.principal_id', auth()->user()->getAccessiblePrincipalIds());
            }
        }

        $attendances = $attQuery->get();

        $trackingQuery = DB::table('tracking_histories')
            ->join('employees', 'tracking_histories.employee_id', '=', 'employees.id')
            ->select([
                'tracking_histories.employee_id',
                'tracking_histories.created_at',
            ])
            ->where('tracking_histories.created_at', '>=', $windowStart)
            ->where('tracking_histories.created_at', '<=', $windowEnd)
            ->whereNull('employees.deleted_at');

        if (!empty($principalId)) {
            $trackingQuery->where('employees.principal_id', $principalId);
        }
        if (!empty($branchId)) {
            $trackingQuery->where('employees.branch_id', $branchId);
        }
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            if (auth()->user()->hasBranchRestriction()) {
                $trackingQuery->whereIn('employees.branch_id', auth()->user()->getAccessibleBranchIds());
            }
            if (auth()->user()->hasPrincipalRestriction()) {
                $trackingQuery->whereIn('employees.principal_id', auth()->user()->getAccessiblePrincipalIds());
            }
        }

        $trackings = $trackingQuery->get();

        $activeSeries   = [];
        $checkinSeries  = [];
        $checkoutSeries = [];

        foreach ($slots as $slot) {
            $slotStart = $slot['start'];
            $slotEnd   = $slot['end'];

            $activeEmployeeIds = [];

            foreach ($attendances as $att) {
                $checkin = Carbon::parse($att->checkin_at);
                if ($checkin->greaterThan($slotEnd)) {
                    continue;
                }
                if (!empty($att->checkout_at)) {
                    $checkout = Carbon::parse($att->checkout_at);
                    if ($checkout->lessThan($slotStart)) {
                        continue;
                    }
                }
                $activeEmployeeIds[$att->employee_id] = true;
            }

            foreach ($trackings as $tr) {
                $trTime = Carbon::parse($tr->created_at);
                if ($trTime->between($slotStart, $slotEnd)) {
                    $activeEmployeeIds[$tr->employee_id] = true;
                }
            }

            $activeSeries[] = count($activeEmployeeIds);

            // New check-ins in this hour
            $newCheckinCount = 0;
            foreach ($attendances as $att) {
                $checkin = Carbon::parse($att->checkin_at);
                if ($checkin->between($slotStart, $slotEnd)) {
                    $newCheckinCount++;
                }
            }
            $checkinSeries[] = $newCheckinCount;

            // Checkouts in this hour
            $checkoutCount = 0;
            foreach ($attendances as $att) {
                if (!empty($att->checkout_at)) {
                    $checkout = Carbon::parse($att->checkout_at);
                    if ($checkout->between($slotStart, $slotEnd)) {
                        $checkoutCount++;
                    }
                }
            }
            $checkoutSeries[] = $checkoutCount;
        }

        $currentActive = !empty($activeSeries) ? end($activeSeries) : 0;
        $prevActive    = count($activeSeries) > 1 ? $activeSeries[count($activeSeries) - 2] : $currentActive;
        $peakActive    = !empty($activeSeries) ? max($activeSeries) : 0;
        $peakIndex     = array_search($peakActive, $activeSeries);
        $peakHour      = ($peakIndex !== false && isset($slots[$peakIndex])) ? $slots[$peakIndex]['label'] : '-';

        return [
            'slots'    => $slots,
            'active'   => $activeSeries,
            'checkin'  => $checkinSeries,
            'checkout' => $checkoutSeries,
            'summary'  => [
                'currentActive'  => $currentActive,
                'peakActive'     => $peakActive,
                'peakHour'       => $peakHour,
                'totalCheckins'  => array_sum($checkinSeries),
                'totalCheckouts' => array_sum($checkoutSeries),
                'diff'           => $currentActive - $prevActive,
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
                    'label'                => 'Employee Aktif (On-Duty)',
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
                ],
                [
                    'label'                => 'Check-in Baru (+)',
                    'data'                 => $computed['checkin'],
                    'borderColor'          => '#10B981',
                    'backgroundColor'      => 'rgba(16, 185, 129, 0.08)',
                    'fill'                 => false,
                    'tension'              => 0.3,
                    'borderWidth'          => 2,
                    'pointRadius'          => 3.5,
                    'pointHoverRadius'     => 6,
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor'     => '#10B981',
                    'pointBorderWidth'     => 2,
                    'borderDash'           => [5, 4],
                ],
                [
                    'label'                => 'Check-out Selesai (-)',
                    'data'                 => $computed['checkout'],
                    'borderColor'          => '#EF4444',
                    'backgroundColor'      => 'rgba(239, 68, 68, 0.08)',
                    'fill'                 => false,
                    'tension'              => 0.3,
                    'borderWidth'          => 2,
                    'pointRadius'          => 3.5,
                    'pointHoverRadius'     => 6,
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor'     => '#EF4444',
                    'pointBorderWidth'     => 2,
                    'borderDash'           => [3, 3],
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
                    'beginAtZero' => true,
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
