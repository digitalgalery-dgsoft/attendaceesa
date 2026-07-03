<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('User Account')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->maxLength(255),
                    ]),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required()
                    ->live(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Area'),
                Select::make('department_id')
                    ->relationship('department', 'name', fn (Builder $query, $get) => $query->where('company_id', $get('company_id')))
                    ->label('Department'),
                Select::make('position_id')
                    ->relationship('position', 'name')
                    ->label('Position'),
                Select::make('supervisor_id')
                    ->relationship('supervisor', 'full_name')
                    ->label('Supervisor'),
                TextInput::make('employee_no')
                    ->required(),
                TextInput::make('full_name')
                    ->required(),
                Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female']),
                DatePicker::make('birth_date'),
                DatePicker::make('join_date'),
                DatePicker::make('resign_date'),
                Select::make('employment_status')
                    ->options([
            'permanent' => 'Permanent',
            'contract' => 'Contract',
            'probation' => 'Probation',
            'intern' => 'Intern',
            'resigned' => 'Resigned',
        ])
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('address')
                    ->columnSpanFull(),
                FileUpload::make('photo')
                    ->image()
                    ->directory('employees'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
