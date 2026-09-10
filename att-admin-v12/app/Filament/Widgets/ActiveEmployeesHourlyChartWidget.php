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

    protected ?string $heading = 'Perubahan Employee Aktif Tiap 30 Menit (Odoo Sync)';
    protected ?string $description = 'Tren pergerakan jumlah karyawan aktif, penambahan karyawan baru, dan mutasi resign hasil sinkronisasi Odoo tiap 30 menit';
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
                        '6h'    => '6 Jam Terakhir (Tiap 30 Menit)',
                        '12h'   => '12 Jam Terakhir (Tiap 30 Menit - Default)',
                        '24h'   => '24 Jam Terakhir (Tiap 30 Menit)',
                        'today' => 'Hari Ini (Tiap 30 Menit)',
                        '7d'    => '7 Hari Terakhir (Harian)',
                        '30d'   => '30 Hari Terakhir (Harian)',
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

        // Untuk rentang 7 hari dan 30 hari: tampilkan agregasi harian
        if ($range === '7d' || $range === '30d') {
            $totalDays = $range === '7d' ? 7 : 30;
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
            return $slots;
        }

        // Interval 30 Menit (Sesuai Jadwal Sinkronisasi Odoo)
        if ($range === '6h') {
            $totalSlots = 12; // 6 jam * 2 slot
        } elseif ($range === '24h') {
            $totalSlots = 48; // 24 jam * 2 slot
        } elseif ($range === 'today') {
            // Sejak 00:00 hari ini sampai slot 30 menit saat ini
            $totalHalfHours = ((int)$now->format('H') * 2) + ($now->minute >= 30 ? 2 : 1);
            $totalSlots = max(1, $totalHalfHours);
        } else {
            // Default 12h: 12 jam * 2 = 24 slot per 30 menit
            $totalSlots = 24;
        }

        // Anchor slot 30 menit terkini (:00 atau :30)
        $currentMinuteSlot = $now->minute >= 30 ? 30 : 0;
        $anchor = $now->copy()->minute($currentMinuteSlot)->second(0);

        for ($i = $totalSlots - 1; $i >= 0; $i--) {
            $slotStart = $anchor->copy()->subMinutes($i * 30);
            $slotEnd   = $slotStart->copy()->addMinutes(29)->second(59);

            $slots[] = [
                'start'      => $slotStart,
                'end'        => $slotEnd,
                'label'      => $slotStart->format('H:i'),
                'full_label' => $slotStart->translatedFormat('D, d M H:i') . ' - ' . $slotEnd->format('H:i'),
            ];
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
        // PENTING: Hanya ambil karyawan yang SEBELUMNYA AKTIF, lalu di-update menjadi resign/non-aktif dalam window ini.
        // Abaikan arsip mantan karyawan lama yang baru di-insert langsung dengan status is_active=false.
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
        $resignedEmployees = $resignedEmpQuery->get(['id', 'created_at', 'updated_at', 'resign_date']);

        // Query Odoo sync logs in this window
        $syncLogs = OdooSyncLog::where('created_at', '>=', $windowStart->toDateTimeString())
            ->where('created_at', '<=', $windowEnd->toDateTimeString())
            ->get(['id', 'created_at', 'new_count', 'resign_count', 'update_count', 'total_employee_count', 'details']);

        $latestSyncLog = OdooSyncLog::latest('created_at')->first();
        $latestSyncTime = $latestSyncLog 
            ? Carbon::parse($latestSyncLog->created_at)->timezone('Asia/Jakarta')->translatedFormat('D, d M H:i WIB') 
            : null;

        $newSeries    = [];
        $resignSeries = [];

        foreach ($slots as $slot) {
            $slotStart = $slot['start'];
            $slotEnd   = $slot['end'];

            // 1. Karyawan Baru
            $slotNewCount = 0;
            foreach ($newEmployees as $ne) {
                $cTime = Carbon::parse($ne->created_at)->timezone('Asia/Jakarta');
                if ($cTime->between($slotStart, $slotEnd)) {
                    $slotNewCount++;
                }
            }

            // 2. Karyawan Resign / Non-Aktif (Mutasi Riil)
            $slotResignCount = 0;
            foreach ($resignedEmployees as $re) {
                $uTime = Carbon::parse($re->updated_at)->timezone('Asia/Jakarta');
                $cTime = Carbon::parse($re->created_at)->timezone('Asia/Jakarta');

                // Hanya hitung jika karyawan tersebut adalah mutasi riil (bukan record baru yang di-insert langsung sebagai non-aktif)
                if ($uTime->between($slotStart, $slotEnd) && $uTime->diffInMinutes($cTime) > 10) {
                    $slotResignCount++;
                }
            }

            // 3. Cross-check dengan OdooSyncLog pada slot jam ini
            $logNewInSlot = 0;
            $logResignInSlot = 0;
            foreach ($syncLogs as $sLog) {
                $lTime = Carbon::parse($sLog->created_at)->timezone('Asia/Jakarta');
                if ($lTime->between($slotStart, $slotEnd)) {
                    $logNewInSlot = max($logNewInSlot, (int)$sLog->new_count);

                    $details = is_array($sLog->details) ? $sLog->details : json_decode($sLog->details ?? '[]', true);
                    if (!empty($details['resigned_employees']) && is_array($details['resigned_employees'])) {
                        $logResignInSlot = max($logResignInSlot, count($details['resigned_employees']));
                    } elseif ((int)$sLog->resign_count > 0 && (int)$sLog->resign_count < 50) {
                        // Hanya gunakan jika nilainya realistis sebagai mutasi per jam (< 50)
                        $logResignInSlot = max($logResignInSlot, (int)$sLog->resign_count);
                    }
                }
            }

            $slotNewCount = max($slotNewCount, $logNewInSlot);
            $slotResignCount = max($slotResignCount, $logResignInSlot);

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
                    'grace' => '5%',
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
                    'suggestedMax' => 10,
                    'grace' => '10%',
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
