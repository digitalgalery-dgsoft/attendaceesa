<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to force deactivate Abdurrahman Jamil on PT ARINA MULTI KARYA
     * and activate solely on PT ANUGRAH TALENTA BERKARYA with photo and device.
     */
    public function up(): void
    {
        $nik = '3528042504850003';

        // 1. Ambil semua record employees dengan NIK 3528042504850003
        $records = DB::table('employees')->where('employee_no', $nik)->get();

        $atbRecord = null;
        $amkRecord = null;

        foreach ($records as $rec) {
            // Cek nama company
            $companyName = DB::table('companies')->where('id', $rec->company_id)->value('name') ?? '';
            $principalName = DB::table('principals')->where('id', $rec->principal_id)->value('name') ?? '';

            if (
                str_contains(strtoupper($companyName), 'ANUGRAH TALENTA BERKARYA') ||
                str_contains(strtoupper($principalName), 'ANUGRAH TALENTA BERKARYA') ||
                $rec->full_name === 'Abdurrahman Jamil'
            ) {
                $atbRecord = $rec;
            } elseif (
                str_contains(strtoupper($companyName), 'ARINA MULTI KARYA') ||
                str_contains(strtoupper($principalName), 'ARINA MULTI KARYA') ||
                $rec->full_name === 'ABDURRAHMAN JAMIL'
            ) {
                $amkRecord = $rec;
            }
        }

        // Jika salah satu belum ketemu, ambil dari urutan record
        if (!$atbRecord && $records->isNotEmpty()) {
            $atbRecord = $records->first();
        }
        if (!$amkRecord && $records->count() > 1) {
            $amkRecord = $records->firstWhere('id', '!=', $atbRecord->id);
        }

        if ($atbRecord && $amkRecord && $atbRecord->id !== $amkRecord->id) {
            $toId = $atbRecord->id;
            $fromId = $amkRecord->id;

            // Tentukan foto yang valid
            $photoToUse = !empty($atbRecord->photo) ? $atbRecord->photo : (!empty($amkRecord->photo) ? $amkRecord->photo : null);

            // A. Update Record ATB -> is_active = true, photo, device
            DB::table('employees')->where('id', $toId)->update([
                'is_active'         => true,
                'photo'             => $photoToUse,
                'device_id'         => 'TECNO TECNO KM7',
                'device_name'       => 'TECNO TECNO KM7',
                'employment_status' => 'permanent',
                'resign_date'       => null,
            ]);

            // B. Update Record AMK -> is_active = false, device = null, status = resigned
            DB::table('employees')->where('id', $fromId)->update([
                'is_active'         => false,
                'device_id'         => null,
                'device_name'       => null,
                'fcm_token'         => null,
                'employment_status' => 'resigned',
                'resign_date'       => '2026-08-01',
            ]);

            // C. Pindahkan seluruh attendances dari AMK ke ATB
            if (Schema::hasTable('attendances')) {
                $amkAttendances = DB::table('attendances')->where('employee_id', $fromId)->get();
                foreach ($amkAttendances as $att) {
                    $existingOnAtb = DB::table('attendances')
                        ->where('employee_id', $toId)
                        ->where('attendance_date', $att->attendance_date)
                        ->first();

                    if ($existingOnAtb) {
                        if (!empty($att->checkin_at)) {
                            DB::table('attendances')->where('id', $existingOnAtb->id)->update([
                                'status'                => $att->status,
                                'checkin_at'            => $att->checkin_at,
                                'checkout_at'           => $att->checkout_at,
                                'checkin_log_id'        => $att->checkin_log_id,
                                'checkout_log_id'       => $att->checkout_log_id,
                                'work_duration_minutes' => $att->work_duration_minutes,
                                'late_minutes'          => $att->late_minutes,
                            ]);
                        }
                        if (Schema::hasTable('attendance_logs')) {
                            DB::table('attendance_logs')->where('attendance_id', $att->id)->update([
                                'attendance_id' => $existingOnAtb->id,
                                'employee_id'   => $toId,
                            ]);
                        }
                        DB::table('attendances')->where('id', $att->id)->delete();
                    } else {
                        DB::table('attendances')->where('id', $att->id)->update(['employee_id' => $toId]);
                    }
                }
            }

            // D. Pindahkan seluruh logs dan tabel lainnya
            $otherTables = [
                'attendance_logs',
                'itineraries',
                'leave_requests',
                'extra_hours',
                'bap_requests',
                'visit_reports',
                'tracking_histories',
            ];
            foreach ($otherTables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'employee_id')) {
                    DB::table($table)->where('employee_id', $fromId)->update(['employee_id' => $toId]);
                }
            }
        } else {
            // Direct safety query untuk record AMK
            DB::table('employees')
                ->where('employee_no', $nik)
                ->where('full_name', 'ABDURRAHMAN JAMIL')
                ->update([
                    'is_active'         => false,
                    'device_id'         => null,
                    'device_name'       => null,
                    'employment_status' => 'resigned',
                    'resign_date'       => '2026-08-01',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
