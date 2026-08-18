<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Itinerary;
use App\Models\TrackingHistory;
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

        // Cek apakah ada permit (izin/cuti/sakit) yang sudah di-approve untuk hari ini
        $activePermit = \App\Models\LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if ($activePermit) {
            $typeLabel = match($activePermit->type) {
                'sakit'  => 'Sakit',
                'izin'   => 'Izin',
                'cuti'   => 'Cuti',
                default  => ucfirst($activePermit->type),
            };
            return response()->json([
                'status'           => 'permit',
                'can_checkin'      => false,
                'has_active_permit' => true,
                'permit'           => [
                    'type'       => $activePermit->type,
                    'type_label' => $typeLabel,
                    'notes'      => $activePermit->notes,
                    'start_date' => $activePermit->start_date->toDateString(),
                    'end_date'   => $activePermit->end_date->toDateString(),
                ],
                'message'  => "Anda memiliki $typeLabel yang disetujui untuk hari ini. Check-in tidak diperlukan.",
                'data'     => null,
            ]);
        }

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $today)
            ->with(['shift', 'workLocation.company'])
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

        $hasUnfinishedItinerary = false;
        if ($itinerary && $itinerary->items->count() > 0) {
            $visitedLocationIds = \App\Models\AttendanceLog::where('employee_id', $employee->id)
                ->where('log_type', 'visit_in')
                ->whereDate('logged_at', $today)
                ->get()
                ->map(function ($log) {
                    $id = $log->metadata['visit_location_id'] ?? null;
                    return $id !== null ? (int) $id : null;
                })
                ->filter()
                ->unique()
                ->toArray();
            
            $unvisitedCount = $itinerary->items->filter(function($item) use ($visitedLocationIds) {
                return !in_array((int)$item->work_location_id, $visitedLocationIds);
            })->count();
            
            if ($unvisitedCount > 0) {
                $hasUnfinishedItinerary = true;
            }
        }

        return response()->json([
            'status'      => 'success',
            'can_checkin' => true,
            'has_itinerary' => $itinerary ? true : false,
            'has_unfinished_itinerary' => $hasUnfinishedItinerary,
            'can_visit'   => $itinerary ? true : false,
            'has_active_permit' => false,
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
                'photo'             => 'nullable|image|max:5120',
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
            $path = null;
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('attendances', 'public');
            } elseif (in_array($request->type, ['checkin', 'visit_in'])) {
                return response()->json(['message' => 'Foto wajib diunggah untuk absen ini'], 400);
            }

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

            // ─── VALIDASI BISNIS DULU, BARU BUAT LOG ──────────────────────────
            // Log hanya dibuat SETELAH semua validasi lolos, mencegah log zombie.

            if ($request->type === 'checkin') {
                if ($refLocation && $refLocation->latitude && $refLocation->longitude && !$isInsideGeofence) {
                    return response()->json(['message' => 'Check-in ditolak: Anda berada di luar radius lokasi kantor (' . round($distance) . 'm). Radius maksimal: ' . ($refLocation->radius_meter ?? 100) . 'm'], 400);
                }

                if ($attendance) {
                    return response()->json(['message' => 'Already checked in for today'], 400);
                }

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
                    'validation_status'            => 'valid',
                ]);

                $attendance = Attendance::create([
                    'employee_id'          => $employeeId,
                    'employee_schedule_id' => $schedule->id,
                    'attendance_date'      => $today,
                    'status'               => 'present',
                    'checkin_at'           => $now,
                    'checkin_log_id'       => $log->id,
                ]);

                $log->update(['attendance_id' => $attendance->id]);

                // Auto-save check-in location as first tracking point so map is never empty
                try {
                    TrackingHistory::create([
                        'employee_id'   => $employeeId,
                        'attendance_id' => $attendance->id,
                        'latitude'      => $request->latitude,
                        'longitude'     => $request->longitude,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to save initial tracking point: ' . $e->getMessage());
                }

                return response()->json(['message' => 'Check in successful', 'attendance' => $attendance]);

            } elseif ($request->type === 'checkout') {
                if (!$attendance)             return response()->json(['message' => 'Must check in first'], 400);
                if ($attendance->checkout_at) return response()->json(['message' => 'Already checked out for today'], 400);

                if ($refLocation && $refLocation->latitude && $refLocation->longitude && !$isInsideGeofence) {
                    if (empty($request->note)) {
                        return response()->json(['message' => 'Catatan/alasan wajib diisi karena Anda berada di luar radius lokasi kantor (' . round($distance) . 'm). Radius maksimal: ' . ($refLocation->radius_meter ?? 100) . 'm'], 400);
                    }
                }

                $log = AttendanceLog::create([
                    'employee_id'                  => $employeeId,
                    'log_type'                     => $request->type,
                    'logged_at'                    => $now,
                    'latitude'                     => $request->latitude,
                    'longitude'                    => $request->longitude,
                    'note'                         => $request->note,
                    'photo_path'                   => $path,
                    'is_inside_geofence'           => $isInsideGeofence,
                    'distance_from_location_meter' => $distance,
                    'source'                       => 'android',
                    'validation_status'            => 'valid',
                ]);

                try {
                    $workDuration = $now->diffInMinutes(Carbon::parse($attendance->checkin_at));
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Error parse date: ' . $e->getMessage()], 500);
                }

                $upd = [
                    'checkout_at'           => $now->toDateTimeString(),
                    'checkout_log_id'       => $log->id ? (int) $log->id : null,
                    'work_duration_minutes' => (int) $workDuration,
                    'updated_at'            => $now->toDateTimeString(),
                ];
                try {
                    \Illuminate\Support\Facades\DB::table('attendances')
                        ->where('id', (int) $attendance->id)
                        ->update($upd);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'DBData['.json_encode($upd).'] ID['.$attendance->id.'] ERR:' . $e->getMessage()], 500);
                }

                try {
                    $log->update(['attendance_id' => $attendance->id]);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Error update log: ' . $e->getMessage()], 500);
                }

                return response()->json(['message' => 'Check-out successful', 'attendance' => $attendance]);

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
                    'validation_status'            => 'valid',
                ]);

                $log->update([
                    'attendance_id' => $attendance->id,
                    'metadata'      => ['visit_location_id' => $request->visit_location_id],
                ]);

                return response()->json(['message' => 'Visit In successful']);

            } elseif ($request->type === 'visit_out') {
                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);
                
                $itinerary = Itinerary::where('employee_id', $employeeId)->where('date', $today)->first();
                if (!$itinerary) {
                    return response()->json(['message' => 'Visit ditolak: Anda tidak memiliki itinerary (jadwal kunjungan) hari ini.'], 403);
                }
                
                if (!$request->visit_type) {
                    return response()->json(['message' => 'Jenis Visit wajib diisi!'], 400);
                }

                $lastVisitIn = AttendanceLog::where('attendance_id', $attendance->id)
                    ->where('log_type', 'visit_in')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastVisitIn && $lastVisitIn->metadata) {
                    $meta = $lastVisitIn->metadata;
                    if (isset($meta['visit_location_id'])) {
                        $loc = WorkLocation::find($meta['visit_location_id']);
                        if ($loc) {
                            $dist = $this->calculateDistance(
                                $request->latitude, $request->longitude,
                                $loc->latitude, $loc->longitude
                            );
                            if ($dist > ($loc->radius_meter ?? 100)) {
                                if (empty($request->note)) {
                                    return response()->json(['message' => 'Catatan/alasan wajib diisi karena Anda berada di luar radius lokasi Visit In! (' . round($dist) . 'm)'], 400);
                                }
                            }
                        }
                    }
                }

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
                    'validation_status'            => 'valid',
                ]);

                $log->update([
                    'attendance_id' => $attendance->id,
                    'note'          => $request->note,
                    'metadata'      => ['visit_type' => $request->visit_type],
                ]);

                return response()->json(['message' => 'Visit Out successful']);
            }

        } catch (\Exception $e) {
            Log::error('Attendance Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to record attendance: ' . $e->getMessage() . ' Line: ' . $e->getLine(), 'error' => $e->getMessage()], 500);
        }
    }

    public function storeVisitReport(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string', // Deskripsi Isu or General Notes
            'photo' => 'required|image|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'met_with' => 'required|string',
            'position' => 'required|string',
            'is_issue' => 'required|boolean',
            'action_taken' => 'nullable|string',
            'itinerary_item_id' => 'nullable|integer', // Optional, if mobile has it
            'target_type' => 'nullable|string',
            'target_qty' => 'nullable|string',
            'actual_qty' => 'nullable|string',
            'target_value' => 'nullable|string',
            'actual_value' => 'nullable|string',
            'deadline' => 'nullable|date',
        ]);

        $employee = $request->user();
        if (!$employee) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $employeeId = $employee->id;
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $now = Carbon::now();

        try {
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $today)
                ->first();

            if (!$attendance) {
                return response()->json(['message' => 'Harap Check-in terlebih dahulu sebelum melakukan visit'], 400);
            }

            // Find the last visit_in log
            $lastVisitIn = AttendanceLog::where('attendance_id', $attendance->id)
                ->where('log_type', 'visit_in')
                ->orderBy('id', 'desc')
                ->first();

            if (!$lastVisitIn) {
                return response()->json(['message' => 'Anda belum melakukan Visit-In'], 400);
            }

            $visitLocationId = $lastVisitIn->metadata['visit_location_id'] ?? null;
            $principalId = null;
            
            // Try to find the associated itinerary item
            if ($request->itinerary_item_id) {
                $item = \App\Models\ItineraryItem::find($request->itinerary_item_id);
                if ($item) $principalId = $item->principal_id;
            } elseif ($visitLocationId) {
                // Find itinerary item for today that matches this location
                $item = \App\Models\ItineraryItem::whereHas('itinerary', function ($q) use ($employeeId, $today) {
                    $q->where('employee_id', $employeeId)->where('date', $today);
                })->where('work_location_id', $visitLocationId)->first();
                if ($item) $principalId = $item->principal_id;
            }

            // Calculate distance for visit_out log
            $distance = 0;
            $isInsideGeofence = false;
            if ($visitLocationId) {
                $loc = WorkLocation::find($visitLocationId);
                if ($loc && $loc->latitude && $loc->longitude) {
                    $distance = $this->calculateDistance(
                        $request->latitude, $request->longitude,
                        $loc->latitude, $loc->longitude
                    );
                    $isInsideGeofence = ($distance <= ($loc->radius_meter ?? 100));
                }
            }

            // Upload Photo
            $path = $request->file('photo')->store('visit_reports', 'public');

            // 1. Create Visit Report Record
            $visitReport = \App\Models\VisitReport::create([
                'employee_id' => $employeeId,
                'itinerary_item_id' => $request->itinerary_item_id ?? ($item->id ?? null),
                'principal_id' => $principalId,
                'met_with' => $request->met_with,
                'position' => $request->position,
                'issue' => $request->is_issue ? 'Ya' : 'Tidak',
                'notes' => $request->notes,
                'action_taken' => $request->is_issue ? $request->action_taken : null,
                'target_type' => $request->target_type,
                'target_qty' => $request->target_qty,
                'actual_qty' => $request->actual_qty,
                'target_value' => $request->target_value,
                'actual_value' => $request->actual_value,
                'deadline' => $request->deadline,
                'photo_path' => $path,
                'status' => 'completed',
            ]);

            // 2. Create automatic Visit Out Log
            $log = AttendanceLog::create([
                'employee_id' => $employeeId,
                'attendance_id' => $attendance->id,
                'log_type' => 'visit_out',
                'logged_at' => $now,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'photo_path' => $path,
                'is_inside_geofence' => $isInsideGeofence,
                'distance_from_location_meter' => $distance,
                'source' => 'android',
                'validation_status' => 'valid',
                'note' => 'Report Submitted',
                'metadata' => [
                    'visit_report_id' => $visitReport->id,
                    'visit_location_id' => $visitLocationId,
                    'visit_type' => 'store' // default for now
                ]
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan visit & Visit Out berhasil diproses',
                'data' => $visitReport
            ]);
        } catch (\Exception $e) {
            Log::error('Visit Report Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengirim laporan visit: ' . $e->getMessage()], 500);
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
            $cutoff = $employee->department->cutoff_start_date ?? 26;
            $now = Carbon::now('Asia/Jakarta');
            
            if ($cutoff == 1) {
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
            } else {
                if ($now->day >= $cutoff) {
                    $startDate = $now->copy()->setDay($cutoff)->startOfDay();
                    $endDate   = $now->copy()->addMonth()->setDay($cutoff - 1)->endOfDay();
                } else {
                    $startDate = $now->copy()->subMonth()->setDay($cutoff)->startOfDay();
                    $endDate   = $now->copy()->setDay($cutoff - 1)->endOfDay();
                }
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
            $meta = $log->metadata ?: [];
            $location = null;

            if (isset($meta['visit_location_id'])) {
                $location = $allWorkLocations->get($meta['visit_location_id']);
                $uniqueStores[$meta['visit_location_id']] = true;
            }

            // For checkin/checkout, find location from schedule's work_location
            if (in_array($log->log_type, ['checkin', 'checkout']) && $location === null) {
                $attendance = \App\Models\Attendance::with('employeeSchedule.workLocation.company')
                    ->find($log->attendance_id);
                if ($attendance && $attendance->employeeSchedule && $attendance->employeeSchedule->workLocation) {
                    $location = $attendance->employeeSchedule->workLocation;
                } else {
                    // Fallback to searching schedule by date
                    $schedule = \App\Models\EmployeeSchedule::with('workLocation.company')
                        ->where('employee_id', $employee->id)
                        ->where('schedule_date', $date)
                        ->first();
                    if ($schedule && $schedule->workLocation) {
                        $location = $schedule->workLocation;
                        // Also update attendance record to fix missing ID
                        if ($attendance && !$attendance->employee_schedule_id) {
                            $attendance->update(['employee_schedule_id' => $schedule->id]);
                        }
                    } else {
                        $location = \App\Models\Branch::find($employee->branch_id);
                    }
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
        $todayStart = Carbon::createFromFormat('Y-m-d', $todayLocal, 'Asia/Jakarta')->startOfDay();
        $todayEnd   = Carbon::createFromFormat('Y-m-d', $todayLocal, 'Asia/Jakarta')->endOfDay();

        // Ambil schedule hari ini untuk mendapatkan work location checkin/checkout
        $todaySchedule = \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $todayLocal)
            ->with('workLocation')
            ->first();
        $scheduleWorkLocation = $todaySchedule?->workLocation ? $todaySchedule->workLocation->toArray() : null;

        $rawLogs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('logged_at', [$todayStart, $todayEnd])
            ->orderBy('id', 'asc') // Sort asc to track active visit location sequentially
            ->get();
            
        $activeVisitLocation = null;
        
        $todayLogs = $rawLogs->map(function ($log) use (&$activeVisitLocation, $scheduleWorkLocation) {
            $logArray = $log->toArray();
            
            // Attach schedule work location for checkin/checkout
            if (in_array($log->log_type, ['checkin', 'checkout'])) {
                if ($scheduleWorkLocation) {
                    $logArray['visit_location'] = $scheduleWorkLocation;
                }
            }
            
            // If it's visit_in, update the active visit location
            if ($log->log_type === 'visit_in') {
                if (isset($log->metadata['visit_location_id'])) {
                    $loc = \App\Models\WorkLocation::find($log->metadata['visit_location_id']);
                    if ($loc) {
                        $activeVisitLocation = $loc->toArray();
                    }
                } else {
                    $activeVisitLocation = null;
                }
            }

            // Append active visit location to any visit activity (visit_in, visit_report, visit_out)
            if (in_array($log->log_type, ['visit_in', 'visit_report', 'visit_out'])) {
                if ($activeVisitLocation) {
                    $logArray['visit_location'] = $activeVisitLocation;
                }
            }
            
            return $logArray;
        })->reverse()->values(); // Reverse back to desc

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

        // Ambil ID lokasi yang sudah di-visit hari ini
        $visitedLocationIds = AttendanceLog::where('employee_id', $employee->id)
            ->where('log_type', 'visit_in')
            ->whereDate('logged_at', $today)
            ->get()
            ->map(function ($log) {
                $id = $log->metadata['visit_location_id'] ?? null;
                return $id !== null ? (int) $id : null;
            })
            ->filter()
            ->unique()
            ->toArray();

        $itinerary = Itinerary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->with(['items' => fn($q) => $q->orderBy('sequence'), 'items.workLocation'])
            ->first();

        if ($itinerary && $itinerary->items->count() > 0) {
            $locations = $itinerary->items
                ->map(fn($item) => $item->workLocation)
                ->filter()
                ->reject(fn($loc) => in_array((int) $loc->id, $visitedLocationIds))
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
