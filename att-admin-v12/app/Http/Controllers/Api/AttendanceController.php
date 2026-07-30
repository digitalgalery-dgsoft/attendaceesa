<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Itinerary;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Ambil jadwal dan itinerary hari ini milik employee yang sedang login.
     */
    public function todaySchedule(Request $request)
    {
        $employee = $request->user();
        $today    = Carbon::today('Asia/Jakarta')->toDateString();

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $today)
            ->with(['shift', 'workLocation'])
            ->first();

        if (!$schedule) {
            return response()->json([
                'status'      => 'error',
                'can_checkin' => false,
                'message'     => 'Anda tidak memiliki jadwal untuk hari ini. Silakan hubungi Admin.',
                'data'        => null,
            ], 403);
        }

        if (in_array($schedule->schedule_type, ['dayoff', 'holiday'])) {
            return response()->json([
                'status'      => 'error',
                'can_checkin' => false,
                'message'     => 'Hari ini adalah ' . ucfirst($schedule->schedule_type) . '. Tidak bisa Check-In.',
                'data'        => null,
            ], 403);
        }

        // Ambil itinerary hari ini (rencana kunjungan), jika ada
        $itinerary = Itinerary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->with(['items.workLocation'])
            ->first();

        return response()->json([
            'status'      => 'success',
            'can_checkin' => true,
            'has_itinerary' => $itinerary ? true : false,
            'can_visit'   => $itinerary ? true : false,
            'data'        => [
                'schedule'  => $schedule,
                'itinerary' => $itinerary,
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'latitude'          => 'required|numeric',
                'longitude'         => 'required|numeric',
                'photo'             => 'required|image|max:5120',
                'type'              => 'required|in:checkin,checkout,visit_in,visit_out',
                'visit_type'        => 'nullable|in:store,prinsiple',
                'note'              => 'nullable|string',
                'visit_location_id' => 'nullable|integer',
            ]);

            $employee = $request->user();

            if (!$employee || !($employee instanceof Employee)) {
                return response()->json(['message' => 'Employee data not found'], 404);
            }

            $employeeId = $employee->id;
            $today = Carbon::today('Asia/Jakarta')->toDateString();
            $now   = Carbon::now();

            // ─── VALIDASI JADWAL WAJIB ───────────────────────────────────────
            $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('schedule_date', $today)
                ->with('workLocation')
                ->first();

            if (!$schedule) {
                return response()->json([
                    'message' => 'Check-in ditolak: Anda tidak memiliki jadwal untuk hari ini. Silakan hubungi Admin.'
                ], 403);
            }

            // Tidak bisa check-in di hari libur / day-off
            if (in_array($schedule->schedule_type, ['dayoff', 'holiday'])) {
                return response()->json([
                    'message' => 'Check-in ditolak: Hari ini adalah ' . ucfirst($schedule->schedule_type) . '.'
                ], 403);
            }

            // ─── UPLOAD FOTO ─────────────────────────────────────────────────
            $path = $request->file('photo')->store('attendances', 'public');

            // ─── ATTENDANCE HARI INI ──────────────────────────────────────────
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $today)
                ->first();

            // ─── HITUNG JARAK (GEOFENCE) ──────────────────────────────────────
            $isInsideGeofence = false;
            $distance         = 0;
            $refLocation      = $schedule->workLocation ?? null;

            // fallback ke branch jika work_location di schedule kosong
            if (!$refLocation) {
                $refLocation = \App\Models\Branch::find($employee->branch_id);
            }

            if ($refLocation && $refLocation->latitude && $refLocation->longitude) {
                $distance = $this->calculateDistance(
                    $request->latitude, $request->longitude,
                    $refLocation->latitude, $refLocation->longitude
                );
                $isInsideGeofence = ($distance <= ($refLocation->radius_meter ?? 100));
            }

            // ─── LOG ──────────────────────────────────────────────────────────
            $log = AttendanceLog::create([
                'employee_id'                  => $employeeId,
                'log_type'                     => $request->type,
                'logged_at'                    => $now,
                'latitude'                     => $request->latitude,
                'longitude'                    => $request->longitude,
                'photo_path'                   => $path,
                'is_inside_geofence'           => $isInsideGeofence,
                'distance_from_location_meter' => $distance,
                'source'                       => 'android',
            ]);

            // ─── TIPE ABSENSI ─────────────────────────────────────────────────
            if ($request->type === 'checkin') {
                if ($refLocation && $refLocation->latitude && $refLocation->longitude && !$isInsideGeofence) {
                    return response()->json(['message' => 'Check-in ditolak: Anda berada di luar radius lokasi kantor (' . round($distance) . 'm). Radius maksimal: ' . ($refLocation->radius_meter ?? 100) . 'm'], 400);
                }

                if ($attendance) {
                    return response()->json(['message' => 'Already checked in for today'], 400);
                }

                $attendance = Attendance::create([
                    'employee_id'     => $employeeId,
                    'attendance_date' => $today,
                    'status'          => 'present',
                    'checkin_at'      => $now,
                    'checkin_log_id'  => $log->id,
                ]);

                $log->update(['attendance_id' => $attendance->id]);

                return response()->json(['message' => 'Check in successful', 'attendance' => $attendance]);

            } elseif ($request->type === 'checkout') {
                if (!$attendance)             return response()->json(['message' => 'Must check in first'], 400);
                if ($attendance->checkout_at) return response()->json(['message' => 'Already checked out for today'], 400);

                if ($refLocation && $refLocation->latitude && $refLocation->longitude && !$isInsideGeofence) {
                    return response()->json(['message' => 'Check-out ditolak: Anda berada di luar radius lokasi kantor (' . round($distance) . 'm). Radius maksimal: ' . ($refLocation->radius_meter ?? 100) . 'm'], 400);
                }

                $workDuration = $now->diffInMinutes(Carbon::parse($attendance->checkin_at));
                $attendance->update([
                    'checkout_at'           => $now,
                    'checkout_log_id'       => $log->id,
                    'work_duration_minutes' => $workDuration,
                ]);
                $log->update(['attendance_id' => $attendance->id]);

                return response()->json(['message' => 'Check out successful', 'attendance' => $attendance]);

            } elseif ($request->type === 'visit_in') {
                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);
                
                $itinerary = Itinerary::where('employee_id', $employeeId)->where('date', $today)->first();
                if (!$itinerary) {
                    return response()->json(['message' => 'Visit ditolak: Anda tidak memiliki itinerary (jadwal kunjungan) hari ini.'], 403);
                }

                if ($request->visit_location_id) {
                    $loc = WorkLocation::find($request->visit_location_id);
                    if ($loc && $loc->latitude && $loc->longitude) {
                        $dist = $this->calculateDistance(
                            $request->latitude, $request->longitude,
                            $loc->latitude, $loc->longitude
                        );
                        if ($dist > ($loc->radius_meter ?? 100)) {
                            return response()->json(['message' => 'Di luar jangkauan lokasi Visit In! (' . round($dist) . 'm)'], 400);
                        }
                    }
                }

                $log->update([
                    'attendance_id' => $attendance->id,
                    'metadata'      => json_encode(['visit_location_id' => $request->visit_location_id]),
                ]);

                return response()->json(['message' => 'Visit In successful']);

            } elseif ($request->type === 'visit_out') {
                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);
                
                $itinerary = Itinerary::where('employee_id', $employeeId)->where('date', $today)->first();
                if (!$itinerary) {
                    return response()->json(['message' => 'Visit ditolak: Anda tidak memiliki itinerary (jadwal kunjungan) hari ini.'], 403);
                }
                
                if (!$request->note || !$request->visit_type) {
                    return response()->json(['message' => 'Jenis Visit dan Keterangan wajib diisi!'], 400);
                }

                $lastVisitIn = AttendanceLog::where('attendance_id', $attendance->id)
                    ->where('log_type', 'visit_in')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastVisitIn && $lastVisitIn->metadata) {
                    $meta = json_decode($lastVisitIn->metadata, true);
                    if (isset($meta['visit_location_id'])) {
                        $loc = WorkLocation::find($meta['visit_location_id']);
                        if ($loc) {
                            $dist = $this->calculateDistance(
                                $request->latitude, $request->longitude,
                                $loc->latitude, $loc->longitude
                            );
                            if ($dist > ($loc->radius_meter ?? 100)) {
                                return response()->json(['message' => 'Di luar jangkauan lokasi Visit In!'], 400);
                            }
                        }
                    }
                }

                $log->update([
                    'attendance_id' => $attendance->id,
                    'note'          => $request->note,
                    'metadata'      => json_encode(['visit_type' => $request->visit_type]),
                ]);

                return response()->json(['message' => 'Visit Out successful']);
            }

        } catch (\Exception $e) {
            Log::error('Attendance Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to record attendance', 'error' => $e->getMessage()], 500);
        }
    }

    public function history(Request $request)
    {
        $employee = $request->user();

        if (!$employee || !($employee instanceof Employee)) {
            return response()->json([]);
        }

        $startDateStr = $request->query('start_date');
        $endDateStr   = $request->query('end_date');

        $tz = new \DateTimeZone('Asia/Jakarta');

        if ($startDateStr && $endDateStr) {
            $startDate = Carbon::parse($startDateStr, 'Asia/Jakarta')->startOfDay();
            $endDate   = Carbon::parse($endDateStr, 'Asia/Jakarta')->endOfDay();
        } else {
            // Default to cutoff 26 previous month to 25 current month (local time)
            $now = Carbon::now('Asia/Jakarta');
            if ($now->day >= 26) {
                $startDate = $now->copy()->startOfDay();
                $endDate   = $now->copy()->addMonth()->setDay(25)->endOfDay();
            } else {
                $startDate = $now->copy()->subMonth()->setDay(26)->startOfDay();
                $endDate   = $now->copy()->setDay(25)->endOfDay();
            }
        }

        $plan = 0;
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekday()) {
                $plan++;
            }
            $currentDate->addDay();
        }

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->orderBy('attendance_date', 'desc')
            ->get();

        $actual = $attendances->count();
        $ach = $plan > 0 ? round(($actual / $plan) * 100, 2) : 0;

        $noOut = 0;
        foreach ($attendances as $att) {
            if (!$att->checkout_at) {
                $noOut++;
            }
        }

        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('logged_at', [$startDate, $endDate])
            ->orderBy('logged_at', 'asc')
            ->get();

        // Preload work locations for checkin/checkout
        $allWorkLocations = WorkLocation::all()->keyBy('id');

        $uniqueStores = [];
        $lessThan5Min = 0;

        $visitSessions = [];
        $formattedLogs = [];

        foreach ($logs as $log) {
            // Group by local date (Asia/Jakarta)
            $date = Carbon::parse($log->logged_at)->timezone('Asia/Jakarta')->toDateString();
            $meta = $log->metadata ? json_decode($log->metadata, true) : [];
            $location = null;

            if (isset($meta['visit_location_id'])) {
                $location = $allWorkLocations->get($meta['visit_location_id']);
                $uniqueStores[$meta['visit_location_id']] = true;
            }

            // For checkin/checkout, find location from schedule's work_location
            if (in_array($log->log_type, ['checkin', 'checkout']) && $location === null) {
                $attendance = \App\Models\Attendance::with('employeeSchedule.workLocation')
                    ->find($log->attendance_id);
                if ($attendance && $attendance->employeeSchedule && $attendance->employeeSchedule->workLocation) {
                    $location = $attendance->employeeSchedule->workLocation;
                }
            }

            if ($log->log_type === 'visit_in') {
                $visitSessions[$log->attendance_id] = [
                    'in_log'   => $log,
                    'in_time'  => Carbon::parse($log->logged_at),
                    'location' => $location,
                ];
            } elseif ($log->log_type === 'visit_out') {
                if (isset($visitSessions[$log->attendance_id])) {
                    $inSession = $visitSessions[$log->attendance_id];
                    $outTime   = Carbon::parse($log->logged_at);
                    $duration  = $outTime->diffInMinutes($inSession['in_time']);

                    if ($duration < 5) {
                        $lessThan5Min++;
                    }

                    if (!$location) {
                        $location = $inSession['location'];
                    }

                    unset($visitSessions[$log->attendance_id]);
                }
            }

            $logArray             = $log->toArray();
            $logArray['location'] = $location;
            // Build full photo URL
            $logArray['photo_url'] = $log->photo_path
                ? url('storage/' . $log->photo_path)
                : null;

            if (!isset($formattedLogs[$date])) {
                $formattedLogs[$date] = [];
            }
            $formattedLogs[$date][] = $logArray;
        }

        $uniqueStoreCount = count($uniqueStores);

        // Use local Jakarta date for today's logs
        $todayLocal = Carbon::now('Asia/Jakarta')->toDateString();
        $todayStart = Carbon::createFromFormat('Y-m-d', $todayLocal, 'Asia/Jakarta')->startOfDay()->utc();
        $todayEnd   = Carbon::createFromFormat('Y-m-d', $todayLocal, 'Asia/Jakarta')->endOfDay()->utc();

        $todayLogs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('logged_at', [$todayStart, $todayEnd])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'stats' => [
                'total_masuk' => $actual,
                'plan' => $plan,
                'actual' => $actual,
                'ach' => $ach,
                'unique_store' => $uniqueStoreCount,
                'no_out' => $noOut,
                'less_than_5_min' => $lessThan5Min
            ],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'data' => $attendances,
            'logs_by_date' => $formattedLogs,
            'today_logs' => $todayLogs
        ]);
    }

    /**
     * Kembalikan daftar lokasi untuk employee hari ini.
     * Jika ada itinerary, kembalikan lokasi dari itinerary (berurutan).
     * Jika tidak, kembalikan semua work location aktif sebagai fallback.
     */
    public function workLocations(Request $request)
    {
        $employee = $request->user();
        $today    = Carbon::today('Asia/Jakarta')->toDateString();

        $itinerary = Itinerary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->with(['items' => fn($q) => $q->orderBy('sequence'), 'items.workLocation'])
            ->first();

        if ($itinerary && $itinerary->items->count() > 0) {
            $locations = $itinerary->items
                ->map(fn($item) => $item->workLocation)
                ->filter()
                ->values();

            return response()->json([
                'source' => 'itinerary',
                'data'   => $locations,
            ]);
        }

        // Jika tidak ada itinerary, kembalikan kosong (tidak bisa visit tanpa itinerary)
        return response()->json([
            'source' => 'none',
            'data'   => [],
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $latDelta    = deg2rad($lat2 - $lat1);
        $lonDelta    = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
