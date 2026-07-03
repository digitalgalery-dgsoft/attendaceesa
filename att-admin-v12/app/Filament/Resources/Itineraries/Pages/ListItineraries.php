<?php

namespace App\Filament\Resources\Itineraries\Pages;

use App\Filament\Resources\Itineraries\ItineraryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Filament\Actions\Action;
use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Department;

class ListItineraries extends ListRecords
{
    protected static string $resource = ItineraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('create_for_group')
                ->label('Create for Department')
                ->icon('heroicon-o-users')
                ->color('success')
                ->form([
                    Select::make('department_id')
                        ->options(\App\Models\Department::pluck('name', 'id'))
                        ->required()
                        ->label('Department'),
                    Select::make('month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->required()
                        ->default(now()->month),
                    Select::make('year')
                        ->options(
                            array_combine(
                                range(now()->year - 1, now()->year + 2),
                                range(now()->year - 1, now()->year + 2)
                            )
                        )
                        ->required()
                        ->default(now()->year),
                    Repeater::make('items')
                        ->schema([
                            Select::make('work_location_id')
                                ->options(\App\Models\WorkLocation::pluck('name', 'id'))
                                ->required(),
                            TextInput::make('sequence')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            Textarea::make('notes')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                ])
                ->action(function (array $data) {
                    $department = Department::find($data['department_id']);
                    $employees = Employee::where('department_id', $data['department_id'])->get();
                    if ($employees->isEmpty()) {
                        Notification::make()->title('No employees in this department')->warning()->send();
                        return;
                    }

                    $workingDays = $department->working_days ?? ['1', '2', '3', '4', '5']; // Default Mon-Fri
                    $year = (int) $data['year'];
                    $month = (int) $data['month'];
                    $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
                    
                    // Get national holidays for this month
                    $holidays = Holiday::whereYear('holiday_date', $year)
                        ->whereMonth('holiday_date', $month)
                        ->pluck('holiday_date')
                        ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                        ->toArray();

                    DB::transaction(function () use ($employees, $data, $workingDays, $year, $month, $daysInMonth, $holidays) {
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $currentDate = Carbon::create($year, $month, $day);
                            
                            // Check if weekend/off day for department
                            if (!in_array((string) $currentDate->dayOfWeek, $workingDays)) {
                                continue;
                            }
                            
                            // Check if national holiday
                            if (in_array($currentDate->format('Y-m-d'), $holidays)) {
                                continue;
                            }

                            foreach ($employees as $employee) {
                                $itinerary = Itinerary::create([
                                    'employee_id' => $employee->id,
                                    'date' => $currentDate->format('Y-m-d'),
                                    'status' => 'draft',
                                ]);

                                if (isset($data['items']) && is_array($data['items'])) {
                                    foreach ($data['items'] as $item) {
                                        ItineraryItem::create([
                                            'itinerary_id' => $itinerary->id,
                                            'work_location_id' => $item['work_location_id'],
                                            'sequence' => $item['sequence'],
                                            'notes' => $item['notes'] ?? null,
                                        ]);
                                    }
                                }
                            }
                        }
                    });

                    Notification::make()->title('Itineraries created successfully for the selected month!')->success()->send();
                }),
            Action::make('create_for_working_group')
                ->label('Create for Working Group')
                ->icon('heroicon-o-users')
                ->color('info')
                ->form([
                    Select::make('working_group_id')
                        ->options(\App\Models\WorkingGroup::pluck('name', 'id'))
                        ->required()
                        ->label('Working Group'),
                    Select::make('month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->required()
                        ->default(now()->month),
                    Select::make('year')
                        ->options(
                            array_combine(
                                range(now()->year - 1, now()->year + 2),
                                range(now()->year - 1, now()->year + 2)
                            )
                        )
                        ->required()
                        ->default(now()->year),
                    Repeater::make('items')
                        ->schema([
                            Select::make('work_location_id')
                                ->options(\App\Models\WorkLocation::pluck('name', 'id'))
                                ->required(),
                            TextInput::make('sequence')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            Textarea::make('notes')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                ])
                ->action(function (array $data) {
                    $workingGroup = \App\Models\WorkingGroup::with('members.employee')->find($data['working_group_id']);
                    $members = $workingGroup->members;
                    
                    if ($members->isEmpty()) {
                        Notification::make()->title('No employees in this working group')->warning()->send();
                        return;
                    }

                    // By default, working group works Mon-Fri unless specified in rules, but we'll use a standard schedule
                    $workingDays = ['1', '2', '3', '4', '5']; 
                    $year = (int) $data['year'];
                    $month = (int) $data['month'];
                    $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
                    
                    // Get national holidays for this month
                    $holidays = Holiday::whereYear('holiday_date', $year)
                        ->whereMonth('holiday_date', $month)
                        ->pluck('holiday_date')
                        ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                        ->toArray();

                    DB::transaction(function () use ($members, $data, $workingDays, $year, $month, $daysInMonth, $holidays) {
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $currentDate = Carbon::create($year, $month, $day);
                            
                            // Check if weekend/off day
                            if (!in_array((string) $currentDate->dayOfWeek, $workingDays)) {
                                continue;
                            }
                            
                            // Check if national holiday
                            if (in_array($currentDate->format('Y-m-d'), $holidays)) {
                                continue;
                            }

                            foreach ($members as $member) {
                                if (!$member->employee_id) continue;
                                
                                $itinerary = Itinerary::create([
                                    'employee_id' => $member->employee_id,
                                    'date' => $currentDate->format('Y-m-d'),
                                    'status' => 'draft',
                                ]);

                                if (isset($data['items']) && is_array($data['items'])) {
                                    foreach ($data['items'] as $item) {
                                        ItineraryItem::create([
                                            'itinerary_id' => $itinerary->id,
                                            'work_location_id' => $item['work_location_id'],
                                            'sequence' => $item['sequence'],
                                            'notes' => $item['notes'] ?? null,
                                        ]);
                                    }
                                }
                            }
                        }
                    });

                    Notification::make()->title('Itineraries created successfully for the Working Group!')->success()->send();
                }),
        ];
    }
}
