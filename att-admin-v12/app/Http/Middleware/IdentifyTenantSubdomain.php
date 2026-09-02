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
        // 0. Jangan jalankan tenant identification jika sedang di halaman install atau belum terinstall
        if ($request->is('install') || $request->is('install/*') || !file_exists(storage_path('app/.installed'))) {
            return $next($request);
        }

        $host = $request->getHost();
        $subdomain = null;

        // 1. Cek domain format {subdomain}.esa-solutions.id atau {subdomain}.appsend.my.id atau {subdomain}.localhost / test
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            if (in_array($subdomain, ['www', 'admin', 'api', 'appsend', 'mail', 'amk', 'akp', 'atk'])) {
                $subdomain = null;
            }
        }

        // 2. Atau via route parameter / header / query param jika dalam mode dev/api
        if (!$subdomain) {
            $subdomain = $request->route('subdomain') 
                      ?? $request->header('X-Principal-Subdomain')
                      ?? $request->query('subdomain');
        }

        // 3. Jika tidak ada subdomain dari host, cek apakah ada query 'p' atau 'principal_id'
        $requestedId = $request->query('p') ?? $request->query('principal_id');

        if (!$subdomain && $requestedId) {
            $pRecord = Principal::where('id', (int) $requestedId)->where('is_active', true)->first();
            if ($pRecord && !empty($pRecord->subdomain)) {
                $subdomain = $pRecord->subdomain;
            }
        }

        // 4. Jika user terautentikasi dan merupakan user prinsiple, cek subdomain dari relasi prinsiple-nya
        if (!$subdomain && auth()->check()) {
            $user = auth()->user();
            if ($user && method_exists($user, 'principals') && $user->principals()->exists()) {
                $userPrinc = $requestedId 
                    ? $user->principals()->where('principals.id', (int) $requestedId)->first() 
                    : $user->principals()->first();
                if ($userPrinc && !empty($userPrinc->subdomain)) {
                    $subdomain = $userPrinc->subdomain;
                }
            }
        }

        $principals = collect();

        try {
            if ($subdomain) {
                $principals = Principal::where(function ($q) use ($subdomain, $host) {
                    $q->where('subdomain', $subdomain)
                      ->orWhere('custom_domain', $host);
                })->where('is_active', true)->orderBy('id')->get();
            } elseif ($requestedId) {
                $pRecord = Principal::where('id', (int) $requestedId)->where('is_active', true)->first();
                if ($pRecord) {
                    $principals = Principal::where('name', $pRecord->name)
                        ->orWhere('id', $pRecord->id)
                        ->where('is_active', true)
                        ->get();
                }
            }

            // Jika user login punya relasi banyak prinsiple, pastikan juga di-include jika relevan
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && method_exists($user, 'principals') && $user->principals()->exists()) {
                    $userPrincipals = $user->principals()->where('is_active', true)->get();
                    if ($principals->isEmpty()) {
                        $principals = $userPrincipals;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Database not ready or table does not exist
            $principals = collect();
        }

        if ($principals->isNotEmpty()) {
            $sessionKey = 'tenant_principal_id_' . ($subdomain ?: 'global');

            if ($requestedId && $request->hasSession()) {
                $request->session()->put($sessionKey, (int) $requestedId);
            } elseif (!$requestedId && $request->hasSession()) {
                $requestedId = $request->session()->get($sessionKey);
            }

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

        return $next($request);
    }
}
