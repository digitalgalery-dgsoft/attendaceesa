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

        // 1. Karyawan yang memiliki record absensi, jadwal roster, atau izin/cuti yang disetujui di periode ini
        $attEmpIds = DB::table('attendances')
            ->whereBetween('attendance_date', [$startDateStr, $endDateStr])
            ->pluck('employee_id');

        $schedEmpIds = DB::table('employee_schedules')
            ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
            ->pluck('employee_id');

        $leaveEmpIds = DB::table('leave_requests')
            ->where('status', 'approved')
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                  ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                  ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                      $sq->where('start_date', '<=', $startDateStr)
                         ->where('end_date', '>=', $endDateStr);
                  });
            })
            ->pluck('employee_id');

        $activeEmpIds = $attEmpIds->merge($schedEmpIds)->merge($leaveEmpIds)->unique()->filter()->toArray();

        if (empty($activeEmpIds)) {
            return [
                'employees' => collect(),
                'totalEmployees' => 0,
                'attendances' => collect(),
                'schedules' => collect(),
                'leaves' => collect(),
                'holidayMap' => $holidayMap,
                'daysInPeriod' => $daysInPeriod,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'summary' => [
                    'total_present' => 0,
                    'total_late' => 0,
                    'total_absent' => 0,
                    'total_leave' => 0,
                ],
                'pagination' => [
                    'page' => 1,
                    'per_page' => $this->perPage,
                    'total_pages' => 1,
                    'from' => 0,
                    'to' => 0,
                ]
            ];
        }

        // Query employees with department working days
        $employeeQuery = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->whereIn('employees.id', $activeEmpIds)
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
                ->where('status', 'approved')
                ->where(function ($q) use ($startDateStr, $endDateStr) {
                    $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                      ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                      ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                          $sq->where('start_date', '<=', $startDateStr)
                             ->where('end_date', '>=', $endDateStr);
                      });
                })
                ->select(['id', 'employee_id', 'start_date', 'end_date', 'type', 'notes'])
                ->get()
                ->groupBy('employee_id');
        }

        // 3. Calculate KPI summaries across entire filtered dataset (all filtered employees)
        $allEmpIds = $allEmployees->pluck('id')->toArray();
        $summary = [
            'total_present' => 0,
            'total_late' => 0,
            'total_absent' => 0,
            'total_leave' => 0,
        ];

        if (!empty($allEmpIds)) {
            // Load attendances
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
                ->get();

            // Load schedules with shift info
            $allScheds = DB::table('employee_schedules')
                ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->whereIn('employee_schedules.employee_id', $allEmpIds)
                ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
                ->select([
                    'employee_schedules.employee_id',
                    'employee_schedules.schedule_date',
                    'employee_schedules.schedule_type',
                    'employee_schedules.planned_start_at',
                    'shifts.name as shift_name',
                    'shifts.code as shift_code',
                    'shifts.start_time as shift_start_time',
                    'shifts.grace_checkin_minutes',
                ])
                ->get();

            // Load approved leaves
            $allLeaves = DB::table('leave_requests')
                ->whereIn('employee_id', $allEmpIds)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDateStr, $endDateStr) {
                    $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                      ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                      ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                          $sq->where('start_date', '<=', $startDateStr)
                             ->where('end_date', '>=', $endDateStr);
                      });
                })
                ->select(['employee_id', 'start_date', 'end_date', 'type'])
                ->get();

            // Count attendances (Present & Late & Explicit Leave/Absent)
            $attMap = [];
            foreach ($allAtts as $row) {
                $attMap[$row->employee_id . '_' . $row->attendance_date] = true;

                if ($row->status === 'absent') {
                    $summary['total_absent']++;
                    continue;
                }
                if (in_array($row->status, ['leave', 'permit', 'sick'])) {
                    $summary['total_leave']++;
                    continue;
                }

                // Check lateness
                $isLate = false;
                if ($row->status === 'late' || (int)$row->late_minutes > 0) {
                    $isLate = true;
                } elseif (!empty($row->checkin_at)) {
                    $checkin = Carbon::parse($row->checkin_at)->timezone('Asia/Jakarta');
                    if (!empty($row->shift_start_time)) {
                        $shiftStart = Carbon::parse($row->attendance_date . ' ' . $row->shift_start_time);
                        $grace = (int)($row->grace_checkin_minutes ?? 0);
                        if ($checkin->greaterThan($shiftStart->copy()->addMinutes($grace))) {
                            $isLate = true;
                        }
                    } elseif (!empty($row->planned_start_at)) {
                        $plannedStart = Carbon::parse($row->planned_start_at);
                        if ($checkin->greaterThan($plannedStart)) {
                            $isLate = true;
                        }
                    } else {
                        $defaultStart = Carbon::parse($row->attendance_date . ' 08:30:00');
                        if ($checkin->greaterThan($defaultStart)) {
                            $isLate = true;
                        }
                    }
                }

                if ($isLate) {
                    $summary['total_late']++;
                } else {
                    $summary['total_present']++;
                }
            }

            // Count approved leaves by date
            $leaveMap = [];
            foreach ($allLeaves as $l) {
                $lStart = Carbon::parse($l->start_date);
                $lEnd = Carbon::parse($l->end_date);
                
                $cur = $lStart->copy();
                while ($cur->lessThanOrEqualTo($lEnd)) {
                    $curStr = $cur->toDateString();
                    if ($curStr >= $startDateStr && $curStr <= $endDateStr) {
                        $key = $l->employee_id . '_' . $curStr;
                        if (!isset($leaveMap[$key])) {
                            $leaveMap[$key] = true;
                            $summary['total_leave']++;
                        }
                    }
                    $cur->addDay();
                }
            }

            // Map employees dept_working_days
            $empWorkingDaysMap = $allEmployees->pluck('dept_working_days', 'id')->toArray();
            $now = Carbon::now('Asia/Jakarta');

            // Count scheduled workdays that have passed (<= today) without checkin and without approved leave (Alpha)
            // ONLY if the day is an actual working day for the employee and NOT a holiday!
            foreach ($allScheds as $sched) {
                if (in_array($sched->schedule_type, ['workday', 'remote', 'field'])) {
                    if ($sched->schedule_date <= $todayStr) {
                        // Check if national holiday
                        if (isset($holidayMap[$sched->schedule_date])) {
                            continue;
                        }

                        // Check if employee working day
                        $schedDate = Carbon::parse($sched->schedule_date);
                        $deptWd = $empWorkingDaysMap[$sched->employee_id] ?? null;
                        if (!self::isWorkingDay($schedDate, $deptWd)) {
                            continue;
                        }

                        $key = $sched->employee_id . '_' . $sched->schedule_date;
                        if (!isset($attMap[$key]) && !isset($leaveMap[$key])) {
                            if ($sched->schedule_date < $todayStr) {
                                // Past day without attendance -> Alpha
                                $summary['total_absent']++;
                            } elseif ($sched->schedule_date === $todayStr) {
                                // Today: only count as absent if current time is >= shift start time
                                $shiftStart = null;
                                if (!empty($sched->shift_start_time)) {
                                    $shiftStart = Carbon::parse($sched->schedule_date . ' ' . $sched->shift_start_time, 'Asia/Jakarta');
                                } elseif (!empty($sched->planned_start_at)) {
                                    $shiftStart = Carbon::parse($sched->planned_start_at, 'Asia/Jakarta');
                                } else {
                                    $shiftStart = Carbon::parse($sched->schedule_date . ' 08:30:00', 'Asia/Jakarta');
                                }

                                if ($now->greaterThanOrEqualTo($shiftStart)) {
                                    $summary['total_absent']++;
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'employees' => $pagedEmployees,
            'totalEmployees' => $totalEmployeesCount,
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
                    ->where('status', 'approved')
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
