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
use App\Models\Employee;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TurnOverExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TurnOverReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';
    protected static ?string $navigationLabel = 'Turn Over Report';
    protected static ?string $title = 'Turn Over Report';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.turn-over-report';

    public ?string $year = null;
    public ?string $company_id = null;

    public function mount(): void
    {
        $this->year = date('Y');
        $this->form->fill([
            'year' => $this->year,
            'company_id' => null,
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
                Grid::make(2)->schema([
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

    public function getTurnOverData(): array
    {
        $year = $this->year ?: date('Y');
        $data = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        $employees = Employee::select('id', 'company_id', 'join_date', 'resign_date', 'employment_status', 'updated_at')
            ->when($this->company_id, function ($q) {
                return $q->where('company_id', $this->company_id);
            })
            ->where(function($q) use ($year) {
                $q->whereYear('join_date', $year)
                  ->orWhereYear('resign_date', $year)
                  ->orWhere(function($sq) use ($year) {
                      $sq->where('employment_status', 'resigned')
                         ->whereYear('updated_at', $year)
                         ->whereNull('resign_date');
                  });
            })
            ->get();

        for ($month = 1; $month <= 12; $month++) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $joined = $employees->filter(function($emp) use ($year, $monthStr) {
                return $emp->join_date && substr($emp->join_date, 0, 7) === "{$year}-{$monthStr}";
            })->count();

            $resigned = $employees->filter(function($emp) use ($year, $monthStr) {
                if ($emp->resign_date && substr($emp->resign_date, 0, 7) === "{$year}-{$monthStr}") {
                    return true;
                }
                if (!$emp->resign_date && $emp->employment_status === 'resigned' && $emp->updated_at && $emp->updated_at->format('Y-m') === "{$year}-{$monthStr}") {
                    return true;
                }
                return false;
            })->count();

            $data[] = [
                'month' => $months[$month - 1],
                'joined' => $joined,
                'resigned' => $resigned,
                'net' => $joined - $resigned,
            ];
        }

        return $data;
    }

    public function exportExcel()
    {
        $rawData = $this->getTurnOverData();
        $exportData = [];
        
        foreach ($rawData as $row) {
            $exportData[] = [
                $row['month'],
                $row['joined'],
                $row['resigned'],
                $row['net'],
            ];
        }

        return Excel::download(new TurnOverExport($exportData, $this->year), 'TurnOver_Report_' . $this->year . '.xlsx');
    }
}
