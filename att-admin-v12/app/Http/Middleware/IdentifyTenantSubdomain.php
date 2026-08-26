<?php

namespace App\Http\Middleware;

use App\Models\Principal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantSubdomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = null;

        // Cek domain format {subdomain}.appsend.my.id atau {subdomain}.localhost / test
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            if (in_array($subdomain, ['www', 'admin', 'api', 'appsend', 'mail'])) {
                $subdomain = null;
            }
        }

        // Atau via route parameter / header / query param jika dalam mode dev/api
        if (!$subdomain) {
            $subdomain = $request->route('subdomain') 
                      ?? $request->header('X-Principal-Subdomain')
                      ?? $request->query('subdomain');
        }

        if ($subdomain) {
            $principals = Principal::where(function ($q) use ($subdomain, $host) {
                $q->where('subdomain', $subdomain)
                  ->orWhere('custom_domain', $host);
            })->where('is_active', true)->get();

            if ($principals->isNotEmpty()) {
                $requestedId = $request->query('p') ?? $request->query('principal_id');
                $primaryTenant = $requestedId ? $principals->firstWhere('id', (int) $requestedId) : null;
                
                if (!$primaryTenant && $request->has('name')) {
                    $requestedName = $request->query('name');
                    $primaryTenant = $principals->first(fn ($p) => stripos($p->name, $requestedName) !== false);
                }

                if (!$primaryTenant) {
                    $primaryTenant = $principals->first();
                }

                $tenantIds = $principals->pluck('id')->toArray();

                app()->instance('current_tenant_principal', $primaryTenant);
                app()->instance('current_tenant_principal_ids', $tenantIds);
                app()->instance('current_tenant_principals_all', $principals);
                $request->attributes->set('tenant_principal', $primaryTenant);
                $request->attributes->set('tenant_principal_ids', $tenantIds);
                $request->attributes->set('tenant_principals_all', $principals);
            }
        }

        return $next($request);
    }
}
