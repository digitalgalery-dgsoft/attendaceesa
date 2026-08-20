<?php

namespace App\Filament\Pages;

use App\Exports\ManPowerExport;
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

class ManPowerReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';
    protected static ?string $navigationLabel = 'Manpower Report';
    protected static ?string $title = 'Man Power Report';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.man-power-report';

    public ?string $year = null;
    public ?string $company_id = null;
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
        $this->company_id = null;
        $this->branch_id = null;
        $this->form->fill([
            'year' => $this->year,
            'company_id' => null,
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
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->options(Company::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Perusahaan')
                        ->live(),
                    Select::make('branch_id')
                        ->label('Region / Area')
                        ->options(Branch::orderBy('name')->pluck('name', 'id'))
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
     * Mengambil data Manpower per perusahaan dan per bulan secara cepat dan hemat RAM
     */
    public function getManPowerData(): array
    {
        if ($this->memoizedData !== null) {
            return $this->memoizedData;
        }

        @ini_set('memory_limit', '512M');

        $year = $this->year ?: date('Y');
        $startOfYear = "{$year}-01-01";
        $endOfYear = "{$year}-12-31";

        // Query companies
        $companiesQuery = DB::table('companies')->orderBy('name');
        if (!empty($this->company_id)) {
            $companiesQuery->where('id', $this->company_id);
        }
        $companies = $companiesQuery->select('id', 'name')->get();

        if ($companies->isEmpty()) {
            return $this->memoizedData = [];
        }

        $companyIds = $companies->pluck('id')->toArray();

        // Query employees lightweight
        $employeesQuery = DB::table('employees')
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at');

        if (!empty($this->branch_id)) {
            $employeesQuery->where('branch_id', $this->branch_id);
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
                'company_id',
                DB::raw("SUBSTRING(CAST(join_date AS VARCHAR), 1, 10) as join_date_str"),
                DB::raw("SUBSTRING(CAST(resign_date AS VARCHAR), 1, 10) as resign_date_str"),
                'employment_status',
            ])
            ->get();

        // Precalculate end of month dates
        $endOfMonths = [];
        for ($month = 1; $month <= 12; $month++) {
            $endOfMonths[$month] = Carbon::createFromDate((int)$year, $month, 1)->endOfMonth()->toDateString();
        }

        $data = [];
        $employeesByCompany = $employees->groupBy('company_id');

        foreach ($companies as $company) {
            $monthlyData = [];
            $totalActive = 0;
            $companyEmps = $employeesByCompany->get($company->id, collect());

            for ($month = 1; $month <= 12; $month++) {
                $endOfMonth = $endOfMonths[$month];
                $count = 0;

                foreach ($companyEmps as $emp) {
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
            }

            $avg = (int)round($totalActive / 12);
            $monthlyData[] = $avg;

            $data[] = [
                'company' => $company->name,
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
            $exportRow = [$row['company']];
            foreach ($row['months'] as $val) {
                $exportRow[] = $val;
            }
            $exportData[] = $exportRow;
        }

        return Excel::download(new ManPowerExport($exportData, $this->year ?: date('Y')), 'ManPower_Report_' . ($this->year ?: date('Y')) . '.xlsx');
    }
}
