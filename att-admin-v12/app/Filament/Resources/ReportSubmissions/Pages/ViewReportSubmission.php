<?php

namespace App\Filament\Resources\ReportSubmissions\Pages;

use App\Filament\Resources\ReportSubmissions\ReportSubmissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReportSubmission extends ViewRecord
{
    protected static string $resource = ReportSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
