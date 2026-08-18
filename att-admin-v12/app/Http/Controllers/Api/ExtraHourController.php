<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtraHour;
use App\Models\EmployeeSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExtraHourController extends Controller
{
    /**
     * Get the current active extra hour (overtime) status for the employee.
     */
    public function status(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }

        $employeeTz = $employee->timezone ?? config('app.timezone');
        $now = Carbon::now($employeeTz);
        $date = $now->toDateString();
        
        $activeOvertime = ExtraHour::where('employee_id', $employee->id)
            ->where('date', $date)
            ->whereNull('end_time')
            ->first();

        $canStart = false;
        $message = '';
        
        if ($activeOvertime) {
            $message = 'Lembur sedang berjalan';
        } else {
            $schedule = EmployeeSchedule::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();

            if (!$schedule || !$schedule->schedule_out) {
                $message = 'Jadwal kerja hari ini tidak ditemukan';
            } else {
                $isDriver = false;
                if ($employee->position && (stripos($employee->position->name, 'driver') !== false || stripos($employee->position->name, 'supir') !== false)) {
                    $isDriver = true;
                }

                $scheduleOutTime = Carbon::parse($date . ' ' . $schedule->schedule_out, $employeeTz);
                $minStartTime = $isDriver ? $scheduleOutTime : $scheduleOutTime->copy()->addHour();

                if ($now->lt($minStartTime)) {
                    $message = $isDriver ? 
                        'Baru Bisa Pengajuan Lembur setelah Jam Pulang (' . $schedule->schedule_out . ')' : 
                        'Baru Bisa Pengajuan Lembur 1 Jam setelah Jam Pulang (' . $minStartTime->format('H:i') . ')';
                }
                
                // TEMPORARY FIX: Always allow starting overtime to debug if frontend is blocking it
                $canStart = true;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("Overtime Result for Employee {$employee->id}:", [
            'activeOvertime' => $activeOvertime ? true : false,
            'message' => $message,
            'canStart' => $canStart
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_running' => $activeOvertime !== null,
                'overtime' => $activeOvertime,
                'can_start' => $canStart,
                'message' => $message
            ]
        ]);
    }

    /**
     * Start a new extra hour (overtime) session.
     */
    public function start(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'notes' => 'required|string'
        ]);

        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }

        $employeeTz = $employee->timezone ?? config('app.timezone');
        $now = Carbon::now($employeeTz);
        $date = $now->toDateString();

        // Check if there's already a running overtime
        $activeOvertime = ExtraHour::where('employee_id', $employee->id)
            ->where('date', $date)
            ->whereNull('end_time')
            ->first();

        if ($activeOvertime) {
            return response()->json(['status' => 'error', 'message' => 'Lembur sudah berjalan'], 400);
        }

        // Validate 1 hour after schedule_out logic
        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if (!$schedule || !$schedule->schedule_out) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal kerja hari ini tidak ditemukan'], 400);
        }

        $isDriver = false;
        if ($employee->position && (stripos($employee->position->name, 'driver') !== false || stripos($employee->position->name, 'supir') !== false)) {
            $isDriver = true;
        }

        $scheduleOutTime = Carbon::parse($date . ' ' . $schedule->schedule_out, $employeeTz);
        $minStartTime = $isDriver ? $scheduleOutTime : $scheduleOutTime->copy()->addHour();

        $inputStartTime = Carbon::parse($date . ' ' . $request->start_time, $employeeTz);

        if ($inputStartTime->lt($minStartTime)) {
            $message = $isDriver ? 
                'Anda hanya dapat mulai lembur setelah jam selesai kantor (' . $schedule->schedule_out . ')' : 
                'Anda hanya dapat mulai lembur 1 jam setelah jam selesai kantor (' . $minStartTime->format('H:i') . ')';
            return response()->json(['status' => 'error', 'message' => $message], 400);
        }

        $extraHour = ExtraHour::create([
            'employee_id' => $employee->id,
            'date' => $date,
            'start_time' => $request->start_time,
            'notes' => $request->notes,
            'status' => 'submitted', // Will be pending approval
            'head_approval_status' => 'pending',
            'hrd_approval_status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lembur berhasil dimulai',
            'data' => $extraHour
        ]);
    }

    /**
     * Finish the current active extra hour (overtime) session.
     */
    public function finish(Request $request)
    {
        $request->validate([
            'end_time' => 'required|date_format:H:i',
        ]);

        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }

        $employeeTz = $employee->timezone ?? config('app.timezone');
        $now = Carbon::now($employeeTz);
        $date = $now->toDateString();
        
        $activeOvertime = ExtraHour::where('employee_id', $employee->id)
            ->where('date', $date)
            ->whereNull('end_time')
            ->first();

        if (!$activeOvertime) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada sesi lembur yang sedang berjalan'], 404);
        }

        $startTime = Carbon::parse($activeOvertime->date->format('Y-m-d') . ' ' . $activeOvertime->start_time, $employeeTz);
        
        // Handle cross day
        $inputEndTime = Carbon::parse($date . ' ' . $request->end_time, $employeeTz);
        $crossDay = false;
        
        if ($inputEndTime->lt($startTime)) {
            // Probably crossed midnight
            $inputEndTime->addDay();
            $crossDay = true;
        }

        $durationMinutes = $startTime->diffInMinutes($inputEndTime);

        $activeOvertime->update([
            'end_time' => $request->end_time,
            'duration' => $durationMinutes,
            'cross_day' => $crossDay,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lembur berhasil diselesaikan',
            'data' => $activeOvertime
        ]);
    }
}
