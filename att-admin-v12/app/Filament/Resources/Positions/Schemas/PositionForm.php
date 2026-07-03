<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PositionForm
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
                    ->default(fn () => 'POS-' . strtoupper(\Illuminate\Support\Str::random(5)))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('level')
                    ->numeric(),
                Toggle::make('allow_offline_mode')
                    ->label('Allow Offline Mode'),
                TextInput::make('distance_lock_override')
                    ->label('Distance Lock Override (Meters)')
                    ->numeric()
                    ->placeholder('Leave blank to use global distance lock'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
