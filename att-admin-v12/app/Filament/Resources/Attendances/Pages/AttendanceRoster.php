<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Principal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

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
            'filter_department_id' => null,
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
                Grid::make(6)->schema([
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
                        ->options(Branch::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Region')
                        ->searchable()
                        ->live(),
                    Select::make('filter_principal_id')
                        ->label('Prinsiple')
                        ->options(Principal::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Prinsiple')
                        ->searchable()
                        ->live(),
                    Select::make('filter_department_id')
                        ->label('Departemen')
                        ->options(Department::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Dept')
                        ->searchable()
                        ->live(),
                    Select::make('filter_employee_id')
                        ->label('Karyawan Spesifik')
                        ->options(Employee::where('is_active', 1)->orderBy('full_name')->pluck('full_name', 'id'))
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
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make('table')
                        ->modifyQueryUsing(function ($query, $livewire) {
                            $startDate = Carbon::parse($livewire->filterData['filter_start_date'] ?? Carbon::now()->startOfMonth()->toDateString())->startOfDay();
                            $endDate = Carbon::parse($livewire->filterData['filter_end_date'] ?? Carbon::now()->endOfMonth()->toDateString())->endOfDay();

                            $query->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);

                            if (!empty($livewire->filterData['filter_branch_id'])) {
                                $query->whereHas('employee', fn($q) => $q->where('branch_id', $livewire->filterData['filter_branch_id']));
                            }
                            if (!empty($livewire->filterData['filter_principal_id'])) {
                                $query->whereHas('employee', fn($q) => $q->where('principal_id', $livewire->filterData['filter_principal_id']));
                            }
                            if (!empty($livewire->filterData['filter_department_id'])) {
                                $query->whereHas('employee', fn($q) => $q->where('department_id', $livewire->filterData['filter_department_id']));
                            }
                            if (!empty($livewire->filterData['filter_employee_id'])) {
                                $query->where('employee_id', $livewire->filterData['filter_employee_id']);
                            }

                            return $query;
                        })
                        ->withColumns([
                            \pxlrbt\FilamentExcel\Columns\Column::make('employee.full_name')->heading('Employee'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('employee.employee_no')->heading('NIK'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('attendance_date')->heading('Date'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('status')->heading('Status'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('checkin_at')->heading('Check-in At'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('checkout_at')->heading('Check-out At'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('work_duration_minutes')->heading('Work Duration (Mins)'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('late_minutes')->heading('Late (Mins)'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('early_leave_minutes')->heading('Early Leave (Mins)'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('overtime_minutes')->heading('Overtime (Mins)'),
                        ])
                ]),

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
                    if (!empty($this->filterData['filter_department_id'])) {
                        $query->whereHas('employee', fn($q) => $q->where('department_id', $this->filterData['filter_department_id']));
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

        // Query employees lightweight
        $employeeQuery = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at');

        if (!empty($this->filterData['filter_branch_id'])) {
            $employeeQuery->where('employees.branch_id', $this->filterData['filter_branch_id']);
        }

        if (!empty($this->filterData['filter_principal_id'])) {
            $employeeQuery->where('employees.principal_id', $this->filterData['filter_principal_id']);
        }

        if (!empty($this->filterData['filter_department_id'])) {
            $employeeQuery->where('employees.department_id', $this->filterData['filter_department_id']);
        }

        if (!empty($this->filterData['filter_employee_id'])) {
            $employeeQuery->where('employees.id', $this->filterData['filter_employee_id']);
        }

        $allEmployees = $employeeQuery->select([
            'employees.id',
            'employees.employee_no',
            'employees.full_name',
            'employees.photo',
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

        // Fetch attendances for currently paged employees
        $attendances = collect();
        if (!empty($pagedEmployeeIds)) {
            $attendances = DB::table('attendances')
                ->whereIn('employee_id', $pagedEmployeeIds)
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->select([
                    'id',
                    'employee_id',
                    'attendance_date',
                    'status',
                    'checkin_at',
                    'checkout_at',
                    'late_minutes',
                ])
                ->get()
                ->groupBy('employee_id');
        }

        // Calculate KPI summaries across entire filtered dataset
        $allEmpIds = $allEmployees->pluck('id')->toArray();
        $summary = [
            'total_present' => 0,
            'total_late' => 0,
            'total_absent' => 0,
            'total_leave' => 0,
        ];

        if (!empty($allEmpIds)) {
            $stats = DB::table('attendances')
                ->whereIn('employee_id', $allEmpIds)
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $summary['total_present'] = (int)($stats['present'] ?? 0);
            $summary['total_late'] = (int)($stats['late'] ?? 0);
            $summary['total_absent'] = (int)($stats['absent'] ?? 0);
            $summary['total_leave'] = (int)($stats['leave'] ?? 0) + (int)($stats['permit'] ?? 0);
        }

        return [
            'employees' => $pagedEmployees,
            'totalEmployees' => $totalEmployeesCount,
            'attendances' => $attendances,
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
            ->modalHeading(fn (array $arguments) => 'Attendance Details - ' . $arguments['date'])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth('2xl')
            ->modalContent(function (array $arguments) {
                $attendance = Attendance::where('employee_id', $arguments['employee_id'])
                    ->where('attendance_date', $arguments['date'])
                    ->with('employeeSchedule.workLocation')
                    ->first();

                $logs = [];
                if ($attendance) {
                    $logs = AttendanceLog::where('attendance_id', $attendance->id)
                        ->with(['itineraryItem.workLocation'])
                        ->orderBy('logged_at', 'asc')
                        ->get();
                }

                return View::make('filament.components.attendance-details-modal', [
                    'attendance' => $attendance,
                    'logs' => $logs,
                    'date' => $arguments['date'],
                ]);
            });
    }
}
