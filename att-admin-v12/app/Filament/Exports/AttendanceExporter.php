<?php

namespace App\Filament\Exports;

use App\Models\Attendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AttendanceExporter extends Exporter
{
    protected static ?string $model = Attendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('employee.first_name')->label('First Name'),
            ExportColumn::make('employee.last_name')->label('Last Name'),
            ExportColumn::make('attendance_date')->label('Date'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('checkin_at')->label('Check-In Time'),
            ExportColumn::make('checkout_at')->label('Check-Out Time'),
            ExportColumn::make('work_duration_minutes')->label('Work Duration (Mins)'),
            ExportColumn::make('late_minutes')->label('Late (Mins)'),
            ExportColumn::make('early_leave_minutes')->label('Early Leave (Mins)'),
            ExportColumn::make('overtime_minutes')->label('Overtime (Mins)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your attendance export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
