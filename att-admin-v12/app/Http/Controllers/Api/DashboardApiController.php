<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkTarget;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    public function stats(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found'], 404);
        }

        // Hitung Periode Cut Off Aktif untuk Karyawan ini
        $cutoff = ($employee->department && isset($employee->department->cutoff_start_date)) ? (int)$employee->department->cutoff_start_date : 26;
        $now = Carbon::now('Asia/Jakarta');

        if ($cutoff == 1) {
            $startDate = $now->copy()->startOfMonth();
            $endDate   = $now->copy()->endOfMonth();
            $monthYear = $now->format('Y-m');

            $prevStartDate = $now->copy()->subMonth()->startOfMonth();
            $prevEndDate   = $now->copy()->subMonth()->endOfMonth();
        } else {
            if ($now->day >= $cutoff) {
                $startDate = $now->copy()->setDay($cutoff)->startOfDay();
                $endDate   = $now->copy()->addMonth()->setDay($cutoff - 1)->endOfDay();
                $monthYear = $now->copy()->addMonth()->format('Y-m');

                $prevStartDate = $now->copy()->subMonth()->setDay($cutoff)->startOfDay();
                $prevEndDate   = $now->copy()->setDay($cutoff - 1)->endOfDay();
            } else {
                $startDate = $now->copy()->subMonth()->setDay($cutoff)->startOfDay();
                $endDate   = $now->copy()->setDay($cutoff - 1)->endOfDay();
                $monthYear = $now->format('Y-m');

                $prevStartDate = $now->copy()->subMonths(2)->setDay($cutoff)->startOfDay();
                $prevEndDate   = $now->copy()->subMonth()->setDay($cutoff - 1)->endOfDay();
            }
        }

        // Target HK: Cek dari WorkTarget periode cut off ini
        $workTarget = WorkTarget::where('employee_id', $employee->id)
            ->where(function ($q) use ($monthYear) {
                $q->where('month_year', $monthYear)
                  ->orWhere('month_year', Carbon::parse($monthYear . '-01')->format('m-Y'));
            })
            ->first();

        // Jika belum ada di tabel WorkTarget, hitung otomatis dari EmployeeSchedule atau hari kerja dalam periode cutoff
        if ($workTarget && $workTarget->target_hk > 0) {
            $targetHK = (int)$workTarget->target_hk;
        } else {
            $scheduledWorkdays = \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
                ->whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('schedule_type', 'workday')
                ->count();

            if ($scheduledWorkdays > 0) {
                $targetHK = $scheduledWorkdays;
            } else {
                // Default weekdays dalam periode cutoff
                $plan = 0;
                $cur = $startDate->copy();
                while ($cur->lte($endDate)) {
                    if ($cur->isWeekday()) {
                        $plan++;
                    }
                    $cur->addDay();
                }
                $targetHK = $plan;
            }
        }

        // Calculate Kehadiran (Attendances pada periode cut off yang sedang berjalan)
        $kehadiran = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['present', 'late', 'permit'])
            ->count();

        // Calculate Sakit & Cuti (Approved Leave Requests pada periode cut off yang sedang berjalan)
        $sakit = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('type', 'sakit')
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        $cuti = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereIn('type', ['cuti', 'izin'])
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        // Running Rate
        $runningRate = 0;
        if ($targetHK > 0) {
            $runningRate = round(($kehadiran / $targetHK) * 100);
        }

        // Previous cut off period kehadiran
        $prevKehadiran = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$prevStartDate->toDateString(), $prevEndDate->toDateString()])
            ->whereIn('status', ['present', 'late', 'permit'])
            ->count();

        return response()->json([
            'position' => $employee->position ? $employee->position->name : 'Standar',
            'target_hk' => $targetHK,
            'running_rate' => $runningRate,
            'kehadiran' => $kehadiran,
            'sakit' => $sakit,
            'cuti' => $cuti,
            'prev_kehadiran' => $prevKehadiran
        ]);
    }

    public function teamStats(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found'], 404);
        }
        $today = Carbon::today('Asia/Jakarta');
        $todayStr = $today->format('Y-m-d');
        $sevenDaysAgo = $today->copy()->subDays(6);
        
        // Get subordinates (direct reports) with relations
        $teamMembers = Employee::where('supervisor_id', $employee->id)
            ->where('is_active', true)
            ->with(['position', 'principal', 'branch', 'company', 'department'])
            ->get();
        $totalTeam = $teamMembers->count();

        $teamIds = $teamMembers->pluck('id')->toArray();

        // Get attendances today
        $todayAttendances = Attendance::whereIn('employee_id', $teamIds)
            ->whereDate('attendance_date', $todayStr)
            ->get()
            ->keyBy('employee_id');

        // Get leave requests today
        $todayLeaves = LeaveRequest::whereIn('employee_id', $teamIds)
            ->where('status', 'approved')
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->get()
            ->keyBy('employee_id');

        $hadirHariIni = 0;
        $sakitHariIni = 0;
        $cutiHariIni = 0;
        $vacantDetails = [];

        // Check each team member status for last 7 days
        $attendances7Days = Attendance::whereIn('employee_id', $teamIds)
            ->whereBetween('attendance_date', [$sevenDaysAgo->toDateString(), $todayStr])
            ->get()
            ->groupBy('employee_id');

        foreach ($teamMembers as $emp) {
            $attToday = $todayAttendances->get($emp->id);
            $leaveToday = $todayLeaves->get($emp->id);

            if ($attToday) {
                $hadirHariIni++;
                continue;
            }

            if ($leaveToday) {
                if ($leaveToday->type === 'sakit') {
                    $sakitHariIni++;
                } else {
                    $cutiHariIni++;
                }
                continue;
            }

            // If not present and not on leave today, check recent vacancy
            $empAtts = $attendances7Days->get($emp->id, collect());
            $attendedDates = $empAtts->pluck('attendance_date')->toArray();

            $missedDates = [];
            for ($i = 0; $i < 7; $i++) {
                $checkDate = $today->copy()->subDays($i)->format('Y-m-d');
                if (!in_array($checkDate, $attendedDates)) {
                    $missedDates[] = $checkDate;
                }
            }

            $lastAttendance = Attendance::where('employee_id', $emp->id)
                ->orderBy('attendance_date', 'desc')
                ->first();

            $daysVacant = 1;
            if ($lastAttendance) {
                $daysVacant = Carbon::parse($lastAttendance->attendance_date)->diffInDays($today);
            } else {
                $daysVacant = 7;
            }

            $vacantDetails[] = [
                'id' => $emp->id,
                'name' => $emp->full_name ?? 'Unknown',
                'full_name' => $emp->full_name ?? 'Unknown',
                'employee_no' => $emp->employee_no ?? '-',
                'position' => $emp->position?->name ?? 'Staff',
                'principal' => $emp->principal?->name ?? ($emp->company?->name ?? '-'),
                'area' => $emp->branch?->name ?? ($emp->branch?->code ?? '-'),
                'branch' => $emp->branch?->name ?? '-',
                'phone' => $emp->phone ?? '-',
                'days' => $daysVacant,
                'last_attendance_date' => $lastAttendance ? Carbon::parse($lastAttendance->attendance_date)->translatedFormat('d M Y') : 'Belum pernah hadir',
                'raw_last_attendance_date' => $lastAttendance ? $lastAttendance->attendance_date : null,
                'last_checkin_at' => $lastAttendance?->checkin_at,
                'missed_count_7days' => count($missedDates),
                'missed_dates' => $missedDates,
            ];
        }

        $vacant = count($vacantDetails);

        // Team Target Mandays (Berdasarkan periode cutoff berjalan)
        $cutoff = ($employee->department && isset($employee->department->cutoff_start_date)) ? (int)$employee->department->cutoff_start_date : 26;
        $now = Carbon::now('Asia/Jakarta');

        if ($cutoff == 1) {
            $startDate = $now->copy()->startOfMonth();
            $endDate   = $now->copy()->endOfMonth();
            $monthYear = $now->format('Y-m');
        } else {
            if ($now->day >= $cutoff) {
                $startDate = $now->copy()->setDay($cutoff)->startOfDay();
                $endDate   = $now->copy()->addMonth()->setDay($cutoff - 1)->endOfDay();
                $monthYear = $now->copy()->addMonth()->format('Y-m');
            } else {
                $startDate = $now->copy()->subMonth()->setDay($cutoff)->startOfDay();
                $endDate   = $now->copy()->setDay($cutoff - 1)->endOfDay();
                $monthYear = $now->format('Y-m');
            }
        }

        $teamTargetMandays = (int)WorkTarget::whereIn('employee_id', $teamIds)
            ->where(function ($q) use ($monthYear) {
                $q->where('month_year', $monthYear)
                  ->orWhere('month_year', Carbon::parse($monthYear . '-01')->format('m-Y'));
            })
            ->sum('target_hk');

        if ($teamTargetMandays == 0) {
            $teamTargetMandays = \App\Models\EmployeeSchedule::whereIn('employee_id', $teamIds)
                ->whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('schedule_type', 'workday')
                ->count();
        }

        // Current team attendance count in this cutoff period
        $teamKehadiranBulanIni = Attendance::whereIn('employee_id', $teamIds)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['present', 'late', 'permit'])
            ->count();
        
        $teamRunningRate = 0;
        if ($teamTargetMandays > 0) {
            $teamRunningRate = round(($teamKehadiranBulanIni / $teamTargetMandays) * 100);
        }

        return response()->json([
            'total_team' => $totalTeam,
            'hadir_hari_ini' => $hadirHariIni,
            'sakit_hari_ini' => $sakitHariIni,
            'cuti_hari_ini' => $cutiHariIni,
            'vacant' => $vacant,
            'vacant_details' => $vacantDetails,
            'team_target_mandays' => $teamTargetMandays,
            'team_running_rate' => $teamRunningRate
        ]);
    }

    public function teamUnchecked(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found'], 404);
        }

        $today = Carbon::today('Asia/Jakarta');
        $todayStr = $today->format('Y-m-d');
        $sevenDaysAgo = $today->copy()->subDays(6);

        $teamMembers = Employee::where('supervisor_id', $employee->id)
            ->where('is_active', true)
            ->with(['position', 'principal', 'branch', 'company'])
            ->get();

        $teamIds = $teamMembers->pluck('id')->toArray();

        // Get attendances for last 7 days
        $attendances7Days = Attendance::whereIn('employee_id', $teamIds)
            ->whereBetween('attendance_date', [$sevenDaysAgo->toDateString(), $todayStr])
            ->get()
            ->groupBy('employee_id');

        // Get approved leaves for last 7 days
        $leaves7Days = LeaveRequest::whereIn('employee_id', $teamIds)
            ->where('status', 'approved')
            ->where('end_date', '>=', $sevenDaysAgo->toDateString())
            ->where('start_date', '<=', $todayStr)
            ->get()
            ->groupBy('employee_id');

        $resultList = [];

        foreach ($teamMembers as $emp) {
            $empAttendances = $attendances7Days->get($emp->id, collect());
            $empLeaves = $leaves7Days->get($emp->id, collect());

            $attendedDates = $empAttendances->pluck('attendance_date')->map(fn($d) => Carbon::parse($d)->toDateString())->toArray();
            
            // Build missed dates list in the last 7 days
            $missedDates = [];
            $curr = $sevenDaysAgo->copy();
            while ($curr <= $today) {
                $cStr = $curr->toDateString();
                $isAttended = in_array($cStr, $attendedDates);
                $isOnLeave = $empLeaves->first(function($leave) use ($cStr) {
                    return $cStr >= $leave->start_date && $cStr <= $leave->end_date;
                }) !== null;

                if (!$isAttended && !$isOnLeave) {
                    $missedDates[] = [
                        'date' => $cStr,
                        'formatted_date' => $curr->translatedFormat('d M Y'),
                        'day_name' => $curr->translatedFormat('l'),
                    ];
                }
                $curr->addDay();
            }

            $missedDates = array_reverse($missedDates);

            $lastAttendance = Attendance::where('employee_id', $emp->id)
                ->orderBy('attendance_date', 'desc')
                ->first();

            $daysSinceLast = -1;
            if ($lastAttendance) {
                $daysSinceLast = Carbon::parse($lastAttendance->attendance_date)->diffInDays($today);
            }

            $isTodayUnchecked = !in_array($todayStr, $attendedDates);

            $resultList[] = [
                'id' => $emp->id,
                'employee_no' => $emp->employee_no ?? '-',
                'full_name' => $emp->full_name ?? 'Unknown',
                'name' => $emp->full_name ?? 'Unknown',
                'position' => $emp->position?->name ?? 'Staff',
                'principal' => $emp->principal?->name ?? ($emp->company?->name ?? '-'),
                'area' => $emp->branch?->name ?? ($emp->branch?->code ?? '-'),
                'branch' => $emp->branch?->name ?? '-',
                'phone' => $emp->phone ?? '-',
                'is_today_unchecked' => $isTodayUnchecked,
                'days' => $daysSinceLast,
                'last_attendance_date' => $lastAttendance ? Carbon::parse($lastAttendance->attendance_date)->translatedFormat('d M Y') : 'Belum pernah hadir',
                'raw_last_attendance_date' => $lastAttendance ? $lastAttendance->attendance_date : null,
                'last_checkin_at' => $lastAttendance?->checkin_at,
                'missed_count_7days' => count($missedDates),
                'missed_dates' => $missedDates,
            ];
        }

        // Sort: members with more missed days first
        usort($resultList, function($a, $b) {
            if ($a['missed_count_7days'] === $b['missed_count_7days']) {
                return strcmp($a['full_name'], $b['full_name']);
            }
            return $b['missed_count_7days'] <=> $a['missed_count_7days'];
        });

        return response()->json([
            'status' => 'success',
            'data' => $resultList,
            'summary' => [
                'total_team' => $teamMembers->count(),
                'unchecked_today' => count(array_filter($resultList, fn($x) => $x['is_today_unchecked'])),
                'period_start' => $sevenDaysAgo->toDateString(),
                'period_end' => $todayStr,
            ]
        ]);
    }
}
