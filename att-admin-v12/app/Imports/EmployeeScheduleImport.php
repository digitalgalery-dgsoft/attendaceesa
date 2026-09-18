<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmployeeScheduleImport implements ToCollection
{
    public int $totalRowsRead = 0;
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public int $totalDaysProcessed = 0;
    public array $errors = [];
    public array $detectedColumns = [];
    public ?int $headerRowIndex = null;
    public string $detectedFormat = 'unknown'; // 'matrix' | 'range' | 'unknown'

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
        if ($rows->isEmpty()) {
            $this->errors[] = "File Excel kosong, tidak ditemukan baris data.";
            return;
        }

        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        $accessibleBranchIds = ($currentUser && !$isSuperAdmin && $currentUser->hasBranchRestriction()) 
            ? $currentUser->getAccessibleBranchIds() 
            : null;
        $accessiblePrincipalIds = ($currentUser && !$isSuperAdmin && $currentUser->hasPrincipalRestriction()) 
            ? $currentUser->getAccessiblePrincipalIds() 
            : null;

        // =========================================================================
        // 1. AUTO-DETECT HEADER ROW (Scan hingga 15 baris pertama)
        // =========================================================================
        $headerRowIdx = null;
        $rawHeaders = [];

        $keywords = [
            'ktp', 'nik', 'name', 'nama', 'karyawan', 'employee',
            'store', 'toko', 'outlet', 'bulan', 'month', 'tahun',
            'year', 'shift', 'tanggal', 'date', 'tgl'
        ];

        foreach ($rows->slice(0, 15) as $rIdx => $rawRow) {
            $rowItems = is_array($rawRow) ? $rawRow : $rawRow->toArray();
            $matches = 0;
            $candidateHeaders = [];

            foreach ($rowItems as $cIdx => $cellVal) {
                if ($cellVal === null || $cellVal === '') continue;
                $cellStr = strtolower(trim((string)$cellVal));
                $candidateHeaders[$cIdx] = $cellStr;

                foreach ($keywords as $kw) {
                    if (str_contains($cellStr, $kw) || $cellStr === (string)$kw) {
                        $matches++;
                        break;
                    }
                }
            }

            // Jika baris ini memiliki minimal 2 kata kunci header yang cocok
            if ($matches >= 2) {
                $headerRowIdx = $rIdx;
                $rawHeaders = $candidateHeaders;
                break;
            }
        }

        // Fallback: Jika tidak ada baris yang cocok >= 2 keyword, ambil baris pertama yang memiliki isi >= 2 kolom
        if ($headerRowIdx === null) {
            foreach ($rows->slice(0, 5) as $rIdx => $rawRow) {
                $rowItems = is_array($rawRow) ? $rawRow : $rawRow->toArray();
                $nonEmpty = array_filter($rowItems, fn($v) => $v !== null && trim((string)$v) !== '');
                if (count($nonEmpty) >= 2) {
                    $headerRowIdx = $rIdx;
                    foreach ($rowItems as $cIdx => $cellVal) {
                        if ($cellVal !== null && trim((string)$cellVal) !== '') {
                            $rawHeaders[$cIdx] = strtolower(trim((string)$cellVal));
                        }
                    }
                    break;
                }
            }
        }

        if ($headerRowIdx === null) {
            $this->errors[] = "Tidak dapat menemukan baris header pada file Excel. Pastikan file memiliki baris judul kolom seperti NIK / KTP, Nama, Shift / Tanggal.";
            return;
        }

        $this->headerRowIndex = $headerRowIdx;

        // Normalisasi nama kolom header
        $headerMap = []; // [colIndex => normalizedKey]
        foreach ($rawHeaders as $cIdx => $hVal) {
            $norm = str_replace([' ', '-', '.'], '_', $hVal);
            $norm = preg_replace('/[^a-z0-9_]/', '', $norm);
            $norm = trim($norm, '_');
            if (!empty($norm)) {
                $headerMap[$cIdx] = $norm;
                $this->detectedColumns[] = $norm;
            }
        }

        // =========================================================================
        // 2. MAPPING KOLOM SPESIFIK BERDASARKAN HEADER
        // =========================================================================
        $nikColIdx = null;
        $nameColIdx = null;
        $principalColIdx = null;
        $storeCodeColIdx = null;
        $storeNameColIdx = null;
        $monthColIdx = null;
        $yearColIdx = null;
        $startDateColIdx = null;
        $endDateColIdx = null;
        $shiftColIdx = null;
        $dailyColsMap = []; // [dayNumber (1..31) => cIdx]

        $nikKeywords = [
            'ktp', 'nik', 'no_ktp', 'no_nik', 'nomor_ktp', 'nomor_nik',
            'nik_karyawan', 'no_karyawan', 'nomor_karyawan', 'employee_no',
            'employee_id', 'id_karyawan', 'nip', 'ktp_nik', 'nik_ktp', 'no_id'
        ];
        $nameKeywords = [
            'name', 'nama', 'nama_karyawan', 'employee_name', 'nama_lengkap',
            'full_name', 'nama_pegawai', 'pegawai', 'karyawan', 'staff', 'nama_staff'
        ];
        $principalKeywords = [
            'prinsiple', 'principal', 'nama_prinsiple', 'nama_principal',
            'client', 'nama_client', 'account', 'customer'
        ];
        $storeCodeKeywords = [
            'store_code', 'kode_toko', 'kode_store', 'store', 'toko',
            'outlet_code', 'kode_outlet', 'branch_code', 'kode_cabang'
        ];
        $storeNameKeywords = [
            'store_name', 'nama_toko', 'nama_store', 'lokasi_kerja', 'lokasi',
            'nama_lokasi', 'outlet', 'nama_outlet', 'work_location', 'area', 'cabang'
        ];
        $monthKeywords = ['month', 'bulan', 'periode_bulan', 'bln'];
        $yearKeywords = ['year', 'tahun', 'periode_tahun', 'thn'];
        $startDateKeywords = ['tanggal_mulai', 'start_date', 'tgl_mulai', 'tgl_awal', 'start', 'mulai', 'tanggal', 'tgl', 'date'];
        $endDateKeywords = ['tanggal_akhir', 'end_date', 'tgl_akhir', 'tgl_selesai', 'end', 'selesai', 'sampai'];
        $shiftKeywords = ['shift', 'shift_name', 'nama_shift', 'kode_shift', 'shift_code', 'jam_kerja'];

        foreach ($headerMap as $cIdx => $colKey) {
            if ($nikColIdx === null && in_array($colKey, $nikKeywords)) {
                $nikColIdx = $cIdx;
                continue;
            }
            if ($nameColIdx === null && in_array($colKey, $nameKeywords)) {
                $nameColIdx = $cIdx;
                continue;
            }
            if ($principalColIdx === null && in_array($colKey, $principalKeywords)) {
                $principalColIdx = $cIdx;
                continue;
            }
            if ($storeCodeColIdx === null && in_array($colKey, $storeCodeKeywords)) {
                $storeCodeColIdx = $cIdx;
                continue;
            }
            if ($storeNameColIdx === null && in_array($colKey, $storeNameKeywords)) {
                $storeNameColIdx = $cIdx;
                continue;
            }
            if ($monthColIdx === null && in_array($colKey, $monthKeywords)) {
                $monthColIdx = $cIdx;
                continue;
            }
            if ($yearColIdx === null && in_array($colKey, $yearKeywords)) {
                $yearColIdx = $cIdx;
                continue;
            }
            if ($startDateColIdx === null && in_array($colKey, $startDateKeywords)) {
                $startDateColIdx = $cIdx;
                continue;
            }
            if ($endDateColIdx === null && in_array($colKey, $endDateKeywords)) {
                $endDateColIdx = $cIdx;
                continue;
            }
            if ($shiftColIdx === null && in_array($colKey, $shiftKeywords)) {
                $shiftColIdx = $cIdx;
                continue;
            }

            // Kolom tanggal harian 1..31 (Matrix)
            $cleanDayNum = preg_replace('/^(d|tgl_|_)/', '', $colKey);
            if (is_numeric($cleanDayNum)) {
                $dInt = (int)$cleanDayNum;
                if ($dInt >= 1 && $dInt <= 31) {
                    $dailyColsMap[$dInt] = $cIdx;
                }
            }
        }

        // Validasi ketersediaan kolom kunci (NIK atau Nama)
        if ($nikColIdx === null && $nameColIdx === null) {
            $colsFound = !empty($this->detectedColumns) ? implode(', ', array_slice($this->detectedColumns, 0, 8)) : 'kosong';
            $this->errors[] = "Kolom NIK / KTP atau Nama Karyawan tidak terdeteksi pada baris header (Baris ke-" . ($headerRowIdx + 1) . "). Kolom yang terbaca pada file: {$colsFound}.";
            return;
        }

        $isMatrix = count($dailyColsMap) >= 3;
        $this->detectedFormat = $isMatrix ? 'matrix' : 'range';

        // =========================================================================
        // 3. PROSES BARIS DATA
        // =========================================================================
        $dataRows = $rows->slice($headerRowIdx + 1);

        foreach ($dataRows as $rIdx => $rawRow) {
            $excelRowNum = $rIdx + 1; // 1-indexed baris di Excel
            $rowItems = is_array($rawRow) ? $rawRow : $rawRow->toArray();

            // Cek apakah baris kosong total
            $nonEmptyValues = array_filter($rowItems, fn($v) => $v !== null && trim((string)$v) !== '');
            if (empty($nonEmptyValues)) {
                continue; // Lewati baris kosong tanpa dianggap error
            }

            $this->totalRowsRead++;

            // Ambil NIK dan bersihkan
            $rawNik = $nikColIdx !== null ? (string)($rowItems[$nikColIdx] ?? '') : '';
            $rawNik = trim($rawNik, " \t\n\r\0\x0B'`");
            if (stripos($rawNik, 'E+') !== false && is_numeric($rawNik)) {
                $rawNik = sprintf('%.0f', (float)$rawNik);
            }
            if (str_ends_with($rawNik, '.0')) {
                $rawNik = substr($rawNik, 0, -2);
            }
            $cleanNik = preg_replace('/[^0-9A-Za-z]/', '', $rawNik);

            // Ambil Nama Karyawan
            $namaKaryawan = $nameColIdx !== null ? trim((string)($rowItems[$nameColIdx] ?? '')) : '';

            // Lewati baris ringkasan / footer (misal "TOTAL", "Grand Total", dsb)
            $combinedLower = strtolower($rawNik . ' ' . $namaKaryawan);
            if (str_contains($combinedLower, 'total') || str_contains($combinedLower, 'grand total') || str_contains($combinedLower, 'mengetahui')) {
                continue;
            }

            // Jika baris berisi sesuatu tapi NIK dan Nama dua-duanya kosong
            if (empty($rawNik) && empty($cleanNik) && empty($namaKaryawan)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$excelRowNum}: Kolom NIK/KTP dan Nama kosong (data baris tidak valid).";
                continue;
            }

            $prinsipleCol = $principalColIdx !== null ? trim((string)($rowItems[$principalColIdx] ?? '')) : '';

            // =====================================================================
            // 4. PENCARIAN KARYAWAN DI DATABASE
            // =====================================================================
            $employee = null;
            $hasNikColumn = Schema::hasColumn('employees', 'nik');

            // 1. Cari berdasarkan NIK (employee_no / nik)
            if (!empty($rawNik) || !empty($cleanNik)) {
                $empQuery = Employee::query();
                $empQuery->where(function ($q) use ($rawNik, $cleanNik, $hasNikColumn) {
                    $q->where('employee_no', $rawNik);
                    if (!empty($cleanNik) && $cleanNik !== $rawNik) {
                        $q->orWhere('employee_no', $cleanNik);
                    }
                    if ($hasNikColumn) {
                        $q->orWhere('nik', $rawNik);
                        if (!empty($cleanNik) && $cleanNik !== $rawNik) {
                            $q->orWhere('nik', $cleanNik);
                        }
                    }
                });

                if (!empty($prinsipleCol)) {
                    $pTrim = trim($prinsipleCol);
                    $empQuery->whereHas('principal', function ($q) use ($pTrim) {
                        $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($pTrim)]);
                    });
                }

                $employee = (clone $empQuery)->where('is_active', true)->whereNull('deleted_at')->first()
                    ?? $empQuery->first();

                // Coba lagi tanpa filter prinsiple jika tidak ketemu
                if (!$employee && !empty($prinsipleCol)) {
                    $fallbackQuery = Employee::where(function ($q) use ($rawNik, $cleanNik, $hasNikColumn) {
                        $q->where('employee_no', $rawNik);
                        if (!empty($cleanNik) && $cleanNik !== $rawNik) {
                            $q->orWhere('employee_no', $cleanNik);
                        }
                        if ($hasNikColumn) {
                            $q->orWhere('nik', $rawNik);
                            if (!empty($cleanNik) && $cleanNik !== $rawNik) {
                                $q->orWhere('nik', $cleanNik);
                            }
                        }
                    });
                    $employee = (clone $fallbackQuery)->where('is_active', true)->whereNull('deleted_at')->first()
                        ?? $fallbackQuery->first();
                }
            }

            // 2. Fallback jika tidak ketemu by NIK: Cari by Nama Karyawan (CASE-INSENSITIVE)
            if (!$employee && !empty($namaKaryawan)) {
                $nameQuery = Employee::whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower($namaKaryawan)]);
                if (!empty($prinsipleCol)) {
                    $pTrim = trim($prinsipleCol);
                    $nameQuery->whereHas('principal', function ($q) use ($pTrim) {
                        $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($pTrim)]);
                    });
                }
                $employee = (clone $nameQuery)->where('is_active', true)->whereNull('deleted_at')->first()
                    ?? $nameQuery->first();

                if (!$employee && !empty($prinsipleCol)) {
                    $employee = Employee::whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower($namaKaryawan)])
                        ->where('is_active', true)->whereNull('deleted_at')->first()
                        ?? Employee::whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower($namaKaryawan)])->first();
                }
            }

            if (!$employee) {
                $this->skippedCount++;
                $identifier = !empty($rawNik) ? "NIK '{$rawNik}'" : "Nama '{$namaKaryawan}'";
                if (!empty($namaKaryawan) && !empty($rawNik)) {
                    $identifier .= " ({$namaKaryawan})";
                }
                $this->errors[] = "Baris {$excelRowNum}: Karyawan dengan {$identifier} tidak ditemukan di database Master Karyawan.";
                continue;
            }

            // Validasi Hak Akses User
            if (!$isSuperAdmin) {
                if ($accessibleBranchIds !== null && !in_array($employee->branch_id, $accessibleBranchIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$excelRowNum}: Karyawan '{$employee->full_name}' di luar wewenang Area Anda.";
                    continue;
                }
                if ($accessiblePrincipalIds !== null && !in_array($employee->principal_id, $accessiblePrincipalIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$excelRowNum}: Karyawan '{$employee->full_name}' di luar wewenang Prinsiple Anda.";
                    continue;
                }
            }

            // Resolusi Lokasi Kerja
            $storeCode = $storeCodeColIdx !== null ? trim((string)($rowItems[$storeCodeColIdx] ?? '')) : '';
            $storeName = $storeNameColIdx !== null ? trim((string)($rowItems[$storeNameColIdx] ?? '')) : '';
            
            $locationId = null;
            if (!empty($storeCode)) {
                $locationId = $this->locationsMap[strtolower($storeCode)] ?? null;
            }
            if (!$locationId && !empty($storeName)) {
                $locationId = $this->locationsMap[strtolower($storeName)] ?? null;
            }
            if (!$locationId && !empty($storeName)) {
                $foundLoc = WorkLocation::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($storeName) . '%'])->first();
                if ($foundLoc) {
                    $locationId = $foundLoc->id;
                }
            }
            if (!$locationId) {
                $locationId = $this->defaultLocationId;
            }

            // =====================================================================
            // 5. INPUT JADWAL SESUAI FORMAT TERDETEKSI
            // =====================================================================
            if ($isMatrix) {
                // -------------------------------------------------------------
                // FORMAT MATRIX (Kolom Harian 1..31)
                // -------------------------------------------------------------
                $rawMonth = $monthColIdx !== null ? ($rowItems[$monthColIdx] ?? null) : null;
                $rawYear = $yearColIdx !== null ? ($rowItems[$yearColIdx] ?? null) : null;

                $month = $this->parseMonth($rawMonth);
                $year = (int)($rawYear ?: Carbon::now()->year);
                if ($year < 2000 || $year > 2100) {
                    $year = Carbon::now()->year;
                }

                $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $processedForEmp = 0;

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    if (!isset($dailyColsMap[$day])) {
                        continue;
                    }

                    $cIdx = $dailyColsMap[$day];
                    $shiftVal = $rowItems[$cIdx] ?? null;

                    if ($shiftVal === null || trim((string)$shiftVal) === '') {
                        continue; // Tanggal tidak diisi
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
                        $shift = $this->resolveShift($shiftValStr, $employee->principal_id, $employee->company_id ?? 1);

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
                } else {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$excelRowNum}: Karyawan '{$employee->full_name}' dilewati karena seluruh kolom tanggal (1..{$daysInMonth}) kosong.";
                }
            } else {
                // -------------------------------------------------------------
                // FORMAT RENTANG TANGGAL (Start Date -> End Date)
                // -------------------------------------------------------------
                $rawStartDate = $startDateColIdx !== null ? ($rowItems[$startDateColIdx] ?? null) : null;
                $rawEndDate = $endDateColIdx !== null ? ($rowItems[$endDateColIdx] ?? null) : null;
                $shiftName = $shiftColIdx !== null ? trim((string)($rowItems[$shiftColIdx] ?? '')) : '';

                $startDateStr = $this->parseDate($rawStartDate);
                $endDateStr = $this->parseDate($rawEndDate) ?: $startDateStr;

                if (!$startDateStr) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$excelRowNum}: Format tanggal mulai '{$rawStartDate}' tidak valid.";
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
                    $shift = $this->resolveShift($shiftName, $employee->principal_id, $employee->company_id ?? 1);
                }

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

        // Jika tidak ada data yang masuk dan belum ada error terdata
        if ($this->importedCount === 0 && empty($this->errors)) {
            if ($this->totalRowsRead === 0) {
                $this->errors[] = "Tidak ada baris data yang ditemukan setelah baris header.";
            } else {
                $this->errors[] = "Tidak ada jadwal yang berhasil diproses dari {$this->totalRowsRead} baris data yang dibaca.";
            }
        }
    }

    protected function resolveShift(string $shiftName, ?int $principalId = null, int $companyId = 1): ?Shift
    {
        $clean = trim($shiftName);
        $key = strtolower($clean) . ($principalId ? "_{$principalId}" : '');

        if (isset($this->shiftsMap[$key])) {
            return $this->shiftsMap[$key];
        }

        // Cek di DB
        $query = Shift::where(function ($q) use ($clean) {
            $q->whereRaw('LOWER(name) = ?', [strtolower($clean)])
              ->orWhereRaw('LOWER(code) = ?', [strtolower($clean)]);
        });

        if ($principalId) {
            $query->where(function ($q) use ($principalId) {
                $q->where('principal_id', $principalId)
                  ->orWhereNull('principal_id');
            });
        }

        $found = $query->first();

        if ($found) {
            $this->shiftsMap[$key] = $found;
            return $found;
        }

        // Auto-create shift jika nama shift baru belum terdaftar di DB
        try {
            $newShift = Shift::create([
                'principal_id' => $principalId,
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
