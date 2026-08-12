<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Notification;
use App\Models\User;

class PermitController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user();
        
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee data not found.',
            ], 404);
        }

        $permits = LeaveRequest::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $permits->map(function ($permit) {
            if ($permit->status === 'approved' && $permit->type === 'cuti') {
                $permit->pdf_url = \Illuminate\Support\Facades\URL::signedRoute('api.permit.download', ['id' => $permit->id]);
            } else {
                $permit->pdf_url = null;
            }
            return $permit;
        });

        return response()->json([
            'status' => 'success',
            'data' => $permits
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user();
        
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee data not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'sub_type' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $today = Carbon::now()->startOfDay();

        // Validasi tidak bisa backdate
        if ($startDate->lt($today)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tanggal mulai tidak boleh di masa lalu (backdate).'
            ], 422);
        }

        // Validasi Cuti Tahunan (H-14)
        if ($request->type === 'cuti' && $request->sub_type === 'cuti_tahunan') {
            $h14 = $today->copy()->addDays(14);
            if ($startDate->lt($h14)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cuti Tahunan harus diajukan minimal H-14.'
                ], 422);
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('photo')) {
            $attachmentPath = $request->file('photo')->store('permits', 'public');
        }

        $permit = LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => $request->type,
            'sub_type' => $request->sub_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
            'head_approval_status' => 'pending',
            'hrd_approval_status' => 'pending',
        ]);

        // Send notification to Admin and Supervisor
        $notifiableUsers = collect();

        // 1. Get supervisor's user account if exists
        if ($employee->supervisor_id) {
            $supervisor = \App\Models\Employee::find($employee->supervisor_id);
            if ($supervisor && $supervisor->user) {
                $notifiableUsers->push($supervisor->user);
            }
        }

        // 2. Get all admin users
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%admin%');
        })->get();
        $notifiableUsers = $notifiableUsers->merge($admins)->unique('id');

        foreach ($notifiableUsers as $user) {
            Notification::make()
                ->title('Pengajuan Permit Baru')
                ->body("{$employee->full_name} telah mengajukan permit baru (" . ucwords(str_replace('_', ' ', $permit->type)) . ").")
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->label('Lihat')
                        ->url(url('/admin/leave-requests/' . $permit->id))
                        ->button(),
                ])
                ->sendToDatabase($user);
        }

        // 3. Send Push Notification via Firebase
        $userIds = $notifiableUsers->pluck('id')->toArray();
        $fcmTokens = \App\Models\Employee::whereIn('user_id', $userIds)
                        ->whereNotNull('fcm_token')
                        ->pluck('fcm_token')
                        ->toArray();
        
        if (!empty($fcmTokens)) {
            $firebase = new \App\Services\FirebaseService();
            $firebase->sendNotification(
                $fcmTokens,
                'Pengajuan Permit Baru',
                "{$employee->full_name} telah mengajukan permit baru (" . ucwords(str_replace('_', ' ', $permit->type)) . ")."
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan izin berhasil dikirim.',
            'data' => $permit
        ]);
    }

    public function downloadPdf(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired signature.');
        }

        $permit = LeaveRequest::with(['employee.position', 'employee.branch', 'employee.supervisor', 'approver'])->findOrFail($id);

        if ($permit->status !== 'approved' || $permit->type !== 'cuti') {
            abort(404, 'Surat Cuti tidak tersedia.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.surat-cuti', ['record' => $permit]);
        $filename = 'Surat-Cuti-' . str_replace(' ', '-', $permit->employee->full_name) . '-' . date('Ymd', strtotime($permit->start_date)) . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
