<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'photo' => 'required|image|max:5120',
                'type' => 'required|in:checkin,checkout,visit_in,visit_out',
                'visit_type' => 'nullable|in:store,prinsiple',
                'note' => 'nullable|string',
                'visit_location_id' => 'nullable|integer',
            ]);

            $user = $request->user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json(['message' => 'Employee data not found'], 404);
            }

            $date = Carbon::now()->toDateString();
            $now = Carbon::now();

            // Handle photo upload
            $path = $request->file('photo')->store('attendances', 'public');

            // Find existing attendance for today
            $attendance = Attendance::where('employee_id', $employee->id)
                                    ->where('attendance_date', $date)
                                    ->first();

            // Calculate distance (simplified geofence)
            $branch = $employee->branch;
            $isInsideGeofence = false;
            $distance = 0;
            if ($branch && $branch->latitude && $branch->longitude) {
                $distance = $this->calculateDistance($request->latitude, $request->longitude, $branch->latitude, $branch->longitude);
                $isInsideGeofence = ($distance <= ($branch->radius_meter ?? 100));
            }

            // Create Log
            $log = AttendanceLog::create([
                'employee_id' => $employee->id,
                'log_type' => $request->type,
                'logged_at' => $now,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'photo_path' => $path,
                'is_inside_geofence' => $isInsideGeofence,
                'distance_from_location_meter' => $distance,
                'source' => 'android',
            ]);

            if ($request->type === 'checkin') {
                if ($attendance) {
                    return response()->json(['message' => 'Already checked in for today'], 400);
                }
                
                // TODO: Geofence logic against Itinerary (for now, simply use Branch logic)

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'status' => 'present',
                    'checkin_at' => $now,
                    'checkin_log_id' => $log->id,
                ]);

                $log->update(['attendance_id' => $attendance->id]);

                return response()->json([
                    'message' => 'Check in successful',
                    'attendance' => $attendance
                ]);

            } else if ($request->type === 'checkout') {
                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);
                if ($attendance->checkout_at) return response()->json(['message' => 'Already checked out for today'], 400);

                $workDuration = $now->diffInMinutes(Carbon::parse($attendance->checkin_at));
                $attendance->update([
                    'checkout_at' => $now,
                    'checkout_log_id' => $log->id,
                    'work_duration_minutes' => $workDuration,
                ]);

                $log->update(['attendance_id' => $attendance->id]);

                return response()->json([
                    'message' => 'Check out successful',
                    'attendance' => $attendance
                ]);
            } else if ($request->type === 'visit_in') {
                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);
                
                // Save visit_location_id to log metadata
                $log->update([
                    'attendance_id' => $attendance->id,
                    'metadata' => json_encode(['visit_location_id' => $request->visit_location_id]),
                ]);

                return response()->json([
                    'message' => 'Visit In successful',
                ]);
            } else if ($request->type === 'visit_out') {
                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);
                if (!$request->note || !$request->visit_type) {
                    return response()->json(['message' => 'Jenis Visit dan Keterangan wajib diisi!'], 400);
                }
                
                // Validate geofence against last visit_in location
                $lastVisitIn = AttendanceLog::where('attendance_id', $attendance->id)
                    ->where('log_type', 'visit_in')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($lastVisitIn && $lastVisitIn->metadata) {
                    $meta = json_decode($lastVisitIn->metadata, true);
                    if (isset($meta['visit_location_id'])) {
                        $loc = WorkLocation::find($meta['visit_location_id']);
                        if ($loc) {
                            $dist = $this->calculateDistance($request->latitude, $request->longitude, $loc->latitude, $loc->longitude);
                            if ($dist > ($loc->radius_meter ?? 100)) {
                                return response()->json(['message' => 'Di luar jangkauan lokasi Visit In!'], 400);
                            }
                        }
                    }
                }

                $log->update([
                    'attendance_id' => $attendance->id,
                    'note' => $request->note,
                    'metadata' => json_encode(['visit_type' => $request->visit_type]),
                ]);

                return response()->json([
                    'message' => 'Visit Out successful',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Attendance Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to record attendance', 'error' => $e->getMessage()], 500);
        }
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        
        if (!$employee) {
            return response()->json([]);
        }

        $attendances = Attendance::where('employee_id', $employee->id)
            ->orderBy('attendance_date', 'desc')
            ->limit(30)
            ->get();

        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('logged_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'data' => $attendances,
            'today_logs' => $logs
        ]);
    }

    public function workLocations()
    {
        return response()->json(['data' => WorkLocation::where('status', 'active')->get()]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
