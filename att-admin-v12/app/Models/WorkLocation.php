<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    use HasFactory;



    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get effective GPS radius in meters for a specific employee.
     * Priority: Position radius (distance_lock_override) -> WorkLocation radius (radius_meter) -> Default 100m.
     */
    public function getEffectiveRadiusForEmployee(?Employee $employee = null): int
    {
        if ($employee && $employee->position && !empty($employee->position->distance_lock_override) && (int) $employee->position->distance_lock_override > 0) {
            return (int) $employee->position->distance_lock_override;
        }

        return (int) ($this->radius_meter ?? 100);
    }
}
