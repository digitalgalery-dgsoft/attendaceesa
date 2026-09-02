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
    protected $signature = 'app:fix-orphaned-data';

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
        $this->info("Scanning for soft-deleted employees that have active counterparts with the same NIK...");

        // Get all duplicate groups
        $duplicateGroups = Employee::withTrashed()
            ->select('employee_no')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->where('employee_no', 'not like', 'OD-%')
            ->groupBy('employee_no')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $totalCleaned = 0;

        foreach ($duplicateGroups as $group) {
            $records = Employee::withTrashed()
                ->where('employee_no', $group->employee_no)
                ->get();

            // Find the primary active account
            $primary = $records->firstWhere('employment_status', 'active') 
                    ?? $records->firstWhere('is_active', true) 
                    ?? $records->whereNull('deleted_at')->first();

            if (!$primary) {
                // If no active, just pick the one with most data
                $primary = $records->first(fn ($e) => !empty($e->photo) || !empty($e->device_id) || !empty($e->password))
                    ?: ($records->firstWhere('odoo_id', '!=', null) ?: $records->first());
            }

            $dupIds = $records->where('id', '!=', $primary->id)->pluck('id')->toArray();
            if (empty($dupIds)) {
                continue;
            }

            $this->info("Merging {$records->count()} records for NIK [{$group->employee_no}] {$primary->full_name} -> Primary ID: {$primary->id}");

            // Safely merge attendances (unique by employee_id + attendance_date)
            $this->safeMerge('attendances', 'employee_id', $primary->id, $dupIds, 'attendance_date');
            
            // Safely merge employee_schedules (unique by employee_id + date)
            $this->safeMerge('employee_schedules', 'employee_id', $primary->id, $dupIds, 'date');

            // Merge other tables safely without unique constraints
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
                        // For tables without unique constraints, we can just do a mass update
                        \Illuminate\Support\Facades\DB::table($t['table'])
                            ->whereIn($t['column'], $dupIds)
                            ->update([$t['column'] => $primary->id]);
                    }
                } catch (\Exception $e) {
                    $this->warn("Error updating {$t['table']}: " . $e->getMessage());
                }
            }
            
            $totalCleaned++;
        }

        $this->info("Done! Merged orphaned data for {$totalCleaned} duplicate NIK groups.");
    }

    private function safeMerge($tableName, $foreignKey, $primaryId, $dupIds, $uniqueDateColumn)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
            return;
        }

        foreach ($dupIds as $dupId) {
            $orphanRecords = DB::table($tableName)->where($foreignKey, $dupId)->get();
            foreach ($orphanRecords as $record) {
                // Check if primary already has a record for this date
                $exists = DB::table($tableName)
                    ->where($foreignKey, $primaryId)
                    ->where($uniqueDateColumn, $record->{$uniqueDateColumn})
                    ->exists();

                if (!$exists) {
                    try {
                        DB::table($tableName)->where('id', $record->id)->update([
                            $foreignKey => $primaryId
                        ]);
                    } catch (\Exception $e) {
                        $this->warn("Failed to merge {$tableName} ID {$record->id}: " . $e->getMessage());
                    }
                } else {
                    // Optionally delete the duplicate orphan if it conflicts
                    // DB::table($tableName)->where('id', $record->id)->delete();
                }
            }
        }
    }
}
