<?php

namespace App\Filament\Resources\LocationRequests;

use App\Filament\Resources\LocationRequests\Pages\CreateLocationRequest;
use App\Filament\Resources\LocationRequests\Pages\EditLocationRequest;
use App\Filament\Resources\LocationRequests\Pages\ListLocationRequests;
use App\Filament\Resources\LocationRequests\Pages\ViewLocationRequest;
use App\Filament\Resources\LocationRequests\Schemas\LocationRequestForm;
use App\Filament\Resources\LocationRequests\Tables\LocationRequestsTable;
use App\Models\LocationRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LocationRequestResource extends Resource
{
    protected static ?string $model = LocationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Request Lokasi Baru';

    public static function getNavigationBadge(): ?string
    {
        $count = LocationRequest::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return LocationRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocationRequests::route('/'),
            'create' => CreateLocationRequest::route('/create'),
            'view' => ViewLocationRequest::route('/{record}'),
            'edit' => EditLocationRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        return \App\Traits\ScopesUserData::applyUserAccessScope($query);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->can('view_work_locations');
    }
}
