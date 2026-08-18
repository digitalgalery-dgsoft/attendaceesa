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
                Select::make('principal_id')
                    ->relationship('principal', 'name')
                    ->label('Company')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(fn () => 'POS-' . strtoupper(\Illuminate\Support\Str::random(5)))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('allow_offline_mode')
                    ->label('Allow Offline Mode'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
