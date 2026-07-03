<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
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
                    ->default(fn () => 'DEP-' . strtoupper(\Illuminate\Support\Str::random(5)))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label('Parent Department'),
                Toggle::make('is_active')
                    ->default(true),
                CheckboxList::make('working_days')
                    ->options([
                        '1' => 'Monday',
                        '2' => 'Tuesday',
                        '3' => 'Wednesday',
                        '4' => 'Thursday',
                        '5' => 'Friday',
                        '6' => 'Saturday',
                        '0' => 'Sunday',
                    ])
                    ->default(['1', '2', '3', '4', '5'])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
