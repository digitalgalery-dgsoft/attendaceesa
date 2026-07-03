<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                    ->dehydrated(fn ($state) => filled($state)),
                \Filament\Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
                \Filament\Forms\Components\Select::make('employee_id')
                    ->label('Link to Employee')
                    ->options(\App\Models\Employee::pluck('full_name', 'id'))
                    ->searchable()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (\Filament\Forms\Components\Select $component, $record) {
                        if ($record && $record->employee) {
                            $component->state($record->employee->id);
                        }
                    }),
            ]);
    }
}
