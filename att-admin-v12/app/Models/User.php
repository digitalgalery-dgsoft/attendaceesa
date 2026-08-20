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

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
