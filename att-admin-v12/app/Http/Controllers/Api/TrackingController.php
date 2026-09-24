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

        // Tentukan timezone karyawan
        $timezone = 'Asia/Jakarta';
        if ($user->branch && $user->branch->timezone) {
            $timezone = $user->branch->timezone;
        } elseif ($user->company && $user->company->timezone) {
            $timezone = $user->company->timezone;
        }

        $today = Carbon::today($timezone)->toDateString();
        $now   = Carbon::now($timezone);

        // 1. Cari absensi yang sedang aktif (sudah check-in dan belum checkout)
        // Cari dalam rentang 24 jam terakhir untuk mengakomodasi shift malam / pergantian tanggal kalender
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereNull('checkout_at')
            ->where(function($q) use ($today, $now) {
                $q->where('attendance_date', $today)
                  ->orWhere('checkin_at', '>=', $now->copy()->subHours(24));
            })
            ->latest('id')
            ->first();

        // 2. Validasi status:
        if (!$attendance) {
            // Cek apakah karyawan memang sudah check-out hari ini
            $hasCheckedOut = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $today)
                ->whereNotNull('checkout_at')
                ->latest('id')
                ->first();

            if ($hasCheckedOut) {
                // Karyawan memang sudah sah check-out hari ini: beritahu client untuk stop tracking
                return response()->json([
                    'status' => 'stopped',
                    'is_tracking_active' => false,
                    'message' => 'Karyawan sudah check-out. Pelacakan dinonaktifkan.'
                ], 200);
            }

            // Karyawan belum check-in atau sedang proses transisi check-in:
            // JANGAN kirim 'status: stopped' atau 'is_tracking_active: false' agar background service di HP tidak dibunuh permanen!
            return response()->json([
                'status' => 'pending',
                'is_tracking_active' => true,
                'message' => 'Belum ada sesi presensi aktif untuk merekam lokasi.'
            ], 200);
        }

        // 3. Simpan titik koordinat pelacakan
        $createdAt = $request->timestamp 
            ? Carbon::parse($request->timestamp)->timezone($timezone)
            : $now;

        TrackingHistory::create([
            'employee_id' => $employeeId,
            'attendance_id' => $attendance->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return response()->json([
            'status' => 'success',
            'is_tracking_active' => true,
            'message' => 'Location recorded'
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $timezone = 'Asia/Jakarta';
        if ($user->branch && $user->branch->timezone) {
            $timezone = $user->branch->timezone;
        } elseif ($user->company && $user->company->timezone) {
            $timezone = $user->company->timezone;
        }

        $date = $request->query('date', Carbon::today($timezone)->format('Y-m-d'));

        $histories = TrackingHistory::where('employee_id', $user->id)
            ->where(function($q) use ($date) {
                $q->whereDate('created_at', $date)
                  ->orWhereHas('attendance', function($attQ) use ($date) {
                      $attQ->where('attendance_date', $date);
                  });
            })
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at'])
            ->map(function ($item) use ($timezone) {
                $time = Carbon::parse($item->created_at)->timezone($timezone);

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
