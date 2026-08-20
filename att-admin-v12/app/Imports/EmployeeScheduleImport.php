<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeScheduleImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    protected $shiftsMap = [];
    protected $locationsMap = [];
    protected $defaultLocationId = null;

    public function __construct()
    {
        // Cache shifts
        $shifts = Shift::all();
        foreach ($shifts as $s) {
            $this->shiftsMap[strtolower(trim($s->name))] = $s;
            if ($s->code) {
                $this->shiftsMap[strtolower(trim($s->code))] = $s;
            }
        }

        // Cache locations
        $locations = WorkLocation::all();
        foreach ($locations as $loc) {
            $this->locationsMap[strtolower(trim($loc->name))] = $loc->id;
            if ($loc->code) {
                $this->locationsMap[strtolower(trim($loc->code))] = $loc->id;
            }
        }
        $this->defaultLocationId = $locations->first()?->id;
    }

    public function collection(Collection $rows)
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        $accessibleBranchIds = ($currentUser && !$isSuperAdmin && $currentUser->hasBranchRestriction()) 
            ? $currentUser->getAccessibleBranchIds() 
            : null;
        $accessiblePrincipalIds = ($currentUser && !$isSuperAdmin && $currentUser->hasPrincipalRestriction()) 
            ? $currentUser->getAccessiblePrincipalIds() 
            : null;

        $rowIndex = 1; // Row 1 is header

        foreach ($rows as $row) {
            $rowIndex++;

            $nik = trim((string)($row['nik'] ?? ($row['nik_karyawan'] ?? ($row['no_karyawan'] ?? ($row['employee_no'] ?? '')))));
            $rawDate = $row['tanggal'] ?? ($row['date'] ?? ($row['schedule_date'] ?? null));
            $shiftName = trim((string)($row['shift'] ?? ($row['shift_name'] ?? ($row['nama_shift'] ?? ''))));
            $locationName = trim((string)($row['lokasi_kerja'] ?? ($row['lokasi'] ?? ($row['work_location'] ?? ''))));
            $scheduleTypeRaw = strtolower(trim((string)($row['tipe_jadwal'] ?? ($row['schedule_type'] ?? ''))));

            if (empty($nik) && empty($rawDate)) {
                continue; // Skip blank rows
            }

            if (empty($nik)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: NIK Karyawan kosong.";
                continue;
            }

            // Find Employee
            $employee = Employee::where('employee_no', $nik)->orWhere('nik', $nik)->first();
            if (!$employee) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Karyawan dengan NIK '{$nik}' tidak ditemukan.";
                continue;
            }

            // Check User Access Scope
            if (!$isSuperAdmin) {
                if ($accessibleBranchIds !== null && !in_array($employee->branch_id, $accessibleBranchIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Karyawan '{$employee->full_name}' berada di luar Area akses Anda.";
                    continue;
                }
                if ($accessiblePrincipalIds !== null && !in_array($employee->principal_id, $accessiblePrincipalIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Karyawan '{$employee->full_name}' berada di luar Prinsiple akses Anda.";
                    continue;
                }
            }

            // Parse Date
            $scheduleDate = $this->parseDate($rawDate);
            if (!$scheduleDate) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Format tanggal '{$rawDate}' tidak valid.";
                continue;
            }

            // Resolve Shift & Schedule Type
            $shiftKey = strtolower($shiftName);
            $isOff = empty($shiftName) || in_array($shiftKey, ['off', 'libur', 'dayoff', 'day off', 'cuti', '-']);
            
            $shiftId = null;
            $plannedStart = null;
            $plannedEnd = null;
            $scheduleType = $isOff ? 'dayoff' : 'workday';

            if (!empty($scheduleTypeRaw) && in_array($scheduleTypeRaw, ['workday', 'dayoff', 'holiday', 'remote', 'field'])) {
                $scheduleType = $scheduleTypeRaw;
            }

            if (!$isOff) {
                $shift = $this->shiftsMap[$shiftKey] ?? null;
                if ($shift) {
                    $shiftId = $shift->id;
                    if ($shift->start_time && $shift->end_time) {
                        $plannedStart = Carbon::parse($scheduleDate . ' ' . $shift->start_time);
                        $plannedEnd = Carbon::parse($scheduleDate . ' ' . $shift->end_time);

                        if ($shift->is_cross_day ?? false) {
                            $plannedEnd->addDay();
                        } elseif ($plannedEnd->lt($plannedStart)) {
                            $plannedEnd->addDay();
                        }
                    }
                } else {
                    // Shift not found, treat as workday without specific shift or dayoff
                    $scheduleType = 'workday';
                }
            }

            // Resolve Location
            $locationId = null;
            if (!empty($locationName)) {
                $locKey = strtolower($locationName);
                $locationId = $this->locationsMap[$locKey] ?? null;
            }
            if (!$locationId) {
                $locationId = $this->defaultLocationId;
            }

            // Update or Create Schedule
            EmployeeSchedule::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'schedule_date' => $scheduleDate,
                ],
                [
                    'shift_id' => $shiftId,
                    'work_location_id' => $locationId,
                    'schedule_type' => $scheduleType,
                    'planned_start_at' => $plannedStart,
                    'planned_end_at' => $plannedEnd,
                    'created_by' => $currentUser?->id,
                ]
            );

            $this->importedCount++;
        }
    }

    protected function parseDate($rawDate): ?string
    {
        if (empty($rawDate)) return null;

        if (is_numeric($rawDate)) {
            try {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate);
                return Carbon::instance($dateTime)->toDateString();
            } catch (\Throwable $e) {
                // Ignore and try string parse
            }
        }

        try {
            return Carbon::parse($rawDate)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
