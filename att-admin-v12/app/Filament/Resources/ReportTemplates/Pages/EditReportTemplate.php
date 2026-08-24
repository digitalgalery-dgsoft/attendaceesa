<?php

namespace App\Filament\Resources\ReportTemplates\Pages;

use App\Filament\Resources\ReportTemplates\ReportTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportTemplate extends EditRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
