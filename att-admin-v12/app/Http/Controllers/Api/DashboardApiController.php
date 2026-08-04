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
        $user = $request->user();
        if (!$user->employee) {
            return response()->json(['error' => 'Employee profile not found'], 404);
        }

        $employee = $user->employee;
        $currentMonth = Carbon::now()->format('Y-m');

        // Target HK
        $workTarget = WorkTarget::where('employee_id', $employee->id)
                                ->where('month_year', $currentMonth)
                                ->first();
        
        $targetHK = $workTarget ? $workTarget->target_hk : 0;

        // Calculate Kehadiran (Attendances this month)
        $kehadiran = Attendance::where('employee_id', $employee->id)
                               ->whereYear('date', Carbon::now()->year)
                               ->whereMonth('date', Carbon::now()->month)
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
                                   ->whereYear('date', Carbon::now()->subMonth()->year)
                                   ->whereMonth('date', Carbon::now()->subMonth()->month)
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
        $user = $request->user();
        if (!$user->employee) {
            return response()->json(['error' => 'Employee profile not found'], 404);
        }

        $employee = $user->employee;
        $today = Carbon::today()->format('Y-m-d');
        
        // Get subordinates (direct reports)
        $teamMembers = Employee::where('supervisor_id', $employee->id)->get();
        $totalTeam = $teamMembers->count();

        $teamIds = $teamMembers->pluck('id')->toArray();

        // Count who is present today
        $hadirHariIni = Attendance::whereIn('employee_id', $teamIds)
                                  ->whereDate('date', $today)
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

        $vacant = $totalTeam - ($hadirHariIni + $sakitHariIni + $cutiHariIni);
        if ($vacant < 0) $vacant = 0;

        // Team Target Mandays (Sum of targets of all team members this month)
        $currentMonth = Carbon::now()->format('Y-m');
        $teamTargetMandays = WorkTarget::whereIn('employee_id', $teamIds)
                                       ->where('month_year', $currentMonth)
                                       ->sum('target_hk');

        // Current team attendance count this month
        $teamKehadiranBulanIni = Attendance::whereIn('employee_id', $teamIds)
                                           ->whereYear('date', Carbon::now()->year)
                                           ->whereMonth('date', Carbon::now()->month)
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
            'team_target_mandays' => $teamTargetMandays,
            'team_running_rate' => $teamRunningRate
        ]);
    }
}
