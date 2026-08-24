<?php

namespace App\Filament\Imports;

use App\Models\WorkLocation;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class WorkLocationImporter extends Importer
{
    protected static ?string $model = WorkLocation::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('principal')
                ->relationship(resolveUsing: 'name')
                ->label('Prinsiple')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('company')
                ->relationship(resolveUsing: 'name')
                ->label('Company'),
            ImportColumn::make('branch')
                ->relationship(resolveUsing: 'name'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:150']),
            ImportColumn::make('type')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('region')
                ->rules(['max:255']),
            ImportColumn::make('area')
                ->rules(['max:255']),
            ImportColumn::make('sub_area')
                ->rules(['max:255']),
            ImportColumn::make('channel')
                ->rules(['max:255']),
            ImportColumn::make('account')
                ->rules(['max:255']),
            ImportColumn::make('timezone')
                ->rules(['max:255']),
            ImportColumn::make('address'),
            ImportColumn::make('latitude')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('longitude')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('radius_meter')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('is_active')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required']),
        ];
    }

    public function resolveRecord(): WorkLocation
    {
        return new WorkLocation();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your work location import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
