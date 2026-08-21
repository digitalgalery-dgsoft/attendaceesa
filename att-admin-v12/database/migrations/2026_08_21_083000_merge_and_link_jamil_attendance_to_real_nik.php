<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to link all attendance and activity history to real NIK 3528042504850003
     * and restore Eka Septiani to her proper record.
     */
    public function up(): void
    {
        // 1. Cari Real Abdurrahman Jamil (NIK: 3528042504850003)
        $realJamil = Employee::withTrashed()->where('employee_no', '3528042504850003')->first();

        // 2. Cari Dummy / Temp Jamil Record (NIK: EMP-JAMIL-001)
        $tempJamil = Employee::withTrashed()
            ->where(function ($q) {
                $q->where('employee_no', 'EMP-JAMIL-001')
                  ->orWhere(function ($sq) {
                      $sq->where('full_name', 'Abdurrahman Jamil')
                         ->where('employee_no', '!=', '3528042504850003');
                  });
            })
            ->first();

        if ($realJamil && $tempJamil && $realJamil->id !== $tempJamil->id) {
            $fromId = $tempJamil->id;
            $toId   = $realJamil->id;

            // A. Pindahkan Foto & Device ke Real Jamil
            if (!empty($tempJamil->photo)) {
                $realJamil->photo = $tempJamil->photo;
            }
            if (!empty($tempJamil->device_id)) {
                $realJamil->device_id = $tempJamil->device_id;
                $realJamil->device_name = $tempJamil->device_name;
            }
            if (!empty($tempJamil->fcm_token)) {
                $realJamil->fcm_token = $tempJamil->fcm_token;
            }
            $realJamil->is_active = true;
            $realJamil->save();

            // B. Pindahkan data attendances dengan aman (hindari duplicate per employee_id + attendance_date)
            if (Schema::hasTable('attendances')) {
                $fromAtts = DB::table('attendances')->where('employee_id', $fromId)->get();
                foreach ($fromAtts as $att) {
                    $existingOnTo = DB::table('attendances')
                        ->where('employee_id', $toId)
                        ->where('attendance_date', $att->attendance_date)
                        ->first();

                    if ($existingOnTo) {
                        // Update jika di record temp memiliki jam checkin
                        if (!empty($att->checkin_at)) {
                            DB::table('attendances')->where('id', $existingOnTo->id)->update([
                                'status' => $att->status,
                                'checkin_at' => $att->checkin_at,
                                'checkout_at' => $att->checkout_at,
                                'checkin_log_id' => $att->checkin_log_id,
                                'checkout_log_id' => $att->checkout_log_id,
                                'work_duration_minutes' => $att->work_duration_minutes,
                                'late_minutes' => $att->late_minutes,
                                'early_leave_minutes' => $att->early_leave_minutes,
                                'overtime_minutes' => $att->overtime_minutes,
                                'is_manual_correction' => $att->is_manual_correction,
                                'correction_note' => $att->correction_note,
                            ]);
                        }
                        if (Schema::hasTable('attendance_logs')) {
                            DB::table('attendance_logs')
                                ->where('attendance_id', $att->id)
                                ->update([
                                    'attendance_id' => $existingOnTo->id,
                                    'employee_id' => $toId,
                                ]);
                        }
                        DB::table('attendances')->where('id', $att->id)->delete();
                    } else {
                        DB::table('attendances')->where('id', $att->id)->update(['employee_id' => $toId]);
                    }
                }
            }

            // C. Pindahkan employee_schedules dengan aman (unique on employee_id, schedule_date)
            if (Schema::hasTable('employee_schedules')) {
                $fromScheds = DB::table('employee_schedules')->where('employee_id', $fromId)->get();
                foreach ($fromScheds as $sched) {
                    $exists = DB::table('employee_schedules')
                        ->where('employee_id', $toId)
                        ->where('schedule_date', $sched->schedule_date)
                        ->exists();
                    if ($exists) {
                        DB::table('employee_schedules')->where('id', $sched->id)->delete();
                    } else {
                        DB::table('employee_schedules')->where('id', $sched->id)->update(['employee_id' => $toId]);
                    }
                }
            }

            // D. Pindahkan tabel-tabel aktivitas lainnya secara aman dengan Schema::hasColumn
            $tables = [
                'attendance_logs',
                'itineraries',
                'leave_requests',
                'extra_hours',
                'bap_requests',
                'visit_reports',
                'working_group_members',
                'tracking_histories',
                'work_targets',
                'payslips',
                'meeting_participants',
                'meeting_attendances',
                'sales_reports',
                'sales_pipelines',
                'chat_messages',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'employee_id')) {
                    DB::table($table)->where('employee_id', $fromId)->update(['employee_id' => $toId]);
                }
            }

            // E. Penanganan Eka Septiani & Pembersihan Temp Record
            $existingEka = Employee::withTrashed()->where('employee_no', '7402256409960001')->first();
            if ($existingEka && $existingEka->id !== $fromId) {
                // Eka sudah ada di baris record terpisah, hapus record dummy/temp
                DB::table('employees')->where('id', $fromId)->delete();
            } else {
                // Kembalikan record ini ke Eka Septiani
                DB::table('employees')->where('id', $fromId)->update([
                    'employee_no' => '7402256409960001',
                    'full_name'   => 'Eka Septiani',
                    'photo'       => null,
                    'device_id'   => null,
                    'device_name' => null,
                    'is_active'   => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
