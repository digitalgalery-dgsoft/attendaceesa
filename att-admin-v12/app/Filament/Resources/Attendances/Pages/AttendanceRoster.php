<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Exports\AttendanceImportTemplateExport;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Imports\AttendanceImport;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Principal;
use App\Models\TrackingHistory;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceRoster extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AttendanceResource::class;
    protected string $view = 'filament.pages.attendance-roster';

    public $filterData = [];
    public ?string $search = '';
    public int $page = 1;
    public int $perPage = 25;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function boot(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function mount(): void
    {
        @ini_set('memory_limit', '512M');
        $this->form->fill([
            'filter_start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'filter_end_date' => Carbon::now()->endOfMonth()->toDateString(),
            'filter_branch_id' => null,
            'filter_principal_id' => null,
            'filter_employee_id' => null,
        ]);
        $this->search = '';
        $this->page = 1;
        $this->perPage = 25;
    }

    public function rendering(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function updatedFilterData(): void
    {
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(5)->schema([
                    DatePicker::make('filter_start_date')
                        ->label('Tanggal Mulai')
                        ->live()
                        ->required(),
                    DatePicker::make('filter_end_date')
                        ->label('Tanggal Akhir')
                        ->live()
                        ->afterOrEqual('filter_start_date')
                        ->required(),
                    Select::make('filter_branch_id')
                        ->label('Region / Area')
                        ->options(function () {
                            $query = Branch::orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
                            }
                            return $query->pluck('name', 'id')->toArray();
                        })
                        ->placeholder('Semua Region')
                        ->live(),
                    Select::make('filter_principal_id')
                        ->label('Prinsiple')
                        ->options(function () {
                            $query = Principal::where('is_active', true)->orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                            }
                            return $query->pluck('name', 'id')->toArray();
                        })
                        ->placeholder('Semua Prinsiple')
                        ->live(),
                    Select::make('filter_employee_id')
                        ->label('Karyawan Spesifik')
                        ->options(function () {
                            $query = Employee::where('is_active', 1)->with(['position', 'branch']);
                            if (auth()->check()) {
                                $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                            }
                            return $query->orderBy('full_name')->get()->mapWithKeys(function ($emp) {
                                $pos = $emp->position?->name ?? 'Staff';
                                $area = $emp->branch?->name ?? '-';
                                return [$emp->id => "{$emp->full_name} ({$pos} - {$area})"];
                            })->toArray();
                        })
                        ->placeholder('Semua Karyawan')
                        ->searchable()
                        ->live(),
                ])
            ])
            ->statePath('filterData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_attendance')
                ->label('Import Attendance (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import Data Attendance (Penyesuaian Absensi)')
                ->modalDescription('Unggah file Excel absensi untuk mengisi check-in/out karyawan yang tidak check-in / ALPHA / kosong. Data check-in asli aplikasi tidak akan tertimpa.')
                ->form([
                    FileUpload::make('file')
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
                        $filePath = Storage::disk('local')->path($data['file']);

                        $import = new AttendanceImport();
                        Excel::import($import, $filePath);

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
                            Notification::make()
                                ->title('Import Absensi Selesai dengan Catatan')
                                ->warning()
                                ->body($msg . '<br><br><strong>Detail:</strong><br>' . $errorSummary)
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Absensi Berhasil')
                                ->success()
                                ->body($msg)
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
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
                    return Excel::download(new AttendanceImportTemplateExport(), 'Template_Import_Attendance.xlsx');
                }),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $startDate = Carbon::parse($this->filterData['filter_start_date'] ?? Carbon::now()->startOfMonth()->toDateString())->startOfDay();
                    $endDate = Carbon::parse($this->filterData['filter_end_date'] ?? Carbon::now()->endOfMonth()->toDateString())->endOfDay();
                    return Excel::download(
                        new \App\Exports\AttendanceRosterMatrixExport(
                            $startDate,
                            $endDate,
                            $this->filterData['filter_branch_id'] ?? null,
                            $this->filterData['filter_principal_id'] ?? null,
                            $this->filterData['filter_employee_id'] ?? null
                        ),
                        'Attendance_Roster_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx'
                    );
                }),

            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action(function () {
                    $startDate = Carbon::parse($this->filterData['filter_start_date'] ?? Carbon::now()->startOfMonth()->toDateString())->startOfDay();
                    $endDate = Carbon::parse($this->filterData['filter_end_date'] ?? Carbon::now()->endOfMonth()->toDateString())->endOfDay();

                    $query = Attendance::query()
                        ->with('employee')
                        ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);

                    if (!empty($this->filterData['filter_branch_id'])) {
                        $query->whereHas('employee', fn($q) => $q->where('branch_id', $this->filterData['filter_branch_id']));
                    }
                    if (!empty($this->filterData['filter_principal_id'])) {
                        $query->whereHas('employee', fn($q) => $q->where('principal_id', $this->filterData['filter_principal_id']));
                    }
                    if (!empty($this->filterData['filter_employee_id'])) {
                        $query->where('employee_id', $this->filterData['filter_employee_id']);
                    }

                    $attendances = $query->get();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.attendance-report', ['attendances' => $attendances]);
                    $filename = 'attendance-report-' . date('Y-m-d-His') . '.pdf';

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
                }),
        ];
    }

    public static function isWorkingDay(Carbon $date, $deptWorkingDays): bool
    {
        $workingDays = null;
        if (!empty($deptWorkingDays)) {
            if (is_array($deptWorkingDays)) {
                $workingDays = $deptWorkingDays;
            } elseif (is_string($deptWorkingDays)) {
                $decoded = json_decode($deptWorkingDays, true);
                if (is_array($decoded)) {
                    $workingDays = $decoded;
                }
            }
        }

        $dow = $date->dayOfWeek; // 0 = Sun, 1 = Mon, ..., 6 = Sat
        $iso = $date->dayOfWeekIso; // 1 = Mon, ..., 7 = Sun

        if (!empty($workingDays)) {
            $normalized = array_map('strval', $workingDays);
            return in_array(strval($dow), $normalized) || in_array(strval($iso), $normalized);
        }

        // Default Mon-Fri (1, 2, 3, 4, 5)
        return in_array($dow, [1, 2, 3, 4, 5]);
    }

    protected function getViewData(): array
    {
        @ini_set('memory_limit', '512M');

        $startDate = Carbon::parse($this->filterData['filter_start_date'] ?? Carbon::now()->startOfMonth()->toDateString())->startOfDay();
        $endDate = Carbon::parse($this->filterData['filter_end_date'] ?? Carbon::now()->endOfMonth()->toDateString())->endOfDay();

        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        if ($daysInPeriod > 31) {
            $endDate = $startDate->copy()->addDays(30)->endOfDay();
            $daysInPeriod = 31;
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        // Load holidays in period
        $holidays = DB::table('holidays')
            ->whereBetween('holiday_date', [$startDateStr, $endDateStr])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
        $holidayMap = array_flip($holidays);

        // 1. Karyawan yang memiliki jadwal roster di periode ini (untuk indikator jumlah employee terjadwal)
        $schedEmpIds = DB::table('employee_schedules')
            ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
            ->pluck('employee_id')
            ->unique()
            ->filter()
            ->toArray();
        $schedEmpSet = array_flip($schedEmpIds);

        // Query seluruh employee aktif yang memenuhi filter akses
        $employeeQuery = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at');

        if (!empty($this->filterData['filter_branch_id'])) {
            $employeeQuery->where('employees.branch_id', $this->filterData['filter_branch_id']);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $employeeQuery->whereIn('employees.branch_id', auth()->user()->getAccessibleBranchIds());
        }

        if (!empty($this->filterData['filter_principal_id'])) {
            $employeeQuery->where('employees.principal_id', $this->filterData['filter_principal_id']);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $employeeQuery->whereIn('employees.principal_id', auth()->user()->getAccessiblePrincipalIds());
        }

        if (!empty($this->filterData['filter_employee_id'])) {
            $employeeQuery->where('employees.id', $this->filterData['filter_employee_id']);
        }

        $allEmployees = $employeeQuery->select([
            'employees.id',
            'employees.employee_no',
            'employees.full_name',
            'employees.photo',
            'employees.department_id',
            'departments.working_days as dept_working_days',
            'positions.name as position_name',
            'branches.name as branch_name',
            'principals.name as principal_name',
        ])->orderBy('employees.full_name')->get();

        // Filter by Search Box if present
        if (!empty(trim($this->search ?? ''))) {
            $q = strtolower(trim($this->search));
            $allEmployees = $allEmployees->filter(function ($emp) use ($q) {
                return str_contains(strtolower($emp->full_name), $q)
                    || str_contains(strtolower($emp->employee_no ?? ''), $q)
                    || str_contains(strtolower($emp->branch_name ?? ''), $q)
                    || str_contains(strtolower($emp->principal_name ?? ''), $q);
            })->values();
        }

        $totalEmployeesCount = $allEmployees->count();
        $totalPages = max(1, (int)ceil($totalEmployeesCount / $this->perPage));

        if ($this->page > $totalPages) {
            $this->page = $totalPages;
        }
        if ($this->page < 1) {
            $this->page = 1;
        }

        $offset = ($this->page - 1) * $this->perPage;
        $pagedEmployees = $allEmployees->slice($offset, $this->perPage)->values();

        $pagedEmployeeIds = $pagedEmployees->pluck('id')->toArray();

        // 2. Fetch attendances, schedules, and leaves for currently paged employees
        $attendances = collect();
        $schedules = collect();
        $leaves = collect();

        if (!empty($pagedEmployeeIds)) {
            $attendances = DB::table('attendances')
                ->leftJoin('employee_schedules', 'attendances.employee_schedule_id', '=', 'employee_schedules.id')
                ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->whereIn('attendances.employee_id', $pagedEmployeeIds)
                ->whereBetween('attendances.attendance_date', [$startDateStr, $endDateStr])
                ->select([
                    'attendances.id',
                    'attendances.employee_id',
                    'attendances.attendance_date',
                    'attendances.status',
                    'attendances.checkin_at',
                    'attendances.checkout_at',
                    'attendances.late_minutes',
                    'attendances.is_manual_correction',
                    'attendances.correction_note',
                    'shifts.start_time as shift_start_time',
                    'shifts.grace_checkin_minutes',
                    'employee_schedules.planned_start_at',
                ])
                ->get()
                ->groupBy('employee_id');

            $schedules = DB::table('employee_schedules')
                ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->whereIn('employee_schedules.employee_id', $pagedEmployeeIds)
                ->whereBetween('employee_schedules.schedule_date', [$startDateStr, $endDateStr])
                ->select([
                    'employee_schedules.id',
                    'employee_schedules.employee_id',
                    'employee_schedules.schedule_date',
                    'employee_schedules.schedule_type',
                    'employee_schedules.planned_start_at',
                    'shifts.name as shift_name',
                    'shifts.code as shift_code',
                    'shifts.start_time as shift_start_time',
                    'shifts.end_time as shift_end_time',
                    'shifts.grace_checkin_minutes',
                ])
                ->get()
                ->groupBy('employee_id');

            $leaves = DB::table('leave_requests')
                ->whereIn('employee_id', $pagedEmployeeIds)
                ->whereIn('status', ['approved', 'pending'])
                ->where(function ($q) use ($startDateStr, $endDateStr) {
                    $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                      ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                      ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                          $sq->where('start_date', '<=', $startDateStr)
                             ->where('end_date', '>=', $endDateStr);
                      });
                })
                ->select(['id', 'employee_id', 'start_date', 'end_date', 'type', 'status', 'notes'])
                ->get()
                ->groupBy('employee_id');
        }

        // 3. Calculate KPI summaries across entire filtered dataset (all filtered active employees)
        $allEmpIds = $allEmployees->pluck('id')->toArray();
        $totalEmployeesCount = count($allEmpIds);

        $now = Carbon::now('Asia/Jakarta');

        // Determine evaluation date for KPI calculation
        $evalDate = $todayStr;
        if ($startDateStr <= $todayStr && $todayStr <= $endDateStr) {
            $evalDate = $todayStr;
        } elseif ($todayStr > $endDateStr) {
            $evalDate = $endDateStr;
        } else {
            $evalDate = $startDateStr;
        }

        // If evalDate is today but current time is before working hours (e.g. before 08:00 AM)
        // and there are no check-ins yet today, fallback evalDate to previous working day in period
        if ($evalDate === $todayStr) {
            $hasCheckinToday = DB::table('attendances')
                ->where('attendance_date', $todayStr)
                ->whereIn('employee_id', $allEmpIds)
                ->whereNotNull('checkin_at')
                ->exists();

            if (!$hasCheckinToday && $now->hour < 8) {
                $prevDate = Carbon::parse($todayStr)->subDay();
                while ($prevDate->toDateString() >= $startDateStr) {
                    $pStr = $prevDate->toDateString();
                    if (!isset($holidayMap[$pStr]) && !$prevDate->isSunday()) {
                        $evalDate = $pStr;
                        break;
                    }
                    $prevDate->subDay();
                }
            }
        }

        // If evalDate falls on holiday/Sunday, try fallback to last recent working day within period
        if (isset($holidayMap[$evalDate]) || Carbon::parse($evalDate)->isSunday()) {
            $checkDate = Carbon::parse($evalDate)->subDay();
            while ($checkDate->toDateString() >= $startDateStr) {
                $cStr = $checkDate->toDateString();
                if (!isset($holidayMap[$cStr]) && !$checkDate->isSunday()) {
                    $evalDate = $cStr;
                    break;
                }
                $checkDate->subDay();
            }
        }

        $totalOntime = 0;
        $totalLate = 0;
        $totalCuti = 0;
        $totalPermitSick = 0;
        $totalAlpha = 0;

        if (!empty($allEmpIds)) {
            // Load attendances in the filtered period
            $allAtts = DB::table('attendances')
                ->leftJoin('employee_schedules', 'attendances.employee_schedule_id', '=', 'employee_schedules.id')
                ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->whereIn('attendances.employee_id', $allEmpIds)
                ->whereBetween('attendances.attendance_date', [$startDateStr, $endDateStr])
                ->select([
                    'attendances.id',
                    'attendances.employee_id',
                    'attendances.status',
                    'attendances.checkin_at',
                    'attendances.attendance_date',
                    'attendances.late_minutes',
                    'shifts.start_time as shift_start_time',
                    'shifts.grace_checkin_minutes',
                    'employee_schedules.planned_start_at',
                ])
                ->get()
                ->groupBy('employee_id');

            // Load leave requests in the filtered period
            $allLeaves = DB::table('leave_requests')
                ->whereIn('employee_id', $allEmpIds)
                ->whereIn('status', ['approved', 'pending'])
                ->where(function ($q) use ($startDateStr, $endDateStr) {
                    $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                      ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                      ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                          $sq->where('start_date', '<=', $startDateStr)
                             ->where('end_date', '>=', $endDateStr);
                      });
                })
                ->select(['employee_id', 'start_date', 'end_date', 'type', 'sub_type', 'status'])
                ->get()
                ->groupBy('employee_id');

            // Load schedules in the filtered period
            $allScheds = DB::table('employee_schedules')
                ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->whereIn('employee_schedules.employee_id', $allEmpIds)
                ->whereBetween('employee_schedules.schedule_date', [$startDateStr, $endDateStr])
                ->select([
                    'employee_schedules.employee_id',
                    'employee_schedules.schedule_date',
                    'employee_schedules.schedule_type',
                    'employee_schedules.planned_start_at',
                    'shifts.start_time as shift_start_time',
                    'shifts.grace_checkin_minutes',
                ])
                ->get()
                ->groupBy('employee_id');

            // Evaluate each employee strictly into one status so:
            // Grand Total Employee Aktif = Ontime + Late + Cuti + Ijin/Sakit + Alpha
            foreach ($allEmployees as $emp) {
                $empId = $emp->id;
                $empAttList = $allAtts->get($empId) ?? collect();
                $empLeaveList = $allLeaves->get($empId) ?? collect();
                $empSchedList = $allScheds->get($empId) ?? collect();

                // Find attendance record: prioritize evalDate, otherwise latest record in period
                $att = $empAttList->firstWhere('attendance_date', $evalDate);
                if (!$att && $empAttList->isNotEmpty()) {
                    $att = $empAttList->sortByDesc('attendance_date')->first();
                }

                // Find leave request: prioritize evalDate, otherwise latest in period
                $leaveItem = $empLeaveList->first(function ($l) use ($evalDate) {
                    return $evalDate >= $l->start_date && $evalDate <= $l->end_date;
                });
                if (!$leaveItem && $empLeaveList->isNotEmpty()) {
                    $leaveItem = $empLeaveList->sortByDesc('start_date')->first();
                }

                // Find schedule
                $sched = $empSchedList->firstWhere('schedule_date', $evalDate)
                    ?? $empSchedList->sortByDesc('schedule_date')->first();

                // 1. Check Attendance record
                if ($att) {
                    $attStatus = strtolower(trim($att->status ?? ''));
                    if ($attStatus === 'absent') {
                        $totalAlpha++;
                        continue;
                    }
                    if (in_array($attStatus, ['cuti', 'leave', 'annual_leave'])) {
                        $totalCuti++;
                        continue;
                    }
                    if (in_array($attStatus, ['permit', 'sick', 'izin', 'ijin', 'sakit'])) {
                        $totalPermitSick++;
                        continue;
                    }

                    // Check lateness
                    $isLate = false;
                    if ($attStatus === 'late' || (int)($att->late_minutes ?? 0) > 0) {
                        $isLate = true;
                    } elseif (!empty($att->checkin_at)) {
                        $checkin = Carbon::parse($att->checkin_at)->timezone('Asia/Jakarta');
                        $shiftStartTime = $att->shift_start_time ?? ($sched->shift_start_time ?? null);
                        $grace = (int)($att->grace_checkin_minutes ?? ($sched->grace_checkin_minutes ?? 0));
                        $plannedStartAt = $att->planned_start_at ?? ($sched->planned_start_at ?? null);
                        $attDate = $att->attendance_date ?? $evalDate;

                        if (!empty($shiftStartTime)) {
                            $shiftStart = Carbon::parse($attDate . ' ' . $shiftStartTime, 'Asia/Jakarta');
                            if ($checkin->greaterThan($shiftStart->copy()->addMinutes($grace))) {
                                $isLate = true;
                            }
                        } elseif (!empty($plannedStartAt)) {
                            $plannedStart = Carbon::parse($plannedStartAt, 'Asia/Jakarta');
                            if ($checkin->greaterThan($plannedStart)) {
                                $isLate = true;
                            }
                        } else {
                            $defaultStart = Carbon::parse($attDate . ' 08:30:00', 'Asia/Jakarta');
                            if ($checkin->greaterThan($defaultStart)) {
                                $isLate = true;
                            }
                        }
                    }

                    if ($isLate) {
                        $totalLate++;
                    } else {
                        $totalOntime++;
                    }
                    continue;
                }

                // 2. Check Leave Requests
                if ($leaveItem) {
                    $lType = strtolower(trim(($leaveItem->type ?? '') . ' ' . ($leaveItem->sub_type ?? '')));

                    if (str_contains($lType, 'cuti') || str_contains($lType, 'annual') || str_contains($lType, 'extra_off') || str_contains($lType, 'leave')) {
                        $totalCuti++;
                    } else {
                        $totalPermitSick++;
                    }
                    continue;
                }

                // 3. No Attendance and No Leave -> Alpha
                $totalAlpha++;
            }
        }

        $totalScheduledEmployees = 0;
        foreach ($allEmployees as $emp) {
            if (isset($schedEmpSet[$emp->id])) {
                $totalScheduledEmployees++;
            }
        }

        $summary = [
            'total_active_employees' => $totalEmployeesCount,
            'total_scheduled_employees' => $totalScheduledEmployees,
            'total_ontime' => $totalOntime,
            'total_late' => $totalLate,
            'total_cuti' => $totalCuti,
            'total_permit_sick' => $totalPermitSick,
            'total_alpha' => $totalAlpha,
            'evaluation_date' => $evalDate,
            // Aliases for compatibility
            'total_present' => $totalOntime,
            'total_leave' => $totalCuti,
            'total_absent' => $totalAlpha,
        ];

        return [
            'employees' => $pagedEmployees,
            'totalEmployees' => $totalEmployeesCount,
            'totalScheduledEmployees' => $totalScheduledEmployees,
            'attendances' => $attendances,
            'schedules' => $schedules,
            'leaves' => $leaves,
            'holidayMap' => $holidayMap,
            'daysInPeriod' => $daysInPeriod,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'pagination' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total_pages' => $totalPages,
                'from' => $totalEmployeesCount > 0 ? $offset + 1 : 0,
                'to' => min($offset + $this->perPage, $totalEmployeesCount),
            ]
        ];
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function nextPage(int $maxPage): void
    {
        if ($this->page < $maxPage) {
            $this->page++;
        }
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function viewDetailsAction(): Action
    {
        return Action::make('viewDetails')
            ->modalHeading(fn (array $arguments) => 'Rincian Presensi & Aktivitas - ' . Carbon::parse($arguments['date'])->translatedFormat('d F Y'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalContent(function (array $arguments) {
                $employee = Employee::with(['company', 'principal', 'branch', 'department', 'position'])
                    ->find($arguments['employee_id']);

                $attendance = Attendance::where('employee_id', $arguments['employee_id'])
                    ->where('attendance_date', $arguments['date'])
                    ->with(['employeeSchedule.workLocation.company', 'employeeSchedule.shift', 'checkinLog', 'checkoutLog'])
                    ->first();

                $schedule = EmployeeSchedule::where('employee_id', $arguments['employee_id'])
                    ->where('schedule_date', $arguments['date'])
                    ->with(['workLocation.company', 'shift'])
                    ->first();

                $leaveRequest = LeaveRequest::where('employee_id', $arguments['employee_id'])
                    ->whereIn('status', ['approved', 'pending'])
                    ->whereDate('start_date', '<=', $arguments['date'])
                    ->whereDate('end_date', '>=', $arguments['date'])
                    ->first();

                $logs = [];
                if ($attendance) {
                    $logs = AttendanceLog::where('attendance_id', $attendance->id)
                        ->orWhere(function($q) use ($arguments) {
                            $q->where('employee_id', $arguments['employee_id'])
                              ->whereDate('logged_at', $arguments['date']);
                        })
                        ->with(['itineraryItem.workLocation'])
                        ->orderBy('logged_at', 'asc')
                        ->get();
                } else {
                    $logs = AttendanceLog::where('employee_id', $arguments['employee_id'])
                        ->whereDate('logged_at', $arguments['date'])
                        ->with(['itineraryItem.workLocation'])
                        ->orderBy('logged_at', 'asc')
                        ->get();
                }

                $trackingCount = TrackingHistory::where('employee_id', $arguments['employee_id'])
                    ->whereDate('created_at', $arguments['date'])
                    ->count();

                return View::make('filament.components.attendance-details-modal', [
                    'employee'      => $employee,
                    'attendance'    => $attendance,
                    'schedule'      => $schedule,
                    'leaveRequest'  => $leaveRequest,
                    'logs'          => $logs,
                    'trackingCount' => $trackingCount,
                    'date'          => $arguments['date'],
                ]);
            });
    }
}
