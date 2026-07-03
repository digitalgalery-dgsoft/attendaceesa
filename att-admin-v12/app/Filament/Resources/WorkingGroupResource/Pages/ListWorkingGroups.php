<?php

namespace App\Filament\Resources\WorkingGroupResource\Pages;

use App\Filament\Resources\WorkingGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkingGroups extends ListRecords
{
    protected static string $resource = WorkingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
