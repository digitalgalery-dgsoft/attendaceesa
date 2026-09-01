<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to fix Abdurrahman Jamil's principal to PT ANUGRAH TALENTA BERKARYA,
     * consolidate all attendances, schedules, logs, and tracking to the ATB employee record,
     * and deactivate AMK record.
     */
    public function up(): void
    {
        $nik = '3528042504850003';

        // 1. Dapatkan Principal & Company PT ANUGRAH TALENTA BERKARYA
        $atbPrincipal = DB::table('principals')->where('name', 'like', '%ANUGRAH TALENTA BERKARYA%')->first();
        $atbCompany = DB::table('companies')->where('name', 'like', '%ANUGRAH TALENTA BERKARYA%')->first();

        $atbPrincipalId = $atbPrincipal?->id;
        $atbCompanyId = $atbCompany?->id;

        // Dapatkan semua record employee dengan NIK ini
        $allRecords = DB::table('employees')->where('employee_no', $nik)->orderByDesc('id')->get();

        if ($allRecords->isEmpty()) {
            return;
        }

        // Cari record ATB utama (prioritaskan record dengan ID tertinggi atau yang sudah terikat ke ATB)
        $targetAtbRecord = $allRecords->firstWhere('id', 2819340)
            ?? $allRecords->firstWhere('principal_id', $atbPrincipalId)
            ?? $allRecords->first();

        $targetId = $targetAtbRecord->id;

        // Pastikan target record berstatus AKTIF dan terikat ke PT ANUGRAH TALENTA BERKARYA
        DB::table('employees')->where('id', $targetId)->update([
            'is_active'         => true,
            'employment_status' => 'permanent',
            'resign_date'       => null,
            'principal_id'      => $atbPrincipalId ?: $targetAtbRecord->principal_id,
            'company_id'        => $atbCompanyId ?: $targetAtbRecord->company_id,
        ]);

        // Non-aktifkan semua record karyawan lainnya untuk NIK ini
        $otherIds = $allRecords->where('id', '!=', $targetId)->pluck('id')->toArray();

        if (!empty($otherIds)) {
            DB::table('employees')->whereIn('id', $otherIds)->update([
                'is_active'         => false,
                'employment_status' => 'resigned',
                'resign_date'       => '2026-08-01',
                'device_id'         => null,
                'device_name'       => null,
                'fcm_token'         => null,
            ]);

            // Pindahkan seluruh attendances dari record lama ke record target
            if (Schema::hasTable('attendances')) {
                $oldAttendances = DB::table('attendances')->whereIn('employee_id', $otherIds)->get();
                foreach ($oldAttendances as $att) {
                    $existingOnTarget = DB::table('attendances')
                        ->where('employee_id', $targetId)
                        ->where('attendance_date', $att->attendance_date)
                        ->first();

                    if ($existingOnTarget) {
                        if (!empty($att->checkin_at)) {
                            DB::table('attendances')->where('id', $existingOnTarget->id)->update([
                                'status'                => $att->status,
                                'checkin_at'            => $att->checkin_at,
                                'checkout_at'           => $att->checkout_at,
                                'checkin_log_id'        => $att->checkin_log_id,
                                'checkout_log_id'       => $att->checkout_log_id,
                                'work_duration_minutes' => $att->work_duration_minutes,
                                'late_minutes'          => $att->late_minutes,
                                'principal_id'          => $atbPrincipalId ?: $existingOnTarget->principal_id,
                            ]);
                        }
                        if (Schema::hasTable('attendance_logs')) {
                            DB::table('attendance_logs')->where('attendance_id', $att->id)->update([
                                'attendance_id' => $existingOnTarget->id,
                                'employee_id'   => $targetId,
                                'principal_id'  => $atbPrincipalId,
                            ]);
                        }
                        DB::table('attendances')->where('id', $att->id)->delete();
                    } else {
                        DB::table('attendances')->where('id', $att->id)->update([
                            'employee_id'  => $targetId,
                            'principal_id' => $atbPrincipalId,
                        ]);
                    }
                }
            }

            // Pindahkan seluruh logs, schedules, and histories
            $tablesToMigrate = [
                'attendance_logs',
                'employee_schedules',
                'tracking_histories',
                'itineraries',
                'leave_requests',
                'extra_hours',
                'bap_requests',
                'visit_reports',
                'conversations',
                'location_requests',
            ];

            foreach ($tablesToMigrate as $tbl) {
                if (Schema::hasTable($tbl)) {
                    DB::table($tbl)->whereIn('employee_id', $otherIds)->update(['employee_id' => $targetId]);
                }
            }
        }

        // Cari atau buat WorkLocation Inhouse ATB Surabaya
        $atbLocation = DB::table('work_locations')
            ->where('name', 'like', '%ANUGRAH TALENTA BERKARYA%')
            ->orWhere('name', 'like', '%INHOUSE ATB%')
            ->first();

        if (!$atbLocation && $atbCompanyId) {
            $locId = DB::table('work_locations')->insertGetId([
                'name'         => 'INHOUSE ATB SURABAYA',
                'address'      => 'Surabaya, Jawa Timur',
                'latitude'     => -7.235519,
                'longitude'    => 112.735520,
                'radius_meter' => 150,
                'type'         => 'office',
                'company_id'   => $atbCompanyId,
                'principal_id' => $atbPrincipalId,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            $locId = $atbLocation?->id;
        }

        // Update schedule kerja hari ini dan yang akan datang ke lokasi ATB
        if ($locId && Schema::hasTable('employee_schedules')) {
            DB::table('employee_schedules')
                ->where('employee_id', $targetId)
                ->update(['work_location_id' => $locId]);
        }
    }

    public function down(): void
    {
    }
};
