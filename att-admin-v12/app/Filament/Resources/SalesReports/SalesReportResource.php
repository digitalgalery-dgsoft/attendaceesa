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
    protected static ?string $navigationGroup = '4. Field Operations & Sales';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'client_name';

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Marketing';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        
        // Super Admin can view all
        if ($user->hasRole('Super Admin')) {
            return $query;
        }
        
        // If not super admin, check if employee exists and has a principal
        if ($user->employee && $user->employee->principal_id) {
            return $query->whereHas('employee', function ($q) use ($user) {
                $q->where('principal_id', $user->employee->principal_id);
            });
        }
        
        // Otherwise return empty result
        return $query->where('id', 0);
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
