<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Filament\Resources\Pages\Page;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Illuminate\Support\Facades\View;

class AttendanceRoster extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string $resource = AttendanceResource::class;

    protected string $view = 'filament.pages.attendance-roster';
    
    public $filterData = [];
    
    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make('table')
                        ->modifyQueryUsing(function ($query, $livewire) {
                            $startDate = \Carbon\Carbon::parse($livewire->filterData['filter_start_date'] ?? \Carbon\Carbon::now()->startOfMonth()->toDateString())->startOfDay();
                            $endDate = \Carbon\Carbon::parse($livewire->filterData['filter_end_date'] ?? \Carbon\Carbon::now()->endOfMonth()->toDateString())->endOfDay();
                            
                            $query->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);
                                
                            if (!empty($livewire->filterData['filter_department_id'])) {
                                $query->whereHas('employee', fn($q) => $q->where('department_id', $livewire->filterData['filter_department_id']));
                            }
                            
                            if (!empty($livewire->filterData['filter_employee_id'])) {
                                $query->where('employee_id', $livewire->filterData['filter_employee_id']);
                            }
                            
                            return $query;
                        })
                        ->withColumns([
                            \pxlrbt\FilamentExcel\Columns\Column::make('employee.full_name')->heading('Employee'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('attendance_date')->heading('Date'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('status')->heading('Status'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('checkin_at')->heading('Check-in At'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('checkout_at')->heading('Check-out At'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('work_duration_minutes')->heading('Work Duration (Mins)'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('late_minutes')->heading('Late (Mins)'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('early_leave_minutes')->heading('Early Leave (Mins)'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('overtime_minutes')->heading('Overtime (Mins)'),
                        ])
                ]),
                
            \Filament\Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action(function () {
                    $startDate = \Carbon\Carbon::parse($this->filterData['filter_start_date'] ?? \Carbon\Carbon::now()->startOfMonth()->toDateString())->startOfDay();
                    $endDate = \Carbon\Carbon::parse($this->filterData['filter_end_date'] ?? \Carbon\Carbon::now()->endOfMonth()->toDateString())->endOfDay();
                    
                    $query = \App\Models\Attendance::query()
                        ->with('employee')
                        ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);
                        
                    if (!empty($this->filterData['filter_department_id'])) {
                        $query->whereHas('employee', fn($q) => $q->where('department_id', $this->filterData['filter_department_id']));
                    }
                    
                    if (!empty($this->filterData['filter_employee_id'])) {
                        $query->where('employee_id', $this->filterData['filter_employee_id']);
                    }
                    
                    $attendances = $query->get();
                    
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.attendance-report', ['attendances' => $attendances]);
                    $filename = 'attendance-report-' . date('Y-m-d-His') . '.pdf';
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
                }),
        ];
    }
    
    public function mount()
    {
        $this->form->fill([
            'filter_start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'filter_end_date' => Carbon::now()->endOfMonth()->toDateString(),
            'filter_department_id' => null,
            'filter_employee_id' => null,
        ]);
    }
    
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    DatePicker::make('filter_start_date')
                        ->label('Start Date')
                        ->live()
                        ->required(),
                    DatePicker::make('filter_end_date')
                        ->label('End Date')
                        ->live()
                        ->afterOrEqual('filter_start_date')
                        ->required(),
                    \Filament\Forms\Components\Select::make('filter_department_id')
                        ->label('Department')
                        ->options(\App\Models\Department::pluck('name', 'id'))
                        ->searchable()
                        ->live(),
                    \Filament\Forms\Components\Select::make('filter_employee_id')
                        ->label('Employee')
                        ->options(\App\Models\Employee::where('is_active', 1)->pluck('full_name', 'id'))
                        ->searchable()
                        ->live(),
                ])
            ])
            ->statePath('filterData');
    }

    protected function getViewData(): array
    {
        $startDate = \Carbon\Carbon::parse($this->filterData['filter_start_date'])->startOfDay();
        $endDate = \Carbon\Carbon::parse($this->filterData['filter_end_date'])->endOfDay();
        
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        if ($daysInPeriod > 31) {
            $endDate = $startDate->copy()->addDays(30)->endOfDay();
            $daysInPeriod = 31;
        }
        
        $employeeQuery = \App\Models\Employee::where('is_active', 1)
            ->with(['position', 'department', 'branch']);
            
        if (!empty($this->filterData['filter_department_id'])) {
            $employeeQuery->where('department_id', $this->filterData['filter_department_id']);
        }
        
        if (!empty($this->filterData['filter_employee_id'])) {
            $employeeQuery->where('id', $this->filterData['filter_employee_id']);
        }
        
        // Only load employees that have attendances in the period (or load all filtered)
        // Wait, the previous logic only loaded employees who HAD attendances:
        $attendancesQuery = \App\Models\Attendance::whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);
        
        if (!empty($this->filterData['filter_department_id'])) {
            $attendancesQuery->whereHas('employee', fn($q) => $q->where('department_id', $this->filterData['filter_department_id']));
        }
        
        if (!empty($this->filterData['filter_employee_id'])) {
            $attendancesQuery->where('employee_id', $this->filterData['filter_employee_id']);
        }
        
        $attendances = $attendancesQuery->get()->groupBy('employee_id');
        
        $employeeIds = $attendances->keys();
        $employees = $employeeQuery->whereIn('id', $employeeIds)->get();
            
        return [
            'employees' => $employees,
            'attendances' => $attendances,
            'daysInPeriod' => $daysInPeriod,
            'startDate' => $startDate,
        ];
    }
    
    public function viewDetailsAction(): Action
    {
        return Action::make('viewDetails')
            ->modalHeading(fn (array $arguments) => 'Attendance Details - ' . $arguments['date'])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalWidth('2xl')
            ->modalContent(function (array $arguments) {
                $attendance = Attendance::where('employee_id', $arguments['employee_id'])
                    ->where('attendance_date', $arguments['date'])
                    ->with('employeeSchedule.workLocation')
                    ->first();
                    
                $logs = [];
                if ($attendance) {
                    $logs = AttendanceLog::where('attendance_id', $attendance->id)
                        ->orderBy('logged_at', 'asc')
                        ->get();
                }
                
                return View::make('filament.components.attendance-details-modal', [
                    'attendance' => $attendance,
                    'logs' => $logs,
                    'date' => $arguments['date'],
                ]);
            });
    }
}
