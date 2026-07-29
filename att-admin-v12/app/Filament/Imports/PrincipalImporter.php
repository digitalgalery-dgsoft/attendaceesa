<?php

namespace App\Filament\Imports;

use App\Models\Principal;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class PrincipalImporter extends Importer
{
    protected static ?string $model = Principal::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('company')
                ->relationship(resolveUsing: 'name')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:150']),
            ImportColumn::make('description'),
        ];
    }

    public function resolveRecord(): Principal
    {
        return new Principal();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your principal import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
