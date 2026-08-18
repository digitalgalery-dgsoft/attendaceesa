<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Actions\Action;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ManPowerExport;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;

class ManPowerReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';
    protected static ?string $navigationLabel = 'Man Power Report';
    protected static ?string $title = 'Man Power Report';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.man-power-report';

    public ?string $year = null;
    public ?string $company_id = null;
    public ?string $branch_id = null;

    public function mount(): void
    {
        $this->year = date('Y');
        $this->form->fill([
            'year' => $this->year,
            'company_id' => null,
            'branch_id' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $years = [];
        for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
            $years[$i] = $i;
        }

        return $form
            ->schema([
                Grid::make(3)->schema([
                    Select::make('year')
                        ->label('Tahun')
                        ->options($years)
                        ->default(date('Y'))
                        ->required()
                        ->live(),
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->options(Company::pluck('name', 'id'))
                        ->placeholder('Semua Perusahaan')
                        ->live(),
                    Select::make('branch_id')
                        ->label('Region / Area')
                        ->options(Branch::pluck('name', 'id'))
                        ->placeholder('Semua Region')
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
        ];
    }

    public function getManPowerData(): array
    {
        $year = $this->year ?: date('Y');
        $startOfYear = "{$year}-01-01";
        $endOfYear = "{$year}-12-31";
        
        $companies = Company::when($this->company_id, function ($q) {
            return $q->where('id', $this->company_id);
        })->get();
        
        $companyIds = $companies->pluck('id')->toArray();

        $employees = Employee::select('id', 'company_id', 'join_date', 'resign_date', 'employment_status')
            ->whereIn('company_id', $companyIds)
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->where(function($q) use ($endOfYear) {
                $q->whereNull('join_date')
                  ->orWhere('join_date', '<=', $endOfYear);
            })
            ->where(function($q) use ($startOfYear) {
                $q->whereNull('resign_date')
                  ->orWhere('resign_date', '>=', $startOfYear);
            })
            ->get();

        $data = [];

        foreach ($companies as $company) {
            $monthlyData = [];
            $totalActive = 0;
            
            $companyEmployees = $employees->where('company_id', $company->id);
            
            for ($month = 1; $month <= 12; $month++) {
                $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                
                $count = 0;
                foreach($companyEmployees as $emp) {
                    $joinedBeforeEndOfMonth = is_null($emp->join_date) || $emp->join_date <= $endOfMonth;
                    $resignedAfterEndOfMonth = is_null($emp->resign_date) || $emp->resign_date > $endOfMonth;
                    $statusOk = $emp->employment_status !== 'resigned' || ($emp->employment_status === 'resigned' && !is_null($emp->resign_date) && $emp->resign_date > $endOfMonth);
                    
                    if ($joinedBeforeEndOfMonth && $resignedAfterEndOfMonth && $statusOk) {
                        $count++;
                    }
                }

                $monthlyData[] = $count;
                $totalActive += $count;
            }

            $avg = round($totalActive / 12);
            $monthlyData[] = $avg;

            $data[] = [
                'company' => $company->name,
                'months' => $monthlyData,
            ];
        }

        return $data;
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

        return Excel::download(new ManPowerExport($exportData, $this->year), 'ManPower_Report_' . $this->year . '.xlsx');
    }
}
