<?php

namespace App\Filament\Resources\CompetitorProducts;

use App\Filament\Resources\CompetitorProducts\Pages\CreateCompetitorProduct;
use App\Filament\Resources\CompetitorProducts\Pages\EditCompetitorProduct;
use App\Filament\Resources\CompetitorProducts\Pages\ListCompetitorProducts;
use App\Filament\Resources\CompetitorProducts\Schemas\CompetitorProductForm;
use App\Filament\Resources\CompetitorProducts\Tables\CompetitorProductsTable;
use App\Models\CompetitorProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CompetitorProductResource extends Resource
{
    protected static ?string $model = CompetitorProduct::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Produk Kompetitor';
    protected static ?string $modelLabel = 'Produk Kompetitor';
    protected static ?string $pluralModelLabel = 'Master Produk Kompetitor';

    protected static ?string $recordTitleAttribute = 'subbrand';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->can('view_products') || auth()->user()->can('view_any_products');
    }

    public static function form(Schema $schema): Schema
    {
        return CompetitorProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetitorProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompetitorProducts::route('/'),
            'create' => CreateCompetitorProduct::route('/create'),
            'edit' => EditCompetitorProduct::route('/{record}/edit'),
        ];
    }
}
