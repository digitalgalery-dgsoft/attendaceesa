<?php

namespace App\Filament\Pages;

use App\Exports\ManPowerExport;
use App\Models\Branch;
use App\Models\Principal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ManPowerReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';
    protected static ?string $navigationLabel = 'Manpower Report';
    protected static ?string $title = 'Man Power Report';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.man-power-report';

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('view_manpower_report'));
    }

    public ?string $year = null;
    public ?string $principal_id = null;
    public ?string $branch_id = null;

    protected ?array $memoizedData = null;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function boot(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function mount(): void
    {
        @ini_set('memory_limit', '512M');
        $this->year = date('Y');
        $this->principal_id = null;
        $this->branch_id = null;
        $this->form->fill([
            'year' => $this->year,
            'principal_id' => null,
            'branch_id' => null,
        ]);
    }

    public function rendering(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function form(Schema $form): Schema
    {
        $years = [];
        for ($i = (int)date('Y'); $i >= (int)date('Y') - 5; $i--) {
            $years[(string)$i] = (string)$i;
        }

        return $form
            ->schema([
                Grid::make(3)->schema([
                    Select::make('year')
                        ->label('Tahun')
                        ->options($years)
                        ->default((string)date('Y'))
                        ->required()
                        ->live(),
                    Select::make('principal_id')
                        ->label('Prinsiple')
                        ->options(function () {
                            $query = Principal::where('is_active', true)->orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->placeholder('Semua Prinsiple')
                        ->live(),
                    Select::make('branch_id')
                        ->label('Region / Area')
                        ->options(function () {
                            $query = Branch::orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->placeholder('Semua Region / Area')
                        ->live(),
                ])
            ])
            ->statePath('');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => $this->exportExcel()),
            Action::make('refresh')
                ->label('Segarkan Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => Notification::make()->title('Data Manpower Diperbarui')->success()->send()),
        ];
    }

    /**
     * Mengambil data Manpower per Prinsiple dan per bulan secara cepat dan hemat RAM.
     * Bulan yang belum berjalan pada tahun berjalan tidak dihitung (null / 0).
     */
    public function getManPowerData(): array
    {
        if ($this->memoizedData !== null) {
            return $this->memoizedData;
        }

        @ini_set('memory_limit', '512M');

        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n'); // 1 to 12
        $selectedYear = (int)($this->year ?: date('Y'));

        // Menentukan bulan maksimal yang sudah berjalan
        $maxValidMonth = 12;
        if ($selectedYear === $currentYear) {
            $maxValidMonth = $currentMonth;
        } elseif ($selectedYear > $currentYear) {
            $maxValidMonth = 0;
        }

        $startOfYear = "{$selectedYear}-01-01";
        $endOfYear = "{$selectedYear}-12-31";

        // Query principals
        $principalsQuery = DB::table('principals')->orderBy('name');
        if (!empty($this->principal_id)) {
            $principalsQuery->where('id', $this->principal_id);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $principalsQuery->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
        }
        $principals = $principalsQuery->select('id', 'name')->get();

        if ($principals->isEmpty()) {
            return $this->memoizedData = [];
        }

        $principalIds = $principals->pluck('id')->toArray();

        // Query employees lightweight
        $employeesQuery = DB::table('employees')
            ->whereIn('principal_id', $principalIds)
            ->whereNull('deleted_at');

        if (!empty($this->branch_id)) {
            $employeesQuery->where('branch_id', $this->branch_id);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $employeesQuery->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
        }

        $employees = $employeesQuery
            ->where(function ($q) use ($endOfYear) {
                $q->whereNull('join_date')
                  ->orWhere('join_date', '<=', $endOfYear);
            })
            ->where(function ($q) use ($startOfYear) {
                $q->whereNull('resign_date')
                  ->orWhere('resign_date', '>=', $startOfYear);
            })
            ->select([
                'id',
                'principal_id',
                DB::raw("SUBSTRING(CAST(join_date AS VARCHAR), 1, 10) as join_date_str"),
                DB::raw("SUBSTRING(CAST(resign_date AS VARCHAR), 1, 10) as resign_date_str"),
                'employment_status',
            ])
            ->get();

        // Precalculate end of month dates
        $endOfMonths = [];
        for ($month = 1; $month <= 12; $month++) {
            $endOfMonths[$month] = Carbon::createFromDate($selectedYear, $month, 1)->endOfMonth()->toDateString();
        }

        $data = [];
        $employeesByPrincipal = $employees->groupBy('principal_id');

        foreach ($principals as $principal) {
            $monthlyData = [];
            $totalActive = 0;
            $validMonthsCount = 0;
            $principalEmps = $employeesByPrincipal->get($principal->id, collect());

            for ($month = 1; $month <= 12; $month++) {
                // Jika bulan belum berjalan pada tahun ini, set null (jangan tampilkan)
                if ($month > $maxValidMonth) {
                    $monthlyData[] = null;
                    continue;
                }

                $endOfMonth = $endOfMonths[$month];
                $count = 0;

                foreach ($principalEmps as $emp) {
                    $joinDate = $emp->join_date_str;
                    $resignDate = $emp->resign_date_str;

                    $joinedBefore = empty($joinDate) || $joinDate <= $endOfMonth;
                    $resignedAfter = empty($resignDate) || $resignDate > $endOfMonth;
                    $statusOk = $emp->employment_status !== 'resigned' || (!empty($resignDate) && $resignDate > $endOfMonth);

                    if ($joinedBefore && $resignedAfter && $statusOk) {
                        $count++;
                    }
                }

                $monthlyData[] = $count;
                $totalActive += $count;
                $validMonthsCount++;
            }

            // Rata-rata hanya dihitung dari bulan yang sudah berjalan
            $avg = $validMonthsCount > 0 ? (int)round($totalActive / $validMonthsCount) : 0;
            $monthlyData[] = $avg;

            $data[] = [
                'principal' => $principal->name,
                'months' => $monthlyData,
            ];
        }

        return $this->memoizedData = $data;
    }

    public function exportExcel()
    {
        $rawData = $this->getManPowerData();
        $exportData = [];

        foreach ($rawData as $row) {
            $exportRow = [$row['principal']];
            foreach ($row['months'] as $val) {
                $exportRow[] = $val !== null ? $val : '-';
            }
            $exportData[] = $exportRow;
        }

        return Excel::download(new ManPowerExport($exportData, $this->year ?: date('Y')), 'ManPower_Report_' . ($this->year ?: date('Y')) . '.xlsx');
    }
}
