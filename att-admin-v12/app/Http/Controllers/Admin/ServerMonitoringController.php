<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServerTelemetryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ServerMonitoringController extends Controller
{
    /**
     * Dashboard View (terintegrasi dalam portal/admin)
     */
    public function index(Request $request)
    {
        $this->checkAuthorization($request);

        $nodeId = $request->query('node', 'all');
        $initialData = ServerTelemetryService::getAllNodesMetrics();
        $definitions = ServerTelemetryService::getNodeDefinitions();

        return view('admin.server_monitoring', [
            'initialData' => $initialData,
            'definitions' => $definitions,
            'activeNode' => $nodeId,
            'isStandalone' => false,
        ]);
    }

    /**
     * Standalone NOC Full-Screen View
     */
    public function standalone(Request $request)
    {
        // Token check or logged in user
        $token = $request->query('token');
        if ($token !== ServerTelemetryService::SECRET_TOKEN && !Auth::check()) {
            abort(403, 'Akses Ditolak: Diperlukan otentikasi login atau token rahasia NOC.');
        }

        $nodeId = $request->query('node', 'all');
        $initialData = ServerTelemetryService::getAllNodesMetrics();
        $definitions = ServerTelemetryService::getNodeDefinitions();

        return view('admin.server_monitoring', [
            'initialData' => $initialData,
            'definitions' => $definitions,
            'activeNode' => $nodeId,
            'isStandalone' => true,
        ]);
    }

    /**
     * API Endpoint: Telemetry Metrics JSON
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $token = $request->query('token') ?: $request->header('X-Monitoring-Token');
        $isTokenValid = ($token === ServerTelemetryService::SECRET_TOKEN);
        $isAuth = false;

        if (!$isTokenValid) {
            try {
                $isAuth = Auth::check();
            } catch (\Throwable $e) {
                $isAuth = false;
            }
        }

        if (!$isTokenValid && !$isAuth) {
            return response()->json(['error' => 'Unauthorized access'], 401);
        }

        if ($request->query('local_only') === '1') {
            $nodeId = $request->query('node_id');
            return response()->json(ServerTelemetryService::getLocalMetrics($nodeId));
        }

        $data = ServerTelemetryService::getAllNodesMetrics();
        return response()->json($data);
    }

    /**
     * API Endpoint: Eksekusi Tindakan Pemeliharaan
     */
    public function executeAction(Request $request): JsonResponse
    {
        $token = $request->input('token') ?: $request->header('X-Monitoring-Token');
        $isTokenValid = ($token === ServerTelemetryService::SECRET_TOKEN);
        $isAuth = false;

        if (!$isTokenValid) {
            try {
                $isAuth = Auth::check();
            } catch (\Throwable $e) {
                $isAuth = false;
            }
        }

        if (!$isTokenValid && !$isAuth) {
            return response()->json(['error' => 'Unauthorized action'], 401);
        }

        $action = $request->input('action', '');
        $result = ServerTelemetryService::executeAction($action);

        return response()->json($result);
    }

    /**
     * Authorize user access
     */
    protected function checkAuthorization(Request $request): void
    {
        $token = $request->query('token') ?: $request->header('X-Monitoring-Token');
        if ($token === ServerTelemetryService::SECRET_TOKEN) {
            return;
        }

        $user = null;
        try {
            $user = Auth::user();
        } catch (\Throwable $e) {
            $user = null;
        }

        if (!$user) {
            abort(403, 'Sesi telah berakhir atau Anda belum login.');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        if (method_exists($user, 'can') && ($user->can('manage_settings') || $user->can('view_settings'))) {
            return;
        }

        // Allow administrator
        if ($user->role === 'admin' || $user->role === 'superadmin') {
            return;
        }

        abort(403, 'Anda tidak memiliki hak akses untuk membuka halaman Server Monitoring.');
    }
}
