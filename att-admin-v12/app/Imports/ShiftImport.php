<?php

namespace App\Imports;

use App\Models\Principal;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ShiftImport implements ToCollection
{
    public int $totalRowsRead = 0;
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    public array $detectedColumns = [];

    protected array $principalsMap = [];
    protected ?int $defaultPrincipalId = null;

    public function __construct()
    {
        $this->refreshCaches();
    }

    protected function refreshCaches(): void
    {
        try {
            $principals = Principal::where('is_active', true)->get();
            foreach ($principals as $p) {
                $this->principalsMap[strtolower(trim($p->name))] = $p->id;
                if (!empty($p->code)) {
                    $this->principalsMap[strtolower(trim($p->code))] = $p->id;
                }
            }

            $this->defaultPrincipalId = $principals->first()?->id 
                ?? Principal::first()?->id;
        } catch (\Throwable $e) {
            // Fallback jika query bermasalah saat konstruksi objek
        }
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errors[] = "File Excel / CSV kosong, tidak ditemukan baris data.";
            return;
        }

        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser ? $currentUser->isSuperAdmin() : true;
        $accessiblePrincipalIds = ($currentUser && !$isSuperAdmin && $currentUser->hasPrincipalRestriction())
            ? $currentUser->getAccessiblePrincipalIds()
            : null;

        // =========================================================================
        // 1. AUTO-DETECT HEADER ROW (Pindai 15 baris pertama)
        // =========================================================================
        $headerRowIdx = null;
        $headerMap = [];
        $keywords = [
            'shift', 'nama', 'masuk', 'pulang', 'start', 'end',
            'kode', 'prinsiple', 'principal', 'name', 'code'
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

            if ($matches >= 2) {
                $headerRowIdx = $rIdx;
                $headerMap = $candidateHeaders;
                break;
            }
        }

        if ($headerRowIdx === null) {
            $this->errors[] = "Header kolom tidak terdeteksi. Pastikan file memiliki baris judul kolom seperti: 'Nama Shift', 'Jam Masuk', 'Jam Pulang', 'Prinsiple'.";
            return;
        }

        // =========================================================================
        // 2. PETAKAN INDEKS KOLOM KE KUNCI KANONIKAL
        // =========================================================================
        $canonicalMap = [];
        $this->detectedColumns = array_values($headerMap);

        foreach ($headerMap as $colIdx => $colName) {
            $normalized = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', $colName)));

            if (in_array($normalized, ['prinsiple', 'principal', 'nama_prinsiple', 'nama_principal', 'pt', 'company', 'klien'])) {
                $canonicalMap['principal'] = $colIdx;
            } elseif (in_array($normalized, ['kode_shift', 'kode', 'code', 'shift_code', 'id_shift'])) {
                $canonicalMap['code'] = $colIdx;
            } elseif (in_array($normalized, ['nama_shift', 'name', 'shift', 'nama', 'nama_jadwal', 'shift_name'])) {
                $canonicalMap['name'] = $colIdx;
            } elseif (in_array($normalized, ['jam_masuk', 'start_time', 'jam_mulai', 'masuk', 'in', 'waktu_masuk', 'start', 'jam_in'])) {
                $canonicalMap['start_time'] = $colIdx;
            } elseif (in_array($normalized, ['jam_pulang', 'end_time', 'jam_selesai', 'pulang', 'out', 'waktu_pulang', 'end', 'jam_out', 'jam_keluar', 'keluar'])) {
                $canonicalMap['end_time'] = $colIdx;
            } elseif (in_array($normalized, ['jam_istirahat_mulai', 'break_start_time', 'istirahat_mulai', 'break_start', 'istirahat_in', 'jam_break_mulai'])) {
                $canonicalMap['break_start_time'] = $colIdx;
            } elseif (in_array($normalized, ['jam_istirahat_selesai', 'break_end_time', 'istirahat_selesai', 'break_end', 'istirahat_out', 'jam_break_selesai'])) {
                $canonicalMap['break_end_time'] = $colIdx;
            } elseif (in_array($normalized, ['toleransi_masuk_menit', 'toleransi_masuk', 'grace_checkin_minutes', 'grace_checkin', 'toleransi_terlambat', 'toleransi_keterlambatan'])) {
                $canonicalMap['grace_checkin_minutes'] = $colIdx;
            } elseif (in_array($normalized, ['toleransi_pulang_menit', 'toleransi_pulang', 'grace_checkout_minutes', 'grace_checkout', 'toleransi_pulang_cepat'])) {
                $canonicalMap['grace_checkout_minutes'] = $colIdx;
            } elseif (in_array($normalized, ['lintas_hari', 'is_cross_day', 'cross_day', 'melewati_hari', 'beda_hari'])) {
                $canonicalMap['is_cross_day'] = $colIdx;
            } elseif (in_array($normalized, ['wajib_checkin', 'required_checkin', 'wajib_masuk', 'checkin_wajib'])) {
                $canonicalMap['required_checkin'] = $colIdx;
            } elseif (in_array($normalized, ['wajib_checkout', 'required_checkout', 'wajib_pulang', 'checkout_wajib'])) {
                $canonicalMap['required_checkout'] = $colIdx;
            } elseif (in_array($normalized, ['status_aktif', 'is_active', 'status', 'aktif', 'active'])) {
                $canonicalMap['is_active'] = $colIdx;
            }
        }

        // =========================================================================
        // 3. PROSES DATA BARIS DEMI BARIS
        // =========================================================================
        $dataRows = $rows->slice($headerRowIdx + 1);

        foreach ($dataRows as $offset => $rawRow) {
            $rowItems = is_array($rawRow) ? $rawRow : $rawRow->toArray();
            $fileRowNumber = $headerRowIdx + 2 + $offset;

            $getVal = function ($key) use ($canonicalMap, $rowItems) {
                if (!isset($canonicalMap[$key])) return null;
                $idx = $canonicalMap[$key];
                return isset($rowItems[$idx]) ? trim((string)$rowItems[$idx]) : null;
            };

            $rawName = $getVal('name');
            $rawStartTime = $getVal('start_time');
            $rawEndTime = $getVal('end_time');

            // Skip baris jika kolom utama seluruhnya kosong
            if (empty($rawName) && empty($rawStartTime) && empty($rawEndTime)) {
                continue;
            }

            $this->totalRowsRead++;

            // Validasi Nama Shift
            if (empty($rawName)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$fileRowNumber}: Kolom 'Nama Shift' tidak boleh kosong.";
                continue;
            }

            // Validasi Jam Masuk & Jam Pulang
            $parsedStartTime = $this->parseTime($rawStartTime);
            if (!$parsedStartTime) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$fileRowNumber} ({$rawName}): Jam Masuk '{$rawStartTime}' tidak valid atau kosong (contoh format: 08:00).";
                continue;
            }

            $parsedEndTime = $this->parseTime($rawEndTime);
            if (!$parsedEndTime) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$fileRowNumber} ({$rawName}): Jam Pulang '{$rawEndTime}' tidak valid atau kosong (contoh format: 17:00).";
                continue;
            }

            // Parse Jam Istirahat
            $rawBreakStart = $getVal('break_start_time');
            $rawBreakEnd = $getVal('break_end_time');
            $parsedBreakStart = !empty($rawBreakStart) ? $this->parseTime($rawBreakStart) : null;
            $parsedBreakEnd = !empty($rawBreakEnd) ? $this->parseTime($rawBreakEnd) : null;

            // Resolusi Prinsiple
            $rawPrincipal = $getVal('principal');
            $principalId = null;

            if (!empty($rawPrincipal)) {
                $pKey = strtolower(trim($rawPrincipal));
                if (isset($this->principalsMap[$pKey])) {
                    $principalId = $this->principalsMap[$pKey];
                } else {
                    // Cari partial match
                    foreach ($this->principalsMap as $pName => $pId) {
                        if (str_contains($pName, $pKey) || str_contains($pKey, $pName)) {
                            $principalId = $pId;
                            break;
                        }
                    }
                }
            }

            if (!$principalId) {
                $principalId = $this->defaultPrincipalId;
            }

            // Validasi hak akses prinsiple user jika dibatasi
            if ($accessiblePrincipalIds !== null && !in_array($principalId, $accessiblePrincipalIds)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$fileRowNumber} ({$rawName}): Anda tidak memiliki izin untuk mengelola shift pada prinsiple tersebut.";
                continue;
            }

            // Parse Toleransi (Menit)
            $rawGraceIn = $getVal('grace_checkin_minutes');
            $rawGraceOut = $getVal('grace_checkout_minutes');
            $graceIn = (is_numeric($rawGraceIn)) ? (int)$rawGraceIn : 0;
            $graceOut = (is_numeric($rawGraceOut)) ? (int)$rawGraceOut : 0;

            // Parse Boolean (Lintas Hari, Wajib Checkin/Out, Status Aktif)
            $rawCrossDay = $getVal('is_cross_day');
            $isCrossDay = $this->parseBoolean($rawCrossDay, false);

            // Auto-detect lintas hari jika jam pulang lebih kecil dari jam masuk (misal 22:00 s/d 06:00)
            if (!$isCrossDay && $parsedEndTime < $parsedStartTime) {
                $isCrossDay = true;
            }

            $rawCheckin = $getVal('required_checkin');
            $reqCheckin = $this->parseBoolean($rawCheckin, true);

            $rawCheckout = $getVal('required_checkout');
            $reqCheckout = $this->parseBoolean($rawCheckout, true);

            $rawActive = $getVal('is_active');
            $isActive = $this->parseBoolean($rawActive, true);

            // Resolusi Kode Shift
            $rawCode = $getVal('code');
            $code = !empty($rawCode) ? trim($rawCode) : null;

            try {
                // Cari apakah shift sudah ada berdasarkan Kode atau Nama + Prinsiple
                $shift = null;
                if (!empty($code)) {
                    $shift = Shift::where('code', $code)->first();
                }

                if (!$shift) {
                    $shift = Shift::where('name', $rawName)
                        ->where('principal_id', $principalId)
                        ->first();
                }

                // Jika masih baru dan kode kosong, generate kode unik
                if (!$shift && empty($code)) {
                    do {
                        $code = 'SHF-' . strtoupper(Str::random(5));
                    } while (Shift::where('code', $code)->exists());
                } elseif ($shift && empty($code)) {
                    $code = $shift->code;
                }

                Shift::updateOrCreate(
                    [
                        'id' => $shift?->id,
                    ],
                    [
                        'principal_id' => $principalId,
                        'name' => $rawName,
                        'code' => $code,
                        'start_time' => $parsedStartTime,
                        'end_time' => $parsedEndTime,
                        'break_start_time' => $parsedBreakStart,
                        'break_end_time' => $parsedBreakEnd,
                        'grace_checkin_minutes' => $graceIn,
                        'grace_checkout_minutes' => $graceOut,
                        'is_cross_day' => $isCrossDay,
                        'required_checkin' => $reqCheckin,
                        'required_checkout' => $reqCheckout,
                        'is_active' => $isActive,
                    ]
                );

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$fileRowNumber} ({$rawName}): Galat saat menyimpan: " . $e->getMessage();
            }
        }
    }

    /**
     * Konversi berbagai format waktu Excel (desimal, string 08:00, 08.00) ke format H:i:s.
     */
    protected function parseTime($value): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $value = trim((string)$value);

        // 1. Format numeric pecahan hari Excel (contoh: 0.333333333333333 = 08:00:00)
        if (is_numeric($value)) {
            try {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return Carbon::instance($dateTime)->format('H:i:s');
            } catch (\Throwable $e) {
                // Abaikan dan lanjut coba parse string
            }
        }

        // 2. Normalisasi pemisah titik ke titik dua (contoh: 08.00 -> 08:00)
        $value = str_replace('.', ':', $value);

        // 3. Parser string dengan format waktu umum
        $formats = ['H:i:s', 'H:i', 'g:i A', 'g:i:s A', 'G:i', 'G:i:s'];
        foreach ($formats as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $value);
                if ($parsed) {
                    return $parsed->format('H:i:s');
                }
            } catch (\Throwable $e) {
                // Lanjut format berikutnya
            }
        }

        // 4. Fallback Carbon parse
        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse nilai boolean fleksibel (Ya/Tidak, Yes/No, 1/0, True/False).
     */
    protected function parseBoolean($value, bool $default = true): bool
    {
        if ($value === null || trim((string)$value) === '') {
            return $default;
        }

        $str = strtolower(trim((string)$value));
        if (in_array($str, ['ya', 'yes', 'y', '1', 'true', 'aktif', 'active', 'on'])) {
            return true;
        }
        if (in_array($str, ['tidak', 'no', 't', '0', 'false', 'nonaktif', 'inactive', 'off'])) {
            return false;
        }

        return $default;
    }
}
