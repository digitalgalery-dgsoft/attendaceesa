<?php

namespace App\Filament\Resources\SalesPipelines\Pages;

use App\Filament\Resources\SalesPipelines\SalesPipelineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesPipeline extends EditRecord
{
    protected static string $resource = SalesPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
