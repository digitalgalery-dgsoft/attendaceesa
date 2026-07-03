<?php

namespace App\Filament\Imports;

use App\Models\Employee;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('user_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('company_id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('branch_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('department_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('position_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('supervisor_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('employee_no')
                ->requiredMapping()
                ->rules(['required', 'max:80']),
            ImportColumn::make('full_name')
                ->requiredMapping()
                ->rules(['required', 'max:150']),
            ImportColumn::make('gender'),
            ImportColumn::make('birth_date')
                ->rules(['date']),
            ImportColumn::make('join_date')
                ->rules(['date']),
            ImportColumn::make('resign_date')
                ->rules(['date']),
            ImportColumn::make('employment_status')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('phone')
                ->rules(['max:50']),
            ImportColumn::make('email')
                ->rules(['email', 'max:150']),
            ImportColumn::make('address'),
            ImportColumn::make('photo')
                ->rules(['max:255']),
            ImportColumn::make('is_active')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
        ];
    }

    public function resolveRecord(): Employee
    {
        return new Employee();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your employee import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
