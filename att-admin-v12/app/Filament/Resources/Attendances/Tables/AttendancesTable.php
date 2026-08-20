<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\ExportAction as ExcelExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Columns\Column;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_schedule_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('checkin_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('checkout_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('checkin_log_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checkout_log_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('work_duration_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('early_leave_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_minutes')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_manual_correction')
                    ->label('Import / Adj')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-device-phone-mobile')
                    ->trueColor('purple')
                    ->falseColor('gray')
                    ->tooltip(fn (\App\Models\Attendance $record): string => $record->is_manual_correction ? ('Hasil Import: ' . ($record->correction_note ?? 'Manual')) : 'Check-in Aplikasi Mobile'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->label('Employee')
                    ->searchable()
                    ->preload(),
                Filter::make('attendance_date')
                    ->form([
                        DatePicker::make('date_from'),
                        DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('attendance_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('attendance_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'leave' => 'Leave',
                        'half_day' => 'Half Day',
                    ])
            ])
            ->recordActions([
                Action::make('view_route')
                    ->label('Lihat Rute')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn (\App\Models\Attendance $record): string => \App\Filament\Resources\Attendances\AttendanceResource::getUrl('view-route', ['record' => $record])),
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('import_attendance')
                    ->label('Import Attendance (Excel)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Import Data Attendance (Penyesuaian Absensi)')
                    ->modalDescription('Unggah file Excel absensi untuk mengisi check-in/out karyawan yang tidak check-in / ALPHA / kosong. Data check-in asli aplikasi tidak akan tertimpa.')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('Pilih File Excel (.xlsx / .xls)')
                            ->disk('local')
                            ->directory('temp-imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'application/octet-stream',
                            ])
                            ->required()
                            ->helperText('Gunakan template resmi untuk format kolom NIK, tanggal, dan jam.'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);

                            $import = new \App\Imports\AttendanceImport();
                            \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);

                            if (file_exists($filePath)) {
                                @unlink($filePath);
                            }

                            $msg = "Berhasil mengimpor/menyesuaikan {$import->importedCount} data absensi.";
                            if ($import->protectedCount > 0) {
                                $msg .= " ({$import->protectedCount} tanggal dilewati karena sudah ada check-in asli).";
                            }
                            if ($import->skippedCount > $import->protectedCount) {
                                $msg .= " (" . ($import->skippedCount - $import->protectedCount) . " baris dilewati karena NIK/akses tidak sesuai).";
                            }

                            if (!empty($import->errors)) {
                                $errorSummary = implode("<br>", array_slice($import->errors, 0, 5));
                                if (count($import->errors) > 5) {
                                    $errorSummary .= "<br>...dan " . (count($import->errors) - 5) . " kendala/info lainnya.";
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title('Import Absensi Selesai dengan Catatan')
                                    ->warning()
                                    ->body($msg . '<br><br><strong>Detail:</strong><br>' . $errorSummary)
                                    ->persistent()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Import Absensi Berhasil')
                                    ->success()
                                    ->body($msg)
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Import Absensi')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('download_attendance_template')
                    ->label('Download Template Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AttendanceImportTemplateExport(), 'Template_Import_Attendance.xlsx');
                    }),

                // Excel Export
                ExcelExportAction::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->exports([
                        ExcelExport::make('table')->withColumns([
                            Column::make('employee.full_name')->heading('Employee'),
                            Column::make('attendance_date')->heading('Date'),
                            Column::make('status')->heading('Status'),
                            Column::make('checkin_at')->heading('Check-in At'),
                            Column::make('checkout_at')->heading('Check-out At'),
                            Column::make('work_duration_minutes')->heading('Work Duration (Mins)'),
                            Column::make('late_minutes')->heading('Late (Mins)'),
                            Column::make('early_leave_minutes')->heading('Early Leave (Mins)'),
                            Column::make('overtime_minutes')->heading('Overtime (Mins)'),
                        ]),
                    ]),
                    
                // PDF Export
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredTableQuery();
                        $attendances = $query->with('employee')->get();
                        
                        $pdf = Pdf::loadView('pdf.attendance-report', ['attendances' => $attendances]);
                        $filename = 'attendance-report-' . date('Y-m-d-His') . '.pdf';
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename);
                    }),
                    
                // Original CSV Export (Optional, we can comment it out if not needed)
                // \Filament\Actions\ExportAction::make()
                //     ->exporter(\App\Filament\Exports\AttendanceExporter::class),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
