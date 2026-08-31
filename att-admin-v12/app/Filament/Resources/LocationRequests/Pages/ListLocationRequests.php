<?php

namespace App\Filament\Resources\LocationRequests\Pages;

use App\Filament\Resources\LocationRequests\LocationRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocationRequests extends ListRecords
{
    protected static string $resource = LocationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pengajuan Baru'),
        ];
    }
}
