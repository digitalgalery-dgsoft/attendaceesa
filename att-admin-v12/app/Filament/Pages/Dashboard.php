<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'isPrincipalUser') && $user->isPrincipalUser()) {
            $this->redirect($user->getRedirectUrlAfterLogin(), navigate: false);
        }
    }
}
