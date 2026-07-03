<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_id')
                    ->required()
                    ->numeric(),
                TextInput::make('employee_schedule_id')
                    ->numeric(),
                DatePicker::make('attendance_date')
                    ->required(),
                Select::make('status')
                    ->options([
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'permit' => 'Permit',
            'sick' => 'Sick',
            'leave' => 'Leave',
            'holiday' => 'Holiday',
            'dayoff' => 'Dayoff',
            'incomplete' => 'Incomplete',
        ])
                    ->required(),
                DateTimePicker::make('checkin_at'),
                DateTimePicker::make('checkout_at'),
                TextInput::make('checkin_log_id')
                    ->numeric(),
                TextInput::make('checkout_log_id')
                    ->numeric(),
                TextInput::make('work_duration_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('late_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('early_leave_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('overtime_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_manual_correction')
                    ->required(),
                Textarea::make('correction_note')
                    ->columnSpanFull(),
            ]);
    }
}
