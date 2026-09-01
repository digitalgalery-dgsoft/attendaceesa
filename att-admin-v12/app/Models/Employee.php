<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Principal;
use App\Models\ReportTemplate;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes, Notifiable;
    
    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'tokens'];

    protected $casts = [
        'odoo_id' => 'integer',
    ];

    protected $appends = ['has_reporting_templates', 'is_inhouse'];

    protected static function booted(): void
    {
        static::saved(function ($employee) {
            if ($employee->principal_id) {
                $employee->principal?->syncActiveStatus();
            }
            if ($employee->isDirty('principal_id')) {
                $oldPrincipalId = $employee->getOriginal('principal_id');
                if ($oldPrincipalId) {
                    Principal::find($oldPrincipalId)?->syncActiveStatus();
                }
            }

            // Sinkronkan password ke seluruh record karyawan dengan NIK / Email sama
            if ($employee->isDirty('password') && !empty($employee->password)) {
                $pwd = $employee->password;
                if (!empty($employee->employee_no)) {
                    static::where('employee_no', $employee->employee_no)
                        ->where('id', '!=', $employee->id)
                        ->update(['password' => $pwd]);
                }
                if (!empty($employee->email)) {
                    static::where('email', $employee->email)
                        ->where('id', '!=', $employee->id)
                        ->update(['password' => $pwd]);
                }
                if ($employee->user_id) {
                    \App\Models\User::where('id', $employee->user_id)->update(['password' => $pwd]);
                }
            }

            // Sinkronkan unlock device ke seluruh record karyawan dengan NIK sama jika device_id di-reset
            if ($employee->isDirty('device_id') && empty($employee->device_id)) {
                if (!empty($employee->employee_no)) {
                    static::where('employee_no', $employee->employee_no)
                        ->where('id', '!=', $employee->id)
                        ->update(['device_id' => null, 'device_name' => null, 'fcm_token' => null]);
                }
            }
        });

        static::deleted(function ($employee) {
            if ($employee->principal_id) {
                $employee->principal?->syncActiveStatus();
            }
        });

        static::restored(function ($employee) {
            if ($employee->principal_id) {
                $employee->principal?->syncActiveStatus();
            }
        });
    }

    public function getIsInhouseAttribute(): bool
    {
        if ($this->department && str_contains(strtolower($this->department->name), 'inhouse')) {
            return true;
        }
        if (!$this->principal_id) {
            return true;
        }
        if ($this->principal && $this->company && strtolower($this->principal->name) === strtolower($this->company->name)) {
            return true;
        }
        return false;
    }

    public function getHasReportingTemplatesAttribute(): bool
    {
        if (!$this->principal_id) {
            return false;
        }

        $principal = $this->principal;
        $allMatchingPrincipalIds = [$this->principal_id];
        if ($principal && !empty($principal->subdomain)) {
            $allMatchingPrincipalIds = Principal::where('subdomain', $principal->subdomain)->pluck('id')->toArray();
        }

        return ReportTemplate::where('is_active', true)
            ->where(function ($q) use ($allMatchingPrincipalIds) {
                $q->whereHas('principals', function ($pq) use ($allMatchingPrincipalIds) {
                    $pq->whereIn('principals.id', $allMatchingPrincipalIds);
                })
                ->orWhereIn('principal_id', $allMatchingPrincipalIds);
            })
            ->exists();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    public function getNikAttribute(): ?string
    {
        return $this->employee_no;
    }

    /**
     * Get effective GPS radius in meters for this employee at a given location.
     * Priority: Position radius (distance_lock_override) -> WorkLocation radius (radius_meter) -> Default 100m.
     */
    public function getEffectiveRadiusForLocation(?WorkLocation $location = null): int
    {
        if ($this->position && !empty($this->position->distance_lock_override) && (int) $this->position->distance_lock_override > 0) {
            return (int) $this->position->distance_lock_override;
        }

        if ($location && !empty($location->radius_meter)) {
            return (int) $location->radius_meter;
        }

        if ($this->workLocation && !empty($this->workLocation->radius_meter)) {
            return (int) $this->workLocation->radius_meter;
        }

        return 100;
    }
}
