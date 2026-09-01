<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withTimestamps();
    }

    public function principals()
    {
        return $this->belongsToMany(Principal::class, 'principal_user')->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin') || $this->hasRole('super_admin');
    }

    public function hasBranchRestriction(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }
        return $this->branches()->exists();
    }

    public function getAccessibleBranchIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }
        return $this->branches()->pluck('branches.id')->toArray();
    }

    public function hasPrincipalRestriction(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }
        return $this->principals()->exists();
    }

    public function getAccessiblePrincipalIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }
        return $this->principals()->pluck('principals.id')->toArray();
    }

    public function isPrincipalUser(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        if ($this->hasRole('Principal PIC') || $this->hasRole('principal_pic') || $this->hasRole('Principal') || $this->hasRole('Client')) {
            return true;
        }

        if ($this->principals()->exists() && !$this->hasRole('Admin') && !$this->hasRole('HR') && !$this->hasRole('Manager')) {
            return true;
        }

        return false;
    }

    public function getRedirectUrlAfterLogin(?string $preferredPrincipalId = null): string
    {
        if ($this->isPrincipalUser()) {
            $principal = null;
            if ($preferredPrincipalId) {
                $principal = $this->principals()->where('principals.id', $preferredPrincipalId)->first()
                           ?: Principal::where('id', $preferredPrincipalId)->where('is_active', true)->first();
            }
            if (!$principal) {
                $principal = $this->principals()->first()
                           ?: Principal::where('is_active', true)->first();
            }

            $request = request();
            $host = $request ? $request->getHost() : '';
            $scheme = ($request && $request->isSecure()) ? 'https://' : 'http://';

            if ($principal && !empty($principal->subdomain)) {
                $subdomain = $principal->subdomain;
                $baseDomain = config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'appsend.my.id';
                $baseDomain = preg_replace('/^www\./', '', $baseDomain ?: 'appsend.my.id');

                if (str_starts_with($host, $subdomain . '.')) {
                    return $preferredPrincipalId ? "/portal?p={$preferredPrincipalId}" : "/portal";
                }

                $subdomainHost = "{$subdomain}.{$baseDomain}";
                $targetUrl = "{$scheme}{$subdomainHost}/portal";
                if ($preferredPrincipalId) {
                    $targetUrl .= "?p={$preferredPrincipalId}";
                }
                return $targetUrl;
            }

            if ($principal) {
                return "/portal?p={$principal->id}";
            }

            return '/portal';
        }

        return '/admin';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
