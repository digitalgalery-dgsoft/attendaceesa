<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\MeetingParticipant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller
{
    /**
     * Ambil daftar meeting hari ini untuk employee yang sedang login.
     */
    public function today(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();

        // Cari meeting hari ini di mana employee terdaftar sebagai peserta
        $meetings = Meeting::where('meeting_date', $today)
            ->whereHas('participants', function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            })
            ->with(['attendances' => function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            }])
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($meeting) {
                $attendance = $meeting->attendances->first();
                $isInMeeting = $attendance && $attendance->status === 'in_meeting';
                $isCompleted = $attendance && $attendance->status === 'completed';

                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'meeting_date' => $meeting->meeting_date->toDateString(),
                    'start_time' => substr($meeting->start_time, 0, 5),
                    'end_time' => $meeting->end_time ? substr($meeting->end_time, 0, 5) : null,
                    'meeting_type' => $meeting->meeting_type,
                    'meeting_link' => $meeting->meeting_link,
                    'location_name' => $meeting->location_name,
                    'latitude' => $meeting->latitude,
                    'longitude' => $meeting->longitude,
                    'radius_meter' => $meeting->radius_meter ?? 100,
                    'notes' => $meeting->notes,
                    'status' => $meeting->status,
                    'is_in_meeting' => $isInMeeting,
                    'is_completed' => $isCompleted,
                    'my_attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'meet_in_at' => $attendance->meet_in_at ? $attendance->meet_in_at->toIso8601String() : null,
                        'meet_out_at' => $attendance->meet_out_at ? $attendance->meet_out_at->toIso8601String() : null,
                        'duration_seconds' => $attendance->duration_seconds,
                        'report_notes' => $attendance->report_notes,
                        'meet_in_photo' => $attendance->meet_in_photo ? asset('storage/' . $attendance->meet_in_photo) : null,
                        'meet_out_photo' => $attendance->meet_out_photo ? asset('storage/' . $attendance->meet_out_photo) : null,
                        'status' => $attendance->status,
                    ] : null,
                ];
            });

        // Cek apakah ada meeting yang sedang berlangsung (in_meeting)
        $activeMeeting = $meetings->firstWhere('is_in_meeting', true);

        return response()->json([
            'status' => 'success',
            'has_meeting_today' => $meetings->isNotEmpty(),
            'has_active_meeting' => $activeMeeting !== null,
            'active_meeting' => $activeMeeting,
            'data' => $meetings,
        ]);
    }

    /**
     * Proses Meet-In (Presensi Masuk Meeting).
     */
    public function meetIn(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:5120',
        ]);

        $meeting = Meeting::findOrFail($request->meeting_id);

        // Verifikasi peserta
        $isParticipant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('employee_id', $employee->id)
            ->exists();

        if (!$isParticipant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai peserta meeting ini.'
            ], 403);
        }

        // Cek apakah sudah pernah Meet-In
        $existing = MeetingAttendance::where('meeting_id', $meeting->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing && $existing->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah menyelesaikan meeting ini (sudah Meet-Out).'
            ], 400);
        }

        $isInsideGeofence = true;
        $distance = 0;

        // Validasi Radius jika Offline
        if ($meeting->meeting_type === 'offline') {
            if ($meeting->latitude && $meeting->longitude) {
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $meeting->latitude,
                    $meeting->longitude
                );

                $allowedRadius = $meeting->radius_meter ?? 100;
                $isInsideGeofence = ($distance <= $allowedRadius);

                if (!$isInsideGeofence) {
                    $distFormatted = round($distance);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Gagal Meet-In: Anda berada di luar radius lokasi meeting ({$distFormatted}m dari titik lokasi, batas max {$allowedRadius}m)."
                    ], 422);
                }
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('meeting_photos', 'public');
        }

        $now = Carbon::now('Asia/Jakarta');

        DB::beginTransaction();
        try {
            $attendance = MeetingAttendance::updateOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'meet_in_at' => $now,
                    'meet_in_lat' => $request->latitude,
                    'meet_in_lng' => $request->longitude,
                    'meet_in_photo' => $photoPath,
                    'status' => 'in_meeting',
                ]
            );

            // Update participant status
            MeetingParticipant::where('meeting_id', $meeting->id)
                ->where('employee_id', $employee->id)
                ->update(['status' => 'attended']);

            // Dapatkan attendance harian aktif (jika ada) untuk mereferensikan log
            $dailyAttendance = Attendance::where('employee_id', $employee->id)
                ->where('attendance_date', $now->toDateString())
                ->first();

            // Catat ke attendance_logs agar muncul di Aktivitas Hari Ini & Riwayat
            AttendanceLog::create([
                'employee_id' => $employee->id,
                'attendance_id' => $dailyAttendance ? $dailyAttendance->id : null,
                'log_type' => 'meet_in',
                'logged_at' => $now,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'photo_path' => $photoPath,
                'is_inside_geofence' => $isInsideGeofence,
                'distance_from_location_meter' => round($distance),
                'source' => 'android',
                'validation_status' => 'valid',
                'note' => 'Meet-In: ' . $meeting->title,
                'metadata' => [
                    'meeting_id' => $meeting->id,
                    'meeting_title' => $meeting->title,
                    'meeting_type' => $meeting->meeting_type,
                    'location_name' => $meeting->location_name ?? $meeting->meeting_link,
                ],
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Meet-In berhasil dicatat. Selamat mengikuti meeting!',
                'data' => [
                    'meeting_id' => $meeting->id,
                    'attendance_id' => $attendance->id,
                    'meet_in_at' => $now->toIso8601String(),
                    'status' => 'in_meeting',
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Meet-In Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan Meet-In: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses Meet-Out & Kirim Laporan Meeting.
     */
    public function meetOut(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'report_notes' => 'required|string|min:5',
            'photo' => 'nullable|image|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'duration_seconds' => 'nullable|integer',
        ]);

        $meeting = Meeting::findOrFail($request->meeting_id);

        $attendance = MeetingAttendance::where('meeting_id', $meeting->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'in_meeting')
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum melakukan Meet-In untuk meeting ini atau meeting sudah selesai.'
            ], 400);
        }

        $now = Carbon::now('Asia/Jakarta');

        // Hitung durasi jika belum disediakan dari mobile
        $durationSeconds = $request->duration_seconds;
        if (!$durationSeconds && $attendance->meet_in_at) {
            $durationSeconds = $now->diffInSeconds($attendance->meet_in_at);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('meeting_reports', 'public');
        }

        DB::beginTransaction();
        try {
            $attendance->update([
                'meet_out_at' => $now,
                'meet_out_lat' => $request->latitude ?? $attendance->meet_in_lat,
                'meet_out_lng' => $request->longitude ?? $attendance->meet_in_lng,
                'meet_out_photo' => $photoPath,
                'duration_seconds' => $durationSeconds,
                'report_notes' => $request->report_notes,
                'status' => 'completed',
            ]);

            // Dapatkan daily attendance
            $dailyAttendance = Attendance::where('employee_id', $employee->id)
                ->where('attendance_date', $now->toDateString())
                ->first();

            // Catat log meet_out ke attendance_logs
            AttendanceLog::create([
                'employee_id' => $employee->id,
                'attendance_id' => $dailyAttendance ? $dailyAttendance->id : null,
                'log_type' => 'meet_out',
                'logged_at' => $now,
                'latitude' => $request->latitude ?? $attendance->meet_in_lat,
                'longitude' => $request->longitude ?? $attendance->meet_in_lng,
                'photo_path' => $photoPath,
                'is_inside_geofence' => true,
                'distance_from_location_meter' => 0,
                'source' => 'android',
                'validation_status' => 'valid',
                'note' => 'Laporan Selesai: ' . $meeting->title . ' - ' . substr($request->report_notes, 0, 50),
                'metadata' => [
                    'meeting_id' => $meeting->id,
                    'meeting_title' => $meeting->title,
                    'meeting_attendance_id' => $attendance->id,
                    'duration_seconds' => $durationSeconds,
                    'report_notes' => $request->report_notes,
                ],
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan meeting berhasil dikirim dan Meet-Out selesai!',
                'data' => [
                    'meeting_id' => $meeting->id,
                    'attendance_id' => $attendance->id,
                    'meet_out_at' => $now->toIso8601String(),
                    'duration_seconds' => $durationSeconds,
                    'status' => 'completed',
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Meet-Out Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim laporan meeting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil riwayat meeting karyawan.
     */
    public function history(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $attendances = MeetingAttendance::where('employee_id', $employee->id)
            ->with('meeting')
            ->orderBy('meet_in_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $attendances
        ]);
    }

    /**
     * Ambil detail & laporan hasil meeting lengkap beserta list peserta.
     */
    public function show($id, Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $meeting = Meeting::with([
            'participants.employee.designation',
            'participants.employee.department',
            'attendances.employee'
        ])->findOrFail($id);

        $attendancesByEmp = $meeting->attendances->keyBy('employee_id');

        $participantsData = $meeting->participants->map(function ($part) use ($attendancesByEmp) {
            $emp = $part->employee;
            $att = $attendancesByEmp->get($part->employee_id);

            $status = 'not_attended';
            if ($att) {
                $status = $att->status; // 'in_meeting' or 'completed'
            }

            $durationFormatted = '-';
            if ($att && $att->duration_seconds) {
                $hours = floor($att->duration_seconds / 3600);
                $mins = floor(($att->duration_seconds % 3600) / 60);
                $secs = $att->duration_seconds % 60;
                if ($hours > 0) {
                    $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $durationFormatted = sprintf('%02d:%02d', $mins, $secs);
                }
            } elseif ($att && $att->status === 'in_meeting') {
                $durationFormatted = 'Sedang Berlangsung';
            }

            $empPosition = '-';
            if ($emp) {
                if ($emp->designation) {
                    $empPosition = $emp->designation->name;
                } elseif ($emp->position) {
                    $empPosition = $emp->position;
                }
            }

            $empDepartment = ($emp && $emp->department) ? $emp->department->name : '-';

            return [
                'employee_id' => $part->employee_id,
                'name' => $emp ? $emp->name : 'Karyawan',
                'employee_no' => $emp ? ($emp->employee_no ?? $emp->nik) : null,
                'position' => $empPosition,
                'department' => $empDepartment,
                'avatar' => ($emp && $emp->photo) ? asset('storage/' . $emp->photo) : null,
                'status' => $status,
                'meet_in_at' => ($att && $att->meet_in_at) ? $att->meet_in_at->format('H:i:s') : null,
                'meet_out_at' => ($att && $att->meet_out_at) ? $att->meet_out_at->format('H:i:s') : null,
                'meet_in_time_full' => ($att && $att->meet_in_at) ? $att->meet_in_at->format('d M Y, H:i') : null,
                'meet_out_time_full' => ($att && $att->meet_out_at) ? $att->meet_out_at->format('d M Y, H:i') : null,
                'duration_seconds' => $att ? $att->duration_seconds : null,
                'formatted_duration' => $durationFormatted,
                'report_notes' => $att ? $att->report_notes : null,
                'meet_in_photo' => ($att && $att->meet_in_photo) ? asset('storage/' . $att->meet_in_photo) : null,
                'meet_out_photo' => ($att && $att->meet_out_photo) ? asset('storage/' . $att->meet_out_photo) : null,
                'meet_in_lat' => $att ? $att->meet_in_lat : null,
                'meet_in_lng' => $att ? $att->meet_in_lng : null,
                'meet_out_lat' => $att ? $att->meet_out_lat : null,
                'meet_out_lng' => $att ? $att->meet_out_lng : null,
            ];
        });

        $completedCount = $participantsData->where('status', 'completed')->count();
        $inMeetingCount = $participantsData->where('status', 'in_meeting')->count();
        $notAttendedCount = $participantsData->where('status', 'not_attended')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'meeting_date' => $meeting->meeting_date->format('Y-m-d'),
                'meeting_date_formatted' => $meeting->meeting_date->locale('id')->isoFormat('dddd, D MMMM Y'),
                'start_time' => substr($meeting->start_time, 0, 5),
                'end_time' => $meeting->end_time ? substr($meeting->end_time, 0, 5) : null,
                'time_range' => substr($meeting->start_time, 0, 5) . ($meeting->end_time ? ' - ' . substr($meeting->end_time, 0, 5) . ' WIB' : ' WIB'),
                'meeting_type' => $meeting->meeting_type,
                'meeting_link' => $meeting->meeting_link,
                'location_name' => $meeting->location_name,
                'latitude' => $meeting->latitude,
                'longitude' => $meeting->longitude,
                'radius_meter' => $meeting->radius_meter,
                'notes' => $meeting->notes,
                'status' => $meeting->status,
                'stats' => [
                    'total_participants' => $participantsData->count(),
                    'completed_count' => $completedCount,
                    'in_meeting_count' => $inMeetingCount,
                    'not_attended_count' => $notAttendedCount,
                ],
                'participants' => $participantsData->values(),
            ]
        ]);
    }

    /**
     * Helper kalkulasi jarak (Haversine Formula) dalam meter.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
