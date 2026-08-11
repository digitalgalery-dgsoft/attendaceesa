<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
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
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';
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

    public function form(Form $form): Form
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
        
        $companies = Company::when($this->company_id, function ($q) {
            return $q->where('id', $this->company_id);
        })->get();

        $data = [];

        foreach ($companies as $company) {
            $monthlyData = [];
            $totalActive = 0;
            
            for ($month = 1; $month <= 12; $month++) {
                $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                
                // Count employees who joined on or before this month, and either haven't resigned or resigned AFTER this month
                $count = Employee::where('company_id', $company->id)
                    ->when($this->branch_id, function ($q) {
                        return $q->where('branch_id', $this->branch_id);
                    })
                    ->where(function ($q) use ($endOfMonth) {
                        $q->whereNull('join_date')
                          ->orWhere('join_date', '<=', $endOfMonth->toDateString());
                    })
                    ->where(function ($q) use ($endOfMonth) {
                        $q->whereNull('resign_date')
                          ->orWhere('resign_date', '>', $endOfMonth->toDateString());
                    })
                    // Assuming people who have "resigned" status but no date might still need to be filtered? 
                    // To be safe, if they have resign_date, we handle it. If not, and they are resigned, maybe they resigned long ago.
                    ->where(function($q) use ($endOfMonth) {
                         $q->where('employment_status', '!=', 'resigned')
                           ->orWhere(function($sq) use ($endOfMonth) {
                               $sq->where('employment_status', 'resigned')->whereNotNull('resign_date')->where('resign_date', '>', $endOfMonth->toDateString());
                           });
                    })
                    ->count();

                $monthlyData[] = $count;
                $totalActive += $count;
            }

            $avg = round($totalActive / 12);
            $monthlyData[] = $avg; // Add average as the last column

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
