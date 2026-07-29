<?php

namespace App\Filament\Imports;

use App\Models\EmployeeSchedule;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EmployeeScheduleImporter extends Importer
{
    protected static ?string $model = EmployeeSchedule::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('employee')
                ->relationship(resolveUsing: 'full_name')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('shift')
                ->relationship(resolveUsing: 'name'),
            ImportColumn::make('workLocation')
                ->relationship(resolveUsing: 'name'),
            ImportColumn::make('schedule_date')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('schedule_type')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('planned_start_at')
                ->rules(['datetime']),
            ImportColumn::make('planned_end_at')
                ->rules(['datetime']),
            ImportColumn::make('note'),
            ImportColumn::make('created_by')
                ->numeric()
                ->rules(['integer']),
        ];
    }

    public function resolveRecord(): EmployeeSchedule
    {
        return new EmployeeSchedule();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your employee schedule import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
