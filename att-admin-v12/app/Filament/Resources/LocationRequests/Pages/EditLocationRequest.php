<?php

namespace App\Filament\Resources\LocationRequests\Pages;

use App\Filament\Resources\LocationRequests\LocationRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLocationRequest extends EditRecord
{
    protected static string $resource = LocationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
