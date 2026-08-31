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

        // Cek fallback Visit Schedule (Itinerary) jika belum ada roster reguler
        $itineraryCheckinItem = null;
        $itineraryToday = \App\Models\Itinerary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->with(['items.workLocation'])
            ->first();

        if (!$schedule) {
            $itineraryCheckinItem = \App\Models\ItineraryItem::whereHas('itinerary', function($q) use ($employee, $today) {
                $q->where('employee_id', $employee->id)->where('date', $today);
            })->where('is_checkin_location', true)->with('workLocation')->first();
        }

        $isRatecardWithItinerary = (!$employee->is_inhouse && $itineraryToday && $itineraryToday->items->count() > 0);

        if (!$schedule && !$itineraryCheckinItem && !$isRatecardWithItinerary) {
            return response()->json([
                'status'      => 'error',
                'can_checkin' => false,
                'message'     => 'Anda tidak memiliki jadwal untuk hari ini. Silakan hubungi Admin.',
                'data'        => null,
            ], 403);
        }

        if ($schedule && in_array($schedule->schedule_type, ['dayoff', 'holiday'])) {
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
                    $meta = is_array($log->metadata) ? $log->metadata : (is_string($log->metadata) ? json_decode($log->metadata, true) : []);
                    return is_array($meta) ? ($meta['visit_location_id'] ?? null) : null;
                })
                ->filter()
                ->map(fn($id) => (int)$id)
                ->toArray();
            
            $isStrict = (bool)($itinerary->is_strict_routing ?? false);
            $foundNextTarget = false;

            $itinerary->items->each(function($item) use ($visitedLocationIds, $isStrict, &$foundNextTarget) {
                $isVisited = in_array((int)$item->work_location_id, $visitedLocationIds);
                $item->is_visited = $isVisited;

                if ($isStrict) {
                    if (!$isVisited && !$foundNextTarget) {
                        $item->is_next_target = true;
                        $item->is_locked = false;
                        $foundNextTarget = true;
                    } elseif (!$isVisited && $foundNextTarget) {
                        $item->is_next_target = false;
                        $item->is_locked = true;
                    } else {
                        $item->is_next_target = false;
                        $item->is_locked = false;
                    }
                } else {
                    $item->is_next_target = !$isVisited;
                    $item->is_locked = false;
                }
            });

            $unvisitedCount = $itinerary->items->filter(fn($item) => !$item->is_visited)->count();
            if ($unvisitedCount > 0) {
                $hasUnfinishedItinerary = true;
            }

            $itinerary->routing_rule = $isStrict ? 'strict' : 'flexible';
            $itinerary->routing_rule_label = $isStrict ? 'Routing Aktif (Wajib Berurutan)' : 'Bebas Visit (Acak)';
        }

        return response()->json([
            'status'                   => 'success',
            'can_checkin'              => true,
            'can_visit'                => $hasUnfinishedItinerary,
            'has_unfinished_itinerary' => $hasUnfinishedItinerary,
            'message'                  => 'Jadwal hari ini berhasil dimuat.',
            'data'                     => [
                'schedule'                 => $schedule ?: [
                    'id'            => null,
                    'schedule_date' => $today,
                    'schedule_type' => 'workday',
                    'work_location' => $itineraryCheckinItem?->workLocation,
                    'shift'         => null,
                ],
                'has_itinerary'            => $itinerary ? true : false,
                'has_unfinished_itinerary' => $hasUnfinishedItinerary,
                'can_visit'                => $hasUnfinishedItinerary,
                'itinerary'                => $itinerary,
                'meta'                     => [
                    'is_from_visit_schedule' => $schedule ? false : true,
                    'itinerary'              => $itinerary,
                ]
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'latitude'                     => 'required|numeric',
                'longitude'                    => 'required|numeric',
                'photo'                        => 'nullable|image|max:5120',
                'type'                         => 'required|in:checkin,checkout,visit_in,visit_out',
                'visit_type'                   => 'nullable|in:store,prinsiple',
                'note'                         => 'nullable|string',
                'visit_location_id'            => 'nullable|integer',
                'scheduled_type'               => 'nullable|string',
                'scheduled_work_location_id'   => 'nullable|integer',
                'scheduled_meeting_id'         => 'nullable|integer',
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

            // Cek fallback Visit Schedule (Itinerary) jika belum ada jadwal reguler
            $itineraryCheckinItem = null;
            if (!$schedule) {
                $itineraryCheckinItem = \App\Models\ItineraryItem::whereHas('itinerary', function($q) use ($employeeId, $today) {
                    $q->where('employee_id', $employeeId)->where('date', $today);
                })->where('is_checkin_location', true)->with('workLocation')->first();
            }

            $itineraryToday = \App\Models\Itinerary::where('employee_id', $employeeId)->where('date', $today)->first();
            $isRatecardWithItinerary = (!$employee->is_inhouse && $itineraryToday);

            if (!$schedule && !$itineraryCheckinItem && !$isRatecardWithItinerary) {
                return response()->json([
                    'message' => 'Check-in ditolak: Anda tidak memiliki jadwal untuk hari ini. Silakan hubungi Admin.'
                ], 403);
            }

            // Tidak bisa check-in di hari libur / day-off
            if ($schedule && in_array($schedule->schedule_type, ['dayoff', 'holiday'])) {
                return response()->json([
                    'message' => 'Check-in ditolak: Hari ini adalah ' . ucfirst($schedule->schedule_type) . '.'
                ], 403);
            }

            // ─── UPLOAD FOTO ─────────────────────────────────────────────────
            $path = null;
            $isFaceRequired = true;
            if ($employee) {
                $employee->loadMissing('position');
                if ($employee->position && isset($employee->position->require_face_recognition)) {
                    $isFaceRequired = (bool) $employee->position->require_face_recognition;
                }
            }

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('attendances', 'public');
            } elseif (in_array($request->type, ['checkin', 'visit_in'])) {
                if ($isFaceRequired) {
                    return response()->json(['message' => 'Foto verifikasi wajah wajib diunggah untuk absen ini'], 400);
                }
            }

            // ─── ATTENDANCE HARI INI ──────────────────────────────────────────
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $today)
                ->first();

            // ─── HITUNG JARAK (GEOFENCE) ──────────────────────────────────────
            $isInsideGeofence = false;
            $distance         = 0;
            $refLocation      = $schedule?->workLocation ?? ($itineraryCheckinItem?->workLocation ?? null);

            // fallback ke branch jika work_location di schedule kosong
            if (!$refLocation) {
                $refLocation = \App\Models\Branch::find($employee->branch_id);
            }

            $isScheduledLocation = false;
            $scheduledLocationName = null;

            if ($request->type === 'checkin') {
                if ($request->filled('scheduled_work_location_id') || ($request->filled('visit_location_id') && $request->scheduled_type === 'visit')) {
                    $locId = $request->scheduled_work_location_id ?: $request->visit_location_id;
                    $customLoc = \App\Models\WorkLocation::find($locId);
                    if ($customLoc) {
                        $refLocation = $customLoc;
                        $isScheduledLocation = true;
                        $scheduledLocationName = $customLoc->name;
                    }
                } elseif ($request->filled('scheduled_meeting_id') || ($request->filled('meeting_id') && $request->scheduled_type === 'meeting')) {
                    $mId = $request->scheduled_meeting_id ?: $request->meeting_id;
                    $meeting = \App\Models\Meeting::find($mId);
                    if ($meeting && $meeting->latitude && $meeting->longitude) {
                        $refLocation = (object) [
                            'latitude'     => (float) $meeting->latitude,
                            'longitude'    => (float) $meeting->longitude,
                            'radius_meter' => $meeting->radius_meter ?? 100,
                            'name'         => $meeting->location_name ?? $meeting->title,
                        ];
                        $isScheduledLocation = true;
                        $scheduledLocationName = $meeting->title . ' (' . ($meeting->location_name ?? 'Meeting') . ')';
                    }
                }

                // If scheduled location is used, note is required
                if ($isScheduledLocation && empty(trim($request->note ?? ''))) {
                    return response()->json([
                        'message' => 'Catatan wajib diisi jika melakukan Check-in di Lokasi Terjadwal.'
                    ], 422);
                }
            }
            $allowedRadius = 100;
            if ($employee) {
                $employee->loadMissing('position');
            }

            if ($refLocation && $refLocation->latitude && $refLocation->longitude) {
                $distance = $this->calculateDistance(
                    $request->latitude, $request->longitude,
                    $refLocation->latitude, $refLocation->longitude
                );

                if ($employee && $employee->position && !empty($employee->position->distance_lock_override) && (int) $employee->position->distance_lock_override > 0) {
                    $allowedRadius = (int) $employee->position->distance_lock_override;
                } elseif (isset($refLocation->radius_meter) && (int) $refLocation->radius_meter > 0) {
                    $allowedRadius = (int) $refLocation->radius_meter;
                }

                $isInsideGeofence = ($distance <= $allowedRadius);
            }

            // ─── VALIDASI BISNIS DULU, BARU BUAT LOG ──────────────────────────
            // Log hanya dibuat SETELAH semua validasi lolos, mencegah log zombie.

            if ($request->type === 'checkin') {
                if ($refLocation && $refLocation->latitude && $refLocation->longitude && !$isInsideGeofence) {
                    $locName = isset($refLocation->name) ? $refLocation->name : 'kantor';
                    return response()->json(['message' => 'Check-in ditolak: Anda berada di luar radius lokasi ' . $locName . ' (' . round($distance) . 'm). Radius maksimal: ' . $allowedRadius . 'm'], 400);
                }

                if ($attendance) {
                    return response()->json(['message' => 'Already checked in for today'], 400);
                }

                $meta = [];
                if ($isScheduledLocation) {
                    $meta['is_scheduled_location'] = true;
                    $meta['scheduled_type'] = $request->scheduled_type ?? ($request->filled('scheduled_meeting_id') ? 'meeting' : 'visit');
                    $meta['scheduled_location_name'] = $scheduledLocationName;
                    if ($request->filled('scheduled_work_location_id') || $request->filled('visit_location_id')) {
                        $meta['visit_location_id'] = (int) ($request->scheduled_work_location_id ?: $request->visit_location_id);
                    }
                    if ($request->filled('scheduled_meeting_id') || $request->filled('meeting_id')) {
                        $meta['meeting_id'] = (int) ($request->scheduled_meeting_id ?: $request->meeting_id);
                    }
                }

                $log = AttendanceLog::create([
                    'employee_id'                  => $employeeId,
                    'log_type'                     => $request->type,
                    'logged_at'                    => $now,
                    'latitude'                     => $request->latitude,
                    'longitude'                    => $request->longitude,
                    'photo_path'                   => $path,
                    'note'                         => $request->note,
                    'metadata'                     => !empty($meta) ? $meta : null,
                    'is_inside_geofence'           => $isInsideGeofence,
                    'distance_from_location_meter' => $distance,
                    'source'                       => 'android',
                    'validation_status'            => 'valid',
                ]);

                $lateMinutes = 0;
                $status = 'present';
                if ($schedule && $schedule->shift && !empty($schedule->shift->start_time)) {
                    $shiftStartTime = Carbon::parse($today . ' ' . $schedule->shift->start_time);
                    $grace = (int)($schedule->shift->grace_checkin_minutes ?? 0);
                    $allowedTime = $shiftStartTime->copy()->addMinutes($grace);
                    if ($now->greaterThan($allowedTime)) {
                        $lateMinutes = (int)$now->diffInMinutes($shiftStartTime);
                        $status = 'late';
                    }
                } elseif ($schedule && !empty($schedule->planned_start_at)) {
                    $plannedStart = Carbon::parse($schedule->planned_start_at);
                    if ($now->greaterThan($plannedStart)) {
                        $lateMinutes = (int)$now->diffInMinutes($plannedStart);
                        $status = 'late';
                    }
                }

                $attendance = Attendance::create([
                    'employee_id'          => $employeeId,
                    'employee_schedule_id' => $schedule->id,
                    'attendance_date'      => $today,
                    'status'               => $status,
                    'checkin_at'           => $now,
                    'checkin_log_id'       => $log->id,
                    'late_minutes'         => $lateMinutes,
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
                        return response()->json(['message' => 'Catatan/alasan wajib diisi karena Anda berada di luar radius lokasi kantor (' . round($distance) . 'm). Radius maksimal: ' . $allowedRadius . 'm'], 400);
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
                $itinerary = Itinerary::where('employee_id', $employeeId)
                    ->where('date', $today)
                    ->with(['items' => function($q) {
                        $q->orderBy('sequence', 'asc')->with('workLocation');
                    }])
                    ->first();

                if (!$itinerary) {
                    return response()->json(['message' => 'Visit ditolak: Anda tidak memiliki itinerary (jadwal kunjungan) hari ini.'], 403);
                }

                // ─── AUTO CHECK-IN UNTUK RATECARD PADA FIRST VISIT-IN ─────────────
                if (!$attendance && (!$employee->is_inhouse || !$schedule)) {
                    $attendance = Attendance::create([
                        'employee_id'          => $employeeId,
                        'employee_schedule_id' => $schedule?->id,
                        'attendance_date'      => $today,
                        'status'               => 'present',
                        'checkin_at'           => $now,
                        'late_minutes'         => 0,
                    ]);

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
                }

                if (!$attendance) return response()->json(['message' => 'Must check in first'], 400);

                // ─── VALIDASI ATURAN ROUTING VISIT (WAJIB BERURUTAN) ───────────────────
                if ($itinerary->is_strict_routing && $itinerary->items->isNotEmpty()) {
                    $visitedLocationIds = \App\Models\AttendanceLog::where('employee_id', $employeeId)
                        ->where('log_type', 'visit_in')
                        ->whereDate('logged_at', $today)
                        ->get()
                        ->map(function ($l) {
                            $meta = is_array($l->metadata) ? $l->metadata : (is_string($l->metadata) ? json_decode($l->metadata, true) : []);
                            $id = is_array($meta) ? ($meta['visit_location_id'] ?? null) : null;
                            return $id ? (int)$id : null;
                        })
                        ->filter()
                        ->unique()
                        ->toArray();

                    // Cari toko/titik kunjungan unvisited pertama berdasarkan urutan sequence
                    $nextTargetItem = $itinerary->items->first(function ($item) use ($visitedLocationIds) {
                        return !in_array((int)$item->work_location_id, $visitedLocationIds);
                    });

                    if ($nextTargetItem && (int)$nextTargetItem->work_location_id !== (int)$request->visit_location_id) {
                        $targetName = $nextTargetItem->workLocation?->name ?? ('Lokasi Urutan ke-' . $nextTargetItem->sequence);
                        return response()->json([
                            'status' => 'error',
                            'message' => "Aturan Routing Visit Aktif: Anda wajib absen kunjungan mengikuti urutan list toko. Silakan lakukan Visit In di '{$targetName}' (Urutan #{$nextTargetItem->sequence}) terlebih dahulu sebelum mengunjungi lokasi lainnya."
                        ], 422);
                    }
                }

                $isOnlineMeeting = false;
                if ($request->visit_location_id) {
                    $itineraryItem = \App\Models\ItineraryItem::whereHas('itinerary', function($q) use ($employeeId, $today) {
                        $q->where('employee_id', $employeeId)->where('date', $today);
                    })->where('work_location_id', $request->visit_location_id)->orderBy('id', 'desc')->first();

                    if ($itineraryItem && strtolower(trim($itineraryItem->meeting_type)) === 'online') {
                        $isOnlineMeeting = true;
                    }
                }

                if ($request->visit_location_id && !$isOnlineMeeting) {
                    $loc = WorkLocation::find($request->visit_location_id);
                    if ($loc && $loc->latitude && $loc->longitude) {
                        $dist = $this->calculateDistance(
                            $request->latitude, $request->longitude,
                            $loc->latitude, $loc->longitude
                        );
                        $allowedRadius = $loc->getEffectiveRadiusForEmployee($employee);
                        if ($dist > $allowedRadius) {
                            return response()->json(['message' => 'Di luar jangkauan lokasi Visit In! (' . round($dist) . 'm). Radius maksimal: ' . $allowedRadius . 'm'], 400);
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

                // Link checkin_log_id jika dibuat otomatis
                if (empty($attendance->checkin_log_id)) {
                    $attendance->update(['checkin_log_id' => $log->id]);
                }

                return response()->json(['message' => 'Visit In successful', 'attendance' => $attendance]);

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
                            $allowedRadius = $loc->getEffectiveRadiusForEmployee($employee);
                            if ($dist > $allowedRadius) {
                                if (empty($request->note)) {
                                    return response()->json(['message' => 'Catatan/alasan wajib diisi karena Anda berada di luar radius lokasi Visit In! (' . round($dist) . 'm). Radius maksimal: ' . $allowedRadius . 'm'], 400);
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
                    'metadata'      => [
                        'visit_type'        => $request->visit_type,
                        'visit_location_id' => $lastVisitIn->metadata['visit_location_id'] ?? null,
                    ],
                ]);

                // ─── AUTO CHECK-OUT UNTUK RATECARD PADA VISIT-OUT ──────────────
                if (!$employee->is_inhouse || !$schedule) {
                    $workDuration = 0;
                    if ($attendance->checkin_at) {
                        $workDuration = $now->diffInMinutes(Carbon::parse($attendance->checkin_at));
                    }
                    $attendance->update([
                        'checkout_at'           => $now->toDateTimeString(),
                        'checkout_log_id'       => $log->id,
                        'work_duration_minutes' => (int) $workDuration,
                    ]);
                }

                return response()->json(['message' => 'Visit Out successful', 'attendance' => $attendance]);
            }

        } catch (\Exception $e) {
            Log::error('Attendance Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to record attendance: ' . $e->getMessage() . ' Line: ' . $e->getLine(), 'error' => $e->getMessage()], 500);
        }
    }

    public function storeVisitReport(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'photo' => 'required|image|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'met_with' => 'nullable|string',
            'position' => 'nullable|string',
            'is_issue' => 'nullable|boolean',
            'action_taken' => 'nullable|string',
            'itinerary_item_id' => 'nullable|integer',
            'principal_id' => 'nullable|integer',
            'grooming_condition' => 'nullable|string',
            'active_promo' => 'nullable|string',
            'oos_products' => 'nullable|string',
            'other_issues' => 'nullable|string',
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
                // If ratecard without prior checkin, auto create attendance
                if (!$employee->is_inhouse) {
                    $attendance = Attendance::create([
                        'employee_id'          => $employeeId,
                        'attendance_date'      => $today,
                        'status'               => 'present',
                        'checkin_at'           => $now,
                        'late_minutes'         => 0,
                    ]);
                } else {
                    return response()->json(['message' => 'Harap Check-in terlebih dahulu sebelum melakukan visit'], 400);
                }
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
            $principalId = $request->principal_id;
            
            // Try to find the associated itinerary item
            if ($request->itinerary_item_id) {
                $item = \App\Models\ItineraryItem::find($request->itinerary_item_id);
                if ($item && !$principalId) $principalId = $item->principal_id;
            } elseif ($visitLocationId) {
                // Find itinerary item for today that matches this location
                $item = \App\Models\ItineraryItem::whereHas('itinerary', function ($q) use ($employeeId, $today) {
                    $q->where('employee_id', $employeeId)->where('date', $today);
                })->where('work_location_id', $visitLocationId)->first();
                if ($item && !$principalId) $principalId = $item->principal_id;
            }

            // Fallback to employee principal_id if still null
            if (!$principalId) {
                $principalId = $employee->principal_id;
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
                    $allowedRadius = $loc->getEffectiveRadiusForEmployee($employee);
                    $isInsideGeofence = ($distance <= $allowedRadius);
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
                'grooming_condition' => $request->grooming_condition,
                'active_promo' => $request->active_promo,
                'oos_products' => $request->oos_products,
                'other_issues' => $request->other_issues,
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
                'status' => $request->is_issue ? 'open_issue' : 'completed',
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
                'note' => 'Report Submitted: ' . ($request->notes ?: 'Visit Inhouse Completed'),
                'metadata' => [
                    'visit_report_id' => $visitReport->id,
                    'visit_location_id' => $visitLocationId,
                    'visit_type' => 'store'
                ]
            ]);

            // 3. Auto-update Check-Out on Attendance for Ratecard
            if (!$employee->is_inhouse) {
                $workDuration = 0;
                if ($attendance->checkin_at) {
                    $workDuration = $now->diffInMinutes(Carbon::parse($attendance->checkin_at));
                }
                $attendance->update([
                    'checkout_at'           => $now->toDateTimeString(),
                    'checkout_log_id'       => $log->id,
                    'work_duration_minutes' => (int) $workDuration,
                ]);
            }

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
        try {
            $employee = $request->user();

            if (!$employee || !($employee instanceof Employee)) {
                return response()->json([
                    'stats' => [
                        'total_masuk' => 0,
                        'plan' => 0,
                        'actual' => 0,
                        'ach' => 0,
                        'unique_store' => 0,
                        'no_out' => 0,
                        'less_than_5_min' => 0
                    ],
                    'period' => ['start' => '', 'end' => ''],
                    'data' => [],
                    'logs_by_date' => (object)[],
                    'today_logs' => []
                ]);
            }

            $startDateStr = $request->query('start_date');
            $endDateStr   = $request->query('end_date');

            if ($startDateStr && $endDateStr) {
                $startDate = Carbon::parse($startDateStr, 'Asia/Jakarta')->startOfDay();
                $endDate   = Carbon::parse($endDateStr, 'Asia/Jakarta')->endOfDay();
            } else {
                $cutoff = ($employee->department && isset($employee->department->cutoff_start_date)) ? (int)$employee->department->cutoff_start_date : 26;
                $now = Carbon::now('Asia/Jakarta');
                
                if ($cutoff == 1) {
                    $startDate = $now->copy()->startOfMonth();
                    $endDate   = $now->copy()->endOfMonth();
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
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('logged_at', [$startDate, $endDate])
                      ->orWhereBetween('logged_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
                })
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
                $meta = is_array($log->metadata) ? $log->metadata : (is_string($log->metadata) ? json_decode($log->metadata, true) : []);
                if (!is_array($meta)) {
                    $meta = [];
                }
                $location = null;

                if (!empty($meta['is_scheduled_location']) && !empty($meta['scheduled_location_name'])) {
                    $location = [
                        'name' => $meta['scheduled_location_name'] . ' (Lokasi Terjadwal)',
                        'address' => ($meta['scheduled_type'] ?? '') === 'meeting' ? 'Jadwal Meeting' : 'Jadwal Visit',
                    ];
                } elseif (isset($meta['visit_location_id'])) {
                    $loc = $allWorkLocations->get($meta['visit_location_id']);
                    $location = $loc ? $loc->toArray() : null;
                    $uniqueStores[$meta['visit_location_id']] = true;
                } elseif (in_array($log->log_type, ['meet_in', 'meet_out'])) {
                    $location = [
                        'name' => ($meta['meeting_title'] ?? 'Meeting') . ' (' . ucfirst($meta['meeting_type'] ?? 'Meeting') . ')',
                        'address' => $meta['location_name'] ?? (($meta['meeting_type'] ?? '') === 'online' ? 'Online Meeting' : '-'),
                    ];
                }

                // For checkin/checkout, find location from schedule's work_location
                if (in_array($log->log_type, ['checkin', 'checkout']) && $location === null) {
                    $attRecord = \App\Models\Attendance::with('employeeSchedule.workLocation.company')
                        ->find($log->attendance_id);
                    if ($attRecord && $attRecord->employeeSchedule && $attRecord->employeeSchedule->workLocation) {
                        $location = $attRecord->employeeSchedule->workLocation->toArray();
                    } else {
                        // Fallback to searching schedule by date
                        $schedule = \App\Models\EmployeeSchedule::with('workLocation.company')
                            ->where('employee_id', $employee->id)
                            ->where('schedule_date', $date)
                            ->first();
                        if ($schedule && $schedule->workLocation) {
                            $location = $schedule->workLocation->toArray();
                            if ($attRecord && !$attRecord->employee_schedule_id) {
                                $attRecord->update(['employee_schedule_id' => $schedule->id]);
                            }
                        } else {
                            $branch = \App\Models\Branch::find($employee->branch_id);
                            $location = $branch ? $branch->toArray() : null;
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
            $scheduleWorkLocation = ($todaySchedule && $todaySchedule->workLocation) ? $todaySchedule->workLocation->toArray() : null;

            $rawLogs = AttendanceLog::where('employee_id', $employee->id)
                ->where(function ($q) use ($todayLocal, $todayStart, $todayEnd) {
                    $q->whereDate('logged_at', $todayLocal)
                      ->orWhereBetween('logged_at', [$todayStart, $todayEnd]);
                })
                ->orderBy('id', 'asc') // Sort asc to track active visit location sequentially
                ->get();
                
            $activeVisitLocation = null;
            
            $todayLogs = $rawLogs->map(function ($log) use (&$activeVisitLocation, $scheduleWorkLocation) {
                $logArray = $log->toArray();
                $meta = is_array($log->metadata) ? $log->metadata : (is_string($log->metadata) ? json_decode($log->metadata, true) : []);
                if (!is_array($meta)) {
                    $meta = [];
                }
                
                // Attach schedule work location for checkin/checkout
                if (in_array($log->log_type, ['checkin', 'checkout'])) {
                    if (!empty($meta['is_scheduled_location']) && !empty($meta['scheduled_location_name'])) {
                        $logArray['visit_location'] = [
                            'name' => $meta['scheduled_location_name'] . ' (Lokasi Terjadwal)',
                            'address' => ($meta['scheduled_type'] ?? '') === 'meeting' ? 'Jadwal Meeting' : 'Jadwal Visit',
                        ];
                    } elseif ($scheduleWorkLocation) {
                        $logArray['visit_location'] = $scheduleWorkLocation;
                    }
                }
                
                // If it's visit_in, update the active visit location
                if ($log->log_type === 'visit_in') {
                    if (isset($meta['visit_location_id'])) {
                        $loc = \App\Models\WorkLocation::find($meta['visit_location_id']);
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

                // Append meeting info for meet_in and meet_out
                if (in_array($log->log_type, ['meet_in', 'meet_out'])) {
                    $logArray['visit_location'] = [
                        'name' => ($meta['meeting_title'] ?? 'Meeting') . ' (' . ucfirst($meta['meeting_type'] ?? 'Meeting') . ')',
                        'address' => $meta['location_name'] ?? (($meta['meeting_type'] ?? '') === 'online' ? 'Online Meeting' : '-'),
                    ];
                }

                $logArray['photo_url'] = $log->photo_path ? url('storage/' . $log->photo_path) : null;
                
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
                'logs_by_date' => !empty($formattedLogs) ? $formattedLogs : (object)[],
                'today_logs' => $todayLogs
            ]);
        } catch (\Throwable $e) {
            Log::error('Attendance History Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat riwayat kehadiran: ' . $e->getMessage(),
                'stats' => [
                    'total_masuk' => 0,
                    'plan' => 0,
                    'actual' => 0,
                    'ach' => 0,
                    'unique_store' => 0,
                    'no_out' => 0,
                    'less_than_5_min' => 0
                ],
                'period' => ['start' => '', 'end' => ''],
                'data' => [],
                'logs_by_date' => (object)[],
                'today_logs' => []
            ], 200);
        }
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
            $employee->loadMissing('position');
            $locations = $itinerary->items
                ->map(fn($item) => $item->workLocation)
                ->filter()
                ->reject(fn($loc) => in_array((int) $loc->id, $visitedLocationIds))
                ->map(function ($loc) use ($employee) {
                    $arr = $loc->toArray();
                    $arr['radius_meter'] = $loc->getEffectiveRadiusForEmployee($employee);
                    return $arr;
                })
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
