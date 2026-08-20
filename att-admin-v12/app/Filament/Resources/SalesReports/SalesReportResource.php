<?php

namespace App\Filament\Resources\SalesReports;

use App\Filament\Resources\SalesReports\Pages\CreateSalesReport;
use App\Filament\Resources\SalesReports\Pages\EditSalesReport;
use App\Filament\Resources\SalesReports\Pages\ListSalesReports;
use App\Filament\Resources\SalesReports\Pages\ViewSalesReport;
use App\Filament\Resources\SalesReports\Schemas\SalesReportForm;
use App\Filament\Resources\SalesReports\Schemas\SalesReportInfolist;
use App\Filament\Resources\SalesReports\Tables\SalesReportsTable;
use App\Models\SalesReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesReportResource extends Resource
{
    protected static ?string $model = SalesReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Field Operations & Sales';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Sales Reports';

    protected static ?string $recordTitleAttribute = 'client_name';

    public static function canViewAny(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('view_sales_reports'));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return \App\Traits\ScopesUserData::applyUserAccessScope($query);
    }

    public static function form(Schema $schema): Schema
    {
        return SalesReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesReportsTable::configure($table);
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
            'index' => ListSalesReports::route('/'),
            'create' => CreateSalesReport::route('/create'),
            'view' => ViewSalesReport::route('/{record}'),
            'edit' => EditSalesReport::route('/{record}/edit'),
        ];
    }
}
