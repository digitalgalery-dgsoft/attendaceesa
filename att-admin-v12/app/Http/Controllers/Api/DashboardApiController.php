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
                                ->where('month_year', $currentMonth)
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
        $today = Carbon::today()->format('Y-m-d');
        
        // Get subordinates (direct reports)
        $teamMembers = Employee::where('supervisor_id', $employee->id)->get();
        $totalTeam = $teamMembers->count();

        $teamIds = $teamMembers->pluck('id')->toArray();

        // Count who is present today
        $hadirHariIni = Attendance::whereIn('employee_id', $teamIds)
                                  ->whereDate('attendance_date', $today)
                                  ->count();

        // Count who is sick / leave today
        $sakitHariIni = LeaveRequest::whereIn('employee_id', $teamIds)
                                    ->where('status', 'approved')
                                    ->where('type', 'sakit')
                                    ->whereDate('start_date', '<=', $today)
                                    ->whereDate('end_date', '>=', $today)
                                    ->count();

        $cutiHariIni = LeaveRequest::whereIn('employee_id', $teamIds)
                                   ->where('status', 'approved')
                                   ->whereIn('type', ['cuti', 'izin'])
                                   ->whereDate('start_date', '<=', $today)
                                   ->whereDate('end_date', '>=', $today)
                                   ->count();

        // Get IDs of present and on leave employees
        $presentIds = Attendance::whereIn('employee_id', $teamIds)
                                  ->whereDate('attendance_date', $today)
                                  ->pluck('employee_id')
                                  ->toArray();

        $leaveIds = LeaveRequest::whereIn('employee_id', $teamIds)
                                    ->where('status', 'approved')
                                    ->whereDate('start_date', '<=', $today)
                                    ->whereDate('end_date', '>=', $today)
                                    ->pluck('employee_id')
                                    ->toArray();

        $activeIds = array_unique(array_merge($presentIds, $leaveIds));

        // Vacant employees are those not in activeIds
        $vacantEmployees = $teamMembers->whereNotIn('id', $activeIds);
        
        $vacantDetails = [];
        foreach ($vacantEmployees as $emp) {
            $lastAttendance = Attendance::where('employee_id', $emp->id)
                                        ->orderBy('attendance_date', 'desc')
                                        ->first();
            $daysVacant = -1; // Indicates never attended
            if ($lastAttendance) {
                $daysVacant = Carbon::parse($lastAttendance->attendance_date)->diffInDays(Carbon::today());
            }
            $vacantDetails[] = [
                'name' => $emp->full_name ?? 'Unknown',
                'days' => $daysVacant
            ];
        }

        $vacant = count($vacantDetails);

        // Team Target Mandays (Sum of targets of all team members this month)
        $currentMonth = Carbon::now()->format('Y-m');
        $teamTargetMandays = WorkTarget::whereIn('employee_id', $teamIds)
                                       ->where('month_year', $currentMonth)
                                       ->sum('target_hk');

        // Current team attendance count this month
        $teamKehadiranBulanIni = Attendance::whereIn('employee_id', $teamIds)
                                           ->whereYear('attendance_date', Carbon::now()->year)
                                           ->whereMonth('attendance_date', Carbon::now()->month)
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
}
