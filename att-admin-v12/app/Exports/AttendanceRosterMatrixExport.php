<?php

namespace App\Exports;

use App\Filament\Resources\Attendances\Pages\AttendanceRoster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceRosterMatrixExport extends DefaultValueBinder implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected ?int $branchId;
    protected ?int $principalId;
    protected ?int $employeeId;
    protected int $daysCount;

    public function __construct(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?int $principalId = null, ?int $employeeId = null)
    {
        $this->startDate = $startDate->copy()->startOfDay();
        $this->endDate = $endDate->copy()->endOfDay();
        
        $daysInPeriod = $this->startDate->diffInDays($this->endDate) + 1;
        if ($daysInPeriod > 31) {
            $this->endDate = $this->startDate->copy()->addDays(30)->endOfDay();
            $daysInPeriod = 31;
        }
        $this->daysCount = $daysInPeriod;

        $this->branchId = $branchId;
        $this->principalId = $principalId;
        $this->employeeId = $employeeId;
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value) && strlen((string) $value) >= 8) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        $headers = [
            'NIK',
            'NAMA KARYAWAN',
            'JABATAN',
            'AREA / CABANG',
            'PRINSIPLE',
        ];

        for ($i = 0; $i < $this->daysCount; $i++) {
            $date = $this->startDate->copy()->addDays($i);
            $headers[] = $date->format('d M (D)');
        }

        $headers[] = 'TOTAL HADIR';
        $headers[] = 'TOTAL TELAT';
        $headers[] = 'TOTAL IZIN/CUTI';
        $headers[] = 'TOTAL ALPHA';

        return $headers;
    }

    public function array(): array
    {
        $startDateStr = $this->startDate->toDateString();
        $endDateStr = $this->endDate->toDateString();
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');

        // Holidays
        $holidays = DB::table('holidays')
            ->whereBetween('holiday_date', [$startDateStr, $endDateStr])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
        $holidayMap = array_flip($holidays);

        // Active employee IDs
        $attEmpIds = DB::table('attendances')
            ->whereBetween('attendance_date', [$startDateStr, $endDateStr])
            ->pluck('employee_id');

        $schedEmpIds = DB::table('employee_schedules')
            ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
            ->pluck('employee_id');

        $leaveEmpIds = DB::table('leave_requests')
            ->where('status', 'approved')
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                  ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                  ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                      $sq->where('start_date', '<=', $startDateStr)
                         ->where('end_date', '>=', $endDateStr);
                  });
            })
            ->pluck('employee_id');

        $activeEmpIds = $attEmpIds->merge($schedEmpIds)->merge($leaveEmpIds)->unique()->filter()->toArray();

        if (empty($activeEmpIds)) {
            return [];
        }

        $employeeQuery = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->whereIn('employees.id', $activeEmpIds)
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at');

        if (!empty($this->branchId)) {
            $employeeQuery->where('employees.branch_id', $this->branchId);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $employeeQuery->whereIn('employees.branch_id', auth()->user()->getAccessibleBranchIds());
        }

        if (!empty($this->principalId)) {
            $employeeQuery->where('employees.principal_id', $this->principalId);
        } elseif (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $employeeQuery->whereIn('employees.principal_id', auth()->user()->getAccessiblePrincipalIds());
        }

        if (!empty($this->employeeId)) {
            $employeeQuery->where('employees.id', $this->employeeId);
        }

        $employees = $employeeQuery->select([
            'employees.id',
            'employees.employee_no',
            'employees.full_name',
            'departments.working_days as dept_working_days',
            'positions.name as position_name',
            'branches.name as branch_name',
            'principals.name as principal_name',
        ])->orderBy('employees.full_name')->get();

        $empIds = $employees->pluck('id')->toArray();

        // Attendances
        $attendances = DB::table('attendances')
            ->leftJoin('employee_schedules', 'attendances.employee_schedule_id', '=', 'employee_schedules.id')
            ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
            ->whereIn('attendances.employee_id', $empIds)
            ->whereBetween('attendances.attendance_date', [$startDateStr, $endDateStr])
            ->select([
                'attendances.employee_id',
                'attendances.attendance_date',
                'attendances.status',
                'attendances.checkin_at',
                'attendances.checkout_at',
                'attendances.late_minutes',
                'shifts.start_time as shift_start_time',
                'shifts.grace_checkin_minutes',
                'employee_schedules.planned_start_at',
            ])
            ->get()
            ->groupBy('employee_id');

        // Schedules
        $schedules = DB::table('employee_schedules')
            ->leftJoin('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
            ->whereIn('employee_schedules.employee_id', $empIds)
            ->whereBetween('employee_schedules.schedule_date', [$startDateStr, $endDateStr])
            ->select([
                'employee_schedules.employee_id',
                'employee_schedules.schedule_date',
                'employee_schedules.schedule_type',
                'employee_schedules.planned_start_at',
                'shifts.name as shift_name',
                'shifts.code as shift_code',
                'shifts.start_time as shift_start_time',
                'shifts.grace_checkin_minutes',
            ])
            ->get()
            ->groupBy('employee_id');

        // Leaves
        $leaves = DB::table('leave_requests')
            ->whereIn('employee_id', $empIds)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('start_date', [$startDateStr, $endDateStr])
                  ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                  ->orWhere(function ($sq) use ($startDateStr, $endDateStr) {
                      $sq->where('start_date', '<=', $startDateStr)
                         ->where('end_date', '>=', $endDateStr);
                  });
            })
            ->select(['employee_id', 'start_date', 'end_date', 'type'])
            ->get()
            ->groupBy('employee_id');

        $rows = [];

        foreach ($employees as $emp) {
            $empAtts = $attendances->get($emp->id);
            $empScheds = $schedules->get($emp->id);
            $empLeaves = $leaves->get($emp->id);

            $totPresent = 0;
            $totLate = 0;
            $totLeave = 0;
            $totAbsent = 0;

            $row = [
                'NIK' => (string) ($emp->employee_no ?? '-'),
                'NAMA KARYAWAN' => $emp->full_name,
                'JABATAN' => $emp->position_name ?? '-',
                'AREA / CABANG' => $emp->branch_name ?? '-',
                'PRINSIPLE' => $emp->principal_name ?? '-',
            ];

            for ($i = 0; $i < $this->daysCount; $i++) {
                $dateObj = $this->startDate->copy()->addDays($i);
                $dateStr = $dateObj->toDateString();

                $isWeekend = in_array($dateObj->dayOfWeek, [0, 6]);
                $isNatHoliday = isset($holidayMap[$dateStr]);
                $isDeptWorkDay = AttendanceRoster::isWorkingDay($dateObj, $emp->dept_working_days);

                $att = $empAtts ? $empAtts->firstWhere('attendance_date', $dateStr) : null;
                $sched = $empScheds ? $empScheds->firstWhere('schedule_date', $dateStr) : null;

                $activeLeave = null;
                if ($empLeaves) {
                    $activeLeave = $empLeaves->first(function($l) use ($dateStr) {
                        return $dateStr >= $l->start_date && $dateStr <= $l->end_date;
                    });
                }

                $cellText = '-';

                if ($activeLeave) {
                    $lType = strtolower($activeLeave->type);
                    if (in_array($lType, ['sakit', 'medical_leave'])) {
                        $cellText = 'SAKIT';
                    } elseif (in_array($lType, ['cuti', 'annual_leave', 'cuti_peraturan'])) {
                        $cellText = 'CUTI';
                    } else {
                        $cellText = 'IZIN';
                    }
                    $totLeave++;
                } elseif ($att) {
                    $isLate = false;
                    $inTime = $att->checkin_at ? Carbon::parse($att->checkin_at)->timezone('Asia/Jakarta')->format('H:i') : '';
                    
                    if ($att->status === 'late' || (int)$att->late_minutes > 0) {
                        $isLate = true;
                    } elseif (!empty($att->checkin_at)) {
                        $checkin = Carbon::parse($att->checkin_at)->timezone('Asia/Jakarta');
                        if (!empty($att->shift_start_time)) {
                            $shiftStart = Carbon::parse($att->attendance_date . ' ' . $att->shift_start_time);
                            $grace = (int)($att->grace_checkin_minutes ?? 0);
                            if ($checkin->greaterThan($shiftStart->copy()->addMinutes($grace))) {
                                $isLate = true;
                            }
                        } elseif (!empty($att->planned_start_at)) {
                            $plannedStart = Carbon::parse($att->planned_start_at);
                            if ($checkin->greaterThan($plannedStart)) {
                                $isLate = true;
                            }
                        } else {
                            $defaultStart = Carbon::parse($att->attendance_date . ' 08:30:00');
                            if ($checkin->greaterThan($defaultStart)) {
                                $isLate = true;
                            }
                        }
                    }

                    if ($isLate) {
                        $cellText = 'TELAT' . ($inTime ? " ($inTime)" : '');
                        $totLate++;
                    } elseif ($att->status === 'present') {
                        $cellText = 'HADIR' . ($inTime ? " ($inTime)" : '');
                        $totPresent++;
                    } elseif ($att->status === 'absent') {
                        $cellText = 'ALPHA';
                        $totAbsent++;
                    } elseif (in_array($att->status, ['leave', 'permit', 'sick'])) {
                        $cellText = strtoupper($att->status);
                        $totLeave++;
                    } else {
                        $cellText = strtoupper($att->status);
                    }
                } elseif ($isNatHoliday || !$isDeptWorkDay || ($sched && in_array($sched->schedule_type, ['dayoff', 'holiday']))) {
                    $cellText = 'LIBUR';
                } elseif ($sched && in_array($sched->schedule_type, ['workday', 'remote', 'field'])) {
                    if ($dateStr < $todayStr) {
                        // Past workday without checkin
                        $cellText = 'ALPHA';
                        $totAbsent++;
                    } elseif ($dateStr === $todayStr) {
                        // Today: check shift start time
                        $shiftStart = null;
                        if (!empty($sched->shift_start_time)) {
                            $shiftStart = Carbon::parse($dateStr . ' ' . $sched->shift_start_time, 'Asia/Jakarta');
                        } elseif (!empty($sched->planned_start_at)) {
                            $shiftStart = Carbon::parse($sched->planned_start_at, 'Asia/Jakarta');
                        } else {
                            $shiftStart = Carbon::parse($dateStr . ' 08:30:00', 'Asia/Jakarta');
                        }

                        if ($now->greaterThanOrEqualTo($shiftStart)) {
                            // Shift has passed and no checkin
                            $cellText = 'ALPHA';
                            $totAbsent++;
                        } else {
                            // Shift hasn't started yet -> Show Shift Name!
                            $shiftName = $sched->shift_name ?: ($sched->shift_code ?: 'Shift');
                            $timeStr = !empty($sched->shift_start_time) ? ' (' . substr($sched->shift_start_time, 0, 5) . ')' : '';
                            $cellText = $shiftName . $timeStr;
                        }
                    } else {
                        // Future workday
                        $cellText = $sched->shift_code ?: ($sched->shift_name ?: '-');
                    }
                }

                $row[$dateObj->format('d M (D)')] = $cellText;
            }

            $row['TOTAL HADIR'] = $totPresent;
            $row['TOTAL TELAT'] = $totLate;
            $row['TOTAL IZIN/CUTI'] = $totLeave;
            $row['TOTAL ALPHA'] = $totAbsent;

            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $this->daysCount + 4);
        $highestRow = $sheet->getHighestRow();

        // Header style
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows alignment and borders
        if ($highestRow > 1) {
            $sheet->getStyle("A2:{$lastCol}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center align date cells and totals
            $dateStartCol = 'F';
            $sheet->getStyle("{$dateStartCol}2:{$lastCol}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
