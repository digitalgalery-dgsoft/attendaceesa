<?php

namespace App\Filament\Resources\EmployeeSchedules\Pages;

use App\Filament\Resources\EmployeeSchedules\EmployeeScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeSchedules extends ListRecords
{
    protected static string $resource = EmployeeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate_schedule')
                ->label('Generate Schedule')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('employee_ids')
                        ->label('Employees')
                        ->multiple()
                        ->options(\App\Models\Employee::where('is_active', 1)->pluck('full_name', 'id'))
                        ->searchable()
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->afterOrEqual('start_date'),
                    \Filament\Forms\Components\Select::make('shift_id')
                        ->label('Shift')
                        ->options(\App\Models\Shift::where('is_active', 1)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    \Filament\Forms\Components\Select::make('work_location_id')
                        ->label('Work Location')
                        ->options(\App\Models\WorkLocation::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    \Filament\Forms\Components\Select::make('schedule_type')
                        ->label('Schedule Type')
                        ->options([
                            'workday' => 'Workday',
                            'dayoff' => 'Dayoff',
                            'holiday' => 'Holiday',
                            'remote' => 'Remote',
                            'field' => 'Field',
                        ])
                        ->default('workday')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $startDate = \Carbon\Carbon::parse($data['start_date']);
                    $endDate = \Carbon\Carbon::parse($data['end_date']);
                    
                    foreach ($data['employee_ids'] as $employeeId) {
                        $currentDate = $startDate->copy();
                        
                        while ($currentDate->lte($endDate)) {
                            $shift = \App\Models\Shift::find($data['shift_id']);
                            
                            $plannedStart = null;
                            $plannedEnd = null;
                            
                            if ($shift && $shift->start_time && $shift->end_time) {
                                $plannedStart = \Carbon\Carbon::parse($currentDate->toDateString() . ' ' . $shift->start_time);
                                $plannedEnd = \Carbon\Carbon::parse($currentDate->toDateString() . ' ' . $shift->end_time);
                                
                                if ($shift->is_cross_day ?? false) {
                                    $plannedEnd->addDay();
                                } elseif ($plannedEnd->lt($plannedStart)) {
                                    $plannedEnd->addDay();
                                }
                            }
                            
                            \App\Models\EmployeeSchedule::updateOrCreate(
                                [
                                    'employee_id' => $employeeId,
                                    'schedule_date' => $currentDate->toDateString(),
                                ],
                                [
                                    'shift_id' => $data['shift_id'],
                                    'work_location_id' => $data['work_location_id'],
                                    'schedule_type' => $data['schedule_type'],
                                    'planned_start_at' => $plannedStart,
                                    'planned_end_at' => $plannedEnd,
                                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                                ]
                            );
                            
                            $currentDate->addDay();
                        }
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Schedules generated successfully')
                        ->success()
                        ->send();
                }),
            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\EmployeeScheduleImporter::class),
            CreateAction::make(),
        ];
    }
}
