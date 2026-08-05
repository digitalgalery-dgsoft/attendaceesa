<?php

namespace App\Filament\Resources\WorkTargetResource\Pages;

use App\Filament\Resources\WorkTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkTarget extends EditRecord
{
    protected static string $resource = WorkTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
