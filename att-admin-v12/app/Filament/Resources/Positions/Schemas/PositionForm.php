<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('principal_id')
                    ->relationship('principal', 'name')
                    ->label('Prinsiple')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(fn () => 'POS-' . strtoupper(Str::random(5)))
                    ->afterStateHydrated(fn (TextInput $component, ?string $state) => empty($state) ? $component->state('POS-' . strtoupper(Str::random(5))) : null)
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('allow_offline_mode')
                    ->label('Allow Offline Mode'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
