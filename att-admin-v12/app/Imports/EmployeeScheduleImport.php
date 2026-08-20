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
            
            // Format NIK dari string angka eksponensial jika ada
            if (stripos($nik, 'E+') !== false && is_numeric($nik)) {
                $nik = number_format((float)$nik, 0, '', '');
            }

            $rawStartDate = $row['tanggal_mulai'] ?? ($row['start_date'] ?? ($row['tgl_mulai'] ?? ($row['tanggal'] ?? ($row['date'] ?? null))));
            $rawEndDate = $row['tanggal_akhir'] ?? ($row['end_date'] ?? ($row['tgl_akhir'] ?? ($row['tanggal'] ?? ($row['date'] ?? null))));
            $shiftName = trim((string)($row['shift'] ?? ($row['shift_name'] ?? ($row['nama_shift'] ?? ''))));
            $locationName = trim((string)($row['lokasi_kerja'] ?? ($row['lokasi'] ?? ($row['work_location'] ?? ''))));

            $namaKaryawan = trim((string)($row['nama_karyawan'] ?? ($row['nama'] ?? ($row['name'] ?? ($row['employee_name'] ?? '')))));

            if (empty($nik) && empty($namaKaryawan) && empty($rawStartDate)) {
                continue; // Lewati baris kosong
            }

            // Cari Karyawan: Acuan Utama adalah NIK (employee_no / nik)
            $employee = null;
            if (!empty($nik)) {
                $employee = Employee::where('employee_no', $nik)->orWhere('nik', $nik)->first();
            }

            // Fallback: Jika NIK tidak ditemukan / kosong, cari berdasarkan nama karyawan
            if (!$employee && !empty($namaKaryawan)) {
                $employee = Employee::where('full_name', $namaKaryawan)->first();
            }

            if (!$employee) {
                $this->skippedCount++;
                $identifier = !empty($nik) ? "NIK '{$nik}'" : "Nama '{$namaKaryawan}'";
                $this->errors[] = "Baris {$rowIndex}: Karyawan dengan {$identifier} tidak ditemukan.";
                continue;
            }

            // Validasi Hak Akses User
            if (!$isSuperAdmin) {
                if ($accessibleBranchIds !== null && !in_array($employee->branch_id, $accessibleBranchIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Karyawan '{$employee->full_name}' di luar wewenang Area Anda.";
                    continue;
                }
                if ($accessiblePrincipalIds !== null && !in_array($employee->principal_id, $accessiblePrincipalIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Karyawan '{$employee->full_name}' di luar wewenang Prinsiple Anda.";
                    continue;
                }
            }

            // Parse Rentang Tanggal
            $startDateStr = $this->parseDate($rawStartDate);
            $endDateStr = $this->parseDate($rawEndDate) ?: $startDateStr;

            if (!$startDateStr) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Format tanggal mulai '{$rawStartDate}' tidak valid.";
                continue;
            }

            $startDate = Carbon::parse($startDateStr);
            $endDate = Carbon::parse($endDateStr);

            if ($endDate->lt($startDate)) {
                $endDate = $startDate->copy();
            }

            // Resolusi Shift
            $shiftKey = strtolower($shiftName);
            $isOff = empty($shiftName) || in_array($shiftKey, ['off', 'libur', 'dayoff', 'day off', 'cuti', '-']);
            
            $shift = null;
            if (!$isOff) {
                $shift = $this->shiftsMap[$shiftKey] ?? null;
            }

            // Resolusi Lokasi Kerja
            $locationId = null;
            if (!empty($locationName)) {
                $locKey = strtolower($locationName);
                $locationId = $this->locationsMap[$locKey] ?? null;
            }
            if (!$locationId) {
                $locationId = $this->defaultLocationId;
            }

            // Ambil Hari Kerja Departemen
            $workingDays = [];
            if ($employee->department && !empty($employee->department->working_days)) {
                $wd = $employee->department->working_days;
                $workingDays = is_array($wd) ? $wd : (json_decode($wd, true) ?: [1, 2, 3, 4, 5]);
            } else {
                $workingDays = [1, 2, 3, 4, 5]; // Default Mon-Fri
            }
            $normalizedWd = array_map('strval', $workingDays);

            // Generate Setiap Tanggal dalam Rentang
            $currentDate = $startDate->copy();
            $recordsCreated = 0;

            while ($currentDate->lte($endDate)) {
                $plannedStart = null;
                $plannedEnd = null;
                $scheduleType = 'dayoff';
                $shiftIdToUse = null;

                $isSingleDay = $startDate->equalTo($endDate);

                if (!$isOff && ($isSingleDay || in_array($dow, $normalizedWd) || in_array($iso, $normalizedWd))) {
                    $scheduleType = 'workday';
                    $shiftIdToUse = $shift?->id;

                    if ($shift && $shift->start_time && $shift->end_time) {
                        $plannedStart = Carbon::parse($currentDate->toDateString() . ' ' . $shift->start_time);
                        $plannedEnd = Carbon::parse($currentDate->toDateString() . ' ' . $shift->end_time);

                        if ($shift->is_cross_day ?? false) {
                            $plannedEnd->addDay();
                        } elseif ($plannedEnd->lt($plannedStart)) {
                            $plannedEnd->addDay();
                        }
                    }
                }

                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'schedule_date' => $currentDate->toDateString(),
                    ],
                    [
                        'shift_id' => $shiftIdToUse,
                        'work_location_id' => $locationId,
                        'schedule_type' => $scheduleType,
                        'planned_start_at' => $plannedStart,
                        'planned_end_at' => $plannedEnd,
                        'created_by' => $currentUser?->id,
                    ]
                );

                $recordsCreated++;
                $currentDate->addDay();
            }

            $this->importedCount++;
        }
    }

    protected function parseDate($rawDate): ?string
    {
        if (empty($rawDate)) return null;

        $rawDate = trim((string)$rawDate);

        // Jika format serial tanggal Excel numerik
        if (is_numeric($rawDate)) {
            try {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate);
                return Carbon::instance($dateTime)->toDateString();
            } catch (\Throwable $e) {
                // Abaikan dan lanjut ke string parser
            }
        }

        // Coba format tanggal umum di Indonesia (d/m/Y, d-m-Y, Y-m-d)
        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'Y/m/d',
            'd M Y',
            'd F Y',
            'm/d/Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $rawDate);
                if ($parsed && $parsed->format($format) === $rawDate) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable $e) {
                // Coba format berikutnya
            }
        }

        // Fallback terakhir dengan Carbon parse
        try {
            return Carbon::parse(str_replace('/', '-', $rawDate))->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
