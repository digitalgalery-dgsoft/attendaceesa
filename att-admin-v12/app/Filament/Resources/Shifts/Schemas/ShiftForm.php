<?php

namespace App\Filament\Resources\Shifts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(fn () => 'SHF-' . strtoupper(\Illuminate\Support\Str::random(5)))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
                TimePicker::make('start_time')
                    ->required(),
                TimePicker::make('end_time')
                    ->required(),
                TimePicker::make('break_start_time'),
                TimePicker::make('break_end_time'),
                TextInput::make('grace_checkin_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('grace_checkout_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_cross_day')
                    ->required(),
                Toggle::make('required_checkin')
                    ->required(),
                Toggle::make('required_checkout')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
