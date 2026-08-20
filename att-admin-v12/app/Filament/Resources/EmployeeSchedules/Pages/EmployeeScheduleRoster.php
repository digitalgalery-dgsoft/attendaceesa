<?php

namespace App\Filament\Resources\EmployeeSchedules\Pages;

use App\Exports\EmployeeScheduleTemplateExport;
use App\Filament\Resources\EmployeeSchedules\EmployeeScheduleResource;
use App\Imports\EmployeeScheduleImport;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\Principal;
use App\Models\Shift;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeScheduleRoster extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = EmployeeScheduleResource::class;
    protected string $view = 'filament.pages.employee-schedule-roster';

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
                            return $query->pluck('name', 'id');
                        })
                        ->placeholder('Semua Region')
                        ->searchable()
                        ->live(),
                    Select::make('filter_principal_id')
                        ->label('Prinsiple')
                        ->options(function () {
                            $query = Principal::orderBy('name');
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->placeholder('Semua Prinsiple')
                        ->searchable()
                        ->live(),
                    Select::make('filter_employee_id')
                        ->label('Karyawan Spesifik')
                        ->options(function () {
                            $query = Employee::where('is_active', 1);
                            if (auth()->check()) {
                                $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                            }
                            return $query->orderBy('full_name')->pluck('full_name', 'id');
                        })
                        ->placeholder('Semua Karyawan')
                        ->searchable()
                        ->live(),
                ])
            ])
            ->statePath('filterData');
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_schedule')
                ->label('Generate Schedule Roster')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Select::make('principal_id')
                        ->label('Prinsiple (Opsional)')
                        ->options(function () {
                            $query = Principal::orderBy('name');
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
                        ->options(Shift::where('is_active', 1)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('work_location_id')
                        ->label('Lokasi Kerja')
                        ->options(WorkLocation::pluck('name', 'id'))
                        ->searchable()
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

                        $workingDays = [];
                        if ($employee->department && !empty($employee->department->working_days)) {
                            $wd = $employee->department->working_days;
                            $workingDays = is_array($wd) ? $wd : (json_decode($wd, true) ?: [1, 2, 3, 4, 5]);
                        } else {
                            $workingDays = [1, 2, 3, 4, 5]; // Default Mon-Fri
                        }
                        $normalizedWd = array_map('strval', $workingDays);

                        while ($currentDate->lte($endDate)) {
                            $plannedStart = null;
                            $plannedEnd = null;
                            $scheduleType = 'dayoff';
                            $shiftIdToUse = null;

                            $isSingleDay = $startDate->equalTo($endDate);

                            if ($isSingleDay || in_array($dow, $normalizedWd) || in_array($iso, $normalizedWd)) {
                                $scheduleType = 'workday';
                                $shiftIdToUse = $data['shift_id'];

                                if ($shift && $shift->start_time && $shift->end_time) {
                                    $plannedStart = Carbon::parse($currentDate->toDateString() . ' ' . $shift->start_time);
                                    $plannedEnd = Carbon::parse($currentDate->toDateString() . ' ' . $shift->end_time);

                                    if ($shift->is_cross_day ?? false) {
                                        $plannedEnd->addDay();
                                    } elseif ($plannedEnd->lt($plannedStart)) {
                                        $plannedEnd->addDay();
                                    }
                                }
                            }

                            EmployeeSchedule::updateOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'schedule_date' => $currentDate->toDateString(),
                                ],
                                [
                                    'shift_id' => $shiftIdToUse,
                                    'work_location_id' => $data['work_location_id'],
                                    'schedule_type' => $scheduleType,
                                    'planned_start_at' => $plannedStart,
                                    'planned_end_at' => $plannedEnd,
                                    'created_by' => Auth::id(),
                                ]
                            );

                            $currentDate->addDay();
                        }
                    }

                    $this->form->fill([
                        'filter_start_date' => $data['start_date'],
                        'filter_end_date' => $data['end_date'],
                    ]);

                    Notification::make()
                        ->title('Jadwal roster berhasil digenerate!')
                        ->body('Berhasil memproses jadwal untuk ' . $employees->count() . ' karyawan.')
                        ->success()
                        ->send();
                }),

            Action::make('import_schedule')
                ->label('Import Schedule (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import Jadwal Roster (Excel)')
                ->modalDescription('Unggah file Excel (.xlsx / .csv) berisi jadwal kerja karyawan. Kolom template: nik, nama_karyawan, tanggal_mulai, tanggal_akhir, shift, lokasi_kerja.')
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
                ->action(function () {
                    return Excel::download(new EmployeeScheduleTemplateExport(), 'Template_Import_Jadwal_Roster.xlsx');
                }),
        ];
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

        // Load holidays
        $holidays = DB::table('holidays')
            ->whereBetween('holiday_date', [$startDateStr, $endDateStr])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
        $holidayMap = array_flip($holidays);

        // Find employees who have schedule in this period
        $scheduledEmpIds = DB::table('employee_schedules')
            ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
            ->distinct()
            ->pluck('employee_id')
            ->toArray();

        if (empty($scheduledEmpIds)) {
            return [
                'employees' => collect(),
                'totalEmployees' => 0,
                'schedules' => collect(),
                'holidayMap' => $holidayMap,
                'daysInPeriod' => $daysInPeriod,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'summary' => [
                    'total_scheduled' => 0,
                    'total_workday' => 0,
                    'total_dayoff' => 0,
                    'unique_shifts' => 0,
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

        // Query employees with relationships & department working days
        $employeeQuery = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->whereIn('employees.id', $scheduledEmpIds)
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

        // Search filtering
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

        // Fetch schedules for paged employees
        $schedules = collect();
        if (!empty($pagedEmployeeIds)) {
            $schedules = DB::table('employee_schedules')
                ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->leftJoin('work_locations', 'employee_schedules.work_location_id', '=', 'work_locations.id')
                ->whereIn('employee_schedules.employee_id', $pagedEmployeeIds)
                ->whereBetween('employee_schedules.schedule_date', [$startDateStr, $endDateStr])
                ->select([
                    'employee_schedules.id',
                    'employee_schedules.employee_id',
                    'employee_schedules.schedule_date',
                    'employee_schedules.schedule_type',
                    'employee_schedules.planned_start_at',
                    'employee_schedules.planned_end_at',
                    'shifts.name as shift_name',
                    'shifts.start_time as shift_start_time',
                    'shifts.end_time as shift_end_time',
                    'work_locations.name as work_location_name',
                ])
                ->get()
                ->groupBy('employee_id');
        }

        // Summary stats across all filtered employees matching working days and holidays
        $allEmpIds = $allEmployees->pluck('id')->toArray();
        $empWorkingDaysMap = $allEmployees->pluck('dept_working_days', 'id')->toArray();

        $summary = [
            'total_scheduled' => $totalEmployeesCount,
            'total_workday' => 0,
            'total_dayoff' => 0,
            'unique_shifts' => 0,
        ];

        if (!empty($allEmpIds)) {
            $allScheds = DB::table('employee_schedules')
                ->whereIn('employee_id', $allEmpIds)
                ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
                ->select(['employee_id', 'schedule_date', 'schedule_type', 'shift_id'])
                ->get();

            foreach ($allScheds as $sched) {
                $schedDate = Carbon::parse($sched->schedule_date);
                $isHoliday = isset($holidayMap[$sched->schedule_date]);
                $deptWd = $empWorkingDaysMap[$sched->employee_id] ?? null;
                $isWorkDay = self::isWorkingDay($schedDate, $deptWd);

                if (in_array($sched->schedule_type, ['workday', 'remote', 'field'])) {
                    if (!$isHoliday && $isWorkDay) {
                        $summary['total_workday']++;
                    } else {
                        $summary['total_dayoff']++;
                    }
                } else {
                    $summary['total_dayoff']++;
                }
            }

            $summary['unique_shifts'] = $allScheds->pluck('shift_id')->filter()->unique()->count();
        }

        return [
            'employees' => $pagedEmployees,
            'totalEmployees' => $totalEmployeesCount,
            'schedules' => $schedules,
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

    public function editScheduleAction(): Action
    {
        return Action::make('editSchedule')
            ->hiddenLabel()
            ->modalHeading(fn (array $arguments) => 'Edit Jadwal Karyawan - ' . Carbon::parse($arguments['schedule_date'])->translatedFormat('d F Y'))
            ->modalWidth('lg')
            ->form([
                \Filament\Forms\Components\Hidden::make('employee_id'),
                \Filament\Forms\Components\Hidden::make('schedule_date'),
                Select::make('schedule_type')
                    ->label('Tipe Jadwal')
                    ->options([
                        'workday' => 'Hari Kerja (Workday)',
                        'dayoff' => 'Hari Libur (Dayoff)',
                        'remote' => 'Kerja Remote (WFH)',
                        'field' => 'Kerja Lapangan (Field)',
                    ])
                    ->required()
                    ->live(),
                Select::make('shift_id')
                    ->label('Shift Kerja')
                    ->options(Shift::where('is_active', 1)->pluck('name', 'id'))
                    ->searchable()
                    ->required(fn ($get) => in_array($get('schedule_type'), ['workday', 'remote', 'field'])),
                Select::make('work_location_id')
                    ->label('Lokasi Kerja')
                    ->options(WorkLocation::pluck('name', 'id'))
                    ->searchable()
                    ->required(fn ($get) => in_array($get('schedule_type'), ['workday', 'remote', 'field'])),
            ])
            ->fillForm(function (array $arguments): array {
                $schedule = EmployeeSchedule::where('employee_id', $arguments['employee_id'])
                    ->where('schedule_date', $arguments['schedule_date'])
                    ->first();
                return [
                    'employee_id' => $arguments['employee_id'],
                    'schedule_date' => $arguments['schedule_date'],
                    'schedule_type' => $schedule ? $schedule->schedule_type : 'workday',
                    'shift_id' => $schedule ? $schedule->shift_id : null,
                    'work_location_id' => $schedule ? $schedule->work_location_id : null,
                ];
            })
            ->action(function (array $data): void {
                $schedule = EmployeeSchedule::firstOrNew([
                    'employee_id' => $data['employee_id'],
                    'schedule_date' => $data['schedule_date'],
                ]);

                $schedule->schedule_type = $data['schedule_type'];
                $schedule->shift_id = in_array($data['schedule_type'], ['workday', 'remote', 'field']) ? ($data['shift_id'] ?: null) : null;
                $schedule->work_location_id = in_array($data['schedule_type'], ['workday', 'remote', 'field']) ? ($data['work_location_id'] ?: null) : null;

                if (in_array($data['schedule_type'], ['workday', 'remote', 'field']) && $data['shift_id']) {
                    $shift = Shift::find($data['shift_id']);
                    if ($shift && $shift->start_time && $shift->end_time) {
                        $plannedStart = Carbon::parse($data['schedule_date'] . ' ' . $shift->start_time);
                        $plannedEnd = Carbon::parse($data['schedule_date'] . ' ' . $shift->end_time);

                        if ($shift->is_cross_day ?? false) {
                            $plannedEnd->addDay();
                        } elseif ($plannedEnd->lt($plannedStart)) {
                            $plannedEnd->addDay();
                        }

                        $schedule->planned_start_at = $plannedStart;
                        $schedule->planned_end_at = $plannedEnd;
                    }
                } else {
                    $schedule->planned_start_at = null;
                    $schedule->planned_end_at = null;
                }

                $schedule->created_by = Auth::id();
                $schedule->save();

                Notification::make()
                    ->title('Jadwal berhasil diperbarui')
                    ->success()
                    ->send();
            });
    }
}
