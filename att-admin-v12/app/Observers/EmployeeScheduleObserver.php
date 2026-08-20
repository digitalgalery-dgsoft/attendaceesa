<?php

namespace App\Observers;

use App\Models\EmployeeSchedule;
use App\Models\WorkTarget;
use Carbon\Carbon;

class EmployeeScheduleObserver
{
    /**
     * Handle the EmployeeSchedule "saved" event.
     */
    public function saved(EmployeeSchedule $schedule): void
    {
        $this->syncWorkTarget($schedule);
    }

    /**
     * Handle the EmployeeSchedule "deleted" event.
     */
    public function deleted(EmployeeSchedule $schedule): void
    {
        $this->syncWorkTarget($schedule);
    }

    /**
     * Synchronize WorkTarget according to the schedule cutoff (26 prev month to 25 current month).
     * Only calculates effective working days (schedule_type = 'workday').
     */
    private function syncWorkTarget(EmployeeSchedule $schedule): void
    {
        if (!$schedule->schedule_date) {
            return;
        }

        $date = Carbon::parse($schedule->schedule_date);

        $employee = $schedule->employee ?: \App\Models\Employee::with('department')->find($schedule->employee_id);
        if (!$employee) {
            return;
        }

        $cutoff = ($employee->department && isset($employee->department->cutoff_start_date)) 
            ? (int)$employee->department->cutoff_start_date 
            : 26;

        if ($cutoff == 1) {
            $monthYear = $date->format('Y-m');
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
        } else {
            if ($date->day >= $cutoff) {
                $monthYear = $date->copy()->addMonth()->format('Y-m');
                $start = $date->copy()->setDay($cutoff)->startOfDay();
                $end = $date->copy()->addMonth()->setDay($cutoff - 1)->endOfDay();
            } else {
                $monthYear = $date->format('Y-m');
                $start = $date->copy()->subMonth()->setDay($cutoff)->startOfDay();
                $end = $date->copy()->setDay($cutoff - 1)->endOfDay();
            }
        }

        // Count workday in this period for the employee
        $targetHk = EmployeeSchedule::where('employee_id', $schedule->employee_id)
            ->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()])
            ->where('schedule_type', 'workday')
            ->count();

        WorkTarget::updateOrCreate(
            [
                'employee_id' => $schedule->employee_id,
                'month_year' => $monthYear,
            ],
            [
                'target_hk' => $targetHk,
            ]
        );
    }
}
