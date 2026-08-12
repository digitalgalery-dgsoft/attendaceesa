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
                Select::make('companies')
                    ->relationship('companies', 'name')
                    ->multiple()
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
                Toggle::make('has_sales_reporting')
                    ->label('Enable Sales Reporting Module')
                    ->default(false),
                TextInput::make('cutoff_start_date')
                    ->label('Tanggal Mulai Cut Off (Misal: 1, 21, atau 26)')
                    ->numeric()
                    ->default(26)
                    ->minValue(1)
                    ->maxValue(31)
                    ->required(),
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
