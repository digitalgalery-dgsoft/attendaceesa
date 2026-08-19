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
        $currentMonth = Carbon::now()->format('Y-m');

        // Target HK
        $workTarget = WorkTarget::where('employee_id', $employee->id)
                                ->whereIn('month_year', [$currentMonth, Carbon::now()->format('m-Y')])
                                ->first();
        
        $targetHK = $workTarget ? $workTarget->target_hk : 0;

        // Calculate Kehadiran (Attendances this month)
        $kehadiran = Attendance::where('employee_id', $employee->id)
                               ->whereYear('attendance_date', Carbon::now()->year)
                               ->whereMonth('attendance_date', Carbon::now()->month)
                               ->count();

        // Calculate Sakit & Cuti (Approved Leave Requests this month)
        $sakit = LeaveRequest::where('employee_id', $employee->id)
                             ->where('status', 'approved')
                             ->where('type', 'sakit')
                             ->whereYear('start_date', Carbon::now()->year)
                             ->whereMonth('start_date', Carbon::now()->month)
                             ->count();

        $cuti = LeaveRequest::where('employee_id', $employee->id)
                            ->where('status', 'approved')
                            ->whereIn('type', ['cuti', 'izin'])
                            ->whereYear('start_date', Carbon::now()->year)
                            ->whereMonth('start_date', Carbon::now()->month)
                            ->count();

        // Running Rate
        $runningRate = 0;
        if ($targetHK > 0) {
            $runningRate = round(($kehadiran / $targetHK) * 100);
        }

        // Previous month kehadiran
        $prevKehadiran = Attendance::where('employee_id', $employee->id)
                                   ->whereYear('attendance_date', Carbon::now()->subMonth()->year)
                                   ->whereMonth('attendance_date', Carbon::now()->subMonth()->month)
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
            ->with(['position', 'principal', 'branch', 'company'])
            ->get();
        $totalTeam = $teamMembers->count();

        $teamIds = $teamMembers->pluck('id')->toArray();

        // Count who is present today
        $hadirHariIni = Attendance::whereIn('employee_id', $teamIds)
                                  ->whereDate('attendance_date', $todayStr)
                                  ->count();

        // Count who is sick / leave today
        $sakitHariIni = LeaveRequest::whereIn('employee_id', $teamIds)
                                    ->where('status', 'approved')
                                    ->where('type', 'sakit')
                                    ->whereDate('start_date', '<=', $todayStr)
                                    ->whereDate('end_date', '>=', $todayStr)
                                    ->count();

        $cutiHariIni = LeaveRequest::whereIn('employee_id', $teamIds)
                                   ->where('status', 'approved')
                                   ->whereIn('type', ['cuti', 'izin'])
                                   ->whereDate('start_date', '<=', $todayStr)
                                   ->whereDate('end_date', '>=', $todayStr)
                                   ->count();

        // Get IDs of present and on leave employees
        $presentIds = Attendance::whereIn('employee_id', $teamIds)
                                  ->whereDate('attendance_date', $todayStr)
                                  ->pluck('employee_id')
                                  ->toArray();

        $leaveIds = LeaveRequest::whereIn('employee_id', $teamIds)
                                    ->where('status', 'approved')
                                    ->whereDate('start_date', '<=', $todayStr)
                                    ->whereDate('end_date', '>=', $todayStr)
                                    ->pluck('employee_id')
                                    ->toArray();

        $activeIds = array_unique(array_merge($presentIds, $leaveIds));

        // Unchecked / Vacant employees today are those not in activeIds
        $vacantEmployees = $teamMembers->whereNotIn('id', $activeIds);
        
        // Preload attendances & leaves for last 7 days
        $attendances7Days = Attendance::whereIn('employee_id', $teamIds)
            ->whereBetween('attendance_date', [$sevenDaysAgo->toDateString(), $todayStr])
            ->get()
            ->groupBy('employee_id');

        $leaves7Days = LeaveRequest::whereIn('employee_id', $teamIds)
            ->where('status', 'approved')
            ->where('end_date', '>=', $sevenDaysAgo->toDateString())
            ->where('start_date', '<=', $todayStr)
            ->get()
            ->groupBy('employee_id');

        $vacantDetails = [];
        foreach ($vacantEmployees as $emp) {
            $empAttendances = $attendances7Days->get($emp->id, collect());
            $empLeaves = $leaves7Days->get($emp->id, collect());
            $attendedDates = $empAttendances->pluck('attendance_date')->map(fn($d) => Carbon::parse($d)->toDateString())->toArray();

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
            $daysVacant = -1; // Indicates never attended
            if ($lastAttendance) {
                $daysVacant = Carbon::parse($lastAttendance->attendance_date)->diffInDays($today);
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

        // Team Target Mandays (Sum of targets of all team members this month)
        $currentMonth = Carbon::now('Asia/Jakarta')->format('Y-m');
        $teamTargetMandays = WorkTarget::whereIn('employee_id', $teamIds)
                                       ->where('month_year', $currentMonth)
                                       ->sum('target_hk');

        // Current team attendance count this month
        $teamKehadiranBulanIni = Attendance::whereIn('employee_id', $teamIds)
                                           ->whereYear('attendance_date', Carbon::now('Asia/Jakarta')->year)
                                           ->whereMonth('attendance_date', Carbon::now('Asia/Jakarta')->month)
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
