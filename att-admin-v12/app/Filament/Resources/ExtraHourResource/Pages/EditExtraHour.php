<?php

namespace App\Filament\Resources\ExtraHourResource\Pages;

use App\Filament\Resources\ExtraHourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtraHour extends EditRecord
{
    protected static string $resource = ExtraHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
