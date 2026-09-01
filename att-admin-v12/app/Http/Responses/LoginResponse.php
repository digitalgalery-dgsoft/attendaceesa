<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'isPrincipalUser') && $user->isPrincipalUser()) {
            $p = $request->input('p') ?? $request->query('p');
            $redirectUrl = $user->getRedirectUrlAfterLogin($p);
            return redirect()->intended($redirectUrl);
        }

        return redirect()->intended(filament()->getUrl());
    }
}
