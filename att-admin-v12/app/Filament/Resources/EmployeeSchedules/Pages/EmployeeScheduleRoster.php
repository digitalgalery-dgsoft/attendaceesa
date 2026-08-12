<?php

namespace App\Filament\Resources\EmployeeSchedules\Pages;

use App\Filament\Resources\EmployeeSchedules\EmployeeScheduleResource;
use Filament\Resources\Pages\Page;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Shift;
use App\Models\WorkLocation;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;

class EmployeeScheduleRoster extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string $resource = EmployeeScheduleResource::class;

    protected string $view = 'filament.pages.employee-schedule-roster';
    
    public ?array $filterData = [];
    
    public function mount()
    {
        $this->form->fill([
            'filter_start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'filter_end_date' => Carbon::now()->endOfMonth()->toDateString(),
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
                    Select::make('filter_department_id')
                        ->label('Department')
                        ->options(\App\Models\Department::pluck('name', 'id'))
                        ->searchable()
                        ->live(),
                    Select::make('filter_employee_id')
                        ->label('Employee')
                        ->options(function (callable $get) {
                            $deptId = $get('filter_department_id');
                            if ($deptId) {
                                return \App\Models\Employee::where('department_id', $deptId)->pluck('full_name', 'id');
                            }
                            return \App\Models\Employee::pluck('full_name', 'id');
                        })
                        ->searchable()
                        ->live(),
                ])->columnSpan(1)
            ])
            ->statePath('filterData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_schedule')
                ->label('Generate Schedule')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Select::make('department_id')
                        ->label('Department (Optional)')
                        ->options(Department::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Select Department to generate for all its employees'),
                    Select::make('employee_ids')
                        ->label('Employees (Optional)')
                        ->multiple()
                        ->options(Employee::where('is_active', 1)->pluck('full_name', 'id'))
                        ->searchable()
                        ->placeholder('Or select specific employees'),
                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->afterOrEqual('start_date'),
                    Select::make('shift_id')
                        ->label('Shift')
                        ->options(Shift::where('is_active', 1)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('work_location_id')
                        ->label('Work Location')
                        ->options(WorkLocation::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if (empty($data['department_id']) && empty($data['employee_ids'])) {
                        Notification::make()
                            ->title('Validation Error')
                            ->body('Please select a Department or specific Employees.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $employees = collect();
                    
                    if (!empty($data['department_id'])) {
                        $deptEmployees = Employee::where('department_id', $data['department_id'])->where('is_active', 1)->get();
                        $employees = $employees->merge($deptEmployees);
                    }
                    
                    if (!empty($data['employee_ids'])) {
                        $specificEmployees = Employee::whereIn('id', $data['employee_ids'])->where('is_active', 1)->get();
                        $employees = $employees->merge($specificEmployees);
                    }
                    
                    $employees = $employees->unique('id');
                    
                    if ($employees->isEmpty()) {
                        Notification::make()
                            ->title('No active employees found.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $startDate = Carbon::parse($data['start_date']);
                    $endDate = Carbon::parse($data['end_date']);
                    $shift = Shift::find($data['shift_id']);
                    
                    foreach ($employees as $employee) {
                        $currentDate = $startDate->copy();
                        
                        $workingDays = [];
                        if ($employee->department && is_array($employee->department->working_days)) {
                            $workingDays = $employee->department->working_days;
                        } else {
                            $workingDays = [1, 2, 3, 4, 5, 6, 7];
                        }
                        
                        while ($currentDate->lte($endDate)) {
                            $plannedStart = null;
                            $plannedEnd = null;
                            $scheduleType = 'dayoff';
                            $shiftIdToUse = null;
                            
                            $isoDay = $currentDate->dayOfWeekIso;
                            if (in_array($isoDay, $workingDays)) {
                                $scheduleType = 'workday';
                                $shiftIdToUse = $data['shift_id'];
                                
                                if ($shift && $shift->start_time && $shift->end_time) {
                                    $plannedStart = Carbon::parse($currentDate->toDateString() . ' ' . $shift->start_time);
                                    $plannedEnd = Carbon::parse($currentDate->toDateString() . ' ' . $shift->end_time);
                                    
                                    if ($shift->is_cross_day ?? false) {
                                        $plannedEnd->addDay();
                                    } elseif ($plannedEnd->lt($plannedStart)) {
                                        $plannedEnd->addDay();
                                    }
                                }
                            }
                            
                            EmployeeSchedule::updateOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'schedule_date' => $currentDate->toDateString(),
                                ],
                                [
                                    'shift_id' => $shiftIdToUse,
                                    'work_location_id' => $data['work_location_id'],
                                    'schedule_type' => $scheduleType,
                                    'planned_start_at' => $plannedStart,
                                    'planned_end_at' => $plannedEnd,
                                    'created_by' => Auth::id(),
                                ]
                            );
                            
                            $currentDate->addDay();
                        }
                    }
                    
                    $this->form->fill([
                        'filter_start_date' => $data['start_date'],
                        'filter_end_date' => $data['end_date'],
                    ]);
                    
                    Notification::make()
                        ->title('Schedules generated successfully')
                        ->success()
                        ->send();
                }),
        ];
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
        
        $schedules = \App\Models\EmployeeSchedule::whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('shift')
            ->get()
            ->groupBy('employee_id');
            
        $scheduledEmployeeIds = $schedules->keys()->toArray();
        
        $employeeQuery = \App\Models\Employee::where('is_active', 1)
            ->whereIn('id', $scheduledEmployeeIds)
            ->with(['position', 'department', 'branch']);
        
        if (!empty($this->filterData['filter_department_id'])) {
            $employeeQuery->where('department_id', $this->filterData['filter_department_id']);
        }
        
        if (!empty($this->filterData['filter_employee_id'])) {
            $employeeQuery->where('id', $this->filterData['filter_employee_id']);
        }
        
        $employees = $employeeQuery->get();
        
        return [
            'employees' => $employees,
            'schedules' => $schedules,
            'daysInPeriod' => $daysInPeriod,
            'startDate' => $startDate,
        ];
    }

    public function editScheduleAction(): Action
    {
        return Action::make('editSchedule')
            ->hiddenLabel()
            ->modalHeading('Edit Employee Schedule')
            ->form([
                \Filament\Forms\Components\Hidden::make('employee_id'),
                \Filament\Forms\Components\Hidden::make('schedule_date'),
                \Filament\Forms\Components\Select::make('schedule_type')
                    ->options([
                        'workday' => 'Workday',
                        'dayoff' => 'Dayoff',
                    ])
                    ->required()
                    ->live(),
                \Filament\Forms\Components\Select::make('shift_id')
                    ->label('Shift')
                    ->options(Shift::where('is_active', 1)->pluck('name', 'id'))
                    ->searchable()
                    ->required(fn (\Filament\Forms\Get $get) => $get('schedule_type') === 'workday'),
                \Filament\Forms\Components\Select::make('work_location_id')
                    ->label('Work Location')
                    ->options(WorkLocation::pluck('name', 'id'))
                    ->searchable()
                    ->required(fn (\Filament\Forms\Get $get) => $get('schedule_type') === 'workday'),
            ])
            ->fillForm(function (array $arguments): array {
                $schedule = EmployeeSchedule::where('employee_id', $arguments['employee_id'])
                    ->where('schedule_date', $arguments['schedule_date'])
                    ->first();
                return [
                    'employee_id' => $arguments['employee_id'],
                    'schedule_date' => $arguments['schedule_date'],
                    'schedule_type' => $schedule ? $schedule->schedule_type : 'dayoff',
                    'shift_id' => $schedule ? $schedule->shift_id : null,
                    'work_location_id' => $schedule ? $schedule->work_location_id : null,
                ];
            })
            ->action(function (array $data): void {
                $schedule = EmployeeSchedule::firstOrNew([
                    'employee_id' => $data['employee_id'],
                    'schedule_date' => $data['schedule_date'],
                ]);

                $schedule->schedule_type = $data['schedule_type'];
                $schedule->shift_id = $data['shift_id'] ?: null;
                $schedule->work_location_id = $data['work_location_id'] ?: null;

                if ($data['schedule_type'] === 'workday' && $data['shift_id']) {
                    $shift = Shift::find($data['shift_id']);
                    if ($shift && $shift->start_time && $shift->end_time) {
                        $plannedStart = Carbon::parse($data['schedule_date'] . ' ' . $shift->start_time);
                        $plannedEnd = Carbon::parse($data['schedule_date'] . ' ' . $shift->end_time);

                        if ($shift->is_cross_day ?? false) {
                            $plannedEnd->addDay();
                        } elseif ($plannedEnd->lt($plannedStart)) {
                            $plannedEnd->addDay();
                        }

                        $schedule->planned_start_at = $plannedStart;
                        $schedule->planned_end_at = $plannedEnd;
                    }
                } else {
                    $schedule->planned_start_at = null;
                    $schedule->planned_end_at = null;
                }

                $schedule->created_by = Auth::id();
                $schedule->save();

                Notification::make()
                    ->title('Schedule updated')
                    ->success()
                    ->send();
            });
    }
}
