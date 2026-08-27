<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\Principal;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VisitScheduleImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : true;
        $accessibleBranchIds = ($user && !$isSuperAdmin && $user->hasBranchRestriction()) ? $user->getAccessibleBranchIds() : null;
        $accessiblePrincipalIds = ($user && !$isSuperAdmin && $user->hasPrincipalRestriction()) ? $user->getAccessiblePrincipalIds() : null;

        // Cache master data
        $workLocations = WorkLocation::all();
        $principals = Principal::all();

        $rowIndex = 1;

        foreach ($rows as $row) {
            $rowIndex++;

            $nik = trim((string)($row['nik'] ?? ($row['nik_karyawan'] ?? ($row['no_karyawan'] ?? ($row['employee_no'] ?? '')))));
            if (stripos($nik, 'E+') !== false && is_numeric($nik)) {
                $nik = number_format((float)$nik, 0, '', '');
            }

            $namaKaryawan = trim((string)($row['nama_karyawan'] ?? ($row['nama'] ?? ($row['name'] ?? ($row['employee_name'] ?? '')))));
            $rawStartDate = $row['tanggal_mulai'] ?? ($row['start_date'] ?? ($row['tgl_mulai'] ?? ($row['tanggal'] ?? ($row['date'] ?? null))));
            $rawEndDate = $row['tanggal_akhir'] ?? ($row['end_date'] ?? ($row['tgl_akhir'] ?? ($row['tanggal'] ?? ($row['date'] ?? null))));
            $locationName = trim((string)($row['lokasi_visit'] ?? ($row['lokasi'] ?? ($row['work_location'] ?? ($row['nama_toko'] ?? '')))));
            $rawSeq = $row['urutan'] ?? ($row['sequence'] ?? ($row['no_urut'] ?? 1));
            $principalName = trim((string)($row['prinsiple'] ?? ($row['principal'] ?? ($row['nama_prinsiple'] ?? ''))));
            $visitType = trim((string)($row['tipe_visit'] ?? ($row['visit_type'] ?? ($row['tipe'] ?? 'store'))));
            $rawCheckin = trim((string)($row['jadikan_lokasi_checkin'] ?? ($row['is_checkin_location'] ?? ($row['checkin_location'] ?? ($row['checkin'] ?? '')))));
            $rawRouting = trim((string)($row['aturan_routing'] ?? ($row['routing'] ?? ($row['is_strict_routing'] ?? ($row['wajib_berurutan'] ?? '')))));
            $isStrictRouting = in_array(strtolower($rawRouting), ['berurutan', 'routing', 'aktif', '1', 'ya', 'yes', 'true', 'strict']);
            $notes = trim((string)($row['catatan'] ?? ($row['notes'] ?? ($row['agenda'] ?? ''))));

            if (empty($nik) && empty($namaKaryawan) && empty($rawStartDate)) {
                continue; // Skip baris kosong
            }

            // Cari Karyawan
            $employee = null;
            if (!empty($nik)) {
                $employee = Employee::where('employee_no', $nik)->first();
            }
            if (!$employee && !empty($namaKaryawan)) {
                $employee = Employee::where('full_name', $namaKaryawan)->first();
            }

            if (!$employee) {
                $this->skippedCount++;
                $identifier = !empty($nik) ? "NIK '{$nik}'" : "Nama '{$namaKaryawan}'";
                $this->errors[] = "Baris {$rowIndex}: Karyawan dengan {$identifier} tidak ditemukan.";
                continue;
            }

            // Validasi Akses User
            if (!$isSuperAdmin) {
                if ($accessibleBranchIds !== null && !in_array($employee->branch_id, $accessibleBranchIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Anda tidak memiliki akses ke area/region karyawan {$employee->full_name}.";
                    continue;
                }
                if ($accessiblePrincipalIds !== null && !in_array($employee->principal_id, $accessiblePrincipalIds)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Anda tidak memiliki akses ke prinsiple karyawan {$employee->full_name}.";
                    continue;
                }
            }

            // Parse Tanggal Mulai dan Akhir
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

            // Cari Lokasi Kerja / Toko
            $workLocation = null;
            if (!empty($locationName)) {
                $workLocation = $workLocations->first(function ($loc) use ($locationName) {
                    return strcasecmp($loc->name, $locationName) === 0 || strcasecmp($loc->code ?? '', $locationName) === 0;
                });

                if (!$workLocation) {
                    $workLocation = $workLocations->first(function ($loc) use ($locationName) {
                        return stripos($loc->name, $locationName) !== false;
                    });
                }
            }

            if (!$workLocation) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Lokasi/Toko '{$locationName}' tidak ditemukan di database.";
                continue;
            }

            // Cari Principal (Opsional)
            $principalId = null;
            if (!empty($principalName)) {
                $matchedPrin = $principals->first(function ($prin) use ($principalName) {
                    return strcasecmp($prin->name, $principalName) === 0;
                });
                $principalId = $matchedPrin?->id;
            }
            if (!$principalId) {
                $principalId = $employee->principal_id;
            }

            // Parse Boolean Is Checkin Location
            $isCheckinLocation = in_array(strtolower($rawCheckin), ['ya', '1', 'true', 'yes', 'y', 'set']);

            // Parse Sequence
            $sequence = is_numeric($rawSeq) ? (int)$rawSeq : 1;

            // Normalize Visit Type
            $normalizedVisitType = match(strtolower($visitType)) {
                'principal', 'kantor prinsiple', 'principal visit' => 'principal',
                'meeting', 'pertemuan', 'rapat' => 'meeting',
                'survey', 'survey lapangan' => 'survey',
                'other', 'lainnya' => 'other',
                default => 'store',
            };

            // Loop setiap tanggal dalam rentang
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateStr = $currentDate->toDateString();

                $itinerary = Itinerary::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $dateStr,
                    ],
                    [
                        'status' => 'approved',
                        'is_strict_routing' => $isStrictRouting,
                        'notes' => 'Imported via Excel',
                    ]
                );

                if ($isStrictRouting && !$itinerary->is_strict_routing) {
                    $itinerary->update(['is_strict_routing' => true]);
                }

                ItineraryItem::updateOrCreate(
                    [
                        'itinerary_id' => $itinerary->id,
                        'work_location_id' => $workLocation->id,
                    ],
                    [
                        'sequence' => $sequence,
                        'principal_id' => $principalId,
                        'visit_type' => $normalizedVisitType,
                        'is_checkin_location' => $isCheckinLocation,
                        'notes' => $notes ?: null,
                    ]
                );

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
                // Lanjut ke string parser
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
                // Next format
            }
        }

        try {
            return Carbon::parse(str_replace('/', '-', $rawDate))->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
