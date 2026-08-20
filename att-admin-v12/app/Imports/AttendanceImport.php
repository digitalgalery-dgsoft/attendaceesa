<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AttendanceImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public int $protectedCount = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        if ($rows->isEmpty()) {
            $this->errors[] = "File Excel kosong atau tidak memiliki baris data.";
            return;
        }

        // Cache all active employees
        $allEmployees = Employee::with(['branch', 'position', 'department'])->get();

        // Scope user access
        $user = auth()->user();
        $accessibleBranchIds = null;
        $accessiblePrincipalIds = null;

        if ($user && !$user->isSuperAdmin()) {
            if ($user->hasBranchRestriction()) {
                $accessibleBranchIds = $user->getAccessibleBranchIds();
            }
            if ($user->hasPrincipalRestriction()) {
                $accessiblePrincipalIds = $user->getAccessiblePrincipalIds();
            }
        }

        foreach ($rows as $index => $row) {
            $rowIndex = $index + 2; // account for header

            $rawNik       = trim((string)($row['nik'] ?? ''));
            $rawName      = trim((string)($row['nama_karyawan'] ?? ($row['nama'] ?? '')));
            $rawStartDate = trim((string)($row['tanggal_mulai'] ?? ($row['tanggal'] ?? ($row['tgl'] ?? ''))));
            $rawEndDate   = trim((string)($row['tanggal_akhir'] ?? ($row['tgl_akhir'] ?? $rawStartDate)));
            $rawInTime    = trim((string)($row['jam_masuk'] ?? ($row['in'] ?? ($row['checkin'] ?? '08:00'))));
            $rawOutTime   = trim((string)($row['jam_keluar'] ?? ($row['out'] ?? ($row['checkout'] ?? '17:00'))));
            $rawStatus    = trim((string)($row['status'] ?? 'present'));
            $keterangan   = trim((string)($row['keterangan'] ?? ($row['catatan'] ?? ($row['note'] ?? ''))));

            // Skip completely empty row
            if (empty($rawNik) && empty($rawName) && empty($rawStartDate)) {
                continue;
            }

            // 1. Cari Karyawan (Prioritas NIK, Fallback Nama)
            $employee = null;
            if (!empty($rawNik)) {
                $cleanNik = preg_replace('/[^0-9A-Za-z]/', '', $rawNik);
                $employee = $allEmployees->first(function ($e) use ($cleanNik) {
                    $eNik = preg_replace('/[^0-9A-Za-z]/', '', (string)$e->employee_no);
                    return $eNik !== '' && $eNik === $cleanNik;
                });
            }

            if (!$employee && !empty($rawName)) {
                $employee = $allEmployees->first(function ($e) use ($rawName) {
                    return strcasecmp($e->full_name, $rawName) === 0;
                });
            }

            if (!$employee) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Karyawan dengan NIK '{$rawNik}' / Nama '{$rawName}' tidak ditemukan.";
                continue;
            }

            // Validasi Hak Akses Admin
            if ($accessibleBranchIds !== null && !in_array($employee->branch_id, $accessibleBranchIds)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Anda tidak memiliki akses ke cabang karyawan {$employee->full_name}.";
                continue;
            }
            if ($accessiblePrincipalIds !== null && !in_array($employee->principal_id, $accessiblePrincipalIds)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Anda tidak memiliki akses ke prinsiple karyawan {$employee->full_name}.";
                continue;
            }

            // 2. Parse Rentang Tanggal
            $parsedStart = $this->parseDate($rawStartDate);
            $parsedEnd = $this->parseDate($rawEndDate) ?: $parsedStart;

            if (!$parsedStart) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Format tanggal mulai '{$rawStartDate}' tidak valid.";
                continue;
            }

            $startDate = Carbon::parse($parsedStart);
            $endDate = Carbon::parse($parsedEnd);

            if ($endDate->lt($startDate)) {
                $endDate = $startDate->copy();
            }

            // 3. Parse Jam Masuk & Jam Keluar
            $inTime = $this->parseTime($rawInTime, '08:00:00');
            $outTime = $this->parseTime($rawOutTime, '17:00:00');

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateStr = $currentDate->toDateString();

                // ─── SAFE GUARD: Cek Apakah Tanggal Sudah Memiliki Check-in Asli ───
                $existingAttendance = Attendance::where('employee_id', $employee->id)
                    ->where('attendance_date', $dateStr)
                    ->first();

                if ($existingAttendance && !empty($existingAttendance->checkin_at) && !$existingAttendance->is_manual_correction) {
                    // Data check-in asli dari aplikasi sudah ada -> JANGAN TIMPA!
                    $this->protectedCount++;
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Tanggal {$dateStr} untuk {$employee->full_name} dilewati karena sudah memiliki data check-in asli aplikasi.";
                    $currentDate->addDay();
                    continue;
                }

                // Ambil atau Hubungkan dengan Jadwal Roster (EmployeeSchedule) jika ada
                $schedule = EmployeeSchedule::where('employee_id', $employee->id)
                    ->where('schedule_date', $dateStr)
                    ->with('shift')
                    ->first();

                // Hitung Jam Masuk & Jam Keluar Lengkap
                $checkinDateTime = Carbon::parse("{$dateStr} {$inTime}");
                $checkoutDateTime = $outTime ? Carbon::parse("{$dateStr} {$outTime}") : null;

                if ($checkoutDateTime && $checkoutDateTime->lt($checkinDateTime)) {
                    $checkoutDateTime->addDay();
                }

                // Hitung Durasi Kerja
                $workDurationMinutes = 0;
                if ($checkoutDateTime) {
                    $workDurationMinutes = (int)$checkinDateTime->diffInMinutes($checkoutDateTime);
                }

                // Hitung Keterlambatan
                $lateMinutes = 0;
                $shiftStart = null;
                $graceMinutes = 0;

                if ($schedule && $schedule->shift) {
                    $shiftStart = Carbon::parse("{$dateStr} {$schedule->shift->start_time}");
                    $graceMinutes = (int)($schedule->shift->grace_period_minutes ?? 0);
                } else {
                    $shiftStart = Carbon::parse("{$dateStr} 08:30:00");
                }

                if ($checkinDateTime->greaterThan($shiftStart->copy()->addMinutes($graceMinutes))) {
                    $lateMinutes = (int)$checkinDateTime->diffInMinutes($shiftStart);
                }

                // Tentukan Status Kehadiran
                $normalizedStatus = strtolower($rawStatus);
                if (!in_array($normalizedStatus, ['present', 'late', 'absent', 'permit', 'sick', 'leave'])) {
                    $normalizedStatus = $lateMinutes > 0 ? 'late' : 'present';
                }

                $correctionNote = !empty($keterangan) 
                    ? $keterangan 
                    : 'Imported via Excel Adjustment (Admin: ' . ($user?->name ?? 'System') . ')';

                // Simpan atau Perbarui Data Attendance
                $attendance = Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'attendance_date' => $dateStr,
                    ],
                    [
                        'employee_schedule_id' => $schedule?->id,
                        'status' => $normalizedStatus,
                        'checkin_at' => $checkinDateTime->toDateTimeString(),
                        'checkout_at' => $checkoutDateTime ? $checkoutDateTime->toDateTimeString() : null,
                        'work_duration_minutes' => $workDurationMinutes,
                        'late_minutes' => $lateMinutes,
                        'early_leave_minutes' => 0,
                        'overtime_minutes' => 0,
                        'is_manual_correction' => true,
                        'correction_note' => $correctionNote,
                    ]
                );

                // Buat / Sinkronkan AttendanceLog untuk Check-in
                $checkinLog = AttendanceLog::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'log_type' => 'checkin',
                    ],
                    [
                        'employee_id' => $employee->id,
                        'employee_schedule_id' => $schedule?->id,
                        'work_location_id' => $schedule?->work_location_id,
                        'logged_at' => $checkinDateTime->toDateTimeString(),
                        'client_logged_at' => $checkinDateTime->toDateTimeString(),
                        'source' => 'import',
                        'validation_status' => 'valid',
                        'is_inside_geofence' => true,
                        'distance_from_location_meter' => 0,
                        'address_text' => 'Disinkronkan melalui Import Excel / Manual Adjustment',
                        'note' => $correctionNote,
                    ]
                );

                $attendance->update(['checkin_log_id' => $checkinLog->id]);

                // Buat / Sinkronkan AttendanceLog untuk Check-out jika ada
                if ($checkoutDateTime) {
                    $checkoutLog = AttendanceLog::updateOrCreate(
                        [
                            'attendance_id' => $attendance->id,
                            'log_type' => 'checkout',
                        ],
                        [
                            'employee_id' => $employee->id,
                            'employee_schedule_id' => $schedule?->id,
                            'work_location_id' => $schedule?->work_location_id,
                            'logged_at' => $checkoutDateTime->toDateTimeString(),
                            'client_logged_at' => $checkoutDateTime->toDateTimeString(),
                            'source' => 'import',
                            'validation_status' => 'valid',
                            'is_inside_geofence' => true,
                            'distance_from_location_meter' => 0,
                            'address_text' => 'Disinkronkan melalui Import Excel / Manual Adjustment',
                            'note' => $correctionNote,
                        ]
                    );

                    $attendance->update(['checkout_log_id' => $checkoutLog->id]);
                }

                $this->importedCount++;
                $currentDate->addDay();
            }
        }
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        $str = trim((string)$value);

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $str)) {
            try {
                return Carbon::parse($str)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            try {
                return Carbon::create($year, $month, $day)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseTime($value, string $default = '08:00:00'): string
    {
        if (empty($value)) return $default;

        if (is_numeric($value) && $value > 0 && $value < 1) {
            // Excel time fraction (e.g. 0.33333333 = 08:00)
            $totalSeconds = round($value * 86400);
            $hours = floor($totalSeconds / 3600);
            $mins = floor(($totalSeconds % 3600) / 60);
            $secs = $totalSeconds % 60;
            return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }

        $str = trim((string)$value);

        if (preg_match('/^(\d{1,2}):(\d{2})(:(\d{2}))?$/', $str, $matches)) {
            $h = (int)$matches[1];
            $m = (int)$matches[2];
            $s = isset($matches[4]) ? (int)$matches[4] : 0;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        try {
            return Carbon::parse($str)->format('H:i:s');
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
