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
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        $employeeId = $user->id;
        $today = Carbon::today()->format('Y-m-d');
        
        // Cari absensi hari ini yang aktif
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->first();

        // Validasi: Jika belum check-in atau sudah check-out hari ini, hentikan tracking dan jangan simpan koordinat
        if (!$attendance || $attendance->checkout_at !== null) {
            return response()->json([
                'status' => 'stopped',
                'is_tracking_active' => false,
                'message' => ($attendance && $attendance->checkout_at !== null)
                    ? 'Karyawan sudah check-out. Pelacakan dinonaktifkan.'
                    : 'Karyawan belum melakukan check-in. Pelacakan tidak aktif.'
            ], 200);
        }
        
        $timezone = 'Asia/Jakarta';
        if ($user->branch && $user->branch->timezone) {
            $timezone = $user->branch->timezone;
        } elseif ($user->company && $user->company->timezone) {
            $timezone = $user->company->timezone;
        }

        $createdAt = $request->timestamp 
            ? Carbon::parse($request->timestamp)->timezone($timezone)
            : Carbon::now($timezone);

        TrackingHistory::create([
            'employee_id' => $employeeId,
            'attendance_id' => $attendance ? $attendance->id : null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location recorded'
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $date = $request->query('date', Carbon::today()->format('Y-m-d'));

        $timezone = 'Asia/Jakarta';
        if ($user->branch && $user->branch->timezone) {
            $timezone = $user->branch->timezone;
        } elseif ($user->company && $user->company->timezone) {
            $timezone = $user->company->timezone;
        }

        $histories = TrackingHistory::where('employee_id', $user->id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at'])
            ->map(function ($item) use ($timezone) {
                $time = \Carbon\Carbon::parse($item->created_at)->timezone($timezone);

                return [
                    'latitude'   => (float) $item->latitude,
                    'longitude'  => (float) $item->longitude,
                    'created_at' => $time->format('H:i:s'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'date'   => $date,
            'data'   => $histories,
        ]);
    }
}
