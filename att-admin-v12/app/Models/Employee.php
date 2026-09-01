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
        static::saving(function ($employee) {
            $nik = $employee->employee_no ?? $employee->nik;
            // Pastikan hanya ada 1 record NIK yang berstatus aktif di sistem
            if ($employee->is_active && !empty($nik)) {
                static::withoutEvents(function () use ($employee, $nik) {
                    static::where('employee_no', $nik)
                        ->where('id', '!=', $employee->id ?? 0)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'employment_status' => 'resigned',
                        ]);
                });
            }
        });

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

            // Sinkronkan password ke akun User terkait jika terhubung
            if ($employee->isDirty('password') && !empty($employee->password) && $employee->user_id) {
                \App\Models\User::where('id', $employee->user_id)->update(['password' => $employee->password]);
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

    /**
     * Deduplicate active employee records by NIK (employee_no).
     * Keeps the most recently updated / linked record active and marks duplicates as resigned.
     */
    public static function deduplicateActiveRecords(): int
    {
        $duplicateNiks = \Illuminate\Support\Facades\DB::table('employees')
            ->select('employee_no')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->groupBy('employee_no')
            ->havingRaw('count(*) > 1')
            ->pluck('employee_no');

        $totalDeactivated = 0;

        foreach ($duplicateNiks as $nik) {
            $records = \Illuminate\Support\Facades\DB::table('employees')
                ->where('employee_no', $nik)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByRaw("CASE WHEN employment_status != 'resigned' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            if ($records->count() > 1) {
                // Record pertama dipertahankan sebagai aktif
                $primary = $records->shift();

                // Sisa record duplikat dinonaktifkan
                $duplicateIds = $records->pluck('id')->toArray();
                $totalDeactivated += count($duplicateIds);

                \Illuminate\Support\Facades\DB::table('employees')
                    ->whereIn('id', $duplicateIds)
                    ->update([
                        'is_active' => false,
                        'employment_status' => 'resigned',
                        'updated_at' => now(),
                    ]);
            }
        }

        return $totalDeactivated;
    }
}
