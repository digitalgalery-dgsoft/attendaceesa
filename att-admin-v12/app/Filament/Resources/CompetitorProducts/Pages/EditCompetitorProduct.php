<?php

namespace App\Filament\Resources\CompetitorProducts\Pages;

use App\Filament\Resources\CompetitorProducts\CompetitorProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetitorProduct extends EditRecord
{
    protected static string $resource = CompetitorProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
