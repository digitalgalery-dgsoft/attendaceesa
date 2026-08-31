<?php

namespace App\Filament\Resources\EmployeeSchedules\Pages;

use App\Exports\EmployeeScheduleTemplateExport;
use App\Filament\Resources\EmployeeSchedules\EmployeeScheduleResource;
use App\Imports\EmployeeScheduleImport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Principal;
use App\Models\Shift;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployeeSchedules extends ListRecords
{
    protected static string $resource = EmployeeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_schedule')
                ->label('Generate Schedule')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Select::make('principal_id')
                        ->label('Prinsiple (Opsional)')
                        ->options(function () {
                            $query = Principal::where('is_active', true)->orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->searchable()
                        ->placeholder('Pilih prinsiple untuk generate seluruh karyawannya')
                        ->live(),
                    Select::make('branch_id')
                        ->label('Area (Opsional)')
                        ->options(function () {
                            $query = Branch::orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->searchable()
                        ->placeholder('Pilih area untuk generate seluruh karyawannya')
                        ->live(),
                    Select::make('employee_ids')
                        ->label('Karyawan Tertentu (Opsional)')
                        ->multiple()
                        ->options(function ($get) {
                            $query = Employee::where('is_active', 1);
                            if (auth()->check()) {
                                $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                            }
                            if ($principalId = $get('principal_id')) {
                                $query->where('principal_id', $principalId);
                            }
                            if ($branchId = $get('branch_id')) {
                                $query->where('branch_id', $branchId);
                            }
                            return $query->orderBy('full_name')->pluck('full_name', 'id');
                        })
                        ->searchable()
                        ->placeholder('Atau pilih karyawan spesifik'),
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->default(Carbon::now()->startOfMonth()->toDateString())
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Tanggal Akhir')
                        ->default(Carbon::now()->endOfMonth()->toDateString())
                        ->required()
                        ->afterOrEqual('start_date'),
                    Select::make('shift_id')
                        ->label('Shift Kerja')
                        ->options(fn () => Shift::where('is_active', 1)->with('principal')->get()->mapWithKeys(fn ($s) => [$s->id => ($s->principal ? "[{$s->principal->name}] " : '') . $s->name]))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('work_location_id')
                        ->label('Lokasi Kerja')
                        ->options(WorkLocation::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('schedule_type')
                        ->label('Tipe Jadwal')
                        ->options([
                            'workday' => 'Workday',
                            'dayoff' => 'Dayoff',
                            'holiday' => 'Holiday',
                            'remote' => 'Remote',
                            'field' => 'Field',
                        ])
                        ->default('workday')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if (empty($data['principal_id']) && empty($data['branch_id']) && empty($data['employee_ids'])) {
                        Notification::make()
                            ->title('Validasi Gagal')
                            ->body('Pilih Prinsiple, Area, atau Karyawan spesifik terlebih dahulu.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $query = Employee::where('is_active', 1);
                    if (auth()->check()) {
                        $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                    }

                    if (!empty($data['employee_ids'])) {
                        $query->whereIn('id', $data['employee_ids']);
                    } else {
                        if (!empty($data['principal_id'])) {
                            $query->where('principal_id', $data['principal_id']);
                        }
                        if (!empty($data['branch_id'])) {
                            $query->where('branch_id', $data['branch_id']);
                        }
                    }

                    $employees = $query->get();

                    if ($employees->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada karyawan aktif ditemukan.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $startDate = Carbon::parse($data['start_date']);
                    $endDate = Carbon::parse($data['end_date']);
                    $shift = Shift::find($data['shift_id']);

                    foreach ($employees as $employee) {
                        $currentDate = $startDate->copy();
                        
                        while ($currentDate->lte($endDate)) {
                            $plannedStart = null;
                            $plannedEnd = null;
                            
                            if ($shift && $shift->start_time && $shift->end_time) {
                                $plannedStart = Carbon::parse($currentDate->toDateString() . ' ' . $shift->start_time);
                                $plannedEnd = Carbon::parse($currentDate->toDateString() . ' ' . $shift->end_time);
                                
                                if ($shift->is_cross_day ?? false) {
                                    $plannedEnd->addDay();
                                } elseif ($plannedEnd->lt($plannedStart)) {
                                    $plannedEnd->addDay();
                                }
                            }
                            
                            EmployeeSchedule::updateOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'schedule_date' => $currentDate->toDateString(),
                                ],
                                [
                                    'shift_id' => $data['shift_id'],
                                    'work_location_id' => $data['work_location_id'],
                                    'schedule_type' => $data['schedule_type'],
                                    'planned_start_at' => $plannedStart,
                                    'planned_end_at' => $plannedEnd,
                                    'created_by' => Auth::id(),
                                ]
                            );
                            
                            $currentDate->addDay();
                        }
                    }
                    
                    Notification::make()
                        ->title('Jadwal berhasil digenerate!')
                        ->body('Berhasil memproses jadwal untuk ' . $employees->count() . ' karyawan.')
                        ->success()
                        ->send();
                }),

            Action::make('import_schedule')
                ->label('Import Schedule (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import Jadwal Roster (Excel)')
                ->modalDescription('Unggah file Excel (.xlsx / .csv) berisi jadwal kerja karyawan. Mendukung format per-tanggal harian (kolom KTP, NAME, STORE CODE, STORE NAME, MONTH, YEAR, 1..31) maupun format rentang tanggal (nik, tanggal_mulai, tanggal_akhir, shift, lokasi_kerja).')
                ->form([
                    FileUpload::make('attachment')
                        ->label('File Excel (.xlsx / .csv)')
                        ->disk('public')
                        ->directory('schedule-imports')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'application/csv',
                        ]),
                ])
                ->action(function (array $data) {
                    $attachment = $data['attachment'];
                    if (Storage::disk('public')->exists($attachment)) {
                        $file = Storage::disk('public')->path($attachment);
                    } elseif (Storage::exists($attachment)) {
                        $file = Storage::path($attachment);
                    } else {
                        $file = storage_path('app/public/' . $attachment);
                    }

                    try {
                        $import = new EmployeeScheduleImport();
                        Excel::import($import, $file);

                        $msg = "Berhasil mengimpor {$import->importedCount} jadwal karyawan.";
                        if ($import->skippedCount > 0) {
                            $msg .= " ({$import->skippedCount} baris dilewati / bermasalah).";
                        }

                        $notif = Notification::make()
                            ->title('Import Jadwal Selesai')
                            ->body($msg);

                        if (!empty($import->errors)) {
                            $notif->body($msg . "\nCatatan: " . implode(', ', array_slice($import->errors, 0, 3)));
                            $notif->warning();
                        } else {
                            $notif->success();
                        }

                        $notif->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Import Jadwal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('download_template')
                ->label('Download Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Pilih Format Template Excel Jadwal')
                ->modalDescription('Pilih salah satu format template jadwal yang ingin Anda gunakan:')
                ->form([
                    Select::make('template_type')
                        ->label('Jenis Format Template')
                        ->options([
                            'matrix' => '1. Format Per Tanggal / Matrix Harian (Kolom 1..31 - Sangat Fleksibel)',
                            'range' => '2. Format Rentang Tanggal (Tanggal Mulai s/d Tanggal Akhir)',
                        ])
                        ->default('matrix')
                        ->required(),
                ])
                ->action(function (array $data) {
                    if ($data['template_type'] === 'range') {
                        return Excel::download(new \App\Exports\EmployeeScheduleRangeTemplateExport(), 'Template_Import_Jadwal_Rentang_Tanggal.xlsx');
                    }
                    return Excel::download(new \App\Exports\EmployeeScheduleMatrixTemplateExport(), 'Template_Import_Jadwal_Per_Tanggal_Matrix.xlsx');
                }),

            CreateAction::make(),
        ];
    }
}

