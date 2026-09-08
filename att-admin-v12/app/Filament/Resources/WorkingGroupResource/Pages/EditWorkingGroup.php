<?php

namespace App\Filament\Resources\WorkingGroupResource\Pages;

use App\Filament\Resources\EmployeeSchedules\Pages\EmployeeScheduleRoster;
use App\Filament\Resources\WorkingGroupResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Principal;
use App\Models\WorkingGroup;
use App\Models\WorkingGroupMember;
use App\Models\WorkingGroupRule;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\DB;

class EditWorkingGroup extends CreateWorkingGroup
{
    use InteractsWithRecord;

    protected static string $resource = WorkingGroupResource::class;
    protected string $view = 'filament.pages.working-group-wizard';

    public function getTitle(): string
    {
        return 'Edit Working Group: ' . ($this->record->name ?? '');
    }

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['rules', 'members', 'branch', 'principal', 'defaultShift', 'defaultWorkLocation']);

        $this->name = $this->record->name;
        $this->data_applied_date = $this->record->data_applied_date ? $this->record->data_applied_date->toDateString() : Carbon::now()->toDateString();
        $this->default_shift_id = $this->record->default_shift_id;
        $this->default_late_tolerance = (int)($this->record->default_late_tolerance ?: 15);
        $this->default_work_location_id = $this->record->default_work_location_id;
        $this->currentStep = 1;

        // Populate branch_ids
        $this->branch_ids = [];
        if (!empty($this->record->branch_id)) {
            $this->branch_ids[] = (int)$this->record->branch_id;
        }
        if (!empty($this->record->area)) {
            $areaNames = array_map('trim', explode(',', $this->record->area));
            $matchedBranchIds = Branch::whereIn('name', $areaNames)->pluck('id')->toArray();
            $this->branch_ids = array_values(array_unique(array_merge($this->branch_ids, $matchedBranchIds)));
        }

        // Populate principal_ids
        $this->principal_ids = [];
        if (!empty($this->record->principal_id)) {
            $this->principal_ids[] = (int)$this->record->principal_id;
        }

        // Populate days
        $defaultDays = [
            'Monday'    => ['name' => 'Monday',    'label' => 'Monday (Senin)',    'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Tuesday'   => ['name' => 'Tuesday',   'label' => 'Tuesday (Selasa)',   'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Wednesday' => ['name' => 'Wednesday', 'label' => 'Wednesday (Rabu)', 'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Thursday'  => ['name' => 'Thursday',  'label' => 'Thursday (Kamis)',  'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Friday'    => ['name' => 'Friday',    'label' => 'Friday (Jumat)',    'is_active' => true,  'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Saturday'  => ['name' => 'Saturday',  'label' => 'Saturday (Sabtu)',  'is_active' => false, 'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
            'Sunday'    => ['name' => 'Sunday',    'label' => 'Sunday (Minggu)',   'is_active' => false, 'has_custom_option' => false, 'shift_id' => null, 'late_tolerance' => 15, 'work_location_id' => null],
        ];

        $rulesByDay = $this->record->rules->keyBy('day_of_week');
        $this->days = [];
        foreach ($defaultDays as $dayKey => $defDay) {
            $rule = $rulesByDay->get($dayKey);
            if ($rule) {
                $this->days[$dayKey] = [
                    'name' => $dayKey,
                    'label' => $defDay['label'],
                    'is_active' => (bool)$rule->is_active,
                    'has_custom_option' => (bool)$rule->has_custom_option,
                    'shift_id' => $rule->shift_id ?: $this->default_shift_id,
                    'late_tolerance' => (int)($rule->late_tolerance ?: $this->default_late_tolerance),
                    'work_location_id' => $rule->store_assignment_id ?: $this->default_work_location_id,
                ];
            } else {
                $this->days[$dayKey] = $defDay;
            }
        }

        // Populate selected_employee_ids
        $this->selected_employee_ids = $this->record->members->pluck('employee_id')->filter()->unique()->values()->toArray();
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

            // 1. Update Working Group record
            $this->record->update([
                'name' => $this->name,
                'branch_id' => $firstBranchId,
                'principal_id' => $firstPrincipalId,
                'area' => $branchNames ?: $this->record->area,
                'data_applied_date' => $this->data_applied_date,
                'default_shift_id' => $this->default_shift_id,
                'default_late_tolerance' => $this->default_late_tolerance ?: 15,
                'default_work_location_id' => $this->default_work_location_id,
            ]);

            // 2. Re-sync Rules
            $this->record->rules()->delete();
            foreach ($this->days as $dayKey => $dayData) {
                $isActive = (bool)($dayData['is_active'] ?? false);
                $hasCustom = (bool)($dayData['has_custom_option'] ?? false);

                WorkingGroupRule::create([
                    'working_group_id' => $this->record->id,
                    'day_of_week' => $dayKey,
                    'is_active' => $isActive,
                    'has_custom_option' => $hasCustom,
                    'shift_id' => $hasCustom ? ($dayData['shift_id'] ?: null) : ($this->default_shift_id ?: null),
                    'late_tolerance' => $hasCustom ? ($dayData['late_tolerance'] ?? 15) : ($this->default_late_tolerance ?? 15),
                    'store_assignment_id' => $hasCustom ? ($dayData['work_location_id'] ?: null) : ($this->default_work_location_id ?: null),
                ]);
            }

            // 3. Re-sync Members
            $this->record->members()->delete();
            foreach ($this->selected_employee_ids as $empId) {
                WorkingGroupMember::create([
                    'working_group_id' => $this->record->id,
                    'employee_id' => $empId,
                    'master_shift_id' => $this->default_shift_id,
                    'late_tolerance' => $this->default_late_tolerance ?: 15,
                    'first_visit_store_id' => $this->default_work_location_id,
                ]);
            }

            // 4. Auto-Generate / refresh employee schedules
            $startDate = Carbon::parse($this->data_applied_date);
            $endDate = $startDate->copy()->endOfYear();

            $totalGenerated = $this->record->generateSchedules($startDate, $endDate);

            DB::commit();

            Notification::make()
                ->title('Working Group Berhasil Diperbarui!')
                ->body("Berhasil memperbarui {$this->record->name} dan mengenerate ulang {$totalGenerated} jadwal presensi untuk " . count($this->selected_employee_ids) . " karyawan hingga akhir tahun (" . $endDate->translatedFormat('d F Y') . ").")
                ->success()
                ->persistent()
                ->send();

            return redirect()->to(EmployeeScheduleRoster::getUrl(['activeTab' => 'working_groups']));
        } catch (\Throwable $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Memperbarui Working Group')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Hapus Working Group')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Working Group')
                ->modalDescription('Apakah Anda yakin ingin menghapus Working Group ini beserta seluruh aturan dan anggotanya?')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->action(function () {
                    $this->record->rules()->delete();
                    $this->record->members()->delete();
                    $this->record->delete();

                    Notification::make()
                        ->title('Working Group Berhasil Dihapus')
                        ->success()
                        ->send();

                    return redirect()->to(EmployeeScheduleRoster::getUrl(['activeTab' => 'working_groups']));
                }),
        ];
    }
}
