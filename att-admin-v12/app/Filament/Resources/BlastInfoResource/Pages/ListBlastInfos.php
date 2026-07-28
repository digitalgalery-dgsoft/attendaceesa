<?php

namespace App\Filament\Resources\BlastInfoResource\Pages;

use App\Filament\Resources\BlastInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastInfos extends ListRecords
{
    protected static string $resource = BlastInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
