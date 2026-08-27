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
    public int $totalDaysProcessed = 0;
    public array $errors = [];

    protected $shiftsMap = [];
    protected $locationsMap = [];
    protected $defaultLocationId = null;

    public function __construct()
    {
        $this->refreshCaches();
    }

    protected function refreshCaches(): void
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
            if ($loc->code ?? null) {
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

            // Ubah row jadi array dengan keys lowercase dan trim
            $rowArr = $row->toArray();
            $cleanRow = [];
            foreach ($rowArr as $k => $v) {
                $cleanRow[strtolower(trim((string)$k))] = is_string($v) ? trim($v) : $v;
            }

            // Identifikasi Karyawan (KTP / NIK / Employee No)
            $nik = (string)($cleanRow['ktp'] ?? ($cleanRow['nik'] ?? ($cleanRow['nik_karyawan'] ?? ($cleanRow['no_karyawan'] ?? ($cleanRow['employee_no'] ?? '')))));
            
            // Format NIK dari string angka eksponensial jika ada (misal 5.31102E+15)
            if (stripos($nik, 'E+') !== false && is_numeric($nik)) {
                $nik = number_format((float)$nik, 0, '', '');
            }

            $namaKaryawan = (string)($cleanRow['name'] ?? ($cleanRow['nama'] ?? ($cleanRow['nama_karyawan'] ?? ($cleanRow['employee_name'] ?? ''))));

            // Jika baris kosong sama sekali, lewati
            if (empty($nik) && empty($namaKaryawan)) {
                continue;
            }

            // Cari Karyawan: Acuan Utama adalah NIK (employee_no)
            $employee = null;
            if (!empty($nik)) {
                $employee = Employee::where('employee_no', $nik)->first();
            }

            // Fallback: Jika NIK tidak ditemukan / kosong, cari berdasarkan nama karyawan
            if (!$employee && !empty($namaKaryawan)) {
                $employee = Employee::where('full_name', $namaKaryawan)->first();
            }

            if (!$employee) {
                $this->skippedCount++;
                $identifier = !empty($nik) ? "KTP/NIK '{$nik}'" : "Nama '{$namaKaryawan}'";
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

            // Resolusi Lokasi Kerja (Store Code / Store Name / Lokasi)
            $storeCode = (string)($cleanRow['store_code'] ?? ($cleanRow['kode_toko'] ?? ($cleanRow['store'] ?? '')));
            $storeName = (string)($cleanRow['store_name'] ?? ($cleanRow['nama_toko'] ?? ($cleanRow['lokasi_kerja'] ?? ($cleanRow['lokasi'] ?? ''))));
            
            $locationId = null;
            if (!empty($storeCode)) {
                $locationId = $this->locationsMap[strtolower($storeCode)] ?? null;
            }
            if (!$locationId && !empty($storeName)) {
                $locationId = $this->locationsMap[strtolower($storeName)] ?? null;
            }
            if (!$locationId && !empty($storeName)) {
                // Fuzzy search
                $foundLoc = WorkLocation::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($storeName) . '%'])->first();
                if ($foundLoc) {
                    $locationId = $foundLoc->id;
                }
            }
            if (!$locationId) {
                $locationId = $this->defaultLocationId;
            }

            // DETEKSI FORMAT:
            // Cek apakah ini Format Matrix / Per Tanggal (memiliki kolom 1..31)
            $hasDailyColumns = false;
            for ($d = 1; $d <= 31; $d++) {
                if (array_key_exists((string)$d, $cleanRow) || array_key_exists(sprintf('%02d', $d), $cleanRow) || array_key_exists('_' . $d, $cleanRow)) {
                    $hasDailyColumns = true;
                    break;
                }
            }

            if ($hasDailyColumns) {
                // ==========================================================
                // METODE 1: IMPORT PER TANGGAL (MATRIX 1..31)
                // ==========================================================
                $month = $this->parseMonth($cleanRow['month'] ?? ($cleanRow['bulan'] ?? null));
                $year = (int)($cleanRow['year'] ?? ($cleanRow['tahun'] ?? Carbon::now()->year));
                if ($year < 2000 || $year > 2100) {
                    $year = Carbon::now()->year;
                }

                $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $processedForEmp = 0;

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dayKeyStr = (string)$day;
                    $dayKeyPad = sprintf('%02d', $day);
                    $dayKeyUnder = '_' . $day;
                    $dayKeyD = 'd' . $day;
                    $dayKeyTgl = 'tgl_' . $day;

                    $shiftVal = $cleanRow[$dayKeyStr] 
                        ?? ($cleanRow[$dayKeyPad] 
                        ?? ($cleanRow[$dayKeyUnder] 
                        ?? ($cleanRow[$dayKeyD] 
                        ?? ($cleanRow[$dayKeyTgl] ?? null))));

                    if ($shiftVal === null || $shiftVal === '') {
                        continue; // Lewati jika tanggal ini tidak diisi
                    }

                    $shiftValStr = trim((string)$shiftVal);
                    $shiftKey = strtolower($shiftValStr);
                    $scheduleDate = Carbon::createFromDate($year, $month, $day)->toDateString();

                    $isOff = in_array($shiftKey, ['off', 'libur', 'dayoff', 'day off', 'cuti', 'c', '0', '-']);

                    if ($isOff) {
                        EmployeeSchedule::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'schedule_date' => $scheduleDate,
                            ],
                            [
                                'shift_id' => null,
                                'work_location_id' => $locationId,
                                'schedule_type' => 'dayoff',
                                'planned_start_at' => null,
                                'planned_end_at' => null,
                                'created_by' => $currentUser?->id,
                            ]
                        );
                    } else {
                        // Cari Shift
                        $shift = $this->resolveShift($shiftValStr, $employee->company_id ?? 1);

                        $plannedStart = null;
                        $plannedEnd = null;

                        if ($shift && $shift->start_time && $shift->end_time) {
                            $plannedStart = Carbon::parse($scheduleDate . ' ' . $shift->start_time);
                            $plannedEnd = Carbon::parse($scheduleDate . ' ' . $shift->end_time);

                            if ($shift->is_cross_day ?? false) {
                                $plannedEnd->addDay();
                            } elseif ($plannedEnd->lt($plannedStart)) {
                                $plannedEnd->addDay();
                            }
                        }

                        EmployeeSchedule::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'schedule_date' => $scheduleDate,
                            ],
                            [
                                'shift_id' => $shift?->id,
                                'work_location_id' => $locationId,
                                'schedule_type' => 'workday',
                                'planned_start_at' => $plannedStart,
                                'planned_end_at' => $plannedEnd,
                                'created_by' => $currentUser?->id,
                            ]
                        );
                    }

                    $processedForEmp++;
                    $this->totalDaysProcessed++;
                }

                if ($processedForEmp > 0) {
                    $this->importedCount++;
                }
            } else {
                // ==========================================================
                // METODE 2: IMPORT RENTANG TANGGAL (START_DATE -> END_DATE)
                // ==========================================================
                $rawStartDate = $cleanRow['tanggal_mulai'] ?? ($cleanRow['start_date'] ?? ($cleanRow['tgl_mulai'] ?? ($cleanRow['tanggal'] ?? ($cleanRow['date'] ?? null))));
                $rawEndDate = $cleanRow['tanggal_akhir'] ?? ($cleanRow['end_date'] ?? ($cleanRow['tgl_akhir'] ?? ($cleanRow['tanggal'] ?? ($cleanRow['date'] ?? null))));
                $shiftName = (string)($cleanRow['shift'] ?? ($cleanRow['shift_name'] ?? ($cleanRow['nama_shift'] ?? '')));

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

                $shiftKey = strtolower(trim($shiftName));
                $isOff = empty($shiftName) || in_array($shiftKey, ['off', 'libur', 'dayoff', 'day off', 'cuti', '-']);
                
                $shift = null;
                if (!$isOff) {
                    $shift = $this->resolveShift($shiftName, $employee->company_id ?? 1);
                }

                // Ambil Hari Kerja Departemen
                $workingDays = [1, 2, 3, 4, 5];
                if ($employee->department && !empty($employee->department->working_days)) {
                    $wd = $employee->department->working_days;
                    $workingDays = is_array($wd) ? $wd : (json_decode($wd, true) ?: [1, 2, 3, 4, 5]);
                }
                $normalizedWd = array_map('strval', $workingDays);

                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    $plannedStart = null;
                    $plannedEnd = null;
                    $scheduleType = 'dayoff';
                    $shiftIdToUse = null;

                    $dow = strval($currentDate->dayOfWeek);
                    $iso = strval($currentDate->dayOfWeekIso);
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

                    $this->totalDaysProcessed++;
                    $currentDate->addDay();
                }

                $this->importedCount++;
            }
        }
    }

    protected function resolveShift(string $shiftName, int $companyId): ?Shift
    {
        $clean = trim($shiftName);
        $key = strtolower($clean);

        if (isset($this->shiftsMap[$key])) {
            return $this->shiftsMap[$key];
        }

        // Cek di DB
        $found = Shift::whereRaw('LOWER(name) = ?', [$key])
            ->orWhereRaw('LOWER(code) = ?', [$key])
            ->first();

        if ($found) {
            $this->shiftsMap[$key] = $found;
            return $found;
        }

        // Auto-create shift jika nama shift baru (misal FLEKSIBEL01) belum terdaftar di DB
        try {
            $newShift = Shift::create([
                'company_id' => $companyId,
                'name' => $clean,
                'code' => strtoupper($clean),
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
            ]);

            $this->shiftsMap[$key] = $newShift;
            return $newShift;
        } catch (\Throwable $e) {
            return Shift::where('is_active', true)->first();
        }
    }

    protected function parseMonth($rawMonth): int
    {
        if (empty($rawMonth)) {
            return Carbon::now()->month;
        }

        if (is_numeric($rawMonth)) {
            $m = (int)$rawMonth;
            if ($m >= 1 && $m <= 12) {
                return $m;
            }
        }

        $str = strtolower(trim((string)$rawMonth));
        $monthNames = [
            'jan' => 1, 'januari' => 1, 'january' => 1,
            'feb' => 2, 'februari' => 2, 'february' => 2,
            'mar' => 3, 'maret' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'mei' => 5, 'may' => 5,
            'jun' => 6, 'juni' => 6, 'june' => 6,
            'jul' => 7, 'juli' => 7, 'july' => 7,
            'agu' => 8, 'agustus' => 8, 'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'okt' => 10, 'oktober' => 10, 'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'des' => 12, 'desember' => 12, 'dec' => 12, 'december' => 12,
        ];

        return $monthNames[$str] ?? Carbon::now()->month;
    }

    protected function parseDate($rawDate): ?string
    {
        if (empty($rawDate)) return null;

        $rawDate = trim((string)$rawDate);

        if (is_numeric($rawDate)) {
            try {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate);
                return Carbon::instance($dateTime)->toDateString();
            } catch (\Throwable $e) {
            }
        }

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
            }
        }

        try {
            return Carbon::parse(str_replace('/', '-', $rawDate))->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
