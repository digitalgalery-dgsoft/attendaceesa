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
use App\Models\Employee;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TurnOverExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TurnOverReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Laporan & Analitik';
    protected static ?string $navigationLabel = 'Turn Over Report';
    protected static ?string $title = 'Turn Over Report';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.turn-over-report';

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

    public function form(Form $form): Form
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

        for ($month = 1; $month <= 12; $month++) {
            $joined = Employee::when($this->company_id, function ($q) {
                    return $q->where('company_id', $this->company_id);
                })
                ->whereYear('join_date', $year)
                ->whereMonth('join_date', $month)
                ->count();

            $resigned = Employee::when($this->company_id, function ($q) {
                    return $q->where('company_id', $this->company_id);
                })
                ->where(function($q) use ($year, $month) {
                    $q->where(function($sq) use ($year, $month) {
                        $sq->whereYear('resign_date', $year)
                           ->whereMonth('resign_date', $month);
                    })->orWhere(function($sq) use ($year, $month) {
                        $sq->where('employment_status', 'resigned')
                           ->whereYear('updated_at', $year)
                           ->whereMonth('updated_at', $month)
                           ->whereNull('resign_date'); // Fallback to updated_at if resign_date is null
                    });
                })
                ->count();

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
