<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Masuk ke Admin Panel';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Sistem Presensi & Manajemen Kinerja Terintegrasi';
    }
}
