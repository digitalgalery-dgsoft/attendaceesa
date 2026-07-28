<?php

namespace App\Filament\Resources\SalesPipelines;

use App\Filament\Resources\SalesPipelines\Pages\CreateSalesPipeline;
use App\Filament\Resources\SalesPipelines\Pages\EditSalesPipeline;
use App\Filament\Resources\SalesPipelines\Pages\ListSalesPipelines;
use App\Filament\Resources\SalesPipelines\Schemas\SalesPipelineForm;
use App\Filament\Resources\SalesPipelines\Tables\SalesPipelinesTable;
use App\Models\SalesPipeline;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesPipelineResource extends Resource
{
    protected static ?string $model = SalesPipeline::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Marketing';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return SalesPipelineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesPipelinesTable::configure($table);
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
            'index' => ListSalesPipelines::route('/'),
            'create' => CreateSalesPipeline::route('/create'),
            'edit' => EditSalesPipeline::route('/{record}/edit'),
        ];
    }
}
