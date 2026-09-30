<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_cross_day' => 'boolean',
        'required_checkin' => 'boolean',
        'required_checkout' => 'boolean',
        'is_active' => 'boolean',
        'grace_checkin_minutes' => 'integer',
        'grace_checkout_minutes' => 'integer',
    ];

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope query untuk membatasi shift sesuai hak akses user (Prinsiple-scoped).
     * Jika role bukan Administrator, hanya tampil shift sesuai prinsiple user.
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return $query;
        }

        $isAdmin = method_exists($user, 'isAdministrator')
            ? $user->isAdministrator()
            : ($user->isSuperAdmin() || $user->hasRole(['Administrator', 'Admin', 'admin']));

        if ($isAdmin) {
            return $query;
        }

        $principalIds = method_exists($user, 'getAccessiblePrincipalIds')
            ? $user->getAccessiblePrincipalIds()
            : $user->principals()->pluck('principals.id')->toArray();

        if (empty($principalIds) && $user->employee && $user->employee->principal_id) {
            $principalIds = [(int) $user->employee->principal_id];
        }

        if (!empty($principalIds)) {
            return $query->whereIn('shifts.principal_id', $principalIds);
        }

        // Non-administrator tanpa prinsiple terdaftar tidak boleh melihat shift milik prinsiple lain
        return $query->whereRaw('1 = 0');
    }
}
