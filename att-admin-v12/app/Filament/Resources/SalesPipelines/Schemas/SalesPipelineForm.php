<?php

namespace App\Filament\Resources\SalesPipelines\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SalesPipelineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->required(),
                TextInput::make('lead_name')
                    ->required(),
                TextInput::make('lead_company'),
                TextInput::make('contact_info'),
                Select::make('stage')
                    ->options([
                        'prospecting' => 'Prospecting',
                        'negotiation' => 'Negotiation',
                        'closed_won' => 'Closed Won',
                        'closed_lost' => 'Closed Lost',
                    ])
                    ->required()
                    ->default('prospecting'),
                TextInput::make('expected_revenue')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('Rp'),
                TextInput::make('probability')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->suffix('%'),
                DatePicker::make('expected_close_date'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
