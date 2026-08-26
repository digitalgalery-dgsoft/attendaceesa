<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Principal;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        $tenantPrincipal = $request->attributes->get('tenant_principal')
                        ?? (app()->bound('current_tenant_principal') ? app('current_tenant_principal') : null);

        if (!$tenantPrincipal) {
            $subdomain = $request->route('subdomain') ?? $request->query('subdomain');
            if ($subdomain) {
                $tenantPrincipal = Principal::where('subdomain', $subdomain)->where('is_active', true)->first();
            }
        }

        if (!$tenantPrincipal) {
            $tenantPrincipal = Principal::where('is_active', true)->first();
        }

        if (Auth::check()) {
            return redirect()->route('portal.dashboard', $request->query('p') ? ['p' => $request->query('p')] : []);
        }

        $tenantPrincipalsAll = $request->attributes->get('tenant_principals_all')
                            ?? (app()->bound('current_tenant_principals_all') ? app('current_tenant_principals_all') : collect([$tenantPrincipal]));

        $setting = Setting::first();

        return view('auth.tenant_login', compact('tenantPrincipal', 'tenantPrincipalsAll', 'setting'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $p = $request->input('p') ?? $request->query('p');
            $request->session()->regenerate();
            if ($p) {
                $parts = explode('.', $request->getHost());
                $subdomain = count($parts) >= 3 ? $parts[0] : 'wings';
                $request->session()->put('tenant_principal_id_' . $subdomain, (int) $p);
            }

            return redirect()->intended(route('portal.dashboard', $p ? ['p' => $p] : []));
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi yang Anda masukkan tidak cocok.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
