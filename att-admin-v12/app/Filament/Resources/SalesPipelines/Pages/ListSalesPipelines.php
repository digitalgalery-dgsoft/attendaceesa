<?php

namespace App\Filament\Resources\SalesPipelines\Pages;

use App\Filament\Resources\SalesPipelines\SalesPipelineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesPipelines extends ListRecords
{
    protected static string $resource = SalesPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
