<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action;
use App\Models\Branch;
use App\Models\Principal;
use App\Models\Employee;
use App\Models\WorkTarget;
use App\Models\Attendance;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MandaysExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MandaysReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';
    protected static ?string $navigationLabel = 'Mandays Report';
    protected static ?string $title = 'Mandays Report';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.mandays-report';

    public ?string $month = null;
    public ?string $year = null;
    public ?string $branch_id = null;
    public ?string $principal_id = null;

    public function mount(): void
    {
        $this->month = date('m');
        $this->year = date('Y');
        $this->form->fill([
            'month' => $this->month,
            'year' => $this->year,
            'branch_id' => null,
            'principal_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        $years = [];
        for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
            $years[$i] = $i;
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
                        ->default(date('Y'))
                        ->required()
                        ->live(),
                    Select::make('branch_id')
                        ->label('Region / Area')
                        ->options(Branch::pluck('name', 'id'))
                        ->placeholder('Semua Region')
                        ->live(),
                    Select::make('principal_id')
                        ->label('Principal')
                        ->options(Principal::pluck('name', 'id'))
                        ->placeholder('Semua Principal')
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

    public function getMandaysData(): array
    {
        $month = $this->month ?: date('m');
        $year = $this->year ?: date('Y');
        $monthYear = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        $employees = Employee::with(['branch', 'principal'])
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->when($this->principal_id, function ($q) {
                return $q->where('principal_id', $this->principal_id);
            })
            ->where('is_active', true)
            ->get();

        $data = [];

        foreach ($employees as $emp) {
            $target = WorkTarget::where('employee_id', $emp->id)
                ->where('month_year', $monthYear)
                ->first();
                
            $targetHK = $target ? $target->target_hk : 0;

            // Aktual HK termasuk present, late, permit
            $aktualHK = Attendance::where('employee_id', $emp->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->whereIn('status', ['present', 'late', 'permit'])
                ->count();

            $percentage = $targetHK > 0 ? round(($aktualHK / $targetHK) * 100, 2) : 0;

            $data[] = [
                'employee' => $emp->full_name,
                'branch' => optional($emp->branch)->name ?? '-',
                'principal' => optional($emp->principal)->name ?? '-',
                'target' => $targetHK,
                'aktual' => $aktualHK,
                'percentage' => $percentage,
            ];
        }

        return $data;
    }

    public function exportExcel()
    {
        $rawData = $this->getMandaysData();
        $exportData = [];
        
        foreach ($rawData as $row) {
            $exportData[] = [
                $row['employee'],
                $row['branch'],
                $row['principal'],
                $row['target'],
                $row['aktual'],
                $row['percentage'],
            ];
        }

        $period = $this->year . '-' . $this->month;
        return Excel::download(new MandaysExport($exportData, $period), 'Mandays_Report_' . $period . '.xlsx');
    }
}
