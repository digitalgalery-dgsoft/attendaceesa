<?php

namespace App\Filament\Resources\VisitReportResource\Pages;

use App\Filament\Resources\VisitReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitReports extends ListRecords
{
    protected static string $resource = VisitReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
