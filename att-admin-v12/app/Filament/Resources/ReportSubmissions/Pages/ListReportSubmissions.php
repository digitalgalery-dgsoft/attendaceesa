<?php

namespace App\Filament\Resources\ReportSubmissions\Pages;

use App\Filament\Resources\ReportSubmissions\ReportSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListReportSubmissions extends ListRecords
{
    protected static string $resource = ReportSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
