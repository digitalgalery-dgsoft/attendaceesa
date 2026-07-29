<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrackingHistory;
use App\Models\Attendance;
use Carbon\Carbon;

class TrackingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        if (!$user || !$user->employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        $employeeId = $user->employee->id;
        $today = Carbon::today()->format('Y-m-d');
        
        // Cari absensi hari ini yang belum checkout (opsional, tapi berguna untuk grouping history per hari)
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->first();

        // Kita tetap rekam history meskipun belum check-in jika service nyala, 
        // tapi idealnya service dimatikan kalau belum check-in.
        
        TrackingHistory::create([
            'employee_id' => $employeeId,
            'attendance_id' => $attendance ? $attendance->id : null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location recorded'
        ]);
    }
}
