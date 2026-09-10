<?php

namespace App\Filament\Resources\CompetitorProducts\Pages;

use App\Filament\Resources\CompetitorProducts\CompetitorProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompetitorProducts extends ListRecords
{
    protected static string $resource = CompetitorProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
