<?php

namespace App\Filament\Resources\LocationRequests\Pages;

use App\Filament\Resources\LocationRequests\LocationRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLocationRequest extends ViewRecord
{
    protected static string $resource = LocationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
