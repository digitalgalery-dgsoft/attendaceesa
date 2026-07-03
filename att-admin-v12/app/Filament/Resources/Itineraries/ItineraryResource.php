<?php

namespace App\Filament\Resources\Itineraries;

use App\Filament\Resources\Itineraries\Pages\CreateItinerary;
use App\Filament\Resources\Itineraries\Pages\EditItinerary;
use App\Filament\Resources\Itineraries\Pages\ListItineraries;
use App\Filament\Resources\Itineraries\Schemas\ItineraryForm;
use App\Filament\Resources\Itineraries\Tables\ItinerariesTable;
use App\Models\Itinerary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ItineraryResource extends Resource
{
    protected static ?string $model = Itinerary::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';
    protected static string|\UnitEnum|null $navigationGroup = 'Attendance';

    public static function form(Schema $schema): Schema
    {
        return ItineraryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItinerariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItineraries::route('/'),
            'create' => CreateItinerary::route('/create'),
            'edit' => EditItinerary::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->can('view_itineraries');
    }
}

