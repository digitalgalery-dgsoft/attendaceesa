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
                    ->searchable()
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
                            ->maxLength(255)
                            ->default('123456')
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state)),
                    ]),
                Select::make('principal_id')
                    ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                    ->label('Prinsiple')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Area'),
                Select::make('department_id')
                    ->relationship('department', 'name', modifyQueryUsing: function ($query, $get) {
                        $principalId = $get('principal_id');
                        if ($principalId) {
                            $query->where('principal_id', $principalId);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->label('Department'),
                Select::make('position_id')
                    ->relationship('position', 'name', modifyQueryUsing: function ($query, $get) {
                        $principalId = $get('principal_id');
                        if ($principalId) {
                            $query->where('principal_id', $principalId);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->label('Position'),
                Select::make('supervisor_id')
                    ->relationship('supervisor', 'full_name')
                    ->searchable()
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
                    ->label('Foto Master Wajah / Profil (Face Recognition Reference)')
                    ->helperText('Foto wajah utama karyawan sebagai referensi verifikasi Face Recognition AI saat absensi.')
                    ->image()
                    ->disk('public')
                    ->directory('employees'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
