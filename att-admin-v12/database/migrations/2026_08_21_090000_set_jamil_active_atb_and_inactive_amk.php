<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to ensure Abdurrahman Jamil is active only in PT ANUGRAH TALENTA BERKARYA,
     * while the older record in PT ARINA MULTI KARYA is set to inactive / non-aktif.
     */
    public function up(): void
    {
        $nik = '3528042504850003';
        $employees = Employee::withTrashed()->where('employee_no', $nik)->get();

        if ($employees->count() >= 2) {
            // Cari record ATB dan AMK
            $atbRecord = $employees->first(function ($e) {
                return ($e->company && str_contains(strtoupper($e->company->name), 'ANUGRAH TALENTA BERKARYA'))
                    || ($e->principal && str_contains(strtoupper($e->principal->name), 'ANUGRAH TALENTA BERKARYA'))
                    || str_contains(strtoupper($e->position?->name ?? ''), 'IT - SURABAYA');
            }) ?: $employees->first();

            $amkRecord = $employees->first(function ($e) use ($atbRecord) {
                return $e->id !== $atbRecord->id;
            });

            if ($atbRecord && $amkRecord) {
                $fromId = $amkRecord->id;
                $toId   = $atbRecord->id;

                // 1. Salin Foto Profil dari AMK ke ATB jika ATB belum memiliki foto
                if (empty($atbRecord->photo) && !empty($amkRecord->photo)) {
                    $atbRecord->photo = $amkRecord->photo;
                }

                // 2. Pasang Device ke ATB
                if (!empty($amkRecord->device_id)) {
                    $atbRecord->device_id = $amkRecord->device_id;
                    $atbRecord->device_name = $amkRecord->device_name;
                    $atbRecord->fcm_token = $amkRecord->fcm_token;
                }

                // 3. Set ATB sebagai AKTIF
                $atbRecord->is_active = true;
                $atbRecord->resign_date = null;
                $atbRecord->save();

                // 4. Set AMK sebagai NON-AKTIF (Resign) dan lepas device binding
                $amkRecord->is_active = false;
                $amkRecord->device_id = null;
                $amkRecord->device_name = null;
                $amkRecord->fcm_token = null;
                if (empty($amkRecord->resign_date)) {
                    $amkRecord->resign_date = '2026-08-01';
                }
                $amkRecord->save();

                // 5. Pindahkan seluruh riwayat attendances dari AMK ke ATB dengan aman
                if (Schema::hasTable('attendances')) {
                    $fromAtts = DB::table('attendances')->where('employee_id', $fromId)->get();
                    foreach ($fromAtts as $att) {
                        $existingOnTo = DB::table('attendances')
                            ->where('employee_id', $toId)
                            ->where('attendance_date', $att->attendance_date)
                            ->first();

                        if ($existingOnTo) {
                            if (!empty($att->checkin_at)) {
                                DB::table('attendances')->where('id', $existingOnTo->id)->update([
                                    'status'                => $att->status,
                                    'checkin_at'            => $att->checkin_at,
                                    'checkout_at'           => $att->checkout_at,
                                    'checkin_log_id'        => $att->checkin_log_id,
                                    'checkout_log_id'       => $att->checkout_log_id,
                                    'work_duration_minutes' => $att->work_duration_minutes,
                                    'late_minutes'          => $att->late_minutes,
                                    'early_leave_minutes'   => $att->early_leave_minutes,
                                    'overtime_minutes'      => $att->overtime_minutes,
                                    'is_manual_correction'  => $att->is_manual_correction,
                                    'correction_note'       => $att->correction_note,
                                ]);
                            }
                            if (Schema::hasTable('attendance_logs')) {
                                DB::table('attendance_logs')
                                    ->where('attendance_id', $att->id)
                                    ->update([
                                        'attendance_id' => $existingOnTo->id,
                                        'employee_id'   => $toId,
                                    ]);
                            }
                            DB::table('attendances')->where('id', $att->id)->delete();
                        } else {
                            DB::table('attendances')->where('id', $att->id)->update(['employee_id' => $toId]);
                        }
                    }
                }

                // 6. Pindahkan employee_schedules
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

                // 7. Pindahkan tabel aktivitas lainnya
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
