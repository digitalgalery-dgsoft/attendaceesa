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
        
        // Cari absensi hari ini yang belum checkout (opsional, tapi berguna untuk grouping history per hari)
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->first();

        // Kita tetap rekam history meskipun belum check-in jika service nyala, 
        // tapi idealnya service dimatikan kalau belum check-in.
        
        // Simpan selalu dalam UTC. history() akan konversi ke WIB saat ditampilkan.
        // Sebelumnya: store() konversi ke WIB → tersimpan "07:38", lalu history() konversi lagi → tampil "14:38" (SALAH).
        $createdAt = $request->timestamp 
            ? Carbon::parse($request->timestamp)->utc()  // parse dengan timezone dari string (Z/+07:00), simpan UTC
            : now()->utc();

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

        $histories = TrackingHistory::where('employee_id', $user->id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at'])
            ->map(function ($item) {
                // created_at tersimpan dalam UTC di DB.
                // Konversi sekali ke Asia/Jakarta untuk ditampilkan.
                $time = \Carbon\Carbon::parse($item->created_at, 'UTC')->setTimezone('Asia/Jakarta');

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
