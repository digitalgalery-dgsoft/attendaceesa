<?php

namespace App\Filament\Resources\ExtraHourResource\Pages;

use App\Filament\Resources\ExtraHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtraHours extends ListRecords
{
    protected static string $resource = ExtraHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
