<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BapRequest;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Itinerary;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BapApiController extends Controller
{
    /**
     * Ambil daftar tanggal jadwal yang belum ada presensi check-in (30 hari terakhir s/d hari ini).
     */
    public function eligibleDates(Request $request): JsonResponse
    {
        $employee = $request->user();
        if (!$employee || !($employee instanceof Employee)) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        $today = Carbon::today('Asia/Jakarta');
        $startDate = $today->copy()->subDays(30)->toDateString();
        $endDate = $today->toDateString();

        // 1. Ambil seluruh jadwal reguler aktif dalam 30 hari
        $schedules = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->whereNotIn('schedule_type', ['dayoff', 'holiday'])
            ->with(['workLocation', 'shift'])
            ->orderBy('schedule_date', 'desc')
            ->get();

        // 2. Ambil seluruh jadwal visit (itinerary) dalam 30 hari
        $itineraries = Itinerary::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['items.workLocation'])
            ->orderBy('date', 'desc')
            ->get();

        // 3. Ambil data attendance yang sudah memiliki check-in
        $attendedDates = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereNotNull('checkin_at')
            ->pluck('attendance_date')
            ->map(fn ($d) => is_string($d) ? Carbon::parse($d)->toDateString() : $d->toDateString())
            ->toArray();

        // 4. Ambil BAP yang berstatus pending atau approved (agar tidak bisa input dobel)
        $existingBaps = BapRequest::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'approved'])
            ->get()
            ->keyBy(fn ($b) => is_string($b->date) ? Carbon::parse($b->date)->toDateString() : $b->date->toDateString());

        $eligible = [];
        $processedDates = [];

        // Evaluasi Roster Schedules
        foreach ($schedules as $sched) {
            $dateStr = is_string($sched->schedule_date) ? Carbon::parse($sched->schedule_date)->toDateString() : $sched->schedule_date->toDateString();
            
            // Skip jika sudah absen hadir
            if (in_array($dateStr, $attendedDates)) {
                continue;
            }

            // Skip jika sudah ada BAP pending/approved
            if (isset($existingBaps[$dateStr])) {
                continue;
            }

            if (in_array($dateStr, $processedDates)) {
                continue;
            }

            $dateCarbon = Carbon::parse($dateStr)->locale('id');
            $shiftName = $sched->shift ? $sched->shift->name : 'Shift Reguler';
            $startTime = $sched->shift ? substr($sched->shift->start_time, 0, 5) : '08:00';
            $endTime   = $sched->shift ? substr($sched->shift->end_time, 0, 5) : '17:00';

            $locName = $sched->workLocation?->name ?? ($employee->branch?->name ?? 'Kantor / Lokasi Kerja');

            $eligible[] = [
                'date'               => $dateStr,
                'formatted_date'     => $dateCarbon->translatedFormat('l, d F Y'),
                'schedule_type'      => 'Jadwal Roster',
                'schedule_id'        => $sched->id,
                'work_location_id'   => $sched->work_location_id,
                'work_location_name' => $locName,
                'shift_name'         => $shiftName,
                'default_checkin'    => $startTime,
                'default_checkout'   => $endTime,
            ];

            $processedDates[] = $dateStr;
        }

        // Evaluasi Itineraries (Visit Schedule)
        foreach ($itineraries as $itin) {
            $dateStr = is_string($itin->date) ? Carbon::parse($itin->date)->toDateString() : $itin->date->toDateString();

            if (in_array($dateStr, $attendedDates) || isset($existingBaps[$dateStr]) || in_array($dateStr, $processedDates)) {
                continue;
            }

            $firstItem = $itin->items->first();
            $locName = $firstItem?->workLocation?->name ?? 'Jadwal Kunjungan Lapangan';

            $dateCarbon = Carbon::parse($dateStr)->locale('id');

            $eligible[] = [
                'date'               => $dateStr,
                'formatted_date'     => $dateCarbon->translatedFormat('l, d F Y'),
                'schedule_type'      => 'Visit Schedule',
                'schedule_id'        => null,
                'work_location_id'   => $firstItem?->work_location_id,
                'work_location_name' => $locName,
                'shift_name'         => 'Jadwal Kunjungan (' . $itin->items->count() . ' Lokasi)',
                'default_checkin'    => '08:00',
                'default_checkout'   => '17:00',
            ];

            $processedDates[] = $dateStr;
        }

        // Urutkan dari tanggal terbaru ke terlama
        usort($eligible, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar tanggal eligible BAP berhasil dimuat.',
            'count'   => count($eligible),
            'data'    => $eligible,
        ]);
    }

    /**
     * Simpan pengajuan BAP baru dari aplikasi mobile.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date'              => 'required|date',
            'checkin_time'      => 'required|string',
            'checkout_time'     => 'nullable|string',
            'issue_category'    => 'required|string|in:app_error,gps_network,device_issue,server_down,other',
            'reason'            => 'required|string|min:5',
            'evidence'          => 'required|image|max:10240', // Max 10 MB
            'work_location_id'  => 'nullable|integer',
            'schedule_id'       => 'nullable|integer',
        ]);

        $employee = $request->user();
        if (!$employee || !($employee instanceof Employee)) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        $dateStr = Carbon::parse($request->date)->toDateString();

        // 1. Cek apakah sudah ada BAP pending atau approved di tanggal yang sama
        $existingBap = BapRequest::where('employee_id', $employee->id)
            ->where('date', $dateStr)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingBap) {
            $statusText = $existingBap->status === 'approved' ? 'sudah disetujui' : 'masih menunggu verifikasi';
            return response()->json([
                'status'  => 'error',
                'message' => "Pengajuan BAP untuk tanggal {$dateStr} {$statusText}. Anda tidak dapat mengajukan BAP ganda pada tanggal yang sama."
            ], 422);
        }

        // 2. Cek apakah sudah pernah check-in normal pada tanggal tersebut
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $dateStr)
            ->whereNotNull('checkin_at')
            ->first();

        if ($existingAttendance && !$existingAttendance->is_manual_correction) {
            return response()->json([
                'status'  => 'error',
                'message' => "Anda tercatat sudah melakukan absensi normal pada tanggal {$dateStr}. Pengajuan BAP tidak diperlukan."
            ], 422);
        }

        // 3. Simpan file bukti screenshot
        $path = $request->file('evidence')->store('bap_evidence', 'public');

        // 4. Resolve Work Location & Schedule
        $schedule = null;
        if ($request->filled('schedule_id')) {
            $schedule = EmployeeSchedule::find($request->schedule_id);
        }
        if (!$schedule) {
            $schedule = EmployeeSchedule::where('employee_id', $employee->id)
                ->where('schedule_date', $dateStr)
                ->first();
        }

        $workLocId = $request->work_location_id ?: ($schedule?->work_location_id ?? null);
        if (!$workLocId) {
            $firstLoc = WorkLocation::where('principal_id', $employee->principal_id)->first();
            $workLocId = $firstLoc?->id;
        }

        $bap = BapRequest::create([
            'employee_id'          => $employee->id,
            'principal_id'         => $employee->principal_id,
            'company_id'           => $employee->company_id,
            'branch_id'            => $employee->branch_id,
            'employee_schedule_id' => $schedule?->id,
            'work_location_id'     => $workLocId,
            'date'                 => $dateStr,
            'checkin_time'         => trim($request->checkin_time),
            'checkout_time'        => $request->filled('checkout_time') ? trim($request->checkout_time) : null,
            'type'                 => 'checkin',
            'issue_category'       => $request->issue_category,
            'reason'               => $request->reason,
            'evidence_path'        => $path,
            'status'               => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengajuan BAP berhasil dikirim! Menunggu verifikasi dari Administrator / HR.',
            'data'    => [
                'id'                   => $bap->id,
                'date'                 => $bap->date->toDateString(),
                'formatted_date'       => Carbon::parse($bap->date)->locale('id')->translatedFormat('l, d F Y'),
                'checkin_time'         => $bap->checkin_time,
                'checkout_time'        => $bap->checkout_time,
                'issue_category'       => $bap->issue_category,
                'issue_category_label' => $bap->issue_category_label,
                'reason'               => $bap->reason,
                'evidence_url'         => $bap->evidence_url,
                'status'               => $bap->status,
                'created_at'           => $bap->created_at->toDateTimeString(),
            ],
        ], 201);
    }

    /**
     * Riwayat pengajuan BAP milik karyawan.
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $request->user();
        if (!$employee || !($employee instanceof Employee)) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        $baps = BapRequest::where('employee_id', $employee->id)
            ->with(['workLocation', 'approver'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($bap) {
                return [
                    'id'                   => $bap->id,
                    'date'                 => is_string($bap->date) ? $bap->date : $bap->date->toDateString(),
                    'formatted_date'       => Carbon::parse($bap->date)->locale('id')->translatedFormat('l, d F Y'),
                    'checkin_time'         => $bap->checkin_time,
                    'checkout_time'        => $bap->checkout_time,
                    'work_location_name'   => $bap->workLocation?->name ?? 'Lokasi Kerja',
                    'issue_category'       => $bap->issue_category,
                    'issue_category_label' => $bap->issue_category_label,
                    'reason'               => $bap->reason,
                    'evidence_url'         => $bap->evidence_url,
                    'status'               => $bap->status,
                    'status_label'         => match ($bap->status) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default    => 'Menunggu Verifikasi',
                    },
                    'status_color'         => match ($bap->status) {
                        'approved' => 'green',
                        'rejected' => 'red',
                        default    => 'orange',
                    },
                    'rejection_reason'     => $bap->rejection_reason,
                    'approved_by_name'     => $bap->approver?->name,
                    'approved_at'          => $bap->approved_at ? $bap->approved_at->format('d M Y, H:i') : null,
                    'created_at'           => $bap->created_at ? $bap->created_at->format('d M Y, H:i') : null,
                ];
            });

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat pengajuan BAP berhasil dimuat.',
            'count'   => $baps->count(),
            'data'    => $baps,
        ]);
    }
}
