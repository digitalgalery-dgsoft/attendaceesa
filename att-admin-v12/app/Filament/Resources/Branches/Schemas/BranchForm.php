<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(fn () => 'BRN-' . strtoupper(Str::random(5)))
                    ->afterStateHydrated(fn (TextInput $component, ?string $state) => empty($state) ? $component->state('BRN-' . strtoupper(Str::random(5))) : null)
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('region')
                    ->options([
                        'Region 1' => 'Region 1',
                        'Region 2' => 'Region 2',
                        'Region 3' => 'Region 3',
                        'Region 4' => 'Region 4',
                        'Region 5' => 'Region 5',
                        'Region 6' => 'Region 6',
                        'Region 7' => 'Region 7',
                    ]),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
