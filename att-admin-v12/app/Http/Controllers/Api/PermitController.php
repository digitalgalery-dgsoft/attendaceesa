<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\EmployeeLeaveQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Notification;
use App\Models\User;

class PermitController extends Controller
{
    /**
     * Definisi jenis Cuti Peraturan beserta batas hari maksimalnya.
     */
    const CUTI_PERATURAN_TYPES = [
        'cuti_menikah'              => ['label' => 'Cuti Menikah (Pernikahan Sendiri)',          'max_days' => 3],
        'cuti_menikahkan'           => ['label' => 'Cuti Menikahkan / Khitan / Baptis Anak',     'max_days' => 2],
        'cuti_istri_melahirkan'     => ['label' => 'Cuti Istri Melahirkan / Keguguran',          'max_days' => 2],
        'cuti_kematian_inti'        => ['label' => 'Cuti Kematian (Suami/Istri/Anak/Ortu/Mertua)', 'max_days' => 2],
        'cuti_kematian_serumah'     => ['label' => 'Cuti Kematian (Anggota Keluarga Serumah)',   'max_days' => 1],
        'cuti_melahirkan'           => ['label' => 'Cuti Melahirkan (Prinsiple)',                 'max_days' => 90],
    ];

    /**
     * Get leave quota for the logged-in employee.
     */
    public function leaveQuota(Request $request)
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }

        $joinDate = $employee->join_date ? Carbon::parse($employee->join_date) : null;
        $now      = Carbon::now();
        $year     = $now->year;

        // Check if employee is eligible (>= 1 year of service)
        $eligible = $joinDate && $joinDate->diffInYears($now) >= 1;

        if (!$eligible) {
            return response()->json([
                'status'   => 'success',
                'eligible' => false,
                'message'  => 'Belum Berhak Cuti Tahunan',
                'quota'    => null,
            ]);
        }

        // Get or create quota record for this year
        $quotaRecord = EmployeeLeaveQuota::firstOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            ['total_quota' => 12]
        );

        $totalQuota = $quotaRecord->total_quota;

        // Used = approved annual leave requests this year
        $used = LeaveRequest::where('employee_id', $employee->id)
            ->where('type', 'cuti')
            ->where('sub_type', 'cuti_tahunan')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->get()
            ->sum(function ($lr) {
                return $lr->start_date->diffInDays($lr->end_date) + 1;
            });

        return response()->json([
            'status'      => 'success',
            'eligible'    => true,
            'quota'       => [
                'year'      => $year,
                'total'     => $totalQuota,
                'used'      => $used,
                'remaining' => max(0, $totalQuota - $used),
            ],
        ]);
    }

    public function cutiPeraturanTypes(Request $request)
    {
        $types = collect(self::CUTI_PERATURAN_TYPES)->map(function ($v, $k) {
            return ['key' => $k, 'label' => $v['label'], 'max_days' => $v['max_days']];
        })->values();

        return response()->json(['status' => 'success', 'data' => $types]);
    }

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
            'type'                => 'required|string',
            'sub_type'            => 'nullable|string',
            'cuti_peraturan_type' => 'nullable|string',
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'notes'               => 'nullable|string',
            'photo'               => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->startOfDay();
        $today     = Carbon::now()->startOfDay();

        // Validasi tidak bisa backdate
        if ($startDate->lt($today)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tanggal mulai tidak boleh di masa lalu (backdate).'
            ], 422);
        }

        // ─── VALIDASI CUTI TAHUNAN ────────────────────────────────────────────
        if ($request->type === 'cuti' && $request->sub_type === 'cuti_tahunan') {
            // 1. Harus sudah minimal 1 tahun kerja
            $joinDate = $employee->join_date ? Carbon::parse($employee->join_date) : null;
            if (!$joinDate || $joinDate->diffInYears(Carbon::now()) < 1) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cuti Tahunan hanya dapat diambil oleh karyawan yang telah bekerja minimal 1 tahun.'
                ], 422);
            }

            // 2. Pengajuan minimal H-14
            $h14 = $today->copy()->addDays(14);
            if ($startDate->lt($h14)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cuti Tahunan harus diajukan minimal H-14.'
                ], 422);
            }

            // 3. Cek kuota sisa
            $year       = Carbon::now()->year;
            $quotaRecord = EmployeeLeaveQuota::firstOrCreate(
                ['employee_id' => $employee->id, 'year' => $year],
                ['total_quota' => 12]
            );
            $used = LeaveRequest::where('employee_id', $employee->id)
                ->where('type', 'cuti')
                ->where('sub_type', 'cuti_tahunan')
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->get()
                ->sum(fn($lr) => $lr->start_date->diffInDays($lr->end_date) + 1);

            $requested = $startDate->diffInDays($endDate) + 1;
            $remaining = $quotaRecord->total_quota - $used;

            if ($requested > $remaining) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Kuota cuti tahunan tidak mencukupi. Sisa kuota Anda: {$remaining} hari, pengajuan: {$requested} hari."
                ], 422);
            }
        }

        // ─── VALIDASI CUTI PERATURAN ──────────────────────────────────────────
        if ($request->type === 'cuti' && $request->sub_type === 'cuti_peraturan') {
            $cpType = $request->cuti_peraturan_type;
            if (!$cpType || !isset(self::CUTI_PERATURAN_TYPES[$cpType])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Jenis Cuti Peraturan wajib dipilih.'
                ], 422);
            }

            $maxDays   = self::CUTI_PERATURAN_TYPES[$cpType]['max_days'];
            $requested = $startDate->diffInDays($endDate) + 1;

            if ($requested > $maxDays) {
                $label = self::CUTI_PERATURAN_TYPES[$cpType]['label'];
                return response()->json([
                    'status'  => 'error',
                    'message' => "{$label} maksimal {$maxDays} hari kerja. Pengajuan Anda: {$requested} hari."
                ], 422);
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('photo')) {
            $attachmentPath = $request->file('photo')->store('permits', 'public');
        }

        $permit = LeaveRequest::create([
            'employee_id'         => $employee->id,
            'type'                => $request->type,
            'sub_type'            => $request->sub_type,
            'cuti_peraturan_type' => $request->cuti_peraturan_type,
            'start_date'          => $request->start_date,
            'end_date'            => $request->end_date,
            'notes'               => $request->notes,
            'attachment_path'     => $attachmentPath,
            'status'              => 'pending',
            'head_approval_status' => 'pending',
            'hrd_approval_status'  => 'pending',
        ]);

        // Send notification to Admin and Supervisor
        $notifiableUsers = collect();

        if ($employee->supervisor_id) {
            $supervisor = \App\Models\Employee::find($employee->supervisor_id);
            if ($supervisor && $supervisor->user) {
                $notifiableUsers->push($supervisor->user);
            }
        }

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

        $userIds   = $notifiableUsers->pluck('id')->toArray();
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
            'status'  => 'success',
            'message' => 'Pengajuan izin berhasil dikirim.',
            'data'    => $permit
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
