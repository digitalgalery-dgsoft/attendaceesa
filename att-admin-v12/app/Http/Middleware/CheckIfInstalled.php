<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ignore API routes or webhooks if necessary, but for full protection we apply everywhere except /install
        if (!file_exists(storage_path('app/.installed'))) {
            // Check if current route is not an installation route
            if (!$request->is('install') && !$request->is('install/*') && !$request->is('_debugbar/*')) {
                return redirect()->route('install.index');
            }
        }

        return $next($request);
    }
}
