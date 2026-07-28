<?php

namespace App\Filament\Resources\EmployeeSchedules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EmployeeScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('shift_id')
                    ->relationship('shift', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('work_location_id')
                    ->relationship('workLocation', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('schedule_date')
                    ->required(),
                Select::make('schedule_type')
                    ->options([
            'workday' => 'Workday',
            'dayoff' => 'Dayoff',
            'holiday' => 'Holiday',
            'remote' => 'Remote',
            'field' => 'Field',
        ])
                    ->required(),
                Textarea::make('note')
                    ->columnSpanFull(),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
