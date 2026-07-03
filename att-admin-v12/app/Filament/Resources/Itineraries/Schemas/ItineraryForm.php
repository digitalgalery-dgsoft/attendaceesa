<?php

namespace App\Filament\Resources\Itineraries\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

class ItineraryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->required(),
                Radio::make('creation_type')
                    ->options([
                        'single' => 'Single Day',
                        'month' => 'Whole Month',
                    ])
                    ->default('single')
                    ->inline()
                    ->live()
                    ->hiddenOn('edit'),
                DatePicker::make('date')
                    ->required(fn (Get $get) => $get('creation_type') === 'single')
                    ->visible(fn (Get $get) => $get('creation_type') === 'single' || request()->routeIs('*.edit'))
                    ->default(now()),
                Select::make('month')
                    ->options([
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                    ])
                    ->required(fn (Get $get) => $get('creation_type') === 'month')
                    ->visible(fn (Get $get) => $get('creation_type') === 'month' && !request()->routeIs('*.edit'))
                    ->default(now()->month),
                Select::make('year')
                    ->options(
                        array_combine(
                            range(now()->year - 1, now()->year + 2),
                            range(now()->year - 1, now()->year + 2)
                        )
                    )
                    ->required(fn (Get $get) => $get('creation_type') === 'month')
                    ->visible(fn (Get $get) => $get('creation_type') === 'month' && !request()->routeIs('*.edit'))
                    ->default(now()->year),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('work_location_id')
                            ->relationship('workLocation', 'name')
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
            ]);
    }
}
