<?php

namespace App\Filament\Resources\WorkingGroupResource\Pages;

use App\Filament\Resources\EmployeeSchedules\Pages\EmployeeScheduleRoster;
use App\Filament\Resources\WorkingGroupResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Principal;
use App\Models\Shift;
use App\Models\WorkLocation;
use App\Models\WorkingGroup;
use App\Models\WorkingGroupMember;
use App\Models\WorkingGroupRule;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateWorkingGroup extends Page
{
    protected static string $resource = WorkingGroupResource::class;
    protected string $view = 'filament.pages.working-group-wizard';
    protected static ?string $title = 'Create Working Group';

    public int $currentStep = 1;

    // Step 1: Description
    public ?string $name = '';
    public ?string $data_applied_date = '';
    public array $branch_ids = [];
    public array $principal_ids = [];

    // Step 1: General Configuration
    public ?int $default_shift_id = null;
    public int $default_late_tolerance = 15;
    public ?int $default_work_location_id = null;

    // Step 1: Days Applied
    public array $days = [];

    // Step 2: Implementing Working Group
    public array $selected_employee_ids = [];
    public ?int $employee_to_add = null;
    public string $table_search = '';
    public int $table_per_page = 5;
    public int $table_page = 1;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->data_applied_date = Carbon::now()->toDateString();
        $this->default_late_tolerance = 15;
        $this->currentStep = 1;
        $this->branch_ids = [];
        $this->principal_ids = [];

        $defaultDays = [
            'Monday'    => ['name' => 'Monday',    'label' => 'Monday (Senin)',    'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Tuesday'   => ['name' => 'Tuesday',   'label' => 'Tuesday (Selasa)',   'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Wednesday' => ['name' => 'Wednesday', 'label' => 'Wednesday (Rabu)', 'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Thursday'  => ['name' => 'Thursday',  'label' => 'Thursday (Kamis)',  'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Friday'    => ['name' => 'Friday',    'label' => 'Friday (Jumat)',    'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Saturday'  => ['name' => 'Saturday',  'label' => 'Saturday (Sabtu)',  'is_active' => false, 'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Sunday'    => ['name' => 'Sunday',    'label' => 'Sunday (Minggu)',   'is_active' => false, 'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
        ];

        $this->days = $defaultDays;
    }

    public function selectAllDays(): void
    {
        foreach ($this->days as $key => $day) {
            $this->days[$key]['is_active'] = true;
        }
    }

    public function selectWorkDays(): void
    {
        $workDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($this->days as $key => $day) {
            $this->days[$key]['is_active'] = in_array($key, $workDays);
        }
    }

    public function toggleCustomOption(string $dayName): void
    {
        if (isset($this->days[$dayName])) {
            $current = $this->days[$dayName]['has_custom_option'] ?? false;
            $this->days[$dayName]['has_custom_option'] = !$current;

            if ($this->days[$dayName]['has_custom_option']) {
                if (empty($this->days[$dayName]['shift_id'])) {
                    $this->days[$dayName]['shift_id'] = $this->default_shift_id;
                }
                if (empty($this->days[$dayName]['work_location_id'])) {
                    $this->days[$dayName]['work_location_id'] = $this->default_work_location_id;
                }
                if (empty($this->days[$dayName]['late_tolerance'])) {
                    $this->days[$dayName]['late_tolerance'] = $this->default_late_tolerance;
                }
            }
        }
    }

    public function goToStep2(): void
    {
        if (empty(trim($this->name ?? ''))) {
            Notification::make()
                ->title('Nama Working Group Wajib Diisi')
                ->danger()
                ->send();
            return;
        }

        if (empty($this->data_applied_date)) {
            Notification::make()
                ->title('Tanggal Berlaku (Date Applied) Wajib Diisi')
                ->danger()
                ->send();
            return;
        }

        $hasActiveDay = false;
        foreach ($this->days as $day) {
            if (!empty($day['is_active'])) {
                $hasActiveDay = true;
                break;
            }
        }

        if (!$hasActiveDay) {
            Notification::make()
                ->title('Pilih Minimal 1 Hari Kerja (Days Applied)')
                ->danger()
                ->send();
            return;
        }

        $this->currentStep = 2;
    }

    public function goToStep1(): void
    {
        $this->currentStep = 1;
    }

    public function updatedEmployeeToAdd($value): void
    {
        if (!empty($value)) {
            $empId = (int)$value;
            if (!in_array($empId, $this->selected_employee_ids)) {
                $this->selected_employee_ids[] = $empId;
                Notification::make()
                    ->title('Karyawan berhasil ditambahkan ke daftar')
                    ->success()
                    ->duration(2000)
                    ->send();
            }
            $this->employee_to_add = null;
            $this->table_page = 1;
        }
    }

    public function addAllEmployeesFromArea(): void
    {
        $query = Employee::where('is_active', 1);

        if (!empty($this->branch_ids)) {
            $selectedBranches = Branch::whereIn('id', $this->branch_ids)->get();
            $branchNames = $selectedBranches->pluck('name')->filter()->toArray();
            $allMatchingBranchIds = Branch::whereIn('name', $branchNames)->pluck('id')->merge($this->branch_ids)->unique()->toArray();
            $query->whereIn('branch_id', $allMatchingBranchIds);
        }

        if (!empty($this->principal_ids)) {
            $selectedPrincipals = Principal::whereIn('id', $this->principal_ids)->get();
            $principalNames = $selectedPrincipals->pluck('name')->filter()->toArray();
            $subdomains = $selectedPrincipals->pluck('subdomain')->filter()->toArray();

            $allMatchingPrincipalIds = Principal::where(function ($q) use ($principalNames, $subdomains) {
                $q->whereIn('name', $principalNames);
                if (!empty($subdomains)) {
                    $q->orWhereIn('subdomain', $subdomains);
                }
            })->pluck('id')->merge($this->principal_ids)->unique()->toArray();

            $query->whereIn('principal_id', $allMatchingPrincipalIds);
        }

        $empIds = $query->pluck('id')->toArray();
        if (empty($empIds)) {
            Notification::make()
                ->title('Tidak ada karyawan aktif pada Area / Prinsiple yang dipilih')
                ->warning()
                ->send();
            return;
        }

        $merged = array_unique(array_merge($this->selected_employee_ids, $empIds));
        $addedCount = count($merged) - count($this->selected_employee_ids);
        $this->selected_employee_ids = array_values($merged);
        $this->table_page = 1;

        Notification::make()
            ->title("Berhasil menambahkan {$addedCount} karyawan ke dalam daftar")
            ->success()
            ->send();
    }

    public function removeEmployee(int $employeeId): void
    {
        $this->selected_employee_ids = array_values(array_filter(
            $this->selected_employee_ids,
            fn($id) => $id !== $employeeId
        ));

        Notification::make()
            ->title('Karyawan dihapus dari daftar')
            ->warning()
            ->duration(2000)
            ->send();
    }

    public function removeAllEmployees(): void
    {
        $this->selected_employee_ids = [];
        $this->table_page = 1;
    }

    public function setTablePage(int $page): void
    {
        $this->table_page = max(1, $page);
    }

    public function previousTablePage(): void
    {
        if ($this->table_page > 1) {
            $this->table_page--;
        }
    }

    public function nextTablePage(int $maxPage): void
    {
        if ($this->table_page < $maxPage) {
            $this->table_page++;
        }
    }

    public function saveAndGenerateSchedule()
    {
        if (empty($this->selected_employee_ids)) {
            Notification::make()
                ->title('Pilih Minimal 1 Karyawan')
                ->body('Tambahkan karyawan yang akan diterapkan jadwal Working Group ini.')
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();
        try {
            $branchNames = !empty($this->branch_ids) ? Branch::whereIn('id', $this->branch_ids)->pluck('name')->unique()->implode(', ') : null;
            $firstBranchId = !empty($this->branch_ids) ? (int)$this->branch_ids[0] : null;
            $firstPrincipalId = !empty($this->principal_ids) ? (int)$this->principal_ids[0] : null;

            // 1. Create Working Group record
            $workingGroup = WorkingGroup::create([
                'name' => $this->name,
                'branch_id' => $firstBranchId,
                'principal_id' => $firstPrincipalId,
                'area' => $branchNames,
                'data_applied_date' => $this->data_applied_date,
                'default_shift_id' => $this->default_shift_id,
                'default_late_tolerance' => $this->default_late_tolerance ?: 15,
                'default_work_location_id' => $this->default_work_location_id,
                'created_by' => Auth::id(),
            ]);

            // 2. Create Working Group Rules
            foreach ($this->days as $dayKey => $dayData) {
                $isActive = (bool)($dayData['is_active'] ?? false);
                $hasCustom = (bool)($dayData['has_custom_option'] ?? false);

                WorkingGroupRule::create([
                    'working_group_id' => $workingGroup->id,
                    'day_of_week' => $dayKey,
                    'is_active' => $isActive,
                    'has_custom_option' => $hasCustom,
                    'shift_id' => $hasCustom ? ($dayData['shift_id'] ?: null) : ($this->default_shift_id ?: null),
                    'late_tolerance' => $hasCustom ? ($dayData['late_tolerance'] ?? 15) : ($this->default_late_tolerance ?? 15),
                    'store_assignment_id' => $hasCustom ? ($dayData['work_location_id'] ?: null) : ($this->default_work_location_id ?: null),
                ]);
            }

            // 3. Create Working Group Members
            foreach ($this->selected_employee_ids as $empId) {
                WorkingGroupMember::create([
                    'working_group_id' => $workingGroup->id,
                    'employee_id' => $empId,
                    'master_shift_id' => $this->default_shift_id,
                    'late_tolerance' => $this->default_late_tolerance ?: 15,
                    'first_visit_store_id' => $this->default_work_location_id,
                ]);
            }

            // 4. Auto-Generate Employee Schedules throughout the running year
            $startDate = Carbon::parse($this->data_applied_date);
            $endDate = $startDate->copy()->endOfYear();

            $totalGenerated = $workingGroup->generateSchedules($startDate, $endDate);

            DB::commit();

            Notification::make()
                ->title('Working Group Berhasil Dibuat!')
                ->body("Berhasil mengonfigurasi {$workingGroup->name} dan mengenerate {$totalGenerated} jadwal presensi untuk " . count($this->selected_employee_ids) . " karyawan hingga akhir tahun (" . $endDate->translatedFormat('d F Y') . ").")
                ->success()
                ->persistent()
                ->send();

            return redirect()->to(EmployeeScheduleRoster::getUrl());
        } catch (\Throwable $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Membuat Working Group')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $shifts = Shift::where('is_active', 1)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->unique(fn($s) => trim(strtoupper($s->name)))
            ->pluck('name', 'id');

        $branches = Branch::whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->unique(fn($b) => trim(strtoupper($b->name)))
            ->pluck('name', 'id');

        $principals = Principal::whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->unique(fn($p) => trim(strtoupper($p->name)))
            ->pluck('name', 'id');

        $workLocations = WorkLocation::whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->unique(fn($w) => trim(strtoupper($w->name)))
            ->pluck('name', 'id');

        // Dropdown available employees (exclude already selected)
        $availEmpQuery = Employee::where('is_active', 1)
            ->whereNotIn('id', $this->selected_employee_ids);

        if (!empty($this->branch_ids)) {
            $selectedBranches = Branch::whereIn('id', $this->branch_ids)->get();
            $branchNames = $selectedBranches->pluck('name')->filter()->toArray();
            $allMatchingBranchIds = Branch::whereIn('name', $branchNames)->pluck('id')->merge($this->branch_ids)->unique()->toArray();
            $availEmpQuery->whereIn('branch_id', $allMatchingBranchIds);
        }

        if (!empty($this->principal_ids)) {
            $selectedPrincipals = Principal::whereIn('id', $this->principal_ids)->get();
            $principalNames = $selectedPrincipals->pluck('name')->filter()->toArray();
            $subdomains = $selectedPrincipals->pluck('subdomain')->filter()->toArray();

            $allMatchingPrincipalIds = Principal::where(function ($q) use ($principalNames, $subdomains) {
                $q->whereIn('name', $principalNames);
                if (!empty($subdomains)) {
                    $q->orWhereIn('subdomain', $subdomains);
                }
            })->pluck('id')->merge($this->principal_ids)->unique()->toArray();

            $availEmpQuery->whereIn('principal_id', $allMatchingPrincipalIds);
        }

        $availableEmployees = $availEmpQuery->orderBy('full_name')
            ->select(['id', 'full_name', 'employee_no'])
            ->get()
            ->mapWithKeys(fn($emp) => [$emp->id => "{$emp->full_name} (" . ($emp->employee_no ?? 'No NIK') . ")"]);

        // Selected employees query for table with search & pagination
        $selectedEmployees = collect();
        $totalSelected = count($this->selected_employee_ids);

        if ($totalSelected > 0) {
            $selQuery = DB::table('employees')
                ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
                ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
                ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
                ->whereIn('employees.id', $this->selected_employee_ids);

            if (!empty(trim($this->table_search))) {
                $q = '%' . trim($this->table_search) . '%';
                $selQuery->where(function ($sq) use ($q) {
                    $sq->where('employees.full_name', 'LIKE', $q)
                        ->orWhere('employees.employee_no', 'LIKE', $q)
                        ->orWhere('positions.name', 'LIKE', $q)
                        ->orWhere('branches.name', 'LIKE', $q)
                        ->orWhere('principals.name', 'LIKE', $q);
                });
            }

            $allMatched = $selQuery->select([
                'employees.id',
                'employees.full_name',
                'employees.employee_no',
                'employees.photo',
                'positions.name as position_name',
                'branches.name as branch_name',
                'principals.name as principal_name',
            ])->orderBy('employees.full_name')->get();

            $totalFiltered = $allMatched->count();
            $totalPages = max(1, (int)ceil($totalFiltered / $this->table_per_page));

            if ($this->table_page > $totalPages) {
                $this->table_page = $totalPages;
            }

            $offset = ($this->table_page - 1) * $this->table_per_page;
            $selectedEmployees = $allMatched->slice($offset, $this->table_per_page)->values();

            $pagination = [
                'page' => $this->table_page,
                'per_page' => $this->table_per_page,
                'total_pages' => $totalPages,
                'total_records' => $totalFiltered,
                'from' => $totalFiltered > 0 ? $offset + 1 : 0,
                'to' => min($offset + $this->table_per_page, $totalFiltered),
            ];
        } else {
            $pagination = [
                'page' => 1,
                'per_page' => $this->table_per_page,
                'total_pages' => 1,
                'total_records' => 0,
                'from' => 0,
                'to' => 0,
            ];
        }

        return [
            'shifts' => $shifts,
            'branches' => $branches,
            'principals' => $principals,
            'workLocations' => $workLocations,
            'availableEmployees' => $availableEmployees,
            'selectedEmployees' => $selectedEmployees,
            'totalSelected' => $totalSelected,
            'pagination' => $pagination,
        ];
    }
}
