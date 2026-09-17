<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class FixOrphanedEmployeeDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-orphaned-data 
                            {--nik= : Target specific employee NIK} 
                            {--today : Only fix employees who have attendance records today} 
                            {--limit= : Limit the number of duplicate groups processed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix orphaned attendances and schedules caused by Odoo Sync deduplication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $todayDate = now()->toDateString();
        $targetNik = $this->option('nik');
        $onlyToday = $this->option('today');
        $limit = $this->option('limit') ? intval($this->option('limit')) : null;

        $this->info("Scanning for duplicate employee records by NIK...");

        $query = Employee::withTrashed()
            ->select('employee_no')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->where('employee_no', 'not like', 'OD-%');

        if (!empty($targetNik)) {
            $query->where('employee_no', $targetNik);
        } elseif ($onlyToday) {
            $todayEmpIds = DB::table('attendances')
                ->where('attendance_date', $todayDate)
                ->whereNotNull('checkin_at')
                ->pluck('employee_id')
                ->unique()
                ->toArray();

            $todayNiks = Employee::withTrashed()
                ->whereIn('id', $todayEmpIds)
                ->whereNotNull('employee_no')
                ->pluck('employee_no')
                ->unique()
                ->toArray();

            $this->info("Found " . count($todayNiks) . " distinct NIKs with check-in records today.");
            $query->whereIn('employee_no', $todayNiks);
        }

        $duplicateGroups = $query->groupBy('employee_no')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($limit && $limit > 0) {
            $duplicateGroups = $duplicateGroups->take($limit);
        }

        $totalCleaned = 0;
        $totalGroups = $duplicateGroups->count();
        $this->info("Processing {$totalGroups} duplicate NIK groups.");

        foreach ($duplicateGroups as $idx => $group) {
            $records = Employee::withTrashed()
                ->where('employee_no', $group->employee_no)
                ->orderBy('id')
                ->get();

            if ($records->count() <= 1) {
                continue;
            }

            // Find the best primary account:
            // 1. Prioritaskan yang memiliki riwayat check-in hari ini
            $primary = $records->first(function ($e) use ($todayDate) {
                return DB::table('attendances')
                    ->where('employee_id', $e->id)
                    ->where('attendance_date', $todayDate)
                    ->whereNotNull('checkin_at')
                    ->exists();
            })
            ?? ($records->firstWhere('is_active', true)
            ?? $records->first(fn ($e) => !empty($e->photo) || !empty($e->device_id) || !empty($e->password))
            ?? ($records->firstWhere('odoo_id', '!=', null) ?: $records->first()));

            $dupIds = $records->where('id', '!=', $primary->id)->pluck('id')->toArray();
            if (empty($dupIds)) {
                continue;
            }

            $currentIdx = $idx + 1;
            $this->info("[{$currentIdx}/{$totalGroups}] Merging {$records->count()} records for NIK [{$group->employee_no}] {$primary->full_name} -> Primary ID: {$primary->id}");

            // 1. Safely merge attendances (with intelligent conflict resolution)
            $this->safeMergeAttendances($primary->id, $dupIds);

            // 2. Safely merge employee_schedules (unique on employee_id + schedule_date)
            $this->safeMergeSchedules($primary->id, $dupIds);

            // 3. Update personal_access_tokens so active mobile sessions immediately point to primary ID
            if (\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->whereIn('tokenable_id', $dupIds)
                    ->where('tokenable_type', 'App\\Models\\Employee')
                    ->update(['tokenable_id' => $primary->id]);
            }

            // 4. Merge other tables without unique date constraints
            $tables = [
                ['table' => 'attendance_logs', 'column' => 'employee_id'],
                ['table' => 'leave_requests', 'column' => 'employee_id'],
                ['table' => 'extra_hours', 'column' => 'employee_id'],
                ['table' => 'bap_requests', 'column' => 'employee_id'],
                ['table' => 'itineraries', 'column' => 'employee_id'],
                ['table' => 'sales_reports', 'column' => 'employee_id'],
                ['table' => 'work_targets', 'column' => 'employee_id'],
                ['table' => 'payslips', 'column' => 'employee_id'],
                ['table' => 'tracking_histories', 'column' => 'employee_id'],
                ['table' => 'report_submissions', 'column' => 'employee_id'],
                ['table' => 'meeting_participants', 'column' => 'employee_id'],
                ['table' => 'meeting_attendances', 'column' => 'employee_id'],
                ['table' => 'location_requests', 'column' => 'employee_id'],
                ['table' => 'report_template_assignments', 'column' => 'employee_id'],
                ['table' => 'employees', 'column' => 'supervisor_id'],
            ];

            foreach ($tables as $t) {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable($t['table'])) {
                        DB::table($t['table'])
                            ->whereIn($t['column'], $dupIds)
                            ->update([$t['column'] => $primary->id]);
                    }
                } catch (\Throwable $e) {
                    $this->warn("Error updating {$t['table']}: " . $e->getMessage());
                }
            }

            // 5. Carry over missing credentials/profile info from duplicates to primary
            $duplicates = Employee::withTrashed()->whereIn('id', $dupIds)->get();
            $dirty = false;
            foreach ($duplicates as $dup) {
                if (empty($primary->photo) && !empty($dup->photo)) {
                    $primary->photo = $dup->photo;
                    $dirty = true;
                }
                if (empty($primary->password) && !empty($dup->password)) {
                    $primary->password = $dup->password;
                    $dirty = true;
                }
                if (empty($primary->device_id) && !empty($dup->device_id)) {
                    $primary->device_id = $dup->device_id;
                    $primary->device_name = $dup->device_name;
                    $dirty = true;
                }
                if (empty($primary->user_id) && !empty($dup->user_id)) {
                    $primary->user_id = $dup->user_id;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $primary->save();
            }

            // 6. Ensure primary is active and duplicates are inactive
            DB::table('employees')->where('id', $primary->id)->update([
                'is_active' => true,
                'deleted_at' => null,
            ]);

            DB::table('employees')->whereIn('id', $dupIds)->update([
                'is_active' => false,
                'employment_status' => 'resigned',
                'deleted_at' => now(),
            ]);

            $totalCleaned++;
        }

        $this->info("Done! Successfully merged and restored data for {$totalCleaned} duplicate NIK groups.");
    }

    private function safeMergeAttendances($primaryId, array $dupIds)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('attendances')) {
            return;
        }

        foreach ($dupIds as $dupId) {
            $orphanRecords = DB::table('attendances')->where('employee_id', $dupId)->get();
            foreach ($orphanRecords as $record) {
                $existing = DB::table('attendances')
                    ->where('employee_id', $primaryId)
                    ->where('attendance_date', $record->attendance_date)
                    ->first();

                if (!$existing) {
                    try {
                        DB::table('attendances')->where('id', $record->id)->update([
                            'employee_id' => $primaryId
                        ]);
                        DB::table('attendance_logs')->where('attendance_id', $record->id)->update([
                            'employee_id' => $primaryId
                        ]);
                    } catch (\Throwable $e) {
                        $this->warn("Failed to transfer attendance ID {$record->id}: " . $e->getMessage());
                    }
                } else {
                    // Conflict resolution:
                    // If primary had absent/blank checkin, but orphan record HAS real checkin data, copy to primary!
                    $updates = [];
                    $existingHasCheckin = !empty($existing->checkin_at) && $existing->checkin_at !== '00:00:00';
                    $orphanHasCheckin   = !empty($record->checkin_at) && $record->checkin_at !== '00:00:00';

                    if (!$existingHasCheckin && $orphanHasCheckin) {
                        $updates['checkin_at']     = $record->checkin_at;
                        $updates['status']         = $record->status;
                        $updates['checkin_log_id'] = $record->checkin_log_id;
                        $updates['late_minutes']   = $record->late_minutes;
                    }

                    $existingHasCheckout = !empty($existing->checkout_at) && $existing->checkout_at !== '00:00:00';
                    $orphanHasCheckout   = !empty($record->checkout_at) && $record->checkout_at !== '00:00:00';

                    if (!$existingHasCheckout && $orphanHasCheckout) {
                        $updates['checkout_at']            = $record->checkout_at;
                        $updates['checkout_log_id']        = $record->checkout_log_id;
                        $updates['work_duration_minutes']  = $record->work_duration_minutes;
                        $updates['early_leave_minutes']    = $record->early_leave_minutes;
                        $updates['overtime_minutes']       = $record->overtime_minutes;
                    }

                    if ($existing->status === 'absent' && in_array($record->status, ['present', 'late', 'incomplete'])) {
                        $updates['status'] = $record->status;
                    }

                    if (!empty($updates)) {
                        DB::table('attendances')->where('id', $existing->id)->update($updates);
                    }

                    // Arahkan logs ke existing attendance
                    DB::table('attendance_logs')->where('attendance_id', $record->id)->update([
                        'attendance_id' => $existing->id,
                        'employee_id'   => $primaryId,
                    ]);

                    // Delete orphan duplicate attendance so unique constraint is satisfied
                    DB::table('attendances')->where('id', $record->id)->delete();
                }
            }
        }
    }

    private function safeMergeSchedules($primaryId, array $dupIds)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('employee_schedules')) {
            return;
        }

        foreach ($dupIds as $dupId) {
            $orphanRecords = DB::table('employee_schedules')->where('employee_id', $dupId)->get();
            foreach ($orphanRecords as $record) {
                $existing = DB::table('employee_schedules')
                    ->where('employee_id', $primaryId)
                    ->where('schedule_date', $record->schedule_date)
                    ->first();

                if (!$existing) {
                    try {
                        DB::table('employee_schedules')->where('id', $record->id)->update([
                            'employee_id' => $primaryId
                        ]);
                    } catch (\Throwable $e) {
                        $this->warn("Failed to transfer schedule ID {$record->id}: " . $e->getMessage());
                    }
                } else {
                    if (empty($existing->shift_id) && !empty($record->shift_id)) {
                        DB::table('employee_schedules')->where('id', $existing->id)->update([
                            'shift_id'         => $record->shift_id,
                            'work_location_id' => $existing->work_location_id ?: $record->work_location_id,
                            'schedule_type'    => $record->schedule_type,
                            'planned_start_at' => $existing->planned_start_at ?: $record->planned_start_at,
                            'planned_end_at'   => $existing->planned_end_at ?: $record->planned_end_at,
                        ]);
                    }

                    DB::table('attendances')
                        ->where('employee_schedule_id', $record->id)
                        ->update(['employee_schedule_id' => $existing->id]);

                    // Existing schedule already present on primary; remove duplicate orphan
                    DB::table('employee_schedules')->where('id', $record->id)->delete();
                }
            }
        }
    }
}
