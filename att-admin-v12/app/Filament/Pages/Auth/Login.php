<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Masuk ke Sistem';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Portal Terintegrasi Admin & Reporting Prinsiple';
    }
}
