<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\Permit;
use App\Models\VisitReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServerGatewayController extends Controller
{
    /**
     * Discover target server cluster based on NIK or Email.
     * Endpoint: POST /api/v1/gateway/discover
     */
    public function discover(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $nik = trim($request->input('nik'));
        $employee = Employee::where('nik', $nik)
            ->orWhere('no_ktp', $nik)
            ->orWhere('id_card_number', $nik)
            ->first();

        $servers = config('multiserver.servers', []);
        $assignedServerKey = 'server_1'; // default
        $companyName = 'PT Arina Multi Karya';

        if ($employee && $employee->company_name) {
            $companyName = $employee->company_name;
            $empComp = strtolower($companyName);

            foreach ($servers as $key => $serverConfig) {
                foreach ($serverConfig['companies'] as $comp) {
                    if (str_contains($empComp, strtolower($comp)) || str_contains(strtolower($comp), $empComp)) {
                        $assignedServerKey = $key;
                        break 2;
                    }
                }
            }
        }

        $server = $servers[$assignedServerKey] ?? $servers['server_1'];

        return response()->json([
            'status' => 'success',
            'data' => [
                'nik' => $nik,
                'employee_name' => $employee ? $employee->name : null,
                'company_name' => $companyName,
                'assigned_server' => $assignedServerKey,
                'server_name' => $server['name'],
                'api_base_url' => $server['api_base_url'],
                'public_url' => $server['public_url'],
                'media_cdn_url' => config('multiserver.media_cdn_url'),
            ]
        ]);
    }

    /**
     * Unified Login endpoint for Mobile App with Dynamic Server Routing.
     * Endpoint: POST /api/v1/gateway/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // NIK or Email
            'password' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $password = $request->input('password');

        // 1. Cari user / employee lokal
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->orWhereHas('employee', function ($q) use ($login) {
                $q->where('nik', $login)
                  ->orWhere('no_ktp', $login)
                  ->orWhere('id_card_number', $login);
            })->with('employee.company')->first();

        if (!$user) {
            // Cek langsung ke model Employee jika user belum dibuat
            $employee = Employee::where('nik', $login)
                ->orWhere('no_ktp', $login)
                ->orWhere('id_card_number', $login)
                ->first();

            if ($employee && $employee->user_id) {
                $user = User::find($employee->user_id);
            }
        }

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK / Email atau Password salah.',
            ], 401);
        }

        // Generate Sanctum Token
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        // Resolve Target Server for Dynamic Base URL
        $servers = config('multiserver.servers', []);
        $assignedServerKey = 'server_1';
        $companyName = $user->employee->company_name ?? 'ESA Groups';
        $empComp = strtolower($companyName);

        foreach ($servers as $key => $serverConfig) {
            foreach ($serverConfig['companies'] as $comp) {
                if (str_contains($empComp, strtolower($comp)) || str_contains(strtolower($comp), $empComp)) {
                    $assignedServerKey = $key;
                    break 2;
                }
            }
        }

        $server = $servers[$assignedServerKey] ?? $servers['server_1'];

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_superadmin' => method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false,
                ],
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'nik' => $user->employee->nik,
                    'name' => $user->employee->name,
                    'company_name' => $user->employee->company_name,
                    'position' => $user->employee->position ?? $user->employee->job_position ?? null,
                    'department' => $user->employee->department ?? null,
                    'is_supervisor' => Employee::where('parent_id', $user->employee->id)->orWhere('supervisor_nik', $user->employee->nik)->exists(),
                ] : null,
                'routing' => [
                    'assigned_server' => $assignedServerKey,
                    'server_name' => $server['name'],
                    'api_base_url' => $server['api_base_url'],
                    'media_cdn_url' => config('multiserver.media_cdn_url'),
                ]
            ]
        ]);
    }

    /**
     * Get Cross-Entity Subordinates for a Supervisor / Head.
     * Endpoint: GET /api/v1/cross-entity/subordinates
     */
    public function crossEntitySubordinates(Request $request)
    {
        $user = $request->user();
        $employee = $user ? $user->employee : null;

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found for this user.'
            ], 404);
        }

        // Subordinates by parent_id or supervisor_nik across all companies in local database
        $subordinates = Employee::where(function ($q) use ($employee) {
            $q->where('parent_id', $employee->id)
              ->orWhere('supervisor_nik', $employee->nik)
              ->orWhere('supervisor_nik', $employee->no_ktp);
        })
        ->select(['id', 'nik', 'name', 'company_name', 'position', 'phone', 'is_active', 'photo'])
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'supervisor' => [
                    'nik' => $employee->nik,
                    'name' => $employee->name,
                    'company' => $employee->company_name,
                ],
                'total_subordinates' => $subordinates->count(),
                'subordinates' => $subordinates,
            ]
        ]);
    }

    /**
     * Cross-Entity Approval Dispatcher (Cuti / Lembur / Izin / Visit Report).
     * Endpoint: POST /api/v1/cross-entity/approve
     */
    public function crossEntityApproval(Request $request)
    {
        $request->validate([
            'type' => 'required|in:permit,visit_report,itinerary',
            'id' => 'required|integer',
            'action' => 'required|in:approve,reject',
            'note' => 'nullable|string',
        ]);

        $user = $request->user();
        $type = $request->input('type');
        $id = $request->input('id');
        $action = $request->input('action');
        $note = $request->input('note', '');

        if ($type === 'permit') {
            $permit = Permit::find($id);
            if (!$permit) {
                return response()->json(['status' => 'error', 'message' => 'Permit not found.'], 404);
            }

            $permit->status = ($action === 'approve') ? 'approved' : 'rejected';
            $permit->approved_by = $user->id;
            $permit->approved_at = now();
            if ($note) {
                $permit->approval_note = $note;
            }
            $permit->save();

            return response()->json([
                'status' => 'success',
                'message' => "Permit has been {$action}d successfully.",
                'data' => $permit
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Approval for {$type} #{$id} processed ({$action})."
        ]);
    }
}
