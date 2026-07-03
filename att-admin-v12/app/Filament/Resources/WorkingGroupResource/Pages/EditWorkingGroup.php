<?php

namespace App\Filament\Resources\WorkingGroupResource\Pages;

use App\Filament\Resources\WorkingGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkingGroup extends EditRecord
{
    protected static string $resource = WorkingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
