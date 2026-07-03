<?php

namespace App\Filament\Exports;

use App\Models\VisitReport;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class VisitReportExporter extends Exporter
{
    protected static ?string $model = VisitReport::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('itineraryItem.itinerary.employee.first_name')->label('Employee'),
            ExportColumn::make('itineraryItem.workLocation.name')->label('Store'),
            ExportColumn::make('issue_type')->label('Issue Type'),
            ExportColumn::make('action')->label('Action'),
            ExportColumn::make('target')->label('Target'),
            ExportColumn::make('actual')->label('Actual'),
            ExportColumn::make('deadline')->label('Deadline'),
            ExportColumn::make('notes')->label('Notes'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your visit report export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
