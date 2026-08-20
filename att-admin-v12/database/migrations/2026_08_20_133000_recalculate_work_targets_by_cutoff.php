<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeSchedule;
use App\Models\WorkTarget;
use App\Models\Employee;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // Ambil semua jadwal karyawan unik
        $schedules = EmployeeSchedule::select('employee_id', 'schedule_date', 'schedule_type')
            ->where('schedule_type', 'workday')
            ->orderBy('schedule_date')
            ->get();

        if ($schedules->isEmpty()) {
            return;
        }

        // Cache department cutoff
        $employees = Employee::with('department')->get()->keyBy('id');

        $grouped = [];

        foreach ($schedules as $schedule) {
            $emp = $employees->get($schedule->employee_id);
            if (!$emp) continue;

            $cutoff = ($emp->department && isset($emp->department->cutoff_start_date)) 
                ? (int)$emp->department->cutoff_start_date 
                : 26;

            $date = Carbon::parse($schedule->schedule_date);

            if ($cutoff == 1) {
                $monthYear = $date->format('Y-m');
            } else {
                if ($date->day >= $cutoff) {
                    $monthYear = $date->copy()->addMonth()->format('Y-m');
                } else {
                    $monthYear = $date->format('Y-m');
                }
            }

            $key = "{$schedule->employee_id}_{$monthYear}";
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'employee_id' => $schedule->employee_id,
                    'month_year' => $monthYear,
                    'target_hk' => 0,
                ];
            }

            $grouped[$key]['target_hk']++;
        }

        foreach ($grouped as $target) {
            WorkTarget::updateOrCreate(
                [
                    'employee_id' => $target['employee_id'],
                    'month_year' => $target['month_year'],
                ],
                [
                    'target_hk' => $target['target_hk'],
                ]
            );
        }
    }

    public function down(): void
    {
        // No down migration needed
    }
};
