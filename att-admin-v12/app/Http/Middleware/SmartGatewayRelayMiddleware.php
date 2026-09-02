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

        // Jangan intercept route login, ping, atau endpoint sinkronisasi publik/khusus
        if ($request->is('api/login') || $request->is('api/v1/auth/login') || $request->is('api/v1/sync/*')) {
            return $next($request);
        }

        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $targetServer = SmartGatewayRelayService::resolveTargetServer($bearerToken);
            if ($targetServer) {
                // Token berasal dari cluster peer server (AKP / ATK), teruskan request secara transparan
                return SmartGatewayRelayService::relayRequest($request, $targetServer);
            }
        }

        return $next($request);
    }
}
