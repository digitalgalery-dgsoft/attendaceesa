<?php

namespace App\Filament\Pages;

use App\Exports\MandaysExport;
use App\Models\Branch;
use App\Models\Company;
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

class MandaysReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';
    protected static ?string $navigationLabel = 'Mandays Report';
    protected static ?string $title = 'Mandays Report';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.mandays-report';

    public ?string $month = null;
    public ?string $year = null;
    public ?string $branch_id = null;
    public ?string $company_id = null;
    public ?string $search = '';

    // Pagination
    public int $page = 1;
    public int $perPage = 25;

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
        $this->month = date('m');
        $this->year = date('Y');
        $this->branch_id = null;
        $this->company_id = null;
        $this->search = '';
        $this->page = 1;
        $this->perPage = 25;

        $this->form->fill([
            'month' => $this->month,
            'year' => $this->year,
            'branch_id' => null,
            'company_id' => null,
        ]);
    }

    public function rendering(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function updatedMonth(): void { $this->page = 1; }
    public function updatedYear(): void { $this->page = 1; }
    public function updatedBranchId(): void { $this->page = 1; }
    public function updatedCompanyId(): void { $this->page = 1; }
    public function updatedSearch(): void { $this->page = 1; }
    public function updatedPerPage(): void { $this->page = 1; }

    public function form(Schema $form): Schema
    {
        $years = [];
        for ($i = (int)date('Y'); $i >= (int)date('Y') - 5; $i--) {
            $years[(string)$i] = (string)$i;
        }

        $months = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        return $form
            ->schema([
                Grid::make(4)->schema([
                    Select::make('month')
                        ->label('Bulan')
                        ->options($months)
                        ->default(date('m'))
                        ->required()
                        ->live(),
                    Select::make('year')
                        ->label('Tahun')
                        ->options($years)
                        ->default((string)date('Y'))
                        ->required()
                        ->live(),
                    Select::make('branch_id')
                        ->label('Region / Area')
                        ->options(Branch::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Region / Area')
                        ->live(),
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->options(Company::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Perusahaan')
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
                ->action(fn () => Notification::make()->title('Data Mandays Diperbarui')->success()->send()),
        ];
    }

    /**
     * Mengambil seluruh data Mandays (Target vs Aktual HK) secara cepat dan aman
     */
    public function getAllMandaysData(): array
    {
        if ($this->memoizedData !== null) {
            return $this->memoizedData;
        }

        @ini_set('memory_limit', '512M');

        $month = str_pad($this->month ?: date('m'), 2, '0', STR_PAD_LEFT);
        $year = $this->year ?: date('Y');
        $monthYear = "{$year}-{$month}";

        $startDateStr = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfMonth()->toDateString();
        $endDateStr = Carbon::createFromDate((int)$year, (int)$month, 1)->endOfMonth()->toDateString();

        $employees = DB::table('employees')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('companies', 'employees.company_id', '=', 'companies.id')
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at')
            ->when(!empty($this->branch_id), function ($q) {
                return $q->where('employees.branch_id', $this->branch_id);
            })
            ->when(!empty($this->company_id), function ($q) {
                return $q->where('employees.company_id', $this->company_id);
            })
            ->select([
                'employees.id',
                'employees.employee_no',
                'employees.full_name',
                'employees.photo',
                'branches.name as branch_name',
                'companies.name as company_name',
            ])
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();

        if (empty($employeeIds)) {
            return $this->memoizedData = [];
        }

        // Targets for selected month
        $targets = DB::table('work_targets')
            ->whereIn('employee_id', $employeeIds)
            ->where('month_year', $monthYear)
            ->pluck('target_hk', 'employee_id');

        // Attendances in that month
        $attendances = DB::table('attendances')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$startDateStr, $endDateStr])
            ->whereIn('status', ['present', 'late', 'permit'])
            ->select('employee_id', DB::raw('count(*) as total'))
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $data = [];

        foreach ($employees as $emp) {
            $targetHK = (int)($targets[$emp->id] ?? 0);
            $aktualHK = (int)($attendances[$emp->id] ?? 0);
            $percentage = $targetHK > 0 ? round(($aktualHK / $targetHK) * 100, 1) : 0;

            $data[] = [
                'id' => $emp->id,
                'employee_no' => $emp->employee_no ?? '-',
                'employee' => $emp->full_name ?? 'Unknown',
                'photo' => $emp->photo,
                'branch' => $emp->branch_name ?? '-',
                'company' => $emp->company_name ?? '-',
                'target' => $targetHK,
                'aktual' => $aktualHK,
                'percentage' => $percentage,
            ];
        }

        return $this->memoizedData = $data;
    }

    /**
     * Mengambil data Mandays terfilter pencarian & dipaginasi
     */
    public function getMandaysData(): array
    {
        $all = $this->getAllMandaysData();

        if (!empty(trim($this->search ?? ''))) {
            $q = strtolower(trim($this->search));
            $all = array_filter($all, function ($row) use ($q) {
                return str_contains(strtolower($row['employee']), $q)
                    || str_contains(strtolower($row['employee_no']), $q)
                    || str_contains(strtolower($row['branch']), $q)
                    || str_contains(strtolower($row['company']), $q);
            });
            $all = array_values($all);
        }

        $totalCount = count($all);
        $totalPages = max(1, (int)ceil($totalCount / $this->perPage));

        if ($this->page > $totalPages) {
            $this->page = $totalPages;
        }
        if ($this->page < 1) {
            $this->page = 1;
        }

        $offset = ($this->page - 1) * $this->perPage;
        $items = array_slice($all, $offset, $this->perPage);

        return [
            'items' => $items,
            'total_count' => $totalCount,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $totalPages,
            'from' => $totalCount > 0 ? $offset + 1 : 0,
            'to' => min($offset + $this->perPage, $totalCount),
        ];
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function nextPage(int $maxPage): void
    {
        if ($this->page < $maxPage) {
            $this->page++;
        }
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function exportExcel()
    {
        $rawData = $this->getAllMandaysData();
        $exportData = [];

        foreach ($rawData as $row) {
            $exportData[] = [
                $row['employee'],
                $row['branch'],
                $row['company'],
                $row['target'],
                $row['aktual'],
                $row['percentage'],
            ];
        }

        $period = ($this->year ?: date('Y')) . '-' . str_pad($this->month ?: date('m'), 2, '0', STR_PAD_LEFT);
        return Excel::download(new MandaysExport($exportData, $period), 'Mandays_Report_' . $period . '.xlsx');
    }
}
