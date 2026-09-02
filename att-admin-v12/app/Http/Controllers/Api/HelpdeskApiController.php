<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\Employee;
use App\Models\User;
use App\Events\MessageSent;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HelpdeskApiController extends Controller
{
    /**
     * Generate secure helpdesk session token for an employee ID.
     */
    protected function generateSessionToken(int $employeeId): string
    {
        $secret = config('app.key', 'esa_helpdesk_secret_key_2026');
        return hash_hmac('sha256', "helpdesk_employee_{$employeeId}", $secret);
    }

    /**
     * Verify the provided session token.
     */
    protected function verifySessionToken(int $employeeId, string $token): bool
    {
        $expected = $this->generateSessionToken($employeeId);
        return hash_equals($expected, $token);
    }

    /**
     * Check employee by NIK / Employee No / Email before opening chat.
     */
    public function checkNik(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $nik = trim($request->nik);

        $employee = Employee::where(function ($q) use ($nik) {
            $q->where('employee_no', $nik)
              ->orWhereRaw('LOWER(employee_no) = ?', [strtolower($nik)])
              ->orWhereRaw('LOWER(email) = ?', [strtolower($nik)]);
        })
        ->where('is_active', true)
        ->with(['company', 'principal', 'branch', 'department', 'position'])
        ->orderByDesc('id')
        ->first();

        if (!$employee) {
            // Cek ke peer servers (AKP / ATK)
            $peers = \App\Services\SmartGatewayRelayService::getPeerServers();
            foreach ($peers as $serverInfo) {
                foreach ($serverInfo['urls'] as $targetUrl) {
                    if (empty($targetUrl)) continue;
                    try {
                        $resp = \Illuminate\Support\Facades\Http::timeout(3)->withoutVerifying()->post(rtrim($targetUrl, '/') . '/api/helpdesk/check-nik', [
                            'nik' => $nik
                        ]);
                        if ($resp->successful()) {
                            return response()->json($resp->json(), $resp->status());
                        }
                    } catch (\Throwable $e) {}
                }
            }

            $inactive = Employee::where(function ($q) use ($nik) {
                $q->where('employee_no', $nik)
                  ->orWhereRaw('LOWER(employee_no) = ?', [strtolower($nik)])
                  ->orWhereRaw('LOWER(email) = ?', [strtolower($nik)]);
            })->first();

            if ($inactive) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun karyawan ' . ($inactive->name ?? $inactive->full_name) . ' (NIK: ' . $nik . ') saat ini berstatus NON-AKTIF. Silakan hubungi HR / Admin ESA untuk proses aktivasi.',
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Data Karyawan dengan NIK / Akun "' . $nik . '" tidak ditemukan. Silakan periksa kembali nomor NIK Anda.',
            ], 404);
        }

        $sessionToken = $this->generateSessionToken($employee->id);

        // Mask email for privacy: e.g. j***@domain.com
        $maskedEmail = null;
        if (!empty($employee->email)) {
            $parts = explode('@', $employee->email);
            if (count($parts) === 2) {
                $namePart = $parts[0];
                $domainPart = $parts[1];
                $maskedName = strlen($namePart) > 2 
                    ? substr($namePart, 0, 1) . str_repeat('*', strlen($namePart) - 2) . substr($namePart, -1)
                    : substr($namePart, 0, 1) . '*';
                $maskedEmail = "{$maskedName}@{$domainPart}";
            } else {
                $maskedEmail = $employee->email;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data Karyawan ditemukan.',
            'data' => [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'employee_no' => $employee->employee_no,
                'position_name' => $employee->position->name ?? '-',
                'principal_name' => $employee->principal->name ?? ($employee->company->name ?? '-'),
                'branch_name' => $employee->branch->name ?? '-',
                'department_name' => $employee->department->name ?? '-',
                'masked_email' => $maskedEmail,
                'has_device_bound' => !empty($employee->device_id),
                'device_name' => $employee->device_name ?: (!empty($employee->device_id) ? 'Perangkat Terikat' : 'Belum Ada Perangkat'),
                'session_token' => $sessionToken,
            ],
        ]);
    }

    /**
     * Initiate or open Helpdesk Live Chat for an Employee.
     */
    public function initiateChat(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'session_token' => 'required|string',
            'issue_type' => 'required|string|in:forgot_password,unlock_device,other',
            'description' => 'nullable|string|max:1000',
        ]);

        $employeeId = (int) $request->employee_id;

        if (!$this->verifySessionToken($employeeId, $request->session_token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid atau telah kedaluwarsa. Silakan periksa NIK kembali.',
            ], 403);
        }

        $employee = Employee::with(['company', 'principal', 'branch', 'position'])->find($employeeId);
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $conversation = Conversation::firstOrCreate([
            'employee_id' => $employeeId,
        ]);

        $issueLabel = match ($request->issue_type) {
            'unlock_device' => '📱 Unlock Device (Ganti HP / Reset ID)',
            'forgot_password' => '🔑 Lupa Password (Reset Kata Sandi)',
            default => '❓ Kendala Login / Bantuan Teknis',
        };

        $employeeName = $employee->name;
        $nik = $employee->employee_no;
        $principal = $employee->principal->name ?? ($employee->company->name ?? '-');
        $branch = $employee->branch->name ?? '-';
        $userDesc = trim($request->description ?: 'Mohon bantuan penanganan dari tim IT / Admin.');

        $ticketMessage = "🎫 [TIKET BANTUAN KARYAWAN]\n"
            . "👤 Nama: {$employeeName} (NIK: {$nik})\n"
            . "🏢 Prinsiple: {$principal} | Cabang: {$branch}\n"
            . "⚠️ Kasus: {$issueLabel}\n"
            . "📝 Keterangan: {$userDesc}";

        // Save Ticket Message
        $message = $conversation->messages()->create([
            'sender_type' => 'employee',
            'sender_id' => $employeeId,
            'message' => $ticketMessage,
            'is_read' => false,
        ]);

        // Broadcast event
        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            Log::error('Helpdesk chat broadcast error: ' . $e->getMessage());
        }

        // Notify Admins synchronously to Filament Database Notifications & Live Toast
        $this->notifyAdmins(
            "💬 Tiket Bantuan: {$employeeName} ({$nik})",
            "Kasus: {$issueLabel}\n\"" . Str::limit($userDesc, 70) . "\"",
            'warning'
        );

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Percakapan bantuan berhasil dimulai.',
            'data' => [
                'conversation_id' => $conversation->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_no' => $employee->employee_no,
                'has_device_bound' => !empty($employee->device_id),
                'session_token' => $request->session_token,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Fetch conversation messages for unauthenticated helpdesk session.
     */
    public function getMessages(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'session_token' => 'required|string',
        ]);

        $employeeId = (int) $request->employee_id;

        if (!$this->verifySessionToken($employeeId, $request->session_token)) {
            return response()->json(['status' => 'error', 'message' => 'Sesi tidak valid.'], 403);
        }

        $employee = Employee::find($employeeId);
        $conversation = Conversation::firstOrCreate(['employee_id' => $employeeId]);
        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
                'has_device_bound' => !empty($employee?->device_id),
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Send message from employee in helpdesk session.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'session_token' => 'required|string',
            'message' => 'required|string|max:2000',
        ]);

        $employeeId = (int) $request->employee_id;

        if (!$this->verifySessionToken($employeeId, $request->session_token)) {
            return response()->json(['status' => 'error', 'message' => 'Sesi tidak valid.'], 403);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $conversation = Conversation::firstOrCreate(['employee_id' => $employeeId]);

        $message = $conversation->messages()->create([
            'sender_type' => 'employee',
            'sender_id' => $employeeId,
            'message' => $request->message,
            'is_read' => false,
        ]);

        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            Log::error('Helpdesk chat broadcast error: ' . $e->getMessage());
        }

        // Notify Admins synchronously to Filament Database Notifications & Live Toast
        $employeeName = $employee->name ?? 'Karyawan';
        $this->notifyAdmins(
            "💬 Pesan bantuan dari {$employeeName}",
            Str::limit($request->message, 70),
            'info'
        );

        return response()->json([
            'status' => 'success',
            'data' => $message,
        ]);
    }

    /**
     * Mark messages as read by employee.
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'session_token' => 'required|string',
        ]);

        $employeeId = (int) $request->employee_id;

        if (!$this->verifySessionToken($employeeId, $request->session_token)) {
            return response()->json(['status' => 'error', 'message' => 'Sesi tidak valid.'], 403);
        }

        $conversation = Conversation::where('employee_id', $employeeId)->first();
        if ($conversation) {
            $conversation->messages()
                ->where('sender_type', 'admin')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Dispatch database notification synchronously into notifications table.
     */
    protected function notifyAdmins(string $title, string $body, string $status = 'info')
    {
        try {
            $admins = User::whereDoesntHave('principals')->get();
            if ($admins->isEmpty()) {
                $admins = User::all();
            }

            foreach ($admins as $admin) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'Filament\Notifications\DatabaseNotification',
                    'notifiable_type' => get_class($admin),
                    'notifiable_id' => $admin->id,
                    'data' => json_encode([
                        'id' => (string) Str::uuid(),
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'heroicon-o-chat-bubble-left-right',
                        'iconColor' => $status === 'warning' ? 'warning' : 'primary',
                        'status' => $status,
                        'duration' => 'persistent',
                        'format' => 'filament',
                        'actions' => [
                            [
                                'name' => 'open_chat',
                                'label' => 'Buka Live Chat',
                                'color' => 'primary',
                                'url' => '/admin/live-chat',
                                'shouldMarkAsRead' => true,
                            ]
                        ]
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Helpdesk notification error: ' . $e->getMessage());
        }
    }
}
