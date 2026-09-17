<?php

namespace App\Http\Middleware;

use App\Services\SmartGatewayRelayService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SmartGatewayRelayMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya cek untuk route API
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Keamanan Ketat: Cegah login dan aktivitas mobile presensi pada domain staging (appsend.my.id)
        $host = $request->getHost();
        if (str_contains($host, 'appsend.my.id') || $host === '43.129.41.93') {
            // Izinkan sinkronisasi antar cluster dan status ping
            if (!$request->is('api/v1/sync/*') && !$request->is('api/ping') && !$request->is('api/health')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akses ditolak: Server staging (appsend.my.id) dinonaktifkan untuk aktivitas mobile presensi. Presensi hanya diizinkan melalui server production resmi (*.esa-solutions.id).'
                ], 403);
            }
        }

        // PENTING: Jika request ini adalah hasil relay dari peer server (ada header X-ESA-Gateway-Relay),
        // JANGAN PERNAH di-relay lagi untuk mencegah infinite recursive storm antar server cluster.
        if ($request->hasHeader('X-ESA-Gateway-Relay') || $request->header('X-ESA-Gateway-Relay') === '1') {
            return $next($request);
        }

        // Jangan intercept route login, ping, telemetry, atau endpoint sinkronisasi publik/khusus
        if ($request->is('api/login') || $request->is('api/v1/auth/login') || $request->is('api/v1/sync/*') || $request->is('api/v1/system/*') || $request->is('api/ping') || $request->is('api/health')) {
            return $next($request);
        }

        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $targetInfo = SmartGatewayRelayService::resolveTargetServer($bearerToken);
            if ($targetInfo) {
                // Token berasal dari cluster peer server (AKP / ATK), teruskan request secara transparan
                return SmartGatewayRelayService::relayRequest($request, $targetInfo);
            }
        }

        return $next($request);
    }
}
